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

    Paciente -->|Agendar, urgencia, lista de espera| Sistema
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
    C->>D: Revalida el horario dentro de una transacción
    C->>DB: Crea o reutiliza paciente y crea cita
    C->>N: Registra la notificación después del commit
    C-->>P: Confirmación de cita agendada
```

## 3. Gestión de estados de una cita

```mermaid
stateDiagram-v2
    [*] --> Agendada
    Agendada --> Confirmada: confirmar
    Agendada --> Cancelada: cancelar
    Agendada --> Agendada: reagendar
    Confirmada --> Completada: completar
    Confirmada --> Cancelada: cancelar
    Confirmada --> Agendada: reagendar
    Completada --> [*]
    Cancelada --> [*]
```

## 4. Atención oportuna

```mermaid
flowchart TD
    Inicio[Persona necesita atención] --> Tipo{¿Qué necesita?}
    Tipo -->|Urgencia| U[Formulario de urgencia]
    Tipo -->|No hay horario| E[Formulario de lista de espera]
    U --> UP[Solicitud pendiente]
    E --> EP[Solicitud pendiente]
    UP --> ContactarU[Personal revisa y contacta]
    EP --> ContactarE[Personal revisa y contacta]
    ContactarU --> UC[Estado contactada]
    ContactarE --> EC[Estado contactada]
    UP -.->|No crea cita automáticamente| Fin[Seguimiento humano]
    EP -.->|No confirma cita| Fin
```

## 5. Historial clínico

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

## 6. Observaciones para la siguiente etapa

- El asistente de agenda actualmente es un asistente guiado: propone horarios
  y requiere confirmación humana.
- Las listas de atención muestran pendientes; los registros contactados se
  conservan, pero todavía no existe una vista histórica dedicada.
- Las imágenes y assets visuales se incorporarán posteriormente con recursos
  preparados fuera de este flujo de desarrollo.
