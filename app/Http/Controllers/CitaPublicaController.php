<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\HorarioAtencion;
use App\Models\Oferta;
use App\Models\Servicio;
use App\Services\RespuestaRecordatorioService;
use App\Models\NotificacionSistema;
use App\Services\CrearCitaService;
use App\Services\NotificacionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class CitaPublicaController extends Controller
{
    public function create(Request $request): View
    {
        // Solo especialidades con al menos un tratamiento activo: no se ofrece
        // al paciente algo que no puede agendar (casos extremos, Fase 4).
        $servicios = Servicio::where('activo', true)
            ->whereHas('tratamientos', fn ($q) => $q->where('activo', true))
            ->with(['tratamientos' => fn ($q) => $q->where('activo', true)])
            ->orderBy('orden')
            ->get();

        // ?servicio=ID llega desde la portada: la agenda abre con esa especialidad
        // elegida, solo si es una de las que se pueden agendar en línea.
        $servicioInicial = $servicios->firstWhere('id', (int) $request->query('servicio'))?->id;

        // ?tratamiento=ID llega desde "Agendar" en una oferta de la portada: abre
        // con su especialidad y ese tratamiento ya elegidos (21/11/2026).
        $tratamientoInicial = null;
        if ($idTratamiento = (int) $request->query('tratamiento')) {
            $servicioDelTratamiento = $servicios->first(fn ($s) => $s->tratamientos->contains('id', $idTratamiento));
            if ($servicioDelTratamiento) {
                $servicioInicial = $servicioDelTratamiento->id;
                $tratamientoInicial = $idTratamiento;
            }
        }

        // ?oferta=ID (23/11/2026): la oferta solo se aplica si el paciente entró
        // desde su tarjeta en la portada; agendar el tratamiento por el camino
        // normal ya no la aplica.
        $ofertaInicial = null;
        if ($tratamientoInicial && ($idOferta = (int) $request->query('oferta'))) {
            $ofertaInicial = Oferta::publicadas()->whereKey($idOferta)->where('id_tratamiento', $tratamientoInicial)->value('id');
        }

        // Link "Reagendar" del recordatorio (23/11/2026): solo si la firma es válida,
        // abre en el calendario con el mismo tratamiento y los datos del paciente.
        $reagendar = null;
        if ($request->filled('reagendar') && $request->hasValidSignature()) {
            $anterior = Cita::with(['paciente', 'tratamiento'])->find((int) $request->query('reagendar'));
            $servicioAnterior = $anterior ? $servicios->first(fn ($s) => $s->tratamientos->contains('id', $anterior->id_tratamiento)) : null;

            if ($anterior?->paciente && $servicioAnterior) {
                $servicioInicial = $servicioAnterior->id;
                $tratamientoInicial = $anterior->id_tratamiento;
                // Conserva la oferta de la cita original (si sigue vigente); no gana una nueva.
                $ofertaInicial = $anterior->id_oferta;
                $reagendar = [
                    'tratamiento' => $anterior->tratamiento->nombre,
                    'token' => RespuestaRecordatorioService::tokenReagendar($anterior),
                    // Al reagendar solo se cambian día y hora (23/11/2026): los datos no
                    // se vuelven a escribir. El aviso solo si nunca lo aceptó (paciente
                    // registrado por el personal).
                    'sin_datos' => true,
                    'pide_aviso' => $anterior->paciente->aviso_privacidad_aceptado_en === null,
                    'datos' => [
                        'nombre' => $anterior->paciente->nombre,
                        'apellido' => $anterior->paciente->apellido,
                        'telefono' => $anterior->paciente->telefono,
                        'email' => $anterior->paciente->email,
                    ],
                ];
            }
        }

        return view('citas-publicas.create', [
            'servicios' => $servicios,
            'servicioInicial' => $servicioInicial,
            'tratamientoInicial' => $tratamientoInicial,
            'ofertaInicial' => $ofertaInicial,
            'reagendar' => $reagendar,
            'ofertasPorTratamiento' => $this->ofertasPorTratamiento(),
            'horarioRegular' => HorarioAtencion::resumenSemanal(),
        ]);
    }

    public function store(Request $request, CrearCitaService $crearCita, NotificacionService $notificaciones): RedirectResponse
    {
        // El formulario muestra el teléfono como "755 113 1833" para leerlo mejor;
        // solo se quitan los espacios: cualquier otro carácter sigue siendo inválido.
        if ($request->has('telefono')) {
            $request->merge(['telefono' => preg_replace('/\s+/', '', (string) $request->input('telefono'))]);
        }

        // Viene del link "Reagendar" del recordatorio (23/11/2026): la cita nueva
        // se conecta con la vieja y el paciente recibe "tu cita se movió". Si ya
        // aceptó el aviso de privacidad, no se le vuelve a pedir.
        $anterior = RespuestaRecordatorioService::citaAReagendar($request->input('reagendar'), (string) $request->input('telefono'));
        $avisoPrevio = $anterior !== null && $anterior->paciente?->aviso_privacidad_aceptado_en !== null;
        if ($anterior) {
            // Solo se cambian día y hora: lo demás sale de la cita vieja aunque el
            // formulario mande otra cosa.
            $request->merge([
                'id_servicio' => $anterior->id_servicio,
                'id_tratamiento' => $anterior->id_tratamiento,
                'nombre' => $anterior->paciente->nombre,
                'apellido' => $anterior->paciente->apellido,
                'telefono' => preg_replace('/\D/', '', (string) $anterior->paciente->telefono),
                'email' => $anterior->paciente->email,
                'id_oferta' => $anterior->id_oferta,
            ]);
        }

        $validated = $request->validate([
            'id_servicio' => ['required', 'exists:servicios,id'],
            'id_tratamiento' => ['required', 'exists:tratamientos,id'],
            'fecha' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'telefono' => [
                'required',
                'string',
                'size:10',
                'regex:/^[0-9]{10}$/',
            ],
            // Opcional: hay pacientes sin correo; el celular sí es obligatorio (WhatsApp).
            'email' => ['nullable', 'email', 'max:100'],
            'motivo' => ['nullable', 'string', 'max:1000'],
            // Solo si entró desde la oferta (o la conserva al reagendar).
            'id_oferta' => ['nullable', 'integer'],
            'acepta_aviso' => [$avisoPrevio ? 'nullable' : 'accepted'],
        ], [
            'telefono.regex' => 'Ingresa exactamente 10 dígitos numéricos de México.',
            'acepta_aviso.accepted' => 'Para agendar necesitamos que aceptes el aviso de privacidad.',
        ]);

        $resultado = $crearCita->crear(['acepta_aviso' => true, 'aviso_previo' => $avisoPrevio] + $validated, 'publico', request: $request,
            eventoInterno: $anterior ? 'reagendada_paciente' : 'confirmada');
        $cita = $resultado['cita'];

        if (! $cita) {
            return back()->withErrors(['hora' => 'Ese horario ya no está disponible. Por favor elige otro.']);
        }

        $reagendada = false;
        if ($resultado['creada'] && $anterior) {
            $reagendada = Cita::whereKey($anterior->id)->whereNull('id_cita_nueva')->update(['id_cita_nueva' => $cita->id]) === 1;
        }

        if ($resultado['creada']) {
            if ($reagendada) {
                // El aviso "Horario liberado" ya quedó resuelto: el paciente eligió.
                NotificacionSistema::where('id_cita', $anterior->id)->where('evento', 'reagendar_paciente')
                    ->whereNull('atendida_en')->update(['atendida_en' => now()]);
                $notificaciones->citaReagendada($cita->load(['paciente', 'tratamiento']));
            } else {
                $notificaciones->citaConfirmada($cita->load(['paciente', 'tratamiento']));
            }
        }

        $cita->loadMissing('tratamiento');
        $fechaHora = $cita->fecha_hora->copy()->locale('es');

        $cita->loadMissing('oferta');

        return Redirect::route('citas.publica.create')
            ->with('success', $reagendada ? 'Tu cita quedó reagendada. Te esperamos en el nuevo horario.' : 'Tu cita quedó confirmada. Te esperamos.')
            ->with('cita_resumen', [
                'tratamiento' => $cita->tratamiento?->nombre,
                'oferta' => $cita->oferta ? "{$cita->oferta->titulo} ({$cita->oferta->etiquetaBeneficio()})" : null,
                'precio' => $cita->tratamiento?->precioFormateado(),
                'fecha' => $fechaHora->translatedFormat('l j \d\e F \d\e Y'),
                'hora' => $fechaHora->format('H:i'),
            ]);
    }

    /**
     * Ofertas publicadas y con lugar de cada tratamiento, de la que vence
     * primero a la última, para avisar al paciente al elegirlo. La pantalla
     * muestra la primera que sigue vigente en la fecha elegida: la misma regla
     * que Oferta::aplicableA() usa al guardar la cita (22/11/2026; antes se
     * mandaba solo la primera y, con una fecha posterior, la pantalla decía
     * "ya no aplica" aunque al guardar se aplicara otra).
     *
     * @return array<int, list<array{titulo: string, etiqueta: string, hasta: string, hasta_legible: string}>>
     */
    private function ofertasPorTratamiento(): array
    {
        return Oferta::publicadas()
            ->whereNotNull('id_tratamiento')
            ->conLugaresUsados()
            ->orderBy('fecha_fin')
            ->get()
            ->reject(fn (Oferta $oferta) => $oferta->lugaresQuedan() === 0)
            ->groupBy('id_tratamiento')
            ->map(fn ($ofertas) => $ofertas->map(fn (Oferta $oferta) => [
                'id' => $oferta->id,
                'titulo' => $oferta->titulo,
                'etiqueta' => $oferta->etiquetaBeneficio(),
                'hasta' => $oferta->fecha_fin->toDateString(),
                'hasta_legible' => $oferta->fecha_fin->locale('es')->translatedFormat('j \d\e F'),
            ])->values()->all())
            ->all();
    }
}
