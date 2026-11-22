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
libre). `WhatsappService::enviarPlantilla()` manda exactamente estas 5 —
los nombres y la cantidad de variables deben coincidir tal cual:

| Plantilla | Idioma | Disparada desde | Variables `{{1}} {{2}} {{3}}` |
|---|---|---|---|
| `cita_agendada` | `es_MX` | `CitaController@store`, `CitaPublicaController@store` | nombre, tratamiento, fecha |
| `cita_confirmada` | `es_MX` | `CitaController@confirm` | nombre, fecha |
| `cita_cancelada` | `es_MX` | `CitaController@cancel` | nombre, fecha |
| `cita_reagendada` | `es_MX` | `CitaController@update` | nombre, fecha |
| `cita_recordatorio` | `es_MX` | `dentissa:enviar-recordatorios` (scheduler) | nombre, tratamiento, fecha + 3 botones |

Se crean en **WhatsApp → Plantillas de mensajes → Crear plantilla**,
categoría **Utilidad (Utility)**. Ejemplo de cuerpo para `cita_agendada`:

```
Hola {{1}}, tu cita de {{2}} quedó agendada para el {{3}}. Te esperamos.
```

La aprobación de Meta suele tardar de minutos a un día.

### Plantilla del recordatorio con botones

`cita_recordatorio` la envía el comando programado
`dentissa:enviar-recordatorios` unas 24 h antes de la cita. Además del cuerpo,
lleva **3 botones de respuesta rápida**, en este orden exacto, porque el
sistema les asigna el payload por posición:

| Posición | Texto del botón | Payload que envía Dentissa |
|---|---|---|
| 0 | Sí, asistiré | `recordatorio:{id_cita}:confirmar` |
| 1 | Cancelar cita | `recordatorio:{id_cita}:cancelar` |
| 2 | Reagendar | `recordatorio:{id_cita}:reagendar` |

Cuerpo sugerido (variables: nombre, tratamiento, fecha):

```
Hola {{1}}, te recordamos tu cita de {{2}} el {{3}}. ¿Nos confirmas si vas a asistir?
```

El recordatorio no es una cascada: sale por WhatsApp **y** por correo. Mientras
la plantilla no esté aprobada, el intento de WhatsApp falla sin afectar el
correo, que trae los mismos tres botones como enlaces.

Cuando el paciente pulsa un botón, Meta avisa al webhook de Dentissa (sección
4.1), que cambia la cita y le contesta por WhatsApp.

## 4.1 Webhook de respuestas (botones del recordatorio)

**Analogía:** la plantilla es el altavoz; el webhook es el buzón donde Meta
deja la respuesta del paciente. Meta necesita la dirección pública del buzón
(HTTPS) y una contraseña compartida para comprobar que el buzón es el correcto.

**Requisito:** Dentissa en línea con HTTPS (Railway). En `localhost` no
funciona, porque Meta no puede llegar a tu laptop.

1. **Inventa un token de verificación**, una frase larga al azar, y ponlo en
   Railway como `DENTISSA_WHATSAPP_VERIFY_TOKEN`. No es una contraseña de
   Meta: es una contraseña que tú eliges para este paso.
2. **Copia el secreto de la app:** developers.facebook.com → tu app →
   **Configuración de la app → Básica → Clave secreta de la app → Mostrar**.
   Ponlo en Railway como `DENTISSA_WHATSAPP_APP_SECRET`. **No lo pegues en
   chats ni en capturas.**
3. Redespliega o reinicia el servicio para que lea las variables.
4. En la app de Meta: **WhatsApp → Configuración → Webhook → Editar**.
   - **URL de devolución de llamada:** `https://TU-DOMINIO/webhooks/whatsapp`
     (en Railway, la URL pública del servicio + `/webhooks/whatsapp`).
   - **Token de verificación:** el mismo del paso 1.
   - Pulsa **Verificar y guardar**. Si Dentissa contesta, Meta lo acepta.
5. En la misma pantalla, en **Campos del webhook**, **suscríbete a
   `messages`**. Sin esto, Meta no envía las pulsaciones de los botones.
6. **Prueba real:** agenda una cita ficticia para mañana con tu número de
   prueba, de forma que se haya creado con más de 24 h de anticipación (o
   cambia su `created_at` en una base de prueba). Ejecuta
   `php artisan dentissa:enviar-recordatorios` y pulsa **Sí, asistiré** en tu
   WhatsApp. Debe llegarte "¡Gracias! Registramos que asistirás…" y la agenda
   debe mostrar **✓ Confirmó asistencia**.

**Seguridad del webhook (`WhatsappWebhookController`):**
- Rechaza (403) cualquier aviso cuya firma `X-Hub-Signature-256` no
  corresponda al secreto de la app: solo Meta puede llamarlo.
- Solo cambia la cita si el número que pulsa el botón es el del paciente de
  esa cita.
- Si Meta reintenta el mismo aviso, se procesa una sola vez.
- A un mensaje de texto, audio o foto (no un botón) contesta automáticamente
  que el número solo envía recordatorios, con el teléfono del consultorio y el
  enlace para agendar. Una vez cada 12 h por número, y sin guardar el contenido.

**Costo:** Meta cobra los mensajes de plantilla que inicia la empresa, como el
recordatorio, según la tarifa de la categoría *Utilidad* para México. La
respuesta que manda Dentissa cuando el paciente pulsa un botón va dentro de la
ventana de 24 h de atención y no se cobra como plantilla. Con el número de
prueba (hasta 5 destinatarios registrados) se puede hacer la demo; para
pacientes reales, revisa la tarifa vigente en la documentación de precios de
WhatsApp Business antes de activarlo.

## 5. Variables de entorno

Agregar en `.env` (ya están declaradas, vacías, en `.env` y `.env.example`):

```
DENTISSA_WHATSAPP_PHONE_ID=el_id_que_copiaste
DENTISSA_WHATSAPP_TOKEN=el_token_permanente_del_usuario_del_sistema
DENTISSA_WHATSAPP_API_VERSION=v25.0
DENTISSA_WHATSAPP_IDIOMA=es_MX
DENTISSA_WHATSAPP_APP_SECRET=clave_secreta_de_la_app
DENTISSA_WHATSAPP_VERIFY_TOKEN=frase_que_tu_eliges
```

Ver `config/dentissa.php` (bloque `whatsapp`) para cómo se leen.

**Importante:** los teléfonos mexicanos pueden guardarse como diez dígitos
(por ejemplo, `5512345678`) o con `+52`, espacios, guiones y paréntesis.
`WhatsappService::normalizarTelefono()` convierte esos formatos al destinatario
`5215512345678` antes de llamar a la API, y conserva números que ya tienen el
prefijo `521`. Los teléfonos incompletos, excesivos o con letras se rechazan
sin hacer una solicitud a Meta; `NotificacionService` usará el correo como
respaldo si está disponible. Las pruebas de esta conversión usan HTTP simulado,
no envían mensajes reales.

## 6. Probar

**Probar el recordatorio sin esperar 24 h.** En cuanto Meta apruebe
`cita_recordatorio`, agenda una cita de prueba con tu teléfono y, en Railway →
Console, ejecuta:

```bash
php artisan dentissa:probar-recordatorio ID_DE_LA_CITA
```

Sin `--confirmar` solo muestra la cita, con el teléfono y el correo
enmascarados. Para enviarlo de verdad:

```bash
php artisan dentissa:probar-recordatorio ID_DE_LA_CITA --confirmar
```

Manda en ese momento el recordatorio por WhatsApp y correo, y marca la cita para
que el envío programado no lo repita. Si WhatsApp no sale, avisa que revises que
la plantilla esté aprobada. El ID de la cita aparece en la dirección al abrirla
desde el panel (`/citas/ID/editar`).

1. Con las credenciales del número de prueba en `.env`, envía primero la
   plantilla que Meta ya incluye para pruebas. Sustituye el teléfono por el
   destinatario que agregaste y verificaste en el panel de Meta:

   ```sh
   php artisan whatsapp:probar 5215512345678 --confirmar
   ```

   El comando solo envía si incluyes `--confirmar`; envía `hello_world` en
   inglés y no crea una cita ni usa datos de pacientes. Si el servidor ya
   estaba corriendo, ejecuta `php artisan config:clear` para que lea el `.env`
   nuevo. No compartas el token en capturas o mensajes.
2. Cuando confirmes la recepción, crea y espera la aprobación de la plantilla
   `cita_agendada` descrita arriba. Solo entonces agenda una cita ficticia con
   el teléfono de prueba para comprobar el flujo real de Dentissa.
3. Si algo falla, revisa `storage/logs/laravel.log` — `WhatsappService`
   registra el código HTTP/error sin guardar el teléfono ni los parámetros
   personales de la plantilla. Si faltan credenciales, registra que WhatsApp
   no está configurado.
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
