# Manual técnico de Dentissa

## 1. Propósito

Dentissa es un sistema web para la gestión de una clínica dental. Permite
administrar pacientes, servicios, tratamientos, citas, atención oportuna,
historial clínico, notificaciones y reportes.

Este manual describe cómo levantar el proyecto, ejecutar las pruebas y
mantener el entorno de desarrollo.

## 2. Stack tecnológico

- Laravel 13 con PHP 8.3.
- Blade y Alpine.js para las vistas interactivas.
- Tailwind CSS y Vite para los estilos y assets.
- MySQL 8.4 para la aplicación local en Docker; SQLite en memoria como motor
  predeterminado de la suite rápida de pruebas.
- Docker Compose para ejecutar la aplicación, compatible con Docker Desktop o
  Docker Engine dentro de WSL.
- Git y GitHub para control de versiones.
- PHPUnit/Pest mediante `php artisan test`.

Las tipografías Figtree y Alex Brush se alojan en `public/fonts` y se precargan
desde el mismo sitio. Así la interfaz no depende de un proveedor externo de
fuentes y reduce el cambio visible de tipografía durante la carga. `server.php`
permite al servidor integrado de PHP asignar caché a los recursos estáticos; las
fuentes y scripts tienen una duración larga y las imágenes se conservan un día.
La portada también precarga su imagen principal para evitar que llegue después
del texto.

En Windows el equipo puede usar Docker Desktop con la integración de WSL2
activada. También es válido ejecutar Docker Engine directamente dentro de WSL;
los comandos de `docker compose` son los mismos.



La portada pública precarga su imagen principal y utiliza tipografías Figtree y Alex Brush servidas desde `public/fonts`, para no depender de un proveedor externo. Las imágenes del hero y servicios están configuradas en `config/dentissa.php`. El bloque de contacto se controla mediante `DENTISSA_MODO_DEMO`; debe permanecer activo mientras los valores sean ficticios y no debe abrirse el sitio a pacientes hasta configurar datos reales.

## 3. Arranque del proyecto

Desde PowerShell o Windows Terminal:

```powershell
Set-Location C:\laragon\www\dentissa
Copy-Item .env.example .env # solo la primera vez
docker compose build app # solo la primera vez o al cambiar Dockerfile/dependencias
docker compose run --rm app php artisan key:generate --force # solo la primera vez
docker compose up -d
docker compose ps
```

Desde WSL:

```bash
cd /mnt/c/laragon/www/dentissa
cp .env.example .env # solo la primera vez
docker compose build app # solo la primera vez o al cambiar Dockerfile/dependencias
docker compose run --rm app php artisan key:generate --force # solo la primera vez
docker compose up -d
docker compose ps
```

La aplicación queda disponible en <http://localhost:8000>.

### Actualización automática durante desarrollo local

Para evitar que el contenedor sirva una compilación anterior después de cambiar
de rama o editar archivos, inicia el servicio con Compose Watch:

```powershell
docker compose up --build --watch app
```

Mantén la terminal abierta. Compose sincroniza al contenedor cambios de PHP,
rutas, configuración y vistas. Los cambios de CSS, JavaScript, dependencias o
configuración de Vite/Tailwind disparan una reconstrucción de la imagen, que
vuelve a generar `public/build/manifest.json` y sus archivos versionados. La
primera reconstrucción puede tardar; las siguientes aprovechan las capas de
Docker. `Ctrl+C` detiene el modo de observación; los datos permanecen en el
volumen MySQL y `storage/`.

Si no se desea dejar una terminal observando cambios, puede iniciarse el modo
normal; después de cambiar de rama o código se debe reconstruir manualmente:

```powershell
docker compose up -d --build app
```

Para reconstruir la imagen cuando cambian el `Dockerfile`, los seeders o los
archivos que se copian durante la construcción:

```bash
docker compose build app
docker compose up -d --force-recreate app
```

## 4. Variables de entorno y datos sensibles

`.env` se usa dentro del contenedor, pero está excluido de Git mediante
`.gitignore` y `.dockerignore`. En la primera instalación se copia desde
`.env.example` y se genera `APP_KEY` dentro del contenedor. Nunca se deben subir contraseñas, tokens de
WhatsApp, claves de API ni otros secretos al repositorio.

El archivo `.env.example` sirve como plantilla. Cada desarrollador debe crear
su propio `.env` local.

## 5. Datos demo reproducibles

Los seeders crean usuarios, un paciente, servicios, tratamientos y sus
relaciones. Son repetibles: ejecutarlos no duplica los registros conocidos.

```bash
docker compose exec app php artisan db:seed
```

No usar `migrate:fresh` para una base de datos de desarrollo que contenga
información que se quiera conservar, porque elimina todas las tablas.

MySQL conserva los datos en el volumen Docker `mysql_data`; los archivos subidos
se conservan en `storage/`. El código de la aplicación se copia dentro de la
imagen, por eso se debe reconstruir después de cambiar archivos de código que
no estén montados como volumen.

La persistencia se verificó reiniciando MySQL: el conteo de usuarios demo fue
2 antes y después. Durante el arranque MySQL puede tardar unos segundos en
volver a aceptar conexiones; espera a que `docker compose ps` muestre `healthy`
antes de consultar la base. `docker compose restart mysql` conserva los datos;
`docker compose down -v` elimina el volumen y no debe usarse si se quieren
conservar.

Al iniciar, el contenedor crea las carpetas de caché de Laravel dentro de
`storage/` y `bootstrap/cache`. Esto es necesario porque el volumen del host
puede comenzar vacío en una instalación nueva.

## 6. Pruebas automatizadas

Ejecutar la suite completa:

```bash
docker compose exec app php artisan test
```

Las pruebas usan SQLite en memoria o el schema aislado `dentissa_test`; no
deben modificar la base normal `dentissa`. La suite cubre reglas de catálogo,
fechas futuras, ciclo de vida y concurrencia de citas, agenda pública,
atención oportuna, permisos, historial clínico, auditoría, notificaciones
internas y asistente. El 16/10/2026 se reconstruyó el servicio `app` con el
código vigente y pasaron **143 pruebas/701 aserciones en SQLite** (1 omisión
esperada porque SQLite no valida de forma representativa `lockForUpdate`) y
**144 pruebas/706 aserciones en MySQL 8.4**, incluida la prueba concurrente.
Ambas ejecuciones usaron el entorno aislado de pruebas; la base operativa local
no fue limpiada ni modificada por esta validación.

La validación de estilo PHP también pasó con Pint para los 183 archivos
revisados. Después de cualquier cambio de lógica, reconstruir el servicio app y
repetir al menos la suite afectada antes de actualizar la matriz QA.

La creación y reagenda bloquean, dentro de su transacción, la fila de
configuración del día de semana antes de revisar las citas existentes. Esto
proporciona un registro común de exclusión aun cuando todavía no haya citas;
la consecuencia es serializar brevemente reservas que caigan en cualquier
fecha del mismo día de semana. QA-24 valida el límite secuencial; QA-34
comprobó con dos procesos PHP concurrentes en MySQL 8.4 que solo una reserva
obtiene el último cupo (1 prueba/5 aserciones, 12/10/2026).

La suite puede correrse también contra el schema MySQL desechable del entorno
Docker. El contenedor utiliza MySQL 8.4 para la aplicación y conserva SQLite
solo como opción aislada de prueba. Para elegir MySQL en la suite:

```bash
docker compose exec -e DENTISSA_TEST_DB_CONNECTION=mysql -e DENTISSA_TEST_DB_DATABASE=dentissa_test app php artisan test
```

La inicialización de un volumen MySQL nuevo crea `dentissa_test` separado de
`dentissa`; `RefreshDatabase` puede reconstruir el schema de pruebas sin tocar
los datos demo de la aplicación. GitHub Actions también ejecuta suites para
SQLite y MySQL en cambios enviados y pull requests; consulta **Actions** en
GitHub para revisar el resultado del commit actual.

La prueba concurrente se ejecuta por separado porque requiere dos procesos PHP
y MySQL real:

```bash
docker compose run --build --rm --no-deps -e DENTISSA_TEST_DB_CONNECTION=mysql -e DENTISSA_TEST_DB_DATABASE=dentissa_test app php artisan test --compact --filter=ConcurrenciaCitasMySQLTest
```

El caso crea fixtures en el schema desechable, libera dos procesos al mismo
tiempo y espera que exactamente uno guarde la cita. El 12/10/2026 aprobó con
1 prueba/5 aserciones en MySQL 8.4. Con SQLite se marca como omitido; no usar la
base `dentissa` para esta prueba.

Ejecutar una prueba específica:

```bash
docker compose exec app php artisan test --filter=CitaPublicaTest
```

GitHub Actions ejecuta la suite al subir commits a cualquier rama y al abrir o
actualizar un pull request hacia `master`. El job SQLite valida la suite
general; `test-mysql` usa MySQL 8.4 y ejecuta también QA-34. La ejecución #1
completó ambos jobs correctamente el 12/10/2026 ([ver ejecución](https://github.com/QuetzalTwT119/dentissa/actions/runs/38339542421)).
Revisa ambos jobs en la pestaña **Actions** antes de integrar un pull request.

## 7. Flujo Git recomendado

1. Actualizar `master`.
2. Crear una rama descriptiva (`feature/...`, `fix/...` o `docs/...`).
3. Hacer cambios pequeños y comprensibles.
4. Ejecutar las pruebas.
5. Crear un commit con mensaje claro.
6. Hacer `git push -u origin nombre-de-rama`.
7. Crear un Pull Request hacia `master`.
8. Fusionar y volver a sincronizar `master` local.

## 8. Capas principales

- **Rutas:** definen URLs públicas, autenticadas y administrativas.
- **Controladores:** validan la entrada y coordinan el caso de uso.
- **Modelos:** representan usuarios, pacientes, servicios, tratamientos,
  citas e historial.
- **Servicios:** contienen reglas reutilizables como disponibilidad,
  notificaciones y atención oportuna.
- **Vistas Blade/Alpine:** muestran formularios, estados y acciones.
- **Base de datos:** conserva los datos mediante migraciones y MySQL; las
  pruebas pueden usar SQLite en memoria o un schema MySQL aislado.

La lista administrativa de pacientes filtra por fragmentos del nombre,
apellido, correo o teléfono. El campo tiene una etiqueta accesible y se adapta
al ancho disponible; la tabla mantiene su ancho legible y permite
desplazamiento horizontal en pantallas estrechas.

La edición administrativa permite actualizar nombre, apellido, teléfono, correo,
identificadores oficiales y tutor/responsable. Nombre y apellido son obligatorios;
correo es opcional y único entre pacientes. El teléfono opcional valida diez
dígitos y admite separadores visuales y el prefijo mexicano `+52`. El registro
de auditoría conserva solo los nombres de los campos modificados, no los valores
de correo o teléfono.

## 9. Regla de disponibilidad

`DisponibilidadService` consulta el horario de atención, la duración del
tratamiento, el margen entre citas, la capacidad simultánea y las citas ya
registradas. Antes de guardar una cita vuelve a validar el horario dentro de
una transacción para evitar reservas duplicadas.

La zona horaria de la aplicación se configura con `APP_TIMEZONE` y por defecto
es `America/Mexico_City`. Debe corresponder a la ubicación de la clínica:
horarios configurados como 9:00–14:00 se interpretan como hora local, no UTC.

`CitaReglasService` centraliza las validaciones compartidas por los flujos
interno, público y asistente: catálogo activo, relación servicio-tratamiento y
fecha/hora futura. Esto evita que un formulario aplique reglas distintas a
otro.

La propuesta del asistente de agenda valida además que la fecha seleccionada
sea hoy o posterior en el servidor. El atributo `min` de la interfaz mejora la
experiencia, pero no sustituye esta validación de la solicitud.

El filtro de `/citas` es de consulta: acepta cualquier fecha válida, incluidas
fechas futuras, y puede combinarse con el estado. No debe heredar el límite de
fecha futura que corresponde a crear o reagendar citas.

La acción `citas.no-show` solo permite marcar una cita confirmada como
**No asistió** cuando llega su `fecha_hora` programada o después. La validación
se realiza en el servidor, por lo que también protege solicitudes directas que
no provienen de la interfaz.

La vista de agenda muestra **Más acciones** solo para citas `agendada` y
`confirmada`, estados en los que se permite reagendar o cancelar. Una cita
`en_curso` ofrece únicamente completar; las citas `completada`, `cancelada` o
`no_asistio` se presentan sin acciones. La vista y las reglas del servidor se
validan por separado para que un control oculto no sea la única barrera.

## 10. Respuestas del asistente de pacientes

`AsistentePacienteService` responde primero con coincidencias seguras sobre FAQs
activas. Normaliza mayúsculas, acentos y puntuación, tolera errores tipográficos
leves y usa el fallback ante resultados débiles o ambiguos. Las variantes por
línea siguen disponibles para formulaciones probadas, pero no son requisito para
cada forma de preguntar.

Como prototipo local opcional, `AsistenteIaLocalService` puede entender otras
formas de preguntar usando FAQs activas y el catálogo activo de
servicios/tratamientos como fuentes. Por eso, nombres ya publicados en el
catálogo no requieren duplicarse como preguntas frecuentes. El modelo solo
devuelve el ID de una fuente exacta; Laravel responde con el contenido original
de esa FAQ o catálogo. El texto generado por el modelo nunca se presenta al
paciente. Para preguntas de precio, solo se consideran FAQs que mencionan
explícitamente el precio; las consultas sobre síntomas, diagnóstico o consejo
sobre medicamentos no se envían al modelo. Si Ollama no está disponible, no hay fuente
elegible o la consulta no tiene respaldo, se conserva el fallback. La consulta
del paciente no se escribe en los logs.

Antes de llamar a Ollama, el servicio filtra fuentes que no compartan con la
consulta al menos un término distintivo, ignorando palabras genéricas y
tolerando errores tipográficos leves. También valida esa relación al recibir
el ID seleccionado; una fuente fuera del conjunto elegible se rechaza. Este
control evita, por ejemplo, que una pregunta sobre el color favorito de la
doctora se responda con una fuente de odontopediatría. Como el filtro no
comprende todos los sinónimos, puede ser conservador y devolver el fallback
cuando la pregunta y la fuente usan palabras distintas; el paciente puede
reformular su duda. No sustituye una evaluación más amplia antes de producción.

La integración usa Ollama local con el modelo `granite3.3:2b` (1.5 GB de
descarga aproximada, licencia Apache 2.0 y soporte declarado para español). No
se arranca por defecto: la variable `DENTISSA_ASISTENTE_IA_HABILITADA` vale
`false`, y el servicio Docker pertenece al perfil `ia`. Para probarlo en el
entorno local:

1. En el `.env` local, establecer `DENTISSA_ASISTENTE_IA_HABILITADA=true`.
2. Ejecutar `docker compose --profile ia up -d --build app mysql ollama`.
3. Descargar el modelo con `docker compose exec ollama ollama pull granite3.3:2b`.
4. Confirmar que aparece con `docker compose exec ollama ollama list`.
5. Probar el chat con contenido aprobado. Las FAQs ficticias de QA no son
   información real y deben desactivarse o reemplazarse antes de publicar.

La primera consulta puede tardar más porque Ollama debe cargar el modelo en
memoria. `DENTISSA_OLLAMA_TIMEOUT` permite ajustar el límite de espera; el
valor predeterminado es 60 segundos para cubrir ese arranque en equipos de
desarrollo. Si se supera el límite, el chat usa la respuesta de respaldo.

El modelo se guarda en el volumen Docker `ollama_data`, no en Git. Esta
integración es un prototipo de desarrollo: un modelo ejecutándose en la PC no
queda disponible automáticamente para una aplicación desplegada en Railway. La
aplicación en producción debe mantener la IA apagada hasta decidir y validar un
alojamiento privado adecuado. No configurar una URL pública de inferencia sin
revisar antes privacidad, consentimiento y retención de datos.

Las pruebas automatizadas fuerzan la IA apagada en `phpunit.xml` y en
`tests/TestCase.php`, incluso si el `.env` local la tiene encendida. Las pruebas
de `AsistenteIaLocalTest` la habilitan explícitamente y simulan las respuestas
HTTP de Ollama. Así la suite no depende de que el modelo esté descargado o
disponible y no envía consultas de prueba a un servicio real.

## 11. Atención oportuna e historial

Las solicitudes urgentes y de lista de espera se conservan en la base de
datos. El panel administrativo muestra por separado los pendientes y el
historial de seguimiento, con filtros de estado y fechas. La transición a
**Contactada** o **Cerrada** no elimina datos.

Las notificaciones de cita guardan también `id_cita`, lo que permite enlazar
un aviso con la cita que lo originó sin depender únicamente del texto del
mensaje.

La bandeja del personal se almacena aparte en `notificaciones_sistema`, para
no mezclar avisos internos con entregas externas registradas en `notificaciones`.
`NotificacionInternaService` genera avisos por altas y transiciones de citas,
incluyendo solicitudes públicas y de Telegram; las cuentas activas de admin y
asistente los reciben, excepto la cuenta que realizó el cambio interno. Cada
cuenta solo consulta y marca sus propios avisos. La bandeja está protegida por
`auth` y `role:admin,asistente`; filtra por estado conservando el usuario
propietario y pagina los resultados. Las transiciones de lectura verifican
también que el aviso pertenezca a la cuenta autenticada. Su contador se muestra
en navegación de escritorio y móvil. La campana ofrece una vista previa de hasta
cinco avisos pendientes de la propia cuenta, y las nuevas notificaciones se
insertan en esa lista en vivo. Una falla al persistir una notificación no
revierte la cita y queda registrada en el log de aplicación.

`NotificacionSistemaCreada` transmite cada aviso inmediatamente mediante
Laravel Reverb y Echo, por el canal privado `users.{id}`. La autorización del
canal exige que la sesión pertenezca a la misma cuenta activa de admin o
asistente; el evento solo transmite título, mensaje, vínculo y contador, nunca
datos clínicos. Mientras la conexión WebSocket no está disponible, el
navegador usa como respaldo `notificaciones.actualizaciones` cada 3 segundos;
el endpoint filtra por la cuenta autenticada y no almacena la respuesta en
caché. Docker inicia Reverb como servicio en el puerto 8080, sin costo de
proveedor externo ni cola adicional. El sonido opcional se genera mediante Web
Audio API, sin archivos ni servicios externos, y su preferencia se almacena en
`localStorage` separada por usuario. El control se muestra en la bandeja de
notificaciones, no en la barra de navegación. La interacción con **Activar sonido**
desbloquea el audio respetando las políticas del navegador; fallar al reproducir
el sonido nunca impide mostrar o guardar la notificación.

En los formularios del historial, fecha, tipo y motivo de consulta se señalan
como obligatorios en la interfaz y se validan también en el servidor. El selector
de tipo incluye el atributo HTML `required`. Diagnóstico, tratamiento,
medicamentos, observaciones, notas privadas y cita relacionada se identifican
como opcionales; la cita relacionada solo se muestra si hay citas disponibles.

## 12. Auditoría de operaciones sensibles

`AuditoriaService` centraliza el registro de acciones relevantes del personal
en `logs_auditoria`. Solo registra solicitudes autenticadas de usuarios con rol
`admin` o `asistente`; las altas de citas provenientes del portal público o de
Telegram no se consideran cambios del personal y no se agregan a esta bitácora.
Dentro de ese alcance se auditan las altas y transiciones internas de citas,
los cambios del historial clínico, los adjuntos clínicos, los datos de contacto
u oficiales del paciente y el seguimiento de atención oportuna. En la edición
del paciente, los metadatos de auditoría enumeran campos modificados sin copiar
correo ni teléfono. También se registran altas,
ediciones, activaciones y bajas de cuentas; actualizaciones de perfil y
contraseña (sin almacenar la contraseña); cambios de servicios, tratamientos,
ofertas, testimonios, galería, casos, preguntas frecuentes, horarios y estado o
eliminación de mensajes de contacto. Los cambios de relaciones servicio-
tratamiento guardan los identificadores afectados. El alta pública de una cita,
las solicitudes de atención iniciadas por pacientes y las operaciones de
Telegram no se atribuyen a una cuenta del personal y quedan fuera de la bitácora.

Los adjuntos del historial se sirven mediante una ruta autenticada que permite
su lectura solo al rol `admin` y rechaza registros de imagen que no pertenezcan
al historial clínico. Las pruebas `HistorialImagenAccessTest` cubren el acceso
del administrador, el rechazo al asistente y a visitantes, y el tipo de imagen.

Cada entrada conserva el usuario autenticado, acción, tabla, registro, fecha,
IP y agente de usuario, además de una copia del ID, nombre y rol del actor. La
copia del actor permite conservar la identidad si la cuenta se elimina más
adelante. Para proteger la privacidad, los cambios de historial
guardan metadatos —por ejemplo paciente, cita, fecha y tipo de consulta—, pero
no copian diagnóstico, medicamentos ni notas clínicas.

`AuditoriaService::registrar` exige una transacción activa y que el modelo de
negocio y la bitácora usen la misma conexión. `registrarCambio` envuelve las
mutaciones administrativas simples y su entrada de auditoría en una misma
transacción; para entidades con varios registros, los controladores usan una
transacción explícita. Esto evita que una llamada escriba una auditoría que no
pueda revertirse junto con el cambio. Las escrituras de negocio y su entrada de
auditoría se ejecutan en una misma transacción de base de datos.
Si la bitácora no puede guardarse, la excepción
revierte también el cambio de negocio y la aplicación muestra una respuesta
503 indicando que no se guardaron los cambios y que se intente de nuevo más
tarde. Las notificaciones externas se envían después de confirmar la
transacción; un fallo al enviar un aviso no deshace una cita válida.

La ruta administrativa `admin.auditoria.index` ofrece una consulta paginada y
de solo lectura, protegida por `auth` y `role:admin`. Acepta filtros validados
por fecha, usuario y acción, y excluye de la vista los eventos sin ID de usuario
ni rol del personal. La pantalla no expone IP ni agente de usuario y presenta únicamente
los metadatos definidos por los emisores de auditoría. Las entradas históricas
de canales externos creadas antes de limitar el alcance permanecen en la base,
pero no se muestran en esta vista.

## 13. Autorización por rol

Las rutas autenticadas de agenda usan `auth`; las rutas bajo `/admin` añaden el
middleware `role:admin`. El endpoint de PDF del historial vuelve a la ficha con
un aviso cuando el historial aún no tiene consultas; las notas `notas_doctor`
no se renderizan en ese PDF. `CitaPolicy` permite las operaciones internas de cita
al personal con rol `admin` o `asistente`, mientras que los adjuntos del
historial clínico están reservados a `admin`. La prueba
`PermisosPorRolTest` verifica que el asistente reciba 403 en 12 páginas
administrativas y al intentar modificar horarios, y que un visitante sea
redirigido al login. Las 3 pruebas (15 aserciones) y la suite completa pasaron
en SQLite en memoria y en MySQL 8.4 con el schema aislado `dentissa_test`.

## 14. Respaldos y recuperación

La automatización cifrada de respaldos y restauración sigue pendiente. El archivo
SQL existente en `storage/backups/` es una copia puntual de trabajo, no un
procedimiento verificado ni una política de retención. No debe considerarse un
respaldo operativo. Antes de usar datos reales hay que definir e implementar
cifrado, retención, cobertura conjunta de MySQL y `storage/app`, verificación de
integridad y recuperación ensayada en un destino aislado. No restaurar sobre la
base activa durante QA.

## 15. Widget público en móvil y acceso por LAN

El widget crea un UUID de conversación para que el servidor pueda relacionar
mensajes. `crypto.randomUUID()` solo está disponible en contextos seguros en
algunos navegadores; una dirección local por IP y HTTP, como
`http://192.168.0.80:8000`, puede no ser considerada segura. El componente usa
esa API cuando existe y genera un UUID v4 compatible como alternativa. El
almacenamiento local también está protegido con manejo de errores para que una
restricción del navegador no deje el widget sin inicializar.

El acceso se posiciona fijo respecto a la ventana también en móvil: una cápsula
horizontal compacta con ícono dental y nombre, pegada visualmente al panel del
chat que se despliega encima. En las vistas públicas, un `IntersectionObserver`
observa la franja ocupada por el botón (con margen inferior compacto y distinto
en móvil y escritorio) y lo eleva temporalmente si una tarjeta, campo o acción
importante la cruza; permanece visible y regresa a su posición al dejar libre
esa zona. El flujo de agenda usa
dos columnas desde `lg`: formulario y resumen con progreso compacto/reactivo. En
el primer paso, elegir un servicio reemplaza esa lista por sus tratamientos y
el recuadro del servicio seleccionado funciona como control para deseleccionarlo
y volver a la lista, evitando un botón adicional y alargar el formulario. En móvil
el resumen y sus accesos alternativos se apilan después del formulario; el panel
lateral reúne progreso, selección, urgencia y lista de espera. La prueba
`AsistenteVistasPublicasTest` valida el resumen y la cuadrícula responsiva.
La agenda reserva más espacio inferior en móvil para el botón flotante y solo
un margen reducido en escritorio, evitando desbordamiento vertical cuando el
flujo cabe en la ventana. El contenido más alto conserva el desplazamiento
normal. La aceptación visual final requiere recargar `/agendar-cita` en el
teléfono conectado a la misma red.

## 16. Preparación local para Railway

Railway es el destino elegido para la demostración escolar. `Dockerfile.local`
mantiene el entorno local de desarrollo/QA que utiliza `compose.yaml`; se
separa del nombre `Dockerfile` para que Railway use Railpack y el runtime
Laravel con PHP-FPM/Caddy. Railway no ejecuta el Compose local como un solo
despliegue: la aplicación y MySQL serán servicios separados.

Validación local después de cambiar esta separación:

```bash
docker compose config
docker compose up -d --build app
docker compose exec app php artisan test
```

Al configurar Railway se deberán definir las variables de producción en su
panel (nunca en Git), validar la compilación Vite, ejecutar migraciones de
forma controlada, mantener MySQL privado y usar `/up` como healthcheck. La
demostración se limita a datos ficticios; Ollama, WhatsApp y Telegram no forman
parte de la configuración web por defecto. Consultar
`docs/DESPLIEGUE-RAILWAY.md` para el orden y los pendientes.

El 16/10/2026 se reconstruyeron desde el código vigente los servicios locales
`app`, `reverb` y `telegram`. Verificación: `/up` respondió HTTP 200, los puertos
8000 de la app y 8080 de Reverb aceptaron conexiones, y MySQL informó estado
saludable. Esto confirma el arranque local, no una entrega de notificación
validada desde un navegador ni el despliegue en Railway. Se ejecutó la suite
completa en SQLite (143 pruebas aprobadas, 701 aserciones, 1 omisión esperada)
y MySQL 8.4 aislado (144 pruebas, 706 aserciones); Pint validó 183 archivos PHP.

El seeder general `DatabaseSeeder` conserva credenciales fijas de desarrollo y
no debe usarse en Railway. `DemoPresentacionSeeder` crea, de forma repetible,
cuentas separadas para admin y asistente, el catálogo de demostración y un
paciente ficticio sin datos clínicos. Exige una bandera explícita y dos
contraseñas configuradas fuera del repositorio. Se ejecuta manualmente una vez
desde la Shell del servicio; al terminar se deshabilita la bandera y se quitan
las variables de contraseña.

## 17. Exportación de reportes

La exportación administrativa de Excel genera un libro `.xlsx` real usando
PhpSpreadsheet, una dependencia PHP de código abierto con licencia MIT. El
reporte conserva valores numéricos como números y los textos como texto para
evitar que contenido del catálogo se interprete como fórmula. Se organiza en
una hoja con título, período, indicadores y resúmenes. El PDF se genera por
separado con DomPDF.

La prueba `ReportePresentacionTest` valida la etiqueta del botón, el tipo MIME,
la firma ZIP propia de XLSX y vuelve a abrir el archivo para comprobar etiquetas
con acentos y valores numéricos. Después de cambiar el código, reconstruir y
recrear el servicio local:

```powershell
docker compose build app
docker compose up -d --no-deps app
docker compose run --rm -T --no-deps --entrypoint php app artisan test tests/Feature/ReportePresentacionTest.php
```

## 18. Base responsiva

Las vistas invitadas de autenticación comparten un layout responsivo de marca,
con fotografía clínica, identidad visual y formulario; en escritorio presenta
dos zonas y en móvil las apila. El login conserva las rutas, CSRF, autenticación, recuperación
de contraseña y `remember`; `AuthenticationTest` valida el render, destino y
presencia de estos controles.

La navegación interna comparte la identidad visual de Dentissa en local y
producción: marca, accesos a Inicio/Agenda/Asistente y grupos por función para
administración. El menú horizontal se muestra desde `2xl`; en pantallas menores
se usa el menú compacto para evitar comprimir enlaces. La lista móvil mantiene
las rutas administrativas, incluida Auditoría. El botón compacto expone etiqueta
accesible, estado abierto/cerrado y relación con su panel. La vista previa de
notificaciones se adapta a móvil/escritorio y comparte el estado entre ambas
presentaciones. Las tablas

El panel de administración reúne accesos rápidos para agenda, urgencias,
pacientes, servicios, contenido y usuarios; el panel de asistente mantiene sus
accesos a citas y al asistente de agenda. El layout carga el manifiesto PWA y
sus iconos; `public/sw.js` registra un caché básico y presenta `offline.html`
cuando no hay conexión. `DashboardVisualTest` y `PwaArchivosPublicosTest`
validan estas piezas.

administrativas anchas se envuelven en contenedores `overflow-x-auto` con un
ancho mínimo por contenido, para mantener legibles columnas y acciones. Los
formularios que agrupan campos relacionados usan una columna en móvil y dos
desde `sm`. Las vistas de cada módulo deben seguir validándose por separado;
esta base no sustituye la revisión responsiva de tarjetas y flujos públicos.
El scrollbar del documento se oculta globalmente con `scrollbar-width: none` y
  `::-webkit-scrollbar { display: none }`; no se aplica `overflow: hidden`, así que
  el desplazamiento existente permanece operativo. En la vista de urgencias, el
  asistente vuelve a comprobar los campos visibles al desplazarse y busca una
  posición libre iterativamente para evitar cubrir varios controles seguidos. Esta
  vista usa una cuadrícula informativa/formulario de dos columnas desde `lg`,
  aprovecha el ancho disponible sin reservar una columna vacía para el widget;
  el observador lo desplaza al detectar campos visibles. El acceso compartido se
  representa como una cápsula horizontal compacta con el ícono dental y el
  nombre en una sola línea, tanto en móvil como en escritorio; el panel abre
  justo encima con espaciado reducido. El botón de envío se limita en ancho. En móvil el contenido se apila,
  reduce la proporción vertical de la imagen y compacta el espaciado, usa una sola
  columna para señales y mantiene controles táctiles de al
menos 44 px; `AtencionOportunaTest` protege estos puntos de quiebre.
El inicio y los listados de ofertas/galería usan cuadrículas que crecen por
breakpoint; urgencia y lista de espera mantienen formularios en una sola columna
y protegen su acción principal de la superposición del asistente. El widget
permanece visible: el observador comprueba el cruce real con cada zona protegida
y eleva el botón flotante solo cuando coincide en horizontal y vertical.

El 16/10 se corrigió el encabezado de la lista de Testimonios para que el filtro
y la acción principal no compitan en una sola fila estrecha. Las cargas de fotos
de Casos y adjuntos clínicos apilan el selector de archivo y el botón en móvil,
pero vuelven a una fila alineada desde `sm`. Los nombres largos de adjuntos
pueden partirse sin empujar el botón de eliminar fuera de pantalla.
`NavegacionResponsivaTest` pasó 5 pruebas/20 aserciones; la suite quedó en
SQLite 143 pruebas aprobadas/701 aserciones y una omisión esperada, y MySQL
8.4 aislado 144 pruebas/706 aserciones. Vite construyó los assets durante la
reconstrucción Docker; Pint pasó 183 archivos PHP. Falta aceptación visual en
dispositivo real.
