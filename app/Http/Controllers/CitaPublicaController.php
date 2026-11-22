<?php

namespace App\Http\Controllers;

use App\Models\HorarioAtencion;
use App\Models\Oferta;
use App\Models\Servicio;
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

        return view('citas-publicas.create', [
            'servicios' => $servicios,
            'servicioInicial' => $servicioInicial,
            'tratamientoInicial' => $tratamientoInicial,
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
            'acepta_aviso' => ['accepted'],
        ], [
            'telefono.regex' => 'Ingresa exactamente 10 dígitos numéricos de México.',
            'acepta_aviso.accepted' => 'Para agendar necesitamos que aceptes el aviso de privacidad.',
        ]);

        $resultado = $crearCita->crear(['acepta_aviso' => true] + $validated, 'publico', request: $request);
        $cita = $resultado['cita'];

        if (! $cita) {
            return back()->withErrors(['hora' => 'Ese horario ya no está disponible. Por favor elige otro.']);
        }

        if ($resultado['creada']) {
            $notificaciones->citaConfirmada($cita->load(['paciente', 'tratamiento']));
        }

        $cita->loadMissing('tratamiento');
        $fechaHora = $cita->fecha_hora->copy()->locale('es');

        $cita->loadMissing('oferta');

        return Redirect::route('citas.publica.create')
            ->with('success', 'Tu cita quedó confirmada. Te esperamos.')
            ->with('cita_resumen', [
                'tratamiento' => $cita->tratamiento?->nombre,
                'oferta' => $cita->oferta ? "{$cita->oferta->titulo} ({$cita->oferta->etiquetaBeneficio()})" : null,
                'precio' => $cita->tratamiento?->precioFormateado(),
                'fecha' => $fechaHora->translatedFormat('l j \d\e F \d\e Y'),
                'hora' => $fechaHora->format('H:i'),
            ]);
    }

    /**
     * Oferta publicada y con lugar de cada tratamiento (21/11/2026), para avisar
     * al paciente al elegirlo. La que vence primero si hay varias. El servidor
     * vuelve a revisarla al guardar la cita (CrearCitaService).
     *
     * @return array<int, array{titulo: string, etiqueta: string, hasta: string, hasta_legible: string}>
     */
    private function ofertasPorTratamiento(): array
    {
        return Oferta::publicadas()
            ->whereNotNull('id_tratamiento')
            ->conLugaresUsados()
            ->orderBy('fecha_fin')
            ->get()
            ->reject(fn (Oferta $oferta) => $oferta->lugaresQuedan() === 0)
            ->unique('id_tratamiento')
            ->mapWithKeys(fn (Oferta $oferta) => [$oferta->id_tratamiento => [
                'titulo' => $oferta->titulo,
                'etiqueta' => $oferta->etiquetaBeneficio(),
                'hasta' => $oferta->fecha_fin->toDateString(),
                'hasta_legible' => $oferta->fecha_fin->locale('es')->translatedFormat('j \d\e F'),
            ]])
            ->all();
    }
}
