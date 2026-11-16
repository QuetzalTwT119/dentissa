# Guion de la demostración de Dentissa v1.0

**Duración objetivo:** 7 minutos de recorrido y 3 minutos de preguntas.
**Entorno:** Railway, con datos ficticios del `DemoPresentacionSeeder`.
**Regla de oro:** nada se improvisa en vivo. Cada paso tiene un resultado
esperado y un plan B.

## 1. Reparto de papeles

| Persona | Papel en la demo | Dispositivo |
|---|---|---|
| Presentador A | Narra y hace de **paciente** | Celular conectado al proyector (o duplicado de pantalla) |
| Presentador B | Hace de **personal de la clínica** (administrador) | Laptop proyectada |

Si solo presenta una persona, se usan dos pestañas en la laptop: una en modo
celular (375 px) para el paciente y otra con la sesión del administrador.

## 2. Preparación

### Dos días antes (T-2)

1. Encender Railway y comprobar que `/version` muestra el último commit de
   `master`.
2. Ejecutar el seeder de demostración (ver `DESPLIEGUE-RAILWAY.md`). Las
   contraseñas viven solo en las variables privadas de Railway; nunca se
   escriben en diapositivas ni se dicen en voz alta.
3. Revisar que `APP_NAME=Dentissa` esté en las variables de Railway (el título
   de la pestaña debe decir Dentissa, no Laravel).
4. **Crear la cita del recordatorio.** Desde `/agendar-cita`, agendar una cita
   para el **día de la presentación, una hora después de que empiece**, con un
   correo del equipo. Se crea con más de 24 h de anticipación a propósito: el
   sistema no manda recordatorio a citas agendadas con menos de un día.

### Un día antes (T-1)

5. Unas 24 h antes de esa cita debe llegar el correo **Recordatorio de tu
   cita**. Comprobar que llegó (revisar también Spam) y **no pulsar ningún
   botón**: la respuesta se hace en vivo.
6. Ensayar el recorrido completo con cronómetro, al menos dos veces.
7. Tomar capturas de cada paso como plan B y guardarlas fuera de línea.

### Treinta minutos antes

8. Abrir `/version`, el sitio público y el correo del recordatorio.
9. Iniciar sesión como administrador en la laptop, en `/citas`.
10. Dejar la campana de notificaciones sin pendientes, para que el aviso nuevo
    se note.
11. Celular con brillo alto, notificaciones silenciadas y zoom del navegador
    al 100 %.

## 3. Recorrido minuto a minuto

### 0:00 – 0:45 · Problema y propuesta

**Decir:** "La clínica de la Dra. Melissa agenda por teléfono y por mensajes.
Se pierden citas y hay pacientes que no llegan. Dentissa centraliza la agenda,
deja que el paciente agende solo y le recuerda su cita un día antes."

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
5. Llenar datos ficticios y pulsar **Agendar cita**.

**Resultado esperado:** pantalla "¡Listo! Tu cita quedó confirmada" con
tratamiento, fecha y hora.

**Decir:** "El paciente no necesita cuenta. Es una decisión de diseño: menos
fricción para pacientes de cualquier edad."

**Plan B:** si no hay horarios ese día, elegir el siguiente día hábil.

### 2:15 – 3:45 · El recordatorio y la respuesta del paciente (celular)

1. Abrir el correo **Recordatorio de tu cita** preparado en T-1.
2. *Señalar* los tres botones: **Sí, asistiré**, **Quiero reagendar** y
   **Cancelar**.
3. Pulsar **Sí, asistiré**. Se abre una página que pregunta; pulsar el botón
   de confirmar.

**Resultado esperado:** "¡Gracias! Te esperamos."

**Decir:** "Abrir el enlace no cambia nada: Gmail y los antivirus abren los
enlaces de los correos para revisarlos, así que el cambio solo ocurre al
confirmar. Además, el enlace va firmado: si alguien cambia el número de cita en
la dirección, el sistema lo rechaza."

**Plan B:** si el correo no llegó, mostrar la captura del correo y explicar el
flujo con las capturas de la página de respuesta.

### 3:45 – 5:15 · La clínica lo ve al instante (laptop)

1. *Señalar* la campana: llegó **Respuesta del paciente**.
2. En `/citas`, cambiar a **Semana**. *Señalar:*
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
   administrador y las notas privadas no salen en el PDF."
2. **Reportes:** filtrar el mes actual y descargar el **PDF** o el **Excel**.

### 6:15 – 7:00 · Calidad y cierre

**Decir (con una diapositiva de apoyo):**

- Más de 200 pruebas automáticas. Cada cambio pasa por integración continua
  con SQLite y MySQL, construcción de imágenes Docker y una puerta de
  liberación antes de poder fusionarse.
- Trabajo con ramas y Pull Requests, mensajes de commit convencionales y una
  matriz de QA con evidencia de cada caso.
- Despliegue en Railway con Docker; un solo contenedor ejecuta el sitio, el
  bot de Telegram y el programador de recordatorios. Costo de herramientas:
  cero.

**Cierre:** "Dentissa reduce las llamadas para agendar y las inasistencias, y
le da a la clínica su agenda en un solo lugar."

## 4. Preguntas probables

| Pregunta | Respuesta corta |
|---|---|
| ¿Y si dos pacientes eligen el mismo horario a la vez? | La reserva se valida dentro de una transacción con bloqueo; el segundo recibe "Ese horario ya no está disponible". Hay una prueba de concurrencia en MySQL. |
| ¿Cómo evitan que cualquiera cancele citas ajenas? | Los enlaces van firmados y caducan; cambiar un dato de la URL invalida la firma. |
| ¿Por qué no tiene cuenta el paciente? | Para quitar fricción; la identidad se resuelve por correo y teléfono, y las respuestas por enlace firmado. |
| ¿Y WhatsApp? | El recordatorio ya sale por WhatsApp con la plantilla de Meta; recibir los botones de WhatsApp es la siguiente etapa (webhook). Hoy la respuesta se hace por correo. |
| ¿Qué pasa si falla el correo o WhatsApp? | Cada canal falla por separado; el otro se envía igual y el intento queda registrado. Agendar nunca se cae por un aviso. |
| ¿Cómo protegen los datos clínicos? | Permisos por rol, auditoría de acciones del personal y notas privadas fuera del PDF. En la demo solo hay datos ficticios. |

## 5. Después de la demo

1. Cancelar desde el panel las citas creadas en vivo.
2. Si ya no se necesita, apagar el servicio de Railway desde su panel.
3. No compartir capturas donde aparezcan correos reales del equipo.
