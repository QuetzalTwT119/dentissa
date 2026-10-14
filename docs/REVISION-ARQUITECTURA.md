# Revisión de arquitectura y calidad — Dentissa

**Fecha de revisión:** 13 de octubre de 2026
**Alcance:** MVP de agenda, atención oportuna, pacientes e historial clínico
**Responsable de revisión:** QA y arquitectura de software

## 1. Dictamen ejecutivo

Dentissa tiene una arquitectura de **monolito modular Laravel** adecuada para
la entrega del MVP. Los módulos están separados por responsabilidad (citas,
disponibilidad, notificaciones, atención oportuna e historial clínico) y las
reglas más delicadas de agenda ya se concentran en servicios reutilizables.

La base es entregable. La autorización defensiva para la creación interna ya
quedó reforzada en esta revisión. La instalación limpia, la CI y la trazabilidad
de cambios sensibles ya fueron implementadas y verificadas. Antes de producción
siguen pendientes la operación de datos reales y el destino externo de
respaldos; estos puntos son más importantes que añadir módulos o rediseñar la
aplicación.

## 2. Arquitectura actual

```text
Navegador / formulario público / panel interno
                    |
              routes/web.php
                    |
        Controllers + middleware de acceso
                    |
       Services de dominio reutilizables
       |              |                 |
  Reglas de cita  Disponibilidad    Notificaciones
                    |
              Modelos Eloquent
                    |
          Base de datos + storage
                    |
       Correo / WhatsApp (si se configura)
```

### Canales principales

- **Público:** agenda una cita, solicita atención urgente o entra a lista de
  espera. El paciente público se crea o reutiliza por correo.
- **Personal autenticado:** consulta y administra citas; el asistente propone
  horarios, pero la cita solo se guarda al confirmar.
- **Administración:** gestiona catálogo, horarios, pacientes, historial clínico,
  contenido, reportes y solicitudes de atención oportuna.

### Reglas críticas ya centralizadas

- `CitaReglasService` valida servicio, tratamiento, relación entre ambos y
  fecha futura.
- `DisponibilidadService` calcula duración, margen, horario de atención,
  capacidad simultánea y solapamientos.
- La acción de inasistencia exige que la hora programada de la cita haya llegado;
  así se evita cerrar prematuramente una cita futura.
- Los adjuntos del historial clínico se entregan desde almacenamiento privado,
  solo a administradores autenticados; la ruta rechaza otros tipos de imagen.
- La creación y reagenda revalidan la disponibilidad dentro de una transacción.
  `DisponibilidadService` bloquea primero la fila de configuración del día de
  semana y después las citas existentes; así hay un registro común que serializa
  las reservas incluso cuando aún no existen citas. Este bloqueo es deliberadamente
  grueso: serializa brevemente reservas de distintas fechas del mismo día de semana.
  QA-24 valida el límite secuencial; QA-34 verificó dos procesos concurrentes en
  MySQL 8.4 (1 cita aceptada, 1 rechazada).
- `NotificacionService` intenta WhatsApp, usa correo como respaldo y registra
  la notificación sin derribar la operación de agenda.
- Las rutas administrativas están protegidas por autenticación y rol de
  administrador; las operaciones de citas usan `CitaPolicy`.

## 3. Fortalezas verificadas

| Área | Evaluación |
| --- | --- |
| Separación de responsabilidades | Buena para el tamaño actual; los controladores delegan reglas de dominio. |
| Reglas de agenda | Buena; existe validación común para los canales público, interno y asistente. |
| Capacidad en flujo secuencial | Verificada: QA-24 rechaza una cita que excedería el límite configurado. |
| Concurrencia real | Protección implementada y verificada con dos procesos de reserva concurrentes en MySQL 8.4 (QA-34). SQLite no valida de forma representativa `lockForUpdate`. La suite completa MySQL pasó: 63 pruebas/250 aserciones (13/10/2026). |
| Seguridad de rutas | Buena como primera barrera; hay middleware de autenticación/rol y políticas. QA-33 prueba el rechazo de asistentes en 12 páginas y una modificación administrativa, y el redirect de visitantes. |
| Datos demo | Reproducibles mediante seeders idempotentes (`updateOrCreate`/equivalentes). |
| Historial clínico | Flujo CRUD, PDF y protección administrativa cubiertos por pruebas. |
| Calidad actual | MySQL 8.4: 63 pruebas/250 aserciones aprobadas; SQLite: 62 aprobadas/1 omitida y 245 aserciones (QA-34 requiere MySQL), 13/10/2026. |
| Documentación | Manuales, diagramas, plan de entrega y matriz QA enlazados desde el README. |

## 4. Riesgos y decisiones pendientes

### P0 — cerrar antes de producción

1. **Base de datos y respaldo.** MySQL 8.4 local ya pasa migraciones y suite
   automatizada, y Railway es el destino elegido por el equipo. Antes de uso
   real se debe probar Railway e implementar un procedimiento de
   respaldo/restauración, cifrado y retención; esa etapa está diferida y fuera
   de esta integración. La preparación debe considerar además que el `Dockerfile`
   raíz actual está configurado para desarrollo, que los archivos subidos usan
   discos locales y que Railway tiene límites/costos según el plan. Ver
   `docs/DESPLIEGUE-RAILWAY.md`; no desplegar datos reales hasta cerrar esos
   puntos.
2. **Datos sensibles.** El historial clínico y los PDFs contienen información
   personal. Definir quién puede leerlos, dónde se almacenan, cuánto tiempo se
   conservan y cómo se protegen los respaldos. No usar pacientes reales en
   ambientes de prueba.
3. ~~**Instalación limpia.**~~ **Completado:** se probó desde una copia sin
   `.env`, SQLite local, dependencias ni almacenamiento. Migraciones, clave,
   arranque HTTP y suite automatizada terminaron correctamente.

### P1 — cerrar antes de la entrega del MVP

1. ~~**Autorización defensiva.**~~ **Completado:** `CitaPolicy::create` exige
   explícitamente personal autorizado (`admin` o `asistente`) para que una
   futura ruta no abra una capacidad por accidente.
2. ~~**CI automatizada.**~~ **Completado:** `.github/workflows/tests.yml` ejecuta
   la instalación de dependencias, el build de frontend, migraciones y pruebas
   en los cambios enviados a GitHub.
3. ~~**Auditoría consistente.**~~ **Completado para el MVP:**
   `AuditoriaService` registra cambios de citas, pacientes, historial y
   atención oportuna con usuario, acción, registro, fecha e IP, evitando copiar
   diagnóstico y medicamentos. El cambio y la auditoría se escriben en la
   misma transacción; ante una falla, se revierte el cambio y se muestra un
   error 503 comprensible. QA-36 está aprobado en SQLite y MySQL 8.4.
4. **Notificaciones resilientes.** Mover WhatsApp/correo a una cola con
   reintentos e idempotencia cuando se habilite el envío real. Mantener el
   registro de resultado y un mecanismo de revisión de fallos.
5. ~~**Concurrencia de reservas.**~~ **Completado para el flujo actual:** el
   bloqueo transaccional sobre la fila de horario semanal proporciona exclusión
   incluso antes de la primera cita. QA-34 verificó con MySQL 8.4 que, ante dos
   procesos simultáneos y capacidad 1, solo una cita se guarda. La suite completa
   de regresión también pasó en MySQL 8.4: 63 pruebas/250 aserciones (13/10/2026).

### P2 — siguiente etapa, no bloquear la entrega

1. Panel completo para capacidad por día/bloque, en lugar del valor global de
   configuración.
2. Búsqueda, paginación y filtros avanzados en listados grandes.
3. Pruebas E2E de navegador y comprobaciones automáticas de accesibilidad.
4. Eventos de dominio para desacoplar cambios de cita de notificaciones,
   reportes y futuras integraciones.
5. Observabilidad operativa: errores centralizados, métricas de tiempos de
   respuesta y alertas.
6. Endurecer la API de auditoría para que `AuditoriaService` rechace llamadas
   fuera de una transacción activa. Las operaciones actuales ya la usan
   correctamente; la comprobación evitaría regresiones si se agrega una acción
   auditada en el futuro.

## 5. Plan técnico recomendado

### Antes de entregar el MVP

1. ~~Reforzar `CitaPolicy::create` y agregar la prueba de autorización.~~
   **Completado:** la creación interna ahora exige rol de personal y cuenta con
   cobertura de prueba.
2. ~~Agregar CI para que ningún PR pueda mezclar código sin pasar la suite.~~
   **Completado:** `.github/workflows/tests.yml` valida PHP, frontend,
   migraciones y pruebas en pushes y PRs; el chequeo de formato compara los
   cambios reales de la rama contra su base.
3. ~~Completar auditoría de las operaciones críticas.~~ **Completado para el
   MVP:** las operaciones críticas ya usan `AuditoriaService`; falta definir la
   política de retención/consulta de logs para producción.
4. Probar la configuración de Railway/MySQL y definir el destino externo
   cifrado para respaldos; mantener la base local Docker para demo/desarrollo.

### Después del MVP

1. Implementar cola de notificaciones y monitoreo de fallos.
2. Migrar la capacidad de agenda a configuración por día y bloque.
3. Incorporar pruebas E2E automatizadas para los recorridos críticos.
4. Revisar retención, exportación y eliminación segura de datos clínicos.

## 6. Criterios de salida arquitectónicos

El MVP puede pasar a revisión final cuando se cumpla todo lo siguiente:

- `php artisan test` pasa sin fallos en un checkout limpio.
- La instalación Docker está documentada y reproducida por otra persona.
- No existen rutas internas accesibles sin el rol correspondiente.
- Una cita no puede saltarse catálogo, fecha futura, horario, capacidad ni
  transición de estado.
- Los cambios de historial clínico quedan identificados por usuario y fecha.
- El equipo conoce qué datos son demo y qué variables deben cambiar antes de
  producción.
- El respaldo y restauración de la base de datos fueron probados, no solo
  descritos.

## 7. Conclusión

No se recomienda reescribir el sistema. La arquitectura actual es suficiente
para entregar el MVP si se cierran los controles P0/P1. La prioridad es hacer
la operación segura, reproducible y auditable; las mejoras P2 pueden evolucionar
sin romper el monolito modular existente.
