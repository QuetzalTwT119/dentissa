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
- iniciar la atención de una cita confirmada;
- marcar una cita confirmada como **No asistió**, solo desde la hora programada;
- completar una cita;
- cancelarla indicando un motivo opcional;
- filtrar por fecha y estado.

Estados principales:

```text
Agendada → Confirmada → En curso → Completada
    │
    ├→ Reagendada → Agendada
    ├→ No asistió
    └→ Cancelada
```

Una cita no se puede completar directamente desde **Agendada** o
**Confirmada**. Primero se debe pulsar **Iniciar** cuando comienza la
atención. Las acciones disponibles dependen del estado actual de la cita.
La inasistencia no se puede registrar antes de la fecha y hora programadas.

Al agendar o reagendar, el sistema vuelve a comprobar que el servicio y el
tratamiento estén activos, que el tratamiento pertenezca al servicio y que la
fecha/hora sea futura. Si el horario ya fue ocupado, se debe elegir otro.

## 4. Asistente de agenda

En `/asistente-agenda`, el personal selecciona los datos de la cita. El
asistente consulta disponibilidad y propone horarios. No guarda nada hasta
pulsar **Confirmar y agendar**.

## 5. Asistente para pacientes y preguntas frecuentes

El asistente flotante responde con preguntas frecuentes activas y sus respuestas
aprobadas. En desarrollo se puede habilitar un modelo de IA local para entender
otras formas de preguntar y redactar una respuesta basada solo en esas fuentes.
Si no encuentra respaldo suficiente, pide reformular la duda o contactar al
consultorio; no debe inventar precios, diagnósticos ni orientación clínica.

El personal administrador gestiona las FAQs desde **Contenido → Preguntas
frecuentes**. En cada registro debe escribir información verificada y aprobada;
no hace falta anticipar todas las palabras que podría usar un paciente. Las
formas alternativas son opcionales. Para que una FAQ pueda usarse como fuente
debe estar activa y visible. El prototipo también consulta los servicios y
tratamientos activos del catálogo, sin que el personal tenga que volver a
capturarlos en preguntas frecuentes.

Antes de publicar cambios, probar preguntas directas, paráfrasis, errores leves,
temas sin información disponible y consultas clínicas. La IA local solo está
disponible cuando el equipo de desarrollo la habilita en Docker; su activación no
significa que el modelo esté disponible en el sitio desplegado. Si no puede
apoyarse en una respuesta activa, debe mostrar el mensaje de respaldo.

## 6. Atención oportuna

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

Desde `/admin/atencion-oportuna`, las solicitudes contactadas o cerradas se
pueden consultar en **Historial de seguimiento** y filtrar por estado y rango
de fechas. Marcar una solicitud como contactada no crea una cita automática.

## 7. Pacientes e historial clínico

Desde `/admin/pacientes`:

1. Buscar o seleccionar un paciente.
2. Abrir **Ver historial**.
3. Pulsar **Nueva consulta**.
4. Registrar fecha, tipo, motivo y, si corresponde, información clínica.
5. Guardar la consulta.
6. Consultar, editar, eliminar o descargar el historial en PDF.

Durante las pruebas se deben usar datos ficticios. No se deben subir imágenes,
documentos ni datos médicos reales al entorno de desarrollo.

## 8. Diferencia entre pendientes e historial

Cuando una solicitud urgente o de lista de espera se marca como contactada,
deja de aparecer en la lista de pendientes. No se elimina: conserva su estado,
la persona que la contactó y la fecha del contacto.
