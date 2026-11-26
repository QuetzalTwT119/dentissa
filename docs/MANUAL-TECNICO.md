# Manual Técnico de Dentissa

## 1. Introducción

### 1.1 Objeto

Este documento especifica la arquitectura, la infraestructura, el modelo de datos, las
reglas de negocio implementadas, los mecanismos de seguridad, las integraciones externas,
la estrategia de pruebas y el procedimiento de despliegue de Dentissa, sistema web de
gestión para consultorio odontológico. Su destinatario es el personal técnico responsable
de mantener, extender, probar u operar el sistema.

### 1.2 Alcance funcional del sistema

| Subsistema | Capacidades |
|---|---|
| Portal público | Portada dinámica, catálogo de especialidades, promociones, testimonios, galería, casos, preguntas frecuentes, ubicación, aviso de privacidad. |
| Agenda pública | Asistente de reserva en cuatro pasos con cálculo de disponibilidad en servidor, consentimiento obligatorio y limitación de tasa. |
| Panel interno | Agenda (lista diaria y mapa semanal), directorio de pacientes, expediente clínico, catálogo, contenido, configuración, usuarios, reportes y bitácora. |
| Expediente clínico | Historia clínica, exploración y odontograma (notación FDI), consultas, recetas y consentimientos en PDF, planes de tratamiento. |
| Notificaciones | Recordatorio el día hábil anterior por WhatsApp Cloud API y correo, webhook de respuestas, avisos internos en tiempo real (Reverb), bot de Telegram. |
| Operación | Respaldo cifrado diario a Google Drive, restauración de prueba aislada, diagnóstico de configuración, bloqueo automático de festivos, cierre de citas olvidadas. |

### 1.3 Convenciones

- Rutas de archivo relativas a la raíz del repositorio (`app/Services/...`).
- Nombres de ruta de Laravel entre comillas invertidas (`citas.publica.create`).
- Las variables de entorno se citan solo por nombre; **sus valores nunca se documentan**.
- Los identificadores `H-nn` remiten a la tabla de hallazgos de
  `docs/CHECKLIST-VERIFICACION.md`, y `QA-nn` a `docs/MATRIZ-QA-MVP.md`.

### 1.4 Documentos relacionados

| Documento | Contenido |
|---|---|
| `docs/ARBOL-DEL-PROYECTO.md` | Árbol de directorios y mapa de pantallas por rol. |
| `docs/DESPLIEGUE-RAILWAY.md` | Procedimiento detallado de despliegue en Railway. |
| `docs/REVISION-ARQUITECTURA.md` | Revisión de arquitectura y calidad. |
| `docs/DIAGRAMAS-FLUJOS.md` | Diagramas Mermaid de flujos principales. |
| `docs/MATRIZ-QA-MVP.md` | Matriz de casos de QA del MVP. |
| `docs/CONFIGURACION-WHATSAPP.md`, `docs/CONFIGURACION-TELEGRAM.md` | Alta y configuración de las integraciones de mensajería. |
| `ANTIGRAVITY.md` | Alcance permitido para la herramienta de entregas de interfaz. |

## 2. Arquitectura

### 2.1 Estilo arquitectónico

Dentissa es un **monolito modular** sobre Laravel 13 con patrón MVC, extendido con una capa
de **servicios de dominio** (`app/Services`) que concentra las reglas de negocio
compartidas entre canales (panel, agenda pública, asistente web y Telegram). Las vistas se
renderizan en servidor con Blade; la interactividad del cliente se implementa con
componentes Alpine.js, sin SPA ni API REST intermedia.

Justificación: un único despliegue, una única base de datos y transacciones locales
simplifican la consistencia de la agenda (exclusión de reservas concurrentes) y reducen el
costo operativo, requisito del proyecto (presupuesto cero).

### 2.2 Vista en capas

```text
┌───────────────────────────────────────────────────────────────────┐
│ Presentación: Blade + componentes (resources/views/components),    │
│ Alpine.js (resources/js/app.js), Tailwind CSS, Vite, PWA           │
├───────────────────────────────────────────────────────────────────┤
│ HTTP: routes/web.php · middleware (permiso, ExigirDosPasos,        │
│ CerrarSesionUsuarioInactivo, CabecerasSeguridad, NoCachearRespuesta)│
│ Controladores (app/Http/Controllers, Admin/, Auth/) · FormRequests │
├───────────────────────────────────────────────────────────────────┤
│ Dominio: app/Services (DisponibilidadService, CrearCitaService,    │
│ CitaReglasService, NotificacionService, AuditoriaService, ...)     │
│ app/Support (Permisos, MomentoRecordatorio, DiasFestivos, ...)     │
│ app/Rules (TelefonoMexico, SinEnlaces) · app/Policies (CitaPolicy) │
├───────────────────────────────────────────────────────────────────┤
│ Persistencia: Eloquent (26 modelos) · 58 migraciones · MySQL 8.4   │
├───────────────────────────────────────────────────────────────────┤
│ Integraciones: Meta WhatsApp Cloud API · Telegram Bot API ·        │
│ Gmail API / Resend · Google Drive API · Laravel Reverb · Ollama    │
└───────────────────────────────────────────────────────────────────┘
```

### 2.3 Procesos en ejecución

| Proceso | Comando | Entorno |
|---|---|---|
| Servidor web | `php artisan serve` (local, `PHP_CLI_SERVER_WORKERS=4`) / servidor del contenedor en el puerto `PORT` (producción) | Ambos |
| Programador | `php artisan schedule:work` | Producción (Supervisor) |
| Bot de Telegram | `php artisan dentissa:telegram` (long polling) | Producción (Supervisor) / perfil `telegram` local |
| WebSockets | Laravel Reverb, puerto 8080 | Servicio `reverb` |
| IA local (opcional) | Ollama con el modelo `granite3.3:2b` | Perfil `ia` |

En producción, `docker/supervisord-dentissa.conf` define los programas `web`, `telegram` y
`scheduler` dentro de un solo contenedor.

### 2.4 Tareas programadas (`routes/console.php`)

| Comando | Frecuencia | Propósito |
|---|---|---|
| `dentissa:enviar-recordatorios` | Cada 15 minutos (`withoutOverlapping`) | Envía los recordatorios cuyo momento de envío ya llegó. |
| `dentissa:respaldar` | Diario, 03:00 | Respaldo cifrado a Google Drive con retención. |
| `dentissa:bloquear-festivos --avisar` | Día 1 de cada mes, 06:00 | Genera bloqueos de día completo por festivo. |
| `dentissa:cerrar-citas-en-curso` | Diario, 00:05 (`withoutOverlapping`) | Completa citas `en_curso` de días anteriores. |

### 2.5 Comandos Artisan propios

| Comando | Clase | Opciones relevantes |
|---|---|---|
| `dentissa:enviar-recordatorios` | `EnviarRecordatoriosCitas` | — |
| `dentissa:probar-recordatorio` | `ProbarRecordatorioCita` | Envío de prueba controlado. |
| `dentissa:respaldar` | `RespaldarDentissa` | — |
| `dentissa:restaurar-prueba` | `RestaurarPruebaRespaldo` | `--archivo=ruta.dntb` |
| `dentissa:respaldo-llave` | Clausura en `routes/console.php` | Genera una llave de 32 bytes. |
| `dentissa:bloquear-festivos` | `BloquearDiasFestivos` | `[años…]`, `--simular`, `--avisar` |
| `dentissa:cerrar-citas-en-curso` | `CerrarCitasEnCurso` | `--simular` |
| `dentissa:diagnostico` | `DiagnosticarDentissa` | `--remoto` |
| `dentissa:telegram` | `ProcesarMensajesTelegram` | — |

## 3. Pila tecnológica

| Capa | Tecnología | Versión / notas |
|---|---|---|
| Lenguaje | PHP | 8.3 |
| Framework | Laravel | 13 (Breeze para autenticación) |
| Plantillas | Blade | Componentes anónimos en `resources/views/components` |
| Interactividad | Alpine.js | Componentes registrados en `resources/js/app.js` |
| Estilos | Tailwind CSS | Tokens de marca y escala tipográfica en `tailwind.config.js` |
| Empaquetado | Vite | Manifiesto en `public/build/manifest.json` |
| Base de datos | MySQL | 8.4; SQLite en memoria para la suite rápida |
| PDF | barryvdh/laravel-dompdf | Expediente, receta, consentimiento, reportes |
| Hojas de cálculo | PhpSpreadsheet | Licencia MIT; exportación `.xlsx` |
| 2FA | pragmarx/google2fa + bacon/bacon-qr-code | TOTP RFC 6238, QR en SVG |
| Tiempo real | Laravel Reverb + Laravel Echo | Canal privado `users.{id}` |
| Correo | Transporte propio `gmail` (Gmail API) / Resend / `log` | Seleccionable con `MAIL_MAILER` |
| Cifrado de respaldos | ext-sodium | XChaCha20-Poly1305 (secretstream) |
| Contenedores | Docker, Docker Compose | `Dockerfile` (producción), `Dockerfile.local` (desarrollo/QA) |
| CI | GitHub Actions | `.github/workflows/tests.yml` |
| Hospedaje | Railway | Servicios `dentissa` y MySQL separados |
| Control de versiones | Git, GitHub | Ramas + pull requests, Conventional Commits |

**Recursos estáticos.** Las tipografías Figtree (texto) y Fraunces (títulos; 500, 600 y 500
itálica) se sirven desde `public/fonts/` y se precargan, lo que elimina la dependencia de un
proveedor externo de fuentes. `server.php` asigna caché de larga duración a fuentes y
scripts y de un día a imágenes. El favicon se define una sola vez en
`resources/views/partials/favicon.blade.php`.

## 4. Entorno de desarrollo

### 4.1 Requisitos

- Docker Desktop con integración WSL2, o Docker Engine dentro de WSL.
- Git.
- PowerShell 5.1+ (scripts de automatización en `scripts/`).

### 4.2 Primera instalación

```powershell
Set-Location C:\laragon\www\dentissa
Copy-Item .env.example .env
docker compose build app
docker compose run --rm app php artisan key:generate --force
docker compose up -d
docker compose ps
```

Equivalente en WSL:

```bash
cd /mnt/c/laragon/www/dentissa
cp .env.example .env
docker compose build app
docker compose run --rm app php artisan key:generate --force
docker compose up -d
```

La aplicación queda expuesta en `http://localhost:8000`. Al iniciar, el contenedor crea los
directorios de caché en `storage/` y `bootstrap/cache`, necesarios cuando el volumen del
host está vacío.

### 4.3 Servicios de `compose.yaml`

| Servicio | Perfil | Función |
|---|---|---|
| `app` | (predeterminado) | Aplicación Laravel. Monta `resources/views` y `public/images` del host. |
| `mysql` | (predeterminado) | MySQL 8.4; volumen `mysql_data`; crea también el esquema `dentissa_test`. |
| `reverb` | (predeterminado) | Servidor WebSocket, puerto 8080. |
| `telegram` | `telegram` | Proceso del bot. |
| `qa` | `qa` | Imagen efímera de validación: compila Vite y vistas y ejecuta las pruebas de portada, imágenes, PWA y responsividad con SQLite en memoria. No expone puertos ni usa la base MySQL. Incluye claves ficticias de Reverb. |
| `ollama` | `ia` | Modelo de lenguaje local; volumen `ollama_data`. |

### 4.4 Ciclo de trabajo

| Necesidad | Comando |
|---|---|
| Sincronización automática de cambios (recomendado) | `docker compose up --build --watch app` |
| Reconstrucción manual tras cambiar de rama | `docker compose up -d --build app` |
| Reconstrucción tras cambiar `Dockerfile`, seeders o archivos copiados | `docker compose build app` y `docker compose up -d --force-recreate app` |
| Validación de una entrega de interfaz | `docker compose --profile qa run --rm --build qa` |
| Validación y reconstrucción condicional | `powershell -ExecutionPolicy Bypass -File .\scripts\antigravity.ps1` (`-ValidarSolo` omite la reconstrucción) |

Con Compose Watch, los cambios de PHP, rutas, configuración y vistas se sincronizan al
contenedor; los de CSS, JavaScript, dependencias o configuración de Vite/Tailwind
disparan la reconstrucción de la imagen y la regeneración del manifiesto.

`GET /version` devuelve el SHA del checkout y el indicador `working_tree_clean`; en Railway
devuelve `RAILWAY_GIT_COMMIT_SHA`. La paridad entre entornos se confirma comparando ambos
SHA con el árbol local limpio.

### 4.5 Persistencia local

- Datos MySQL: volumen `mysql_data`. `docker compose restart mysql` los conserva;
  `docker compose down -v` **elimina el volumen**.
- Archivos subidos: `storage/`.
- `php artisan migrate:fresh` elimina todas las tablas; no debe ejecutarse sobre una base
  con información que se desee conservar.
- Antes de consultar la base tras un reinicio, debe esperarse el estado `healthy` en
  `docker compose ps`.

### 4.6 Datos de demostración

| Seeder | Uso | Restricciones |
|---|---|---|
| `DatabaseSeeder` | Desarrollo local. | Contiene credenciales fijas de desarrollo; **no debe ejecutarse en Railway**. |
| `ServiciosTratamientosSeeder` | Catálogo real de la clínica con precios de demostración. | Idempotente. Desactiva sin borrar el catálogo de ejemplo anterior para preservar el historial de citas; no modifica lo creado desde el panel. |
| `DemoPresentacionSeeder` | Cuentas de demostración, catálogo y paciente ficticio. | Requiere `DENTISSA_DEMO_SEED_ENABLED` y contraseñas definidas fuera del repositorio; se ejecuta una vez y se retiran las variables. |
| `DemoDatosSeeder` | Pobla con datos ficticios todas las pantallas de la demostración, incluidas galería y casos (ilustraciones en `database/seeders/demo-fotos`). | Solo datos ficticios. |

Catálogo base:

| Servicio | Tratamiento | Categoría | Duración |
|---|---|---|---|
| Odontología General | Valoración | Diagnóstico | 30 min |
| Odontología General | Limpieza Dental | Preventiva | 60 min |
| Odontología General | Retiro de Caries | Restauradora | 60 min |
| Odontología General | Extracción Dental | Quirúrgica | 60 min |
| Odontología Estética | Blanqueamiento Dental | Estética | 90 min |

Las categorías válidas residen en `Tratamiento::CATEGORIAS`. La tabla pivote
`servicios_tratamientos` tiene `created_at` pero no `updated_at`; el modelo pivote
`ServicioTratamiento` sobrescribe `getUpdatedAtColumn()` para devolver `null`.

## 5. Configuración

### 5.1 Gestión de secretos

`.env` está excluido mediante `.gitignore` y `.dockerignore`. Ningún token, contraseña,
clave de API o archivo de credenciales debe versionarse. `.env.example` es la plantilla.
En Railway, los valores se definen como variables privadas del servicio. Los archivos
`storage/app/private/google-oauth-*.json` están excluidos de Git y de los respaldos.

### 5.2 Variables de entorno propias

| Variable | Propósito |
|---|---|
| `APP_TIMEZONE` | Zona horaria de negocio; predeterminada `America/Mexico_City`. Los horarios configurados se interpretan como hora local. |
| `SESSION_LIFETIME`, `SESSION_ENCRYPT` | Inactividad máxima (30 min) y cifrado de sesión. |
| `DENTISSA_MODO_DEMO` | Marca los datos de contacto como ficticios y desactiva sus enlaces. |
| `DENTISSA_TELEFONO`, `DENTISSA_CORREO`, `DENTISSA_DIRECCION`, `DENTISSA_HORARIO_TEXTO` | Valores de contacto por defecto (sobrescritos por `datos_consultorio`). |
| `DENTISSA_PRIVACIDAD_RESPONSABLE`, `_DOMICILIO`, `_CORREO`, `DENTISSA_AVISO_REVISADO` | Contenido y estado de revisión del aviso de privacidad. |
| `DENTISSA_2FA_ADMIN`, `DENTISSA_2FA_EMISOR` | Obligatoriedad de 2FA para perfiles con expediente; emisor mostrado en la aplicación TOTP. |
| `DENTISSA_MODULO_PAGOS`, `DENTISSA_MODULO_ASISTENTE_AGENDA`, `DENTISSA_MODULO_MENSAJES`, `DENTISSA_MODULO_DOS_PASOS`, `DENTISSA_MODULO_GALERIA`, `DENTISSA_MODULO_CASOS` | Interruptores de módulos opcionales (`config('dentissa.modulos')`); todos `false` por omisión. |
| `DENTISSA_RECETA_EXIGE_CEDULA` | Si es verdadero, la receta exige la cédula de quien atendió (predeterminado `false`). |
| `DENTISSA_MAPS_NOMBRE` | Nombre del negocio en Google Maps (sobrescrito por `datos_consultorio.nombre_maps`). |
| `DENTISSA_RESPALDO_CLAVE`, `DENTISSA_RESPALDO_DIAS`, `DENTISSA_RESPALDO_CARPETA`, `DENTISSA_RESPALDO_DIRECTORIO` | Llave de cifrado, retención, carpeta de Drive y directorio alterno (solo ensayos). |
| `DENTISSA_WHATSAPP_PHONE_ID`, `_TOKEN`, `_API_VERSION`, `_IDIOMA`, `_APP_SECRET`, `_VERIFY_TOKEN` | Integración con WhatsApp Cloud API y su webhook. |
| `DENTISSA_TELEGRAM_BOT_TOKEN`, `DENTISSA_TELEGRAM_USUARIO`, `DENTISSA_TELEGRAM_URL` | Bot de Telegram y enlace público. |
| `GMAIL_OAUTH_CLIENT_ID`, `GMAIL_OAUTH_CLIENT_SECRET`, `GMAIL_OAUTH_REFRESH_TOKEN` | Credenciales OAuth de Google para correo y Drive sin archivos JSON. |
| `DENTISSA_ASISTENTE_IA_HABILITADA`, `DENTISSA_OLLAMA_BASE_URL`, `_MODELO`, `_TIMEOUT`, `DENTISSA_ASISTENTE_IA_MAX_FUENTES` | Asistente con IA local. |
| `DENTISSA_DEMO_SEED_ENABLED`, `DENTISSA_DEMO_*_EMAIL`, `DENTISSA_DEMO_*_PASSWORD` | Siembra controlada de la demostración. |
| `REVERB_*`, `BROADCAST_CONNECTION` | Servidor y cliente WebSocket. |

### 5.3 Configuración en base de datos

`datos_consultorio` (fila única: teléfono, WhatsApp, correo, dirección, `nombre_maps`,
misión, visión, foto de inicio) se aplica sobre `config('dentissa.contacto')` en
`AppServiceProvider::boot()` mediante `DatosConsultorio::aplicarAConfiguracion()`, con caché
`datos_consultorio` invalidada al guardar. Un campo vacío o la tabla sin migrar conservan
los valores de entorno. `telefono_enlace` construye el URI `tel:` con prefijo +52.

## 6. Modelo de datos

### 6.1 Entidades principales

| Tabla | Modelo | Descripción |
|---|---|---|
| `users` | `User` | Personal. `rol` (`string(20)`: `super_admin`, `doctor`, `asistente`), `activo`, campos `dos_pasos_*`, `cedula_profesional`, `especialidad`, foto privada. |
| `pacientes` | `Paciente` | Contacto del paciente; `aviso_privacidad_aceptado_en`, `aviso_privacidad_version` (fuera de `$fillable`). |
| `servicios` | `Servicio` | Especialidades: nombre, descripción, foto, `activo` («Disponible»), orden. |
| `tratamientos` | `Tratamiento` | Duración (15–480 min), `precio_referencia`, categoría, `activo`. |
| `servicios_tratamientos` | `ServicioTratamiento` | Relación N:M. |
| `citas` | `Cita` | `fecha_hora`, `estado` (`EstadoCita`), origen, `id_oferta`, `recordatorio_enviado_en`, `asistencia_confirmada_en`, `id_cita_nueva`. |
| `horarios_atencion` | `HorarioAtencion` | Siete filas (una por día) con apertura, cierre y `capacidad_simultanea`. |
| `bloqueos_agenda` | `BloqueoAgenda` | `fecha_inicio`, `fecha_fin`, `hora_inicio`/`hora_fin` (nulas = día completo), `motivo`, `creado_por` (nulo = festivo del sistema). |
| `festivos_omitidos` | `FestivoOmitido` | Festivos retirados manualmente; el comando no los vuelve a crear. |
| `historias_clinicas` | `HistoriaClinica` | Una por paciente (`id_paciente` único); listas en JSON. |
| `exploraciones_clinicas` | `ExploracionClinica` | N por paciente; odontograma JSON (diente FDI → estado). |
| `historial_clinico` | `HistorialClinico` | Consultas; `id_doctor` con `cascadeOnDelete`, `dientes`, `notas_doctor`. |
| `imagenes` | `Imagen` | Galería, casos y adjuntos clínicos (`TipoImagen`). |
| `consentimientos` | `Consentimiento` | Procedimiento, dientes, beneficios, riesgos, alternativas. |
| `planes_tratamiento`, `plan_tratamiento_pasos` | `PlanTratamiento`, `PlanTratamientoPaso` | Estado `activo|terminado|cancelado`; pasos con costo y `hecho_en`. |
| `ofertas` | `Oferta` | `estado` (`EstadoOferta`), beneficio, vigencia, cupo. |
| `testimonios` | `Testimonio` | `EstadoTestimonio`. |
| `preguntas_frecuentes`, `casos`, `mensajes_contacto` | — | Contenido público y contacto. |
| `notificaciones` | `Notificacion` | Entregas externas (WhatsApp/correo) por canal. |
| `notificaciones_sistema` | `NotificacionSistema` | Avisos internos: `vista_en`, `leida_en`, `atendida_en`, `id_cita` (`nullOnDelete`). |
| `logs_auditoria` | `LogAuditoria` | Bitácora con copia de `actor_nombre` y rol. |
| `pagos` | `Pago` | Monto, método, fecha, concepto, `registrado_por`. |
| `datos_consultorio` | `DatosConsultorio` | Configuración editable del consultorio. |

Enumeraciones (`app/Enums`): `RolUsuario`, `EstadoCita`, `EstadoOferta`, `EstadoTestimonio`,
`CanalNotificacion`, `TipoConsulta`, `TipoImagen`, `TipoNotificacion`, `TipoOferta`,
`TipoValorOferta`. Cada una centraliza etiquetas y clases visuales.

### 6.2 Máquina de estados de la cita

| Estado (`EstadoCita`) | Transiciones permitidas | Regla de servidor |
|---|---|---|
| `agendada` (heredado) | `confirmada`, `cancelada`, `no_asistio`, `completada` | Solo registros históricos. |
| `confirmada` | `en_curso`, `cancelada`, `no_asistio`, `completada`, reagendar | `start()` exige día actual, cita no terminada y capacidad disponible. |
| `en_curso` | `completada` | Cierre automático nocturno si queda de un día anterior. |
| `completada`, `cancelada`, `no_asistio` | — | Estados finales. |

- `EstadoCita::esPorAtender()` (agendada, confirmada, en curso) gobierna los conteos.
- `Cita::fin()`, `yaTermino()` y `sePuedeIniciar()` determinan las acciones de
  `citas/partials/acciones`.
- `complete()` acepta citas agendadas/confirmadas cuya hora ya llegó («Atendida» en un
  paso); `noShow()` exige `fecha_hora` ≤ ahora.
- `start()` aplica, dentro de una transacción con `lockForUpdate`, el límite
  `capacidad_simultanea` frente a otras citas `en_curso`.

### 6.3 Política de eliminación

Se evita el borrado físico de cuentas con actividad clínica porque `historial_clinico.id_doctor`
tiene `cascadeOnDelete`: `UsuarioController::destroy()` rechaza la cuenta propia (422) y
cualquier cuenta con `consultasRegistradas`. `logs_auditoria` conserva la identidad del actor
aunque la cuenta llegue a eliminarse.

## 7. Agenda y disponibilidad

### 7.1 Algoritmo de disponibilidad (`DisponibilidadService`)

Entradas: horario del día, duración del tratamiento, margen de preparación, capacidad
simultánea, citas existentes, bloqueos y zona horaria.

1. **Generación de candidatos:** inicios cada `dentissa.intervalo_minutos` (30) desde la
   apertura, siempre que `inicio + duración ≤ cierre`.
2. **Margen de preparación:** las categorías largas o quirúrgicas suman 15 minutos que solo
   se consideran entre citas (`tieneCupo()`), no contra el cierre.
3. **Capacidad:** un candidato es válido si las citas solapadas no inactivas son menos que
   `capacidad_simultanea`.
4. **Bloqueos:** `BloqueoAgenda::tramosDelDia()` devuelve tramos `[inicio, fin)` y
   `chocaCon()` los compara con el intervalo de la cita.
5. **Validación final:** `estaDisponible()` invoca además `dentroDelHorario()` (inicio ≥
   apertura, fin ≤ cierre, desfase múltiplo del intervalo). Toda vía de escritura (panel,
   agenda pública, asistente y Telegram) pasa por esta verificación.

### 7.2 Control de concurrencia

La creación y el reagendamiento ocurren en una transacción que bloquea con `lockForUpdate`
la fila de `horarios_atencion` del día de la semana antes de leer las citas existentes. Ello
provee un recurso de exclusión común aun cuando todavía no existan citas para la fecha. El
costo es una serialización breve de reservas del mismo día de la semana. La prueba
`ConcurrenciaCitasMySQLTest` (QA-34) lanza dos procesos PHP simultáneos contra MySQL 8.4 y
verifica que exactamente uno obtiene el último cupo. En SQLite se omite por no reproducir
`lockForUpdate` de forma representativa.

### 7.3 Endpoints de disponibilidad

| Ruta | Parámetros | Respuesta |
|---|---|---|
| `GET /disponibilidad` | `tratamiento_id`, `fecha`, `exclude_cita_id` (solo personal autorizado) | `{"horarios": ["09:00", ...]}` |
| `GET /disponibilidad/mes` | `tratamiento_id`, `mes=YYYY-MM` | `{"mes", "duracion_minutos", "dias": {"YYYY-MM-DD": {"horarios": n, "estado": "..."}}}` |

- `resumenMensual()` invoca `horariosDisponibles()` por día: el conteo del calendario
  coincide exactamente con la lista que verá el usuario.
- Estados por día: `pasado`, `cerrado`, `sin_horarios`, `disponible`.
- Rango aceptado: mes actual a 12 meses (`DisponibilidadController::MESES_ADELANTE`);
  limitador propio de 60 solicitudes por minuto.
- `exclude_cita_id` en la agenda pública responde 403.
- Los errores de validación se devuelven como JSON 422 (`bootstrap/app.php`).

### 7.4 Componente de calendario

`partials/disponibilidad-picker` es compartido por la agenda pública, **Nueva cita** y
**Reagendar**. El componente Alpine `calendarioDisponibilidad` vive dentro del `x-data` del
formulario y comparte `idTratamiento`, `fecha`, `hora`, `horarios` y `cargarHorarios()`; los
valores viajan en `<input type="hidden">`. Ante cambios rápidos de mes solo se aplica la
última respuesta. Accesibilidad: cada día es un botón con etiqueta completa, navegación por
flechas entre días disponibles y `radiogroup` para los horarios.

### 7.5 Reglas compartidas

`CitaReglasService` centraliza para los tres flujos (interno, público, asistente): catálogo
activo, pertenencia tratamiento–servicio y fecha/hora futura. El filtro de `/citas` es de
consulta y no hereda la restricción de fecha futura. La validación del asistente de agenda
repite en servidor la restricción de fecha mínima, independientemente del atributo `min`.

### 7.6 Bloqueos y festivos

- `BloqueoAgendaController` (`admin.bloqueos.store|destroy`, módulo `configuracion`) valida:
  sin fechas pasadas, horas solo dentro de un día, extensión máxima de 60 días y ausencia de
  citas `agendada|confirmada|en_curso` en el tramo. La lista «Próximos bloqueos» se pagina de cinco en cinco (`ConfiguracionController::BLOQUEOS_POR_PAGINA`, parámetro `bloqueos`, ancla `#bloqueos`). Bitácora: `bloqueo.creado`,
  `bloqueo.eliminado`.
- `App\Support\DiasFestivos::delAnio()` calcula los siete festivos del artículo 74 de la Ley
  Federal del Trabajo (los móviles con `nthOfMonth`), el 1 de octubre sexenal desde 2024 y,
  por decisión de la clínica, el 2 de noviembre y el 12, 24 y 31 de diciembre.
- `dentissa:bloquear-festivos` es idempotente: omite fechas pasadas, días ya bloqueados,
  días con citas activas (reportados) y fechas de `festivos_omitidos`. Se ejecuta en el
  arranque de producción (`scripts/start-production.sh`, sin detenerlo si falla) y
  mensualmente con `--avisar` (aviso `festivos_con_citas`).

- `Admin\CedulaProfesionalController` (invocable, `PUT admin/consultorio/cedulas`, ruta
  `admin.consultorio.cedulas`, módulo `configuracion`) guarda `cedulas[{id}]` solo de
  usuarios activos con el módulo `pacientes` (`CedulaProfesionalController::doctores()`),
  ignora otros identificadores, valida `[0-9A-Za-z-]` con máximo 20 caracteres y registra
  `cedula.actualizada` por cada cambio. Vista `admin/configuracion/_cedulas.blade.php`
  (ancla `#cedulas`). `ProfileUpdateRequest` ya no acepta `cedula_profesional`.

### 7.7 Vistas de la agenda

**Lista diaria** (`GET /citas?fecha=`): una consulta por día, pestañas de la semana con
activas y por confirmar (`pestanasDeLaSemana()`), plegado de citas cerradas en `<details>`,
modo global con `?buscar=` o `?todas=1` (paginado 20/50, ignora `fecha` deliberadamente).
`CitaController::volverALaAgenda()` redirige a `url()->previous()` solo si es `/citas…`; en
otro caso, al día de la cita.

**Mapa semanal** (`GET /citas?vista=semana&semana=`): `lunesDeLaSemana()` normaliza la fecha
y sustituye fechas inválidas por la semana actual; una sola consulta con relaciones
precargadas. `OcupacionSemanalService::calcular()` obtiene por bloque de dos horas:

- ocupación = minutos-cita ÷ (minutos del bloque × `capacidad_simultanea`), excluidas
  canceladas e inasistencias;
- «lleno» cuando la ocupación ≥ 90 % o no quedan inicios con lugar.

El cálculo es informativo: al agendar, `DisponibilidadService` vuelve a validar.

### 7.8 Cierre automático de citas en curso

`dentissa:cerrar-citas-en-curso` cambia a `completada` las citas `en_curso` de días
anteriores con un `UPDATE` que vuelve a filtrar por estado (no sobrescribe cambios
concurrentes) y emite `alertaSistema` con evento `citas_en_curso_cerradas`. No genera
entrada de bitácora porque no es una acción de usuario.

## 8. Agenda pública

### 8.1 Flujo

`GET /agendar-cita` (`CitaPublicaController@create`) acepta `?servicio=ID` y
`?tratamiento=&oferta=` solo si corresponden a elementos agendables. `POST /agendar-cita`
(`store`) delega en `CrearCitaService`, que:

1. valida reglas de catálogo y disponibilidad;
2. exige `acepta_aviso === true` (lanza excepción en caso contrario: última barrera para
   cualquier canal);
3. crea o reutiliza el paciente por teléfono normalizado a 10 dígitos y correo;
4. registra la evidencia de consentimiento con `forceFill`;
5. asigna la oferta solo si es publicada, del mismo tratamiento, vigente en la fecha de la
   cita y con lugar, bloqueando su fila con `lockForUpdate`;
6. crea la cita en estado `confirmada` y emite el aviso interno tras el `commit`.

### 8.2 Validación de datos del paciente

El componente Alpine `datosPaciente` replica las reglas de `store()` (formulario con
`novalidate`): validación al abandonar el campo, cancelación del envío con errores, conteo
de pendientes y foco en el primero. `store()` elimina solo espacios del teléfono; prefijos,
guiones o paréntesis se rechazan. `App\Rules\TelefonoMexico` rechaza números que inician
con 0 o 1 o compuestos por un dígito repetido; se aplica en agenda pública, Telegram, alta y
edición de pacientes y datos del consultorio.

### 8.3 Limitación de tasa

El limitador `agendar-cita` (`AppServiceProvider`) permite 5 envíos por minuto y 20 por hora
por IP. Al excederse, se redirige al formulario con los datos y un mensaje, en lugar de una
respuesta 429.

### 8.4 Decisión de diseño: paciente sin cuenta (aceptada)

| Aspecto | Detalle |
|---|---|
| Contexto | El SRS inicial (RF01, RF02) proponía registro e inicio de sesión del paciente. |
| Decisión | El paciente no tiene cuenta; agenda públicamente y responde al recordatorio mediante enlace firmado. |
| Motivos | Menor fricción; ausencia de contraseñas de pacientes y de acceso público a datos clínicos; el paciente solo confirma o libera su horario, y toda nueva fecha pasa por la validación de disponibilidad. |
| Consecuencias | RF01 aplica solo al personal; RF02 se redefine como agendado público; se agrega RF12 (recordatorio y respuesta). Otros cambios se canalizan por teléfono o WhatsApp. |
| Alternativas descartadas | Cuenta obligatoria; portal completo con enlace mágico (pospuesto; se adoptó el enlace acotado a una cita). |

## 9. Recordatorios y mensajería

### 9.1 Momento de envío

`App\Support\MomentoRecordatorio::para()` calcula el día hábil anterior (abierto en
`horarios_atencion` y sin bloqueo de día completo) a la hora de apertura + 1 h.
`dentissa:enviar-recordatorios` examina citas de los próximos 8 días y envía aquellas cuyo
momento ya transcurrió y que fueron creadas antes de dicho momento.

### 9.2 Flujo

```text
scheduler (*/15) ──> dentissa:enviar-recordatorios
                      │ reclama la cita: UPDATE ... WHERE recordatorio_enviado_en IS NULL
                      ▼
            NotificacionService::citaRecordatorio
              ├─ WhatsApp: plantilla cita_recordatorio + 3 botones
              │            payload recordatorio:{id_cita}:{accion}
              └─ Correo: RecordatorioCita con 3 enlaces firmados
                      ▼
 Correo:   GET (página de confirmación, sin efectos) ──POST──> RespuestaRecordatorioService::responder
 WhatsApp: POST /webhooks/whatsapp ───────────────────────────> RespuestaRecordatorioService::responder
                      ├─ confirmar → asistencia_confirmada_en
                      ├─ cancelar  → cancelada (motivo del paciente)
                      └─ reagendar → cancelada + enlace firmado de reagendamiento
                    + aviso interno (asistencia_confirmada | cancelada_paciente | reagendar_paciente)
```

### 9.3 Decisiones técnicas

- **Idempotencia:** `withoutOverlapping()` y el reclamo atómico por `UPDATE` garantizan un
  único envío aunque dos ejecuciones se solapen. La marca se establece aun si ambos canales
  fallan (intento único auditado en `notificaciones`).
- **Doble canal, no cascada:** el recordatorio intenta WhatsApp y correo y registra una
  fila por cada envío exitoso. Los demás avisos usan WhatsApp con correo de respaldo.
- **Enlaces firmados** (`URL::temporarySignedRoute`): la firma sustituye a la autenticación.
  Vence un día después de la cita; la regla efectiva está en
  `RespuestaRecordatorioService::admiteRespuesta()` (estado activo y cita futura).
- **GET sin efectos, POST con efectos:** protege frente a escáneres de enlaces de clientes de
  correo y antivirus; se aplica Post/Redirect/Get.
- **Exclusión:** `responder()` bloquea la fila con `lockForUpdate`.
- **Reagendamiento:** al reagendar desde el panel se vacían `recordatorio_enviado_en` y
  `asistencia_confirmada_en`.
- **Idioma:** las páginas y mensajes a pacientes fuerzan `locale('es')`.

### 9.4 Reagendamiento por el paciente

- `RespuestaRecordatorioService::enlaceReagendar()` emite una ruta firmada de 7 días a
  `citas.publica.create` con `reagendar=id`; sin firma válida se abre la agenda normal.
- El formulario incluye `tokenReagendar()` (cifrado, 7 días). `citaAReagendar()` lo acepta
  solo si la cita anterior está cancelada por solicitud de reagendar, no tiene
  `id_cita_nueva` y coincide el celular. Servicio, tratamiento, datos y oferta se toman de
  la cita anterior (se ignora lo enviado por el navegador) y no se vuelve a exigir el
  consentimiento si ya existía.
- Resultado: plantilla `cita_reagendada`, aviso `reagendada_paciente`, `id_cita_nueva` en la
  cita anterior y el aviso «Horario liberado» marcado como atendido.

### 9.5 Webhook de WhatsApp

| Elemento | Especificación |
|---|---|
| Verificación | `GET /webhooks/whatsapp`: compara `hub_verify_token` con `DENTISSA_WHATSAPP_VERIFY_TOKEN`. |
| Recepción | `POST /webhooks/whatsapp`, `throttle:120,1`, excluida de CSRF. |
| Autenticidad | `X-Hub-Signature-256` = `sha256=` + HMAC-SHA256 del cuerpo crudo con `DENTISSA_WHATSAPP_APP_SECRET`, comparado con `hash_equals`; sin firma válida responde 403. |
| Mensajes aceptados | `button.payload` o `interactive.button_reply.id` con formato `recordatorio:{id}:{accion}`. |
| Autorización | El remitente debe coincidir con el teléfono del paciente (`WhatsappService::mismoTelefono`, últimos 10 dígitos); en caso contrario, `textoNoAplicada()` (mismo texto si la cita no existe). |
| Deduplicación | `Cache::add` por id de mensaje durante 2 días (Meta reintenta). |
| Otros mensajes | Respuesta automática una vez cada 12 h por número; el contenido no se almacena ni se registra. |
| Respuesta | Con firma válida siempre 200; la respuesta al paciente usa texto libre dentro de la ventana de 24 h. |

El webhook apunta a una sola instancia (Railway); los botones de un recordatorio enviado
desde un entorno local se procesan en producción y no afectan la base local.

### 9.6 Telegram

`AgendaTelegramService` implementa la reserva conversacional y reutiliza
`CrearCitaService`; el resumen previo enlaza el aviso de privacidad y la confirmación
constituye la aceptación. Las ofertas no se aplican en este canal. El token reside solo en
`DENTISSA_TELEGRAM_BOT_TOKEN`; regenerarlo invalida el anterior y requiere redespliegue.

## 10. Expediente clínico

| Componente | Implementación |
|---|---|
| Historia clínica | `historias_clinicas` (1:1). Listas JSON con solo las claves marcadas; catálogos en constantes de `HistoriaClinica`. `alertas()` produce el aviso `x-aviso-clinico` (alergias, `RIESGO_PERSONALES`, embarazo, reacción a anestesia). El formulario reemplaza la historia completa. |
| Exploración y odontograma | `exploraciones_clinicas` (1:N, orden descendente). `indiceOleary()` = superficies con placa ÷ (dientes × 4) × 100 (aceptable ≤ 20 %). Odontograma JSON diente FDI → estado (`ESTADOS_DIENTE`: rojo por tratar, azul existente). `ExploracionClinicaController` descarta renglones vacíos y dientes inexistentes; cada exploración parte de la anterior. |
| Consultas | `HistorialClinicoController`; fecha, tipo y motivo obligatorios en cliente y servidor; `dientes`; `notas_doctor` nunca se renderiza en PDF. |
| Receta | `HistorialClinicoController@receta` (`admin.pacientes.historial.receta`): exige medicamentos; la cédula de `id_doctor` solo se exige si `config('dentissa.recetas.exigir_cedula')` es verdadero (por omisión no, y `pdf/receta` omite esas líneas); la cédula se captura en Configuración (`Admin\CedulaProfesionalController`, ver apartado 7.6), ya no en Mi perfil; toma edad y alergias de la historia; tamaño carta vía `stream()`; bitácora `receta.generada`. |
| Consentimiento | `ConsentimientoController` (`admin.pacientes.consentimientos.create|store|pdf`); textos base en `Consentimiento::BENEFICIOS|RIESGOS|ALTERNATIVAS`; `pdf.consentimiento` con los elementos de la NOM-004-SSA3-2012 y firmas autógrafas. |
| Plan de tratamiento | `PlanTratamientoController` (`admin.pacientes.planes.*`); `PlanTratamiento::avance()` derivado de `hecho_en`; bitácora `plan.creado|actualizado|paso_hecho|paso_pendiente`. |
| Alta de paciente | `admin.pacientes.create|store`: teléfono normalizado y único, correo único, `acepta_aviso` obligatorio; quien no tiene módulo `pacientes` es redirigido al cuestionario. |
| Adjuntos | Servidos por ruta autenticada (`historial.imagenes.show`) solo con módulo `pacientes`; se rechazan imágenes no clínicas. |

En la bitácora, los cambios clínicos almacenan exclusivamente `campos_modificados` y
metadatos (paciente, cita, fecha, tipo); nunca diagnósticos, medicamentos ni notas.

## 11. Contenido público y catálogo

### 11.1 Ofertas

- `estado` (`EstadoOferta`): `pendiente` (En revisión), `activa` (Publicada), `inactiva`
  (Oculta). `store` guarda `pendiente` y `fecha_inicio = hoy`; `update` regresa a
  `pendiente` si cambia algún campo de `CAMPOS_PUBLICOS`.
- `PATCH admin.ofertas.publicar` (rechaza vencidas) y `admin.ofertas.ocultar`, auditadas.
- Formulario → `beneficio` (`descuento|precio|gratis`), `cantidad`;
  `Oferta::claveDeFormulario()` y `columnasDeBeneficio()` traducen a `tipo`, `tipo_valor`,
  `valor_descuento`, `precio_especial`. Descuento máximo 99 %.
- Validación: título 5–120, descripción 20–300, `fecha_fin` ≥ hoy, regla
  `App\Rules\SinEnlaces` (enlaces, dominios, correos, HTML, caracteres repetidos).
- Visibilidad: `Oferta::vigentesConLugar()` (publicadas, agendables, con lugar, ordenadas por
  vencimiento) y `paraPortada()` (primeras `MAXIMO_PORTADA` = 24). `motivoNoVisible()` y
  `motivoNoPublicable()` alimentan los indicadores ⚠.
- Lugares contados con citas reales (`scopeConLugaresUsados()`, `ESTADOS_QUE_LIBERAN`).
- Página pública `GET /promociones` (`PromocionPublicaController`, filtro `?especialidad=`);
  tarjeta compartida `partials/tarjeta-oferta`.

### 11.2 Catálogo

- `Tratamiento::scopeAgendables()` (activo y con especialidad activa) filtra selectores,
  validaciones y ofertas.
- Un tratamiento activo siempre tiene precio: sin `precio_referencia` permanece inactivo y
  la migración `2026_11_20_000001` desactivó los activos sin precio.
- `servicios.activo` solo se modifica con el interruptor de la lista
  (`ServicioController::estado()`); `store()` crea con `activo = true`.
- Orden de portada mediante `servicios.mover`, deshabilitado con filtros activos.

### 11.3 Portada

Orden de secciones: bienvenida, servicios, nosotros, galería, promociones, casos,
testimonios, primera visita, preguntas, ubicación y agenda. Puntos técnicos:

- Imagen principal con `fetchpriority="high"` y dimensiones explícitas (evita CLS); no se
  anima por ser el elemento LCP. `PortadaPublicaTest` falla si una fotografía de
  `images/dentissa/` supera 150 KB.
- Carrusel `carruselServicios` (`app.js`): avance por tarjeta, circular, flechas según tipo
  de puntero, imágenes `loading="lazy"`.
- Estado de apertura: `HorarioAtencion::estadoActual()` con la hora de la clínica.
- Mapa: iframe de Google Maps sin clave de API, `loading="lazy"`. `partials/portada-ubicacion`
  consulta «nombre, dirección» cuando existe `nombre_maps`, de modo que el mapa y «Cómo
  llegar» abren la ficha del negocio; en otro caso, solo la dirección.
- Foto de inicio reemplazable: `datos_consultorio.foto_inicio` (disco `public/portada`),
  `FotoInicioController` (JPG/PNG/WebP ≤ 5 MB; elimina el archivo previo).
- Ilustraciones SVG propias en `public/images/ilustraciones/` como respaldo de la galería.
- Navegación con *scroll-spy* (`aria-current="location"`) actualizada en
  `requestAnimationFrame`.
- Descripción y vista previa al compartir: `partials/meta-compartir` agrega `description`,
  `canonical` (página actual sin parámetros), Open Graph y Twitter en la portada,
  `/promociones` y `/agendar-cita`, cada una con título y descripción propios. La imagen
  `images/dentissa/vista-previa.jpg` (1200 × 630, < 300 KB, logotipo y nombre sobre la foto
  de inicio de fábrica) es fija; si se cambia la foto de inicio, conviene regenerarla.
  Prueba: `VistaPreviaCompartirTest`.

### 11.4 Testimonios

Envío público identificado por teléfono o correo de una cita previa; estado inicial
pendiente con aviso `testimonio_nuevo`. Las acciones aprobar, rechazar y eliminar
(`TestimonioController`) marcan el aviso como atendido cuando ya no quedan pendientes.

## 12. Seguridad

### 12.1 Autorización basada en roles (`config/permisos.php`)

Mapa único de permisos con dos secciones:

- `modulos`: agrupa nombres de ruta con comodines (`inicio`, `cuenta` (`profile.*`),
  `dos_pasos` (`dos-pasos.*`), `notificaciones`, `agenda` (`citas.*`), `asistente_agenda`
  (`asistente-agenda.*`), `directorio_pacientes`, `historia_clinica`, `pacientes`,
  `usuarios`, `reportes`, `pagos`, `mensajes`, `control_actividades`, `catalogo`,
  `contenido`, `configuracion`).
- `roles`: `super_admin` → `*`; `doctor` → inicio, cuenta, dos_pasos, notificaciones,
  agenda, asistente_agenda, directorio_pacientes, pacientes, historia_clinica, pagos;
  `asistente` → inicio, cuenta, dos_pasos, notificaciones, agenda, asistente_agenda,
  directorio_pacientes, historia_clinica, pagos, mensajes.

Mecanismo:

1. El middleware `permiso` (`VerificarPermiso`) resuelve el módulo de la ruta con
   `Permisos::moduloDeRuta()` (gana la **primera** coincidencia; por ello
   `directorio_pacientes` e `historia_clinica` se declaran antes de `pacientes`) y responde
   403 si el rol no lo posee.
2. **Falla cerrado:** una ruta protegida sin módulo se niega a todos;
   `PermisosPorModuloTest` verifica que ninguna quede sin asignar.
3. `Permisos::rolPuede()` niega además los módulos cuyo interruptor en `dentissa.modulos`
   esté apagado (`pagos`, `asistente_agenda`, `mensajes`, `dos_pasos`): el módulo
   desaparece del menú para todos los perfiles y sus rutas responden 403. `phpunit.xml`
   enciende estos módulos para la suite general y `MenuDemoTest` verifica el estado
   predeterminado (apagados), como en producción.
4. `User::puede($modulo)` es consumido por el menú, el inicio, `CitaPolicy`, el acceso a
   adjuntos, el canal en vivo y `User::debeUsarDosPasos()`.
5. `Permisos::rolesCon($modulo)` determina destinatarios de avisos (`notificaciones`) y de
   alertas del sistema (`configuracion`).
6. Protección de la titular: solo una `super_admin` crea, desactiva o elimina otra
   `super_admin` (`rolesAsignables()`, `protegerSuperAdmin()`).

Extender permisos consiste en editar la lista del rol; no requiere cambios en rutas, menús
ni controladores.

### 12.2 Autenticación y sesión

| Control | Implementación |
|---|---|
| Fuerza bruta | Bloqueo tras 5 intentos de inicio de sesión; reto 2FA con `throttle` 5/min. |
| Sesión | Regeneración al autenticar; driver `database`; `SESSION_LIFETIME=30`; `SESSION_ENCRYPT=true`. |
| Cuenta desactivada | `CerrarSesionUsuarioInactivo` en el grupo `web` invalida la sesión en la siguiente petición. |
| Política de contraseñas | `Password::defaults()`: mínimo 10, mayúsculas, minúsculas y números. |
| Cambio de contraseña | Elimina las demás sesiones y rota `remember_token`. |
| Recuperación | Respuesta genérica idéntica para correos existentes e inexistentes (sin enumeración). |
| Cabeceras | `CabecerasSeguridad`: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` y HSTS solo sobre HTTPS. |
| Fotos de perfil | Disco privado (`storage/app/private/profile-photos`), servidas solo a la cuenta propietaria; JPG/PNG/WebP ≤ 5 MB y ≤ 3000×3000 px; vista previa local con `URL.createObjectURL`. |

### 12.3 Verificación en dos pasos (TOTP)

```text
contraseña válida ──> ¿2FA activa? ── no ──> panel
                            │ sí
                            ▼
   se cierra la sesión recién creada; la sesión guarda
   {usuario_id, recordar, expira: +5 min}
                            ▼
   /dos-pasos: código TOTP o código de recuperación ──> login(usuario, recordar) ──> panel
```

- Almacenamiento: `dos_pasos_secreto` (cast `encrypted` con `APP_KEY`),
  `dos_pasos_recuperacion` (8 hashes SHA-256 en `encrypted:array`),
  `dos_pasos_confirmado_en`, `dos_pasos_ultimo_paso`; fuera de `$fillable` y ocultos.
- Antirrepetición: `verifyKeyNewer` acepta solo intervalos posteriores al último usado;
  tolerancia de ±1 intervalo de 30 s.
- Activación: el secreto permanece en sesión hasta confirmar un código válido.
- Obligatoriedad: `ExigirDosPasos` redirige a la activación en toda ruta autenticada (salvo
  activación y `logout`) a quien tenga los módulos `pacientes` y `dos_pasos`, cuando
  `DENTISSA_2FA_ADMIN` es verdadero. Exigir también `dos_pasos` en
  `User::debeUsarDosPasos()` evita un ciclo de redirecciones si la obligatoriedad está
  activa con el módulo apagado. Actualmente el módulo está oculto y la obligatoriedad
  deshabilitada; **ambos deben habilitarse antes de operar con pacientes reales**.
- Recuperación administrativa: vaciar los cuatro campos `dos_pasos_*` del usuario,
  dejando constancia de la intervención.

### 12.4 Bitácora de auditoría

- `AuditoriaService::registrar()` exige una transacción activa y la misma conexión que el
  modelo de negocio; `registrarCambio()` envuelve mutaciones simples. Si la bitácora falla,
  se revierte el cambio y se responde 503.
- `tests/Unit/BitacoraEnTransaccionTest` analiza estáticamente `app/` y falla si se invoca
  `registrar()` fuera de `DB::transaction()` (las pruebas de pantalla no lo detectarían
  porque `RefreshDatabase` ya envuelve cada prueba en una transacción).
- `AuditoriaObserver` deposita eventos genéricos en `App\Support\BitacoraPendiente`
  (singleton por petición) y los escribe en `terminating()` solo si el registro no recibió un
  evento detallado; así cada cambio aparece una única vez.
- `fecha = now()` en la zona de la aplicación.
- Alcance: solo acciones autenticadas del personal; se excluyen operaciones del portal y de
  Telegram, contraseñas, valores clínicos y valores de contacto (solo nombres de campo).
- `App\Support\DetalleBitacora::filas()` traduce cada cambio a `[campo, antes, después]`
  legible; nuevos campos deben registrarse en `DetalleBitacora::CAMPOS` y `VALORES`.
- Consulta: `admin.auditoria.index` en `/admin/control-de-actividades` (la ruta
  `/admin/auditoria` redirige con 301 conservando filtros); no expone IP ni agente de usuario.

### 12.5 Protección de datos personales

- Página `/aviso-de-privacidad` (`privacidad.aviso`) parametrizada por
  `config('dentissa.privacidad')`; muestra «[Por completar: …]» y «Borrador pendiente de
  revisión legal» hasta que `DENTISSA_AVISO_REVISADO` sea verdadero. `privacidad.version`
  debe actualizarse con cada cambio de texto.
- Consentimiento obligatorio en todos los canales y evidencia en `pacientes` (fecha y
  versión) escrita solo con `forceFill`.
- Las citas capturadas por el personal recaban el consentimiento de manera presencial.
- El texto es una plantilla técnica y no constituye asesoría legal.

### 12.6 Pruebas sin efectos externos

`tests/TestCase.php` anula las credenciales de WhatsApp, Telegram y Gmail y activa
`Http::preventStrayRequests()`: cualquier llamada HTTP sin `Http::fake()` hace fallar la
prueba. `phpunit.xml` y `TestCase` fuerzan la IA deshabilitada.

### 12.7 Superficie de API

La decisión del proyecto es entregar Dentissa exclusivamente como aplicación web. Sin
embargo, la rama `master` vuelve a registrar `routes/api.php` (en `bootstrap/app.php`) con:

| Ruta | Autenticación |
|---|---|
| `POST /api/app/login` (`Api\AppAuthController`) | Credenciales de usuario; acepta los roles `super_admin`, `doctor` y `asistente`. |
| `POST|GET /api/iot/estado`, `POST /api/iot/actuadores`, `GET /api/iot/comandos`, `POST /api/iot/puerta/abrir`, `GET /api/iot/estadisticas` | Ninguna; el estado se conserva en caché. |

> **Riesgo abierto.** Las rutas IoT no exigen clave de dispositivo (cualquier cliente puede
> invocar `POST /api/iot/puerta/abrir`) y el inicio de sesión de la aplicación no aplica
> limitación de intentos específica ni verificación en dos pasos. Además, `SinApiMovilTest`
> afirma que no existe ninguna ruta de API, por lo que falla con este estado de `master`.
> Antes de operar con pacientes reales debe decidirse entre retirar la API o protegerla con
> tokens por usuario (Sanctum), clave por dispositivo, limitación de intentos y los roles
> vigentes, y ajustar la prueba en consecuencia.

### 12.8 Dependencias

`composer audit` debe ejecutarse antes de cada entrega; las actualizaciones de seguridad se
aplican en commits independientes dentro de las restricciones de `composer.json`.

## 13. Notificaciones internas en tiempo real

- Almacenamiento separado: `notificaciones` (entregas externas) y `notificaciones_sistema`
  (avisos internos). `NotificacionInternaService` genera avisos por altas y transiciones de
  citas (incluidas las del portal y Telegram) para los roles con módulo `notificaciones`,
  excepto el autor del cambio. Un fallo al persistir un aviso no revierte la cita.
- `vista_en` (abrió la campana), `leida_en` (abrió el aviso) y `atendida_en` (resuelto) son
  estados independientes.
- Prioritarios (`EVENTOS_PRIORITARIOS`): cancelación, cancelación del paciente, solicitud de
  reagendar, inasistencia, respaldo fallido, `testimonio_nuevo`, `festivos_con_citas`.
- `accionPrincipal()` determina el destino por tipo de evento.
- Transmisión: evento `NotificacionSistemaCreada` por Reverb/Echo en el canal privado
  `users.{id}`, despachado con `DB::afterCommit()` para evitar avisos de transacciones
  revertidas. La carga útil incluye el fragmento HTML de `notificaciones/partials/aviso-panel`
  (render idéntico en carga inicial y en vivo) y nunca datos clínicos.
- Respaldo: sondeo a `notificaciones.actualizaciones` cada 3 s, sin caché y filtrado por
  cuenta.
- `NotificacionSistema::booted()` trunca `titulo` (120) y `mensaje` (255) para evitar
  errores «Data too long» en MySQL.
- Avatar: tono derivado de `crc32` del nombre (6 tonos); clases `avatar-tono-N` declaradas
  fuera de `@layer` para que Tailwind no las purgue.
- Sonido opcional mediante Web Audio API, preferencia en `localStorage` por usuario.

## 14. Asistentes

### 14.1 Asistente de pacientes

`AsistentePacienteService` normaliza mayúsculas, acentos y puntuación, tolera errores
tipográficos leves y responde con preguntas frecuentes activas; ante coincidencias débiles o
ambiguas aplica la respuesta de respaldo.

`AsistenteIaLocalService` (opcional) envía a Ollama el conjunto de fuentes elegibles
(preguntas activas y catálogo) y recibe **solo el identificador** de una fuente; Laravel
responde con el texto aprobado original. Controles: prefiltrado por términos distintivos,
revalidación del identificador devuelto, exclusión de consultas clínicas, precios solo con
fuentes que los mencionen y ausencia de registro de la consulta en logs. Solo se aceptan
endpoints HTTP locales o bajo `.railway.internal` en el puerto 11434.

Activación local:

1. `DENTISSA_ASISTENTE_IA_HABILITADA=true` en `.env`.
2. `docker compose --profile ia up -d --build app mysql ollama`.
3. `docker compose exec ollama ollama pull granite3.3:2b`.
4. Verificar con `docker compose exec ollama ollama list`.

`DENTISSA_OLLAMA_TIMEOUT` (60 s por defecto) cubre la carga en frío del modelo. En Railway,
Ollama se ejecuta como servicio privado con volumen en `/root/.ollama` y sin dominio
público; el modo *serverless* puede retrasar la primera respuesta.

### 14.2 Asistente de agenda

`AsistenteAgendaService` consulta disponibilidad por servicio, tratamiento y fecha (`hoy`,
`mañana`, `DD/MM/AAAA`, `DD-MM-AAAA`, `AAAA-MM-DD`), rechaza fechas inválidas o pasadas
antes de invocar `DisponibilidadService` y no crea citas. La sesión de búsqueda caduca a
los 15 minutos de inactividad.

### 14.3 Widget en dispositivos móviles

El identificador de conversación usa `crypto.randomUUID()` cuando está disponible y un UUID
v4 alternativo en contextos no seguros (HTTP por IP en LAN). El acceso a `localStorage` está
protegido con manejo de errores. Por debajo de 640 px, un *listener* pasivo de `scroll`
oculta el botón al descender y lo muestra al ascender, para no cubrir los controles de la
agenda; con `prefers-reduced-motion` el cambio es instantáneo.

## 15. Reportes

`ReporteService` calcula indicadores por rango (citas por estado, pacientes nuevos,
tratamientos más solicitados, consultas) y acumulados (pacientes, catálogo activo).

**Tasa de ocupación** (`ReporteService::ocupacion()`): minutos de citas no canceladas ÷
minutos de atención (horario × `capacidad_simultanea`), excluidos los días bloqueados
completos. Devuelve `capacidad_total`, `minutos_ocupados` y `citas_atendidas`.

Exportación: PDF con DomPDF y `.xlsx` real con PhpSpreadsheet (`ReporteExcelService`), que
escribe números como valores numéricos y textos como cadenas para impedir la inyección de
fórmulas. El rango rechaza fechas futuras en cliente y servidor.

## 16. Interfaz y sistema de diseño

### 16.1 Tokens y tipografía

- Colores de marca: rosa `#d75078`, durazno `#f2b0a6`, fondo `#FAF6F6`, texto `#2D2526`,
  salvia `#4E8D72`, bordes `#e9dfdb`. Neutros como canales RGB en `resources/css/app.css`
  (`--c-cream`, `--c-shell`, `--c-line`, `--c-ink`, `--c-ink-soft`, `--c-muted`, `--c-field`)
  para admitir transparencias; `--c-field` ofrece contraste 3.3:1 sobre blanco.
- Escala tipográfica en `tailwind.config.js` (`display` a `caption`), mínimo 12 px; `h1`/`h2`
  con `text-wrap-style: balance` y párrafos `pretty`.
- Ninguna vista utiliza la paleta genérica de Tailwind (`gray`, `blue`, etc.).

### 16.2 Componentes globales

| Pieza | Componente |
|---|---|
| Encabezado de página (`h1` único) | `x-admin.header` |
| Tarjeta | `x-admin.card` |
| Filtros de lista | `x-filtro.barra` |
| Campos | `x-text-input`, `x-textarea`, `x-select`, `x-opcion`, `x-campo-contrasena`, `x-contrasena-nueva` |
| Botones | `x-primary-button`, `x-secondary-button`, `x-boton-enlace`, `x-boton-cancelar` |
| Acciones de fila | `x-admin.acciones-fila`, `x-admin.boton-fila`, `x-admin.interruptor-estado` |
| Tablas | `x-admin.table` (corta `.tabla-apilable` / larga `:larga="true"`) |
| Avisos y confirmación | `x-alert`, `x-avisos-sesion`, `x-dialogo-confirmar`, `x-ayuda` |
| Otros | `x-marca`, `x-volver`, `x-texto-expandible`, `x-chip`, `x-icon`, `x-aviso-clinico` |

### 16.3 Reglas verificadas automáticamente

| Regla | Prueba |
|---|---|
| Un solo `<h1>` por pantalla; campos de 44 px; solo colores de la paleta; asterisco en obligatorios; confirmación con diálogo propio (`data-confirmar`, nunca `confirm()`); `.panel-contenido` en toda pantalla; `x-volver` en pantallas hijas | `ConsistenciaDisenoTest` |
| Ningún botón anidado dentro de `<a>` | `QaPanelProduccionTest` |
| Tablas cortas apilables y largas con columna fija | `TablasMixtasTest` |
| Un solo control de regreso por formulario | `BotonesFormularioTest` |
| Ninguna acción visible que el servidor rechace | `AccionesPosiblesTest` |
| Filtros que ignoran valores inválidos y conservan paginación | `FiltrosListasTest` |

### 16.4 Comportamientos globales de `app.js`

- Validación HTML5 interceptada en fase de captura y presentada bajo el campo con
  `aria-invalid`; grupos de radio con un solo mensaje por `<fieldset>`.
- Formularios PUT/PATCH con botones deshabilitados hasta que la huella de `FormData`
  cambia (`data-sin-esperar-cambios` lo excluye).
- Botones con estado de carga (`data-loading-text`), restablecidos al volver o a los 12 s
  (`data-sin-carga` lo excluye).
- Búsquedas con `whereContiene()` (macro con escape `ESCAPE '!'` compatible con MySQL y
  SQLite) y filtros leídos mediante `Controller::filtros()`.

### 16.5 Diseño adaptable

- Menú completo desde `lg` (1024 px); anchos de verificación: 375, 768, 1024, 1280, 1366 y
  1920 px.
- `viewport-fit=cover` con `env(safe-area-inset-*)`; campos de 16 px en `pointer: coarse`;
  objetivos táctiles de 44 px.
- En escritorio, el panel se ajusta al alto de la ventana (`.panel-marco`, `.panel-pagina`,
  `.panel-contenido`), con desplazamiento solo del contenido.
- Cuadrículas con `grid-cols-1` explícito y `min-w-0` para evitar desbordes.

### 16.6 Movimiento

Se conservan cinco animaciones con propósito (cambio de paso, confirmación de cita, avisos,
menú de acciones y carga de botones), solo sobre `transform` y `opacity`, con la curva
`--ease-out` (`cubic-bezier(0.23, 1, 0.32, 1)`), y atenuadas bajo `prefers-reduced-motion`.

### 16.7 Internacionalización

`config/app.php` usa `es`; traducciones en `lang/es/` (`validation`, `auth`, `passwords`,
`pagination`). Todo campo nuevo debe incorporarse a `attributes` de `validation.php`.

### 16.8 Herramienta de casos extremos

Con `APP_ENV=local`: `/dev/casos-extremos/{agenda|pacientes|agendar|confirmacion|dashboard}?datos=demo|peor|vacio|uno|muchos`,
con datos en memoria (`App\Support\Dev\CasosExtremos`) y sin escritura en base.

### 16.9 PWA

El layout incluye el manifiesto y sus iconos; `public/sw.js` aplica un caché básico y sirve
`offline.html` sin conexión.

## 17. Respaldo y recuperación

### 17.1 Respaldo automático en servidor (`dentissa:respaldar`)

```text
base activa ──> VolcadoBaseDatos ─┐
  SQLite: VACUUM INTO             │
  MySQL: PDO en START TRANSACTION ├─> respaldo.zip + manifiesto.json (SHA-256 y conteos)
  WITH CONSISTENT SNAPSHOT        │            │
storage/app/private (excluye      ┘            ▼
google-oauth-*.json)                 CifradoRespaldo (XChaCha20-Poly1305) → .dntb
                                               ▼
                                     GoogleDriveDestino: «Dentissa respaldos»
                                     retención: DENTISSA_RESPALDO_DIAS (30), mínimo 3 copias
```

| Decisión | Justificación |
|---|---|
| Volcado por PDO, sin `mysqldump` | El cliente de Debian es MariaDB y puede fallar al autenticar contra MySQL 8. |
| Tablas efímeras solo con estructura | `sessions`, `cache`, `jobs`, `password_reset_tokens`. |
| `time_zone = '+00:00'` durante el volcado | Evita desplazamiento de columnas `TIMESTAMP` al restaurar en otra zona. |
| Cifrado autenticado antes de salir del servidor | Un byte alterado o un archivo truncado invalidan el descifrado. |
| Alcance OAuth `drive.file` | Acceso únicamente a los archivos creados por la aplicación; subida reanudable. |
| Token compartido (`GoogleAccessToken`) | Un solo flujo OAuth para correo y Drive. |
| Interfaz `DestinoRespaldo` | Destino sustituible (Drive, memoria en pruebas). |
| Alerta | «Respaldo fallido» a la titular en la campana y por correo; el log solo guarda el mensaje. |

### 17.2 Restauración de prueba (`dentissa:restaurar-prueba`)

Descarga la copia más reciente (o `--archivo`), la descifra, verifica las huellas SHA-256 y
la carga de forma aislada: en SQLite ejecuta `PRAGMA integrity_check`; en MySQL importa en
`<base>_restauracion_prueba`, compara conteos con el manifiesto y vacía las tablas. Si el
usuario carece de privilegios, el comando imprime el `CREATE DATABASE` y el `GRANT`
necesarios. **Nunca modifica la base ni los archivos activos.** Debe ejecutarse al menos
una vez al mes.

### 17.3 Configuración

1. Habilitar Google Drive API en el proyecto de Google Cloud (sin ella, error 403
   `accessNotConfigured`).
2. Autorizar con `scripts/google-oauth.ps1` (alcances `gmail.send` y `drive.file`) y
   actualizar `GMAIL_OAUTH_REFRESH_TOKEN`.
3. Generar la llave con `php artisan dentissa:respaldo-llave`, almacenarla en un gestor de
   contraseñas y en `DENTISSA_RESPALDO_CLAVE`. **Su pérdida impide recuperar los respaldos.**
4. Validar con `php artisan dentissa:respaldar` y `php artisan dentissa:restaurar-prueba`.

La aplicación OAuth debe estar publicada («En producción»): en modo de prueba, Google
invalida los *refresh tokens* a los 7 días, incluidos los emitidos antes de la publicación.

### 17.4 Evidencia de validación

| Fecha | Entorno | Resultado |
|---|---|---|
| 16/11/2026 | Local, SQLite | Respaldo de 12.5 KB; restauración con conteos idénticos en 0.3 s. |
| 18/11/2026 | MySQL 8.4 con datos ficticios y Drive real | 256.6 KB subidos en 11 s; restauración en 13 s; 18/18 tablas idénticas por `CHECKSUM TABLE`; archivos idénticos byte a byte; sin escrituras en la base activa. |
| 18/11/2026 | Railway | Primera copia en 2.2 s; restauración aislada en 4.7 s. |
| 19/11/2026 | Railway | Primera ejecución programada a las 03:00. |

### 17.5 Respaldo manual local (Restic)

`scripts/respaldo-dentissa.ps1`, `scripts/respaldo-dentissa-wsl.sh` y
`scripts/restaurar-prueba-dentissa.ps1` operan un repositorio Restic cifrado en unidad
externa, con contraseña interactiva no persistida, volcado consistente, manifiesto SHA-256,
`restic check --read-data-subset=1/10` y sin retención automática. Desde PowerShell, la
unidad se monta en WSL con `wsl -d Ubuntu -u root -- mount -t drvfs E: /mnt/e`. Estos
scripts no se ensayaron (QA-31 y QA-32 diferidos); el mecanismo vigente en servidor es el
de la sección 17.1.

## 18. Correo electrónico

El transporte propio `gmail` envía mediante Gmail API sin modificar destinatarios. Opera con
el par de archivos privados `storage/app/private/google-oauth-client.json` y
`google-oauth-token.json`, o con las tres variables `GMAIL_OAUTH_*`; una configuración
parcial produce un error genérico sin registrar valores. Resend o `log` pueden seleccionarse
con `MAIL_MAILER` sin retirar esta integración. `GmailApiTransportTest` simula el
intercambio de token y el envío.

## 19. Diagnóstico de configuración (`dentissa:diagnostico`)

| Área | Verificación local | Con `--remoto` |
|---|---|---|
| Aplicación | `APP_KEY`, `APP_DEBUG` apagado en producción, `APP_URL` HTTPS, `APP_NAME` | — |
| Sesiones | Cookie segura, cifrado, inactividad ≤ 30 min | — |
| Base de datos | Conexión y migraciones pendientes | — |
| Almacenamiento | `storage/app/private` escribible | — |
| Correo y Google | Transporte, remitente válido, credenciales | Token y alcances vía `tokeninfo`; último respaldo < 36 h |
| Respaldos | Llave válida de 32 bytes | — |
| WhatsApp | Cuatro variables requeridas | Número, nombre, estado y calidad en Meta; `DENTISSA_TELEFONO` distinto del número del bot |
| Privacidad | Datos del responsable y estado de borrador | — |
| Seguridad | Obligatoriedad de 2FA y cuentas sin activarla | — |
| Telegram | Token del bot | — |

El comando nunca imprime secretos, depura los mensajes de error de terceros y termina con
código de error si existe algún fallo, lo que permite usarlo en scripts.

## 20. Estrategia de pruebas y aseguramiento de calidad

### 20.1 Niveles

| Nivel | Herramienta | Cobertura |
|---|---|---|
| Unitarias | PHPUnit (`tests/Unit`) | Reglas puras y análisis estático (p. ej., `BitacoraEnTransaccionTest`). |
| Funcionales | PHPUnit (`tests/Feature`) | Rutas, permisos, validaciones, máquina de estados, agenda, recordatorios, webhook, respaldo, reportes, vistas. |
| Concurrencia | `ConcurrenciaCitasMySQLTest` | Dos procesos simultáneos sobre MySQL real. |
| Interfaz | Pruebas de consistencia, casos extremos, tablas, tipografía y responsividad | Reglas de diseño y estructura del HTML. |
| Manual | `docs/CHECKLIST-VERIFICACION.md`, `docs/MATRIZ-QA-MVP.md` | Aceptación por rol y por dispositivo. |

Al 26/11/2026, el directorio `tests/` contiene 99 archivos (94 funcionales y 5 unitarios)
con 584 métodos de prueba, según el conteo del código fuente.

### 20.2 Ejecución

| Objetivo | Comando |
|---|---|
| Suite completa (SQLite en memoria) | `docker compose exec app php artisan test` |
| Suite sobre MySQL aislado | `docker compose exec -e DENTISSA_TEST_DB_CONNECTION=mysql -e DENTISSA_TEST_DB_DATABASE=dentissa_test app php artisan test` |
| Prueba de concurrencia | `docker compose run --build --rm --no-deps -e DENTISSA_TEST_DB_CONNECTION=mysql -e DENTISSA_TEST_DB_DATABASE=dentissa_test app php artisan test --compact --filter=ConcurrenciaCitasMySQLTest` |
| Una clase | `docker compose exec app php artisan test --filter=CitaPublicaTest` |
| Imagen limpia | `docker compose --profile qa build qa` y `docker compose --profile qa run --rm --no-deps --entrypoint php qa artisan test` |
| Estilo | Laravel Pint |

Las pruebas nunca utilizan la base operativa `dentissa`; `RefreshDatabase` reconstruye
`dentissa_test` o la base en memoria.

### 20.3 Integración continua (`.github/workflows/tests.yml`)

Se ejecuta en cada envío a cualquier rama y en cada pull request hacia `master`:

- job SQLite con la suite general;
- job `test-mysql` sobre MySQL 8.4, incluida la prueba de concurrencia y el esquema aislado
  de restauración;
- construcción de ambos Dockerfiles desde el mismo commit;
- verificación agregada `Tests / release-gate`.

`master` debe protegerse exigiendo pull request y la aprobación de `Tests / release-gate`;
Railway despliega desde `master` tras la fusión.

### 20.4 Flujo de control de versiones

1. Actualizar `master`.
2. Crear una rama por propósito (`feat/…`, `fix/…`, `docs/…`, `chore/…`).
3. Realizar cambios acotados y ejecutar las pruebas afectadas.
4. Confirmar con mensajes Conventional Commits.
5. Publicar la rama y abrir un pull request hacia `master`.
6. Fusionar tras la aprobación de CI y sincronizar `master` local.

Al 26/11/2026, la rama `master` registra 378 commits y 111 fusiones desde el 5 de octubre
de 2026.

## 21. Despliegue en Railway

### 21.1 Topología

| Servicio | Construcción | Observaciones |
|---|---|---|
| `dentissa` | `Dockerfile` raíz (builder Dockerfile) | Ejecuta `scripts/start-production.sh`. |
| MySQL | Servicio administrado | Red privada; sin exposición pública. |
| Ollama (opcional) | Imagen `ollama/ollama` | Servicio privado con volumen; sin dominio público. |

Railway no ejecuta `compose.yaml`; la aplicación y la base son servicios separados.

### 21.2 Secuencia de arranque (`scripts/start-production.sh`)

1. `php artisan migrate --force`.
2. `dentissa:bloquear-festivos` (no interrumpe el arranque si falla).
3. `dentissa:cerrar-citas-en-curso` (no interrumpe el arranque si falla).
4. Supervisor inicia `web` (puerto `PORT`), `telegram` y `scheduler`.

Healthcheck: `GET /up`.

### 21.3 Lista de comprobación de producción

1. Variables privadas definidas en el panel; nunca en Git, comandos de inicio, capturas o
   logs.
2. `php artisan dentissa:diagnostico --remoto` sin errores.
3. `php artisan schedule:list` con las cuatro tareas programadas.
4. Respaldo y restauración de prueba ejecutados con éxito.
5. `DENTISSA_MODO_DEMO=false`, datos reales del consultorio y aviso revisado
   (`DENTISSA_AVISO_REVISADO=true`).
6. `DENTISSA_MODULO_DOS_PASOS=true` y `DENTISSA_2FA_ADMIN=true`.
7. Resolución del riesgo de la API móvil e IoT (sección 12.7).
8. Límite de consumo y alertas configurados en Railway.
9. `composer audit` sin vulnerabilidades pendientes.

El procedimiento completo se detalla en `docs/DESPLIEGUE-RAILWAY.md`.

### 21.4 Rotación de credenciales

Al rotar un token (por ejemplo, el *refresh token* de Google), debe copiarse al portapapeles
sin mostrarlo en pantalla, pegarse en la variable del servicio, redesplegarse y limpiar el
portapapeles; a continuación debe ejecutarse `dentissa:diagnostico --remoto`.

## 22. Mantenimiento y extensión

| Tarea | Procedimiento |
|---|---|
| Agregar una pantalla del panel | Asignar sus rutas a un módulo de `config/permisos.php`; usar `.panel-pagina > .panel-contenido`, `x-admin.header` y componentes globales. |
| Otorgar o retirar permisos a un rol | Editar su lista en `roles`. |
| Agregar un campo auditado | Registrar su etiqueta en `DetalleBitacora::CAMPOS` y, si es enumerado, en `VALORES`. |
| Agregar un campo validado | Incorporarlo a `attributes` en `lang/es/validation.php`. |
| Nuevo canal de reserva | Utilizar `CrearCitaService` (garantiza disponibilidad, consentimiento y oferta). |
| Nueva fotografía pública | Comprimir a ≤ 150 KB antes de versionarla. |
| Nueva escritura auditada | Envolver en `DB::transaction()` junto con `AuditoriaService::registrar()`. |
| Habilitar pagos | `DENTISSA_MODULO_PAGOS=true`; las tablas ya existen. |
| Cambiar destino de respaldo | Implementar `DestinoRespaldo` y registrarlo en `AppServiceProvider`. |

### 22.1 Mejoras pendientes identificadas

- Conversión de fotografías de la portada a WebP para reducir la carga en dispositivos
  móviles.
- Aceptación visual en teléfono y tableta físicos.
- Ensayo de los scripts Restic locales (QA-31 y QA-32).
- Política de retención aprobada para respaldos manuales.

## 23. Entregas de interfaz con herramientas externas

La herramienta Antigravity trabaja sobre el mismo directorio del proyecto con el alcance
definido en `ANTIGRAVITY.md`: puede modificar vistas y recursos de interfaz, pero no
backend, rutas, base de datos, Docker ni credenciales. Como el servicio `app` monta
`resources/views` y `public/images`, Laravel utiliza directamente los archivos guardados.
`scripts/antigravity.ps1` ejecuta la validación `qa` en Docker y reconstruye la aplicación
solo si esta aprueba.
