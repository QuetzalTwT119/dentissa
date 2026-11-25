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

**Bienvenida de la portada (hero "Panorámica").** Se eligió en una ronda de
prototipos frente a una versión con reserva integrada y otra centrada en la
doctora. Usa `public/images/dentissa/hero-inicio.jpg` (1376×768, con
`fetchpriority="high"` y `width`/`height` para no mover el contenido al
cargar). **Peso de las fotos (H-97, 25/11/2026):** las de `images/dentissa/`
pesaban ~650 KB cada una (4.7 MB la portada); se recomprimieron con GD a JPEG
progresivo calidad 80 (tratamientos a 800×800, inicio a 1376×768) y quedaron en
0.6 MB en total. `PortadaPublicaTest` falla si alguna pasa de 150 KB: comprime
cualquier foto nueva antes de subirla. En celular, "¿Prefieres llamar?" va
debajo del botón principal (`w-full sm:w-auto`) porque a la derecha lo tapaban
los botones flotantes de WhatsApp y del asistente. En escritorio la foto ocupa del 44 % al borde derecho y su orilla se
funde con el color crema; el texto se limita al 40 % izquierdo para no tapar a
las personas. En celular y tableta la foto va arriba. Solo el texto tiene
animación de entrada (`.hero-entra` en `app.css`); la foto no se anima porque es
el elemento más grande de la página (LCP). El teléfono sale de
`config('dentissa.contacto')` y el aviso "Abierto hoy hasta las 18:00" de
`HorarioAtencion::estadoActual()`, que lee `horarios_atencion` con la hora de la
clínica (`EstadoConsultorioTest`). La imagen pesa unos 600 KB; convertirla a
WebP reduciría la carga en celular.

**Especialidades de la portada (carrusel "Fila").** Se eligió tras dos rondas
de prototipos (Galería, Lista y Explorador; luego Galería en cuadrícula, fila
deslizable o una a la vez). Reemplaza las tarjetas fijas de la vista por las
especialidades activas de la base de datos (`servicios` en la ruta `/`, con sus
tratamientos activos). Si aún no hay especialidades, se muestra el catálogo de
ejemplo de antes.

- **Datos de cada tarjeta:** `Servicio::fotoPublica()` (la imagen subida en el
  panel, `/storage/…`, o la aprobada en `dentissa.imagenes.servicios`) y
  `Servicio::descripcionPublica()` (la escrita por la clínica o
  `dentissa.descripciones_servicios`). Con tratamientos activos dice "Agenda en
  línea" y enlaza a `/agendar-cita?servicio=ID`; sin ellos, "Agenda por
  teléfono" y enlaza a `contacto.telefono_enlace`.
- **Agenda con especialidad elegida:** `CitaPublicaController::create()` acepta
  `?servicio=ID` solo si es una especialidad agendable; cualquier otro valor se
  ignora.
- **Comportamiento (`carruselServicios` en `app.js`, estilos `.carrusel-*` en
  `app.css`):** nunca avanza solo; cada flecha mueve una tarjeta (con 7 tarjetas
  y 3 visibles, avanzar por páginas saltaba puntos) y da la vuelta al llegar al
  final. Muestra 3 tarjetas completas en escritorio y 2 en tableta; en celular
  se asoma la siguiente como pista para deslizar. Las flechas dependen del tipo
  de puntero, no del ancho: con mouse se ven a cualquier ancho y en pantallas
  táctiles se ocultan. Las fotos cargan con `loading="lazy"`; pesan unos 600 KB
  cada una y convertirlas a WebP es una mejora pendiente.
- **Pruebas:** `ServiciosPortadaTest`; `ImagenesPublicasTest` cubre el catálogo
  de ejemplo sin especialidades.

El ícono de la pestaña del navegador se define una sola vez en
`resources/views/partials/favicon.blade.php` y usa el recurso de marca
`public/images/brand/pwa-icon-192.png`. El layout autenticado ya lo hereda por
`partials.pwa-meta`; los layouts y pantallas independientes también incluyen
el parcial para conservar el mismo favicon en todo el sitio.

En Windows el equipo puede usar Docker Desktop con la integración de WSL2
activada. También es válido ejecutar Docker Engine directamente dentro de WSL;
los comandos de `docker compose` son los mismos.



La portada pública precarga su imagen principal y utiliza tipografías Figtree y Alex Brush servidas desde `public/fonts`, para no depender de un proveedor externo. Su composición editorial usa el nombre de la clínica como firma, describe el flujo real de agenda en línea y presenta un acceso directo a la agenda. Los servicios se muestran en un carrusel que mueve el paciente (ver "Especialidades de la portada"). Las imágenes del hero y servicios están configuradas en `config/dentissa.php`. El bloque de contacto se controla mediante `DENTISSA_MODO_DEMO`; debe permanecer activo mientras los valores sean ficticios y no debe abrirse el sitio a pacientes hasta configurar datos reales.

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

`ServiciosTratamientosSeeder` carga el catálogo real de la clínica, tomado de la
libreta de la doctora:

| Servicio | Tratamiento | Categoría | Duración |
|---|---|---|---|
| Odontología General | Valoración | Diagnóstico | 30 min |
| Odontología General | Limpieza Dental | Preventiva | 60 min |
| Odontología General | Retiro de Caries | Restauradora | 60 min |
| Odontología General | Extracción Dental | Quirúrgica | 60 min |
| Odontología Estética | Blanqueamiento Dental | Estética | 90 min |

Los precios quedan vacíos hasta que la clínica los confirme. La categoría
`Quirúrgica` suma el margen de preparación entre citas (sección 9). Las
categorías válidas viven en `Tratamiento::CATEGORIAS`; el formulario del panel y
el seeder usan esa misma lista, así que editar un tratamiento no borra su
categoría. La tabla `servicios_tratamientos` tiene `created_at` (lo llena la
base) pero no `updated_at`; por eso el pivote `ServicioTratamiento` sobrescribe
`getUpdatedAtColumn()` para devolver `null`. Sin eso, reordenar una asignación
existente (por ejemplo, al volver a ejecutar el seeder sobre una base con
datos) fallaba con `no such column: updated_at`. El seeder
desactiva, sin borrarlos, los servicios y tratamientos del catálogo de ejemplo
anterior (por ejemplo `Implante Dental` o `Implantología`), para conservar el
historial de citas; lo creado desde el panel no se modifica. Lo cubre
`ServiciosTratamientosSeederTest`.

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
autenticada. Desde el 23/11/2026 la foto viaja en el mismo `PATCH /profile` que los
datos (`ProfileUpdateRequest`: `foto_perfil` opcional, `quitar_foto` booleano); se
quitaron `POST/DELETE /profile/photo`. La vista previa es local (`URL.createObjectURL`):
nada se sube hasta pulsar Guardar. Solo se aceptan JPG, PNG y WebP de hasta 5 MB y 3000 × 3000 px.
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

El árbol completo de carpetas y el de pantallas por rol están en
[`docs/ARBOL-DEL-PROYECTO.md`](ARBOL-DEL-PROYECTO.md).

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

La edición administrativa permite actualizar nombre, apellido, teléfono celular y
correo (desde H-61, 22/11/2026, ya no CURP, RFC, clave de elector ni tutor; lo guardado
antes se conserva en la base). Nombre, apellido y teléfono son obligatorios; el correo
es opcional y único entre pacientes. El teléfono valida diez dígitos y admite
separadores visuales y el prefijo mexicano `+52`. El registro
de auditoría conserva solo los nombres de los campos modificados, no los valores
de correo o teléfono.

## 9. Regla de disponibilidad

`DisponibilidadService` consulta el horario de atención, la duración del
tratamiento, el margen entre citas, la capacidad simultánea y las citas ya
registradas. Antes de guardar una cita vuelve a validar el horario dentro de
una transacción para evitar reservas duplicadas.

**Bloqueos (H-81, 24/11/2026).** Tabla `bloqueos_agenda` (`fecha_inicio`, `fecha_fin`,
`hora_inicio`/`hora_fin` nulas = todo el día, `motivo`, `creado_por`). `BloqueoAgenda::tramosDelDia()`
devuelve los tramos [inicio, fin) de una fecha y `chocaCon()` los compara con la cita
(inicio + duración). `horariosDisponibles()` y `estaDisponible()` los descuentan; `resumenMensual()`
marca los días completos como `cerrado`. `BloqueoAgendaController` (módulo `configuracion`,
`admin.bloqueos.store/destroy`) valida: no fechas pasadas, horas solo en un día, máximo 60 días y
**ninguna cita agendada, confirmada o en curso dentro del tramo**. Bitácora: `bloqueo.creado` y
`bloqueo.eliminado`. Pruebas: `BloqueoAgendaTest`.

**Festivos (H-93, 24/11/2026).** `App\Support\DiasFestivos::delAnio()` calcula los festivos
del consultorio: los 7 del art. 74 de la LFT (tres caen en lunes y se calculan con
`nthOfMonth`), el 1 de octubre cada 6 años desde 2024, y por decisión del usuario el 2 de
noviembre y el 12, 24 y 31 de diciembre. `php artisan dentissa:bloquear-festivos [años…]`
crea un `BloqueoAgenda` de día completo por festivo (`motivo` = «Festivo: …»,
`creado_por` nulo → `esFestivo()`). Es idempotente: salta fechas pasadas, días ya bloqueados
completos, días con citas activas (los reporta) y las fechas de `festivos_omitidos`, que se
llena cuando el personal quita con 🗑 un festivo del sistema. `--simular` solo muestra;
`--avisar` manda una alerta `festivos_con_citas` a la campana. Corre en
`scripts/start-production.sh` después de `migrate --force` (no detiene el arranque si falla)
y el programador lo ejecuta el día 1 de cada mes a las 06:00 con `--avisar`.
Pruebas: `DiasFestivosTest` y `BloquearDiasFestivosTest`.

### Calendario de disponibilidad (`partials/disponibilidad-picker`)

**Reagendar directo (H-66, 23/11/2026).** El botón Reagendar (WhatsApp y correo) manda
`RespuestaRecordatorioService::enlaceReagendar()`: `URL::temporarySignedRoute('citas.publica.create',
+7 días, ['reagendar' => id])`. `CitaPublicaController@create` solo lo usa si
`hasValidSignature()`: abre en el paso 2 (calendario) con el servicio y tratamiento de la cita
anterior y los datos del paciente llenos. Sin firma, alterado o caducado abre la agenda normal
sin datos. Cancelar sigue mandando la agenda normal.

**Reagendar conectado, solo día y hora (H-68, 23/11/2026).** Columna `citas.id_cita_nueva`
(la cita vieja apunta a la que la reemplazó). El formulario lleva oculto
`RespuestaRecordatorioService::tokenReagendar()` (cifrado, 7 días); en `store`,
`citaAReagendar()` lo acepta solo si la cita vieja está cancelada por "pidió reagendar", sin cita
nueva y con el mismo celular. Entonces: servicio, tratamiento y datos se toman de la cita vieja
(se ignora lo que mande el navegador), el aviso de privacidad no se vuelve a pedir si ya estaba
aceptado (se conserva su fecha), se manda la plantilla `cita_reagendada`, el aviso interno es
`reagendada_paciente` y "Horario liberado" queda atendido. La agenda muestra
"↻ Reagendada por el paciente → fecha" y el botón viejo contesta "Esta cita ya se reagendó al…".
En la agenda en línea el último paso es "Confirmar" (sin campos) y el calendario no tiene "Atrás".

**Oferta solo desde su tarjeta (H-69, 23/11/2026).** `CrearCitaService` ya no usa
`Oferta::aplicableA()`: guarda `$datos['id_oferta']` solo si es una oferta publicada del mismo
tratamiento, vigente en la fecha de la cita y con lugar. La tarjeta de la portada enlaza
`?tratamiento=&oferta=`; la agenda en línea manda `id_oferta` oculto solo si `ofertaAplica`.
Al reagendar, `store` fuerza `id_oferta` = la de la cita vieja. Telegram no la aplica.

La agenda pública, **Nueva cita** y **Reagendar** comparten un mismo selector:
el mes a la izquierda y los horarios del día elegido a la derecha (en celular,
uno debajo del otro). Se eligió en una ronda de prototipos frente a una tira
semanal y una lista de "lo más pronto", porque deja ver de un vistazo qué días
tienen lugar y sirve igual para quien agenda para mañana que para quien planea
con semanas de anticipación.

Usa dos consultas:

| Ruta | Para qué | Respuesta |
| --- | --- | --- |
| `GET /disponibilidad?tratamiento_id&fecha` | Horarios del día elegido | `{"horarios": ["09:00", ...]}` |
| `GET /disponibilidad/mes?tratamiento_id&mes=YYYY-MM` | Puntos del calendario | `{"mes", "duracion_minutos", "dias": {"2026-11-19": {"horarios": 8, "estado": "disponible"}}}` |

- `DisponibilidadService::resumenMensual()` recorre el mes y llama a
  `horariosDisponibles()` por cada día abierto: el número del calendario es
  exactamente la lista que verá el paciente al elegir ese día.
- Estados por día: `pasado`, `cerrado` (el consultorio no abre ese día de la
  semana), `sin_horarios` (abre, pero no queda cupo) y `disponible`. La
  interfaz marca con punto verde los días con lugar, con punto durazno los de
  3 horarios o menos y tacha los días sin horarios.
- Solo acepta del mes actual a 12 meses adelante
  (`DisponibilidadController::MESES_ADELANTE`) y tiene límite propio de 60
  consultas por minuto, porque cada consulta calcula hasta 31 días.
- Igual que la consulta por día, `exclude_cita_id` solo lo acepta el personal
  autorizado a editar esa cita (reagendar); la agenda pública recibe 403.
- Las rutas `/disponibilidad*` responden los errores de validación como JSON
  422 (`bootstrap/app.php`), porque solo las consulta JavaScript.
- En el navegador, el componente Alpine `calendarioDisponibilidad`
  (`resources/js/app.js`) vive dentro del `x-data` del formulario y comparte
  sus datos (`idTratamiento`, `fecha`, `hora`, `horarios`, `cargarHorarios()`).
  Cada formulario envía `fecha` y `hora` con `<input type="hidden">`. Si el
  paciente cambia de mes rápido, solo se aplica la última respuesta.
- Accesibilidad: cada día es un botón con etiqueta completa ("Jueves, 19 de
  noviembre, 8 horarios disponibles"); las flechas del teclado mueven el foco
  entre días con lugar y los horarios son un grupo de opciones (`radiogroup`).

Pruebas: `tests/Feature/DisponibilidadMesTest.php`.

```powershell
docker compose --profile qa build qa
docker compose --profile qa run --rm --no-deps --entrypoint php qa artisan test --filter=DisponibilidadMesTest
```

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

El modelo se guarda en el volumen Docker `ollama_data`, no en Git. Para la
prueba de prototipo en Railway, ejecuta Ollama como servicio privado del mismo
proyecto y entorno que `dentissa`, con un volumen montado en `/root/.ollama`.
Configura `DENTISSA_OLLAMA_BASE_URL=http://ollama.railway.internal:11434` en
`dentissa`; la aplicación solo acepta HTTP en hosts locales o bajo
`.railway.internal`, puerto 11434, y rechaza endpoints públicos. Ollama no debe
tener un dominio público. `DENTISSA_OLLAMA_MODELO` debe coincidir con el modelo
descargado, por ejemplo `granite3.3:2b`; habilita
`DENTISSA_ASISTENTE_IA_HABILITADA=true` solo después de comprobar la conexión.

Serverless puede reducir el consumo mientras Ollama está inactivo. La primera
solicitud posterior al reposo puede tardar o fallar temporalmente durante el
arranque. Vigila Usage y Metrics de Railway: el límite de cómputo detiene los
servicios del workspace al alcanzarse. Un despliegue exitoso por sí solo no
confirma que una respuesta del modelo llegó a Dentissa.

Las pruebas automatizadas fuerzan la IA apagada en `phpunit.xml` y en
`tests/TestCase.php`, incluso si el `.env` local la tiene encendida. Las pruebas
de `AsistenteIaLocalTest` la habilitan explícitamente y simulan las respuestas
HTTP de Ollama. Así la suite no depende de que el modelo esté descargado o
disponible y no envía consultas de prueba a un servicio real.

## 11. Historial y notificaciones

**Cuestionario de historia clínica (H-63, 22/11/2026).** Tabla `historias_clinicas`, una
por paciente (`Paciente::historiaClinica()`, `id_paciente` único). Datos generales,
antecedentes familiares y personales, alergias, medicamentos, embarazo/lactancia (solo
`sexo = femenino`), antecedentes dentales, hábitos y observaciones. Las listas se guardan
como JSON con solo las claves marcadas; opciones y nombres en las constantes de
`App\Models\HistoriaClinica` (agregar una opción no cambia la base). `alertas()` arma el
aviso rojo (`x-aviso-clinico`): alergias, `RIESGO_PERSONALES`, embarazo y reacción previa a
la anestesia; sale en el expediente y al crear o editar una consulta.
`HistoriaClinicaController` (`admin.pacientes.historia.edit/update`, módulo `pacientes` de
config/permisos.php): el formulario reemplaza la historia completa (campo que no llega =
vacío) y Control de actividades guarda solo `campos_modificados` (nunca valores de salud).
El PDF del historial incluye la historia y se puede descargar con historia aunque no haya
consultas. En la Agenda, una cita **completada** sin consulta muestra "Registrar consulta"
(abre `historial.create?id_cita=` con la cita y su fecha elegidas). La columna de Pacientes
se llama "Consultas en expediente". El odontograma queda para una etapa posterior.

**Acceso de la asistente (H-64, 22/11/2026).** Módulo propio `historia_clinica`
(`admin.historias.*`, `admin.pacientes.historia.*`), declarado **antes** de `pacientes` en
config/permisos.php porque `Permisos::moduloDeRuta()` devuelve el primero que coincide.
Lo tienen las tres cuentas; la asistente queda con `['inicio', 'historia_clinica']`.
Para ella hay una lista propia, `/admin/historias-clinicas` (`HistoriaClinicaController@index`,
buscar por nombre o teléfono, estado Llena/Pendiente) y un enlace "Historias clínicas" en el
menú solo si no tiene `pacientes`. `HistoriaClinicaController::urlRegreso()` decide el regreso
(expediente o lista). Consultas, notas privadas, adjuntos y PDF siguen en `pacientes`.
Nueva/Editar consulta usan `.formulario-panel.formulario-una-columna` (una pregunta por renglón).

**Historia clínica completa (H-70 y H-71, 23/11/2026).** Se tomó como base el formato de
historia clínica odontológica que entregó el usuario (Colombia), adaptado a México; del
formulario en inglés solo el contacto de emergencia y el médico de cabecera. Lo de EPS,
seguro médico y número de seguro social no aplica.

- **H-70, cuestionario** (`historias_clinicas`): `estado_civil`, `tratamiento_medico_actual`,
  `contacto_emergencia_*`, `medico_cabecera_*`; antecedentes personales `respiratoria`,
  `gastritis` y `cirugias` (constantes de `HistoriaClinica`).
- **H-71, exploración clínica** (`exploraciones_clinicas`, varias por paciente,
  `Paciente::exploracionesClinicas()` de la más reciente a la más antigua):
  `estomatologico` (JSON por estructura: normal/hallazgo), `atm`, `periodontal` (JSON:
  hallazgo → dientes), `higiene_oral`, `oleary_dientes` y `oleary_superficies_placa`
  (`indiceOleary()` = placa ÷ (dientes × 4) × 100; 20 % o menos es aceptable), `pulpares` y
  `radiografica` (renglones por diente), `otros_hallazgos`, `odontograma` (JSON diente FDI →
  estado; `ESTADOS_DIENTE`, rojo = por tratar y azul = hecho) y `diagnosticos`.
  `ExploracionClinicaController` (`admin.pacientes.exploracion.*`, módulo `pacientes`: la
  asistente no la ve) descarta renglones vacíos y dientes inexistentes; una nueva parte del
  odontograma de la anterior. Control de actividades solo guarda `campos_modificados`. Sale en
  el expediente (resumen, O'Leary e historial de exploraciones) y en el PDF (la última).
  Las consultas ganan `historial_clinico.dientes` (diente(s) tratados).

**Botón global de regreso (H-73, 23/11/2026).** `components/volver.blade.php` ("← Volver a …"). Desde H-74 (24/11/2026) va solo en pantallas hijas **sin** botón "Cancelar" (expediente, dos pasos); en los formularios "Cancelar" ya cumple esa función.

**Agenda tras una acción (H-75, 24/11/2026).** `CitaController::volverALaAgenda()` redirige a `url()->previous()` solo si es `/citas` o `/citas?…` (conserva día, vista y filtros); si no, a `/citas?fecha=` del día de la cita. `store` y `update` (reagendar) van al día de la cita. `pestanasDeLaSemana()` agrega `canceladas` y `noAsistio`.

**Lógica de la agenda (H-96, QA en producción 24/11/2026).** `EstadoCita::esPorAtender()` (agendada,
confirmada, en curso) reemplaza a `! esInactiva()` en el conteo del día: las completadas salen como
"atendidas". `Cita::fin()`, `yaTermino()` (agendada/confirmada con su fin ya pasado) y `sePuedeIniciar()`
(confirmada, de hoy y sin terminar) deciden los botones de `citas/partials/acciones`. `start()` rechaza
citas de otro día, ya terminadas y, dentro de una transacción con `lockForUpdate`, las que excederían
`capacidad_simultanea` del día con otras «En curso». `complete()` acepta además agendadas/confirmadas
cuya hora ya llegó («Atendida» en un paso) y `noShow()` acepta agendadas. `index` manda
`enCursoOlvidadas` (en curso de días anteriores) para el aviso ámbar. Pruebas: `AgendaLogicaQaTest`.

**Campos de número (H-76, 24/11/2026).** `resources/css/app.css` (`@layer base`) quita las flechas de todo `input[type=number]`; se conserva `type=number` para validar y abrir el teclado numérico. **Citas a la vez** (`horarios_atencion.capacidad_simultanea`) la usan `DisponibilidadService`, `OcupacionSemanalService` y `ReporteService`; en producción quedó en 1. Desde H-77 se edita con **un solo campo** (`capacidad_simultanea` en la raíz del formulario) y `HorarioController::update` lo copia a los 7 días.

**Portada: primera visita y ubicación (H-78, 24/11/2026).** `partials/portada-primera-visita.blade.php` (3 pasos) y `partials/portada-ubicacion.blade.php`: iframe `https://maps.google.com/maps?q=…&output=embed` (sin clave de API, `loading="lazy"`) y enlace `https://www.google.com/maps/dir/?api=1&destination=…`. La dirección viene de `dentissa.contacto.direccion` (por defecto Blvd. Montebello 228, 78421 Pozos, S.L.P.; `DENTISSA_DIRECCION` o Datos del consultorio la reemplazan). Si está vacía, la sección no se muestra.

**Portada: casos, WhatsApp y misión/visión (H-79, 24/11/2026).** `partials/portada-casos.blade.php` usa `$casos` de la ruta `/` (antes se enviaba y no se dibujaba): primera imagen = Antes, segunda = Después. `partials/portada-whatsapp-flotante.blade.php` (fijo, `bottom-24`, encima del asistente). Ofertas con enlace `wa.me/…?text=` por oferta. Migración `2026_11_24_000001`: `datos_consultorio.mision` y `vision` (400), aplicadas a `dentissa.contacto` como los demás datos. Orden de secciones: servicios, nosotros, galería, promociones, casos, testimonios, primera-visita, preguntas, ubicación, agendar.

**Galería y preguntas (H-80, 24/11/2026).** `welcome.blade.php` arma "Instalaciones" con `$galeria` (Imagen tipo galería activa, máx. 8; `asset('storage/…')`) y, si no hay, cae a dos ilustraciones (H-94).

**Ilustraciones y foto de inicio (H-94, 25/11/2026).** Las fotos de marca (`public/images/1–8.jpg`: tazas, uniforme, instrumental con logo) se borraron: el consultorio no vende productos. En su lugar hay SVG dibujados a mano en `public/images/ilustraciones/` con la paleta de la marca (`consultorio`, `esterilizacion`, `sala-espera`, `recordatorio`); el dibujo va en la mitad de arriba porque abajo va el texto de la tarjeta. La tarjeta de "Instalaciones" dice «Un paciente a la vez» solo si `max(capacidad_simultanea)` de los días abiertos es 1 (`$pacientesALaVez`). La foto grande del inicio se puede reemplazar: `datos_consultorio.foto_inicio` (ruta en el disco `public`, carpeta `portada/`), `DatosConsultorio::fotoInicio()` (en caché) y `FotoInicioController` (`admin.galeria.foto-inicio.store/destroy`, módulo de Contenido por `admin.galeria.*`; jpg/png/webp hasta 5 MB; borra el archivo anterior; bitácora `portada.foto_inicio_cambiada/quitada`). Pruebas: `FotoInicioPortadaTest`, `ImagenesPublicasTest`, `PortadaPublicaTest`. `partials/portada-preguntas.blade.php` muestra `$faqs` activas con `<details>` (sin JavaScript).
En `x-admin.header` se usa con `:volver="route(...)" volver-texto="..."`; en encabezados propios,
`<x-volver :href texto />`. Va en toda pantalla que se abre desde otra (crear/editar,
expediente, cuestionarios); no en las principales del menú. `ConsistenciaDisenoTest` revisa las
pantallas hijas y que nadie dibuje la flecha a mano.

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
en navegación de escritorio y móvil. Una falla al persistir una notificación no
revierte la cita y queda registrada en el log de aplicación.

**Panel de la campana "Por atender" con avatares.** El panel separa dos
secciones: **Por atender** (eventos de `NotificacionSistema::EVENTOS_PRIORITARIOS`
—cancelación, cancelación del paciente, petición de reagendar, inasistencia y
respaldo fallido— sin `atendida_en`) y **Informativas** (lo demás, plegado en un
`<details>` que se abre solo cuando no hay pendientes). Cada sección muestra
hasta cinco avisos (scopes `porAtender()` e `informativas()`).

- `NotificacionInternaService::cambioCita` guarda `id_cita` (FK nula,
  `nullOnDelete`), así el aviso conoce al paciente. La figura es un avatar con
  sus iniciales; el tono (`tonoAvatar()`, 6 tonos) se deriva del nombre con
  `crc32` para que el mismo paciente tenga siempre el mismo color. Una insignia
  en la esquina indica qué pasó (`insignia()`); los avisos sin persona (respaldo)
  usan un marco punteado.
- Los avisos por atender ofrecen **Ver en la agenda** (`/citas?fecha=` del día de
  la cita), **Llamar** (`tel:` con el teléfono del paciente) y **Ya lo atendí**
  (`PATCH notificaciones.atendida`, que marca `atendida_en`, `leida_en` y
  `vista_en` y responde 422 para avisos informativos y 404 para ajenos).
- El aviso se dibuja en un solo parcial, `notificaciones/partials/aviso-panel`.
  `NotificacionSistema::datosEnVivo()` incluye ese HTML (`html`) y la sección
  (`por_atender`) tanto en el evento de Reverb como en el sondeo, y el navegador
  lo inserta con un `<template>`: la carga inicial y la llegada en vivo se ven
  idénticas sin duplicar marcado en JavaScript.

**QA de notificaciones (H-95, 25/11/2026).** Errores encontrados y corregidos:

- **Avisos perdidos por largo:** `titulo` es VARCHAR(120) y `mensaje` VARCHAR(255).
  Un respaldo fallido con un error largo de Google Drive, o un aviso de cita con
  una oferta de título largo, daba «Data too long» en MySQL y el aviso no se
  guardaba (SQLite no lo nota). `NotificacionSistema::booted()` los recorta con
  «…» al guardar (`LARGO_TITULO`, `LARGO_MENSAJE`).
- **«Marcar todas como leídas»** ahora también pone `vista_en`: antes la lista
  decía 0 pendientes y la campana seguía con su número.
- **Acción de cada aviso:** `accionPrincipal()` decide por evento (cita → agenda
  del día; `testimonio_nuevo` → Contenido → Testimonios; `festivos_con_citas` →
  Configuración `#bloqueos`; lo demás → la url guardada). La usan la campana, la
  lista (antes decía «Ver cita» en todos) y el aviso emergente; en la lista se
  oculta si llevaría a la misma lista (`accionLlevaAOtraPantalla()`).
- **Prioritarios:** se agregan `testimonio_nuevo` y `festivos_con_citas` con
  etiqueta e insignia propias. La migración `2026_11_25_000002` da por atendidos
  los avisos viejos de esos tipos que ya estaban leídos.
- **Aviso en vivo después de guardar:** `NotificacionInternaService::transmitir()`
  usa `DB::afterCommit()`. `CrearCitaService` llama a `cambioCita()` dentro de su
  transacción; si la cita no se guardaba, la campana mostraba un aviso fantasma.
  Además, un error al guardar el aviso de una persona ya no deja sin aviso a las demás.

Pruebas: `NotificacionesQaTest` (6, todas fallan con el código anterior).

**Página pública de promociones (H-100, 25/11/2026).** La portada muestra como
máximo `Oferta::MAXIMO_PORTADA` (24) y había más publicadas, así que algunas no
las veía nadie.

- `Oferta::vigentesConLugar()` devuelve todas las que ve el público (publicadas,
  agendables, con lugar, primero las que vencen antes); `paraPortada()` toma las
  primeras 24 de esa misma lista, así las reglas son una sola.
- Ruta pública `GET /promociones` (`promociones.index`) →
  `PromocionPublicaController` (invocable) → `promociones/index.blade.php`. Se
  llama así porque `/ofertas` ya es la vista del personal. Filtro opcional
  `?especialidad={id}`; solo se ofrecen especialidades activas con alguna
  promoción, y un id desconocido muestra todas.
- La tarjeta vive en `partials/tarjeta-oferta.blade.php` y la usan el carrusel y
  la página, para que se vean igual.
- La portada calcula la lista una vez en `routes/web.php` y pasa `totalOfertas`:
  el botón dice «Ver todas las promociones (N)» cuando no caben todas.
- Pruebas: `PromocionesPublicasTest` (5).
**Receta sin cédula, temporal (H-102, 25/11/2026).** `config('dentissa.recetas.exigir_cedula')`
(`DENTISSA_RECETA_EXIGE_CEDULA`, por omisión `false`). Apagado,
`HistorialClinicoController::receta` imprime aunque quien atendió no tenga cédula y
`pdf/receta.blade.php` omite sus dos líneas. Para volver a exigirla, se pone `true` en
Railway; no hay que tocar código. Prueba: `RecetaPdfTest`.

**Ficha del negocio en Google Maps (H-103, 25/11/2026).** Con solo la dirección, Maps
marcaba un punto en la calle. Nueva columna `datos_consultorio.nombre_maps` (migración
`2026_11_25_000002`, nullable, 100) y `config('dentissa.contacto.nombre_maps')`
(`DENTISSA_MAPS_NOMBRE`). `partials/portada-ubicacion` busca «nombre, dirección» en el
mapa embebido y en «Cómo llegar»; vacío, solo la dirección. Se edita en Configuración →
Datos del consultorio y queda en la bitácora. Prueba: `DatosConsultorioTest`.

**Menú y módulos ocultos para la presentación (H-104, 25/11/2026).**
- Interruptores nuevos en `config('dentissa.modulos')`, **apagados por omisión**:
  `asistente_agenda` (`DENTISSA_MODULO_ASISTENTE_AGENDA`), `mensajes`
  (`DENTISSA_MODULO_MENSAJES`; la portada no tiene formulario que llame a
  `contacto.store`) y `dos_pasos` (`DENTISSA_MODULO_DOS_PASOS`). Se apoyan en
  `Permisos::rolPuede()`: apagado, nadie tiene el módulo, el menú no lo muestra y la ruta
  da 403.
- `config/permisos.php`: `asistente-agenda.*` sale de `agenda` a su propio módulo y
  `dos-pasos.*` sale de `cuenta`. `User::debeUsarDosPasos()` exige además `dos_pasos`,
  para que un `DENTISSA_2FA_ADMIN=true` con el módulo apagado no deje al panel en un ciclo.
- Menú: Inicio · Agenda · Pacientes · Administración · Catálogo (Especialidades,
  Tratamientos) · Sitio web · Configuración. Títulos alineados: `citas.index` → «Agenda»,
  pacientes → «Pacientes», servicios → «Especialidades»; tarjetas del inicio iguales.
- `ConfiguracionController`: «Próximos bloqueos» paginados (`BLOQUEOS_POR_PAGINA = 5`,
  parámetro `bloqueos`, ancla `#bloqueos`).
- `phpunit.xml` enciende los tres módulos para el resto de la suite; `MenuDemoTest` (4)
  los revisa apagados, como en producción.

**QA del panel en producción (H-98, 25/11/2026).** Hallazgos corregidos:

- **Avisos de testimonio:** `TestimonioController` (aprobar, rechazar, eliminar) da
  por atendidos los avisos `testimonio_nuevo` cuando ya no queda ninguno pendiente.
- **Celulares imposibles:** `App\Rules\TelefonoMexico` rechaza números que empiezan
  con 0 o 1 o que son un dígito repetido (se guardó un «0000000000»). Se aplica en
  nuevo y editar paciente, agenda en línea, Telegram y datos del consultorio.
- **Tasa de ocupación:** `ReporteService::ocupacion()` comparaba citas *completadas*
  con espacios de 30 min (daba 0.6 %). Ahora: minutos de citas no canceladas entre
  minutos de atención (horario × pacientes a la vez), sin los días bloqueados
  completos. Devuelve `capacidad_total` (minutos), `minutos_ocupados` y
  `citas_atendidas`; Reportes muestra «N h con citas de M h de atención».
- **Duración obligatoria:** un tratamiento necesita de 15 a 480 minutos; con 0 la
  cita no ocupaba tiempo y se podían encimar varias.
- **Nueva cita:** pacientes en orden alfabético, enlace «Registrar paciente nuevo»
  (si puede `pacientes`) y aviso cuando la especialidad no tiene tratamientos.
- **Agenda:** el nombre abre el expediente (solo con módulo `pacientes`) y el
  teléfono es un enlace `tel:`.
- **Botón dentro de enlace:** 26 lugares usaban un botón dentro de un `<a>` (HTML
  inválido). Componentes `x-boton-enlace` y `x-boton-cancelar`; una prueba revisa
  todas las vistas para que no vuelva.

Pruebas: `QaPanelProduccionTest` (10).

**Citas «En curso» olvidadas (H-99, 25/11/2026).** Una cita solo pasa a «En curso»
cuando alguien presiona Iniciar con el paciente presente; si al día siguiente
sigue así, se atendió y nadie presionó Completar. Antes la agenda solo mostraba un
aviso ámbar y el día contaba mal en Reportes.

- Comando `dentissa:cerrar-citas-en-curso` (`--simular` solo lista): pasa a
  `Completada` las citas `EnCurso` de días anteriores. Las de hoy no las toca. El
  `update` vuelve a filtrar por estado, así que no pisa un cambio hecho al mismo
  tiempo. Sin pendientes no hace nada (se puede correr las veces que sea).
- Avisa con `alertaSistema` (evento `citas_en_curso_cerradas`, mensaje limitado a
  255 caracteres por MySQL) a quien tiene el módulo de configuración.
- Programado en `routes/console.php` cada día a las 00:05 (`withoutOverlapping`) y
  ejecutado al arrancar en `scripts/start-production.sh`, antes de `supervisord`;
  si falla, el arranque sigue.
- No queda en la bitácora: `AuditoriaService::registrar` solo registra acciones de
  usuarios; el rastro es el aviso en la campana.

Pruebas: `CerrarCitasEnCursoTest` (4).
- Las clases `avatar-tono-N` y `aviso-tono-*` se escriben fuera de `@layer` en
  `app.css`: se arman en PHP y Tailwind eliminaría las que no ve escritas.

`NotificacionSistemaCreada` transmite cada aviso inmediatamente mediante
Laravel Reverb y Echo, por el canal privado `users.{id}`. La autorización del
canal exige que la sesión pertenezca a la misma cuenta activa de admin o
asistente; el evento transmite título, mensaje, vínculo, contador y el
fragmento del panel (nombre y teléfono del paciente para el avatar y el botón
**Llamar**, que el personal ya puede ver en la agenda), nunca datos clínicos. Además del WebSocket, el navegador consulta
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

**Registrar paciente sin cita (H-83, 24/11/2026).** `admin.pacientes.create/store` (módulo `pacientes`). Guarda el celular normalizado a 10 dígitos (igual que la agenda en línea) y rechaza uno ya registrado (ofrece abrir ese expediente) y un correo repetido. Exige `acepta_aviso` y llena `aviso_privacidad_aceptado_en`/`_version` con `forceFill`. Bitácora: `paciente.registrado`. Redirige al expediente. Pruebas: `PacienteNuevoTest`.

**Receta en PDF (H-84, 24/11/2026).** `users.cedula_profesional` y `users.especialidad` (Mi perfil, solo con el módulo `pacientes`). `HistorialClinicoController@receta` (`admin.pacientes.historial.receta`): exige medicamentos y cédula de `id_doctor`, toma edad y alergias de la historia clínica, registra `receta.generada` y devuelve `pdf.receta` en tamaño carta con `stream()` (se abre en el navegador para imprimir). Pruebas: `RecetaPdfTest`.

**Consentimiento informado (H-85, 24/11/2026).** Tabla `consentimientos` (paciente, doctor, procedimiento, dientes, beneficios, riesgos, alternativas). `ConsentimientoController` (`admin.pacientes.consentimientos.create/store/pdf`, módulo `pacientes`); textos de partida en `Consentimiento::BENEFICIOS/RIESGOS/ALTERNATIVAS`. `pdf.consentimiento` sigue los elementos de la NOM-004-SSA3-2012 (firmas de paciente o representante, odontólogo y dos testigos); se firma a mano, sin firma digital. Bitácora `consentimiento.creado` sin el texto clínico. Pruebas: `ConsentimientoTest`.

**Agenda para la asistente (H-86, 24/11/2026).** `config/permisos.php`: `asistente => [inicio, agenda, notificaciones, historia_clinica]`. Con eso ve Agenda, Asistente de agenda y la campana, y recibe los avisos internos (`Permisos::rolesCon(notificaciones)`). "Registrar consulta" en la Agenda sigue oculto para ella porque pide el módulo `pacientes`.

**Pagos e ingresos (H-87, 24/11/2026) — oculto.** El usuario decidió no usarlo: `config('dentissa.modulos.pagos')` (`DENTISSA_MODULO_PAGOS`, `false` por omisión). `Permisos::rolPuede()` niega cualquier módulo de `permisos.modulos` cuyo interruptor en `dentissa.modulos` esté apagado, así desaparecen el menú, la sección del expediente y el botón de la Agenda, y las rutas responden 403. Las tablas se conservan. Tabla `pagos` (paciente, cita opcional, monto, método `efectivo|tarjeta|transferencia`, fecha, concepto, `registrado_por`). Módulo nuevo `pagos` (`admin.pagos.index/create/store/destroy`) para `super_admin` y `doctor`. `Cita::precioEstimado()` usa `precio_referencia` del tratamiento y `Oferta::precioPara()`; `PagoController::resumenPaciente()` da pagado, estimado y por cobrar para el expediente. En la Agenda, una cita completada sin pagos muestra "Registrar pago". Bitácora: `pago.registrado`, `pago.eliminado`. Pruebas: `PagosTest`.

**Plan de tratamiento (H-88, 24/11/2026).** Tablas `planes_tratamiento` (paciente, doctor, título, estado `activo|terminado|cancelado`, notas) y `plan_tratamiento_pasos` (descripción, dientes, costo, `id_tratamiento` si coincide con el catálogo, `hecho_en`, orden). `PlanTratamiento::avance()` = pasos con `hecho_en` / total; no se escribe a mano. `PlanTratamientoController` (`admin.pacientes.planes.*`, módulo `pacientes`). Bitácora: `plan.creado`, `plan.actualizado`, `plan.paso_hecho`, `plan.paso_pendiente`. Pruebas: `PlanTratamientoTest`.

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

Las pruebas de cada pantalla **no** detectan si falta la transacción, porque
`RefreshDatabase` ya corre cada prueba dentro de una. Por eso
`tests/Unit/BitacoraEnTransaccionTest` lee el código de `app/` y falla si una
función llama a `$auditoria->registrar()` sin abrir `DB::transaction()` (H-92,
24/11/2026: nueve funciones de bloqueos, consentimientos, recetas, pagos, planes
y exploración clínica fallaban en local y en Railway).
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

## 13. Autorización por rol (RBAC, 22/11/2026)

**Tres roles** (`App\Enums\RolUsuario`): `super_admin` (la doctora dueña, "Doctora
titular"), `doctor` (doctores asociados) y `asistente`. Antes eran `admin` y
`asistente`; la migración `2026_11_22_000003_roles_super_admin_doctor_asistente`
cambia `users.rol` de `enum` a `string(20)` y pasa las cuentas `admin` a `super_admin`.

**Un solo mapa de permisos: `config/permisos.php`.**
- `modulos`: cada módulo agrupa nombres de ruta (con comodines): `inicio`, `cuenta`
  (Mi perfil y dos pasos), `notificaciones`, `agenda`, `pacientes`, `usuarios`,
  `reportes`, `mensajes`, `control_actividades`, `catalogo`, `contenido`, `configuracion`.
- `roles`: `super_admin` → `*`.
  **25/11/2026 (H-101):** `doctor` → `inicio, cuenta, notificaciones, agenda,
  directorio_pacientes, pacientes, historia_clinica, pagos` (solo lo clínico; antes
  todo, «temporal»). `asistente` → `inicio, cuenta, notificaciones, agenda,
  directorio_pacientes, historia_clinica, pagos, mensajes` (recepción; antes no tenía
  `cuenta` y no podía cambiar su contraseña).
- Módulo nuevo `directorio_pacientes`: `admin.pacientes.index|create|store|edit|update`
  (lista, alta y datos de contacto). Va **antes** de `pacientes`, que se queda con el
  expediente (historial, recetas, planes, exploración, consentimientos), porque
  `moduloDeRuta()` devuelve el primero que coincide. El menú y el panel de inicio
  enlazan Pacientes con `directorio_pacientes`; la lista oculta los enlaces al
  expediente sin `pacientes`; `PacienteController::store` lleva a quien no tiene
  expediente al cuestionario (`admin.pacientes.historia.edit`).
- `RolUsuario::descripcion()` explica cada rol en Usuarios → Nuevo usuario
  (`data-permisos-rol`). Las alertas del sistema (`configuracion`) ahora llegan solo a
  la titular. Los dos pasos siguen obligatorios solo para quien tiene `pacientes`.
- Pruebas: `PermisosPerfilesTest` (4) y las de permisos existentes actualizadas.
- Para quitarle algo a `doctor` o darle algo a `asistente`, se edita su lista; no hay
  que tocar rutas, menús ni controladores.

**Quién lo lee:** el middleware `permiso` (`App\Http\Middleware\VerificarPermiso`,
sustituye a `role:admin`) en el panel de inicio, en el grupo autenticado y en
`/admin`: busca el módulo de la ruta actual (`App\Support\Permisos::moduloDeRuta()`)
y niega con 403 si el rol no lo tiene. **Una ruta protegida sin módulo se niega a
todos** (falla cerrado); `PermisosPorModuloTest` revisa que no quede ninguna.
`User::puede($modulo)` lo usan el menú (`layouts/navigation`), el panel de inicio,
`CitaPolicy` (módulo `agenda`), `HistorialImagenController` (`pacientes`), el canal
en vivo (`notificaciones`) y `User::debeUsarDosPasos()` (quien tiene `pacientes`).
`Permisos::valoresCon($modulo)` decide a quién llegan los avisos internos
(`notificaciones`) y las alertas del sistema (`configuracion`).

**Protección de la cuenta titular:** solo una `super_admin` crea otra `super_admin`
y desactiva o elimina una cuenta `super_admin` (`UsuarioController::rolesAsignables()`
y `protegerSuperAdmin()`). No da permisos nuevos a nadie: evita que un `doctor`
(que conserva Usuarios) bloquee o borre a la dueña.

El PDF del historial vuelve a la ficha con un aviso cuando aún no hay consultas; las
notas `notas_doctor` no se renderizan en ese PDF. Pruebas: `PermisosPorModuloTest`
(mapa completo, matriz de los 3 roles, menú, cuenta titular) y `PermisosPorRolTest`.

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
sobrescribe la base activa. Estos scripts quedan para respaldos manuales del
entorno local y no se ensayaron (QA-31 y QA-32 diferidos); en el servidor se
usa el respaldo automático de la sección 14.1, ensayado en la 14.2.

### 14.1 Respaldo automático cifrado a Google Drive (servidor)

**Analogía:** cada noche se saca una copia de los expedientes, se mete en una
caja fuerte con candado y se guarda en otro edificio. Si se pierde el
consultorio, la copia sigue a salvo, y sin la llave nadie puede abrirla.

**Flujo** (`dentissa:respaldar`, diario a las 03:00 con el scheduler):

```
base activa ──> VolcadoBaseDatos ─┐
  SQLite: VACUUM INTO             │
  MySQL: volcado PDO en una       ├─> respaldo.zip + manifiesto.json (SHA-256 y conteos)
  instantánea consistente         │            │
storage/app/private (sin las      ┘            ▼
credenciales google-oauth-*.json)    CifradoRespaldo (XChaCha20-Poly1305)
                                               │  .dntb, el .zip en claro se borra
                                               ▼
                                     GoogleDriveDestino: carpeta "Dentissa respaldos"
                                     + retención (30 días, mínimo 3 copias)
```

**Decisiones técnicas**

- **Sin `mysqldump`:** el cliente de Debian es de MariaDB y puede fallar al
  autenticarse contra MySQL 8. El volcado se hace con PDO dentro de
  `START TRANSACTION WITH CONSISTENT SNAPSHOT`, una sentencia por línea. Las
  tablas efímeras (`sessions`, `cache`, `jobs`, `password_reset_tokens`…)
  solo guardan su estructura.
- **Horas en UTC:** MySQL convierte las columnas `TIMESTAMP` según la zona
  horaria de la sesión. El volcado fija `time_zone = '+00:00'` (y lo escribe
  al inicio del archivo) para que, al restaurar en un servidor con otra zona,
  las horas no se recorran. Al terminar, la conexión vuelve a su zona.
- **Cifrado antes de salir del servidor:** `sodium` secretstream con una llave
  de 32 bytes en `DENTISSA_RESPALDO_CLAVE`. Cada bloque está autenticado: un
  byte alterado o un archivo cortado hacen fallar el descifrado. Google solo
  guarda bytes ilegibles.
- **Las credenciales nunca salen:** `google-oauth-client.json` y
  `google-oauth-token.json` se excluyen del paquete, aunque vaya cifrado.
- **Permiso mínimo en Drive:** `drive.file`. Dentissa solo ve y borra los
  archivos que ella misma creó. La subida es reanudable, para archivos grandes.
- **Token compartido:** `App\Services\Google\GoogleAccessToken` sirve al
  correo y a Drive. Hay que volver a autorizar la cuenta con
  `scripts/google-oauth.ps1`, que ahora pide los dos permisos, y actualizar
  `GMAIL_OAUTH_REFRESH_TOKEN` en Railway.
- **Retención:** borra las copias con más de `DENTISSA_RESPALDO_DIAS` (30)
  días, pero conserva siempre las 3 más recientes, aunque el servidor haya
  estado detenido.
- **Alerta:** si algo falla, cada administrador activo recibe "Respaldo
  fallido" en la campana (destacado) y por correo. El log guarda solo el
  mensaje del error.
- **Destino intercambiable:** la interfaz `DestinoRespaldo` (Drive hoy, uno en
  memoria en las pruebas). Si la clínica paga otro almacenamiento, basta con
  otra implementación registrada en `AppServiceProvider`.

**Restauración de prueba** (`dentissa:restaurar-prueba`, o con
`--archivo=ruta.dntb`): descarga la copia más reciente, la descifra, comprueba
la huella SHA-256 de la base y de cada archivo, y carga la base en un lugar
aislado. En SQLite abre la copia aparte y corre `PRAGMA integrity_check`; en
MySQL la importa en el esquema `<base>_restauracion_prueba`, cuenta los
registros y vacía sus tablas. El esquema se conserva vacío: en un servidor
real el usuario de la aplicación suele tener permisos sobre una sola base, así
que, si no puede crearlo, el comando muestra el `CREATE DATABASE` y el `GRANT`
exactos que debe ejecutar una vez un administrador de MySQL (el CI hace lo
mismo con `root`). Los conteos deben coincidir con el manifiesto. **Nunca
toca la base ni los archivos activos.**

**Configuración en Railway**

1. En Google Cloud, en el mismo proyecto de las credenciales OAuth, habilitar
   **Google Drive API** ("APIs y servicios" > "Biblioteca"). Sin ella Drive
   responde 403 `accessNotConfigured` y el comando lo explica.
2. Autorizar la cuenta con `scripts/google-oauth.ps1` (permisos `gmail.send` y
   `drive.file`) y copiar el refresh token nuevo a `GMAIL_OAUTH_REFRESH_TOKEN`.
3. Generar la llave:

```bash
php artisan dentissa:respaldo-llave
```

Genera la llave: guárdala en un gestor de contraseñas y en
`DENTISSA_RESPALDO_CLAVE`. **Si se pierde, los respaldos no se pueden
recuperar.** Opcionales: `DENTISSA_RESPALDO_CARPETA` y `DENTISSA_RESPALDO_DIAS`.

**Validar:**

```bash
php artisan dentissa:respaldar
```

```bash
php artisan dentissa:restaurar-prueba
```

Hay que repetir la restauración de prueba al menos una vez al mes.

**Pruebas:** `RespaldoTest` (10 pruebas): paquete cifrado sin datos en claro ni
credenciales; restauración que no toca la base activa; archivo alterado o llave
distinta rechazados; retención con mínimo de 3; alerta solo al administrador y
sin temporales; horario 03:00; subida reanudable a Drive con `Http::fake`;
mensaje claro si falta el permiso de Drive o si la API está desactivada; y
volcado MySQL en UTC (solo corre en MySQL; en SQLite se omite). En el job de
MySQL del CI las mismas pruebas ejercitan el volcado PDO y el esquema aislado.
Ensayo local el 16/11/2026 sobre una copia de SQLite: respaldo de 12.5 KB,
restauración con conteos idénticos, 0.3 s y sin temporales.

### 14.2 Ensayo con Google Drive real y datos ficticios

**Por qué así:** la base local tiene pacientes reales, y un ensayo no debe
subirlos a Drive ni arriesgar la base activa. Se usa una base y una carpeta de
archivos de mentira, una llave temporal y una carpeta de Drive aparte.

1. Con el usuario `root` de MySQL (una sola vez), crear `dentissa_ensayo` y
   `dentissa_ensayo_restauracion_prueba` y dar `GRANT ALL` sobre ambas al
   usuario de la aplicación.
2. Migrar `dentissa_ensayo` y cargar datos ficticios (correos
   `@example.invalid`, textos con acentos, comillas y saltos de línea).
3. Ejecutar los comandos con variables de entorno del proceso, sin tocar
   `.env`: `DB_DATABASE=dentissa_ensayo`, `DB_HOST=127.0.0.1`,
   `DB_PORT=3307`, `DENTISSA_RESPALDO_DIRECTORIO=<carpeta con archivos
   ficticios>`, `DENTISSA_RESPALDO_CARPETA="Dentissa respaldos ENSAYO"`,
   `DENTISSA_RESPALDO_CLAVE=<llave temporal>` y `MAIL_MAILER=log`. Antes de
   respaldar, comprobar con `DB::connection()->getDatabaseName()` que apunta a
   `dentissa_ensayo`.
4. `php artisan dentissa:respaldar` y luego `php artisan
   dentissa:restaurar-prueba`.

`DENTISSA_RESPALDO_DIRECTORIO` existe solo para estos ensayos; en el servidor
se deja vacía y se respalda `storage/app/private`.

**Resultado del 18/11/2026 (MySQL 8.4 en Docker):** 2 usuarios, 26 pacientes,
50 citas, 25 historiales, 5 imágenes y 2 archivos privados. Respaldo cifrado de
256.6 KB subido en 11 s; el token OAuth falso de la carpeta quedó fuera. La
restauración desde Drive tardó 13 s con conteos y huellas idénticos. Una
verificación independiente descargó la copia, confirmó que no hay texto en
claro, la importó en el esquema aislado con la sesión en −06:00 y comparó con
`CHECKSUM TABLE`: 18/18 tablas idénticas y archivos iguales byte a byte. La base
activa no registró escrituras durante el ensayo.

**Problemas encontrados y su arreglo:** el token anterior solo tenía permiso de
Gmail (se reautorizó), la Google Drive API estaba desactivada en el proyecto (se
habilitó; ahora el error lo indica) y las horas `TIMESTAMP` dependían de la zona
de la sesión (el volcado ahora fija UTC).

### 14.3 Primera ejecución en Railway

Desde la pestaña **Console** del servicio `dentissa` (terminal dentro del
contenedor, en `/var/www/html`), el 18/11/2026, con el despliegue del PR #54:

1. `php artisan dentissa:diagnostico --remoto` marcó que Google no entregaba
   token: el `GMAIL_OAUTH_REFRESH_TOKEN` de Railway había caducado. Se copió
   el nuevo al portapapeles sin mostrarlo:

   ```powershell
   (Get-Content 'C:\laragon\www\dentissa\storage\app\private\google-oauth-token.json' -Raw | ConvertFrom-Json).refresh_token | Set-Clipboard
   ```

   Se pegó en la variable, se volvió a desplegar y se limpió el portapapeles
   con `Set-Clipboard -Value ' '`. El diagnóstico quedó en 0 errores.
2. `php artisan dentissa:respaldar` subió la primera copia real en 2.2 s.
3. `php artisan dentissa:restaurar-prueba` la restauró en el esquema aislado
   con conteos idénticos en 4.7 s, sin tocar la base activa.
4. `php artisan schedule:list` muestra el respaldo a las 03:00 (hora de
   México); supervisord mantiene `schedule:work` encendido.

5. El 19/11/2026 a las 03:00 (hora de México) el scheduler creó solo
   `dentissa-respaldo-20261119-030000.dntb` en la carpeta "Dentissa respaldos"
   de Drive.

**Tokens que caducan cada semana:** con la app de Google en modo "Prueba",
Google invalida los refresh tokens a los 7 días. El 18/11/2026 la app se
publicó en Google Auth Platform → "Público" → "Publicar app" (estado "En
producción"). Para publicar, "Información de la marca" exige nombre, correo de
asistencia, página principal (`https://dentissa-production-11db.up.railway.app/`)
y política de privacidad (`/aviso-de-privacidad`), con ese dominio en
"Dominios autorizados". El aviso "Tu app requiere una verificación" se puede
ignorar: es una app de una sola cuenta, y sin verificar solo se ve la pantalla
de "app no verificada" al autorizar. **Los tokens emitidos mientras la app
estaba en prueba siguen caducando**, así que después de publicar se volvió a
ejecutar `scripts/google-oauth.ps1` y se actualizó `GMAIL_OAUTH_REFRESH_TOKEN`
en Railway.

**Operación:** la llave `DENTISSA_RESPALDO_CLAVE` debe existir también fuera de
Railway (gestor de contraseñas). Repetir `dentissa:restaurar-prueba` cada mes y
revisar que la campana no muestre "Respaldo fallido".

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
chat que se despliega encima. El botón no cambia de posición con el scroll. En
pantallas menores a 640 px (`sm`), el componente escucha `scroll` en la ventana
(listener pasivo) y **se oculta al bajar y reaparece al subir**: así no tapa el
extremo derecho de **Continuar** o **Agendar cita** cuando el paciente baja
hasta esos botones (hallazgo de QA a 375 px: lo cubría unos 35 px). Al ocultarse
se desliza hacia abajo y pierde opacidad, sin recibir clics. Con
`prefers-reduced-motion` el cambio es instantáneo, y al enfocarlo con teclado
vuelve a aparecer. Abierto el chat, o desde `sm`, nunca se oculta. (La versión
anterior describía un `IntersectionObserver` que elevaba el botón; ese código ya
no existe.) El flujo de agenda usa
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
  - Vistas públicas (`welcome`, agenda pública wizard por pasos, galería, ofertas): tipografía Figtree y Fraunces locales precargadas (ver sección 26), servicios en cuadrícula y menú responsivo desplegable para móvil y tablet. En la portada, el scroll-spy de `welcome.blade.php` sincroniza enlaces de escritorio/móvil con la sección bajo el encabezado fijo mediante `aria-current="location"`; ese estado da el acento rosa únicamente al enlace activo y se actualiza en `requestAnimationFrame`.
  - Vistas de acceso (`guest`, `login`, recuperación de contraseña): tarjetas centradas de estilo boutique con fondo ambiental cálido y enlaces visibles.
  - Panel administrativo (`layouts/app`, `navigation`, `dashboard`, citas, pacientes, historial clínico con fotos/radiografías, catálogo de servicios y tratamientos, horarios, usuarios, reportes, auditoría, mensajes, testimonios, casos y preguntas frecuentes): tablas con encabezados claros en `#FAF6F6`, badges de estado legibles, filtros organizados y scroll horizontal en móviles (`min-w-[760px]`).
  - Total preservación de lógica de negocio, nombres de campos, CSRF, validaciones y roles. La suite de pruebas completa pasó con 158 pruebas aprobadas y 0 fallos.


### 19.1 Reglas de consistencia (barrido del 19/11/2026)

Un barrido de 41 pantallas a 375 y 1280 px dejó cuatro reglas, protegidas por
`ConsistenciaDisenoTest` para que no vuelvan a romperse:

- **Un solo `<h1>` por pantalla.** Los formularios del panel usaban `<h2>` con
  el mismo estilo de `x-admin.header`; ahora son `<h1>` (mismo aspecto, pero los
  lectores de pantalla encuentran el título).
- **Campos de 44 px (`min-h-11`).** Todo `input` o `select` con borde de campo
  mide lo mismo en filtros y formularios; antes había cinco alturas (34–48 px).
  Las acciones de fila que se apilan en celular (Historial, Editar en
  Pacientes) también miden 44 px y vuelven a su tamaño compacto desde `lg`.
- **Solo colores de la paleta.** Ninguna vista usa `gray`, `blue`, `green`,
  etc. de Tailwind; se usan los tokens de marca.
- **Campos obligatorios con asterisco, sin desactivar el botón.** El botón de
  guardar nunca se desactiva (un botón gris no dice qué falta); la etiqueta de
  cada campo con `required` lleva `<x-input-label :required="true">` (asterisco
  rojo y "(obligatorio)" para lectores de pantalla) y el formulario abre con
  `<x-form-obligatorios />`. Si falta algo, el error aparece junto al campo al
  enviar. Excepción: formularios de un solo campo con etiqueta invisible.
- **Confirmaciones con el diálogo propio, nunca `confirm()`/`prompt()`.** Un
  formulario pide confirmación declarando `data-confirmar="Mensaje"` (y, si
  hace falta, `data-confirmar-titulo`, `-boton`, `-tono="peligro|normal"` y
  `-motivo="campo"` para pedir un texto). `components/dialogo-confirmar` vive
  una sola vez en `layouts/app` y `app.js` intercepta el envío en fase de
  captura. Cada botón actúa en su propio clic: no se usa el evento `close` del
  `<dialog>`, que algunos navegadores no disparan.
- **Ofertas: "Beneficio para el paciente" (21/11/2026).** El formulario envía
  `beneficio` (`descuento|precio|gratis`), `unidad` (`porcentaje|monto`, solo
  con descuento) y `cantidad`; `Oferta::claveDeFormulario()` lo convierte a la
  clave interna (`porcentaje|monto|precio|gratis`) y `Oferta::columnasDeBeneficio()`
  lo traduce a las columnas de siempre (`tipo`, `tipo_valor`, `valor_descuento`,
  `precio_especial`), sin migración. `beneficio()`, `cantidadBeneficio()` y
  `etiquetaBeneficio()` son la única regla para leerlo: la usan el formulario
  de edición, la portada, la lista del panel y `/ofertas`. Lo opcional
  (descripción, tratamiento, cupo, inicio, imagen) va en un `<details
  data-mas-detalles>` que se abre solo si ya tiene datos o errores. En la
  portada las ofertas son tarjetas compactas en cuadrícula (4 por fila en PC),
  sin carrusel.
- **Ofertas: revisión antes de publicar (21/11/2026).** La columna `activa`
  (booleano) se reemplazó por `estado` (`App\Enums\EstadoOferta`:
  `pendiente` = En revisión, `activa` = Publicada, `inactiva` = Oculta) con la
  migración `2026_11_21_000001_cambiar_activa_por_estado_en_ofertas` (las
  existentes conservan cómo se veían). `store` siempre guarda `pendiente` y
  `fecha_inicio` = hoy (sin campos "Empieza" ni "Mostrar"); `update` regresa a
  `pendiente` una publicada si cambia algo de `CAMPOS_PUBLICOS`. Rutas
  `PATCH admin.ofertas.publicar` (rechaza vencidas) y `admin.ofertas.ocultar`,
  ambas auditadas (`oferta.publicada`, `oferta.ocultada`). El público (portada y
  `/ofertas`) usa solo `Oferta::publicadas()`. Validación: título 5–120,
  descripción obligatoria 20–300, `fecha_fin` ≥ hoy, y la regla
  `App\Rules\SinEnlaces` (enlaces, dominios, correos, HTML y caracteres
  repetidos) en título y descripción; el formulario repite `required`,
  `minlength`, `maxlength` y `min`. Solo el rol admin entra a estas rutas, así
  que no hay "creador" por oferta: todo administrador ve todas en el panel.
- **Ofertas en la cita (21/11/2026).** Migración
  `2026_11_21_000002_add_id_oferta_to_citas_table` (`citas.id_oferta`, FK
  `nullOnDelete`). `CrearCitaService` (web y Telegram) llama a
  `Oferta::aplicableA(idTratamiento, fecha)` (publicada, tratamiento agendable,
  fecha de la cita ≤ `fecha_fin`, con lugar; la que vence primero) y bloquea la
  fila con `lockForUpdate()` para no vender el último lugar dos veces. Los
  lugares se cuentan con citas reales: `scopeConLugaresUsados()`,
  `lugaresUsados()`, `lugaresQuedan()`; canceladas y no asistidas liberan
  (`ESTADOS_QUE_LIBERAN`). La columna `cupos_utilizados` ya no se usa.
  `Tratamiento::scopeAgendables()` (activo + especialidad activa) filtra el
  selector, la validación (`Rule::in`), `publicadas()` y `publicar()`. La agenda
  pública recibe `ofertasPorTratamiento` y `?tratamiento=ID`; el personal ve
  `x-cita-oferta` y la notificación interna agrega "Con oferta «…»". Descuento
  nuevo solo en % (la unidad `monto` se acepta solo al editar una oferta vieja).
- **"Guardar cambios" espera cambios (21/11/2026, global en `app.js`).** Todo
  `<form>` con `_method` PUT/PATCH y al menos un campo editable empieza con sus
  botones de enviar desactivados (también los de fuera con `form="id"`) y se
  activan cuando la huella de `FormData` difiere de la inicial; si se regresa al
  valor original se desactivan otra vez. No aplica a formularios de un solo
  botón ni si la página volvió con errores; `data-sin-esperar-cambios` lo
  excluye. El diálogo de confirmación se conserva: el botón evita guardar
  "nada" y el diálogo evita guardar por accidente.
- **Un solo botón para regresar (21/11/2026).** Los formularios ya no tienen
  "Volver al listado" arriba y "Cancelar"/"Volver" abajo: queda "Cancelar"
  junto a "Guardar" (en Servicios y Tratamientos, en PC ambos van arriba).
  `BotonesFormularioTest` lo vigila.
- **Validación de grupos de opciones (22/11/2026).** `x-opcion` acepta
  `requerido` (atributo `required` en el radio). La validación global de
  `app.js` pone un solo aviso al final del `<fieldset>` para los radios (antes
  no se contemplaban: Beneficio no avisaba al guardar vacío) y al elegir uno
  limpia `aria-invalid` de todo el grupo; `.opcion:has([aria-invalid])` pinta el
  borde rosa. En Ofertas, "Tratamiento al que aplica" salió de "Más detalles"
  porque decide si la oferta se aplica al agendar.
- **Ayuda con ícono global (21/11/2026).** `x-ayuda` (`tono="aviso|info"`,
  `etiqueta`): símbolo junto a un dato que muestra un globo al pasar el mouse,
  enfocar con teclado o tocar en celular (el `title` del navegador no funciona
  en táctil). El globo es `position: fixed` (no lo recortan las tablas que se
  desplazan), se abre abajo o arriba según el espacio y se cierra con Esc, al
  tocar fuera o al desplazar. Primer uso: "Tratamiento inactivo" en Ofertas.
- **Tablas largas: esquina fija (21/11/2026).** El encabezado de la primera
  columna es `top: 0` + `left: 0` con `z-index: 3` y el color del encabezado;
  antes tenía `z-index: 1`, otro tono, y al bajar la primera fila se montaba
  encima. La celda fija toma el tono de la fila al pasar el mouse.
- **Opción elegible global (21/11/2026).** `x-opcion` (componente) + clase
  `.opcion` en `resources/css/app.css`: radio oculto dentro de un `<label>`;
  elegida = `brand-50` de fondo, borde `brand-400`, texto `brand-800`, sin
  palomita. También aplica a `<button aria-checked|aria-pressed="true">`. Se usa
  en Beneficio y en la unidad % / $ de Ofertas. Pendiente decidir si unifica
  los horarios de la agenda (relleno `brand-600`) y las tarjetas de tratamiento.
- **Todo cambio pide confirmación (21/11/2026).** Cualquier `<form method="POST">`
  del panel lleva `data-confirmar` (tono `normal` si se puede deshacer,
  `peligro` si no). Los botones de fila que cambian algo usan
  `x-admin.boton-fila`, que siempre confirma. `ConsistenciaDisenoTest`
  falla si aparece uno sin confirmación; excepciones: `asistente-agenda.proponer`
  (no guarda), `dos-pasos.confirmar` (el código es la confirmación) y
  `verification.send`.
- **Toda acción avisa qué pasó.** `components/avisos-sesion` (en `layouts/app`)
  pinta `session('success')` y `session('error')` en todo el panel, abajo a la
  derecha (los avisos de notificaciones nuevas salen arriba). El de éxito se va
  a los 6 s; el de error se queda hasta cerrarlo. Cada acción de crear, guardar,
  eliminar, aprobar o activar envía un mensaje que dice qué y a cuál («Tratamiento
  «Valoración» creado.»). Las pantallas no pintan copias propias.
- **En PC la pantalla se adapta al alto de la ventana, no a px fijos.** (20/11/2026,
  agenda en línea.) Se mide con la ventana real del usuario (1920×1080 al 125 %
  con barra de marcadores ≈ 1536×748), no con el alto del monitor. Patrón: el
  `<main>` mide `calc(100dvh - barra superior)`, la tarjeta `max-h-full flex
  flex-col` crece con su contenido hasta ese tope, el cuerpo del paso es
  `flex-1 min-h-0 overflow-y-auto` y la barra de botones `sticky` con
  `-bottom-6` (compensa el relleno). Si el contenido no cabe (pantallas bajas o
  listas largas), se desplaza solo el interior y los botones siguen a la vista.
  Textos de ayuda secundarios se ocultan con `lg:[@media(max-height:820px)]:hidden`.
  En celular el flujo es normal con la barra fija abajo.
- **Todo el panel se ajusta a la ventana en PC (20/11/2026).** Mismo patrón
  aplicado a las 41 pantallas con clases compartidas (reglas en `app.css`,
  bloques "Panel ajustado" y "Formularios del panel"):
  - `layouts/app` → `.panel-marco` (alto de la ventana) y `.panel-encabezado`.
  - Cada pantalla → `.panel-pagina > .panel-contenido`. Si algo no cabe, se
    desplaza solo `.panel-contenido`; el menú y el título quedan fijos.
  - Listas: `x-admin.table` lleva `.tabla-panel` (la lista de citas y la de
    notificaciones, `.lista-panel`). La tarjeta se encoge, la tabla se desplaza
    por dentro con encabezados fijos; filtros y paginación siempre a la vista.
  - Formularios: `.formulario-panel` en el `<form>` = 2 columnas (las filas
    de 2 campos se integran a la cuadrícula; `.formulario-3` = 3 columnas;
    `.col-completa` ocupa el ancho). La barra de botones (último hijo) queda
    pegada abajo. Nueva cita (`.formulario-cita`): datos a la izquierda y el
    calendario (`.col-fecha`) a la derecha.
  - Perfil: pestañas (una sección a la vez) en lugar de secciones apiladas.
  - Pantallas bajas (`max-height: 820px`): se ocultan descripciones
    secundarias (subtítulo de la página y de las tarjetas).
  - Verificación: se midió cada pantalla en 1536×748 (ventana real del
    usuario), 1024×700 y 375×812 con un recorrido automático en el navegador;
    `ConsistenciaDisenoTest` exige `.panel-contenido` en toda pantalla nueva.
  - Local: `PHP_CLI_SERVER_WORKERS=4` en `compose.yaml` (antes `php artisan
    serve` atendía una petición a la vez y cada página tardaba ~2 s).
- **Catálogo de componentes globales del panel (21/11/2026).** Lo que se
  repite usa un componente; `ConsistenciaDisenoTest` falla si se vuelve a
  hacer a mano:

  | Pieza | Componente | Notas |
  |---|---|---|
  | Encabezado de página | `x-admin.header` (badge, title, subtitle, slot actions) | Excepción: Editar servicio/tratamiento (ruta + Guardar arriba) |
  | Tarjeta | `x-admin.card` | `overflow-clip` (no rompe la barra de botones fija) |
  | Filtros de lista | `x-filtro.barra` + `.select` / `.buscar` / `.fecha` | Se aplican solos; activo resaltado; "Limpiar filtros" solo si hay filtro; el buscador actualiza sin recargar |
  | Campo de texto / largo / lista | `x-text-input` / `x-textarea` / `x-select` | Mismo borde, foco y alto mínimo de 44 px |
  | Botón principal / secundario | `x-primary-button` (o `.btn-brand` en enlaces) / `x-secondary-button` | Las acciones de fila de Citas son más chicas a propósito |
  | Etiqueta redonda | `x-chip` (forma) + color con significado | `x-admin.status-badge` para Activo/Inactivo |
  | Acciones de fila, vacío, paginación, avisos, confirmar | `x-admin.acciones-fila`, `x-admin.boton-fila` (nuevo), `x-admin.empty-state`, `x-pagination-summary`, `x-avisos-sesion`, `x-dialogo-confirmar` | Ya eran globales |

  Se quedan únicos (un solo uso): portada y agenda pública (estilo para
  pacientes), bienvenida de Inicio, pestañas de días de Citas; las tarjetas
  `<section>`/`<article>` conservan las mismas clases que `x-admin.card`.
- **Validación del navegador con el diseño propio.** `app.js` escucha `invalid`
  en captura, cancela la burbuja genérica y escribe el error en rojo debajo del
  campo (o de su envoltorio `.relative`), con `aria-invalid` y foco en el primero.
  Usa `validity.valid` para limpiar: `checkValidity()` volvería a disparar el evento.
- **Sin precio se guarda, pero no se publica.** Un tratamiento activo siempre
  tiene precio: se puede crear o guardar sin `precio_referencia` (queda inactivo,
  y si le quitan el precio a uno activo se desactiva), el interruptor de la lista
  (`tratamientos.estado`) no activa sin precio y la migración
  `2026_11_20_000001` desactivó los activos sin precio. Por eso las pantallas
  públicas solo filtran por `activo`. El precio se muestra en la agenda en línea
  (`formatoPrecio`, MXN sin decimales si es entero) y en el resumen.
  `ServiciosTratamientosSeeder` trae precios **de demostración**.
- **Campos sin uso fuera de los formularios.** "Requiere autorización",
  "Imagen", "Sesiones", "Descripción" y "Categoría" de tratamientos no los usaba
  ninguna parte del sistema; se quitaron del panel y las columnas se conservan.
  La relación servicio–tratamiento se edita solo en el servicio.
- **Cuadrículas con columna explícita.** Una `grid` sin `grid-cols-1` crece al
  ancho mínimo de su contenido: en el perfil, la fila de pestañas desplazable
  ensanchaba la página 120 px en celular. `grid-cols-1` (`minmax(0, 1fr)`) y
  `min-w-0` en el hijo lo evitan.

**Solo web, sin app móvil (H-80, 24/11/2026).** Dentissa se entrega como sistema web. La API
del PR #80 (`routes/api.php`: login de la app Ionic y rutas `/api/iot/*` del ESP32) se quitó
junto con `App\Http\Controllers\Api\AppAuthController` y su registro en `bootstrap/app.php`:
no pedía ninguna clave (`POST /api/iot/puerta/abrir` dejaba abrir la puerta a cualquiera), el
login no tenía límite de intentos, se saltaba la verificación en dos pasos y solo aceptaba los
roles viejos `admin`/`asistente`. `SinApiMovilTest` cuida que no vuelva sin querer. Si se
retoma la app, debe volver con token por usuario (Sanctum), clave por dispositivo para el
ESP32, límite de intentos y los roles de config/permisos.php. La tabla `historial_asientos`
que usaba no tiene migración en el repositorio y no se tocó.

## 20. Decisión de diseño: el paciente agenda sin cuenta

**Estado:** aceptada.

**Contexto.** La versión inicial del SRS (RF01 y RF02), el pizarrón y las
diapositivas de requerimientos proponían que el paciente se registrara e
iniciara sesión para agendar, consultar, reprogramar y cancelar sus citas.

**Decisión.** El paciente no tiene cuenta. Agenda desde el sitio público
(`/agendar-cita`) eligiendo servicio, tratamiento, fecha y hora disponible, y
captura nombre, apellido, teléfono y correo. Responde a su recordatorio con un
enlace firmado: Asistiré, Cancelar o Reagendar (sección 22, RF12). El
administrador y el asistente gestionan los demás cambios desde el panel
(`/citas`).

**Motivos.**

1. **Menos fricción:** el paciente agenda en pocos pasos, sin crear una cuenta
   ni recordar una contraseña. Muchos pacientes agendan una o dos veces al año.
2. **Menos riesgo de seguridad:** no se guardan contraseñas de pacientes y no
   existe un acceso público a información clínica.
3. **Privacidad del historial:** el historial clínico solo se consulta desde el
   panel con rol `admin`, como piden los requerimientos.
4. **Control de la agenda:** el paciente solo puede confirmar o liberar su
   horario. Una nueva fecha siempre pasa por la agenda en línea, que solo
   ofrece horarios libres, o por el personal, lo que evita empalmes.

**Consecuencias.**

- El RF01 aplica solo al personal (`admin` y `asistente`) y el RF02 se
  redefine como agendado público. Se agrega el RF12 (recordatorio y respuesta
  del paciente).
- El paciente no puede consultar su historial. Confirma, cancela o reagenda
  solo desde el recordatorio; para cualquier otro cambio contacta a la clínica
  por **teléfono o WhatsApp** (decidido el 16/11/2026). Ambos datos salen de
  `config('dentissa.contacto')` (`DENTISSA_TELEFONO`, `DENTISSA_WHATSAPP`); su
  valor por defecto es el número que publica la portada, para que ninguna página
  muestre un número de ejemplo. `telefono_enlace` arma el `tel:` con +52, y una
  variable vacía también usa el valor por defecto (`ContactoClinicaTest`).
  Desde el 22/11/2026 el valor por defecto es el celular del consultorio,
  **755 113 1833** (antes 755 102 0089); es distinto del número del bot de
  WhatsApp, como pide el diagnóstico remoto.
- **Datos del consultorio editables (22/11/2026).** Tabla `datos_consultorio`
  (una fila: `telefono`, `whatsapp`, `correo`, `direccion`), modelo
  `DatosConsultorio` con caché (`datos_consultorio`, se borra al guardar).
  `AppServiceProvider::boot()` llama a `DatosConsultorio::aplicarAConfiguracion()`,
  que pone lo guardado encima de `config('dentissa.contacto')` (incluido
  `telefono_enlace`; sin WhatsApp propio usa el teléfono con +52). Un campo
  vacío o la tabla sin migrar dejan los valores de `.env`. Guardar:
  `PUT admin.consultorio.update` (solo admin, `registrarCambio`
  `consultorio.actualizado` con valores antes/después).
- **Las pruebas nunca usan credenciales reales (22/11/2026).** `tests/TestCase.php`
  pone en `null` el token y el número de WhatsApp, el token de Telegram y las
  credenciales de Gmail, y llama a `Http::preventStrayRequests()`: una llamada
  HTTP sin `Http::fake()` hace fallar la prueba. Antes, con las credenciales
  reales en `.env`, 10 pruebas (7 archivos) mandaron WhatsApp de verdad a
  teléfonos de prueba; 3 de recordatorios usaban números al azar.
- **Barra de desplazamiento del panel (22/11/2026).** En PC `.panel-contenido`
  con `max-w-*` ocupa todo el ancho y centra su contenido con relleno lateral
  (`--ancho-contenido`): la barra queda en la orilla de la ventana y no en
  medio, junto a las tarjetas. En las listas tampoco se desplaza la tabla por
  dentro: se desplaza toda la página y la tarjeta completa sube con ella, sin
  encabezados de columna fijos (con el encabezado pegado arriba parecía que la
  tabla se desplazaba por dentro).
  En los botones redondos de solo ícono, mientras se envía, `.btn-spinner`
  ocupa el lugar del ícono (antes quedaba apilado encima).
- **Ofertas en la portada y la agenda (22/11/2026, QA con 21 ofertas).**
  `Oferta::paraPortada()` es la única regla de la portada (publicadas, con
  lugar, las que vencen primero, hasta `MAXIMO_PORTADA` = 24) y se muestra
  en el carrusel global `carruselServicios` con la descripción completa.
  `CitaPublicaController::ofertasPorTratamiento()` manda TODAS las ofertas de
  cada tratamiento y la agenda toma la primera vigente en la fecha elegida,
  igual que `Oferta::aplicableA()`. Descuento máximo 99 % (100 % = Gratis).
  `Oferta::motivoNoVisible()` explica en la lista por qué una Publicada no se ve.
- **Acciones posibles (22/11/2026).** Ningún botón de fila ofrece algo que el
  servidor rechace: `Oferta::motivoNoPublicable()` (lista y `publicar()`),
  `x-admin.acciones-fila` con `bloqueo-eliminar` (Servicios y Tratamientos con
  `citas_activas`) y la cuenta propia en Usuarios. En su lugar, `x-ayuda` ⚠ con el
  motivo (`AccionesPosiblesTest`).
- **Filtros sin sorpresas (22/11/2026).** Las listas leen sus filtros con
  `Controller::filtros($request, $reglas)`: un valor inválido se ignora (antes
  `$request->validate()` regresaba a la página anterior). Las búsquedas usan
  `whereContiene()` / `orWhereContiene()` (macro en `AppServiceProvider`): `%`,
  `_` y `!` se escapan con `ESCAPE '!'`, igual en MySQL y SQLite.
- **Filtro global en todas las listas que crecen (22/11/2026).** `x-filtro.barra`
  (buscar + estado, y tratamiento o especialidad donde aplica) en Ofertas,
  Tratamientos, Preguntas frecuentes, Casos y Galería, con `withQueryString()`
  para conservar el filtro al cambiar de página (`FiltrosListasTest`).
- **Horarios y validación al guardar (H-58, 22/11/2026).** `DisponibilidadService::horariosDisponibles()`
  ofrece un inicio si `inicio + duración <= cierre` (el margen de limpieza solo entra en `tieneCupo()`, entre
  citas). `estaDisponible()` ahora también llama a `dentroDelHorario()`: inicio ≥ apertura, fin ≤ cierre y
  minutos desde la apertura múltiplo de `dentissa.intervalo_minutos`. Toda vía que guarde citas (panel,
  agenda pública, asistente, Telegram) pasa por ahí.
- **Botones de WhatsApp no aplicables (H-59).** `WhatsappWebhookController` contesta `textoNoAplicada()`
  cuando la cita no existe o es de otro teléfono (mismo texto en ambos casos). Recordatorio: el webhook
  de Meta apunta a una sola instancia (Railway); los botones de un recordatorio enviado desde local llegan
  allá y no tocan la base local.
- **Usuarios: crear, desactivar y eliminar (H-57, 22/11/2026).** `Route::resource('usuarios')->only([index,
  create, store, destroy])` + `usuarios.activo.toggle`; ya no hay `edit`/`update` (las reglas de rol de
  H-55 quedan sin camino para romperse). `destroy()` rechaza la cuenta propia (422) y a quien tenga
  `consultasRegistradas` (`User::hasMany(HistorialClinico, id_doctor)`), porque `historial_clinico.id_doctor`
  tiene `cascadeOnDelete`. Bitácora: `usuario.eliminado`; `logs_auditoria` conserva el nombre (`actor_nombre`).
- **Contraseñas y cuenta propia (H-56, 22/11/2026).** `x-campo-contrasena` (input con 👁) y
  `x-contrasena-nueva` (par nueva/confirmar con Alpine `nueva`/`confirmar`, requisitos y aviso en
  vivo; props `prefijo`, `errores`, `requerida`, `etiqueta`, `ayuda`). Usar estas piezas en
  cualquier formulario nuevo de contraseña. `UsuarioController::edit()` de la cuenta propia
  redirige a `profile.edit`; la lista no muestra ✎ en esa fila.
- **Rol propio y último administrador (H-55, 22/11/2026).** `UsuarioController::update()` rechaza
  (`ValidationException` en `rol`) cambiar el rol de la cuenta con sesión y degradar al último
  administrador activo; la vista de la cuenta propia muestra el rol sin `<select>`. Mismo
  criterio que `toggleActivo` (no desactivarse a uno mismo).
- **Historial como ícono (H-54).** `x-icon` `expediente`; el enlace va en el slot de
  `x-admin.acciones-fila` y la cifra de Consultas también lleva al historial.
- **Acciones solo con íconos (H-53, 22/11/2026).** Regla: las acciones de fila son solo
  ícono (se quitó `texto` de `x-admin.boton-fila`); cada una con un dibujo propio de `x-icon`:
  `eye`/`eye-off` (publicar/ocultar), `thumb-up`/`thumb-down` (aprobar/rechazar),
  `mail-open`/`mail` (marcar leído/sin leer); `aria-label`/`title` y el diálogo dan el nombre.
  `x-ayuda` info vuelve a gris (`text-muted`); el amarillo queda solo para ⚠.
- **Acciones alineadas a la derecha (H-52, 22/11/2026).** Regla: el grupo de acciones y el
  encabezado "Acciones" van a la derecha (no centrados), para que Editar y Eliminar queden
  en la misma columna aunque el primer botón tenga palabra. Una acción que no se puede hacer
  se muestra apagada con su ⚠ (nunca un ⚠ suelto en su lugar): `x-ayuda` acepta el slot `icono`.
- **Íconos claros y ventana de cambios (H-51, 22/11/2026).** `x-admin.boton-fila` tiene `texto`
  (píldora con ícono + palabra) para acciones que el ícono no explica; regla: editar, eliminar
  y flechas sin texto. Ícono nuevo `eye-off` en `x-icon`. `x-ayuda` tono info: círculo
  `fill-amber-400` con la "i" `fill-amber-950`. "Ver cambios" usa `x-modal`
  (`$dispatch('open-modal', 'cambios-{id}')`); las ventanas se dibujan después de la
  tabla para que la tabla que se desliza de lado no las descoloque.
- **"Disponible" solo en la lista (H-50, 22/11/2026).** `ServicioController::validated()` ya no
  acepta `activo` ni `icono`: `store()` crea con `activo = true` y `update()` no lo toca; solo
  `estado()` (interruptor de la lista) lo cambia. `_formulario` avisa si el servicio está oculto.
- **"Ver cambios" legible (H-49, 22/11/2026).** `App\Support\DetalleBitacora::filas($registro)`
  devuelve `[campo, antes, despues]` en palabras: con antes y después solo lo que cambió;
  oculta campos internos (`icono`, `id`, marcas de tiempo); nombres por tabla
  (`servicios.activo` = "Disponible"); ids → nombres con una consulta por tipo y página
  (`reiniciar()` al inicio de la vista). La vista abre el detalle como `<tr colspan=5>`
  debajo del evento (Alpine `abiertos` en la tarjeta), con la caja fija a la izquierda
  (`sticky`) para leerse en celular sin deslizar la tabla. Al agregar un campo nuevo a la
  bitácora, darle nombre en `DetalleBitacora::CAMPOS` y, si es una lista de opciones, en `VALORES`.
- **Servicio: un formulario y un nombre (H-48, 22/11/2026).** `create` y `edit` incluyen
  `admin/servicios/_formulario` (`$esNuevo = ! $servicio->exists`); `create()` manda
  `new Servicio(['activo' => true])` y los tratamientos agendables (`tratamientosAgendables()`),
  y `store()` también sincroniza `tratamiento_ids`. El campo `icono` ya no se pide (la portada
  usa la foto). `servicios.activo` se muestra como "Disponible / No disponible": oculta el
  servicio en portada, agenda pública, Nueva cita del panel, asistente y Telegram.
- **Menú del panel desde 1024 px y marca única (H-47, 22/11/2026).** `layouts/navigation`
  muestra el menú completo con `lg:` (antes `2xl:`, 1536 px); entre `lg` y `xl` reduce
  el relleno de los enlaces (`px-3 xl:px-4`, también en `x-nav-link` y `x-nav-dropdown`) y
  esconde el nombre del usuario (`sr-only xl:not-sr-only`) y la firma de la marca solo en ese rango (`lg:sr-only xl:not-sr-only`) (siguen
  para lectores de pantalla). `x-marca` (`href`, `tono` claro/oscuro, `tamano`,
  `textoDesde`) es el logotipo con firma en portada, agenda pública, inicio de sesión y
  panel; usa `images/9.jpg` (31 KB; el PNG pesaba 150 KB). Regla: el sistema se usa en
  celular, tableta, laptop, PC, TV y proyector; revisar 375, 768, 1024, 1280, 1366 y 1920 px.
- **Interruptor de estado global (H-46, 22/11/2026).** `x-admin.interruptor-estado`
  (`action`, `activo`, `nombre`, `activar`/`desactivar` para el diálogo, `textoActivo`/
  `textoInactivo`, slot `bloqueo`). Rutas `PATCH …/{modelo}/estado` en servicios, casos,
  galería y preguntas frecuentes, con el trait `Concerns\AlternaEstado` (`alternarCampo()`:
  invierte el campo con `AuditoriaService::registrarCambio` y regresa con mensaje).
  Tratamientos (`tratamientos.estado`) y Usuarios (`usuarios.activo.toggle`) conservan
  sus reglas (precio, cuenta propia) y usan el mismo componente.
- **Servicios con filtro global (H-45, 22/11/2026).** `buscar` y `activo` como
  Tratamientos. La vista recibe `posiciones` (id → lugar real en el carrusel) y
  `totalServicios`; con `filtrando` no se muestran las flechas de `servicios.mover`.
- **"Auditoría" se llama "Control de actividades" (22/11/2026).** Solo cambia lo
  que ve el usuario (menú, título, mensajes y manual de usuario); el nombre de
  ruta `admin.auditoria.index`, la tabla `logs_auditoria` y `AuditoriaService` siguen igual.
  La dirección sí cambió (H-43): `/admin/control-de-actividades`; `/admin/auditoria`
  redirige con 301 conservando los filtros de la consulta.
- **Control de actividades sin duplicados y con la hora correcta (H-43, 22/11/2026).**
  `AuditoriaObserver` ya no escribe al momento: deja su evento genérico en
  `App\Support\BitacoraPendiente` (singleton `scoped`, uno por petición) y se escribe
  en `terminating()` solo si ese `tabla#id` no tuvo evento con detalle de
  `AuditoriaService` (que llama `marcarDetallado()`). El observador ahora guarda
  también nombre y rol de quien hizo el cambio. `AuditoriaService` pone `fecha = now()`:
  antes la ponía MySQL en hora universal y se veía 6 horas adelantado. La migración
  `2026_11_22_000002_corregir_hora_y_duplicados_de_la_bitacora` (solo MySQL) corrige la
  hora del historial y borra los genéricos duplicados (en local: 374 → 218 registros).
- **Texto largo con "Leer completo" (H-44, 22/11/2026).** `x-texto-expandible`
  (`texto`, `lineas` 2–5, `comillas`) recorta con `line-clamp` y respeta saltos de línea;
  en `init()` mide si hay texto oculto y solo entonces muestra el botón. Se usa en
  los testimonios de la portada y del panel. Testimonios del panel tiene `buscar`
  (paciente por palabra en nombre o apellido, o comentario) con `whereContiene`.
  Nota de pruebas: el navegador integrado puede pausar `requestAnimationFrame`;
  `x-show` espera un cuadro para mostrar, así que hay que forzar el dibujado.
- **Configuración en una sola página (22/11/2026).** `GET admin.configuracion`
  (`ConfiguracionController`) muestra `admin/configuracion/_consultorio` y
  `_horarios`, cada uno con su `<form>`; las rutas `admin.horarios.edit` y
  `admin.consultorio.edit` redirigen a `/admin/configuracion#horarios` y
  `#consultorio`. En el menú, "Configuración" es un enlace directo.
- Los datos del paciente se vinculan por teléfono y correo, y el formulario
  público valida los campos.
- Como el formulario es público, `POST /agendar-cita` usa el limitador
  `agendar-cita` (definido en `AppServiceProvider`): 5 envíos por minuto y 20
  por hora por IP. Al superarlo, el paciente vuelve al formulario con sus
  datos capturados y el aviso "Recibimos varios intentos seguidos…" en vez de
  una página de error 429. Lo cubre
  `CitaPublicaTest::test_la_agenda_publica_limita_los_envios_repetidos_desde_la_misma_ip`.

**Alternativas descartadas.**

- *Cuenta obligatoria para el paciente:* más pasos para el paciente y más datos
  sensibles que proteger.
- *Portal completo del paciente con enlace mágico* (ver todas sus citas y
  cambiarlas en cualquier momento): descartado para la v1.0. Se adoptó una
  versión acotada: el enlace firmado del recordatorio, que solo sirve para esa
  cita y deja de aceptar respuestas a la hora de la cita.

### Lista de citas por pestañas (`GET /citas`)

Se eligió en dos rondas de prototipos: primero "Tabla por día" frente a una
vista "Ahora" y un tablero por estado; después, para desplazarse menos,
"Por pestañas" frente a una versión compacta y otra desplegable. En el
prototipo, el día de hoy (11 citas) pasó de unos 1700 px a 1124 px en celular
porque solo se ve un día y lo ya atendido se pliega.

- **Por día (predeterminado):** `?fecha=AAAA-MM-DD` (hoy si falta). Muestra
  todas las citas de ese día en orden de hora, sin paginación, y el filtro
  `?estado` actúa dentro del día. Arriba, una pestaña por día de la semana del
  día elegido (lunes a sábado; domingo solo si abre, tiene citas o es el día
  elegido) con sus citas activas y las que siguen **por confirmar**
  (`CitaController::pestanasDeLaSemana()`, una sola consulta). Las flechas
  mueven una semana conservando el día de la semana.
- **Plegado:** mientras queden citas por atender, las completadas, canceladas
  y no asistidas se agrupan en un `<details>` ("4 ya atendidas o canceladas")
  cerrado por defecto. Si el día ya no tiene pendientes, se muestran todas.
- **Todas las fechas:** `?buscar=` o `?todas=1` muestran citas de cualquier
  fecha, de la más reciente a la más antigua, agrupadas por día y paginadas
  (20 o 50). La búsqueda ignora `fecha` a propósito: buscar a un paciente debe
  encontrar todo su historial.
- **Vistas:** `citas/index.blade.php` decide el modo con `isset($dia)`;
  `citas/partials/fila.blade.php` dibuja cada cita (tabla desde 1024 px,
  tarjeta en celular, clases `.fila-cita` de `app.css`) y
  `citas/partials/acciones.blade.php` conserva los botones de antes
  (Confirmar, Iniciar, Completar y "Más acciones").
- **Pruebas:** `ListaCitasPorDiaTest`. Varias pruebas anteriores ahora abren el
  día de la cita (`?fecha=`) o `?todas=1`, porque la lista ya no mezcla días.

## 21. Vista semanal de la agenda (RF03)

`GET /citas?vista=semana&semana=AAAA-MM-DD` muestra la agenda de la semana; sin
`vista` se muestra la lista por pestañas (ver arriba). Ambas vistas comparten el selector
`citas/partials/selector-vista.blade.php`.

- **Controlador:** `CitaController::index` delega en `semana()` cuando
  `vista=semana`. `lunesDeLaSemana()` normaliza cualquier día a su lunes y, si
  `semana` no es una fecha `Y-m-d` válida (por ejemplo `2030-02-30`), usa la
  semana actual en lugar de responder con error.
- **Consulta:** una sola consulta entre el lunes y el domingo de la semana, con
  `paciente` y `tratamiento` precargados (sin N+1), agrupada por día.
- **Días:** lunes a sábado siempre; el domingo solo si `HorarioAtencion` lo marca
  abierto o tiene citas. Cada día muestra su horario o **Cerrado**. "Hoy" se
  calcula con la zona horaria de la aplicación (`America/Mexico_City`).
- **Estados:** `EstadoCita::etiqueta()`, `claseInsignia()` y `esInactiva()`
  centralizan textos y colores; la lista y la semana los comparten.
- **Vista ("Bloques"):** `citas/semana.blade.php`, sin librerías de calendario.
  Se eligió tras tres rondas de prototipos (Columnas, Cuadrícula y Ocupación;
  luego versiones menos saturadas de Ocupación). Es un mapa de días × bloques de
  2 horas: blanco libre, rosa claro con citas y lugar, rosa lleno; los bloques
  que ya terminaron se atenúan. Al tocar un bloque, Alpine muestra a la derecha
  (abajo en celular) su detalle: horarios donde todavía cabe una cita (enlazan a
  **Agendar cita** con `?fecha=`) y sus citas (enlazan a la edición). Sin
  JavaScript se ve el detalle que eligió el servidor.
- **Cálculo:** `OcupacionSemanalService::calcular()` recibe los días y
  `HorarioAtencion`. Por bloque: ocupación = minutos-cita ÷ (minutos del bloque
  × `capacidad_simultanea`), sin contar canceladas ni inasistencias; horarios
  con lugar = inicios cada `dentissa.intervalo_minutos` que no han pasado y con
  menos citas que la capacidad; "lleno" si ocupación ≥ 90 % o ya no queda lugar.
  Es solo consulta: al agendar, `DisponibilidadService` vuelve a validar con el
  tratamiento y su margen reales.
- **Bloque inicial:** el primero de la semana que todavía no termina (el actual
  si es hoy o el siguiente aunque sea otro día); en una semana ya pasada, el
  primero abierto.
- **Días cerrados con citas:** no tienen bloques, pero su encabezado muestra
  "Cerrado · N citas" y abre la lista de esas citas.
- **Pruebas:** `AgendaSemanalTest` cubre acceso, rango de la semana, orden por
  hora, datos de cada cita, navegación, fecha inválida, días cerrados y que la
  lista siga siendo la vista predeterminada. `OcupacionSemanalTest` cubre
  niveles, canceladas, horarios con lugar, sábado corto, bloque inicial, días
  cerrados con citas y la fecha prellenada al agendar.

## 22. Recordatorio y respuesta del paciente (etapa 1)

**Desde el 24/11/2026 (H-82)** el recordatorio ya no sale 24 h antes: `App\Support\MomentoRecordatorio::para()` calcula el día hábil anterior (abierto en Horarios de atención y sin bloqueo de día completo) a la hora de apertura + 1. `dentissa:enviar-recordatorios` revisa las citas de los próximos 8 días y manda las que ya pasaron ese momento y se crearon antes de él. Lo que sigue en esta sección describe la versión original de 24 h.

**Objetivo:** reducir inasistencias. Un día antes, el paciente recibe un
recordatorio por WhatsApp **y** por correo y puede responder **Asistiré**,
**Reagendar** o **Cancelar**. La etapa 1 recibe las respuestas por los enlaces
del correo. La etapa 2 (sección 22.1) recibe los botones de WhatsApp en un
webhook firmado por Meta. Las dos usan `RespuestaRecordatorioService`.

### 22.1 Webhook de WhatsApp (etapa 2)

- **Rutas:** `GET /webhooks/whatsapp` responde la verificación de Meta
  (`hub_verify_token` contra `DENTISSA_WHATSAPP_VERIFY_TOKEN`; PHP convierte
  `hub.mode` en `hub_mode`). `POST /webhooks/whatsapp` recibe los mensajes, con
  `throttle:120,1` y fuera de CSRF.
- **Firma:** `X-Hub-Signature-256` = `sha256=` + HMAC-SHA256 del **cuerpo
  crudo** con `DENTISSA_WHATSAPP_APP_SECRET`, comparada con `hash_equals`. Sin
  firma válida responde 403 y no lee nada.
- **Mensaje:** botón de plantilla (`button.payload`) o interactivo
  (`interactive.button_reply.id`) con `recordatorio:{id}:{accion}`.
- **Controles:**
  - el teléfono `from` debe ser el del paciente de esa cita
    (`WhatsappService::mismoTelefono`, últimos 10 dígitos);
  - cada `id` de mensaje se procesa una sola vez (`Cache::add`, 2 días), porque
    Meta reintenta;
  - cualquier otro mensaje recibe una respuesta automática (una vez cada 12 h
    por número) con el teléfono del consultorio y el enlace para agendar; el
    contenido no se guarda ni se registra, porque puede traer datos de salud;
  - con firma válida siempre responde 200, para que Meta no reintente durante
    horas.
- **Respuesta al paciente:** `WhatsappService::enviarTexto()`, texto libre
  permitido dentro de la ventana de 24 h abierta por su mensaje: confirmación,
  enlace a la agenda si canceló o reagendó, o "ya no admite cambios" con el
  teléfono del consultorio.
- **Pruebas:** `WhatsappWebhookTest` (10): verificación de la URL, confirmar,
  reagendar con enlace, firma ausente o falsa, otro número, reintento
  idempotente, cita pasada, respuesta automática al texto (una vez cada 12 h por
  número) y avisos de estado de Meta sin respuesta.

### Flujo

```
scheduler (cada 15 min) ──> dentissa:enviar-recordatorios
                              │ citas Agendada/Confirmada, en (ahora, ahora+24h],
                              │ sin recordatorio_enviado_en y creadas ≥24 h antes
                              ▼
                     NotificacionService::citaRecordatorio
                       ├─ WhatsApp: plantilla cita_recordatorio + 3 botones
                       └─ Correo: RecordatorioCita con 3 enlaces firmados
                              ▼
   paciente abre enlace ──GET──> página que pregunta (no cambia nada)
                         ──POST─> RespuestaRecordatorioService::responder
                                    ├─ confirmar → Confirmada + asistencia_confirmada_en
                                    ├─ cancelar  → Cancelada (motivo del paciente)
                                    └─ reagendar → Cancelada + enlace a /agendar-cita
                                  + aviso interno al personal (campana)
```

### Decisiones técnicas

- **Columnas nuevas en `citas`:** `recordatorio_enviado_en` evita duplicados;
  `asistencia_confirmada_en` guarda la respuesta "Asistiré" aparte del estado,
  porque las citas públicas ya nacen Confirmadas. Reagendar desde el panel
  (`CitaController@update`) vacía ambas para que la nueva fecha tenga su propio
  recordatorio y su propia confirmación.
- **Sin duplicados aunque el comando se encime:** el schedule usa
  `withoutOverlapping()` y, además, cada cita se *reclama* con un `UPDATE ...
  WHERE recordatorio_enviado_en IS NULL`; solo la ejecución que cambia la fila
  manda el aviso. La marca se pone aunque los dos canales fallen: es un solo
  intento, auditado en `notificaciones` con `tipo` vacío.
- **Doble canal, no cascada:** los demás avisos siguen siendo WhatsApp → correo
  de respaldo; el recordatorio intenta los dos y guarda una fila por cada envío
  exitoso (`tipo = whatsapp` / `email`, `canal = cita_recordatorio`).
- **Enlaces firmados** (`URL::temporarySignedRoute`): el paciente no tiene
  cuenta, así que la firma es lo que autoriza. Cambiar el número de cita o la
  acción invalida la URL (403). La firma vence un día después de la cita para
  que un enlace viejo muestre "ya no admite cambios" en vez de un error; la
  regla real (estado Agendada/Confirmada y cita futura) está en
  `RespuestaRecordatorioService::admiteRespuesta()`.
- **GET pregunta, POST cambia:** Gmail, Outlook y algunos antivirus abren los
  enlaces de un correo para revisarlos. Si el GET cancelara, una cita podría
  cancelarse sola. Después del POST se redirige al GET (Post/Redirect/Get), así
  que recargar no reenvía la respuesta.
- **Una sola lógica para los dos canales:** `RespuestaRecordatorioService`
  bloquea la fila (`lockForUpdate`) para no pisar un cambio simultáneo del
  personal, y es lo que reutilizará el webhook de WhatsApp. El payload de cada
  botón ya se envía como `recordatorio:{id_cita}:{accion}`.
- **Avisos al personal:** eventos `asistencia_confirmada`, `cancelada_paciente`
  y `reagendar_paciente`; los dos últimos se destacan como prioritarios porque
  liberan un horario. No van a la auditoría: no los hizo un empleado.
- **Fechas en español:** `APP_LOCALE` es `en`; la página y los mensajes a
  pacientes fuerzan `locale('es')` (antes los avisos decían "Jan").
- **Producción:** Supervisor ejecuta un proceso `scheduler`
  (`php artisan schedule:work`) junto al web y al bot de Telegram, en el mismo
  contenedor, sin costo extra. En local, el comando se prueba a mano.

### Cómo validar en local

```bash
php artisan schedule:list
```

Debe listar `dentissa:enviar-recordatorios` con `*/15 * * * *`.

```bash
php artisan test --filter=RecordatorioCitaTest
```

**Cuidado:** si tu `.env` local tiene credenciales reales de WhatsApp o un
`MAIL_MAILER` que envía de verdad, `php artisan dentissa:enviar-recordatorios`
avisará a los pacientes reales de la base local. Para probarlo a mano usa una
copia de la base con `MAIL_MAILER=log` y las variables de WhatsApp vacías.

### Pruebas

`RecordatorioCitaTest` (16 pruebas, hora fija 9 ene 2030 10:00) cubre: envío
por ambos canales una sola vez con los 3 botones, WhatsApp caído con correo que
sí sale, correo caído sin detener el comando, citas excluidas (canceladas,
inasistencia, completadas, pasadas, a más de 24 h, agendadas con menos de 24 h),
reagendar que vuelve a habilitar el recordatorio, el schedule cada 15 min,
enlaces firmados en el correo, GET que no modifica, confirmar/cancelar/reagendar,
la marca en lista y semana, cita pasada o cancelada, y URL sin firma o alterada.

## 23. Aviso de privacidad y consentimiento (fase 1 para pacientes reales)

**Objetivo:** antes de usar Dentissa con pacientes reales, informar qué datos
se recaban y obtener el consentimiento expreso, porque el motivo de la visita y
el historial clínico son **datos de salud**, que la ley considera sensibles.

### Qué se implementó

- **Página pública `/aviso-de-privacidad`** (`privacidad.aviso`). Incluye:
  responsable y domicilio, datos que se recaban (incluidos los de salud),
  finalidades, proveedores que tratan datos por cuenta de la clínica (hospedaje,
  correo de Google, WhatsApp de Meta y Telegram), derechos ARCO y revocación,
  consentimiento y cambios al aviso.
- **Configuración `config('dentissa.privacidad')`:**

  | Variable | Uso |
  |---|---|
  | `DENTISSA_PRIVACIDAD_RESPONSABLE` | Nombre del responsable |
  | `DENTISSA_PRIVACIDAD_DOMICILIO` | Domicilio del responsable |
  | `DENTISSA_PRIVACIDAD_CORREO` | Correo para solicitudes ARCO |
  | `DENTISSA_AVISO_REVISADO` | `true` solo cuando un abogado o la clínica lo aprueben |

  Mientras falte un dato, la página lo marca como **[Por completar: …]**.
  Mientras `revisado` sea `false`, muestra "Borrador pendiente de revisión
  legal". Así nadie publica el aviso creyendo que está completo.
- **Versión del aviso:** `privacidad.version` (fecha). Hay que cambiarla cada
  vez que cambie el texto.
- **Consentimiento obligatorio en todos los canales:**
  - Web: casilla `acepta_aviso` con la regla `accepted`, y el mensaje "Para
    agendar necesitamos que aceptes el aviso de privacidad."
  - Telegram: el resumen antes de confirmar enlaza el aviso y explica que tocar
    **Confirmar cita** es aceptar.
  - `CrearCitaService::crear()` lanza una excepción si no recibe
    `acepta_aviso === true`. Es la última barrera: un canal nuevo no puede
    olvidar el consentimiento sin que las pruebas fallen.
- **Evidencia:** `pacientes.aviso_privacidad_aceptado_en` y
  `pacientes.aviso_privacidad_version` se actualizan en cada cita. **No** están
  en `Fillable`: solo se escriben con `forceFill` desde el servicio, nunca desde
  un formulario.
- **Enlaces:** pie de la portada, formulario de la agenda (abre en otra
  pestaña para no perder lo capturado) y pie de los correos.

### Límites

- El texto es una **plantilla técnica, no asesoría legal**. Debe revisarla un
  abogado o la clínica antes de poner `DENTISSA_AVISO_REVISADO=true`.
- Las citas que el personal registra en el consultorio (`/citas/crear`) no
  pasan por la casilla: el consentimiento se recaba en persona.

### Pruebas

`AvisoPrivacidadTest` (9 pruebas): apartados del aviso, borrador y datos por
completar, aviso limpio con datos de la clínica, agenda sin consentimiento
rechazada sin guardar nada, fecha y versión guardadas, campos fuera de
`Fillable`, servicio que rechaza cualquier canal sin consentimiento, enlaces en
formulario y portada, y el texto de Telegram.

## 24. Seguridad de las cuentas del personal (fase 3 para pacientes reales)

Auditoría del 16/11/2026: ya existían el bloqueo tras 5 intentos de inicio de
sesión, la regeneración de la sesión, los permisos por rol y la bitácora. Se
cerraron cinco huecos.

| # | Hueco | Solución |
|---|---|---|
| 1 | `activo` solo se revisaba al iniciar sesión: una cuenta desactivada seguía dentro, o semanas con "Recordarme". | Middleware `CerrarSesionUsuarioInactivo` en el grupo `web`: en cada petición, si la cuenta está inactiva, cierra la sesión y la envía al login con un aviso. |
| 2 | Sin verificación en dos pasos. | TOTP (RFC 6238) con `pragmarx/google2fa` y QR en SVG con `bacon/bacon-qr-code`, ambas gratuitas. **Obligatoria para el administrador.** |
| 3 | Contraseña mínima de 8 caracteres, sin más reglas. | `Password::defaults()`: mínimo 10, con mayúsculas, minúsculas y números. Aplica al crear o cambiar contraseñas. |
| 4 | Cambiar la contraseña no cerraba otras sesiones; inactividad de 2 h. | Al cambiarla se borran las demás sesiones (driver `database`) y se rota el `remember_token`. `SESSION_LIFETIME=30` y `SESSION_ENCRYPT=true`. |
| 5 | Sin cabeceras de seguridad. | Middleware `CabecerasSeguridad`: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, y HSTS solo en HTTPS. |

### Verificación en dos pasos

```
contraseña correcta ──> ¿tiene 2 pasos? ── no ──> panel
                               │ sí
                               ▼
        se cierra la sesión recién abierta; la sesión guarda
        {usuario_id, recordar, expira en 5 min}
                               ▼
        /dos-pasos: código de 6 dígitos o código de recuperación
        (throttle 5/min) ──> login(usuario, recordar) ──> panel
```

- **Datos** (`users`): `dos_pasos_secreto` (cast `encrypted`, cifrado con
  `APP_KEY`), `dos_pasos_recuperacion` (8 hashes SHA-256 en `encrypted:array`),
  `dos_pasos_confirmado_en` y `dos_pasos_ultimo_paso`. Ninguno está en
  `Fillable`; `secreto` y `recuperacion` están en `Hidden`.
- **Sin repetición:** `verifyKeyNewer` solo acepta un intervalo de 30 s
  posterior al último usado, así que un código ya aceptado no vuelve a servir.
  La ventana es de ±1 intervalo por relojes desfasados.
- **Activación** (`/profile/dos-pasos`): el secreto vive en la sesión hasta que
  el usuario confirma un código; solo entonces se guarda. Los 8 códigos de
  recuperación se muestran **una sola vez**.
- **Obligatoria para el administrador** (`DENTISSA_2FA_ADMIN`, por defecto
  `true`): el middleware `ExigirDosPasos` lo lleva a activarla en cualquier ruta
  con `auth`, salvo la propia activación y `logout`. Las páginas públicas no se
  bloquean. El administrador no puede desactivarla; la asistente sí, con su
  contraseña.
- **Demostración:** con `DENTISSA_2FA_ADMIN=false` se puede presentar sin
  celular. **Con pacientes reales debe quedar en `true`.**
- **Si se pierde el celular y los códigos:** un administrador de la base puede
  vaciar los cuatro campos `dos_pasos_*` del usuario para que vuelva a
  activarla. Esto debe quedar registrado por el equipo.

### Dependencias

`composer audit` mostró 19 avisos en paquetes que ya existían: uno crítico en
`laravel/framework` (`TokenGuard`, que la app no usa) y varios altos en
`league/commonmark` y Symfony. Se actualizaron dentro de las restricciones de
`composer.json` en un commit aparte. Hay que repetir `composer audit` antes de
cada entrega.

### Pruebas

`SeguridadPersonalTest`: cuenta desactivada expulsada; cabeceras y HSTS solo en
HTTPS; contraseña débil rechazada; otras sesiones cerradas y `remember_token`
rotado; vida de sesión de 30 min; activación con QR, código incorrecto
rechazado, códigos de recuperación y secreto cifrado en la base;
administrador bloqueado hasta activarla (sin afectar a la asistente ni a las
páginas públicas); el administrador no puede desactivarla; la contraseña sola
no abre sesión; un código no sirve dos veces; un código de recuperación sirve
una sola vez; el reto caduca a los 5 minutos.

## 25. Diagnóstico de la configuración (`dentissa:diagnostico`)

**Por qué:** en Railway no se ven los valores de las variables, y un dato
faltante (por ejemplo, el token de verificación del webhook) costaba tiempo de
adivinar. El comando lo revisa todo de una vez.

```bash
php artisan dentissa:diagnostico
```

```bash
php artisan dentissa:diagnostico --remoto
```

| Área | Qué revisa (sin `--remoto`) |
|---|---|
| Aplicación | `APP_KEY`; `APP_DEBUG` apagado en producción; `APP_URL` con https; `APP_NAME=Dentissa` |
| Sesiones | Cookie segura en producción, sesión cifrada, inactividad ≤ 30 min |
| Base de datos | Conexión y migraciones pendientes |
| Almacenamiento | `storage/app/private` existe y se puede escribir (volumen) |
| Correo y Google | `MAIL_MAILER`, remitente que no sea de ejemplo, credenciales de Google |
| Respaldos | Llave de cifrado válida (32 bytes) |
| WhatsApp | Las 4 variables (número, token, clave secreta, token de verificación) |
| Privacidad | Datos del responsable y si el aviso sigue como borrador |
| Seguridad | Verificación en dos pasos obligatoria y administradores que aún no la activan |
| Telegram | Token del bot |

**Con `--remoto`**, además:
- **Meta:** consulta el número con el token. Confirma que el token sirve y
  muestra el número, el nombre, el estado y la calidad. Avisa si
  `DENTISSA_TELEFONO` es el mismo número del bot.
- **Google:** obtiene un token y revisa sus permisos (`gmail.send`,
  `drive.file`) con `tokeninfo`. Si hay Drive, comprueba que el último respaldo
  tenga menos de 36 h.

**Reglas:**
- Nunca imprime el valor de un secreto: tokens, llaves ni contraseñas.
- Los mensajes de error de Meta y Google no incluyen el token.
- Termina con código de error si hay algún ❌, para usarlo en scripts.
- El ícono de aviso es ❗ porque la consola de Windows no dibuja ⚠️.

**Pruebas:** `DiagnosticoTest` (6): configuración completa sin errores; lo que
falta y la salida con error; administrador sin verificación; revisión remota
(Meta, número del bot y falta de Drive); token rechazado por Meta; y que la
salida no contiene ningún secreto.

## 26. Pulido de la interfaz (fases 1 a 7)

**Por qué:** después del rediseño (sección 19) quedaban textos de 10–11 px,
botones pequeños para el dedo, avisos distintos en cada vista y pantallas que
se rompían con datos extremos. El pulido se hizo en 7 fases, cada una con su
commit en la rama `rediseno`.

### Tipografía (fase 1)

- **Fuentes locales** en `public/fonts/`: Figtree para el texto y Fraunces
  (500 y 600, más 500 itálica) para los títulos. No se cargan desde Google, así
  que funcionan sin internet y sin enviar datos a terceros.
- **Escala única** en `tailwind.config.js`: `display`, `title-1`, `title-2`,
  `title-3`, `headline`, `lead`, `body`, `callout`, `footnote` y `caption`.
  Cada nivel trae su interlineado y espaciado. El tamaño mínimo es 12 px.
- **Nombre de la doctora:** la clase `.firma` (Fraunces itálica) se usa en la
  portada, el encabezado y el acceso.
- `h1` y `h2` usan `text-wrap-style: balance` y los párrafos `pretty`. Se usa
  `text-wrap-style` y no `text-wrap`, porque este último anula `whitespace-nowrap`.

### Tokens de color

Los colores neutros viven en `resources/css/app.css` como canales RGB
(`--c-cream`, `--c-shell`, `--c-line`, `--c-ink`, `--c-ink-soft`, `--c-muted`,
`--c-field`). Así Tailwind puede aplicar transparencias (`bg-cream/50`).
`--c-field` es el borde de los campos, con contraste 3.3:1 sobre blanco.

### Estados y avisos (fase 2)

- `<x-alert type="success|error|warning|info">`: un solo componente para todos
  los avisos de éxito o error.
- `<x-text-input>` marca `aria-invalid` automáticamente si el campo tiene error,
  y `<x-input-error>` muestra el mensaje con ícono.
- **Botones con carga:** `resources/js/app.js` desactiva el botón al enviar un
  formulario y cambia el texto por `data-loading-text` (por ejemplo
  "Agendando…"). Evita citas dobles. Se restablece al volver con el botón
  "atrás" del navegador o tras 12 s. Un formulario lo omite con `data-sin-carga`.
- El foco visible solo aparece con teclado (`:focus-visible`).
- **Datos del paciente en la agenda pública (variante "Clásica"):** elegida en
  una ronda de prototipos frente a "Conversacional" y "Boleto". El formulario usa
  `novalidate` y el componente Alpine `datosPaciente` (`app.js`) valida con las
  mismas reglas que `CitaPublicaController@store`: el error aparece al salir del
  campo y se quita al corregirlo; al enviar con errores se cancela el envío
  (el botón "Agendando…" no se activa porque respeta `defaultPrevented`), se
  cuenta lo pendiente y se enfoca el primer campo. Los errores del servidor
  llegan al mismo componente. El teléfono se muestra como "55 1234 5678";
  `store()` quita solo los espacios antes de validar, así que `+52…`, guiones o
  paréntesis siguen rechazándose. Pruebas: `DatosPacientePublicoTest`.

### Celular (fase 3)

- `viewport-fit=cover` y márgenes `env(safe-area-inset-*)` para la muesca.
- En pantallas táctiles (`@media (pointer: coarse)`) los campos miden 16 px
  para que el teléfono no haga zoom al escribir.
- Objetivos táctiles de 44 px como mínimo (`min-h-11`), con `sm:min-h-0`
  cuando el tamaño de escritorio debe quedar igual.
- Agendar cita tiene una barra de acciones fija abajo, y el asistente flotante
  sube para no taparla (`.agenda-publica .asistente-flotante`).

### Casos extremos (fase 4)

- `App\Support\Iniciales`: iniciales que respetan acentos y omiten emoji
  (antes mostraban `��`).
- Contadores en singular ("1 cita") y con separador de miles ("1,284").
- Agendar oculta las especialidades sin tratamientos activos.
- El motivo de la cita acepta hasta 1000 caracteres.
- **Herramienta de revisión** (solo con `APP_ENV=local`):
  `/dev/casos-extremos/{agenda|pacientes|agendar|confirmacion|dashboard}?datos=demo|peor|vacio|uno|muchos`.
  Dibuja la pantalla con datos de prueba en memoria, sin tocar la base. Los
  datos están en `App\Support\Dev\CasosExtremos`.

### Animaciones (fases 5 y 6)

- **Se conservan solo 5, con propósito:**
  - el cambio entre pasos al agendar (`@starting-style`, con dirección);
  - la confirmación de la cita (círculo, palomita y textos en secuencia);
  - los avisos emergentes (Web Animations API);
  - el menú de acciones;
  - el estado de carga de los botones.
- **Se quitaron:** apariciones al hacer scroll, carrusel, punto que latía sin
  parar, zoom lento en imágenes y animación de ancho del asistente.
- **Solo se anima `transform` y `opacity`.** Curva común: `--ease-out`
  (`cubic-bezier(0.23, 1, 0.32, 1)`).
- Con "reducir movimiento" activado en el sistema, las animaciones se acortan o
  desaparecen y el scroll de la portada es instantáneo.

### Revisión final (fase 7)

- **Idioma:** `config/app.php` usa `es` por omisión, y las traducciones están en
  `lang/es/` (`validation`, `auth`, `passwords`, `pagination`).
- **Nombres legibles de campos:** `validation.php` traduce los campos dentro de
  los mensajes (`telefono` → "el teléfono").
- **Al agregar un campo nuevo:** súmalo a `attributes` en ese archivo.
- **Títulos del panel:** `<x-admin.header>` dibuja el título como `h1`, para que
  cada página tenga un solo título principal.
- **Tablas del panel (`<x-admin.table>`, modo "Mixta"):** elegido en una ronda
  de prototipos (Tarjetas, Prioridades, Lista y Mixta) con la regla "las
  tablas cortas nunca se desplazan de lado; las largas, lo mínimo".
  - **Corta (predeterminada):** clase `.tabla-apilable`. Por debajo de 1024 px
    cada fila es una tarjeta; desde 1024 px, tabla normal sin ancho mínimo. El
    componente Alpine `tablaApilable` (`app.js`) copia el título de cada `<th>`
    a `data-label` de sus celdas, marca la columna "Acciones" y agrega roles
    ARIA, porque `display: block` quita la semántica de tabla.
  - **Larga (`:larga="true"`):** usa `$minWidth`, se desplaza de lado dentro
    de su tarjeta, la primera columna es `position: sticky` y el componente
    `tablaLarga` muestra "Desliza para ver más columnas" solo si hay desborde
    (`ResizeObserver`); al desplazarse, la columna fija recibe sombra.
  - **Cuándo marcar una tabla como larga:** si la suma de los anchos mínimos
    de sus columnas pasa de unos 800 px. Hoy: Auditoría, Mensajes de contacto,
    Ofertas y Tratamientos. Una tabla nueva es corta por defecto.
  - **Requisito para tablas cortas:** la primera columna debe identificar el
    registro (nombre, título) y la de botones debe titularse "Acciones".
  - **Pruebas:** `TablasMixtasTest` revisa las 11 pantallas.

### Pruebas

- **`CasosExtremosInterfazTest` (27):** dibuja las pantallas con cada conjunto de
  datos y revisa:
  - iniciales y plurales;
  - especialidades vacías y el límite del motivo;
  - mensajes en español y títulos `h1`.
- **`TipografiaEstableTest`:** precarga de fuentes.
- **`NavegacionResponsivaTest` y `AsistenteVistasPublicasTest`:** clases de
  diseño responsivo.
