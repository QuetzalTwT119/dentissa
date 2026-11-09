# Manual técnico de Dentissa

## Entregas de frontend con Antigravity

Antigravity trabaja directamente en `C:\laragon\www\dentissa`. Sus instrucciones de alcance están en `ANTIGRAVITY.md`: puede rediseñar todas las vistas y recursos de interfaz, pero no backend, rutas, base de datos, Docker ni credenciales.

El servicio `app` monta `resources/views` y `public/images` desde la carpeta del proyecto. Por ello, al guardar una portada o imagen, Laravel usa ese mismo archivo sin una copia intermedia. `scripts/antigravity.ps1` ejecuta QA en Docker y reconstruye la aplicación sólo si la validación aprueba; `-ValidarSolo` omite la reconstrucción.

## 1. Propósito

Dentissa es un sistema web para la gestión de una clínica dental. Permite
administrar pacientes, servicios, tratamientos, citas,
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

El ícono de la pestaña del navegador se define una sola vez en
`resources/views/partials/favicon.blade.php` y usa el recurso de marca
`public/images/brand/pwa-icon-192.png`. El layout autenticado ya lo hereda por
`partials.pwa-meta`; los layouts y pantallas independientes también incluyen
el parcial para conservar el mismo favicon en todo el sitio.

En Windows el equipo puede usar Docker Desktop con la integración de WSL2
activada. También es válido ejecutar Docker Engine directamente dentro de WSL;
los comandos de `docker compose` son los mismos.



La portada pública precarga su imagen principal y utiliza tipografías Figtree y Alex Brush servidas desde `public/fonts`, para no depender de un proveedor externo. Su composición editorial usa el nombre de la clínica como firma, describe el flujo real de agenda en línea y presenta un acceso directo a la agenda. Los servicios se renderizan como carrusel duplicado animado con CSS para permitir un bucle continuo; las tarjetas conservan altura e imagen uniformes, la animación se pausa al recibir foco y se desactiva con `prefers-reduced-motion`. Las imágenes del hero y servicios están configuradas en `config/dentissa.php`. El bloque de contacto se controla mediante `DENTISSA_MODO_DEMO`; debe permanecer activo mientras los valores sean ficticios y no debe abrirse el sitio a pacientes hasta configurar datos reales.

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

La ruta `/version` muestra el SHA actual del checkout local y si hay cambios
sin commit. En Railway devuelve el SHA de `RAILWAY_GIT_COMMIT_SHA`. Para
confirmar paridad, compara `commit` en ambos ambientes y exige
`working_tree_clean: true` en local. Si el valor dice `false`, compara solo
después de guardar los cambios en un commit y reiniciar Compose Watch.

GitHub Actions también construye ambos Dockerfiles a partir del mismo commit y
publica el check agregado `Tests / release-gate`. Para que producción no reciba
un merge con pruebas fallidas, proteger `master` desde GitHub: requerir pull
request y exigir `Tests / release-gate` como verificación aprobada. La regla
de rama se configura una vez en GitHub (Settings > Branches o Rulesets); Railway
debe seguir desplegando desde `master` después del merge aprobado.

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

Las fotos de perfil se guardan en el disco privado de Laravel, dentro de
`storage/app/private/profile-photos`; no se publica un enlace de `storage`.
Las rutas de lectura requieren sesión y sirven únicamente la foto de la cuenta
autenticada. Solo se aceptan JPG, PNG y WebP de hasta 5 MB y 3000 × 3000 px.
La sustitución y eliminación actualizan el usuario y la bitácora; se limpia el
archivo anterior solo después de guardar correctamente el cambio.

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

### Validación de entregas de frontend

El servicio opcional `qa` ofrece una barrera local entre una entrega visual y
su integración con Laravel. Se inicia bajo demanda y termina solo al finalizar:

```powershell
docker compose --profile qa run --rm --build qa
```

La imagen compila los recursos de Vite durante el build, compila las vistas
Blade y ejecuta las pruebas de portada, imágenes públicas, PWA y responsividad
con SQLite en memoria. No expone puertos, no utiliza la base MySQL de desarrollo
y no sustituye la revisión visual manual ni la aprobación del contenido por la
clínica.

El perfil `qa` incluye claves ficticias de Reverb exclusivamente para que la
suite pueda comprobar la autorización de canales privados de notificaciones.
No se conecta a un servicio Reverb real ni utiliza credenciales del `.env`.
Para probar lógica recién modificada dentro de una imagen limpia, sin depender
de la copia que ya está ejecutándose en `app`, usar:

```powershell
docker compose --profile qa build qa
docker compose --profile qa run --rm --no-deps --entrypoint php qa artisan test
```

Ejecutar la suite completa:

```bash
docker compose exec app php artisan test
```

Las pruebas usan SQLite en memoria o el schema aislado `dentissa_test`; no
deben modificar la base normal `dentissa`. La suite cubre reglas de catálogo,
fechas futuras, ciclo de vida y concurrencia de citas, agenda pública,
permisos, historial clínico, auditoría, notificaciones
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
  notificaciones.
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

La vista de agenda muestra **Más acciones** solo para citas `agendada` (estado
heredado de citas históricas) y `confirmada`, estados en los que se permite
reagendar o cancelar. Las citas creadas por cualquier canal vigente nacen
confirmadas; el control para confirmar manualmente se conserva para registros
históricos pendientes. Una cita
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

`AsistenteAgendaService` es un flujo separado del modelo de IA. En el widget
web consulta disponibilidad por servicio, tratamiento y fecha; no crea ni
confirma citas. Acepta `hoy`, `mañana` y fechas estrictas `DD/MM/AAAA`,
`DD-MM-AAAA` o `AAAA-MM-DD`. Rechaza fechas mal formadas y fechas pasadas antes
de consultar `DisponibilidadService`; para reservar, la persona continúa en
`/agendar-cita`.

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

El modelo se guarda en el volumen Docker `ollama_data`, no en Git. Un Ollama
local no queda disponible automáticamente para Railway. Para una prueba de
prototipo en Railway, despliega Ollama como un servicio del mismo proyecto y
entorno, guárdalo en un volumen montado en `/root/.ollama` y conéctalo usando
su dominio privado, por ejemplo `http://ollama.railway.internal:11434`. No
asignes dominio público a Ollama. La aplicación solo acepta HTTP hacia hosts
locales o el sufijo privado `.railway.internal` en el puerto 11434; rechaza
endpoints de inferencia públicos. Habilita `DENTISSA_ASISTENTE_IA_HABILITADA`
solo en el servicio `dentissa` después de que el servicio Ollama esté listo.

Serverless puede reducir el consumo inactivo durante una prueba, pero la
primera consulta después de dormir puede demorarse o devolver 502 mientras
Ollama despierta. Railway mide recursos del workspace en conjunto; el límite
duro detiene todos los servicios al alcanzarse. Vigila Usage y Metrics, y no
interpretes un despliegue activo como prueba de que el modelo ya contestó una
consulta real.

Las pruebas automatizadas fuerzan la IA apagada en `phpunit.xml` y en
`tests/TestCase.php`, incluso si el `.env` local la tiene encendida. Las pruebas
de `AsistenteIaLocalTest` la habilitan explícitamente y simulan las respuestas
HTTP de Ollama. Así la suite no depende de que el modelo esté descargado o
disponible y no envía consultas de prueba a un servicio real.

## 11. Historial y notificaciones

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
datos clínicos. Además del WebSocket, el navegador consulta
`notificaciones.actualizaciones` cada 3 segundos: así un fallo de suscripción,
una reconexión o un reinicio de Reverb no puede ocultar avisos nuevos. El
endpoint filtra por la cuenta autenticada y no almacena la respuesta en caché.
La tabla `notificaciones_sistema` separa `vista_en` de `leida_en`: al abrir la
campana, el navegador registra los avisos como vistos, actualiza el contador de
novedades y conserva el estado pendiente (punto rosa) hasta que cada aviso se
lee individualmente. El
centro de actividad agrupa el historial por día y clasifica visualmente cada
evento; cancelaciones e inasistencias se consideran prioritarias. Las
rutas guardadas para avisos internos son relativas (por ejemplo, `/citas`),
por lo que funcionan igual desde `localhost`, una IP local o el dominio de
producción, sin perder la sesión. Docker inicia
Reverb como servicio en el puerto 8080, sin costo de
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
u oficiales del paciente. En la edición
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
desactivación de cuentas se gestiona desde administración; el perfil no expone
una acción para desactivar o borrar la propia cuenta. Se evita el borrado físico
porque las claves foráneas históricas podrían eliminar pacientes e historiales
clínicos. La copia del actor conserva su identidad aunque la cuenta llegara a
eliminarse por una operación externa. Para proteger la privacidad, los cambios de historial
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

Los scripts `scripts/respaldo-dentissa.ps1` y
`scripts/restaurar-prueba-dentissa.ps1` usan el repositorio Restic cifrado de
`E:\DentissaBackups\restic-repo`. La contraseña se pide de forma interactiva,
se conserva sólo durante la ejecución y no se guarda en Git ni en `.env`.

En este equipo Docker Desktop no monta de forma fiable la unidad externa `E:`
desde PowerShell. El respaldo operativo se ejecuta desde Ubuntu/WSL con
`scripts/respaldo-dentissa-wsl.sh`, donde la unidad se usa como
`/mnt/e/DentissaBackups/restic-repo`. Si la unidad no aparece en WSL, se monta
una vez desde PowerShell con:

```powershell
wsl -d Ubuntu -u root -- mount -t drvfs E: /mnt/e
```

Después, desde Ubuntu, se ejecuta:

```bash
bash /mnt/c/laragon/www/dentissa/scripts/respaldo-dentissa-wsl.sh
```

El script inicializa el repositorio sólo cuando no existe y nunca elimina
snapshots anteriores.

El respaldo exporta MySQL con una transacción consistente, incluye `storage/app`
y un manifiesto con el hash SHA-256 del dump. Después crea un snapshot cifrado y
ejecuta `restic check --read-data-subset=1/10`. No borra snapshots ni aplica
retención automática: ese proceso requiere una política de conservación
aprobada para no eliminar copias históricas.

La restauración de prueba sólo permite un destino que no exista bajo el disco
externo; comprueba que se recuperen el manifiesto y el dump. No importa ni
sobrescribe la base activa. El ensayo de importación a un schema MySQL aislado
queda como siguiente paso antes de usar datos clínicos reales.

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
lateral reúne progreso y selección. La prueba
`AsistenteVistasPublicasTest` valida el resumen y la cuadrícula responsiva.
La agenda reserva más espacio inferior en móvil para el botón flotante y solo
un margen reducido en escritorio, evitando desbordamiento vertical cuando el
flujo cabe en la ventana. El contenido más alto conserva el desplazamiento
normal. La aceptación visual final requiere recargar `/agendar-cita` en el
teléfono conectado a la misma red.

## 16. Preparación local para Railway

Railway es el destino elegido para la demostración escolar. `Dockerfile.local`
mantiene el entorno local de desarrollo/QA que utiliza `compose.yaml`.
La configuración observada en Railway el 27/10/2026 usa el `Dockerfile` raíz
(builder Dockerfile) y no define un comando de inicio personalizado. Railway no
ejecuta el Compose local como un solo despliegue; la aplicación y MySQL son
servicios separados.

El `Dockerfile` productivo inicia `scripts/start-production.sh`: primero aplica
migraciones y después ejecuta Supervisor. Supervisor mantiene en el mismo
contenedor el servidor web (en el puerto `PORT` de Railway) y el proceso
`php artisan dentissa:telegram`. El bot solo puede autenticarse si
`DENTISSA_TELEGRAM_BOT_TOKEN` está configurado como variable privada del servicio;
no se debe poner el token en Git, el comando de inicio, capturas o logs. Cambiar
el token en Telegram invalida el anterior; actualizar la variable requiere un
nuevo despliegue. No se necesita crear un segundo servicio para el prototipo.

Validación local después de cambiar esta separación:

```bash
docker compose config
docker compose up -d --build app
docker compose exec app php artisan test
```

Al configurar Railway se deberán definir las variables de producción en su
panel (nunca en Git), validar la compilación Vite, mantener MySQL privado y usar
`/up` como healthcheck. El arranque productivo aplica migraciones antes de
levantar procesos. La demostración se limita a datos ficticios; Ollama y
WhatsApp no forman parte de la configuración web por defecto. Telegram queda
habilitado solo cuando su token privado está configurado. Consultar
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

La solicitud de restablecimiento devuelve el mismo mensaje en español tanto
para un correo registrado como para uno inexistente, evitando enumerar cuentas.
`PasswordResetTest` comprueba la notificación para usuarios existentes, el
mensaje genérico para correos desconocidos, el formulario con token y el cambio
de contraseña. La entrega real depende del transporte de correo configurado en
cada entorno; las pruebas usan notificaciones simuladas.

### Correo Gmail API para prototipo local

Existe un transporte opcional `gmail` para enviar correo mediante la API de
Gmail. La cuenta emisora autoriza una vez a Dentissa; el destinatario sigue
siendo el correo almacenado en la cuenta del usuario. Para conectarlo en local:

1. En Google Cloud, habilitar Gmail API, configurar OAuth como aplicación
   externa en modo de prueba, agregar la cuenta emisora como usuario de prueba
   y crear un cliente OAuth de escritorio.
2. Guardar el JSON del cliente como
   `storage/app/private/google-oauth-client.json`. El directorio privado ya
   está excluido de Git.
3. Desde PowerShell, en la raíz del proyecto, ejecutar
   `powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\google-oauth.ps1`.
   El script abre Google para autorizar `gmail.send` y guarda el refresh token
   en `storage/app/private/google-oauth-token.json`; no debe copiarse a Git ni
   compartirse.
4. Configurar sólo en el `.env` local `MAIL_MAILER=gmail` y
   `MAIL_FROM_ADDRESS` con la cuenta emisora; después ejecutar
   `docker compose exec -T app php artisan config:clear`.

`GmailApiTransportTest` simula el intercambio del token y el endpoint de envío;
no manda correos reales. El envío real debe probarse solicitando un enlace de
recuperación para una cuenta de prueba y verificando el mensaje en su buzón.
Mientras la aplicación OAuth permanezca en modo de prueba, Google puede hacer
que el refresh token caduque a los siete días; este flujo sirve para prototipos,
no se considera configuración estable de producción. El proyecto puede volver
a Resend cambiando `MAIL_MAILER` sin eliminar esta integración.

El transporte también permite despliegues breves sin montar archivos JSON: se
pueden definir `GMAIL_OAUTH_CLIENT_ID`, `GMAIL_OAUTH_CLIENT_SECRET` y
`GMAIL_OAUTH_REFRESH_TOKEN` como secretos del proveedor de hosting. Si las tres
variables no están presentes, se usa el par de archivos privados local; si sólo
se configura una parte del conjunto de variables, el transporte falla con un
error genérico de configuración y no registra sus valores.

La navegación interna comparte la identidad visual de Dentissa en local y
producción: marca, accesos a Inicio/Agenda/Asistente y grupos por función para
administración. El menú horizontal se muestra desde `2xl`; en pantallas menores
se usa el menú compacto para evitar comprimir enlaces. La lista móvil mantiene
las rutas administrativas, incluida Auditoría. El botón compacto expone etiqueta
accesible, estado abierto/cerrado y relación con su panel. La vista previa de
notificaciones se adapta a móvil/escritorio y comparte el estado entre ambas
presentaciones. Las tablas

El panel de administración reúne accesos rápidos para agenda,
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
  el desplazamiento existente permanece operativo. El acceso compartido se
  representa como una cápsula horizontal compacta con el ícono dental y el
  nombre en una sola línea, tanto en móvil como en escritorio; el panel abre
  justo encima con espaciado reducido.
El inicio y los listados de ofertas/galería usan cuadrículas que crecen por
breakpoint; la agenda protege su acción principal de la superposición del asistente. El widget
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

## 19. Rediseño visual integral y sistema de diseño boutique

Se completó el rediseño visual de todo el ecosistema de Dentissa (sitio público, autenticación y panel de administración) alineándolo a la identidad de marca cálida y boutique:

- **Tokens de color y jerarquía visual:**
  - Rosa de marca: `#d75078` (acciones primarias, estados destacados y acentos)
  - Durazno cálido: `#f2b0a6` (fondos de selección, bordes activos y detalles suaves)
  - Fondo base: `#FAF6F6` (lienzo unificado libre de contrastes fríos o grises clínicos duros)
  - Texto principal: `#2D2526` (alto contraste y calidez para legibilidad óptima)
  - Verde salvia: `#4E8D72` (confirmaciones, estados activos y badges de éxito clínico)
  - Bordes neutros cálidos: `#e9dfdb` (separadores sutiles y tarjetas suaves)

- **Componentes y arquitectura de interfaz:**
  - Componentes atómicos en `resources/views/components/`: botones primarios (`bg-brand-600`, esquinas redondeadas `rounded-xl`, sombra suave), secundarios con borde cálido, inputs y selects consistentes, modales con desenfoque de fondo y alertas amigables.
  - Vistas públicas (`welcome`, agenda pública wizard por pasos, galería, ofertas): tipografía Figtree y Alex Brush locales precargadas, carrusel de servicios optimizado y menú responsivo desplegable para móvil y tablet. En la portada, el scroll-spy de `welcome.blade.php` sincroniza enlaces de escritorio/móvil con la sección bajo el encabezado fijo mediante `aria-current="location"`; ese estado da el acento rosa únicamente al enlace activo y se actualiza en `requestAnimationFrame`.
  - Vistas de acceso (`guest`, `login`, recuperación de contraseña): tarjetas centradas de estilo boutique con fondo ambiental cálido y enlaces visibles.
  - Panel administrativo (`layouts/app`, `navigation`, `dashboard`, citas, pacientes, historial clínico con fotos/radiografías, catálogo de servicios y tratamientos, horarios, usuarios, reportes, auditoría, mensajes, testimonios, casos y preguntas frecuentes): tablas con encabezados claros en `#FAF6F6`, badges de estado legibles, filtros organizados y scroll horizontal en móviles (`min-w-[760px]`).
  - Total preservación de lógica de negocio, nombres de campos, CSRF, validaciones y roles. La suite de pruebas completa pasó con 158 pruebas aprobadas y 0 fallos.

