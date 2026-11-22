# Árbol del proyecto Dentissa

Actualizado el 22/11/2026. Dos árboles:

1. **Árbol de carpetas**: dónde vive cada parte del código (para quien programa o mantiene).
2. **Árbol de pantallas**: qué páginas tiene el sistema y qué rol puede abrir cada una
   (para la doctora, el manual de usuario y para planear permisos).

> Si se agrega una carpeta importante o una pantalla nueva, actualizar este archivo.
> Para ver la lista exacta de rutas en cualquier momento: `php artisan route:list`.

---

## 1. Árbol de carpetas

Solo lo que se toca al trabajar. Se omiten `vendor/` y `node_modules/` (dependencias
descargadas), `storage/` (archivos generados) y `public/build/` (CSS/JS compilado).

```text
dentissa/
├── app/                          Código de la aplicación (Laravel)
│   ├── Console/Commands/         Comandos: recordatorios de citas, respaldos, diagnóstico,
│   │                             bot de Telegram, prueba de recordatorio
│   ├── Enums/                    Listas fijas: RolUsuario (super_admin, doctor, asistente),
│   │                             EstadoCita, EstadoOferta, TipoConsulta…
│   ├── Events/                   Avisos en vivo (NotificacionSistemaCreada)
│   ├── Http/
│   │   ├── Controllers/          Reciben cada página o formulario y deciden qué hacer
│   │   │   ├── Admin/            Panel de administración: pacientes, historial, usuarios,
│   │   │   │                     servicios, tratamientos, ofertas, testimonios, galería,
│   │   │   │                     casos, preguntas, reportes, mensajes, control de actividades,
│   │   │   │                     configuración (horarios y datos del consultorio)
│   │   │   ├── Auth/             Inicio de sesión y recuperación de contraseña
│   │   │   ├── Concerns/         Piezas compartidas (AlternaEstado: interruptor Disponible)
│   │   │   ├── CitaController…   Agenda interna (citas del personal)
│   │   │   ├── CitaPublica…      Agenda en línea para pacientes
│   │   │   └── WhatsappWebhook…  Respuestas a los botones de WhatsApp
│   │   ├── Middleware/           Filtros de cada petición: VerificarPermiso (roles),
│   │   │                         ExigirDosPasos, cabeceras de seguridad, sesión inactiva
│   │   └── Requests/             Validaciones de formularios
│   ├── Mail/                     Correos (confirmación y recordatorio de cita, envío por Gmail)
│   ├── Models/                   Tablas de la base: User, Paciente, Cita, HistorialClinico,
│   │                             Servicio, Tratamiento, Oferta, Testimonio, LogAuditoria…
│   ├── Observers/                AuditoriaObserver: registra cambios en Control de actividades
│   ├── Policies/                 CitaPolicy: quién puede cambiar una cita
│   ├── Providers/                Arranque: observadores, búsqueda de texto, datos del consultorio
│   ├── Rules/                    Reglas de validación propias
│   ├── Services/                 Reglas del negocio reutilizables
│   │   ├── DisponibilidadService     Horarios libres y validación al guardar
│   │   ├── CrearCitaService          Crear una cita desde cualquier canal
│   │   ├── NotificacionService       WhatsApp y correo al paciente
│   │   ├── NotificacionInterna…      Campana del personal
│   │   ├── AuditoriaService          Control de actividades
│   │   ├── Asistente*Service         Asistente de agenda y de pacientes
│   │   ├── Telegram / Whatsapp       Bots y mensajería
│   │   ├── Reporte* / Respaldo/      Reportes PDF/Excel y respaldos cifrados
│   │   └── Google/                   Conexión con Google (correo y Drive)
│   ├── Support/                  Utilidades: Permisos (lee config/permisos.php),
│   │                             DetalleBitacora ("Ver cambios" en palabras), Iniciales…
│   └── View/                     Componentes de vista con lógica
├── bootstrap/app.php             Registro de middleware (alias "permiso") y arranque
├── config/
│   ├── permisos.php              ★ Qué puede ver y hacer cada rol (único lugar)
│   ├── dentissa.php              Ajustes propios: horarios, intervalos, contacto, WhatsApp…
│   └── (app, auth, mail…)        Configuración estándar de Laravel
├── database/
│   ├── migrations/               Cambios de la estructura de la base (45 migraciones)
│   ├── seeders/                  Datos iniciales y de demostración
│   └── factories/                Datos de prueba para las pruebas automáticas
├── resources/
│   ├── views/                    Pantallas (Blade)
│   │   ├── welcome.blade.php         Portada pública
│   │   ├── citas-publicas/           Agenda en línea del paciente
│   │   ├── dashboard.blade.php       Panel de inicio del personal
│   │   ├── citas/                    Agenda interna (lista y semana)
│   │   ├── admin/historia-clinica/   Cuestionario de historia clínica del paciente
│   │   ├── asistente-agenda/         Asistente de agenda
│   │   ├── admin/                    Una carpeta por sección del panel
│   │   ├── notificaciones/           Bandeja de avisos
│   │   ├── profile/                  Mi perfil y verificación en dos pasos
│   │   ├── auth/                     Acceso, olvidé mi contraseña
│   │   ├── layouts/                  Marco común: menú (navigation) y portada
│   │   ├── components/               ★ Piezas globales reutilizables:
│   │   │   ├── admin/                    tabla, acciones de fila, interruptor de estado,
│   │   │   │                             botones, encabezado, tarjeta
│   │   │   ├── filtro/                   barra de filtros, buscador, fechas, menús
│   │   │   └── (sueltas)                 marca, ayuda ⓘ/⚠, ícono, modal, diálogo de
│   │   │                                 confirmación, campo de contraseña, texto expandible…
│   │   ├── partials/                 Secciones de la portada (ofertas, testimonios…)
│   │   ├── emails/ y pdf/            Plantillas de correo y de PDF
│   │   └── errors/                   Páginas de error (403, 404, 500…)
│   ├── css/app.css               Estilos (Tailwind) y reglas globales
│   └── js/app.js                 Comportamiento (Alpine): carruseles, calendario, avisos en vivo
├── routes/
│   ├── web.php                   ★ Todas las direcciones del sitio y del panel
│   ├── auth.php                  Acceso y contraseñas
│   ├── console.php               Tareas programadas (recordatorios cada 15 min)
│   └── channels.php              Canal de avisos en vivo
├── tests/                        Pruebas automáticas (70 de funciones + 3 unitarias)
├── docs/                         Manuales, checklist, despliegue, diagramas y este árbol
├── scripts/                      Respaldos, arranque en producción, utilidades
├── docker/ + Dockerfile*         Contenedores (MySQL y la app) para local y Railway
├── compose.yaml                  Levanta todo en local con Docker
└── AGENTS.md                     Reglas de trabajo del proyecto
```

★ = archivos que se tocan seguido.

---

## 2. Árbol de pantallas

### 2.1 Sitio público (sin cuenta)

```text
Sitio público
├── /                              Portada: servicios, ofertas, testimonios, casos, preguntas,
│                                  formulario "Comparte tu experiencia", contacto
├── /agendar-cita                  Agenda en línea (servicio → tratamiento → fecha → hora → datos)
├── /cita/{cita}/recordatorio/…    Respuesta al recordatorio (Asistiré / Cancelar / Reagendar)
├── /aviso-de-privacidad           Aviso de privacidad
├── /login                         Acceso del personal
│   ├── /forgot-password           ¿La olvidaste?
│   ├── /reset-password/…          Nueva contraseña desde el correo
│   └── /dos-pasos                 Código de verificación en dos pasos
└── Canales sin pantalla           Bot de Telegram, botones de WhatsApp, asistente del chat
```

### 2.2 Panel del personal (con cuenta)

Quién puede abrir cada pantalla, según `config/permisos.php`.
**T** = Doctora titular (`super_admin`) · **D** = Doctor (`doctor`) · **A** = Asistente (`asistente`).

```text
Panel                                         Módulo               T   D   A
├── Inicio  /dashboard                         inicio               ✓   ✓   ✓
├── Agenda  /citas (lista y semana)            agenda               ✓   ✓   ✗
│   ├── Nueva cita / Editar / Reagendar
│   └── Confirmar · Iniciar · Completar · No asistió · Cancelar
├── Asistente  /asistente-agenda               agenda               ✓   ✓   ✗
├── 🔔 Notificaciones  /notificaciones         notificaciones       ✓   ✓   ✗
├── Gestión ▾
│   ├── Pacientes  /admin/pacientes            pacientes            ✓   ✓   ✗
│   │   ├── ✎ Editar paciente
│   │   └── 📋 Historial clínico: historia clínica (cuestionario), consultas,
│   │       adjuntos, PDF
│   ├── Usuarios  /admin/usuarios              usuarios             ✓   ✓*  ✗
│   │   └── Nuevo usuario · Desactivar · Eliminar
│   ├── Reportes  /admin/reportes (PDF/Excel)  reportes             ✓   ✓   ✗
│   ├── Mensajes  /admin/mensajes              mensajes             ✓   ✓   ✗
│   └── Control de actividades                 control_actividades  ✓   ✓   ✗
│       /admin/control-de-actividades
├── Catálogo ▾
│   ├── Servicios  /admin/servicios            catalogo             ✓   ✓   ✗
│   └── Tratamientos  /admin/tratamientos      catalogo             ✓   ✓   ✗
├── Contenido ▾
│   ├── Ofertas  /admin/ofertas                contenido            ✓   ✓   ✗
│   ├── Testimonios  /admin/testimonios        contenido            ✓   ✓   ✗
│   ├── Galería  /admin/galeria                contenido            ✓   ✓   ✗
│   ├── Casos  /admin/casos                    contenido            ✓   ✓   ✗
│   └── Preguntas frecuentes                   contenido            ✓   ✓   ✗
├── Configuración  /admin/configuracion        configuracion        ✓   ✓   ✗
│   (horarios de atención y datos del consultorio)
└── Menú de la cuenta
    ├── Mi perfil y dos pasos  /profile        cuenta               ✓   ✓   ✗
    └── Cerrar sesión                          (siempre)            ✓   ✓   ✓
```

\* En Usuarios, el doctor no puede crear, desactivar ni eliminar una cuenta de Doctora
titular: eso solo lo hace otra Doctora titular.

**Cómo cambiar lo que ve un rol:** editar su lista en `config/permisos.php`
(por ejemplo, agregar `'agenda'` a `asistente` le abre Agenda y Asistente, y el menú
los muestra solo). La prueba `PermisosPorModuloTest` avisa si una pantalla del panel
queda sin módulo.
