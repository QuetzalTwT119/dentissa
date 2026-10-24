# Manual de usuario de Dentissa

## Revisión de cambios de la portada

Antigravity trabaja en la misma carpeta que Dentissa. Cuando termine un cambio visual, abre PowerShell en `C:\laragon\www\dentissa` y ejecuta `powershell -ExecutionPolicy Bypass -File .\scripts\antigravity.ps1`. El sistema revisa automáticamente la portada y después actualiza la aplicación local. Finalmente abre `http://localhost:8000` para revisar el resultado.

## 1. Acceso

> Nota para la clínica: el contenedor de validación `qa` es una herramienta
> interna del equipo técnico. No modifica citas, pacientes ni historiales y no
> requiere ninguna acción del personal clínico.

Abre <http://localhost:8000>. El personal de la clínica inicia sesión desde
`/login`. Los pacientes pueden agendar desde el formulario público sin crear
una cuenta.
La pantalla de acceso combina una fotografía del consultorio con el nombre e
identidad de la clínica, el formulario de inicio de sesión y un enlace para
volver al sitio público. Usa **Recuérdame**
solo en un dispositivo de confianza; si no recuerdas la contraseña, abre
**¿Olvidaste tu contraseña?** para solicitar un enlace de recuperación.

La navegación interna muestra la marca Dentissa, un acceso a **Inicio**, otro a
la **Agenda** y otro al **Asistente**. En administración, **Gestión** agrupa
Pacientes, Usuarios, Reportes, Mensajes y Auditoría; los
menús **Catálogo**, **Contenido** y **Configuración** reúnen el resto de las
secciones. En pantallas angostas, usa el botón de menú para encontrar las
mismas opciones y la campana para abrir una vista rápida de notificaciones.
En el teléfono, los listados anchos conservan sus
columnas dentro de un área que puedes desplazar lateralmente; los filtros y
controles de carga se acomodan en varias filas para facilitar el toque.
**Auditoría** solo está disponible para administración; las cuentas de
asistente no ven esa opción.

Los listados de registros muestran **20 elementos por página de forma predeterminada**. En el pie del listado puedes elegir **20** o **50** registros por página. Cuando
hay más resultados, al final aparecen los botones **Anterior**, los números de
página y **Siguiente**; los filtros actuales se conservan al cambiar de página.

Desde el menú de usuario abre **Perfil** para actualizar nombre, apellido,
teléfono y correo, o para cambiar la contraseña confirmando la actual. La
desactivación de cuentas se administra desde **Gestión → Usuarios**; el perfil
no ofrece eliminar ni desactivar la propia cuenta, para proteger la continuidad
de la clínica y sus registros.

En la sección **Foto de perfil** puedes seleccionar una imagen JPG, PNG o WebP
de hasta 5 MB y pulsar **Subir foto**. La imagen aparece en el resumen del
perfil y junto al nombre del menú de usuario; **Quitar foto** la elimina y
restaura las iniciales. Solo la cuenta que inició sesión puede consultar su
imagen.

El inicio del administrador reúne accesos rápidos para agendar citas,
consultar pacientes, administrar servicios y
contenido, y gestionar usuarios. Las cuentas de asistente ven un inicio más
sencillo con accesos a sus citas y al asistente de agenda. El sitio también
puede instalarse como aplicación compatible; si se pierde la conexión, muestra
una pantalla para reintentar al volver a estar en línea.

### Demostración escolar en Railway

Para la presentación, el administrador y su ayudante usarán cuentas ficticias
separadas que prepara el equipo desde las variables privadas de Railway. Las
credenciales no se escriben en este manual ni se reutilizan las cuentas locales
de Docker. El administrador gestiona usuarios, pacientes, citas, historial,
catálogo, contenido y seguimiento; el ayudante opera los flujos permitidos por
su rol. Para el recorrido público se puede agendar una cita con datos
completamente ficticios. El paciente precargado es «Paciente Demostración» y no
tiene historial clínico.

Los nombres, teléfonos, correos y precios del catálogo demo son de prueba. No
son datos ni tarifas de una clínica real. Al terminar la exposición, el equipo
debe apagar o eliminar el servicio de Railway desde el panel.



La página principal presenta una portada cálida para Dentissa con fotografía,
servicios, galería y un acceso directo a la agenda. El botón **Elegir fecha y
horario** abre el flujo público validado; ahí se seleccionan tratamiento, fecha
y hora antes de solicitar la cita. La portada no crea citas por sí misma ni usa
teléfonos, correos, horarios, calificaciones o testimonios como si ya fueran
datos oficiales. La clínica debe aprobar ese contenido antes de publicarlo.

La navegación **Inicio**, **Tratamientos**, **La Clínica**, **Instalaciones**,
**Testimonios** y **Contacto** se desplaza suavemente a su sección sin recargar
la página; también funciona desde el menú móvil y respeta la preferencia del
dispositivo para reducir movimiento.

## 2. Agendamiento público

1. Abre `/agendar-cita`.
2. Selecciona un servicio.
3. La lista de servicios se sustituye por los tratamientos disponibles. Si
   cambias de opinión, pulsa el recuadro del servicio seleccionado para volver
   a la lista.
4. Elige una fecha futura.
5. Selecciona un horario disponible.
6. Escribe nombre, apellido, teléfono y correo.
7. Pulsa **Agendar cita**.

En pantallas amplias, junto al formulario aparece un indicador compacto del paso
actual y un resumen que se actualiza con el servicio, tratamiento, fecha y hora
seleccionados. En móvil, ese panel se acomoda debajo del formulario para mantener
los controles cómodos. Al elegir un servicio, su nombre permanece visible sobre
los tratamientos para conservar el contexto; el mismo recuadro permite volver
a elegir otro servicio.

El sistema crea o reutiliza el paciente por correo, bloquea el horario y crea
la cita en estado **Confirmada**. La pantalla muestra la confirmación; no hace
falta que el personal pulse una segunda vez **Confirmar**.

## 3. Gestión interna de citas

Desde `/citas`, el personal puede:

- crear una cita seleccionando paciente, servicio, tratamiento, fecha y hora;
- las citas nuevas creadas aquí quedan confirmadas en cuanto el horario se
  reserva; las citas históricas que sigan agendadas conservan la opción de
  confirmación manual;
- reagendar una cita a otro horario disponible;
- iniciar la atención de una cita confirmada;
- marcar una cita confirmada como **No asistió**, solo desde la hora programada;
- completar una cita;
- cancelarla indicando un motivo opcional;
- buscar por nombre o apellido del paciente, teléfono, correo, servicio o
  tratamiento, y combinar la búsqueda con los filtros por fecha y estado. La
  lista se actualiza automáticamente tras dejar de escribir; no es necesario
  pulsar un botón de búsqueda.

El filtro de fecha permite consultar citas pasadas, de hoy o futuras; no crea ni
modifica citas. La fecha futura es válida aquí porque la agenda incluye
reservas próximas.

Estados principales:

```text
Reserva pública, Telegram o interna → Confirmada → En curso → Completada
                                     │
                                     ├→ Reagendada → Confirmada
                                     ├→ No asistió
                                     └→ Cancelada

Cita histórica pendiente → Agendada → Confirmada o Cancelada
```

Una cita no se puede completar directamente desde **Agendada** o
**Confirmada**. Primero se debe pulsar **Iniciar** cuando comienza la
atención. La agenda muestra una acción principal según el estado y agrupa
opciones secundarias en el botón **Acciones**. El menú muestra sus opciones con
iconos y separa visualmente la cancelación. Las acciones disponibles dependen
del estado actual de la cita: **Acciones** aparece solo en citas agendadas o
confirmadas. En la agenda, la acción principal y el menú **Acciones** se
presentan alineados en una misma línea para ubicarlos rápidamente. Una cita **En
curso** muestra únicamente **Completar**, y los estados finales muestran **Sin
acciones**.
La inasistencia no se puede registrar antes de la fecha y hora programadas.
Después de una acción válida, la agenda muestra el estado actualizado y un
aviso de confirmación; si la operación no procede, muestra el motivo del error.
En pantallas angostas, la tabla conserva las acciones en una sola línea y permite
desplazamiento horizontal para consultar sus columnas sin comprimir los controles.
Las listas administrativas de pacientes, usuarios, citas y catálogo también
conservan sus columnas y ofrecen desplazamiento horizontal cuando la pantalla no
alcanza; los formularios con campos relacionados se apilan en móvil y se vuelven
a distribuir en columnas al tener más espacio.

Al agendar o reagendar, el sistema vuelve a comprobar que el servicio y el
tratamiento estén activos, que el tratamiento pertenezca al servicio y que la
fecha/hora sea futura. Si el horario ya fue ocupado, se debe elegir otro.

## 4. Asistente de agenda

En `/asistente-agenda`, el personal selecciona los datos de la cita. El
asistente consulta disponibilidad y propone horarios. No guarda nada hasta
pulsar **Confirmar y agendar**. Se pueden buscar horarios para hoy o fechas
futuras; las fechas pasadas se rechazan también en el servidor.

## 5. Asistente para pacientes y preguntas frecuentes

El asistente flotante responde con preguntas frecuentes activas y sus respuestas
aprobadas. En desarrollo se puede habilitar un modelo de IA local para reconocer
otras formas de preguntar y elegir una FAQ o un servicio del catálogo. La
aplicación muestra el texto aprobado original; el modelo no redacta la respuesta.
Para precios sin una FAQ que hable de ellos, síntomas, medicamentos y
diagnósticos, el chat pide reformular la duda o contactar al consultorio.
En todas las pantallas, el asistente permanece disponible en una cápsula
horizontal compacta, con un ícono dental y su nombre en la esquina inferior
derecha. Al abrirlo, el panel aparece cerca, justo encima del acceso.
En la agenda pública, el flujo principal se presenta primero.

El inicio, las ofertas, la galería y la agenda pública se adaptan al
ancho de la pantalla: las tarjetas pasan a una o varias columnas según el
espacio disponible y los formularios conservan sus controles a lo ancho. La barra
de desplazamiento se oculta por estética, pero la página sigue desplazándose con
rueda, teclado o gestos. En teléfonos, el formulario reduce espacios y los
campos conservan un área cómoda para tocar. En escritorio, el formulario usa
el ancho disponible y deja un margen exterior estrecho para el acceso flotante.
La
portada tiene una composición editorial: mensaje principal y agenda a la
izquierda, fotografía de la clínica a la derecha y acceso directo a la agenda.
Los servicios se recorren en un carrusel continuo con
imágenes alineadas y tarjetas de altura uniforme. Contacto conserva el fondo claro
de la marca y presenta el botón de agenda separado de las tarjetas de
información; no muestra una tarjeta de contacto vacía si no hay datos
configurados. En la portada el asistente flotante permanece visible sobre las
tarjetas informativas. El acceso combina el ícono dental de la marca y el nombre
en una sola cápsula horizontal, sin una etiqueta suelta debajo. El chat abierto
queda más compacto y se reduce el espacio inferior y lateral que no aporta al
contenido. Los datos de
contacto se marcan como ficticios mientras `DENTISSA_MODO_DEMO=true`; en ese
modo los números, correos y Telegram no son enlaces accionables. Antes de abrir
el sitio a pacientes, configura `DENTISSA_MODO_DEMO=false` y completa los datos
reales. Telegram usa `DENTISSA_TELEGRAM_URL` fuera de la demostración. Nunca
presentes teléfonos, direcciones, horarios, credenciales o testimonios de
ejemplo como información real.

El personal administrador gestiona las FAQs desde **Contenido → Preguntas
frecuentes**. En cada registro debe escribir información verificada y aprobada;
no hace falta anticipar todas las palabras que podría usar un paciente. Las
formas alternativas son opcionales. Para que una FAQ pueda usarse como fuente
debe estar activa y visible. El prototipo también consulta los servicios y
tratamientos activos del catálogo, sin que el personal tenga que volver a
capturarlos en preguntas frecuentes.

Antes de publicar cambios, probar preguntas directas, paráfrasis, errores leves,
temas sin información disponible y consultas clínicas. La IA local solo está
disponible cuando el equipo de desarrollo la habilita en Docker; su activación no
significa que el modelo esté disponible en el sitio desplegado. Si no puede
apoyarse en una respuesta activa, debe mostrar el mensaje de respaldo.

## 6. Pacientes e historial clínico

Desde `/admin/pacientes`:

1. Buscar por una parte del nombre, correo o teléfono, o seleccionar un paciente de la lista.
2. Abrir **Editar** para corregir su nombre, apellido, teléfono y correo. Nombre y apellido son obligatorios; teléfono y correo son opcionales. El correo debe ser único y el teléfono debe contener diez dígitos, con código `+52` opcional.
3. En esa misma pantalla, actualizar si hace falta CURP, RFC, clave de elector o tutor/responsable; son opcionales.
4. Abrir **Ver historial**.
5. Pulsar **Nueva consulta**.
6. Completar los campos indicados como **obligatorios**: fecha, tipo y motivo. Diagnóstico, tratamiento, medicamentos, observaciones y notas privadas son opcionales; la cita relacionada también es opcional cuando aparece.
7. Guardar la consulta.
8. Consultar, editar o eliminar las consultas. El PDF se habilita cuando existe al menos una consulta.

El historial clínico está reservado al administrador. El campo **Notas privadas del doctor**
es visible para el administrador y no se incluye en el PDF descargable.

Durante las pruebas se deben usar datos ficticios. No se deben subir imágenes,
documentos ni datos médicos reales al entorno de desarrollo.

## 6.1 Cuentas del personal

En **Usuarios**, el administrador puede filtrar las cuentas por rol y estado,
crear una cuenta para una asistente o administrador, editar sus datos y activar
o desactivar el acceso. Los pacientes se gestionan desde **Pacientes**, en una
sección distinta.

## 7. Reportes administrativos

En **Reportes**, el administrador selecciona las fechas **Desde** y **Hasta**
y pulsa **Filtrar**. El panel resume pacientes, citas por estado, tratamientos
más solicitados, consultas clínicas y métricas de ocupación/eficiencia. Si no
hay citas completadas, las métricas correspondientes pueden mostrar cero.
El rango solo permite fechas hasta hoy; el selector bloquea fechas futuras y el
sistema también las rechaza si se envían manualmente.
El total histórico de pacientes y el conteo del catálogo activo son acumulados
actuales; las citas, nuevos pacientes, consultas y métricas operativas sí usan
el rango seleccionado. Los mismos alcances se indican en las descargas.

Los botones permiten descargar un PDF o un libro de **Excel** (`.xlsx`). El
libro incluye los indicadores y resúmenes en una hoja con etiquetas en español;
se puede abrir y editar en Excel o LibreOffice Calc.

## 8. Auditoría administrativa

El administrador puede abrir **Configuración → Auditoría** para consultar los
cambios realizados por cuentas del administrador o de las asistentes. La vista
permite filtrar por fechas, usuario y acción, y mostrar los metadatos anteriores
y posteriores. Es de solo lectura; las asistentes no tienen acceso. Las
solicitudes iniciadas por pacientes desde el portal o Telegram no aparecen en
la bitácora. Los registros anteriores de esos canales se conservan en la base
de datos, pero no se muestran como cambios del personal. Se registran cambios
de citas, pacientes e historial clínico, usuarios, perfil,
catálogo, contenido público, horarios y mensajes de contacto. No se guardan
contraseñas ni notas clínicas; al editar un paciente, la auditoría indica qué
campos cambiaron, pero no copia sus valores de teléfono o correo. Las consultas,
descargas y acciones de pacientes
no se consideran cambios del personal.

## 11. Notificaciones internas

Administradores y asistentes pueden abrir **Notificaciones** desde el ícono de
campana. El contador muestra cuántos avisos son **nuevos** desde la última vez
que se abrió la campana; no equivale a los avisos pendientes de atender. La bandeja
resume el total, separa avisos en **Todas**, **Pendientes** y **Leídas**, y
permite cambiar un aviso leído de vuelta a pendiente. También permite marcar
todas las pendientes como leídas. Cada aviso muestra su fecha, estado y acceso
a la cita relacionada. Al pulsar la campana se abre una vista rápida de hasta
cinco avisos pendientes sin salir de la página actual; **Ver todas las
notificaciones** abre la bandeja completa. La bandeja informa cuando se registra, reagenda,
confirma, cancela, inicia o completa una cita, o cuando se registra una
inasistencia. Las solicitudes hechas desde el
portal o Telegram también avisan al personal; estos avisos no se agregan a la
auditoría como si los hubiera realizado un empleado. Cada persona ve sus
propios avisos y puede marcarlos como leídos individualmente o todos a la vez.
Al abrir la campana, los avisos se registran como **vistos** y el contador de
novedades queda en cero, pero conservan su estado pendiente y su punto rosa
hasta que se abra el aviso o se marque como leído. El centro completo agrupa los avisos por **Hoy**, **Ayer** y
**Anteriores**, y etiqueta el tipo de cambio; cancelaciones e inasistencias se
destacan para facilitar el seguimiento. Al abrir un aviso desde esa lista o desde el aviso emergente,
el sistema lleva a la cita relacionada sin cerrar la sesión.
Con la sesión abierta, la notificación aparece al instante en un aviso
emergente y el contador se actualiza sin recargar la página. Si el canal en
vivo se desconecta temporalmente, el sistema revisa novedades cada pocos
segundos como respaldo. En la bandeja de notificaciones, el control **Activar
sonido** habilita una señal breve al llegar un aviso y guarda la preferencia en
ese navegador; el primer clic también confirma el sonido. Se puede silenciar
desde el mismo control.

Los avisos son internos del sistema y no dependen de WhatsApp ni envían un
mensaje externo al paciente.

## 12. Experiencia visual y navegación boutique

Dentissa cuenta con una interfaz visual unificada con paleta cálida (rosa `#d75078`, durazno `#f2b0a6`, fondo `#FAF6F6`, texto `#2D2526` y verde salvia `#4E8D72`), pensada para transmitir tranquilidad y profesionalismo tanto a los pacientes como al equipo de la clínica:

1. **Navegación fluida y adaptativa:**
   - En computadoras de escritorio, la barra superior permanece accesible con accesos directos claros a Inicio, Agenda, Asistente y a los menús agrupados de Gestión, Catálogo, Contenido y Configuración.
   - En celulares y tablets, el botón de menú con ícono de tres líneas permite desplegar todas las secciones en un panel vertical accesible sin saturar la pantalla.
2. **Tablas con desplazamiento lateral seguro:**
   - En pantallas angostas, las listas de citas, pacientes, usuarios, testimonios y reportes conservan todas sus columnas e información legible mediante desplazamiento horizontal, evitando columnas comprimidas o textos cortados.
3. **Formularios confortables y claros:**
   - Los campos de captura cuentan con bordes suaves, enfoque en color rosa de marca y mensajes de ayuda legibles para evitar errores durante el registro de datos clínicos o citas.
4. **Asistente flotante no invasivo:**
   - El widget del asistente virtual se reposiciona dinámicamente para nunca tapar botones de acción, formularios ni tarjetas de selección.

