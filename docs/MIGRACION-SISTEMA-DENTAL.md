# Migración de `sistema_dental` a `dentissa`

**Fecha:** agosto 2026
**Origen:** `C:\laragon\www\sistema_dental` (Laravel + React/Inertia)
**Destino:** `C:\laragon\www\dentissa` (Laravel + Blade/Alpine.js)

## 1. Contexto

`sistema_dental` fue el primer intento de construir el sistema de gestión para
el consultorio de Melissa López N. (Odontología Integral). Se quedó sin
continuidad por un tema de presupuesto. `dentissa` es el reinicio del mismo
proyecto de negocio, arrancado desde cero con `laravel new` + Breeze, usando
un stack más simple (Blade + Alpine.js en vez de React + Inertia) para reducir
la complejidad y el costo de mantenimiento a futuro.

Esta migración trajo **toda la lógica de negocio** ya construida en
`sistema_dental` (modelos, reglas, validaciones, base de datos, PDFs, panel de
administración) hacia el proyecto nuevo, **reescribiendo el frontend** de
React a Blade porque son stacks incompatibles entre sí — no existe un
"copiar y pegar" posible para las vistas.

## 2. Decisiones tomadas al inicio (con el usuario)

Antes de tocar código se acordaron tres cosas:

1. **Alcance**: migrar solo el código de negocio (modelos, controladores,
   migraciones, vistas propias), **no** un reemplazo total del proyecto — se
   conservó el scaffolding de autenticación que Breeze ya había generado en
   `dentissa`.
2. **Historial de git**: no se trajo el historial de commits de
   `sistema_dental`; solo el código tal como estaba en ese momento.
3. **`.env`**: no se copiaron credenciales ni configuración de entorno de
   `sistema_dental`. `dentissa` sigue usando su propio `.env` (SQLite en
   desarrollo). Esto queda pendiente de configurar por el usuario cuando
   pase a producción.

Después, al explorar `sistema_dental`, se descubrió que su frontend era
**React + Inertia** (44 páginas `.jsx`), no Blade. Se le preguntó al usuario
cómo resolverlo y se decidió: **mantener Blade en `dentissa`** y reescribir
las 44 páginas a mano.

## 3. Backend migrado (prácticamente 1:1)

Todo esto es independiente del stack de frontend, así que se copió con
ajustes mínimos:

| Elemento | Detalle |
|---|---|
| **Modelos** (`app/Models`) | 14 modelos: `Caso`, `Cita`, `HistorialClinico`, `HorarioAtencion`, `Imagen`, `LogAuditoria`, `MensajeContacto`, `Notificacion`, `Oferta`, `Paciente`, `Servicio`, `ServicioTratamiento`, `Testimonio`, `Tratamiento`. El modelo `User` se **reemplazó** por el de `sistema_dental` (usa `nombre`/`apellido`/`telefono`/`rol`/`foto_perfil`/`activo` en vez del `name` genérico de Breeze). |
| **Migraciones** (`database/migrations`) | 22 migraciones nuevas copiadas en orden cronológico (creación de tablas de pacientes, servicios, tratamientos, citas, ofertas, testimonios, historial clínico, imágenes, notificaciones, logs de auditoría, horarios de atención, casos, y varios ajustes/índices posteriores). |
| **Enums** (`app/Enums`) | `CanalNotificacion`, `EstadoCita`, `EstadoTestimonio`, `RolUsuario`, `TipoConsulta`, `TipoImagen`, `TipoNotificacion`, `TipoOferta`, `TipoValorOferta`. |
| **Policy** | `CitaPolicy` (autoriza crear/editar/cancelar/confirmar/completar citas). |
| **Services** (`app/Services`) | `DisponibilidadService` (calcula horarios libres para agendar) y `ReporteService` (genera las métricas del panel de reportes). |
| **Middleware** (`app/Http/Middleware`) | `EnsureUserHasRole` (alias `role`) y `NoCachearRespuesta` (alias `no-cache`), registrados en `bootstrap/app.php`. **No** se trajo `HandleInertiaRequests` porque es exclusivo de Inertia y no aplica a Blade. |
| **Form Requests** | `LoginRequest` (de `sistema_dental`) y `ProfileUpdateRequest` (el de Breeze, pero corregido — ver sección 5). |
| **Config** | `config/dentissa.php` (datos de contacto público, parámetros de disponibilidad de citas). |
| **Seeders/Factories** | `DatabaseSeeder` (crea admin, asistente y paciente demo) y `ServiciosTratamientosSeeder`, más el `UserFactory` ajustado a los campos reales. |
| **Assets públicos** | Logos (`public/images/brand/*.jpg`) y symlink de `storage` (`php artisan storage:link`) para servir imágenes subidas (servicios, tratamientos, ofertas, galería, casos). |
| **Paquete Composer** | Se instaló `barryvdh/laravel-dompdf`, que faltaba en `dentissa` y es necesario para los PDFs de historial clínico y reportes. |

### Controladores

Se copiaron los 20 controladores de negocio (`CitaController`,
`CitaPublicaController`, `DisponibilidadController`, `GaleriaController`,
`HistorialImagenController`, `MensajeContactoController`, `OfertaController`
y los 11 de `Admin/`). Los que usaban `Inertia::render('Pagina/X', [...])` se
adaptaron a `view('pagina.x', [...])` (convención de puntos de Blade,
`Admin/X/Y` → `admin.x.y`). Los que no dependían de Inertia (JSON, streams,
redirects puros) se copiaron sin cambios.

### Rutas

`routes/web.php` se reconstruyó fusionando la estructura pública/privada/admin
de `sistema_dental` con las rutas de `profile`/`dashboard` que ya traía
`dentissa`. `routes/auth.php` se reemplazó por el de `sistema_dental`, que
**no tiene ruta de registro público** — en este negocio los usuarios
(personal de la clínica) los da de alta el administrador desde el panel, los
pacientes no se autentican. Por eso también se eliminaron
`RegisteredUserController.php` y `resources/views/auth/register.blade.php`
de Breeze, que quedaban huérfanos.

## 4. Frontend: de React a Blade

### Piezas compartidas (antes de las páginas)

- **`resources/views/layouts/navigation.blade.php`**: reescrita con la
  lógica de menú según rol (`admin` ve Pacientes/Usuarios/Reportes y tres
  submenús desplegables — Catálogo, Contenido, Configuración — mientras que
  `asistente` ve Ofertas/Galería).
- **`resources/views/components/nav-dropdown.blade.php`**: componente nuevo
  para esos submenús (no existe en Breeze).
- **`resources/views/components/icon.blade.php`**: un solo componente
  `<x-icon name="...">` que reemplaza los 9 iconos SVG que `sistema_dental`
  tenía como componentes React sueltos (`edit`, `trash`, `check`,
  `check-circle`, `x`, `calendar-clock`, `power`, `eye`).
- **`resources/views/components/checkbox.blade.php`**: no existía en el
  scaffolding de Breeze Blade; se creó siguiendo el estilo del proyecto.
- **`resources/views/partials/disponibilidad-picker.blade.php`** +
  **`window.cargarHorariosDisponibles()`** en `resources/js/app.js`: el
  selector de horarios libres (antes un componente React con `useEffect` +
  `axios`) ahora es un partial de Blade que lee variables de un `x-data` de
  Alpine del formulario padre (`idTratamiento`, `fecha`, `hora`, `horarios`,
  `loadingHorarios`) y una función global que hace `fetch()` al endpoint
  `GET /disponibilidad`. Se usa en 3 formularios: citas del staff (crear y
  editar) y agendar cita pública.
- **`resources/views/partials/oferta-carousel.blade.php`** +
  `Alpine.data('ofertaCarousel', ...)` en `app.js`: el carrusel de ofertas de
  la portada (autoplay, arrastre, flechas, puntos), reescrito de un
  componente React con `useRef`/`useState` a un componente Alpine.
- **`tailwind.config.js`**: se agregó la paleta de marca (`brand.50`...
  `brand.900`, `brand-primary`, `brand-secondary`) y la fuente cursiva
  (`Alex Brush`) que usa el logo tipográfico "Melissa López N.".
- **`resources/css/app.css`**: se agregó la utilidad `.no-scrollbar` y la
  animación `carousel-fade-in` que usa el carrusel.

### Módulo de perfil (ajuste necesario)

Breeze genera el perfil asumiendo un campo `name`. Como el `User` real usa
`nombre`/`apellido`/`telefono`, se corrigieron:
`ProfileUpdateRequest`, `update-profile-information-form.blade.php` (ahora
pide nombre, apellido, teléfono y correo) y se tradujeron al español
`update-password-form.blade.php`, `delete-user-form.blade.php` y el título de
`profile/edit.blade.php` (antes tenían textos en inglés sin traducir, sin
que se notara porque Breeze los envuelve en `__()` y el proyecto no tiene
archivos de idioma).

También se tradujeron al español las 5 vistas de autenticación
(`login`, `forgot-password`, `reset-password`, `confirm-password`,
`verify-email`) por la misma razón.

### Las 44 páginas

Se reescribieron todas, organizadas en bloques:

1. **Público** (sin login): `welcome.blade.php` (portada completa: hero,
   servicios, ofertas, casos, testimonios, galería, contacto),
   `citas-publicas/create.blade.php` (wizard de 5 pasos para agendar cita sin
   cuenta — ver nota técnica abajo), `ofertas/index.blade.php`,
   `galeria/index.blade.php`.
2. **Staff**: `dashboard.blade.php` (distinto contenido si es admin o
   asistente), `citas/index.blade.php` (con filtros y acciones de
   confirmar/completar/cancelar/reagendar), `citas/create.blade.php`,
   `citas/edit.blade.php`.
3. **Admin — Catálogo**: `admin/servicios/*` y `admin/tratamientos/*`
   (index, create, edit — con subida de imagen y, en servicios, un segundo
   formulario para asignar tratamientos).
4. **Admin — Pacientes**: `admin/pacientes/*` (datos oficiales: CURP, RFC,
   INE, tutor) y `admin/historial/*` (consultas clínicas, con un partial
   compartido `_campos.blade.php` para no duplicar el formulario entre crear
   y editar, más subida/borrado de adjuntos).
5. **Admin — Contenido**: `admin/ofertas/*` (con partial `_campos.blade.php`
   compartido), `admin/galeria/*`, `admin/casos/*` (con subida de fotos
   antes/después).
6. **Admin — Gestión**: `admin/usuarios/*` (alta de personal con
   contraseña), `admin/testimonios/index.blade.php` (aprobar/rechazar),
   `admin/mensajes-contacto/index.blade.php`, `admin/horarios/edit.blade.php`
   (tabla editable por día de la semana), `admin/reportes/index.blade.php`
   (KPIs + descarga de PDF/CSV).

### Notas técnicas de la conversión React → Blade

- **El wizard de "Agendar cita" público** (5 pasos) usaba `useState` de React
  para el paso actual y los datos del formulario. Se reimplementó como un
  único `x-data` de Alpine que envuelve los 5 `<template x-if="paso === N">`.
  Como el envío final es un POST normal de HTML (no Inertia), si el servidor
  devuelve errores de validación la página se recarga — se resolvió
  inicializando el `x-data` con `old(...)` y saltando directo al paso 4
  (`$errors->any() ? 4 : 0`) para que el usuario no pierda lo ya llenado.
- **La tabla de horarios de atención** (`admin/horarios/edit.blade.php`)
  tiene una fila por día con un checkbox "abierto" que debía deshabilitar
  visualmente los campos de hora/capacidad cuando el día está cerrado. La
  versión React lo hacía con el atributo `disabled` de HTML sin problema,
  porque Inertia serializa el estado en JS. En un formulario HTML normal,
  un campo `disabled` **no se envía al servidor** — así que si se hubiera
  copiado igual, guardar con un día cerrado habría fallado la validación del
  backend (`hora_apertura` es `required`). Se cambió por una clase CSS
  (`opacity-40 pointer-events-none`) que solo afecta la apariencia, sin
  quitar el campo del envío.
- Los `Enums` de PHP (`TipoConsulta`, `TipoOferta`, etc.) no se comparan
  igual que los strings que React manejaba; los partials compartidos
  (`admin/historial/_campos.blade.php`, `admin/ofertas/_campos.blade.php`)
  usan un helper local que desenvuelve el enum (`->value`) antes de comparar
  con `old()`, para que las opciones `<select>` queden marcadas
  correctamente al reabrir un formulario con errores.

## 5. Validación hecha

- `php artisan migrate:fresh --seed` corrió sin errores con las 22
  migraciones nuevas.
- `php artisan route:list` cargó todas las rutas sin errores de clases
  faltantes.
- `npm run build` compiló sin errores de Tailwind/JS.
- Se inició sesión real como `admin@dentissa.com` (contraseña `password`,
  la del seeder) y se probaron con `curl` **todas** las páginas nuevas:
  portada, agendar cita pública, citas del staff, y los 11 módulos de admin
  — todas responden `200`. También se probó la descarga de PDF de historial
  clínico, PDF de reporte y CSV de reporte (los tres devuelven el
  `content-type` correcto).
- El único paciente/servicio/tratamiento usado en las pruebas fue el que
  crea el seeder (`ServiciosTratamientosSeeder` + el paciente demo).

## 6. Lo que falta por hacer (no técnico, decisión del usuario)

1. **Ver el sitio en el navegador** con tus propios ojos. Se validó que
   todo carga sin errores de servidor, pero convertir ~44 pantallas a mano
   es trabajo humano — vale la pena revisar visualmente cada módulo antes
   de darlo por cerrado.
2. **Configurar el `.env` de producción** (base de datos real en vez de
   SQLite, credenciales de correo si se usan notificaciones, etc.) — se dejó
   fuera de la migración a propósito.
3. **Revisar contenido de ejemplo**: los datos de contacto en
   `config/dentissa.php` (teléfono, WhatsApp, correo, dirección) son
   placeholders — reemplázalos por los reales del consultorio antes de
   publicar.
