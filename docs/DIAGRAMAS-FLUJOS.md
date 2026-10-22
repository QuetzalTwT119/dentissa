# Diagramas y flujos de Dentissa

Los bloques siguientes usan Mermaid. GitHub puede renderizarlos directamente
en Markdown.

## 1. Actores y canales

```mermaid
flowchart LR
    Paciente[Paciente o visitante]
    Personal[Personal de clínica]
    Admin[Administrador]
    Sistema[Dentissa]
    Datos[(SQLite)]
    Notif[Notificaciones]

    Paciente -->|Agendar cita| Sistema
    Personal -->|Gestionar citas y pacientes| Sistema
    Admin -->|Catálogos, usuarios, reportes| Sistema
    Sistema --> Datos
    Sistema --> Notif
```

## 2. Agendamiento público

```mermaid
sequenceDiagram
    actor P as Paciente
    participant V as Formulario público
    participant C as CitaPublicaController
    participant D as DisponibilidadService
    participant DB as Base de datos
    participant N as NotificacionService

    P->>V: Selecciona servicio, tratamiento, fecha y hora
    V->>D: Consulta horarios disponibles
    D-->>V: Devuelve horarios
    P->>V: Envía sus datos
    V->>C: POST /agendar-cita
    C->>C: Valida servicio, tratamiento y relación activa
    C->>C: Valida fecha/hora futura
    C->>D: Revalida el horario dentro de una transacción
    C->>DB: Crea o reutiliza paciente y crea cita como Confirmada
    C->>N: Registra la notificación después del commit
    C-->>P: Confirmación de cita confirmada
```

Si la combinación de catálogo no está activa, el tratamiento no pertenece al
servicio elegido, la fecha ya pasó o el horario fue ocupado, el formulario
devuelve errores y no crea la cita.

## 3. Gestión de estados de una cita

```mermaid
stateDiagram-v2
    [*] --> Confirmada: reserva pública, Telegram o interna
    Agendada --> Confirmada: confirmar una cita histórica pendiente
    Agendada --> Cancelada: cancelar
    Agendada --> Agendada: reagendar
    Confirmada --> EnCurso: iniciar atención
    Confirmada --> NoAsistio: marcar inasistencia (desde hora programada)
    Confirmada --> Cancelada: cancelar
    Confirmada --> Confirmada: reagendar
    EnCurso --> Completada: completar
    Completada --> [*]
    NoAsistio --> [*]
    Cancelada --> [*]
```

La aplicación no permite completar directamente una cita **Agendada** o
**Confirmada**: primero debe pasar a **En curso**. Las acciones de iniciar,
marcar inasistencia y completar requieren autorización del personal.
La inasistencia se rechaza si la fecha y hora programadas aún están en el
futuro.

## 4. Historial clínico

```mermaid
flowchart LR
    A[Personal] --> B[Selecciona paciente]
    B --> C[Abre historial]
    C --> D[Nueva consulta]
    D --> E[Guarda consulta]
    E --> F[Historial consultable]
    F --> G[Editar o eliminar]
    F --> H[Generar PDF]
```

## 5. Observaciones para la siguiente etapa

- El asistente de agenda actualmente es un asistente guiado: propone horarios
  y requiere confirmación humana.
- Las listas de atención separan pendientes de historial; el historial puede
  filtrarse por estado y rango de fechas.
- Las imágenes y assets visuales se incorporarán posteriormente con recursos
  preparados fuera de este flujo de desarrollo.
