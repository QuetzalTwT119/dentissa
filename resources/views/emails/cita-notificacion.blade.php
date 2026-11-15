<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>{{ $titulo }}</title>
    </head>
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
                            <td style="padding:32px;">
                                <h1 style="margin:0 0 16px; color:#111827; font-size:20px;">{{ $titulo }}</h1>
                                <p style="margin:0; color:#374151; font-size:14px; line-height:1.6;">{{ $mensaje }}</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:16px 32px; background-color:#fdf1f4;">
                                <p style="margin:0; color:#9ca3af; font-size:11px;">
                                    Recibiste este correo porque tienes una cita registrada con nosotros. Si tienes dudas, contáctanos directamente.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
