<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatosConsultorio;
use App\Rules\TelefonoMexico;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

/**
 * Configuración → Datos del consultorio (22/11/2026). La doctora cambia el
 * teléfono, el WhatsApp de contacto, el correo y la dirección sin depender de
 * las variables de Railway (por ejemplo si pierde o le roban el celular).
 * Solo administradores; cada cambio pide confirmación y queda en Auditoría
 * con el valor anterior y el nuevo.
 */
class DatosConsultorioController extends Controller
{
    private const CAMPOS = ['telefono', 'whatsapp', 'correo', 'direccion', 'nombre_maps', 'mision', 'vision', 'instagram', 'facebook', 'tiktok'];

    // La pantalla vive en Configuración (ConfiguracionController); aquí solo se guarda.

    public function update(Request $request, AuditoriaService $auditoria): RedirectResponse
    {
        // Se aceptan espacios y guiones ("755 113 1833"); se guardan solo los dígitos.
        $request->merge([
            'telefono' => preg_replace('/\D/', '', (string) $request->input('telefono')),
            'whatsapp' => preg_replace('/\D/', '', (string) $request->input('whatsapp')),
        ]);

        $datos = $request->validate([
            'telefono' => ['required', 'digits:10', new TelefonoMexico],
            'mismo_whatsapp' => ['boolean'],
            'whatsapp' => ['exclude_if:mismo_whatsapp,1', 'required', 'digits:10', new TelefonoMexico],
            'correo' => ['nullable', 'email', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'nombre_maps' => ['nullable', 'string', 'max:100'],
            'mision' => ['nullable', 'string', 'max:400'],
            'vision' => ['nullable', 'string', 'max:400'],
            // 27/11/2026: redes sociales del pie de la portada.
            'instagram' => ['nullable', 'url:https', 'max:200', 'regex:#^https://(www\.)?instagram\.com/#i'],
            'facebook' => ['nullable', 'url:https', 'max:200', 'regex:#^https://(www\.|m\.)?facebook\.com/#i'],
            'tiktok' => ['nullable', 'url:https', 'max:200', 'regex:#^https://(www\.)?tiktok\.com/#i'],
        ], [
            'telefono.digits' => 'Escribe los 10 dígitos del celular, por ejemplo 755 113 1833.',
            'whatsapp.digits' => 'Escribe los 10 dígitos del WhatsApp, por ejemplo 755 113 1833.',
            'instagram.regex' => 'Pega el enlace de tu perfil, por ejemplo https://www.instagram.com/tu_consultorio.',
            'facebook.regex' => 'Pega el enlace de tu página, por ejemplo https://www.facebook.com/tu_consultorio.',
            'tiktok.regex' => 'Pega el enlace de tu perfil, por ejemplo https://www.tiktok.com/@tu_consultorio.',
        ], [
            'telefono' => 'teléfono',
            'whatsapp' => 'WhatsApp',
            'correo' => 'correo',
            'direccion' => 'dirección',
            'nombre_maps' => 'nombre en Google Maps',
            'mision' => 'misión',
            'vision' => 'visión',
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            'tiktok' => 'TikTok',
        ]);

        $nuevos = [
            'telefono' => DatosConsultorio::formatear($datos['telefono']),
            // "El mismo que el teléfono": vacío, y se toma del teléfono.
            'whatsapp' => $request->boolean('mismo_whatsapp') ? null : '52'.$datos['whatsapp'],
            'correo' => $datos['correo'] ?? null,
            'direccion' => $datos['direccion'] ?? null,
            'nombre_maps' => filled($datos['nombre_maps'] ?? null) ? trim($datos['nombre_maps']) : null,
            'mision' => filled($datos['mision'] ?? null) ? trim($datos['mision']) : null,
            'vision' => filled($datos['vision'] ?? null) ? trim($datos['vision']) : null,
            'instagram' => $datos['instagram'] ?? null,
            'facebook' => $datos['facebook'] ?? null,
            'tiktok' => $datos['tiktok'] ?? null,
        ];

        $fila = DatosConsultorio::fila();
        $auditoria->registrarCambio($request, 'consultorio.actualizado', $fila, self::CAMPOS, function () use ($fila, $nuevos) {
            $fila->fill($nuevos)->save();

            return $fila;
        });

        return Redirect::to(route('admin.configuracion').'#consultorio')
            ->with('success', 'Datos del consultorio guardados. La portada y los mensajes a pacientes ya usan el número '.$nuevos['telefono'].'.');
    }
}
