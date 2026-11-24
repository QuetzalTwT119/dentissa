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
Escribe el correo asociado a tu cuenta y revisa también la carpeta de correo no
deseado. La confirmación es genérica por seguridad; si el mensaje no llega,
comprueba que sea el correo registrado y solicita un nuevo enlace después de
unos minutos.

El enlace se envía a la dirección asociada a esa cuenta de Dentissa. En el
prototipo, la clínica puede usar Gmail API como canal emisor; eso no cambia el
destinatario ni requiere que cada usuario conecte su propio Google.

La navegación interna muestra la marca (el logotipo con la firma **Dra. Melissa
López**, igual que en la portada), un acceso a **Inicio**, otro a
la **Agenda** y otro al **Asistente**. En administración, **Gestión** agrupa
Pacientes, Usuarios, Reportes, Mensajes y Control de actividades; los
menús **Catálogo**, **Contenido** y **Configuración** reúnen el resto de las
secciones. El menú completo se ve en computadora, laptop, TV y proyector (desde
1024 px de ancho; en las más chicas solo se ve el círculo del logotipo y tu foto). En celular y tableta, usa el botón de menú (☰) para encontrar las
mismas opciones y la campana para abrir una vista rápida de notificaciones.
En el teléfono, los listados anchos conservan sus
columnas dentro de un área que puedes desplazar lateralmente; los filtros y
controles de carga se acomodan en varias filas para facilitar el toque.
**Roles del personal** (desde el 22/11/2026):
- **Doctora titular** (la dueña): entra a todo.
- **Doctor** (doctores asociados): por ahora, lo mismo que la doctora titular,
  salvo que no puede desactivar, eliminar ni crear una cuenta de Doctora titular.
- **Asistente**: por ahora **solo el panel de inicio**; no ve el menú de secciones,
  la campana ni Mi perfil (sí puede cerrar sesión). Para cambiar su contraseña,
  usa "¿La olvidaste?" en la pantalla de acceso.

El menú solo muestra lo que cada rol puede abrir.

Los listados de registros muestran **20 elementos por página de forma predeterminada**. En el pie del listado puedes elegir **20** o **50** registros por página. Cuando
hay más resultados, al final aparecen los botones **Anterior**, los números de
página y **Siguiente**; los filtros actuales se conservan al cambiar de página.

Desde el menú de usuario abre **Perfil** para actualizar nombre, apellido,
teléfono y correo, o para cambiar la contraseña confirmando la actual. La
desactivación de cuentas se administra desde **Gestión → Usuarios**; el perfil
no ofrece eliminar ni desactivar la propia cuenta, para proteger la continuidad
de la clínica y sus registros.

La **foto de perfil** está dentro de **Información personal**, a la derecha (arriba en
celular). Pulsa **Elegir foto** o **Cambiar foto** y escoge una imagen JPG, PNG o WebP de
hasta 5 MB: verás una **vista previa** grande y otra pequeña de "así se verá en el menú"
**antes de guardar**. La foto se guarda con el mismo botón **Guardar** que tus datos.
**Quitar foto** muestra tus iniciales en la vista previa y se aplica también al pulsar
**Guardar** (**No quitarla** lo deshace). Solo la cuenta que inició sesión puede ver su
imagen. El rol aparece con su nombre: Doctora titular, Doctor o Asistente.

El inicio del administrador reúne accesos rápidos para agendar citas,
consultar pacientes, administrar servicios y
contenido, y gestionar usuarios. Las cuentas de asistente ven un inicio más
sencillo con accesos a sus citas y al asistente de agenda. El sitio también
puede instalarse como aplicación compatible; si se pierde la conexión, muestra
una pantalla para reintentar al volver a estar en línea.

Para regresar: en los formularios usa **Cancelar** (vuelve a la pantalla anterior sin
guardar). Las pantallas que no tienen Cancelar, como el expediente del paciente,
muestran arriba del título el enlace **← Volver a …**.

### Demostración escolar en Railway

Para la presentación, el administrador y su ayudante usarán cuentas ficticias
separadas que prepara el equipo desde las variables privadas de Railway. Las
credenciales no se escriben en este manual ni se reutilizan las cuentas locales
de Docker. El administrador gestiona usuarios, pacientes, citas, historial,
catálogo, contenido y seguimiento; el ayudante opera los flujos permitidos por
su rol. Para el recorrido público se puede agendar una cita con datos
completamente ficticios. El paciente precargado es «Paciente Demostración» y no
tiene historial clínico.

El asistente de Telegram solo responde mientras el proceso del bot esté activo
en el entorno. Si no responde, utiliza el portal web y no compartas ni solicites
el token del bot; es una credencial privada de configuración.

Los nombres, teléfonos y correos del paciente demo son de prueba. El catálogo
demo usa los tratamientos y duraciones reales de la clínica (Valoración 30 min,
Limpieza Dental, Retiro de Caries y Extracción Dental 1 hora, y Blanqueamiento
Dental 1 hora y media) con **precios de demostración** (Valoración $300,
Limpieza $600, Retiro de Caries $700, Extracción $900 y Blanqueamiento $3,500):
un tratamiento sin precio no se publica. La clínica debe confirmarlos o
corregirlos en Catálogo → Tratamientos antes de recibir pacientes reales. Al terminar la exposición, el equipo
debe apagar o eliminar el servicio de Railway desde el panel.



La página principal presenta una portada cálida para Dentissa con fotografía,
servicios, galería y un acceso directo a la agenda.

Lo primero que se ve es la bienvenida: la fotografía del consultorio a la
derecha (arriba en el celular), el mensaje "Una consulta sin prisas, explicada
paso a paso" y dos formas de contacto:

- **Agendar mi valoración** abre la agenda en línea.
- **¿Prefieres llamar?** muestra el teléfono del consultorio; en el celular,
  al tocarlo se marca directamente. Es el mismo número del pie de página
  (lo cambia la doctora en **Configuración → Datos del consultorio**).
- Debajo aparece si el consultorio está abierto en este momento, por ejemplo
  "Abierto hoy hasta las 18:00" o "Cerrado ahora · abre el lunes a las 8:00".
  Se calcula con la hora de la clínica a partir de **Horarios**: si el
  administrador cambia el horario, la portada lo refleja sin más pasos.
  El **pie de página** muestra también el horario de la semana (por ejemplo
  "Lunes a viernes de 8:00 a 18:00 hrs"); los días cerrados no aparecen.

Después de los tratamientos, **Tu primera visita** explica el proceso en 3 pasos:
elegir horario, recibir la confirmación y el recordatorio por WhatsApp, y la
primera consulta con la historia clínica. Antes del pie, **Ubicación** muestra la
dirección con un **mapa de Google**; **Cómo llegar** abre la ruta en Google Maps y
**Escribir por WhatsApp** abre el chat. La dirección se cambia en
**Configuración → Datos del consultorio**; si se deja vacía, el mapa no aparece.

La portada también muestra:

- **Misión y Visión** en "La clínica". Se cambian en **Configuración → Datos del
  consultorio**; si se borran, vuelve el texto de fábrica.
- **Casos antes y después**, con lo capturado en **Contenido → Casos**: la primera foto
  que subas es "Antes" y la segunda "Después". Solo salen los casos activos con foto.
- En cada oferta, **Pedir info** abre WhatsApp con el nombre de la oferta ya escrito.
- Un **botón verde de WhatsApp** fijo abajo a la derecha, encima del asistente.
  En **Configuración → Horarios de atención**, **Pacientes a la vez** (un solo campo para todos los días, al final del bloque) es cuántos pacientes se pueden
  atender a la misma hora (1 si hay un solo sillón o dentista; el consultorio usa 1). Los campos de número solo se escriben, sin flechas. Los horarios se ofrecen cada
  30 minutos y la última cita del día es la que **termina** a la hora de cierre: con cierre a
  las 18:00, una valoración de 30 min se puede agendar hasta las 17:30 y una limpieza de
  60 min hasta las 17:00. Los 15 minutos de limpieza de los tratamientos largos o
  quirúrgicos solo se reservan entre una cita y la siguiente.

Más abajo, **Tratamientos para tu salud y armonía** muestra las especialidades
activas en una fila de tarjetas con foto:

- Cada tarjeta dice **Agenda en línea** (tiene tratamientos activos; al tocarla
  se abre la agenda con esa especialidad ya elegida) o **Agenda por teléfono**
  (al tocarla se marca el número del consultorio).
- En computadora se ven 3 tarjetas y se avanza con las flechas **‹ ›** a los
  lados, una tarjeta por clic; al llegar al final regresa a la primera. En el
  celular se desliza con el dedo. Los puntos de abajo indican en qué parte vas.
- La foto y la descripción salen de **Catálogo › Servicios**: si se sube una
  imagen o se escribe una descripción ahí, la portada las usa; si no, usa las
  aprobadas por defecto. Para que una especialidad se agende en línea basta con
  darle al menos un tratamiento activo.

Los botones **Agendar mi valoración** y **Elegir fecha y horario** (al final
de la portada) abren el flujo público validado; ahí se seleccionan tratamiento,
fecha y hora antes de solicitar la cita. La portada no crea citas por sí misma ni usa
teléfonos, correos, horarios, calificaciones o testimonios como si ya fueran
datos oficiales. La clínica debe aprobar ese contenido antes de publicarlo.

La navegación **Inicio**, **Tratamientos**, **La Clínica**, **Instalaciones**,
**Testimonios** y **Contacto** se desplaza suavemente a su sección sin recargar
la página; el enlace de la sección actual se resalta automáticamente en rosa,
tanto en escritorio como en el menú móvil.

## 2. Agendamiento público

El proceso tiene 4 pasos. Arriba siempre se ve "Paso X de 4".

1. Abre `/agendar-cita`.
2. **Selección clínica:** elige la especialidad. La lista cambia por los
   tratamientos de esa especialidad, cada uno con su duración aproximada y su
   precio (el precio también aparece en el resumen de la cita). **El pago se
   realiza en el consultorio el día de la cita**; así lo dicen la agenda, la
   confirmación y los correos. Si
   cambias de opinión, pulsa el recuadro de la especialidad para volver a la
   lista. Solo aparecen las especialidades que tienen tratamientos activos.
3. **Fecha y hora:** se muestra el calendario del mes.
   - Los días con lugar tienen un **punto verde**; con **punto durazno**
     quedan pocos lugares (3 o menos). Los días tachados ya no tienen
     horarios; los días grises son pasados o el consultorio no abre.
   - Cambia de mes con las flechas **‹ ›** (hasta 12 meses adelante).
   - Los pasos son: tipo de atención → día → hora → datos. Las horas libres
     aparecen en cuadrícula; tocar otra vez el día o la hora elegidos los quita. El correo es opcional (sin correo se avisa por WhatsApp); el
     celular de 10 dígitos es obligatorio.
   - Al pulsar un día aparecen sus horarios, cada uno con su hora de término
     (por ejemplo, "09:30 a 10:15"). En el celular aparecen debajo del
     calendario.
   - Elige un horario y pulsa **Revisar resumen**. Debajo aparece el horario
     regular, tomado de la configuración de **Horarios** del consultorio.
4. **Confirmación:** revisa el tratamiento, la fecha y la hora. Pulsa
   **Continuar a mis datos**.
5. **Datos de contacto:** arriba aparece un recordatorio de la cita
   (tratamiento, fecha y hora); **Cambiar** regresa a elegir fecha y hora.
   Escribe nombre, apellido, teléfono y correo, y acepta el aviso de
   privacidad. El motivo es opcional, de hasta 1000 caracteres.
   - El teléfono es el de WhatsApp, a 10 dígitos; se acomoda solo como
     "55 1234 5678" mientras lo escribes.
   - Cada dato se revisa al pasar al siguiente campo: si algo falta, aparece
     el mensaje debajo (por ejemplo "Faltan 6 dígitos…"); cuando ya está
     bien, aparece una palomita verde.
6. Pulsa **Agendar cita**. El botón cambia a "Agendando…" y se desactiva
   mientras se guarda, para no crear la cita dos veces.
7. Aparece la pantalla **"¡Listo! Tu cita quedó confirmada"** con el resumen.

En cualquier paso, **Atrás** regresa al anterior sin perder lo elegido. Los
botones para avanzar se activan cuando el paso está completo. En el celular,
**Atrás** y el botón para avanzar quedan fijos en la parte de abajo de la
pantalla.

Si falta un dato o tiene un error, al pulsar **Agendar cita** no se envía nada:
arriba del formulario aparece un aviso como "Revisa los 2 datos marcados para
poder agendar tu cita", el cursor salta al primer dato con error y cada campo
marcado en rojo muestra su mensaje en español. Si el error lo detecta el
consultorio al guardar, el formulario vuelve con los mismos avisos y conserva
lo que ya estaba bien.

El resumen muestra la fecha completa (por ejemplo, "miércoles 18 de noviembre
de 2026") y el calendario solo permite elegir desde el día de hoy según la hora
de la clínica, aunque se agende tarde por la noche.

Para evitar citas falsas, el formulario acepta hasta 5 envíos por minuto y 20
por hora desde la misma conexión. Si se supera, aparece el aviso **"Recibimos
varios intentos seguidos. Espera un minuto e inténtalo de nuevo."** y los datos
escritos se conservan; basta esperar y volver a pulsar **Agendar cita**.

En pantallas amplias, junto al formulario aparece un indicador compacto del paso
actual y un resumen que se actualiza con el servicio, tratamiento, fecha y hora
seleccionados. En móvil, ese panel se acomoda debajo del formulario para mantener
los controles cómodos. Al elegir un servicio, su nombre permanece visible sobre
los tratamientos para conservar el contexto; el mismo recuadro permite volver
a elegir otro servicio.

### Aviso de privacidad

Antes de **Agendar cita**, el paciente debe marcar la casilla **He leído y
acepto el aviso de privacidad**. Sin esa casilla la cita no se guarda y aparece
el mensaje "Para agendar necesitamos que aceptes el aviso de privacidad". El
enlace abre el aviso en otra pestaña, así que lo capturado no se pierde. En
Telegram, el resumen antes de confirmar enlaza el aviso, y tocar **Confirmar
cita** equivale a aceptarlo. El sistema guarda cuándo aceptó cada paciente y
qué versión del aviso aceptó.

El aviso completo está en **/aviso-de-privacidad** y se enlaza desde el pie de
la portada y de los correos. **Antes de usar el sistema con pacientes
reales:** la clínica debe llenar sus datos (responsable, domicilio y correo para
solicitudes), y un abogado o la clínica deben revisar el texto. Mientras eso no
ocurra, la página muestra "Borrador pendiente de revisión legal" y resalta los
datos que faltan.

El sistema crea o reutiliza el paciente por correo, bloquea el horario y crea
la cita en estado **Confirmada**. La pantalla muestra la confirmación con el
tratamiento, la fecha y la hora, para que el paciente pueda guardarlos; no hace
falta que el personal pulse una segunda vez **Confirmar**.

### Recordatorio un día antes

El **día hábil anterior** a la cita, **una hora después de abrir** el consultorio, el
paciente recibe un recordatorio por correo y, cuando WhatsApp está configurado, también por
WhatsApp (desde el 24/11/2026; antes salía 24 horas antes). "Día hábil" es un día abierto en
**Horarios de atención** que no esté bloqueado completo. Ejemplo con horario de lunes a
viernes de 9:00 a 18:00: una cita del martes a las 19:00 se recuerda el **lunes a las
10:00**; una del lunes, el **viernes a las 10:00**. Así el mensaje nunca llega de noche ni en
día cerrado, y si el paciente cancela o reagenda, el personal lo ve en horario de trabajo. El
correo trae tres botones:

| Botón | Qué pasa |
|---|---|
| **Sí, asistiré** | La cita queda confirmada y en la agenda aparece **✓ Paciente confirmó asistencia**. |
| **Quiero reagendar** | Se libera el horario y el paciente recibe un enlace que abre la agenda en línea **directo en el calendario**, con su mismo tratamiento: **solo elige el nuevo día y la hora** y pulsa **Confirmar cambio de cita** (no vuelve a escribir sus datos ni puede cambiar el tratamiento). Recibe "Tu cita se movió al…", la doctora ve en la campana **Nuevo horario del paciente** y en la Agenda la cita vieja dice **↻ Reagendada por el paciente → nueva fecha**. El enlace es personal, dura 7 días y sirve una sola vez. |
| **No podré asistir, cancelar cita** | La cita se cancela y el horario queda libre para otro paciente. |

Abrir un botón no cambia nada por sí solo: se abre una página que muestra la
cita y pide confirmar la respuesta con otro botón. Desde esa página el paciente
puede elegir otra respuesta. Si la cita ya pasó o ya se canceló, la página
indica que ya no admite cambios y muestra el teléfono del consultorio.

Las citas que se agendan **después** del momento del recordatorio (por ejemplo, el
viernes por la tarde para el lunes) no reciben recordatorio, porque el paciente acaba de
recibir su confirmación. Si el
personal reagenda una cita, el paciente recibe un recordatorio nuevo para la
nueva fecha.

El mensaje de WhatsApp trae los mismos tres botones. Al pulsar uno, la cita
cambia igual que con el correo y el paciente recibe una respuesta en el mismo
chat: la confirmación o, si pidió reagendar o canceló, el enlace para elegir
otro horario. Para que funcione, el sistema debe estar en línea y Meta debe
haber aprobado la plantilla del recordatorio. Si el botón ya no se puede aplicar (por
ejemplo, la cita se borró), el paciente recibe un mensaje que le pide comunicarse con el
consultorio. Los botones solo funcionan con citas del sistema en línea (Railway): un
recordatorio de prueba enviado desde una computadora local no cambia la cita local.

El número de WhatsApp de los recordatorios no lo atiende nadie. Si un paciente
le escribe un mensaje (texto, audio o foto), recibe una respuesta automática
con el teléfono del consultorio y el enlace para agendar. La respuesta llega
una sola vez cada 12 horas por número, y el sistema no guarda lo que escribió.

## 3. Gestión interna de citas

### Vista de lista o de semana

A la derecha del título de `/citas` hay un selector **Lista | Semana**:

- **Lista** (predeterminada): las citas de **un día a la vez**, en orden de
  hora. Se abre en el día de hoy.
  - Arriba hay una pestaña por día de la semana con cuántas citas tiene y
    cuántas faltan **por confirmar** (en ámbar). Toca una pestaña para ver ese
    día; las flechas **‹ ›** (o "Semana anterior / siguiente" en el celular)
    cambian de semana e **Ir a hoy** regresa al día actual.
  - El conteo dice cuántas citas siguen **activas** y, aparte, cuántas se
    cancelaron ("0 citas activas · 4 canceladas"); un día con solo canceladas
    lo indica en su pestaña en lugar de "0 citas".
  - Al confirmar, iniciar, completar, cancelar o marcar "no asistió" te quedas
    en el **mismo día** (y vista) donde estabas; al crear o reagendar una cita
    la agenda se abre en el día de esa cita.
  - Mientras queden citas por atender, las ya completadas, canceladas o sin
    asistencia se agrupan en un renglón ("4 ya atendidas o canceladas");
    pulsa **Mostrar** para verlas.
  - La cita en curso aparece resaltada con una franja rosa.
  - **Buscar en todas las fechas** encuentra a un paciente (nombre, teléfono,
    correo), servicio o tratamiento en cualquier fecha, agrupado por día.
    **Todas las fechas** muestra el historial completo, 20 o 50 por página.
  - **Estado** filtra las citas del día elegido (o de la búsqueda).
  - En el celular, cada cita es una tarjeta: hora y paciente arriba,
    tratamiento en medio y los botones abajo, sin desplazarse de lado.
- **Semana**: un mapa de lunes a sábado que muestra qué tan llena está cada
  parte de la semana. Cada día se divide en bloques de 2 horas:
  - **Blanco:** libre. **Rosa claro:** tiene citas pero aún hay lugar.
    **Rosa:** lleno. Los bloques que ya pasaron se ven más tenues.
  - Al tocar un bloque aparece su detalle (a la derecha en computadora, abajo
    en el celular): el horario del día, **Dónde cabe una cita** con las horas
    libres y la lista de citas de ese bloque. Al tocar una hora libre se abre
    **Agendar cita** con esa fecha ya elegida; al tocar una cita se abre su
    edición, donde están las acciones.
  - Al entrar se abre el bloque actual o, si hoy ya terminó, el siguiente.
  - Un día cerrado que tenga citas muestra **Cerrado · N citas**; al tocarlo
    aparecen esas citas.
  - Usa **←**, **Hoy** y **→** para cambiar de semana. El enlace de la página
    conserva la semana, así que se puede recargar o compartir.
  - Las horas libres son una guía: al agendar, el sistema vuelve a revisar el
    horario con la duración del tratamiento elegido.

Desde `/citas`, el personal puede:

- crear una cita seleccionando paciente, servicio y tratamiento, y después el
  día y la hora en el mismo calendario de la agenda pública;
- las citas nuevas creadas aquí quedan confirmadas en cuanto el horario se
  reserva; las citas históricas que sigan agendadas conservan la opción de
  confirmación manual;
- reagendar una cita a otro horario disponible con ese calendario (el horario
  actual de la cita cuenta como libre mientras se reagenda);
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
La inasistencia no se puede registrar antes de la fecha y hora programadas; por
eso **Marcar inasistencia** solo aparece en **Más acciones** de una cita
confirmada cuando ya pasó su hora.
Antes de marcarla, el sistema pide confirmación porque el cambio no se puede
deshacer; cancelar también solicita un motivo opcional y confirmación.
Después de una acción válida, la agenda muestra el estado actualizado y un
aviso de confirmación; si la operación no procede, muestra el motivo del error.
En pantallas angostas, la tabla conserva las acciones en una sola línea y permite
desplazamiento horizontal para consultar sus columnas sin comprimir los controles.
Las listas administrativas siguen una regla según su tamaño:

- **Tablas cortas** (Pacientes, Usuarios, Servicios, Testimonios, Preguntas
  frecuentes, Casos y Horarios): nunca se desplazan de lado. En celular y
  tableta cada registro es una tarjeta: el nombre arriba, cada dato con el
  título de su columna y los botones abajo. En computadora son tablas normales.
- **Tablas largas** (Control de actividades, Mensajes de contacto, Ofertas y Tratamientos):
  conservan sus columnas y se desplazan de lado solo cuando no caben. La
  primera columna se queda fija y arriba aparece "Desliza para ver más
  columnas".

Los formularios con campos relacionados se apilan en móvil y se vuelven a
distribuir en columnas al tener más espacio.

Al agendar o reagendar, el sistema vuelve a comprobar que el servicio y el
tratamiento estén activos, que el tratamiento pertenezca al servicio y que la
fecha/hora sea futura. Si el horario ya fue ocupado, se debe elegir otro.

## 3.1 Catálogo: servicios y tratamientos

- **Servicios** (especialidades del carrusel): en **Editar servicio** se cambia
  el nombre, la descripción, la foto (con vista previa de la tarjeta) y **qué
  tratamientos se pueden agendar**. **Nuevo servicio** es la misma pantalla, así que al
  crearlo ya se eligen su foto y sus tratamientos. Un servicio nuevo queda **Disponible**: se
  muestra en la portada y se puede agendar en línea, en el panel, en el asistente y por Telegram.
  Para ocultarlo (o volver a mostrarlo) usa el interruptor **Disponible** de la lista; es el
  único lugar donde se cambia. Las citas ya agendadas no cambian. Si editas uno oculto, la
  pantalla te lo recuerda. El orden en que salen en la portada se acomoda con
  las flechas ↑ ↓ de la columna **Orden en la portada**. La lista se puede buscar por nombre y filtrar por
  activos o inactivos; mientras haya un filtro, el número sigue siendo su lugar
  real en el carrusel y, para cambiar el orden, hay que quitar el filtro.
- **Tratamientos**: el formulario pide **Nombre**, **Duración** (cuánto ocupa en
  la agenda) y **Precio** (el paciente lo ve al agendar). Se puede guardar sin
  precio mientras la clínica lo confirma, pero **no se publica** hasta tenerlo. A la
  derecha, **Así lo verá el paciente** muestra la opción tal como aparece en la
  agenda en línea y en qué especialidad está (eso se cambia en el servicio).
- **Activar o desactivar un tratamiento**: con el interruptor de la columna
  **Estado** en la lista. Activar y desactivar piden confirmación; desactivar
  avisa si una especialidad se quedaría sin tratamientos (pasaría a "Agenda por
  teléfono"). **Sin precio no se puede activar**: la fila dice "Falta precio".
- **El mismo interruptor en todas las listas** (Servicios, Tratamientos, Casos,
  Galería, Preguntas frecuentes y Usuarios): la columna **Estado** muestra si
  está encendido y se cambia ahí mismo, sin entrar a Editar. Siempre pide
  confirmación y dice qué pasará (por ejemplo, "Dejará de mostrarse en la
  portada"). Tu propia cuenta sale atenuada con ⓘ: no puedes desactivarte.
- **Íconos de las acciones** (columna Acciones, a la derecha): ✎ editar, 🗑 eliminar,
  ojo = publicar, ojo tachado = ocultar, pulgar arriba = aprobar, pulgar abajo = rechazar,
  sobre abierto = marcar leído, sobre cerrado = marcar sin leer, 📋 = historial clínico del
  paciente (también se abre tocando su número de consultas). Al pasar el mouse dicen qué
  hacen y siempre piden confirmación. Si un ícono sale apagado con ⚠, esa acción no se
  puede hacer; toca el ⚠ para ver por qué. El ⓘ gris solo explica.
- Las confirmaciones (eliminar, cancelar una cita, desactivar…) usan la ventana
  de Dentissa; se cancelan con **Esc**, con **Cancelar** o tocando fuera.
- En cada lista, arriba se ve cuántos registros hay y cuántos mostrar por página
  (izquierda) y el botón **Nuevo …** (derecha). Las **páginas** están abajo, al
  centro ("Página 1 de 1" si solo hay una).
- Después de crear, guardar o eliminar aparece un **aviso abajo a la derecha**
  que dice qué pasó (verde) o qué falta (rojo). El verde se va solo; el rojo se
  cierra con la ×.
- Si falta un dato obligatorio (marcado con \*) o tiene un formato incorrecto,
  el mensaje aparece en rojo **debajo del campo** y el cursor salta al primero.

## 3.2 Promociones y testimonios en la portada

- En la portada, sección **Ofertas** (un carrusel que se desliza, hasta 24,
  primero las que vencen antes), solo aparecen las ofertas **Publicadas**
  dentro de sus fechas (beneficio, tratamiento, imagen si tiene, vigencia y
  lugares que quedan). Las que están **En revisión** u **Ocultas** solo se ven
  en el panel.
- **Crear una oferta** (Contenido → Ofertas → Nueva oferta): se llenan
  **título** (5 a 120 letras), **Beneficio para el paciente**, la **cantidad**,
  **descripción** (20 a 300 letras) y **Válida hasta** (hoy o después). La
  oferta cuenta desde el día en que se crea. **Tratamiento al que aplica** está
  a la vista (opcional): sin tratamiento, la oferta solo se anuncia y no se
  aplica al agendar. Lugares e imagen están en **Más detalles (opcional)**.
  Si guardas sin llenar algo obligatorio, cada campo dice qué falta (en
  Beneficio: "Elige una opción") y el cursor va al primero.
  - **Beneficio para el paciente** tiene 3 opciones: **Descuento**, **Precio
    especial** o **Gratis**. La elegida se ve con fondo rosa claro y borde rosa.
    Al tocar otra, la anterior se desmarca; al tocar la elegida, se quita (al
    guardar pedirá "Elige el beneficio para el paciente").
  - **Descuento** es siempre en **%** (el símbolo aparece dentro del campo).
    Para un monto en pesos usa **Precio especial**.
  - **Tratamiento al que aplica:** solo aparecen los que se agendan en línea
    (activos y dentro de una especialidad activa). Si después se desactiva, la
    oferta deja de verse en la portada, la lista muestra un símbolo ⚠ junto al
    tratamiento (al pasar el mouse o tocarlo explica qué pasa y qué hacer) y no
    se puede publicar hasta activarlo o cambiarlo.
  - Título y descripción **no aceptan enlaces, correos, código ni letras
    repetidas** (por ejemplo "!!!!!!").
  - Cómo se ve cada opción en la portada: **Descuento** "20" → **−20%**;
    **Precio especial** "2800" →
    **Precio especial $2,800**; **Gratis** → sin cantidad, **Gratis**.
- **Publicar:** al guardar, la oferta queda **En revisión** (aviso amarillo en
  la lista) y no se ve en la portada. Revísala y toca **✓ Publicar** en su
  fila; el sistema pide confirmar. Una vencida no se puede publicar hasta
  cambiar "Válida hasta".
- **Configuración** (menú del panel, solo administradores) es una sola página
  con dos bloques, cada uno con su botón de guardar:
  - **Datos del consultorio:** teléfono, WhatsApp de contacto ("Es el mismo
    número que el teléfono" o uno distinto), correo y dirección. Lo que se
    guarde se ve de inmediato en la portada, el pie, "¿Prefieres llamar?", la
    página del recordatorio y la respuesta automática del bot. Úsalo si se
    pierde o cambia el celular. Pide confirmación y queda en Control de actividades. El
    número del bot de WhatsApp no se cambia aquí (se configura en Meta).
  - **Horarios de atención:** los días, las horas y cuántos pacientes se atienden a la vez (un solo valor).
  - **Días u horas bloqueados** (desde el 24/11/2026): para vacaciones, un curso, un
    imprevisto o mantenimiento. Elige **Desde**, opcionalmente **Hasta**, deja **Todo el día**
    o quítalo para bloquear solo unas horas (**De** / **A**, en un solo día), escribe el
    **motivo** (solo lo ve el personal) y pulsa **Bloquear**. Esos horarios dejan de
    ofrecerse en la agenda en línea y en Nueva cita; los días completos se ven cerrados en el
    calendario. Si en ese tiempo ya hay citas activas, el sistema avisa cuáles: primero
    reagéndalas o cancélalas. En **Próximos bloqueos** se ve la lista y la 🗑 los quita
    (vuelven a ofrecerse). Todo queda en Control de actividades.
- **Guardar cambios** (en cualquier pantalla de editar) aparece apagado hasta
  que cambias algo; si regresas todo como estaba, se vuelve a apagar. Al
  guardar, el sistema sigue pidiendo confirmar. Para salir sin guardar usa
  **Cancelar**, junto a "Guardar".
- **Ofertas al agendar (desde el 23/11/2026):** la oferta **solo se aplica si
  el paciente entra desde su tarjeta**, pulsando "Agendar" en la portada: la
  agenda abre con ese tratamiento, la etiqueta (por ejemplo "Oferta −20%"), un
  aviso con la vigencia y la oferta en el resumen. Si agenda el mismo tratamiento
  por el camino normal, **no** lleva oferta ni gasta un lugar. Si la fecha de su
  cita es posterior a "Válida hasta", el resumen avisa que ya no aplica. Al
  **reagendar** desde el recordatorio se conserva la oferta que ya tenía (si
  sigue vigente), pero no se gana una nueva.
- **La doctora aplica el descuento al cobrar:** la cita guarda la oferta. En la
  agenda (lista, semana y detalle) aparece "Oferta −20% · título" y la campana
  dice "Con oferta «…»". El sistema no cobra ni calcula el total.
- **Lugares:** cada cita agendada con la oferta ocupa un lugar; cancelar o
  marcar "No asistió" lo libera. Sin lugares, la oferta deja de anunciarse en
  la portada y las citas nuevas ya no la llevan.
- **Publicada, no se ve** (rosa, con ⚠): está publicada pero no aparece en la
  portada ni al agendar. El ⚠ dice por qué: venció, su tratamiento está
  inactivo, ya no tiene lugares o no cabe entre las 24 del carrusel.
- **Buscar y filtrar:** Ofertas, Tratamientos, Preguntas frecuentes, Casos y
  Galería tienen arriba el buscador y los filtros de estado (los mismos que
  Pacientes o Usuarios); se aplican solos y "Limpiar filtros" los quita.
- **Ocultar:** en una oferta **Publicada**, el botón de encendido la oculta de
  la portada; sigue en la lista como **Oculta** para editarla o publicarla otra
  vez con ✓.
- **Editar una publicada:** si cambias lo que ve el paciente, vuelve a
  **En revisión** hasta que la publiques de nuevo.
- **Testimonios:** el paciente escribe el suyo en la portada (**Comparte tu
  experiencia**) con el teléfono o correo con el que agendó. Llega como
  **pendiente**, la campana avisa al personal y, al **aprobarlo** en Contenido →
  Testimonios, se publica con su nombre y la inicial de su apellido.
- La clínica **no crea ni edita** testimonios: solo los **aprueba**, **rechaza**
  (se quedan en la lista como "Rechazado", sin publicarse, y se pueden aprobar
  después) o **elimina** (se borran para siempre, por ejemplo si el paciente lo pide).
- Los testimonios largos muestran unas líneas y el botón **Leer completo**, en la
  portada y en el panel, para leerlos enteros antes de aprobarlos. El buscador de
  la lista encuentra por nombre del paciente o por lo que escribió, y se combina
  con el filtro de estado.

## 4. Asistente de agenda

En `/asistente-agenda`, el personal selecciona los datos de la cita. El
asistente consulta disponibilidad y propone horarios. No guarda nada hasta
pulsar **Confirmar y agendar**. Se pueden buscar horarios para hoy o fechas
futuras; las fechas pasadas se rechazan también en el servidor.

## 5. Asistente para pacientes y preguntas frecuentes

El asistente flotante responde con preguntas frecuentes activas y sus respuestas
aprobadas. En desarrollo o en la prueba de prototipo, se puede habilitar un
modelo de IA privado para reconocer otras formas de preguntar y elegir una FAQ
o un servicio del catálogo. La aplicación muestra el texto aprobado original;
el modelo no redacta la respuesta. El bot de Telegram es independiente del
asistente web.
Para precios sin una FAQ que hable de ellos, síntomas, medicamentos y
diagnósticos, el chat pide reformular la duda o contactar al consultorio.
En la portada, al agendar una cita y en las páginas del portal, el acceso queda
fijo en la esquina inferior derecha. Plegado muestra el diente; al pasar el
cursor o enfocarlo con teclado aparece el nombre **Dentissa Asistente**. En
pantallas táctiles, tócalo para abrir el chat. El panel se despliega encima del
acceso. En el celular, el botón se esconde mientras bajas por la página, para
no tapar botones como **Continuar** o **Agendar cita**, y vuelve a aparecer en
cuanto subes un poco. En la agenda pública, el flujo principal se presenta primero.

El chat también puede consultar horarios: escribe que buscas una cita, elige el
servicio y tratamiento tal como aparecen en las listas y proporciona una fecha.
Acepta `hoy`, `mañana`, `DD/MM/AAAA`, `DD-MM-AAAA` o `AAAA-MM-DD`. Muestra los
horarios disponibles, pero la reserva se confirma en **Agendar cita**.
Si solo preguntas por el horario de atención (por ejemplo, "¿a qué hora
abren?"), el chat responde con el horario configurado sin iniciar una reserva;
una pregunta frecuente aprobada sobre el horario tiene prioridad. Durante la
búsqueda de horarios, cada paso indica que se puede escribir **cancelar** (o
**salir**) para terminarla; si en medio de la búsqueda se pregunta por el
horario de atención, el chat lo responde y conserva el paso actual. La búsqueda
vence sola a los 15 minutos sin actividad.

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
temas sin información disponible y consultas clínicas. Para el prototipo se
puede conectar el chat web con Ollama mediante la red privada de Railway; esto
no conecta ni modifica el bot de Telegram. Si Railway duerme Ollama por
inactividad, la primera consulta puede tardar o mostrar una respuesta de
respaldo mientras el servicio despierta. Si el modelo no responde o no puede
apoyarse en una respuesta activa, el chat conserva su mensaje de respaldo.

## 6. Pacientes e historial clínico

**Historia clínica (cuestionario).** Cada paciente tiene una, en su expediente (📋): es la
primera sección, arriba de las consultas. La primera vez aparece **Llenar cuestionario**;
después, **Ver o editar**. Incluye datos generales, antecedentes familiares y personales,
alergias, medicamentos, embarazo y lactancia (solo si el sexo es femenino), antecedentes
dentales, hábitos y observaciones. **Nada es obligatorio**: se llena lo que se sepa y se
completa en otra visita. Las alergias y condiciones de riesgo (coagulación, corazón,
presión alta, diabetes, embarazo…) salen en un **aviso rojo** arriba del expediente y al
registrar cada consulta, para verlas antes de atender. La historia también sale en el PDF.

El cuestionario también pide **estado civil**, **contacto de emergencia** (nombre, parentesco
y teléfono), **médico de cabecera**, si está **en tratamiento médico** y, entre los
antecedentes, gastritis, otras enfermedades respiratorias y cirugías previas.

**Exploración clínica y odontograma (solo doctora y doctores).** En el expediente, debajo de
la historia clínica, está **Hacer exploración** (la primera) o **Nueva exploración** (los
controles). Incluye:

- **Examen de la boca:** labios, lengua, paladar, encías, ganglios… marca "Normal" o escribe
  el hallazgo.
- **ATM** (mandíbula) y **encías**: escribe en qué dientes hay sangrado, inflamación, sarro…
- **Higiene oral** e **índice de O'Leary**: escribe los dientes presentes y las superficies con
  placa; el porcentaje se calcula solo (verde si es 20 % o menos).
- **Problemas de la pulpa** e **interpretación radiográfica**, un renglón por diente
  (**+ Agregar diente**).
- **Odontograma:** elige un estado (Cariado, Obturado, Extracción indicada…) y toca los
  dientes; tocar otra vez con el mismo estado lo quita. **Rojo** = por tratar, **azul** = ya
  está o ya se hizo. Los dientes de leche están en su propia sección. Una exploración nueva
  trae el odontograma de la anterior para cambiar solo lo nuevo.
- **Otros hallazgos** y **diagnósticos definitivos.**

El expediente muestra el resumen de la última (higiene, O'Leary y dientes por estado) y la
lista de exploraciones anteriores; la última sale en el PDF. Cada consulta tiene ahora el
campo **Diente(s) tratados**.

**Cita y consulta.** Al completar una cita en la Agenda aparece **Registrar consulta**: abre
la consulta nueva con esa cita y su fecha ya elegidas. La columna de Pacientes se llama
"Consultas en expediente" (las notas médicas registradas, no las citas).

**La asistente** también llena la historia clínica: en su menú aparece **Historias
clínicas**, una lista de pacientes con buscador que dice si cada uno la tiene **Llena** o
**Pendiente**, con el botón **Llenar cuestionario** o **Ver o editar**. Ella no ve las
consultas, las notas privadas, los adjuntos ni el PDF.

**Nueva consulta** va en una sola columna: fecha, tipo y cita arriba, y cada pregunta en su
propio renglón con espacio para escribir.

Desde `/admin/pacientes`:

- **Receta en PDF** (desde el 24/11/2026): en el expediente, cada consulta con **medicamentos recetados** muestra **Receta**. Se abre lista para imprimir, con tu nombre, **cédula profesional**, especialidad, fecha, nombre y edad del paciente, sus **alergias**, el diagnóstico, los medicamentos y la línea de firma; al pie, la dirección y el teléfono del consultorio. La cédula se escribe una sola vez en **Mi perfil** (solo la ve quien atiende consultas); sin ella el sistema avisa en lugar de imprimir. Cada receta impresa queda en Control de actividades.
- **Nuevo paciente** (desde el 24/11/2026): registra a alguien que llegó sin cita, o para llenarle la historia clínica antes. Pide nombre, apellido, celular (obligatorios), correo (opcional) y confirmar que **aceptó el aviso de privacidad**. Si el celular ya está registrado, avisa de quién es y ofrece **Abrir su expediente** en lugar de duplicarlo. Al guardar se abre su expediente. Cuando después agende en línea con ese celular, el sistema lo reconoce.

1. Buscar por una parte del nombre, correo o teléfono, o seleccionar un paciente de la lista.
2. Abrir **Editar** (✎) para corregir su nombre, apellido, teléfono celular y correo. Nombre, apellido y **teléfono celular son obligatorios** (por el teléfono se le avisa por WhatsApp y se le reconoce al agendar); el correo es opcional y debe ser único. El teléfono debe tener diez dígitos, con código `+52` opcional.
3. Abrir el **historial** (📋), o tocar su número de consultas.
4. Pulsar **Nueva consulta**.
5. Completar los campos indicados como **obligatorios**: fecha, tipo y motivo. Diagnóstico, tratamiento, medicamentos, observaciones y notas privadas son opcionales; la cita relacionada también es opcional cuando aparece.
6. Guardar la consulta.
7. Consultar, editar o eliminar las consultas. El PDF se habilita cuando existe al menos una consulta.

**Cita y consulta no son lo mismo:** la cita es la reservación de la Agenda (día, hora y
estado); la consulta es la nota médica del expediente. Completar una cita no crea la
consulta: hay que registrarla en el historial del paciente.

El historial clínico está reservado al administrador. El campo **Notas privadas del doctor**
es visible para el administrador y no se incluye en el PDF descargable.

Durante las pruebas se deben usar datos ficticios. No se deben subir imágenes,
documentos ni datos médicos reales al entorno de desarrollo.

## 6.1 Cuentas del personal

En **Usuarios**, el administrador solo puede hacer tres cosas: **crear** una cuenta
(Nuevo usuario), **activarla o desactivarla** (interruptor de la columna Estado) y
**eliminarla** (papelera). También puede filtrar por rol y estado. Los datos y la
contraseña de cada cuenta los corrige su dueño en **Mi perfil**. Los pacientes se
gestionan desde **Pacientes**, en una sección distinta.

- **Eliminar** pide confirmación y no se puede deshacer. No se puede eliminar la
  cuenta propia, ni una cuenta que registró consultas clínicas: borrarla eliminaría
  esas consultas del historial de los pacientes. En ese caso la papelera sale
  apagada con ⚠ y lo indicado es **desactivarla**.
- **Cambiar el rol de alguien:** si la cuenta no tiene consultas, elimínala y créala
  de nuevo con el rol correcto; si tiene consultas, desactívala y crea una cuenta nueva.

Para cambiar el correo de la cuenta actual, abre **Perfil** desde el menú de
usuario, edita **Correo electrónico** en **Información personal** y guarda.
Para crear otra cuenta administrativa, entra a **Gestión → Usuarios → Nuevo
usuario**, captura los datos, selecciona **Admin**, escribe una contraseña
segura y guarda. El correo debe ser único. Nadie puede cambiar su propio rol ni
eliminar su propia cuenta, así la clínica nunca se queda sin quien administre el
panel. Al escribir una contraseña nueva,
el 👁 muestra lo que escribiste y, debajo de **Confirmar**, el sistema te dice al momento si
las dos coinciden.

### Seguridad de la cuenta

- **Contraseñas:** al crear o cambiar una contraseña, debe tener al menos 10
  caracteres, con mayúsculas, minúsculas y números. Al cambiarla, se cierran
  las sesiones abiertas en otros equipos.
- **Inactividad:** después de 30 minutos sin usar el panel, la sesión se cierra
  sola. Vuelve a iniciar sesión.
- **Cuentas desactivadas:** al desactivar una cuenta en **Usuarios**, esa
  persona sale del sistema en su siguiente clic, aunque tuviera "Recordarme".

### Verificación en dos pasos

Además de la contraseña, al entrar se pide un código de 6 dígitos que genera
una app gratuita en tu celular (Google Authenticator o Microsoft
Authenticator). **Es obligatoria para el administrador**, porque puede ver
historiales clínicos; para la asistente es opcional y recomendada.

**Para activarla:** **Perfil → Verificación en dos pasos**. Escanea el código QR
con la app (o escribe la clave que aparece debajo) y escribe el código de 6
dígitos que muestra la app. Si eres administrador y aún no la activas, el
sistema te lleva a esa pantalla automáticamente.

**Códigos de recuperación:** al activarla verás 8 códigos. **Guárdalos en un
lugar seguro: no se vuelven a mostrar.** Si pierdes el celular, cada uno sirve
una sola vez para entrar. Desde la misma pantalla puedes generar códigos
nuevos con tu contraseña; los anteriores dejan de servir.

**Al iniciar sesión:** después de la contraseña aparece **Un paso más**.
Escribe el código actual de la app o un código de recuperación. Tienes 5
minutos; si se pasa el tiempo, vuelve a iniciar sesión. Si el código no
funciona, revisa que la hora de tu celular esté en automático.

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

## 8. Control de actividades

El administrador puede abrir **Configuración → Control de actividades** para consultar los
cambios realizados por cuentas del administrador o de las asistentes. La vista
permite filtrar por fechas, usuario y acción. **Ver cambios** abre una ventana con
qué se hizo, quién y cuándo, y una tabla con cada dato que cambió, cómo estaba **Antes** y cómo quedó **Después**, en
palabras (por ejemplo, Estado: Confirmada → En curso). Si algo se creó solo se ve cómo
quedó, y si se borró, lo que tenía. La ventana se cierra con **Cerrar**, con Esc o tocando fuera. Es de solo lectura; las asistentes no tienen acceso. Las
solicitudes iniciadas por pacientes desde el portal o Telegram no aparecen en
la bitácora. Los registros anteriores de esos canales se conservan en la base
de datos, pero no se muestran como cambios del personal. Se registran cambios
de citas, pacientes e historial clínico, usuarios, perfil,
catálogo, contenido público, horarios y mensajes de contacto. No se guardan
contraseñas ni notas clínicas; al editar un paciente, la auditoría indica qué
campos cambiaron, pero no copia sus valores de teléfono o correo. Las consultas,
descargas y acciones de pacientes
no se consideran cambios del personal.

Cada cambio aparece **una sola vez**, con la hora de México y el nombre y rol
de quien lo hizo. La dirección de la página es
`/admin/control-de-actividades`; si tienes guardada la anterior (`/admin/auditoria`),
te lleva sola a la nueva.

## 11. Notificaciones internas

Administradores y asistentes pueden abrir **Notificaciones** desde el ícono de
campana. El contador muestra cuántos avisos son **nuevos** desde la última vez
que se abrió la campana; no equivale a los avisos pendientes de atender. La bandeja
resume el total, separa avisos en **Todas**, **Pendientes** y **Leídas**, y
permite cambiar un aviso leído de vuelta a pendiente. También permite marcar
todas las pendientes como leídas. Cada aviso muestra su fecha, estado y acceso
a la cita relacionada. Al pulsar la campana se abre una vista rápida sin salir
de la página actual; **Ver todas las notificaciones** abre la bandeja completa.

La vista rápida tiene dos partes:

- **Por atender**: lo que necesita que alguien haga algo (un paciente canceló o
  pidió reagendar, una inasistencia, un respaldo que falló). Cada aviso trae
  **Ver en la agenda** (abre el día de esa cita), **Llamar** (marca al paciente
  desde el celular) y **Ya lo atendí**, que lo pasa a informativas cuando ya lo
  resolviste. Si no hay nada, verás "No hay nada pendiente".
- **Informativas**: confirmaciones, citas nuevas y cambios. Están plegadas;
  tócalas para abrirlas.

Cada aviso muestra un círculo con las **iniciales del paciente** (el mismo
paciente conserva siempre su color) y una insignia pequeña con lo que pasó:
calendario (cita nueva o cambio), palomita (confirmó o se completó), tache
(canceló o no asistió) o flechas (pidió reagendar). Los avisos del sistema,
como un respaldo, usan un recuadro punteado porque no vienen de un paciente. La bandeja informa cuando se registra, reagenda,
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
Las respuestas de los pacientes al recordatorio también llegan aquí:
**Respuesta del paciente** cuando confirma que asistirá, y **Cancelación del
paciente** u **Horario liberado** (destacados) cuando cancela o pide reagendar.
Solo el administrador recibe **Respaldo fallido** (destacado, también por
correo) si la copia de seguridad nocturna no se completó. Ese aviso no se debe
ignorar: avisa al equipo técnico el mismo día, porque mientras siga fallando no
hay copia reciente de citas ni historiales. La copia se guarda cifrada en la
carpeta **Dentissa respaldos** del Google Drive de la clínica: no se debe
mover, renombrar ni borrar a mano, y abrirla en Drive no muestra nada legible
(es normal). Una vez al mes, el equipo técnico comprueba que la copia más
reciente se puede recuperar sin tocar los datos en uso.
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
2. **Tablas que se adaptan a su tamaño:**
   - Las tablas cortas se convierten en tarjetas en celular y tableta, sin desplazamiento lateral; las largas (por ejemplo Control de actividades) se desplazan de lado lo mínimo, con la primera columna fija para no perder de qué registro se trata.
3. **Formularios confortables y claros:**
   - Los campos de captura cuentan con bordes suaves, enfoque en color rosa de marca y mensajes de ayuda legibles para evitar errores durante el registro de datos clínicos o citas.
4. **Asistente flotante no invasivo:**
   - El widget del asistente virtual se reposiciona dinámicamente para nunca tapar botones de acción, formularios ni tarjetas de selección.
5. **Textos y botones cómodos en el celular:**
   - Ningún texto mide menos de 12 px y los botones y enlaces miden al menos
     44 px de alto, el tamaño recomendado para el dedo.
   - Al escribir en un campo, el celular no hace zoom.
   - En teléfonos con muesca, el contenido no queda debajo de ella.
6. **Avisos y estados claros:**
   - Los avisos de éxito, error, advertencia e información tienen el mismo
     diseño en todo el sistema.
   - Los botones que guardan muestran un texto de espera (por ejemplo
     "Agendando…") y no se pueden pulsar dos veces.
   - Los mensajes de error de los formularios están en español.
7. **Movimiento con propósito:**
   - Solo se anima lo que ayuda a entender qué pasó: el cambio entre pasos al
     agendar, la confirmación de la cita, los avisos emergentes y el menú de
     acciones.
   - Si el celular o la computadora tiene activada la opción "reducir
     movimiento", estas animaciones se suavizan o desaparecen.
8. **Nombre de la doctora:** "Dra. Melissa López" se muestra con la tipografía
   Fraunces itálica en la portada, el encabezado y el acceso.
