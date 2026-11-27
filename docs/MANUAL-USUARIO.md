# Manual de Usuario de Dentissa

## 1. Presentación

### 1.1 Propósito del documento

El presente manual describe la operación del sistema Dentissa, plataforma web para la
gestión integral del consultorio odontológico de la Dra. Melissa López N. Su finalidad es
que el personal de la clínica pueda utilizar cada módulo de forma correcta, segura y
uniforme, y que conozca las reglas de negocio que el sistema aplica de manera automática.

### 1.2 Alcance

El manual abarca:

- el portal público, en el que los pacientes consultan información y agendan citas sin
  necesidad de crear una cuenta;
- el panel interno del personal: agenda, pacientes y expediente clínico, catálogo,
  contenido de la portada, configuración, usuarios, reportes y control de actividades;
- las notificaciones internas y los mensajes que el sistema envía a los pacientes;
- las medidas de seguridad que el personal debe observar.

La instalación, la configuración del servidor y el despliegue se documentan por separado
en el Manual Técnico.

### 1.3 Destinatarios

| Perfil | Uso principal del manual |
|---|---|
| Doctora titular | Operación completa y administración del consultorio. |
| Doctores asociados | Operación clínica: agenda y expediente de los pacientes. |
| Asistente | Recepción: agenda, registro de pacientes y cuestionario de historia clínica. |

No se requieren conocimientos técnicos. Basta con saber utilizar un navegador web.

### 1.4 Convenciones del documento

- Los nombres de botones, menús y campos se escriben en **negritas**, tal como aparecen en
  pantalla (por ejemplo, **Guardar cambios**).
- La ruta de un menú se indica con flechas: **Administración → Usuarios → Nuevo usuario**.
- Las direcciones web del sistema se escriben en formato de código, por ejemplo `/citas`.
- Los recuadros con sangría contienen notas o advertencias que deben leerse antes de
  continuar con el procedimiento.
- Cada procedimiento indica sus pasos en orden y, al final, el **resultado esperado**.

### 1.5 Requisitos de uso

| Requisito | Detalle |
|---|---|
| Dispositivo | Computadora, laptop, tableta, teléfono celular, televisión o proyector. |
| Navegador | Versión actual de Google Chrome, Microsoft Edge, Mozilla Firefox o Safari. |
| Conexión | Acceso a Internet (o a la red local del consultorio en la instalación local). |
| Cuenta | Cuenta de personal creada por la doctora titular. Los pacientes no requieren cuenta. |
| Verificación en dos pasos | Aplicación gratuita Google Authenticator o Microsoft Authenticator, cuando la verificación esté habilitada. |

### 1.6 Direcciones de acceso

| Entorno | Dirección | Observaciones |
|---|---|---|
| Demostración en línea | https://dentissa-production-11db.up.railway.app | Contiene exclusivamente datos ficticios. |
| Instalación local del consultorio | http://localhost:8000 | Disponible en la computadora donde se ejecuta el sistema. |

## 2. Conceptos generales

### 2.1 Perfiles de usuario y permisos

El sistema reconoce tres perfiles de personal. El menú de cada persona muestra únicamente
las secciones que su perfil puede abrir; si se intenta acceder a una dirección no
permitida, el sistema niega el acceso.

| Módulo | Doctora titular | Doctor | Asistente |
|---|---|---|---|
| Inicio, Mi perfil y notificaciones | Sí | Sí | Sí |
| Agenda | Sí | Sí | Sí |
| Asistente de agenda (cuando el módulo está habilitado) | Sí | Sí | Sí |
| Directorio de pacientes (alta y datos de contacto) | Sí | Sí | Sí |
| Historia clínica (cuestionario) | Sí | Sí | Sí |
| Expediente clínico (consultas, notas privadas, recetas, planes, consentimientos, exploración y odontograma) | Sí | Sí | No |
| Cobros (cuando el módulo de pagos está habilitado) | Sí | Sí | Sí |
| Mensajes de contacto (cuando el módulo está habilitado) | Sí | No | Sí |
| Usuarios | Sí | No | No |
| Catálogo y precios | Sí | No | No |
| Contenido de la portada | Sí | No | No |
| Configuración, horarios y bloqueos | Sí | No | No |
| Reportes | Sí | No | No |
| Control de actividades | Sí | No | No |

Al crear una cuenta (**Administración → Usuarios → Nuevo usuario**), el sistema muestra
debajo del campo **Rol** una descripción de lo que el perfil seleccionado puede hacer.

### 2.2 Módulos opcionales

Algunos módulos están implementados, pero se mantienen **ocultos de forma predeterminada**.
Mientras un módulo está oculto, no aparece en el menú para ningún perfil y su dirección
responde con acceso denegado. El equipo técnico los habilita desde la configuración del
servidor, sin modificar el sistema.

| Módulo | Estado predeterminado | Apartado de este manual |
|---|---|---|
| Asistente de agenda | Oculto | 17.1 |
| Mensajes de contacto | Oculto | 18 |
| Verificación en dos pasos | Oculta | 3.3 |
| Galería | Oculta | 11.3 |
| Casos antes y después | Oculto | 11.3 |
| Pagos e ingresos | Oculto | 20 |

Los pacientes no tienen cuenta: agendan desde el portal público con su nombre y su
teléfono celular.

### 2.3 Diferencia entre cita y consulta

| Concepto | Definición | Dónde se gestiona |
|---|---|---|
| Cita | Reservación de un horario en la agenda: día, hora, tratamiento y estado. | Agenda (`/citas`). |
| Consulta | Nota médica que documenta la atención brindada al paciente. | Expediente del paciente. |

Completar una cita **no** crea la consulta de manera automática; esta debe registrarse en
el expediente del paciente (véase el apartado 9.6).

### 2.4 Estados de una cita

| Estado | Significado |
|---|---|
| Agendada | Cita histórica pendiente de confirmación manual. Las citas nuevas ya no utilizan este estado. |
| Confirmada | Horario reservado. Es el estado inicial de toda cita creada en línea, por Telegram o desde el panel. |
| En curso | La atención del paciente ha iniciado. |
| Completada | La atención concluyó. |
| Cancelada | La cita se anuló y su horario quedó disponible. |
| No asistió | El paciente no se presentó a la cita. |

Flujo de estados:

```text
Reserva pública, Telegram o panel → Confirmada → En curso → Completada
                                       │
                                       ├→ (reagendar) → Confirmada en el nuevo horario
                                       ├→ No asistió
                                       └→ Cancelada

Cita histórica → Agendada → Confirmada o Cancelada
```

Los estados **Completada**, **Cancelada** y **No asistió** son finales: una vez
registrados, la cita ya no admite acciones.

## 3. Acceso al sistema

### 3.1 Inicio de sesión

1. Abra la dirección del sistema y seleccione el acceso del personal, o escriba
   directamente `/login`.
2. Capture su **correo electrónico** y su **contraseña**.
3. Marque **Recuérdame** únicamente si utiliza un dispositivo personal y de confianza.
4. Pulse **Iniciar sesión**.
5. Si la verificación en dos pasos está activa, capture el código de seis dígitos
   (véase el apartado 3.3).

**Resultado esperado:** el sistema muestra la pantalla de **Inicio** correspondiente a su
perfil.

La pantalla de acceso presenta una fotografía del consultorio, el nombre y la identidad
de la clínica, el formulario de inicio de sesión y un enlace para regresar al sitio
público.

### 3.2 Recuperación de contraseña

1. En la pantalla de acceso, pulse **¿Olvidaste tu contraseña?**
2. Escriba el correo asociado a su cuenta y pulse el botón de envío.
3. Revise su bandeja de entrada y, en su caso, la carpeta de correo no deseado.
4. Abra el enlace recibido y defina una nueva contraseña.

> Por motivos de seguridad, el mensaje de confirmación es genérico: no revela si el
> correo existe en el sistema. Si el enlace no llega, verifique que el correo sea el
> registrado y solicite un nuevo enlace después de unos minutos.

El enlace se envía siempre a la dirección registrada en la cuenta de Dentissa. El canal
de envío que utilice la clínica no modifica al destinatario ni exige que cada usuario
vincule una cuenta propia de Google.

### 3.3 Verificación en dos pasos

La verificación en dos pasos agrega, además de la contraseña, un código de seis dígitos
que genera una aplicación del teléfono celular (Google Authenticator o Microsoft
Authenticator). Cuando la clínica la habilita, es **obligatoria para la doctora titular**,
por tener acceso a los expedientes clínicos, y **opcional y recomendada** para el resto
del personal.

> Actualmente el módulo de verificación en dos pasos se encuentra oculto (véase el
> apartado 2.2) y su obligatoriedad para la doctora titular, deshabilitada. Ambos deben
> habilitarse antes de registrar pacientes reales.

**Procedimiento de activación:**

1. Abra **Mi perfil → Verificación en dos pasos**. Si su perfil la requiere y aún no la ha
   activado, el sistema lo dirige a esta pantalla de forma automática.
2. Escanee el código QR con la aplicación del celular o, si no es posible, capture la
   clave que aparece debajo del código.
3. Escriba el código de seis dígitos que muestra la aplicación y confirme.
4. Guarde los **ocho códigos de recuperación** que el sistema presenta.

> Los códigos de recuperación se muestran una sola vez. Consérvelos en un lugar seguro.
> Cada código permite iniciar sesión una única vez en caso de extravío del celular. Desde
> la misma pantalla es posible generar códigos nuevos confirmando la contraseña; los
> anteriores quedan inutilizados.

**Inicio de sesión con verificación:** después de la contraseña aparece la pantalla
**Un paso más**. Capture el código vigente de la aplicación o un código de recuperación.
El código debe ingresarse dentro de los cinco minutos siguientes; de lo contrario, será
necesario iniciar sesión nuevamente. Si el código es rechazado, verifique que la fecha y
la hora del celular estén configuradas en modo automático.

### 3.4 Políticas de sesión y contraseña

| Política | Comportamiento del sistema |
|---|---|
| Longitud y composición | La contraseña debe tener al menos 10 caracteres e incluir mayúsculas, minúsculas y números. |
| Cambio de contraseña | Al cambiarla, se cierran las sesiones abiertas en otros equipos. |
| Inactividad | Tras 30 minutos sin actividad, la sesión se cierra automáticamente. |
| Cuenta desactivada | La persona es expulsada del sistema en su siguiente acción, aun cuando hubiera marcado **Recuérdame**. |
| Visualización | El icono 👁 muestra la contraseña capturada; debajo de **Confirmar** el sistema indica en tiempo real si ambas coinciden. |

### 3.5 Mi perfil

Desde el menú de usuario, la opción **Perfil** permite:

- actualizar nombre, apellido, teléfono y correo electrónico;
- cambiar la contraseña, para lo cual se solicita la contraseña actual;
- capturar la **especialidad**, que aparece en las recetas (solo para quienes atienden
  consultas; la cédula profesional se registra en **Configuración**, apartado 12.5);
- administrar la verificación en dos pasos;
- cambiar o quitar la fotografía de perfil.

**Fotografía de perfil:**

1. En **Información personal**, pulse **Elegir foto** o **Cambiar foto**.
2. Seleccione una imagen JPG, PNG o WebP de hasta 5 MB.
3. Revise la vista previa amplia y la vista reducida que muestra cómo aparecerá en el menú.
4. Pulse **Guardar**; la fotografía se guarda junto con los datos personales.

**Quitar foto** muestra las iniciales en la vista previa y se aplica al pulsar
**Guardar**; **No quitarla** revierte la acción. La fotografía solo es visible para la
cuenta propietaria.

El perfil no permite eliminar ni desactivar la propia cuenta. La desactivación se realiza
desde **Administración → Usuarios** y nadie puede cambiar su propio rol, con el fin de que la
clínica nunca se quede sin una persona que administre el panel.

## 4. Navegación e interfaz

### 4.1 Menú principal

La barra superior muestra la marca del consultorio (logotipo con la firma
**Dra. Melissa López**) y los accesos siguientes. Cada pantalla se titula igual que su
enlace en el menú.

| Elemento del menú | Contenido |
|---|---|
| Inicio | Pantalla de inicio con accesos rápidos. |
| Agenda | Citas en vista de lista o de semana. |
| Asistente | Asistente de agenda (solo cuando el módulo está habilitado). |
| Pacientes | Directorio de pacientes y expediente. El perfil de asistente ve en su lugar **Historias clínicas**. |
| Administración | Usuarios, Reportes, Pagos e ingresos y Mensajes (cuando están habilitados) y Control de actividades. |
| Catálogo | Especialidades y Tratamientos. |
| Sitio web | Ofertas, Testimonios y Preguntas frecuentes (lo que ven los pacientes en la portada); Galería y Casos cuando están habilitados. |
| Configuración | Datos del consultorio, Horarios de atención y Días u horas bloqueados. |

El menú completo se muestra en pantallas de 1024 píxeles de ancho o más (computadora,
laptop, televisión y proyector). En pantallas menores se muestran únicamente el logotipo y
la fotografía del usuario; el resto de las opciones se encuentra en el botón de menú (☰).
La campana abre una vista rápida de las notificaciones.

### 4.2 Pantalla de inicio

La doctora titular dispone de accesos rápidos para agendar citas, consultar pacientes,
administrar servicios y contenido, y gestionar usuarios. Los demás perfiles ven un inicio
simplificado con accesos a sus citas y al asistente de agenda.

### 4.3 Listados

- Cada listado muestra, en la parte superior izquierda, el total de registros y el
  selector de registros por página (**20** de forma predeterminada, o **50**); en la
  parte superior derecha, el botón **Nuevo …**.
- La paginación se ubica al centro, en la parte inferior, con los botones **Anterior**,
  los números de página y **Siguiente**. Los filtros activos se conservan al cambiar de
  página.
- Los buscadores y filtros se aplican automáticamente al dejar de escribir; **Limpiar
  filtros** los restablece.
- **Tablas cortas** (Pacientes, Usuarios, Servicios, Testimonios, Preguntas frecuentes,
  Casos y Horarios): en celular y tableta cada registro se presenta como tarjeta, con el
  nombre en la parte superior, cada dato con el título de su columna y los botones al
  final.
- **Tablas extensas** (Control de actividades, Mensajes de contacto, Ofertas y
  Tratamientos): conservan sus columnas y se desplazan horizontalmente solo cuando no
  caben. La primera columna permanece fija y se muestra la indicación «Desliza para ver
  más columnas».

### 4.4 Iconos de acción

| Icono | Acción |
|---|---|
| ✎ | Editar el registro. |
| 🗑 | Eliminar el registro. |
| Ojo | Publicar. |
| Ojo tachado | Ocultar. |
| Pulgar arriba / pulgar abajo | Aprobar / rechazar. |
| Sobre abierto / sobre cerrado | Marcar como leído / marcar como no leído. |
| 📋 | Abrir el expediente clínico del paciente. |
| ⚠ (icono apagado) | La acción no está disponible; al tocar el símbolo se explica el motivo. |
| ⓘ | Información adicional; no ejecuta ninguna acción. |

Al colocar el cursor sobre un icono se muestra una descripción de su función.

### 4.5 Interruptor de estado

Las listas de Servicios, Tratamientos, Casos, Galería, Preguntas frecuentes y Usuarios
incluyen un interruptor en la columna **Estado** que permite activar o desactivar el
registro sin entrar a su edición. El sistema siempre solicita confirmación e indica la
consecuencia del cambio (por ejemplo, «Dejará de mostrarse en la portada»). La cuenta
propia aparece atenuada con ⓘ, ya que nadie puede desactivarse a sí mismo.

### 4.6 Confirmaciones, avisos y formularios

- Las acciones sensibles (eliminar, cancelar una cita, desactivar, entre otras) abren una
  ventana de confirmación propia del sistema. Puede cerrarse con **Esc**, con
  **Cancelar** o pulsando fuera de ella.
- Tras crear, guardar o eliminar, aparece un aviso en la esquina inferior derecha: verde
  cuando la operación fue exitosa (se oculta solo) o rojo cuando falta información (se
  cierra con ×).
- Los campos obligatorios se marcan con asterisco (\*). Si un dato falta o es incorrecto,
  el mensaje aparece en rojo debajo del campo y el cursor se coloca en el primer error.
- **Guardar cambios** permanece deshabilitado hasta que se modifica algún dato, y vuelve a
  deshabilitarse si se restablecen los valores originales.
- Los botones que guardan información muestran un texto de espera (por ejemplo,
  «Agendando…») y se bloquean para impedir envíos duplicados.
- Para salir de un formulario sin guardar, utilice **Cancelar**. Las pantallas que no
  tienen este botón, como el expediente del paciente, muestran el enlace
  **← Volver a …** sobre el título.

### 4.7 Adaptación a dispositivos y accesibilidad

- El sistema se adapta a todos los tamaños de pantalla, desde teléfono celular hasta
  televisión o proyector.
- En el celular, ningún texto mide menos de 12 píxeles y los botones tienen al menos
  44 píxeles de alto. El teléfono no amplía la página al escribir en un campo, y en
  equipos con muesca el contenido no queda oculto.
- Las animaciones se limitan a las que ayudan a comprender una acción (cambio de paso al
  agendar, confirmación de la cita, avisos y menús). Si el dispositivo tiene activada la
  opción «reducir movimiento», se suavizan o se suprimen.
- El sitio puede instalarse como aplicación en el dispositivo. Si se pierde la conexión,
  se muestra una pantalla para reintentar cuando se restablezca.

## 5. Portal público

La página principal presenta la información del consultorio y el acceso a la agenda en
línea. Sus secciones, en orden de aparición, son las siguientes.

### 5.1 Bienvenida

- Fotografía principal (a la derecha en computadora y arriba en el celular) y el mensaje
  «Una consulta sin prisas, explicada paso a paso».
- **Agendar mi valoración**: abre la agenda en línea.
- **¿Prefieres llamar?**: muestra el teléfono del consultorio; en el celular, permite
  marcarlo directamente.
- Indicador de disponibilidad, por ejemplo «Abierto hoy hasta las 18:00» o «Cerrado ahora
  · abre el lunes a las 8:00». Se calcula con la hora local de la clínica a partir de
  **Horarios de atención**, por lo que cualquier cambio de horario se refleja de inmediato.

### 5.2 Tratamientos

La sección **Tratamientos para tu salud y armonía** muestra las especialidades activas en
un carrusel de tarjetas con fotografía:

- Cada tarjeta indica **Agenda en línea** (la especialidad tiene tratamientos activos y, al
  pulsarla, abre la agenda con esa especialidad seleccionada) o **Agenda por teléfono**
  (al pulsarla, marca el número del consultorio).
- En computadora se muestran tres tarjetas; las flechas **‹ ›** avanzan una tarjeta por
  pulsación y, al llegar al final, regresan a la primera. En el celular se desliza con el
  dedo. Los puntos inferiores indican la posición.
- La fotografía y la descripción provienen de **Catálogo → Especialidades**; si no se
  capturan, se utilizan las predeterminadas.

### 5.3 Primera visita, la clínica e instalaciones

- **Tu primera visita** explica el proceso en tres pasos: elegir horario, recibir la
  confirmación y el recordatorio por WhatsApp, y acudir a la primera consulta con la
  historia clínica.
- **La clínica** muestra la misión y la visión capturadas en **Configuración → Datos del
  consultorio**; si se borran, se restablece el texto predeterminado.
- **El consultorio** (antes «Instalaciones» en el menú) muestra las fotografías activas de
  **Sitio web → Galería**, en su orden. Si no existe ninguna, se presentan dos
  ilustraciones informativas: «Un paciente a la vez» (o «Citas con hora exacta» cuando se
  atiende a más de un paciente por horario) y «Te avisamos un día antes».

### 5.4 Ofertas, casos y testimonios

- **Ofertas**: carrusel con hasta 24 promociones publicadas y vigentes, ordenadas por
  fecha de vencimiento. Cada una incluye **Pedir info** (abre WhatsApp con el nombre de la
  oferta) y **Agendar** (abre la agenda con la oferta aplicada). El botón **Ver todas las
  promociones** abre `/promociones`, con todas las ofertas publicadas y filtros por
  especialidad; el enlace puede compartirse, incluso filtrado.
- **Casos antes y después**: casos activos con fotografía de **Sitio web → Casos**. La
  primera imagen se presenta como «Antes» y la segunda como «Después».
- **Testimonios**: opiniones aprobadas por la clínica. El paciente puede enviar la suya
  desde **Comparte tu experiencia**: el formulario aparece al pulsar **Escribir mi opinión**
  y el paciente se identifica con el teléfono o el correo con el que agendó. Si el envío
  tiene errores, la página regresa con el formulario ya abierto y los errores marcados.

### 5.5 Preguntas frecuentes, ubicación y contacto

- **Preguntas frecuentes**: se muestran las activas; al pulsar una pregunta se despliega
  su respuesta.
- **Ubicación**: dirección del consultorio con mapa de Google. **Cómo llegar** abre la ruta
  en Google Maps y **Escribir por WhatsApp** abre la conversación. Si además se captura el
  **Nombre del negocio en Google Maps**, el mapa y **Cómo llegar** abren la ficha del
  consultorio (con su fotografía, horario y teléfono) en lugar de un punto en la calle. Si
  la dirección se deja vacía en la configuración, el mapa no se muestra.
- **Barra de promoción** (arriba de todo): aparece solo cuando hay una promoción vigente; muestra
  la que termina primero con su cuenta regresiva y lleva a **Promociones**. El paciente puede
  cerrarla y no vuelve a salir en esa visita.
- **Llamar** (ícono de teléfono junto al menú, solo en el celular): abre la llamada al consultorio.
- **Opiniones**: arriba del carrusel se ve la calificación promedio de todas las opiniones
  aprobadas (por ejemplo «4.8 de 5 · promedio de 6 opiniones»).
- **Tu visita** (columna del pie): Agendar cita, Promociones, Preguntas frecuentes, Cómo llegar
  y Aviso de privacidad.
- **Redes sociales del pie**: íconos de Instagram, Facebook y TikTok; solo los que tengan enlace en
  **Configuración → Datos del consultorio**.
- **Pie de página**: teléfono, correo (de **Configuración → Datos del consultorio**), horario semanal (por ejemplo, «Lunes a viernes de 8:00 a
  18:00 hrs»; los días cerrados se omiten) y enlace al aviso de privacidad.
- Un botón verde de WhatsApp permanece fijo en la esquina inferior derecha, encima del
  asistente virtual. En el celular ambos se ocultan al bajar por la página, para no tapar
  el contenido, y vuelven a aparecer al subir.

### 5.6 Navegación del portal

Los enlaces **Inicio**, **Tratamientos**, **La doctora**, **El consultorio**,
**Testimonios** y **Contacto** desplazan la página hasta la sección correspondiente sin
recargarla, y el enlace de la sección visible se resalta automáticamente.

> El portal no crea citas por sí mismo. Teléfonos, correos, horarios, calificaciones y
> testimonios solo deben publicarse una vez que la clínica los haya verificado y aprobado.

## 6. Agendamiento en línea

### 6.1 Procedimiento para el paciente

El proceso consta de cuatro pasos; la parte superior indica en todo momento «Paso X de 4».

1. Abrir `/agendar-cita` o pulsar **Agendar mi cita**.
2. **Tipo de atención:** elegir la especialidad y, a continuación, el tratamiento. Cada
   tratamiento muestra su duración aproximada y su precio. Para cambiar de especialidad,
   se pulsa el recuadro de la especialidad elegida. Solo se muestran las especialidades
   con tratamientos activos.
3. **Fecha y hora:** seleccionar un día en el calendario y, después, un horario libre.
   Cada horario indica su hora de término (por ejemplo, «09:30 a 10:15»). Pulsar
   **Revisar resumen**.
4. **Confirmación:** revisar tratamiento, fecha y hora, y pulsar **Continuar a mis datos**.
5. **Datos de contacto:** capturar nombre, apellido, teléfono celular (obligatorio) y
   correo electrónico (opcional); el motivo de la consulta es opcional, con un máximo de
   1000 caracteres. Marcar **He leído y acepto el aviso de privacidad**. El enlace
   **Cambiar** permite regresar a la selección de fecha y hora.
6. Pulsar **Agendar cita**. El botón cambia a «Agendando…» y se bloquea mientras se guarda.

En computadora y tableta, el recuadro **Tu cita, paso a paso** acompaña todo el proceso con
el servicio, el tratamiento, el precio, la oferta aplicada, la fecha y la hora. En el celular
aparece solo en el último paso, para revisar todo antes de confirmar sin alargar los pasos
anteriores.

**Resultado esperado:** aparece la pantalla **«¡Listo! Tu cita quedó confirmada»** con el
resumen de la cita. La cita queda en estado **Confirmada**, sin que el personal tenga que
confirmarla nuevamente.

### 6.2 Lectura del calendario

| Indicación | Significado |
|---|---|
| Punto verde | El día tiene horarios disponibles. |
| Punto durazno | Quedan tres lugares o menos. |
| Día tachado | Ya no hay horarios disponibles. |
| Día gris | Fecha pasada o día en que el consultorio no abre. |

- Las flechas **‹ ›** cambian de mes, hasta doce meses en adelante.
- El calendario solo permite elegir a partir del día actual según la hora de la clínica,
  aunque se agende de noche.
- Pulsar nuevamente el día o la hora seleccionados anula la selección.
- Debajo del calendario se muestra el horario regular del consultorio.

### 6.3 Validaciones de los datos

- El teléfono corresponde al número de WhatsApp, a diez dígitos, y se da formato
  automáticamente mientras se escribe (por ejemplo, «55 1234 5678»).
- Cada dato se valida al pasar al siguiente campo: si es incorrecto, aparece el mensaje
  debajo (por ejemplo, «Faltan 6 dígitos…»); si es correcto, se muestra una palomita verde.
- Si al pulsar **Agendar cita** existen errores, no se envía la información: aparece un
  aviso como «Revisa los 2 datos marcados para poder agendar tu cita», el cursor se coloca
  en el primer error y cada campo señalado muestra su mensaje en español.
- Si el servidor detecta un error al guardar, el formulario regresa con los mismos avisos
  y conserva los datos correctos.
- Sin la aceptación del aviso de privacidad, la cita no se guarda y se muestra el mensaje
  «Para agendar necesitamos que aceptes el aviso de privacidad».

### 6.4 Reglas aplicadas por el sistema

- **Pago:** se realiza en el consultorio el día de la cita; así se indica en la agenda, en
  la confirmación y en los correos. El sistema no procesa pagos en línea.
- **Identificación del paciente:** el sistema crea un registro nuevo o reutiliza el
  existente y bloquea el horario en el mismo momento en que crea la cita.
- **Protección contra abuso:** se aceptan hasta 5 envíos por minuto y 20 por hora desde
  una misma conexión. Al superarse, aparece el aviso «Recibimos varios intentos seguidos.
  Espera un minuto e inténtalo de nuevo.», y los datos capturados se conservan.
- **Ofertas:** una oferta solo se aplica si el paciente ingresa desde su tarjeta (véase el
  apartado 11.1).

### 6.5 Navegación del formulario

**Atrás** regresa al paso anterior sin perder lo seleccionado, y los botones de avance se
habilitan cuando el paso está completo. En el celular, ambos botones permanecen fijos en
la parte inferior. En pantallas amplias, junto al formulario se muestra un resumen que se
actualiza con el servicio, el tratamiento, la fecha y la hora elegidos; en el celular, ese
resumen se ubica debajo del formulario.

### 6.6 Aviso de privacidad

El aviso completo se publica en `/aviso-de-privacidad` y se enlaza desde el pie de la
portada y desde los correos. Al marcar la casilla, el sistema registra la fecha de
aceptación y la versión del aviso aceptada. En Telegram, el resumen previo a la
confirmación enlaza el aviso y pulsar **Confirmar cita** equivale a aceptarlo.

> Antes de operar con pacientes reales, la clínica debe capturar sus datos de responsable,
> domicilio y correo para solicitudes, y el texto debe ser revisado por un abogado o por
> la propia clínica. Mientras ello no ocurra, la página muestra la leyenda «Borrador
> pendiente de revisión legal» y resalta los datos faltantes.

## 7. Recordatorios y respuestas del paciente

### 7.1 Momento de envío

El recordatorio se envía el **día hábil anterior** a la cita, **una hora después de la
apertura** del consultorio, por correo electrónico y, cuando WhatsApp está configurado,
también por WhatsApp. Se considera día hábil aquel que está abierto en **Horarios de
atención** y no tiene un bloqueo de día completo.

| Horario del consultorio | Cita | Envío del recordatorio |
|---|---|---|
| Lunes a viernes, 9:00 a 18:00 | Martes a las 19:00 | Lunes a las 10:00 |
| Lunes a viernes, 9:00 a 18:00 | Lunes | Viernes a las 10:00 |

Con esta regla, el mensaje nunca llega de noche ni en un día cerrado, y las respuestas
del paciente se reciben en horario de trabajo.

- Las citas agendadas después del momento del recordatorio no lo reciben, puesto que el
  paciente acaba de obtener su confirmación.
- Si el personal reagenda una cita, se programa un recordatorio nuevo para la nueva fecha.

### 7.2 Opciones de respuesta

| Opción | Efecto |
|---|---|
| **Sí, asistiré** | La cita permanece confirmada y la agenda muestra **✓ Paciente confirmó asistencia**. |
| **Quiero reagendar** | Se libera el horario y el paciente recibe un enlace que abre la agenda directamente en el calendario, con el mismo tratamiento. Solo elige el nuevo día y la nueva hora y pulsa **Confirmar cambio de cita**, sin volver a capturar sus datos. |
| **No podré asistir, cancelar cita** | La cita se cancela y el horario queda disponible. |

- Abrir una opción desde el correo no modifica la cita por sí mismo: se muestra una página
  con los datos de la cita que solicita confirmar la respuesta. Desde ella es posible
  elegir otra opción.
- Si la cita ya pasó o fue cancelada, la página indica que ya no admite cambios y muestra
  el teléfono del consultorio.
- El enlace de reagendamiento es personal, tiene vigencia de siete días y solo puede
  utilizarse una vez. El paciente recibe el mensaje «Tu cita se movió al…»; la campana del
  personal muestra **Nuevo horario del paciente** y la cita anterior aparece en la agenda
  como **↻ Reagendada por el paciente → nueva fecha**.
- Al reagendar se conserva la oferta que la cita tenía, siempre que siga vigente.

### 7.3 Respuestas por WhatsApp

El mensaje de WhatsApp contiene las mismas tres opciones. Al pulsar una, la cita cambia de
la misma manera y el paciente recibe una respuesta en la conversación: la confirmación o,
si pidió reagendar o canceló, el enlace para elegir otro horario. Si la opción ya no puede
aplicarse (por ejemplo, porque la cita fue eliminada), se le solicita comunicarse con el
consultorio.

> Para que las respuestas por WhatsApp funcionen, el sistema debe estar publicado en línea
> y Meta debe haber aprobado la plantilla del recordatorio. Un recordatorio de prueba
> enviado desde una instalación local no modifica las citas locales.

### 7.4 Respuesta automática

El número de WhatsApp que envía los recordatorios no es atendido por personal. Si un
paciente escribe (texto, audio o imagen), recibe una respuesta automática con el teléfono
del consultorio y el enlace para agendar. Esta respuesta se envía como máximo una vez cada
12 horas por número, y el sistema no almacena el contenido del mensaje recibido.

## 8. Agenda interna

La agenda se encuentra en `/citas`. A la derecha del título, el selector **Lista |
Semana** cambia la forma de visualización.

### 8.1 Vista de lista

Es la vista predeterminada y muestra las citas de un solo día, en orden de hora,
comenzando por el día actual.

- **Pestañas de días:** una por cada día de la semana, con el número de citas y, en ámbar,
  las que están por confirmar. Las flechas **‹ ›** (o «Semana anterior / siguiente» en el
  celular) cambian de semana e **Ir a hoy** regresa al día actual.
- **Conteo del día:** indica cuántas citas quedan por atender y, por separado, cuántas se
  atendieron, se cancelaron o no asistieron (por ejemplo, «1 por atender · 2 atendidas · 1
  cancelada»). Las citas cerradas se agrupan en un renglón con el botón **Mostrar**.
- **Cita en curso:** se resalta con una franja de color.
- **Buscar en todas las fechas:** localiza un paciente (nombre, apellido, teléfono o
  correo), servicio o tratamiento en cualquier fecha, agrupando los resultados por día. La
  búsqueda se ejecuta al dejar de escribir. **Todas las fechas** muestra el historial
  completo, con 20 o 50 registros por página.
- **Estado:** filtra las citas del día elegido o de la búsqueda.
- En el celular, cada cita es una tarjeta con la hora y el paciente arriba, el tratamiento
  en medio y los botones abajo.

Tras confirmar, iniciar, completar, cancelar o registrar una inasistencia, la agenda
permanece en el mismo día y en la misma vista. Al crear o reagendar una cita, la agenda se
abre en el día de esa cita.

Desde cada cita, el nombre del paciente abre su expediente (si el perfil tiene acceso) y
el teléfono permite llamarle desde el celular.

### 8.2 Vista semanal

Presenta un mapa de lunes a sábado dividido en bloques de dos horas:

| Color del bloque | Significado |
|---|---|
| Blanco | Libre. |
| Rosa claro | Con citas, pero con lugar disponible. |
| Rosa | Lleno. |
| Tono atenuado | Bloque que ya transcurrió. |

- Al seleccionar un bloque se muestra su detalle (a la derecha en computadora y debajo en
  el celular): horario del día, **Dónde cabe una cita** con las horas libres y la lista de
  citas del bloque.
- Pulsar una hora libre abre **Agendar cita** con esa fecha seleccionada; pulsar una cita
  abre su edición, donde se encuentran las acciones.
- Al ingresar, se abre el bloque actual o, si el día terminó, el siguiente.
- Un día cerrado que tenga citas muestra **Cerrado · N citas**.
- **←**, **Hoy** y **→** cambian de semana. La dirección de la página conserva la semana,
  por lo que puede recargarse o compartirse.

> Las horas libres de la vista semanal son orientativas: al agendar, el sistema vuelve a
> verificar el horario con la duración del tratamiento elegido.

### 8.3 Crear una cita

1. Pulse **Nueva cita**.
2. Seleccione el paciente (la lista está en orden alfabético). Si el paciente no existe,
   utilice **Registrar paciente nuevo**.
3. Seleccione el servicio y el tratamiento. Si la especialidad no tiene tratamientos, el
   sistema lo advierte; deben agregarse en **Catálogo → Especialidades**.
4. Elija el día y la hora en el calendario, que funciona igual que el de la agenda pública.
5. Guarde la cita.

**Resultado esperado:** la cita queda en estado **Confirmada** y la agenda se abre en el
día correspondiente.

### 8.4 Reagendar una cita

Abra la cita, seleccione la opción para reagendar y elija un nuevo horario disponible en
el calendario. Mientras se reagenda, el horario actual de la propia cita se considera
libre. El paciente recibe un recordatorio nuevo para la nueva fecha.

### 8.5 Acciones disponibles según el estado

| Estado | Acción principal | Otras acciones |
|---|---|---|
| Agendada | **Confirmar** | Menú **Acciones**: reagendar, cancelar e inasistencia (cuando procede). |
| Confirmada | **Iniciar** (el día de la cita) | Menú **Acciones**: reagendar, cancelar e inasistencia (cuando procede). |
| En curso | **Completar** | Ninguna. |
| Completada, Cancelada o No asistió | Sin acciones | Ninguna. |

Reglas de las acciones:

- **Iniciar** solo aparece el día de la cita y mientras no haya concluido su horario. No
  es posible iniciar otra cita mientras exista una **En curso**, salvo que el valor
  **Pacientes a la vez** sea mayor que uno.
- **Marcar inasistencia** aparece en el menú de acciones solo a partir de la hora
  programada. Una vez concluido el horario de la cita sin que se haya atendido, desaparece
  **Iniciar** y se muestran a la vista **Atendida** (completa la cita en un solo paso) y
  **No asistió**.
- Registrar una inasistencia solicita confirmación, ya que no puede deshacerse. Cancelar
  solicita un motivo opcional y confirmación.
- Al completar una cita aparece **Registrar consulta**, que abre una consulta nueva con
  esa cita y su fecha preseleccionadas.
- Tras cada acción, la agenda muestra el estado actualizado y un aviso de confirmación; si
  la operación no procede, se muestra el motivo.

### 8.6 Cierre automático de citas olvidadas

Si una cita permanece **En curso** de un día anterior porque nadie pulsó **Completar**, el
sistema la completa automáticamente cada noche a las 00:05 y deja un aviso en la campana
(«Se completaron N citas olvidadas», con los días correspondientes). Mientras tanto, la
agenda muestra un aviso ámbar para que el personal pueda completarla manualmente. Se
recomienda verificar que esas citas tengan su consulta registrada en el expediente.

### 8.7 Validaciones al agendar o reagendar

El sistema verifica que el servicio y el tratamiento estén activos, que el tratamiento
pertenezca al servicio y que la fecha y hora sean futuras. Si el horario fue ocupado en el
intervalo, se solicita elegir otro. El filtro de fecha de la agenda permite consultar
citas pasadas, actuales o futuras sin modificarlas.

## 9. Pacientes y expediente clínico

### 9.1 Directorio de pacientes

El directorio se encuentra en **Pacientes** (`/admin/pacientes`). Permite buscar
por una parte del nombre, el correo o el teléfono. La columna «Consultas en expediente»
indica el número de notas médicas registradas (no el número de citas).

### 9.2 Registrar un paciente

Este procedimiento se utiliza para pacientes que llegan sin cita o para capturar su
historia clínica con anticipación.

1. Pulse **Nuevo paciente**.
2. Capture nombre, apellido y teléfono celular (obligatorios) y, opcionalmente, el correo.
3. Confirme que el paciente **aceptó el aviso de privacidad**.
4. Pulse **Guardar**.

**Resultado esperado:** se abre el expediente del paciente; en el perfil de asistente, se
abre directamente su cuestionario de historia clínica. Si el celular ya está registrado,
el sistema indica a quién pertenece y ofrece **Abrir su expediente** para evitar
duplicados. Cuando el paciente agende en línea con ese número, el sistema lo reconocerá.

### 9.3 Editar los datos de contacto

Pulse ✎ en el paciente. Nombre, apellido y teléfono celular son obligatorios, ya que el
teléfono se utiliza para los avisos por WhatsApp y para reconocer al paciente al agendar.
El teléfono debe tener diez dígitos, con el prefijo `+52` opcional. El correo es opcional
y no puede repetirse entre pacientes.

### 9.4 Historia clínica (cuestionario)

La historia clínica es la primera sección del expediente (📋). La primera vez se muestra
**Llenar cuestionario** y, posteriormente, **Ver o editar**. Comprende:

- datos generales, estado civil, contacto de emergencia (nombre, parentesco y teléfono) y
  médico de cabecera;
- antecedentes familiares y personales, incluidos gastritis, otras enfermedades
  respiratorias y cirugías previas, y si el paciente se encuentra en tratamiento médico;
- alergias y medicamentos;
- embarazo y lactancia (solo si el sexo registrado es femenino);
- antecedentes dentales, hábitos y observaciones.

Ningún campo es obligatorio: se captura la información disponible y se completa en
visitas posteriores. Las alergias y las condiciones de riesgo (coagulación, padecimientos
cardiacos, hipertensión, diabetes, embarazo, entre otras) se muestran en un **aviso rojo**
en la parte superior del expediente y al registrar cada consulta. La historia clínica se
incluye en el PDF del expediente.

El perfil de asistente accede al cuestionario desde **Historias clínicas**, un listado con
buscador que indica si cada paciente la tiene **Llena** o **Pendiente**. La asistente no
tiene acceso a las consultas, las notas privadas, los adjuntos ni el PDF.

### 9.5 Exploración clínica y odontograma

Disponible para la doctora titular y los doctores. Debajo de la historia clínica se
encuentra **Hacer exploración** (la primera) o **Nueva exploración** (controles
posteriores). Incluye:

| Apartado | Captura |
|---|---|
| Examen de la boca | Labios, lengua, paladar, encías, ganglios y demás estructuras: «Normal» o descripción del hallazgo. |
| ATM y encías | Dientes con sangrado, inflamación, sarro u otros hallazgos. |
| Higiene oral e índice de O'Leary | Dientes presentes y superficies con placa; el porcentaje se calcula automáticamente (en verde si es igual o menor al 20 %). |
| Problemas pulpares e interpretación radiográfica | Un renglón por diente, mediante **+ Agregar diente**. |
| Odontograma | Se elige un estado (Cariado, Obturado, Extracción indicada, etc.) y se seleccionan los dientes. **Rojo** indica pendiente de tratar y **azul**, existente o ya realizado. Los dientes temporales tienen su propia sección. |
| Otros hallazgos y diagnósticos definitivos | Texto libre. |

Seleccionar de nuevo un diente con el mismo estado elimina la marca. Cada exploración nueva
parte del odontograma de la anterior, de modo que solo se registran los cambios. El
expediente muestra el resumen de la última exploración (higiene, O'Leary y dientes por
estado) y la lista de exploraciones anteriores; la más reciente se incluye en el PDF.

### 9.6 Consultas

1. Abra el expediente (📋) o pulse el número de consultas del paciente.
2. Pulse **Nueva consulta**.
3. Capture los campos obligatorios: **fecha**, **tipo** y **motivo**.
4. Capture, si corresponde, diagnóstico, tratamiento, diente(s) tratados, medicamentos,
   observaciones y notas privadas, y relacione la cita cuando aparezca disponible.
5. Pulse **Guardar**.

**Resultado esperado:** la consulta aparece en el historial del paciente, desde donde
puede consultarse, editarse o eliminarse. El formulario se presenta en una sola columna,
con cada pregunta en su propio renglón.

El campo **Notas privadas del doctor** solo es visible para el personal clínico y no se
incluye en el PDF descargable. El PDF del expediente se habilita cuando existe al menos
una consulta.

### 9.7 Receta en PDF

Cada consulta con medicamentos registrados muestra el botón **Receta**, que abre un
documento listo para imprimir con: nombre, cédula profesional y especialidad de quien
atiende; fecha; nombre, edad y alergias del paciente; diagnóstico; medicamentos; línea de
firma; y, al pie, la dirección y el teléfono del consultorio.

> La cédula profesional de cada doctor la registra la doctora titular en **Configuración →
> Cédulas profesionales** (apartado 12.5). De manera temporal, la
> receta se genera aunque la cédula no se haya capturado; en ese caso, el documento omite
> esa línea, que aparece automáticamente una vez registrada. El equipo técnico puede
> volver a exigir la cédula desde la configuración del servidor. Cada receta impresa
> queda registrada en el Control de actividades.

### 9.8 Consentimiento informado

1. En el expediente, abra **Consentimientos informados → Nuevo consentimiento**.
2. Capture el **procedimiento** (el sistema sugiere los tratamientos del catálogo) y, si
   aplica, los dientes involucrados.
3. Revise y ajuste los textos predeterminados de **beneficios**, **riesgos** y
   **alternativas**, de acuerdo con lo explicado al paciente.
4. Guarde y pulse **Abrir para imprimir**.

**Resultado esperado:** se genera una carta con los datos del consultorio y del paciente
(incluidas edad y alergias), el procedimiento, la información explicada y los espacios
para la firma autógrafa del paciente o su representante, de quien atiende y de dos
testigos. El documento firmado se archiva físicamente en el expediente del paciente; el
consentimiento queda en la lista para reimprimirse cuando sea necesario.

### 9.9 Plan de tratamiento

Se utiliza para tratamientos de varias sesiones (ortodoncia, rehabilitación, entre otros).

1. En el expediente, abra **Plan de tratamiento → Nuevo plan** y asigne un nombre.
2. Agregue los **pasos**: procedimiento, dientes y costo. Al elegir un tratamiento del
   catálogo, se sugiere su precio.
3. Marque cada paso como **hecho** mediante el círculo correspondiente; el sistema
   registra la fecha. Un paso puede regresar a pendiente.

El **avance** (porcentaje) y el **costo del plan** se calculan automáticamente. Cuando
todos los pasos están concluidos, el sistema sugiere cambiar el estado del plan a
**Terminado**. El expediente muestra cada plan con su barra de avance.

### 9.10 Confidencialidad

> Durante las pruebas y la demostración deben utilizarse exclusivamente datos ficticios.
> No deben cargarse imágenes, documentos ni datos médicos reales en entornos de
> desarrollo o demostración.

## 10. Catálogo

### 10.1 Especialidades

En **Catálogo → Especialidades**, la pantalla de edición de la especialidad permite modificar el nombre,
la descripción, la fotografía (con vista previa de la tarjeta) y los tratamientos que
pueden agendarse. La pantalla de alta utiliza el mismo formulario.

- Un servicio nuevo queda **Disponible**: se muestra en la portada y puede agendarse en
  línea, desde el panel, desde el asistente y por Telegram.
- El interruptor **Disponible** de la lista es el único medio para ocultarlo o mostrarlo.
  Las citas ya agendadas no se modifican. Al editar un servicio oculto, la pantalla lo
  recuerda.
- El orden en la portada se ajusta con las flechas ↑ ↓ de la columna **Orden en la
  portada**. Con un filtro activo, el número conserva la posición real y, para reordenar,
  es necesario quitar el filtro.
- Para que una especialidad se agende en línea, basta con que tenga al menos un
  tratamiento activo.

### 10.2 Tratamientos

El formulario solicita **Nombre**, **Duración** (tiempo que ocupa en la agenda, de 15 a
480 minutos) y **Precio** (visible para el paciente al agendar). El panel **Así lo verá el
paciente** muestra la presentación en la agenda en línea y la especialidad a la que
pertenece.

- Un tratamiento puede guardarse sin precio mientras la clínica lo confirma, pero **no
  puede activarse ni publicarse** sin él; su fila muestra «Falta precio».
- Activar o desactivar un tratamiento solicita confirmación. Si al desactivarlo una
  especialidad quedaría sin tratamientos, el sistema advierte que pasará a «Agenda por
  teléfono».

### 10.3 Asignación de horarios según la duración

- Los horarios se ofrecen cada 30 minutos.
- La última cita del día es aquella que **termina** a la hora de cierre. Con cierre a las
  18:00, una valoración de 30 minutos puede agendarse hasta las 17:30 y una limpieza de
  60 minutos, hasta las 17:00.
- Los tratamientos largos o quirúrgicos reservan 15 minutos de preparación entre una cita y
  la siguiente.

### 10.4 Catálogo de la demostración

La demostración utiliza los tratamientos y las duraciones reales de la clínica con precios
de demostración:

| Tratamiento | Duración | Precio de demostración |
|---|---|---|
| Valoración | 30 minutos | $300 |
| Limpieza dental | 1 hora | $600 |
| Retiro de caries | 1 hora | $700 |
| Extracción dental | 1 hora | $900 |
| Blanqueamiento dental | 1 hora 30 minutos | $3,500 |

> La clínica debe confirmar o corregir estos precios en **Catálogo → Tratamientos** antes
> de recibir pacientes reales.

## 11. Contenido de la portada

### 11.1 Ofertas

**Crear una oferta** (**Sitio web → Ofertas → Nueva oferta**):

1. Capture el **título** (de 5 a 120 caracteres).
2. Seleccione el **Beneficio para el paciente**: **Descuento** (siempre en porcentaje),
   **Precio especial** (monto en pesos) o **Gratis** (sin cantidad). La opción elegida se
   resalta; pulsarla de nuevo la deselecciona.
3. Capture la **cantidad**, cuando el beneficio la requiera.
4. Capture la **descripción** (de 20 a 300 caracteres).
5. Indique **Válida hasta** (la fecha actual o posterior). La vigencia inicia el día de
   creación.
6. Opcionalmente, seleccione el **Tratamiento al que aplica**. Sin tratamiento, la oferta
   solo se anuncia y no se aplica al agendar. Solo se listan tratamientos que se agendan
   en línea.
7. Opcionalmente, en **Más detalles**, defina el número de lugares y una imagen.
8. Guarde.

| Beneficio | Captura | Presentación en la portada |
|---|---|---|
| Descuento | 20 | −20% |
| Precio especial | 2800 | Precio especial $2,800 |
| Gratis | — | Gratis |

> El título y la descripción no admiten enlaces, correos electrónicos, código ni
> caracteres repetidos (por ejemplo, «!!!!!!»).

**Ciclo de publicación:**

| Estado | Visible en la portada | Observaciones |
|---|---|---|
| En revisión | No | Estado inicial al guardar y tras editar datos visibles para el paciente. |
| Publicada | Sí | Se publica con **✓ Publicar**, previa confirmación. |
| Publicada, no se ve (⚠) | No | Venció, su tratamiento está inactivo, se agotaron los lugares o no cabe entre las 24 del carrusel. El símbolo ⚠ explica el motivo. |
| Oculta | No | Se oculta con el interruptor; puede editarse y publicarse de nuevo con ✓. |

Una oferta vencida no puede publicarse hasta modificar **Válida hasta**. Si su tratamiento
se desactiva, la lista muestra ⚠ junto al tratamiento y la oferta no puede publicarse
hasta reactivarlo o cambiarlo.

**Aplicación al agendar:**

- La oferta solo se aplica si el paciente ingresa desde su tarjeta, pulsando **Agendar**:
  la agenda se abre con el tratamiento, la etiqueta (por ejemplo, «Oferta −20%»), un aviso
  de vigencia y la oferta en el resumen. Si agenda el mismo tratamiento por el camino
  habitual, no se aplica la oferta ni se consume un lugar.
- Si la fecha de la cita es posterior a la vigencia, el resumen advierte que la oferta ya
  no aplica.
- Cada cita con oferta ocupa un lugar; cancelarla o registrar una inasistencia lo libera.
  Sin lugares, la oferta deja de anunciarse.
- La cita guarda la oferta: la agenda muestra «Oferta −20% · título» y la campana indica
  «Con oferta «…»». **El sistema no cobra ni calcula totales; la doctora aplica el
  descuento al cobrar.**

### 11.2 Testimonios

- El paciente envía su testimonio desde la portada. Este llega como **pendiente** y la
  campana avisa al personal.
- La clínica no crea ni edita testimonios: solo los **aprueba** (se publican con el nombre
  y la inicial del apellido), los **rechaza** (permanecen en la lista como «Rechazado» y
  pueden aprobarse después) o los **elimina** de forma definitiva, por ejemplo, a
  solicitud del paciente.
- Los testimonios extensos muestran algunas líneas y el botón **Leer completo**. El
  buscador localiza por nombre del paciente o por el contenido del texto.

### 11.3 Galería, foto de inicio y casos

- **Galería:** las fotografías activas se muestran en la sección «El consultorio», en el
  orden definido.
- **Foto de inicio de la portada:** la fotografía principal predeterminada es de catálogo
  y no corresponde a la doctora ni al consultorio. En **Sitio web → Galería → Foto de
  inicio de la portada** puede cargarse una fotografía real (JPG, PNG o WebP, hasta 5 MB,
  en orientación horizontal), que se aplica de inmediato. **Quitar y usar la de fábrica**
  restablece la original.
- **Casos:** la primera fotografía cargada corresponde a «Antes» y la segunda a «Después».
  Solo se publican los casos activos con fotografía.

### 11.4 Preguntas frecuentes

En **Sitio web → Preguntas frecuentes** se registran preguntas y respuestas verificadas y
aprobadas por la clínica. Una pregunta solo se utiliza en la portada y en el asistente
virtual si está activa y visible. Las formas alternativas de formular la pregunta son
opcionales; no es necesario anticipar todas las palabras que podría utilizar un paciente.

> Antes de publicar, verifique que no permanezca ninguna pregunta de prueba.

## 12. Configuración

**Configuración** es una página exclusiva de la doctora titular con bloques
independientes, cada uno con su botón de guardado.

### 12.1 Datos del consultorio

Teléfono, WhatsApp de contacto («Es el mismo número que el teléfono» o uno distinto),
correo, dirección, nombre del negocio en Google Maps (opcional, tal como aparece en Maps),
misión y visión. Opcionales desde el 27/11/2026: enlaces de **Instagram, Facebook y TikTok** (deben ser el enlace completo
del perfil, por ejemplo `https://www.instagram.com/tu_consultorio`). Los cambios se reflejan de inmediato en la portada, el
pie de página, **¿Prefieres llamar?**, la página del recordatorio y la respuesta automática
de WhatsApp. Si el correo queda vacío, el pie de página no lo muestra. El guardado
solicita confirmación y queda registrado en el Control de actividades.

> El número desde el que se envían los recordatorios de WhatsApp no se modifica aquí; se
> administra en la plataforma de Meta.

### 12.2 Horarios de atención

Define los días y horas de apertura y el valor **Pacientes a la vez**, único para todos los
días, que indica cuántos pacientes pueden atenderse en un mismo horario (1 cuando existe un
solo sillón o un solo dentista; el consultorio utiliza 1). Los campos numéricos se
capturan con el teclado.

### 12.3 Días u horas bloqueados

Se utiliza para vacaciones, cursos, imprevistos o mantenimiento.

1. Indique **Desde** y, opcionalmente, **Hasta**.
2. Mantenga **Todo el día** o desmárquelo para bloquear solo un intervalo (**De** / **A**,
   dentro de un mismo día).
3. Capture el **motivo** (visible únicamente para el personal).
4. Pulse **Bloquear**.

**Resultado esperado:** esos horarios dejan de ofrecerse en la agenda en línea y en
**Nueva cita**, y los días completos aparecen cerrados en el calendario. Si existen citas
activas en el periodo, el sistema las enumera y deben reagendarse o cancelarse primero.
La sección **Próximos bloqueos** lista los bloqueos vigentes de cinco en cinco, con el
total junto al título y la paginación al pie; la 🗑 los elimina y los horarios vuelven a
ofrecerse. Todas las operaciones quedan registradas.

### 12.4 Días festivos

El sistema bloquea automáticamente los días en que el consultorio no labora: 1 de enero,
primer lunes de febrero, tercer lunes de marzo, 1 de mayo, 16 de septiembre, 2 de
noviembre, tercer lunes de noviembre, y 12, 24, 25 y 31 de diciembre, además del 1 de
octubre en los años de transmisión del Poder Ejecutivo Federal.

- Los festivos aparecen en **Próximos bloqueos** con el motivo «Festivo: …» y la nota
  «Festivo puesto por el sistema».
- Si la clínica decide laborar un festivo, puede quitarlo con la 🗑; el sistema no
  volverá a bloquearlo.
- Si un festivo ya tenía citas, no se bloquea y la campana indica las fechas afectadas
  para reagendar las citas o mantener el día abierto.

### 12.5 Cédulas profesionales

Muestra una tabla con una fila por cada doctor activo que atiende consultas (la doctora
titular primero), con sus iniciales, nombre, rol, el campo de la cédula y su estado:
**Capturada** (verde) o **Falta** (ámbar), que cambia al escribir. Al pie se indica cuántas
cédulas faltan. La cédula de cada doctor aparece en sus recetas y
consentimientos en PDF; no se publica en la portada. Solo se aceptan números, letras y
guiones (máximo 20 caracteres). El guardado solicita confirmación y cada cambio queda
registrado en el Control de actividades.

## 13. Cuentas del personal

En **Administración → Usuarios**, la doctora titular puede **crear** cuentas, **activarlas o
desactivarlas** (interruptor de la columna Estado) y **eliminarlas**. El listado puede
filtrarse por rol y estado. Los datos personales y la contraseña de cada cuenta los
actualiza su titular en **Mi perfil**.

**Crear una cuenta:**

1. Abra **Administración → Usuarios → Nuevo usuario**.
2. Capture los datos de la persona; el correo no puede repetirse.
3. Seleccione el **Rol** y revise la descripción de permisos que se muestra.
4. Defina una contraseña que cumpla la política del apartado 3.4.
5. Guarde.

**Reglas:**

- La eliminación solicita confirmación y no puede deshacerse. No es posible eliminar la
  cuenta propia ni una cuenta que haya registrado consultas clínicas, ya que ello
  eliminaría dichas consultas del historial de los pacientes; en ese caso, la 🗑 aparece
  deshabilitada con ⚠ y lo procedente es **desactivar** la cuenta.
- Para cambiar el rol de una persona: si la cuenta no tiene consultas, se elimina y se
  crea de nuevo con el rol correcto; si las tiene, se desactiva y se crea una cuenta nueva.

## 14. Reportes

En **Administración → Reportes**, seleccione las fechas **Desde** y **Hasta** y pulse
**Filtrar**.

| Indicador | Alcance |
|---|---|
| Citas por estado, pacientes nuevos, tratamientos más solicitados y consultas clínicas | Rango de fechas seleccionado. |
| Tasa de ocupación | Rango seleccionado. Proporción del horario de atención ocupada por citas, sin contar las canceladas (por ejemplo, «40 h con citas de 180 h de atención»). Los festivos y los días bloqueados completos no se consideran horario disponible. |
| Total histórico de pacientes y catálogo activo | Acumulado a la fecha. |

- El rango solo admite fechas hasta el día actual; el selector bloquea las fechas futuras
  y el sistema las rechaza si se envían manualmente.
- Si no existen citas completadas, algunos indicadores pueden mostrar cero.
- Los botones de descarga generan un **PDF** o un libro de **Excel** (`.xlsx`) con los
  indicadores y resúmenes, con etiquetas en español, compatible con Excel y LibreOffice
  Calc. Ambos indican el alcance de cada dato.

## 15. Control de actividades

Disponible para la doctora titular en **Configuración → Control de actividades**
(`/admin/control-de-actividades`; la dirección anterior `/admin/auditoria` redirige
automáticamente). Es una bitácora de solo lectura de los cambios realizados por el
personal.

- **Filtros:** fechas, usuario y acción.
- **Ver cambios:** muestra qué se hizo, quién y cuándo, y una tabla con cada dato
  modificado en sus valores **Antes** y **Después**, expresados en lenguaje natural (por
  ejemplo, «Estado: Confirmada → En curso»). En una creación solo se muestra el resultado y
  en una eliminación, el contenido previo.
- Cada cambio aparece una sola vez, con la hora de México y el nombre y rol de quien lo
  realizó.

| Se registra | No se registra |
|---|---|
| Cambios en citas, pacientes, historial clínico, usuarios, perfil, catálogo, contenido público, horarios, bloqueos, configuración y mensajes de contacto; impresión de recetas. | Contraseñas, contenido de las notas clínicas, valores de teléfono o correo de los pacientes (solo se indica qué campo cambió), consultas de información, descargas y acciones realizadas por los pacientes desde el portal o Telegram. |

## 16. Notificaciones internas

La campana del menú abre una vista rápida de los avisos sin abandonar la página. El
contador indica los avisos **nuevos** desde la última apertura, no los pendientes de
atender.

### 16.1 Vista rápida

- **Por atender:** avisos que requieren una acción (cancelación o solicitud de
  reagendamiento, inasistencia, respaldo fallido, testimonio por aprobar o festivo no
  bloqueado por tener citas). Cada uno incluye su botón (**Ver en la agenda**, **Revisar
  testimonios** o **Ver bloqueos**), además de **Llamar** y **Ya lo atendí**, que lo
  traslada a informativas. Si no hay pendientes, se muestra «No hay nada pendiente».
- **Informativas:** confirmaciones, citas nuevas y cambios, agrupadas y plegadas.
- **Ver todas las notificaciones** abre la bandeja completa.

Cada aviso muestra un círculo con las iniciales del paciente (cada paciente conserva su
color) y una insignia que indica el tipo de evento: calendario (cita nueva o cambio),
palomita (confirmación o cita completada), tache (cancelación o inasistencia) o flechas
(solicitud de reagendamiento). Los avisos del sistema, como los de respaldo, utilizan un
recuadro punteado.

### 16.2 Bandeja completa

- Pestañas **Todas**, **Pendientes** y **Leídas**; agrupación por **Hoy**, **Ayer** y
  **Anteriores**.
- Permite marcar avisos como leídos de forma individual o masiva, y devolver un aviso
  leído a pendiente.
- Al abrir la campana, los avisos se marcan como vistos y el contador vuelve a cero, pero
  conservan su estado pendiente hasta abrirse o marcarse como leídos.
- Al abrir un aviso, el sistema conduce a la cita relacionada.
- **Activar sonido** habilita una señal breve al recibir avisos y guarda la preferencia en
  el navegador.

### 16.3 Eventos notificados

Registro, reagendamiento, confirmación, cancelación, inicio y cierre de citas;
inasistencias; solicitudes del portal y de Telegram; respuestas al recordatorio
(**Respuesta del paciente**, **Cancelación del paciente**, **Horario liberado**); nuevos
testimonios; festivos no bloqueados; citas cerradas automáticamente; y, solo para la
doctora titular, **Respaldo fallido**.

Con la sesión abierta, los avisos aparecen al instante y el contador se actualiza sin
recargar la página. Si el canal en tiempo real se interrumpe, el sistema consulta
novedades cada pocos segundos. Los avisos son internos: no envían mensajes al paciente.

## 17. Asistentes virtuales

### 17.1 Asistente de agenda (personal)

> Módulo oculto de forma predeterminada (véase el apartado 2.2).

En `/asistente-agenda`, el personal selecciona los datos de la cita y el asistente
consulta la disponibilidad y propone horarios. No se guarda nada hasta pulsar
**Confirmar y agendar**. Se aceptan fechas a partir del día actual; las fechas pasadas se
rechazan.

### 17.2 Asistente para pacientes

El asistente flotante, identificado como **Dentissa Asistente**, se ubica en la esquina
inferior derecha de la portada, la agenda pública y las páginas del portal.

- Responde con las preguntas frecuentes activas y sus respuestas aprobadas, y con los
  servicios y tratamientos activos del catálogo.
- Consulta horarios: el paciente indica que busca una cita, elige servicio y tratamiento y
  proporciona una fecha en alguno de los formatos `hoy`, `mañana`, `DD/MM/AAAA`,
  `DD-MM-AAAA` o `AAAA-MM-DD`. El asistente muestra los horarios disponibles, pero la
  reserva se confirma en **Agendar cita**.
- Ante preguntas sobre el horario de atención, responde con el horario configurado; una
  pregunta frecuente aprobada sobre el tema tiene prioridad.
- Durante una búsqueda, la palabra **cancelar** (o **salir**) la termina. La búsqueda vence
  tras 15 minutos de inactividad.
- Ante preguntas sobre precios sin respuesta aprobada, síntomas, medicamentos o
  diagnósticos, solicita reformular la duda o contactar al consultorio.
- En el celular, el botón se oculta al desplazarse hacia abajo para no cubrir otros
  controles, y reaparece al desplazarse hacia arriba.

De manera opcional, puede habilitarse un modelo de inteligencia artificial privado que
reconoce otras formas de preguntar y elige la respuesta aprobada más adecuada. El modelo
no redacta respuestas: el sistema muestra siempre el texto aprobado. Si el servicio de IA
tarda en responder o no está disponible, se muestra un mensaje de respaldo.

> Antes de publicar cambios en las preguntas frecuentes, se recomienda probar preguntas
> directas, paráfrasis, errores ortográficos leves, temas sin información disponible y
> consultas clínicas.

### 17.3 Bot de Telegram

El bot de Telegram permite a los pacientes agendar y es independiente del asistente web.
Solo responde mientras su proceso se encuentra activo en el servidor. Si no responde, debe
utilizarse el portal web.

> El token del bot es una credencial privada: no debe compartirse ni solicitarse.

## 18. Mensajes de contacto

La sección **Administración → Mensajes** concentra los mensajes enviados desde el formulario de
contacto. Permite marcarlos como leídos o no leídos (sobre abierto / sobre cerrado) y
filtrarlos. Está disponible para la doctora titular y la asistente cuando el módulo está
habilitado; de forma predeterminada se encuentra oculto, ya que la portada actual no incluye
un formulario de contacto.

## 19. Respaldos de la información

El sistema realiza cada noche una copia de seguridad cifrada que se almacena en la carpeta
**Dentissa respaldos** del Google Drive de la clínica.

- La carpeta no debe moverse, renombrarse ni modificarse manualmente. Su contenido no es
  legible al abrirse en Drive, lo cual es normal por estar cifrado.
- Si la copia no se completa, la doctora titular recibe el aviso **Respaldo fallido** en
  la campana y por correo. **Este aviso debe atenderse el mismo día**, notificando al
  equipo técnico, ya que mientras persista la falla no existe una copia reciente de
  citas ni historiales.
- Una vez al mes, el equipo técnico verifica que la copia más reciente pueda recuperarse,
  sin afectar los datos en uso.

## 20. Módulo de pagos e ingresos (opcional)

> Este módulo está deshabilitado por decisión de la clínica: no aparece en el menú, en el
> expediente ni en la agenda. Lo siguiente describe su funcionamiento en caso de que el
> equipo técnico lo habilite.

El módulo registra los pagos realizados en el consultorio. No procesa pagos en línea ni
emite facturas.

- **Registrar un pago:** desde la agenda, una cita completada sin pago muestra **$
  Registrar pago**, con la cita y su precio estimado como monto sugerido. También puede
  registrarse desde el expediente (**Pagos → Registrar pago**), por ejemplo, para un
  anticipo o un abono sin cita. Se indica monto, forma de pago (efectivo, tarjeta o
  transferencia), fecha (actual o anterior) y, opcionalmente, la cita y un concepto.
- **Expediente:** muestra el total pagado y, si existen citas completadas con precio, el
  saldo **por cobrar (estimado)**: suma del precio de esas citas, con su oferta, menos lo
  pagado. Es una referencia; lo que prevalece es lo registrado.
- **Administración → Pagos e ingresos:** total cobrado del periodo (de forma predeterminada, el
  mes en curso), desglose por forma de pago y lista de pagos, con filtros **Desde**,
  **Hasta** y **Forma de pago**. Un pago capturado por error se elimina con la 🗑.
- El registro y la eliminación de pagos quedan en el Control de actividades.

## 21. Solución de problemas frecuentes

| Situación | Causa probable | Acción recomendada |
|---|---|---|
| No llega el correo de recuperación | Correo distinto al registrado o filtro de correo no deseado. | Revisar la carpeta de correo no deseado y solicitar un nuevo enlace después de unos minutos. |
| El código de verificación es rechazado | Hora del celular desincronizada o código vencido. | Configurar la hora del celular en automático y capturar un código nuevo. |
| La sesión se cerró sola | Inactividad de 30 minutos o cuenta desactivada. | Iniciar sesión nuevamente o consultar a la doctora titular. |
| No aparece una sección del menú | El perfil no tiene permiso para ese módulo. | Consultar la tabla del apartado 2.1. |
| Una especialidad indica «Agenda por teléfono» | No tiene tratamientos activos. | Activar al menos un tratamiento con precio en el catálogo. |
| No es posible activar un tratamiento | No tiene precio. | Capturar el precio y activarlo. |
| No aparece **Iniciar** en una cita | No es el día de la cita, ya concluyó su horario o existe otra cita en curso. | Completar la cita en curso o utilizar **Atendida** / **No asistió**. |
| Una oferta publicada no se ve en la portada | Venció, su tratamiento está inactivo, se agotaron los lugares o excede el límite del carrusel. | Consultar el símbolo ⚠ de la oferta. |
| La receta no muestra la cédula, o no se genera | Falta la cédula profesional (si el sistema está configurado para exigirla, no se genera). | La doctora titular la captura en **Configuración → Cédulas profesionales**. |
| No aparece Asistente, Mensajes, Galería, Casos o la verificación en dos pasos | El módulo está oculto. | Solicitar su habilitación al equipo técnico (apartado 2.2). |
| No es posible eliminar un usuario | Es la cuenta propia o registró consultas. | Desactivar la cuenta. |
| El bot de Telegram no responde | El proceso del bot no está activo. | Utilizar el portal web y notificar al equipo técnico. |
| Aviso **Respaldo fallido** | Falla en la copia nocturna. | Notificar al equipo técnico el mismo día. |

## 22. Uso en la demostración

Para la presentación del proyecto se utiliza el entorno en línea con datos ficticios.

- La doctora titular y la asistente utilizan cuentas de demostración independientes,
  preparadas por el equipo técnico mediante variables privadas del servidor. Las
  credenciales no se incluyen en este manual ni se reutilizan las de la instalación local.
- Los nombres, teléfonos y correos de los pacientes son ficticios. El paciente precargado
  «Paciente Demostración» no tiene historial clínico. El recorrido público puede realizarse
  agendando una cita con datos completamente ficticios.
- Mientras el modo de demostración esté activo, los datos de contacto se identifican como
  ficticios y los números, correos y enlaces de Telegram no son accionables.
- Al concluir la exposición, el equipo técnico debe apagar o eliminar el servicio en línea.

### 22.1 Lista de verificación antes de operar con pacientes reales

1. Desactivar el modo de demostración y capturar los datos reales del consultorio.
2. Habilitar el módulo de verificación en dos pasos y su obligatoriedad para la doctora
   titular.
3. Completar los datos del responsable en el aviso de privacidad y obtener su revisión
   legal.
4. Confirmar los precios de todos los tratamientos.
5. Sustituir la fotografía de inicio y revisar galería, casos, testimonios y preguntas
   frecuentes, eliminando cualquier contenido de prueba.
6. Verificar que el respaldo nocturno se ejecute correctamente.
