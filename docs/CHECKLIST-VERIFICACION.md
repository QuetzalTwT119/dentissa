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
- [x] **1.2** Desliza el carrusel de especialidades. *Debe pasar:* en celular se asoma la orilla de la siguiente tarjeta (invita a deslizar); en computadora y tableta se ven tarjetas completas, sin cortes. Un punto por posición. En computadora la flecha › en la última tarjeta regresa al inicio; en celular (sin flechas) el deslizamiento se detiene en la última, por decisión de diseño (H-3). — ✔ 20/11: probado en celular (H-3 decidido; H-4/H-5 corregidos).
- [x] **1.3** Toca una especialidad con "Agenda en línea". *Debe pasar:* abre la agenda con esa especialidad ya elegida. — ✔ 20/11: la agenda abre con la especialidad elegida (verificado en navegador).
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

- [x] **2.1** Inicia sesión como **administrador**. *Debe pasar:* entra al panel. — ✔ 20/11: sesión de administradora usada en toda la revisión.
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
| H-10 (7.2) | **Desactivar no funcionaba** ("Blanqueamiento Dental" seguía activo): una casilla desmarcada no se envía y el servidor lo tomaba como "sin cambio" (en Galería, incluso como "sí"). Afectaba Tratamientos (Activo y Requiere autorización), Ofertas, Galería y crear Servicio. **Corregido**: el servidor lee cada casilla explícitamente. `CasillasDesmarcadasTest` (4) | Computadora del usuario (local), 20/11 | Sí |
| H-11 (7.x) | **Dos botones "Nuevo …"** en 8 listas (encabezado de la página y copia en la tarjeta de la tabla). **Corregido**: queda solo el del encabezado; regla en `ConsistenciaDisenoTest` | Computadora del usuario (local), 20/11 | Sí |
| H-12 (7.2) | **Tratamientos**: activar/desactivar requería abrir el formulario y bajar al final; "Requiere autorización previa" e "Imagen explicativa" no se usaban en ninguna parte del sistema; categorías fijas en código (solo informativas); formulario angosto con espacio desaprovechado. **Corregido**: el estado se cambia con un interruptor (encendido/apagado) en la lista —una etiqueta no se reconocía como clicable—; "Requiere autorización previa", "Imagen explicativa" y "Sesiones requeridas" salen del formulario porque no se usaban (los datos se conservan); crear y editar comparten un formulario a dos columnas sin desplazarse, con "Se ofrece en" (servicios que lo incluyen, con enlace). Las categorías siguen fijas para v1.0. Después: se quitó la columna "Servicios" (solo mostraba un número) y **desactivar** pide confirmación explicando la consecuencia (si un servicio se queda sin tratamientos y pasa a "Agenda por teléfono"); activar es inmediato. También salió "Descripción" (no se usaba en ningún lado) y la etiqueta de estado del encabezado de Editar; la nota del precio ahora dice la verdad: es interno, los pacientes no lo ven | Computadora del usuario (local), 20/11 | Sí |
| H-13 (varios) | Las confirmaciones usaban las **ventanas genéricas del navegador** (`confirm()` en 15 formularios y `prompt()` para el motivo de cancelación). **Corregido**: diálogo propio (`components/dialogo-confirmar`, `<dialog>` nativo) con título, mensaje, botón que dice la acción ("Sí, eliminar"), tono rojo en lo peligroso, foco inicial en "Cancelar" y campo de motivo dentro del mismo diálogo. Probado en navegador: Esc, Cancelar y clic en el fondo no envían; confirmar envía una sola vez. Regla en `ConsistenciaDisenoTest` | Computadora del usuario (local), 20/11 | Sí |
| H-14 (7.2) | Decisiones del 20/11 sobre tratamientos: **Categoría** fuera (no se usaba); **precio visible** al paciente (agenda en línea y resumen) y **sin precio se guarda pero no se publica** (aclarado con el usuario: guardar sí, publicar no) (interruptor bloqueado con "Falta precio"; migración desactiva activos sin precio); precios de demostración en el catálogo real; "Se ofrece en" pasa a **"Así lo verá el paciente"** (vista previa en vivo) y la relación con servicios se edita solo en el servicio. Verificado en navegador: agenda en línea con precios en escritorio y celular, y fila "Precio" en el resumen | Computadora del usuario (local), 20/11 | Sí |
| H-15 (7.x) | **Crear, guardar y eliminar no avisaban nada**: el layout del panel no pintaba los mensajes (solo Citas y Notificaciones) y 36 acciones no enviaban mensaje. **Corregido**: aviso global abajo a la derecha (éxito 6 s, error hasta cerrarlo) y mensaje específico en las 36 acciones; sin duplicados. `AvisosAccionesTest` (4) | Computadora del usuario (local), 20/11 | Sí |
| H-16 (7.2) | Burbuja genérica del navegador **"Completa este campo"**. **Corregido**: el error sale en rojo debajo del campo con el diseño de Dentissa, foco en el primero y se borra al corregir. Probado en navegador (vacío, mínimo, correo, corrección, envío único). Nota: "Falta precio" no aparecía porque todos los tratamientos locales ya tienen precio; solo se ve con uno sin precio | Computadora del usuario (local), 20/11 | Sí |
| H-17 (7.x) | La barra "Mostrando… / Mostrar 20 por página / Página 1 de 1" estaba **abajo** de cada lista y se perdía; además había una cabecera de tarjeta que repetía el título ("Procedimientos clínicos…") y el botón "Nuevo …" estaba fuera de la tarjeta (y era un `<button>` dentro de un `<a>`). **Corregido** en las 16 listas: arriba "Mostrando…/Mostrar por página" a la izquierda y [Nuevo …] a la derecha, dentro de la tarjeta; abajo, centradas, las páginas o "Página 1 de 1" (ajustado con el usuario); sin cabeceras repetidas; el botón es un enlace válido y sigue visible con la lista vacía. `BarraListasYPagoTest` | Computadora del usuario (local), 20/11 | Sí |
| H-18 (1.4) | El paciente no sabía **cómo se paga**. **Agregado**: "El pago se realiza en el consultorio el día de tu cita" al elegir tratamiento, junto al precio del resumen, en la confirmación final (con el precio) y en los correos de cita agendada, confirmada y reagendada. Verificado en navegador | Computadora del usuario (local), 20/11 | Sí |
| H-19 (7.1) | ¿Un tratamiento puede estar en varios servicios? **Sí, a propósito** (relación muchos a muchos: Valoración en General y Ortodoncia, sin duplicar precios). Mejora: en Editar servicio, nota "También en X (+N)" bajo cada tratamiento que ya está en otro servicio (un nombre + contador; lista completa al pasar el mouse). Además, la vista previa de Editar tratamiento partía el nombre letra por letra con "Falta precio": ahora se ve como en celular (nombre arriba; duración · precio abajo) | Computadora del usuario (local), 20/11 | Sí |
| H-20 (7.x) | (1) Guardar no pedía confirmación: ahora **los 19 formularios de crear/editar** del panel preguntan "¿Guardar los cambios del …?" / "¿Crear el …?" con el diálogo propio (las acciones rápidas —flechas, interruptores, "Ya lo atendí"— no, para no estorbar). (2) Guardar un servicio se quedaba en la misma página: ahora vuelve a la lista, como los demás. A propósito NO vuelven a la lista: crear un **caso** (abre su edición para subir las fotos de antes/después), **historial** (vuelve al historial del paciente), adjuntar/eliminar archivos (se queda para seguir adjuntando) y **horarios** (no tiene lista: es una sola pantalla). (3) Las flechas de posición se rediseñaron: número en círculo de la marca y control redondeado ↑ \| ↓ | Computadora del usuario (local), 20/11 | Sí |
| H-21 (7.x) | (1) "Visible en la portada" no decía todo: apagar un servicio lo oculta en portada, agenda en línea, nueva cita del panel, asistente y bot → ahora dice **"Disponible para agendar"** (con explicación al pasar el mouse). (2) El mensaje de lista vacía quedaba **descentrado**: el componente esperaba `colspan` y las listas mandaban `cols` → ahora abarca siempre todas las columnas. (3) **Galería y Casos no se muestran en ninguna página pública** (la portada recibe los datos pero no los pinta) → se **ocultan del menú** con `DENTISSA_MODULO_GALERIA` / `DENTISSA_MODULO_CASOS` (no se borran). (4) **Ofertas** y **testimonios**: resuelto en H-22 | Computadora del usuario (local), 20/11 | — |
| H-22 (1.x) | **Portada**: (a) nueva sección **Promociones** con las ofertas activas y vigentes (descuento, tratamiento, vigencia y lugares restantes; solo aparece si hay alguna). (b) La sección de testimonios mostraba **tarjetas fijas** y nunca los aprobados → ahora muestra los **aprobados** (nombre + inicial del apellido y estrellas). (c) Formulario **"Comparte tu experiencia"**: el paciente se identifica con el teléfono o correo con el que agendó; entra **pendiente**, avisa al personal en la campana y se publica al aprobarse en Contenido → Testimonios. Privacidad: misma respuesta exista o no el paciente; trampa para robots y límite de 5 envíos por hora. `TestimoniosYPromocionesPublicasTest` (5). Verificado en navegador | Computadora del usuario (local), 20/11 | Sí |
| H-23 (7.x) | Íconos **Editar/Eliminar distintos en cada lista** (cada una los armaba a su manera; en Preguntas el ícono de basura se movía). **Corregido**: componente compartido `x-admin.acciones-fila` en 8 listas (mismo tamaño, 44 px en celular, confirmación al eliminar). El **filtro de Testimonios** estaba en una franja aparte con etiqueta larga → ahora va **dentro de la barra** de la lista ("Estado: Todos ▾") mediante el nuevo espacio `filtros` del componente de barra | Computadora del usuario (local), 20/11 | Sí |
| H-24 (7.4) | Testimonios: Acciones mostraba **dos botes de basura**; además la clínica podía **crear y editar** testimonios a nombre de pacientes (serían reseñas falsas). **Corregido**: la clínica solo **modera** — ✓ Aprobar, ✕ Rechazar (mismo tamaño que los demás íconos) y 🗑 Eliminar; se quitaron "Nuevo testimonio", "Editar" y sus rutas. Rechazado = se queda en la lista con estado "Rechazado", no se publica y se puede aprobar después; Eliminar lo borra para siempre | Computadora del usuario (local), 20/11 | Sí |
| H-25 (7.4) | (a) En el panel, la columna **Acciones de Testimonios se cortaba**: un nombre largo de paciente empujaba la tabla fuera de la tarjeta → el nombre se recorta (completo al pasar el mouse) y Acciones ya no se desborda. (b) En la portada, los testimonios ocupaban mucho → **tarjetas compactas** (texto normal, máximo 5 líneas) en el **mismo carrusel** de las especialidades (flechas y puntos solo si hay más de los que caben). Verificado: un testimonio rechazado no aparece en la portada | Computadora del usuario (local), 20/11 | Sí |
| H-26 (Horarios) | (a) El **pie de la portada decía "Horarios por confirmar"** aunque ya estaban configurados → ahora muestra el horario real de **Panel › Horarios** agrupando días iguales ("Lunes a viernes de 8:00 a 18:00 hrs"). (b) La pantalla de Horarios era una tabla alta con el botón al final → **un renglón compacto por día** con interruptor Abierto/Cerrado, "de __ a __ hrs" y **Citas a la vez** (antes "Capacidad simultánea", con explicación); Guardar arriba en PC. (c) Ya no deja guardar un día abierto cuya hora de cierre sea antes de la de apertura | Computadora del usuario (local), 20/11 | Sí |
| H-27 (1.4–1.5) | Agenda en línea: (a) el día y la hora elegidos **no se podían quitar** → tocarlos otra vez los deselecciona. (b) Día y horas juntos obligaban a desplazarse → pasos **Tipo → Día → Hora → Datos** (el paso "Resumen" sobraba: el resumen ya está a la derecha y sobre los datos); las horas en **cuadrícula** (solo inicio; el resumen dice "08:00 a 09:00"). La página **se adapta al alto de la ventana** (no a px fijos): medido en 1536×748 (la ventana real del usuario) Día, Hora y Datos caben sin desplazarse; en pantallas más bajas (1280×600) solo se desplaza el interior de la tarjeta con los botones a la vista; en celular los botones quedan fijos abajo. Solo la lista de especialidades/tratamientos puede desplazarse. Calendario a la izquierda y horario del consultorio a la derecha. Cambiar de tratamiento limpia el día y la hora elegidos. (c) **Correo opcional**, celular obligatorio: sin correo el paciente se reconoce por su celular y, si después da correo, se le guarda. (d) Se quitó **"Motivo de la visita"** (se platica en la consulta). (e) El teléfono se acomoda como **"755 113 1833"** (3-3-4, sirve para cualquier lada; antes suponía la de CDMX) | Computadora del usuario (local), 20/11 | Pendiente de revisión |
| H-28 (todo el panel) | Las pantallas se **ajustaban con medidas fijas** y en la ventana real (1920×1080 al 125 % ≈ 1536×748) casi todas obligaban a desplazar la página (Auditoría sobraba 1 487 px; Ofertas, Paciente, Perfil, Reportes, Inicio, etc.) → patrón **ajustado a la ventana** en las 41 pantallas: listas con la tabla desplazable por dentro (encabezados, filtros y paginación fijos), formularios en 2–3 columnas con los botones siempre a la vista, Perfil en pestañas, Nueva cita con el calendario a la derecha, Inicio y Reportes compactos. Medido automáticamente en 1536×748, 1024×700 y 375×812 (sin desplazamiento lateral en celular) | Computadora del usuario (local), 20/11 | Pendiente de revisión |
| H-29 (todo el panel) | Piezas con **diseños distintos**: 7 filtros hechos a mano (con y sin etiquetas, botones Filtrar/Buscar), 20 encabezados, 20 tarjetas, 30 campos largos/listas, 8 botones y 8 chips → **componentes globales** (`x-filtro.*`, `x-admin.header`, `x-admin.card`, `x-textarea`, `x-select`, `x-chip`, `.btn-brand`). Filtros: mismo lugar, se aplican solos, activo resaltado, "Limpiar filtros" solo si hay filtro, buscador sin recargar. La flecha de las listas ya no tapa el texto. Reportes: descargas en la tarjeta del filtro, sin botón Filtrar | Computadora del usuario (local), 21/11 | Pendiente de revisión |
| H-30 (todo el panel) | Aprobar/Rechazar testimonio, Confirmar/Iniciar/Completar cita, marcar leído, mover servicio, activar tratamiento, historial, adjuntos y perfil **se aplicaban de un clic** → **todo cambio pide confirmación** con el diálogo del sistema; botones de fila con `x-admin.boton-fila` (siempre confirma). Excepciones: acceso, páginas públicas, "Proponer horarios" y "Activar verificación" | Computadora del usuario (local), 21/11 | Pendiente de revisión |
| H-31 (Ofertas) | (a) El formulario pedía **Tipo de oferta, Tipo de valor, Valor del descuento y Precio especial**, que se contradecían (el precio especial ganaba sin avisar; una bonificación con $300 salía "−$300") → **una sola pregunta "¿Qué ofreces?"** (% de descuento / $ de descuento / Precio especial / Gratis) y **una cantidad** con su símbolo; "Desde" viene con la fecha de hoy. (b) La imagen se pedía pero **no se veía en la portada** → ahora sí. (c) La vista de ofertas del personal mostraba "$20 de descuento" para un 20 % (comparaba el enum con texto) → misma etiqueta en todas partes. (d) Editar dejaba vacías las fechas. (e) La sección de la portada se llama **Ofertas**. (f) Seguían siendo muchos campos → lo opcional va plegado en **Más detalles**; la opción elegida se pinta de color con ✓. (g) Las tarjetas de la portada eran muy grandes → **compactas, 4 por fila**, sin carrusel | Computadora del usuario (local), 21/11 | Pendiente de revisión |
| H-32 (Ofertas) | (a) Sobraban la casilla **Mostrar** y la fecha **Empieza** → se quitaron (cuenta desde hoy). (b) **¿Qué ofreces?** se desmarca al tocar la opción elegida y la elegida usa el rosa claro de la marca (fondo brand-50, borde brand-400) en lugar del relleno saturado; sin palomita. (c) Toda oferta nueva o editada queda **En revisión**; se publica con ✓ y se oculta con el botón de encendido; el público solo ve **Publicadas** vigentes. (d) Validación estricta: título 5–120, descripción 20–300, sin enlaces/correos/código/texto repetido, fecha no pasada. (e) **Tratamiento** se conservó: es un dato real (`id_tratamiento`) que la portada muestra. (f) **Acciones** descentrado → en PC el encabezado y los botones van centrados en todas las listas, sin separador. (g) Un título largo desbordaba la tabla → título y tratamiento usan 2 líneas. (h) "¿Qué ofreces?" era ambiguo y "% de descuento"/"$ de descuento" repetían lo mismo → **Beneficio para el paciente** con 3 opciones (Descuento con un solo botón de unidad que alterna % y $ junto a la cantidad, Precio especial, Gratis) y diseño global `x-opcion`. QA manual 21/11: 4 ofertas creadas desde el formulario (enlace, 120 %, texto corto y signos repetidos rechazados), publicadas con ✓, una ocultada y vuelta a publicar | Computadora del usuario (local), 21/11 | Pendiente de revisión |
| H-33 (Ofertas ↔ citas) | (a) El paciente no veía la oferta al agendar → etiqueta en el tratamiento, aviso con vigencia y fila "Oferta" en el resumen y la confirmación; "Agendar" abre con el tratamiento elegido. (b) La cita no guardaba la oferta → `citas.id_oferta`; la doctora la ve en la agenda y en la campana y la aplica al cobrar. (c) "Quedan N lugares" nunca bajaba → se cuenta con citas reales (cancelar o no asistir libera); agotada deja de anunciarse. (d) Ofertas ligadas a tratamientos inactivos → solo tratamientos agendables. (e) Botón de unidad % / $ quitado: descuento solo en %. (f) "Tratamiento inactivo: no se muestra" ocupaba 2 renglones → símbolo ⚠ con explicación al pasar el mouse o tocar (`x-ayuda`, global). (g) Al bajar en una tabla larga, la primera fila se montaba sobre "TÍTULO" y su celda no tomaba el tono de la fila → esquina con `z-index` 3 y mismo color |  Pruebas automáticas (`OfertasEnCitasTest`), 21/11 | Pendiente de revisión |
| H-34 (Formularios) | (a) "Guardar cambios" se podía tocar sin haber cambiado nada → desactivado hasta que algo cambie, en todos los formularios de edición (global, `app.js`), incluidos los botones de arriba de Servicios y Tratamientos. (b) "Volver al listado"/"Volver a la agenda"/"Volver al historial" arriba y "Cancelar"/"Volver" abajo hacían lo mismo en 15 pantallas → un solo "Cancelar" junto a "Guardar"; Historial tenía "Volver a Pacientes" dos veces | Navegador interno (Ofertas y Servicios) + `BotonesFormularioTest`, 21/11 | Pendiente de revisión |
| H-35 (Nueva oferta) | (a) "Tratamiento al que aplica" estaba escondido en "Más detalles" aunque decide si la oferta se aplica al agendar → a la vista, con la nota "Sin tratamiento, la oferta solo se anuncia". (b) Guardar vacío no marcaba "Beneficio para el paciente" (los botones del diseño global perdieron `required`) → "Elige una opción." y borde rosa, también para cualquier grupo `x-opcion` (global). El foco va al primer error (Título); no se reprodujo que saltara a Tratamiento | Navegador interno + `OfertasEnCitasTest`, 22/11 | Pendiente de revisión |
| H-36 (WhatsApp) | Prueba real de `cita_recordatorio` al celular del consultorio: llegó con encabezado, las 3 variables y los botones Sí, asistiré · Cancelar cita · Reagendar. Fallas encontradas en el camino: token temporal vencido (401, código 190), contenedor con una copia vieja del token (se recrea con `up -d --force-recreate`), `DENTISSA_WHATSAPP_PHONE_ID` vacío (400, código 100/33) y API `v21.0` → `v25.0`. El número del consultorio pasa a 755 113 1833 (el bot es 755 102 0089). Falta probar la respuesta del botón en Railway (el webhook no llega a localhost) | Celular del consultorio, 22/11 | Parcial: falta Railway |
| H-37 (Configuración) | (a) El teléfono del consultorio solo se cambiaba con variables de Railway: si la doctora perdía el celular dependía de alguien con acceso al servidor → **Datos del consultorio** (teléfono, WhatsApp de contacto, correo y dirección) en el panel, solo administradores, con confirmación y Auditoría. (b) Configuración era un menú con una página por ajuste → **una sola página** con los bloques Datos del consultorio y Horarios, cada uno con su botón | `DatosConsultorioTest` + navegador interno, 22/11 | Pendiente de revisión |
| H-38 (Pruebas y barra) | (a) **Crítico:** con el token y el número reales en `.env`, 10 pruebas sin `Http::fake()` mandaron WhatsApp de verdad (3 con números al azar) → las pruebas anulan las credenciales y bloquean toda salida a internet (`Http::preventStrayRequests()`). (b) La barra de desplazamiento del panel quedaba en medio, junto a las tarjetas, y en las listas dentro de la tabla → en la orilla de la ventana en todas las pantallas; en las listas se desplaza la página y la tarjeta completa sube con ella (sin encabezado fijo, que hacía parecer que la tabla se desplazaba por dentro). (e) En las tablas largas, la primera celda del encabezado ("Fecha y hora") tenía otro tono → el mismo que el resto. La paginación ya era global (`x-pagination-summary`). (d) "Auditoría" no describía lo que muestra → se llama **Control de actividades**. (c) Al eliminar o publicar desde una fila, el indicador de carga quedaba apilado encima del ícono → ocupa el lugar del ícono (global) | Suite completa (427) + navegador interno, 22/11 | Hecho |
| H-39 (QA 21 ofertas) | Se crearon 21 ofertas por el formulario real (límites: título de 120, descripción de 300, $2,999.50, precio $1, vence hoy, sin tratamiento) y 14 intentos inválidos: los 14 rechazados con su motivo. Hallazgos corregidos: (a) con varias ofertas del mismo tratamiento la agenda solo conocía la que vence primero y con una fecha posterior decía "ya no aplica" aunque al guardar se aplicara otra → recibe todas y elige igual que el servidor; (b) la portada tomaba 12 y después quitaba las agotadas → `Oferta::paraPortada()`; (c) "ya se ve en la portada" aunque no cupiera → el mensaje lo dice; (d) 100 % salía "−100%" → pide elegir Gratis; (e) tratamiento inactivo sin explicación → mensaje claro; (f) descripción cortada con "…" y cuadrícula cargada a la izquierda → carrusel global con la descripción completa (hasta 24) | Navegador interno + `OfertasEnCitasTest`, 22/11 | Pendiente de revisión |
| H-2 (4.4) | Subir radiografías está escondido: solo aparece en **Editar** de un registro ya guardado, y desde una cita no hay enlace al historial del paciente | Reporte del usuario, 20/11 | — |
| | | | |

## Para la siguiente versión (después de v1.0)

- **Aviso de novedades en las listas** (Testimonios y Citas): cuando llega algo
  nuevo, mostrar arriba de la tabla "Hay 1 testimonio nuevo · Ver ahora" en vez
  de redibujar la lista sola. Hoy la campana sí avisa en tiempo real; las listas
  se actualizan al recargar (decisión del 20/11/2026).
- Página completa de notificaciones con avatares, fotos en WebP y unificar las
  descripciones de servicios de la agenda con `config('dentissa.descripciones_servicios')`.

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
