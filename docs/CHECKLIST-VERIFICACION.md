# Checklist de verificación manual — Dentissa v1.0

Recorrido funcional completo en **Railway**
(`https://dentissa-production-11db.up.railway.app`), de preferencia desde un
**celular**. Así también se cubre la aceptación en dispositivo real
(QA-48, QA-56 y QA-57).

**En local** (`http://localhost:8000`) la lista funciona igual, con estas diferencias:
el celular entra por la IP de la computadora en la misma red Wi-Fi (por ejemplo
`http://192.168.0.80:8000`); los arreglos de la rama de trabajo se ven antes que
en Railway; y la Parte 9 (volumen y respaldo del servidor) solo aplica a Railway.

## Cómo usarla

1. Recorre las partes en el orden que quieras. Marca `[x]` en lo que funcione.
2. Si algo **no** hace lo que dice "Debe pasar", no lo marques: escribe en la
   tabla de hallazgos (al final) el número del punto y qué pasó, y toma una
   **captura**.
3. Al terminar, manda solo los hallazgos con sus capturas. Se corrigen en un solo
   paquete y la evidencia se registra en la matriz QA.

**Datos para probar:** usa pacientes ficticios, salvo donde se pide tu propio
correo o celular (para comprobar que los mensajes llegan de verdad). Nunca uses
datos ni radiografías de pacientes reales.

**Ya sabemos (no son hallazgos):**
- WhatsApp no llega hasta que Meta apruebe las 5 plantillas (en revisión desde el 20/11).
- El aviso de privacidad dice "Por completar" hasta que la clínica dé sus datos.
- La verificación en dos pasos del administrador está apagada para la demo.

---

## Parte 1 — Sitio público y cita en línea (sin iniciar sesión)

- [x] **1.1** Abre la portada. *Debe pasar:* foto del consultorio, botón para agendar y aviso de si el consultorio está abierto o cerrado ahora.
- [ ] **1.2** Desliza el carrusel de especialidades. *Debe pasar:* en celular se asoma la orilla de la siguiente tarjeta (invita a deslizar); en computadora y tableta se ven tarjetas completas, sin cortes. Un punto por posición. En computadora la flecha › en la última tarjeta regresa al inicio; en celular (sin flechas) el deslizamiento se detiene en la última, por decisión de diseño (H-3).
- [ ] **1.3** Toca una especialidad con "Agenda en línea". *Debe pasar:* abre la agenda con esa especialidad ya elegida.
- [ ] **1.4** Elige tratamiento, día en el calendario y hora. *Debe pasar:* el calendario solo deja elegir días con lugar y horas libres.
- [ ] **1.5** En "Tus datos" escribe tu nombre, **tu celular** y **tu correo**. *Debe pasar:* el teléfono se acomoda como "55 1234 5678"; si falta algo, el error sale al momento.
- [ ] **1.6** Acepta el aviso y confirma. *Debe pasar:* pantalla de confirmación con el resumen de la cita.
- [ ] **1.7** Revisa tu correo. *Debe pasar:* llega el correo de Dentissa con la cita.
- [ ] **1.8** Intenta agendar **la misma hora otra vez**. *Debe pasar:* esa hora ya no aparece disponible.
- [ ] **1.9** Manda un mensaje en el formulario de contacto. *Debe pasar:* aviso de "enviado".
- [ ] **1.10** Abre el aviso de privacidad (pie de página). *Debe pasar:* la página carga.
- [ ] **1.11** En todas las pantallas de esta parte. *Debe pasar:* nada se sale de la pantalla ni obliga a desplazarse de lado.
- [ ] **1.12** Agenda **2 citas más** en línea con nombres ficticios (por ejemplo "Ana Prueba" y "Luis Prueba", con un correo inventado). *Debe pasar:* quedan 3 pacientes para las Partes 3 a 5. *(La base de producción se limpió el 20/11 y el panel todavía no permite registrar pacientes; ver H-1.)*

## Parte 2 — Acceso y roles

- [ ] **2.1** Inicia sesión como **administrador**. *Debe pasar:* entra al panel.
- [ ] **2.2** Escribe mal la contraseña a propósito. *Debe pasar:* mensaje de error claro, sin decir si el correo existe.
- [ ] **2.3** "¿La olvidaste?" con el correo del administrador. *Debe pasar:* llega el correo para restablecer.
- [ ] **2.4** Cierra sesión y entra como **asistente**. *Debe pasar:* entra al panel.
- [ ] **2.5** Como asistente, busca Pacientes, Usuarios, Auditoría y Reportes. *Debe pasar:* no aparecen en el menú; si escribes la dirección (por ejemplo `/admin/pacientes`), sale "sin permiso".
- [ ] **2.6** En el celular, abre el menú (☰). *Debe pasar:* se abre completo y se cierra al elegir una opción.

## Parte 3 — Agenda y estados de la cita

- [ ] **3.1** Abre **Citas**. *Debe pasar:* aparece la cita que agendaste en la Parte 1.
- [ ] **3.2** Cambia de día con las pestañas y usa el buscador por nombre. *Debe pasar:* la lista cambia y el buscador encuentra la cita.
- [ ] **3.3** Abre la **vista semanal**. *Debe pasar:* los bloques indican libre / con citas / lleno y al tocar uno salen sus citas.
- [ ] **3.4** Crea una cita desde el panel para un paciente ficticio. *Debe pasar:* "Cita confirmada correctamente" y aparece en la agenda.
- [ ] **3.5** **Reagenda** esa cita a otra hora. *Debe pasar:* cambia de hora; la hora anterior vuelve a estar libre.
- [ ] **3.6** Marca una cita como **iniciada** y luego **completada**. *Debe pasar:* el estado cambia en cada paso.
- [ ] **3.7** Marca otra como **no asistió**. *Debe pasar:* cambia el estado.
- [ ] **3.8** **Cancela** una cita escribiendo un motivo. *Debe pasar:* queda cancelada y el horario se libera.
- [ ] **3.9** Abre el **Asistente de agenda** y pide una propuesta. *Debe pasar:* sugiere horarios disponibles.

## Parte 4 — Pacientes, historial y radiografías (administrador)

- [ ] **4.1** Abre **Pacientes** y busca uno ficticio. *Debe pasar:* aparece en la lista.
- [ ] **4.2** Edita sus datos (por ejemplo, el teléfono) y guarda. *Debe pasar:* "guardado" y el dato cambia.
- [ ] **4.3** Botón **Historial** → **Nuevo registro** → llena y guarda. *Debe pasar:* el registro aparece en el historial.
- [ ] **4.4** Abre ese registro con **Editar** → **Adjuntar archivo** → sube una imagen **ficticia**. *Debe pasar:* la imagen aparece en el registro. *(Ya sabemos que este camino está escondido; se va a mejorar.)*
- [ ] **4.5** Toca la imagen. *Debe pasar:* se abre y se ve completa.
- [ ] **4.6** Descarga el **PDF del historial**. *Debe pasar:* el PDF trae los registros del paciente.
- [ ] **4.7** Cierra sesión y pega la dirección de la imagen en el navegador. *Debe pasar:* pide iniciar sesión; la imagen no es pública.

## Parte 5 — Notificaciones y campana

Para esta parte necesitas **dos sesiones a la vez**: por ejemplo, administrador
en la computadora y asistente en el celular.

- [ ] **5.1** Con el administrador, crea una cita. *Debe pasar:* a la asistente le aparece el aviso en la campana **en unos segundos, sin recargar**.
- [ ] **5.2** Abre la campana. *Debe pasar:* el aviso tiene las **iniciales del paciente** con una insignia; el contador se pone en cero.
- [ ] **5.3** Con el administrador, cancela una cita. *Debe pasar:* a la asistente le sale en **"Por atender"** con "Ver en la agenda", "Llamar" y "Ya lo atendí".
- [ ] **5.4** Toca **"Ya lo atendí"**. *Debe pasar:* el aviso pasa a "Informativas" sin recargar la página.
- [ ] **5.5** Toca **"Llamar"** en el celular. *Debe pasar:* abre el marcador con el número del paciente.
- [ ] **5.6** Abre **"Ver todas las notificaciones"**. *Debe pasar:* la página lista todo, con filtros de pendientes y leídas.

## Parte 6 — Correo, WhatsApp y Telegram

- [ ] **6.1** Confirma, reagenda y cancela una cita **de tu propio paciente** (el de la Parte 1). *Debe pasar:* te llega un correo por cada cambio.
- [ ] **6.2** *(Cuando Meta apruebe las plantillas)* Repite 6.1. *Debe pasar:* también llega por WhatsApp.
- [ ] **6.3** *(Cuando Meta apruebe)* Prueba de recordatorio a tu celular; se hace junto con el desarrollador desde la Console. *Debe pasar:* llega con 3 botones y "Sí, asistiré" confirma la cita en el panel.
- [ ] **6.4** Escribe cualquier cosa al **número de WhatsApp del bot**. *Debe pasar:* responde que es automático y da el **teléfono real de la clínica** (no el del bot).
- [ ] **6.5** En **Telegram**, abre el bot y manda `/agendar`. *Debe pasar:* te guía con botones hasta reservar.
- [ ] **6.6** En Telegram manda `/cancelar` a mitad del proceso. *Debe pasar:* se cancela la conversación sin crear cita.

## Parte 7 — Catálogo y contenido (administrador)

- [ ] **7.1** **Servicios:** crea uno de prueba con foto. *Debe pasar:* aparece en el carrusel de la portada.
- [ ] **7.2** **Tratamientos:** crea uno y asígnalo a ese servicio. *Debe pasar:* sale al agendar en línea con ese servicio.
- [ ] **7.3** Desactiva el servicio de prueba. *Debe pasar:* desaparece de la portada y de la agenda.
- [ ] **7.4** **Ofertas**, **Testimonios** y **Preguntas frecuentes:** crea uno de cada uno. *Debe pasar:* se ven en la portada (los testimonios, solo si están aprobados).
- [ ] **7.5** **Galería** y **Casos**: sube una foto ficticia. *Debe pasar:* aparece donde corresponde.
- [ ] **7.6** **Horarios:** cambia la hora de cierre de un día. *Debe pasar:* la agenda en línea ya no ofrece horas después del cierre.
- [ ] **7.7** Borra o desactiva todo lo que creaste de prueba en esta parte.

## Parte 8 — Usuarios, reportes y auditoría (administrador)

- [ ] **8.1** **Usuarios:** crea un asistente de prueba. *Debe pasar:* puede iniciar sesión.
- [ ] **8.2** Desactiva ese usuario. *Debe pasar:* ya no puede iniciar sesión.
- [ ] **8.3** **Reportes:** descarga el PDF y el Excel. *Debe pasar:* abren y traen las citas del periodo.
- [ ] **8.4** **Auditoría:** busca las acciones que hiciste hoy. *Debe pasar:* aparecen con fecha, usuario y acción.
- [ ] **8.5** **Mensajes:** abre el mensaje que mandaste en 1.9 y márcalo como leído. *Debe pasar:* cambia su estado.
- [ ] **8.6** **Perfil:** cambia tu foto y tu nombre. *Debe pasar:* se actualizan en el encabezado; en celular nada se sale de la pantalla.

## Parte 9 — Respaldo y volumen (Console de Railway)

- [x] **9.1** `df -h /var/www/html/storage/app` → montado en `/var/www/html/storage/app` (4.6 GB). *Hecho el 20/11.*
- [ ] **9.2** Después de subir la imagen de 4.4: `ls -la storage/app/private/historial`. *Debe pasar:* aparece el archivo.
- [ ] **9.3** `php artisan dentissa:respaldar` y luego `php artisan dentissa:restaurar-prueba`. *Debe pasar:* "1 archivos con huella verificada" (o más) y "La base activa no se modificó".
- [ ] **9.4** En Railway: **Deployments → ⋮ → Redeploy**. Al terminar, repite 9.2 y abre la imagen en el panel. *Debe pasar:* el archivo y la imagen siguen ahí.

---

## Hallazgos

| Punto | Qué pasó | Dispositivo | Captura |
|---|---|---|---|
| H-1 (3.4) | El panel no permite **registrar pacientes**: "Nueva cita" solo deja elegir pacientes existentes. Un paciente que llama o llega en persona no se puede dar de alta desde recepción | Revisión de código, 20/11 | — |
| H-3 (1.2) | En celular el carrusel no regresa al inicio al deslizar con el dedo (las flechas que dan la vuelta solo existen en computadora). **Decisión 20/11: se deja así**, como los carruseles nativos; no es error | Celular del usuario, 20/11 | Sí |
| H-4 (1.2) | La etiqueta **"Agenda por teléfono"** no se lee: texto blanco sobre fondo blanco casi transparente encima de una foto clara. **Corregido** en `fix/hallazgos-verificacion`: fondo oscuro de la paleta (`bg-ink/80`), contraste ≈7:1 | Celular del usuario, 20/11 | Sí |
| H-5 (1.2) | Producción tiene el **catálogo de ejemplo** con precios inventados (Extracción Simple $100, Selladores $80…) y 7 especialidades; el catálogo real de la doctora (`ServiciosTratamientosSeeder`) nunca se cargó. Periodoncia y Odontopediatría salen "por teléfono" porque no tienen tratamientos. **Decisión 20/11: cargar el catálogo real** (2 especialidades en línea, sin precios; las de ejemplo se desactivan, no se borran). Ensayado en local. Además, el carrusel con 1–2 especialidades ya no muestra flechas ni puntos sin función y la frase "las demás… por teléfono" solo sale si hay alguna por teléfono | Revisión de datos de producción, 20/11 | — |
| H-6 (7.1) | **Editar servicio**: la foto subida no se guardaba si se pulsaba "Guardar tratamientos" (había dos formularios); no se veía la foto actual; desmarcar "Servicio activo" no lo desactivaba; fotos de celular >2 MB se rechazaban; mucho espacio vacío en escritorio. **Corregido** en `fix/hallazgos-verificacion`: un solo formulario y botón "Guardar cambios", vista previa de la tarjeta del carrusel con la foto actual o la nueva, límite de 5 MB y diseño a dos columnas que cabe sin desplazarse en una PC de 1920×1080 (encabezado compacto con el nombre del servicio y "Guardar cambios" arriba; "Visible en la portada" como interruptor; tratamientos en 3 columnas; en celular, una columna con el botón abajo). Aprobado por el usuario el 20/11. Los tratamientos inactivos ya no aparecen ni se pueden asignar (el servidor los rechaza); su vínculo queda "dormido" y vuelve solo si se reactivan. La descripción vacía ya no muestra texto de ejemplo dentro del campo (parecía escrito) ni leyendas: la tarjeta de vista previa se actualiza al escribir y, vacía, muestra el texto predeterminado que verá la portada | Computadora del usuario (local), 20/11 | Sí |
| H-7 (7.1) | ¿Bloquear "Guardar" hasta llenar todo? **Decisión 20/11: no** (un botón desactivado no explica qué falta y casi todos los campos de servicio son opcionales a propósito). En su lugar, **asterisco rojo en cada campo obligatorio** y la nota "Los campos con * son obligatorios" en los 25 formularios del panel; regla protegida por `ConsistenciaDisenoTest` | Computadora del usuario (local), 20/11 | — |
| H-8 (7.1) | El campo numérico "Orden en el carrusel" estorbaba y no se entendía qué número poner. **Corregido**: sale de los formularios; en Catálogo → Servicios la columna "Posición en el carrusel" tiene flechas ↑ ↓ (desactivadas en los extremos); los servicios nuevos van al final. `OrdenServiciosTest` (5) | Computadora del usuario (local), 20/11 | Sí |
| H-9 (2.1) | El asistente de chat (botón flotante del diente) aparecía en **todas las pantallas del panel**; es para pacientes. **Corregido**: solo en la portada y la agenda en línea; protegido por `ConsistenciaDisenoTest` | Computadora del usuario (local), 20/11 | Sí |
| H-2 (4.4) | Subir radiografías está escondido: solo aparece en **Editar** de un registro ya guardado, y desde una cita no hay enlace al historial del paciente | Reporte del usuario, 20/11 | — |
| | | | |

## Resultado

| Parte | Fecha | Resultado | Quién |
|---|---|---|---|
| 1. Sitio público | | | |
| 2. Acceso y roles | | | |
| 3. Agenda | | | |
| 4. Pacientes e historial | | | |
| 5. Notificaciones | | | |
| 6. Correo, WhatsApp y Telegram | | | |
| 7. Catálogo | | | |
| 8. Usuarios, reportes y auditoría | | | |
| 9. Respaldo y volumen | 20/11/2026 (9.1) | En curso | |
