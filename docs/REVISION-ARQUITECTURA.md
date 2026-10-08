# Revisión de arquitectura y calidad — Dentissa

**Fecha de revisión:** 7 de octubre de 2026  
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
- La creación y reagenda revalidan la disponibilidad dentro de una transacción
  y usan `lockForUpdate` sobre las citas del día. La prueba QA-24 valida el
  límite de capacidad de forma secuencial; no representa dos solicitudes
  ejecutándose al mismo tiempo.
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
| Concurrencia real | Pendiente de verificar con dos reservas simultáneas. SQLite no valida de forma representativa `lockForUpdate`; la suite MySQL actual también prueba el límite de forma secuencial y no demuestra ausencia de carreras. |
| Seguridad de rutas | Buena como primera barrera; hay middleware de autenticación/rol y políticas. |
| Datos demo | Reproducibles mediante seeders idempotentes (`updateOrCreate`/equivalentes). |
| Historial clínico | Flujo CRUD, PDF y protección administrativa cubiertos por pruebas. |
| Calidad actual | 49 pruebas aprobadas y 185 aserciones en el último ciclo Docker. |
| Documentación | Manuales, diagramas, plan de entrega y matriz QA enlazados desde el README. |

## 4. Riesgos y decisiones pendientes

### P0 — cerrar antes de producción

1. **Base de datos y respaldo.** MySQL 8.4 local ya pasa migraciones y suite
   automatizada, y Railway es el destino elegido por el equipo. Antes de uso
   real se debe probar Railway, implementar respaldo/restauración de MySQL y
   configurar el destino externo cifrado y la retención propuestos en
   `POLITICA-RESPALDOS.md`.
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
   diagnóstico y medicamentos.
4. **Notificaciones resilientes.** Mover WhatsApp/correo a una cola con
   reintentos e idempotencia cuando se habilite el envío real. Mantener el
   registro de resultado y un mecanismo de revisión de fallos.
5. **Concurrencia de reservas.** La prueba secuencial de capacidad está
   completada (QA-24). Antes de producción, probar dos solicitudes realmente
   concurrentes usando Railway/MySQL; las suites SQLite y MySQL actuales
   verifican el límite de forma secuencial, no garantizan que los bloqueos
   funcionen ante carreras en producción.

### P2 — siguiente etapa, no bloquear la entrega

1. Panel completo para capacidad por día/bloque, en lugar del valor global de
   configuración.
2. Búsqueda, paginación y filtros avanzados en listados grandes.
3. Pruebas E2E de navegador y comprobaciones automáticas de accesibilidad.
4. Eventos de dominio para desacoplar cambios de cita de notificaciones,
   reportes y futuras integraciones.
5. Observabilidad operativa: errores centralizados, métricas de tiempos de
   respuesta y alertas.

## 5. Plan técnico recomendado

### Antes de entregar el MVP

1. ~~Reforzar `CitaPolicy::create` y agregar la prueba de autorización.~~
   **Completado:** la creación interna ahora exige rol de personal y cuenta con
   cobertura de prueba.
2. ~~Agregar CI para que ningún PR pueda mezclar código sin pasar la suite.~~
   **Completado en código:** `.github/workflows/tests.yml` valida PHP, frontend,
   migraciones, pruebas y `git diff --check` en cada push a `master` y cada PR.
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
