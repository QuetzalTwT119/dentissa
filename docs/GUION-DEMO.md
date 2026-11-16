# Guion de la demostración de Dentissa v1.0

**Duración objetivo:** 7 minutos de recorrido y 3 minutos de preguntas.
**Entorno:** Railway, con datos ficticios del `DemoPresentacionSeeder`.
**Regla de oro:** nada se improvisa en vivo. Cada paso tiene un resultado
esperado y un plan B.

**El momento central de la demo:** el paciente recibe el recordatorio por
WhatsApp, pulsa **Sí, asistiré** en su chat, y la clínica lo ve al instante en
la agenda y en la campana.

## 1. Reparto de papeles

| Persona | Papel en la demo | Dispositivo |
|---|---|---|
| Presentador A | Narra y hace de **paciente** | Celular con WhatsApp, conectado al proyector (o duplicando la pantalla) |
| Presentador B | Hace de **personal de la clínica** (administrador) | Laptop proyectada |

Si solo presenta una persona, se usan dos pestañas en la laptop: una en modo
celular (375 px) para el paciente y otra con la sesión del administrador. El
WhatsApp se muestra desde el celular.

**El celular del paciente debe ser el número registrado como destinatario de
prueba en Meta.** Con el número de prueba gratuito, Meta solo envía a los
números que registraste (hasta 5).

## 2. Preparación

### Tres o cuatro días antes (T-4 / T-3)

1. Encender Railway y comprobar que `/version` muestra el último commit de
   `master`.
2. Configurar las variables según `DESPLIEGUE-RAILWAY.md`: `APP_NAME`,
   sesión segura, `DENTISSA_2FA_ADMIN`, la llave de respaldos y las de
   WhatsApp (`DENTISSA_WHATSAPP_*`).
3. **WhatsApp listo** (`CONFIGURACION-WHATSAPP.md`, secciones 4 y 4.1):
   - la plantilla `cita_recordatorio` **aprobada** por Meta, con los 3 botones
     en orden;
   - el webhook configurado con
     `https://dentissa-production-11db.up.railway.app/webhooks/whatsapp`
     y suscrito a `messages`;
   - el celular del presentador A registrado como destinatario de prueba.
4. Ejecutar el seeder de demostración. Las contraseñas viven solo en las
   variables privadas de Railway; nunca se escriben en diapositivas ni se
   dicen en voz alta.
5. **Verificación en dos pasos:** el presentador B inicia sesión como
   administrador y la activa con su celular. Guarda los códigos de
   recuperación fuera de la laptop. (Si se decidió presentar con
   `DENTISSA_2FA_ADMIN=false`, decirlo en la sección de seguridad.)
6. **Prueba completa de WhatsApp:** agendar una cita de prueba para el día
   siguiente con el celular del presentador A, esperar el recordatorio (o
   ejecutar `php artisan dentissa:enviar-recordatorios`), pulsar **Sí,
   asistiré** y comprobar la respuesta del bot y la marca en la agenda. Después
   cancelar esa cita de prueba.

### Dos días antes (T-2)

7. **Crear la cita del recordatorio de la demo.** Desde `/agendar-cita`, agendar
   una cita para el **día de la presentación, una hora después de que
   empiece**, con el **teléfono del presentador A** y un correo del equipo. Se
   crea con más de 24 h de anticipación a propósito: el sistema no manda
   recordatorio a citas agendadas con menos de un día.

### Un día antes (T-1)

8. Unas 24 h antes de esa cita deben llegar **dos** recordatorios: el mensaje
   de WhatsApp y el correo **Recordatorio de tu cita**. Comprobar que llegaron
   (en el correo, revisar también Spam) y **no pulsar ningún botón**: la
   respuesta se hace en vivo.
9. Ensayar el recorrido completo con cronómetro, al menos dos veces.
10. Tomar capturas de cada paso como plan B y guardarlas fuera de línea:
    portada, agenda, mensaje de WhatsApp, respuesta del bot, campana y agenda
    semanal con la marca.

### Treinta minutos antes

11. Abrir `/version`, el sitio público y, en el celular, el chat del
    recordatorio en WhatsApp.
12. Iniciar sesión como administrador en la laptop (contraseña y código de la
    app) y dejarla en `/citas`, vista **Semana**.
13. Dejar la campana de notificaciones sin pendientes, para que el aviso nuevo
    se note.
14. Celular con brillo alto, datos o Wi-Fi estables, notificaciones de otras
    apps silenciadas y zoom del navegador al 100 %.

## 3. Recorrido minuto a minuto

### 0:00 – 0:45 · Problema y propuesta

**Decir:** "La clínica de la Dra. Melissa agenda por teléfono y por mensajes.
Se pierden citas y hay pacientes que no llegan. Dentissa centraliza la agenda,
deja que el paciente agende solo y le recuerda su cita un día antes por
WhatsApp, donde puede confirmar con un solo toque."

**Mostrar:** la portada en el celular.

### 0:45 – 2:15 · El paciente agenda sin cuenta (celular)

1. Pulsar **Elegir fecha y horario**.
2. Elegir **Odontología General → Limpieza Dental**. *Señalar:* cada
   procedimiento muestra su duración real.
3. Elegir una fecha. *Señalar:* el calendario no permite días pasados y la
   fecha se muestra en español ("miércoles 18 de noviembre de 2026").
4. Elegir una hora disponible. *Decir:* "Solo aparecen horarios libres según
   el horario de atención y la duración del tratamiento; dos personas no
   pueden tomar el mismo turno."
5. Llenar datos ficticios, **marcar la casilla del aviso de privacidad** y
   pulsar **Agendar cita**. *Decir:* "Sin aceptar el aviso de privacidad no se
   guarda nada, porque el motivo de la visita es un dato de salud."

**Resultado esperado:** pantalla "¡Listo! Tu cita quedó confirmada" con
tratamiento, fecha y hora.

**Decir:** "El paciente no necesita cuenta. Es una decisión de diseño: menos
fricción para pacientes de cualquier edad."

**Plan B:** si no hay horarios ese día, elegir el siguiente día hábil.

### 2:15 – 3:45 · El recordatorio por WhatsApp (celular)

1. Abrir en WhatsApp el **recordatorio** que llegó en T-1. *Señalar:* el
   mensaje dice el tratamiento, la fecha y la hora, y trae tres botones:
   **Sí, asistiré**, **Cancelar cita** y **Reagendar**.
2. Pulsar **Sí, asistiré**.

**Resultado esperado:** en segundos llega la respuesta del bot: "¡Gracias!
Registramos que asistirás a tu cita del…".

**Decir:** "Este recordatorio se manda solo, un día antes, por WhatsApp y por
correo a la vez. Si el paciente pulsa **Reagendar**, el sistema libera el
horario y le manda el enlace para elegir otro."

**Decir (seguridad, una frase):** "Solo aceptamos avisos firmados por Meta, y
solo si el botón lo pulsa el mismo número del paciente: no se puede confirmar
la cita de otra persona."

**Plan B:**
- Si la respuesta del bot tarda más de 10 segundos, seguir con la laptop: la
  cita igual aparece confirmada.
- Si el mensaje de WhatsApp no llegó, usar el **correo del recordatorio**, que
  trae los mismos tres botones: pulsar **Sí, asistiré** y confirmar en la
  página. *Decir:* "El correo es el respaldo; cada canal funciona por su
  cuenta."
- Si tampoco hay correo, mostrar las capturas.

### 3:45 – 5:15 · La clínica lo ve al instante (laptop)

1. *Señalar* la campana: llegó **Respuesta del paciente**.
2. En `/citas`, vista **Semana**. *Señalar:*
   - la cita recién agendada desde el celular;
   - la cita del recordatorio con **✓ Confirmó asistencia**;
   - los días cerrados y el horario de cada día.
3. Abrir una cita y mostrar **Reagendar** y **Cancelar**. *Decir:* "Si la
   clínica reagenda, el paciente recibirá un recordatorio nuevo para la nueva
   fecha."

**Plan B:** si la campana no se actualiza sola, recargar la página.

### 5:15 – 6:15 · Historial y reportes (laptop)

1. **Pacientes → Paciente Demostración → Ver historial**. Está vacío a
   propósito (datos ficticios): pulsar **Nueva consulta** solo para mostrar
   los campos, sin guardar. *Decir:* "El historial clínico solo lo ve el
   administrador, que entra con contraseña y un código de su celular, y las
   notas privadas no salen en el PDF."
2. **Reportes:** filtrar el mes actual y descargar el **PDF** o el **Excel**.

### 6:15 – 7:00 · Calidad, seguridad y cierre

**Decir (con una diapositiva de apoyo):**

- Más de 260 pruebas automáticas. Cada cambio pasa por integración continua
  con SQLite y MySQL, construcción de imágenes Docker y una puerta de
  liberación antes de poder fusionarse.
- Trabajo con ramas y Pull Requests, mensajes de commit convencionales y una
  matriz de QA con evidencia de cada caso.
- Pensado para datos de salud: aviso de privacidad con consentimiento,
  verificación en dos pasos para el administrador, y un respaldo cifrado cada
  noche fuera del servidor, con prueba de restauración.
- Despliegue en Railway con Docker; un solo contenedor ejecuta el sitio, el
  bot de Telegram y el programador de recordatorios y respaldos. Las
  herramientas de desarrollo son gratuitas; WhatsApp cobra cada recordatorio
  enviado.

**Cierre:** "Dentissa reduce las llamadas para agendar y las inasistencias, y
le da a la clínica su agenda en un solo lugar."

## 4. Preguntas probables

| Pregunta | Respuesta corta |
|---|---|
| ¿Y si dos pacientes eligen el mismo horario a la vez? | La reserva se valida dentro de una transacción con bloqueo; el segundo recibe "Ese horario ya no está disponible". Hay una prueba de concurrencia en MySQL. |
| ¿Cómo funciona el botón de WhatsApp? | Meta avisa a Dentissa por un webhook. Comprobamos la firma de Meta y que el número sea el del paciente, y aplicamos la misma lógica que los botones del correo. |
| ¿Cuánto cuesta WhatsApp? | Meta cobra cada recordatorio (plantilla de utilidad). La respuesta del bot cuando el paciente pulsa un botón no se cobra como plantilla. Para la demo usamos el número de prueba gratuito. |
| ¿Cómo evitan que cualquiera cancele citas ajenas? | En WhatsApp, solo cuenta el botón pulsado desde el número del paciente. En el correo, los enlaces van firmados; cambiar un dato de la URL invalida la firma. |
| ¿Por qué no tiene cuenta el paciente? | Para quitar fricción; la identidad se resuelve por correo y teléfono, y las respuestas por enlace firmado o por su propio WhatsApp. |
| ¿Qué pasa si falla el correo o WhatsApp? | Cada canal falla por separado; el otro se envía igual y el intento queda registrado. Agendar nunca se cae por un aviso. |
| ¿Cómo protegen los datos clínicos? | Permisos por rol, verificación en dos pasos para el administrador, auditoría de acciones del personal, notas privadas fuera del PDF y sesiones que se cierran tras 30 minutos sin actividad. En la demo solo hay datos ficticios. |
| ¿Y si se borra la base de datos? | Cada noche se hace un respaldo cifrado en Google Drive; Google no puede leerlo. Un comando comprueba que se puede restaurar sin tocar la base real. |

## 5. Después de la demo

1. Cancelar desde el panel las citas creadas en vivo.
2. Si ya no se necesita, apagar el servicio de Railway desde su panel.
3. No compartir capturas donde aparezcan teléfonos o correos reales del equipo.
