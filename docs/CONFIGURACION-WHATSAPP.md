# Configuración de WhatsApp Cloud API

Cómo cargar credenciales reales de Meta para que `NotificacionService` mande
recordatorios, confirmaciones y cancelaciones de citas por WhatsApp de verdad
en vez de degradarse a correo. Sin esto configurado, el sistema sigue
funcionando (registra la notificación y manda correo) pero nunca sale por
WhatsApp — ver `app/Services/WhatsappService.php::configurado()`.

## 1. Cuenta y app en Meta

1. Entra a [business.facebook.com](https://business.facebook.com) y crea (o
   usa) una cuenta de negocio.
2. Ve a [developers.facebook.com/apps](https://developers.facebook.com/apps)
   → "Crear app" → tipo **Business**.
3. Dentro de la app, agrega el producto **WhatsApp**.

## 2. Número y Phone Number ID

Para pruebas/demo **no hace falta un número real verificado**: Meta da un
número de prueba gratis con la app, que manda mensajes a hasta 5 números que
tú registres como "destinatario de prueba".

1. Dashboard de la app → **WhatsApp → Configuración de la API**.
2. Copia el **"ID del número de teléfono"** → va a `DENTISSA_WHATSAPP_PHONE_ID`.
3. En esa misma pantalla, agrega tu número personal como destinatario de
   prueba para recibir los mensajes durante el desarrollo.

Cuando se quiera usar el número real de la clínica: **WhatsApp → Números de
teléfono → Agregar número**, verificarlo por SMS/llamada, y usar el Phone
Number ID de ese número en vez del de prueba.

## 3. Token de acceso permanente

El token que aparece por default en "Configuración de la API" expira en 24
horas — no sirve para producción. Hace falta uno permanente:

1. [business.facebook.com/settings](https://business.facebook.com/settings)
   → **Usuarios del sistema** → crear uno (rol Admin).
2. Asignarle la app y el activo de WhatsApp.
3. Generar un token para ese usuario del sistema, con permisos
   `whatsapp_business_messaging` y `whatsapp_business_management`, **sin
   fecha de expiración**.
4. Ese token va a `DENTISSA_WHATSAPP_TOKEN`.

## 4. Plantillas de mensaje (obligatorio)

Meta exige plantillas pre-aprobadas para mensajes que la clínica inicia
(fuera de la ventana de 24h de conversación, no se puede mandar texto
libre). `WhatsappService::enviarPlantilla()` manda exactamente estas 4 —
los nombres y la cantidad de variables deben coincidir tal cual:

| Plantilla | Idioma | Disparada desde | Variables `{{1}} {{2}} {{3}}` |
|---|---|---|---|
| `cita_agendada` | `es_MX` | `CitaController@store`, `CitaPublicaController@store` | nombre, tratamiento, fecha |
| `cita_confirmada` | `es_MX` | `CitaController@confirm` | nombre, fecha |
| `cita_cancelada` | `es_MX` | `CitaController@cancel` | nombre, fecha |
| `cita_reagendada` | `es_MX` | `CitaController@update` | nombre, fecha |

Se crean en **WhatsApp → Plantillas de mensajes → Crear plantilla**,
categoría **Utilidad (Utility)**. Ejemplo de cuerpo para `cita_agendada`:

```
Hola {{1}}, tu cita de {{2}} quedó agendada para el {{3}}. Te esperamos.
```

La aprobación de Meta suele tardar de minutos a un día.

## 5. Variables de entorno

Agregar en `.env` (ya están declaradas, vacías, en `.env` y `.env.example`):

```
DENTISSA_WHATSAPP_PHONE_ID=el_id_que_copiaste
DENTISSA_WHATSAPP_TOKEN=el_token_permanente_del_usuario_del_sistema
DENTISSA_WHATSAPP_API_VERSION=v21.0
DENTISSA_WHATSAPP_IDIOMA=es_MX
```

Ver `config/dentissa.php` (bloque `whatsapp`) para cómo se leen.

**Importante:** el paciente que agenda debe tener su `telefono` guardado
**con código de país** (ej. `5215512345678` para México — sin `+` ni
espacios). `WhatsappService::normalizarTelefono()` limpia el formato, pero
no puede inventar el código de país si no está guardado.

## 6. Probar

1. Si el servidor ya estaba corriendo, `php artisan config:clear` para que
   lea el `.env` nuevo.
2. Agenda una cita de prueba usando el número que registraste como
   destinatario de prueba en el paso 2.
3. Si algo falla, revisa `storage/logs/laravel.log` — `WhatsappService`
   registra el motivo exacto del rechazo de Meta (`response->json()`), o el
   mensaje "WhatsApp sin configurar" si faltan credenciales.
4. Cuando no llega por WhatsApp (sin credenciales, plantilla no aprobada,
   número sin código de país, etc.), `NotificacionService` cae a correo
   automáticamente — revisa la bandeja de `MAIL_MAILER` configurado
   (`log` por default en desarrollo, ver `storage/logs/laravel.log`).

## Referencia rápida de archivos

- `app/Services/WhatsappService.php` — llamada HTTP a la Cloud API de Meta.
- `app/Services/NotificacionService.php` — decide WhatsApp vs. correo y deja
  el registro en la tabla `notificaciones`.
- `app/Mail/CitaNotificacion.php` + `resources/views/emails/cita-notificacion.blade.php`
  — respaldo por correo.
- `config/dentissa.php` → bloque `whatsapp`.
