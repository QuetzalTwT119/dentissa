# Matriz QA del MVP

Usar esta matriz durante las pruebas del equipo. Cada caso debe registrar la
fecha, la persona que lo ejecutó y una captura o referencia de evidencia.

Estados sugeridos: `Pendiente`, `Aprobado`, `Falló`, `Bloqueado`.

| ID | Flujo | Resultado esperado | Prioridad | Estado | Evidencia / incidencia | Responsable |
|---|---|---|---|---|---|---|
| QA-01 | Instalar con Docker Desktop desde cero | La aplicación abre en `localhost:8000` | Bloqueante | Aprobado | Checkout temporal sin `.env`, SQLite, dependencias ni almacenamiento; se creó SQLite vacío, se ejecutaron `build`, `key:generate`, migraciones y arranque; HTTP 200 en `localhost:8010`; copia y red temporal eliminadas al terminar | Codex · 07/10/2026 |
| QA-02 | Ejecutar seeders demo dos veces | Los datos conocidos no se duplican | Importante | Aprobado | 2 usuarios demo, 1 paciente demo, 7 servicios y 8 tratamientos después de ambas ejecuciones | Codex · 07/10/2026 |
| QA-03 | Agendar cita pública válida | Se crea paciente/cita y se muestra confirmación | Bloqueante | Aprobado | `CitaPublicaTest`: 1 prueba, 1 aserción | Codex · 07/10/2026 |
| QA-04 | Agendar con fecha pasada | El formulario rechaza la fecha | Importante | Aprobado | `CitaReglasTest`: fecha pasada rechazada | Codex · 07/10/2026 |
| QA-05 | Elegir tratamiento de otro servicio | El sistema rechaza la combinación | Importante | Aprobado | `CitaReglasTest`: relación servicio-tratamiento validada | Codex · 07/10/2026 |
| QA-06 | Crear cita desde administración | La cita queda en estado `Agendada` | Bloqueante | Aprobado | `CitaReglasTest`: creación interna válida | Codex · 07/10/2026 |
| QA-07 | Confirmar, iniciar y completar una cita | Solo aparecen las acciones válidas por estado | Bloqueante | Aprobado | `CitaReglasTest`: confirmar, iniciar y completar | Codex · 07/10/2026 |
| QA-08 | Cancelar y marcar no asistencia | El estado y motivo se conservan | Importante | Aprobado | `CitaReglasTest`: cancelar con motivo y no asistencia | Codex · 07/10/2026 |
| QA-09 | Reagendar una cita | Se valida disponibilidad y se actualiza fecha/hora | Bloqueante | Aprobado | `CitaReglasTest`: reagendamiento válido | Codex · 07/10/2026 |
| QA-10 | Usar asistente de agenda | Propone horarios y no guarda hasta confirmar | Importante | Aprobado | `AsistenteAgendaTest`: propuesta y confirmación controlada | Codex · 07/10/2026 |
| QA-11 | Enviar solicitud urgente | No crea una cita automática y muestra aviso médico | Bloqueante | Aprobado | `AtencionOportunaTest`: solicitud sin cita automática | Codex · 07/10/2026 |
| QA-12 | Contactar solicitud urgente | Desaparece de pendientes y aparece en historial | Importante | Aprobado | `AtencionOportunaTest`: admin puede marcarla como contactada | Codex · 07/10/2026 |
| QA-13 | Unirse a lista de espera | La solicitud conserva preferencias y no confirma cita | Bloqueante | Aprobado | `AtencionOportunaTest`: alta y visualización administrativa | Codex · 07/10/2026 |
| QA-14 | Contactar persona de lista de espera | Desaparece de pendientes y queda en seguimiento | Importante | Aprobado | `AtencionOportunaTest`: contacto e historial filtrado | Codex · 07/10/2026 |
| QA-15 | Buscar y editar paciente | Los datos permitidos se actualizan correctamente | Importante | Aprobado | `PacienteHistorialTest`: búsqueda y datos oficiales | Codex · 07/10/2026 |
| QA-16 | Crear consulta clínica | La consulta aparece en el historial del paciente | Bloqueante | Aprobado | `PacienteHistorialTest`: creación de consulta | Codex · 07/10/2026 |
| QA-17 | Editar y eliminar consulta | Solo usuarios autorizados pueden hacerlo | Importante | Aprobado | `PacienteHistorialTest`: edición y eliminación | Codex · 07/10/2026 |
| QA-18 | Descargar historial PDF | El PDF contiene paciente, fecha, doctor y consulta | Importante | Aprobado | `PacienteHistorialTest`: respuesta PDF válida | Codex · 07/10/2026 |
| QA-19 | Revisar permisos por rol | Un usuario no accede a módulos no autorizados | Bloqueante | Aprobado | Modelo actual: admin y asistente gestionan citas; `PacienteHistorialTest` confirma que asistente recibe 403 en el módulo administrativo de pacientes/historial | Codex · 08/10/2026 |
| QA-20 | Vista móvil y mensajes de error | Formularios y estados son comprensibles y utilizables | Mejora | Aprobado | Revisión a 390×844: navegación colapsada, campos apilados y controles requeridos deshabilitados hasta completar selección | Codex · 07/10/2026 |
| QA-21 | Ejecutar suite automatizada | Todas las pruebas terminan correctamente | Bloqueante | Aprobado | Suite completa SQLite: 50 pruebas/203 aserciones (08/10). MySQL: suite completa previa 49/185 (07/10) y `CitaReglasTest` actualizada 13/70 en schema aislado `dentissa_test` (08/10) | Codex · 08/10/2026 |
| QA-22 | Auditar cambios sensibles | Citas, historial y seguimiento dejan trazabilidad sin copiar datos clínicos | Bloqueante | Aprobado | `CitaPublicaTest`, `CitaReglasTest`, `PacienteHistorialTest` y `AtencionOportunaTest` verifican usuario, acción, registro y ausencia de diagnóstico/medicamentos en la auditoría | Codex · 07/10/2026 |
| QA-23 | Respaldar y restaurar datos locales | La base y archivos se recuperan desde el ZIP y el estado previo queda protegido | Bloqueante | Aprobado | Restauración en copia temporal: hash SQLite y archivo de `storage/app` coincidieron y se generó ZIP preventivo. Unidad externa E: (etiqueta “pruebas”): respaldo ZIP de 10,910 bytes, contiene SQLite y 3 entradas de storage/app; aplicación volvió a HTTP 200 | Codex · 07/10/2026 |
| QA-24 | Respetar capacidad simultánea configurada | Con capacidad 1, una segunda cita para el mismo horario se rechaza | Bloqueante | Aprobado | `CitaReglasTest::test_no_se_supera_la_capacidad_simultanea_para_un_mismo_horario`: 1 prueba, 5 aserciones; prueba secuencial en SQLite de memoria, no simula dos transacciones concurrentes | Codex · 07/10/2026 |
| QA-25 | Migrar, sembrar y probar contra MySQL | Migraciones completas, seeders idempotentes y suite sin fallos | Bloqueante | Aprobado | MySQL 8.4.11 local: 31 migraciones; seeders ejecutados dos veces (2 usuarios, 1 paciente, 7 servicios, 8 tratamientos); suite completa 49 pruebas/185 aserciones | Codex · 07/10/2026 |
| QA-26 | Persistir datos MySQL tras reiniciar el contenedor | Los registros demo siguen disponibles en el volumen | Bloqueante | Aprobado | Conteo de `users`: 2 antes y después de `docker compose restart mysql`; la primera consulta se hizo antes de `healthy` y dio conexión rechazada, se repitió al estar saludable y pasó | Usuario · 07/10/2026 |
| QA-27 | Respaldar y restaurar MySQL sin sobrescribir la base activa | Se recupera el dump en un schema aislado y se rechazan destinos inseguros | Importante | Aprobado | Windows PowerShell 5.1: ZIP temporal creado; restauración aislada recuperó 2 usuarios; se rechazaron la base activa y un schema ya existente; conteo activo permaneció en 2; artefactos QA eliminados. No cubre `storage/app` ni cifrado | Codex · 07/10/2026 |
| QA-28 | Rechazar transiciones inválidas de cita | La acción no permitida devuelve error y conserva el estado original | Importante | Aprobado | `CitaReglasTest::test_las_transiciones_invalidas_no_cambian_el_estado_de_la_cita`: 6 acciones inválidas cubiertas; clase completa: 13 pruebas/70 aserciones tanto en SQLite en memoria como en MySQL `dentissa_test` | Codex · 08/10/2026 |

## Regla de aprobación

El MVP no se considera listo mientras exista un caso `Bloqueante` fallido o
bloqueado. Las mejoras visuales pueden programarse después si no impiden usar
el flujo.
