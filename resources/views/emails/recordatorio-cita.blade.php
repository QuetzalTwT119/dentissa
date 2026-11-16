<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>{{ $titulo }}</title>
    </head>
    {{-- Los correos no cargan CSS externo: los estilos van en línea y los
         botones son enlaces con tabla para que se vean igual en Gmail y Outlook. --}}
    <body style="margin:0; padding:0; background-color:#fdf1f4; font-family: Arial, Helvetica, sans-serif;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fdf1f4; padding:32px 0;">
            <tr>
                <td align="center">
                    <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:12px; overflow:hidden;">
                        <tr>
                            <td style="background-color:#d75078; padding:24px 32px;">
                                <p style="margin:0; color:#ffffff; font-size:13px; letter-spacing:0.08em; text-transform:uppercase;">Melissa López N. — Odontología integral</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:32px 32px 8px;">
                                <h1 style="margin:0 0 16px; color:#111827; font-size:20px;">{{ $titulo }}</h1>
                                <p style="margin:0; color:#374151; font-size:14px; line-height:1.6;">{{ $mensaje }}</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:16px 32px 32px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="padding:0 0 10px;">
                                            <a href="{{ $enlaces['confirmar'] }}" style="display:block; padding:13px 16px; border-radius:999px; background-color:#d75078; color:#ffffff; font-size:15px; font-weight:bold; text-align:center; text-decoration:none;">Sí, asistiré</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:0 0 10px;">
                                            <a href="{{ $enlaces['reagendar'] }}" style="display:block; padding:12px 16px; border-radius:999px; border:1px solid #e5c7d0; color:#9d2f52; font-size:14px; font-weight:bold; text-align:center; text-decoration:none;">Quiero reagendar</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <a href="{{ $enlaces['cancelar'] }}" style="display:block; padding:12px 16px; border-radius:999px; border:1px solid #e5e7eb; color:#4b5563; font-size:14px; text-align:center; text-decoration:none;">No podré asistir, cancelar cita</a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:16px 32px; background-color:#fdf1f4;">
                                <p style="margin:0; color:#9ca3af; font-size:11px;">
                                    Los botones abren una página donde confirmas tu respuesta; nada cambia solo con abrir este correo. Los enlaces dejan de funcionar a la hora de tu cita. <a href="{{ route('privacidad.aviso') }}" style="color:#9ca3af;">Aviso de privacidad</a>.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
