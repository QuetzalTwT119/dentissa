# Manual de usuario de Dentissa

## 1. Acceso

Abre <http://localhost:8000>. El personal de la clínica inicia sesión desde
`/login`. Los pacientes pueden agendar desde el formulario público sin crear
una cuenta.

## 2. Agendamiento público

1. Abre `/agendar-cita`.
2. Selecciona un servicio.
3. Selecciona el tratamiento que pertenece a ese servicio.
4. Elige una fecha futura.
5. Selecciona un horario disponible.
6. Escribe nombre, apellido, teléfono y correo.
7. Pulsa **Agendar cita**.

El sistema crea o reutiliza el paciente por correo y crea la cita en estado
**Agendada**. La pantalla muestra un mensaje de confirmación.

## 3. Gestión interna de citas

Desde `/citas`, el personal puede:

- crear una cita seleccionando paciente, servicio, tratamiento, fecha y hora;
- confirmar una cita agendada;
- reagendar una cita a otro horario disponible;
- completar una cita;
- cancelarla indicando un motivo opcional;
- filtrar por fecha y estado.

Estados principales:

```text
Agendada → Confirmada → Completada
    │
    ├→ Reagendada → Agendada
    └→ Cancelada
```

## 4. Asistente de agenda

En `/asistente-agenda`, el personal selecciona los datos de la cita. El
asistente consulta disponibilidad y propone horarios. No guarda nada hasta
pulsar **Confirmar y agendar**.

## 5. Atención oportuna

### Solicitud urgente

En `/atencion-urgente`, una persona describe su situación y puede seleccionar
señales reportadas. El formulario no diagnostica ni crea una cita automática.
El personal revisa la solicitud desde `/admin/atencion-oportuna` y la marca
como **Contactada**.

Si existe una emergencia que pone en riesgo la vida, se debe llamar a los
servicios de emergencia; este formulario no sustituye atención médica.

### Lista de espera

En `/lista-de-espera`, una persona registra preferencias de servicio,
tratamiento, fecha y franja horaria. Esto no confirma una cita. El personal
revisa la solicitud y la marca como **Contactada** cuando realiza el contacto.

## 6. Pacientes e historial clínico

Desde `/admin/pacientes`:

1. Buscar o seleccionar un paciente.
2. Abrir **Ver historial**.
3. Pulsar **Nueva consulta**.
4. Registrar fecha, tipo, motivo y, si corresponde, información clínica.
5. Guardar la consulta.
6. Consultar, editar, eliminar o descargar el historial en PDF.

Durante las pruebas se deben usar datos ficticios. No se deben subir imágenes,
documentos ni datos médicos reales al entorno de desarrollo.

## 7. Diferencia entre pendientes e historial

Cuando una solicitud urgente o de lista de espera se marca como contactada,
deja de aparecer en la lista de pendientes. No se elimina: conserva su estado,
la persona que la contactó y la fecha del contacto.
