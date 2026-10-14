# Plan de entrega de Dentissa

## Objetivo

Entregar una versión estable y demostrable de Dentissa antes del **5 de
diciembre de 2026**, dejando el 4 de diciembre como margen para correcciones
finales.

La meta inmediata es cerrar el MVP funcional antes de agregar funciones
nuevas.

## Estado actual

- Flujos principales implementados: citas públicas e internas, asistente,
  pacientes, historial clínico, PDF, urgencias y lista de espera.
- Datos demo reproducibles mediante seeders.
- Entorno Docker documentado para Docker Desktop y WSL2; la aplicación local usa
  MySQL 8.4 y la suite puede correrse en SQLite o en un schema MySQL aislado.
- Última suite MySQL completa registrada (13/10/2026): **84 pruebas y 338
  aserciones** aprobadas en el schema aislado `dentissa_test`. Después del
  ajuste de espera de la IA local, `AsistenteIaLocalTest` pasó 10 pruebas y 26
  aserciones (14/10/2026). GitHub Actions #9 había aprobado ambos jobs con
  la cobertura anterior de 63 pruebas/250 aserciones en MySQL.
- El trabajo de respaldo y restauración está diferido y no forma parte de esta
  integración.
- Autorización interna de citas y auditoría de operaciones sensibles cubiertas
  por pruebas.
- La reconstrucción de la imagen debe hacerse después de cambios que se copian
  durante el build:

  ```bash
  docker compose up -d --build app
  ```

## Calendario de trabajo

### 1. Cierre de alcance y matriz QA — 7 al 12 de octubre

- Confirmar con el equipo qué funciones entran en la entrega.
- Convertir cada flujo en criterios de aceptación verificables.
- Clasificar pendientes por prioridad: bloqueante, importante y mejora.
- No incorporar funciones nuevas fuera del alcance sin registrarlas como
  posteriores al MVP.

### 2. Reglas, permisos y datos — 13 al 22 de octubre

- Revisar permisos por rol para citas, pacientes, historial y atención
  oportuna.
- Validar estados y transiciones de citas.
- Revisar mensajes de validación y casos límite.
- Confirmar que el entorno de prueba use únicamente datos ficticios.

### 3. Operación y despliegue — 23 de octubre al 2 de noviembre

- Probar instalación desde cero con Docker Desktop.
- Documentar variables de entorno y secretos sin subirlos a GitHub.
- Probar respaldo y restauración con Docker Desktop y datos ficticios.
- Configurar, si se usará en la entrega, WhatsApp o correo con credenciales
  reales de prueba.

### 4. QA funcional y visual — 3 al 14 de noviembre

- Ejecutar la matriz completa en navegador.
- Probar los flujos principales en escritorio y móvil.
- Revisar accesibilidad básica, mensajes de error y estados vacíos.
- Registrar cada defecto con pasos para reproducirlo y evidencia.

### 5. Correcciones y aceptación del equipo — 15 al 23 de noviembre

- Corregir primero defectos bloqueantes e importantes.
- Repetir pruebas automatizadas y manuales después de cada corrección.
- Hacer una demostración completa al equipo.
- Obtener aprobación del alcance y de los criterios de aceptación.

### 6. Candidato de entrega — 24 de noviembre al 3 de diciembre

- Congelar nuevas funciones.
- Ejecutar regresión completa.
- Revisar manual técnico, manual de usuario y diagramas.
- Preparar datos demo, instrucciones de instalación y guion de demostración.

### 7. Margen final — 4 de diciembre

- Corregir únicamente problemas críticos descubiertos en la revisión final.
- Confirmar que el repositorio, la imagen Docker y la documentación estén
  alineados.

## Criterios para considerar lista la entrega

1. La instalación desde cero funciona con Docker Desktop.
2. Los seeders generan datos demo sin duplicarlos.
3. Todos los flujos principales tienen una prueba automatizada o un caso de
   QA manual documentado.
4. La suite automatizada termina sin fallos.
5. No existen defectos bloqueantes o críticos abiertos.
6. Los permisos impiden acceder a información no autorizada.
7. No se usan datos médicos reales en desarrollo o demostración.
8. El equipo puede seguir el manual sin depender de instrucciones verbales.

## Seguimiento inmediato

La **matriz QA del MVP** es la fuente de verdad. La instalación desde un
checkout limpio ya fue validada con Docker Desktop, incluyendo clave,
migraciones, seeders, HTTP y la suite automatizada. El respaldo y la restauración
se probaron de forma exploratoria, pero se difieren fuera de esta integración;
los casos QA-23, QA-27, QA-31 y QA-32 quedan marcados como diferidos. No usar
datos clínicos reales hasta que el equipo implemente y valide un procedimiento
de protección y recuperación.

El 12/10 se amplió la cobertura de permisos: el middleware administrativo se
probó contra 12 páginas y una modificación; también se comprobó el acceso
anónimo. El 13/10 se aseguró que los cambios sensibles y su bitácora sean
atómicos; ante una falla, el cambio se revierte. GitHub Actions #9 pasó con 63
pruebas/250 aserciones en MySQL y 62 pruebas aprobadas, 1 omitida y 245
aserciones en SQLite. La mejora de exigir una transacción dentro del servicio
de auditoría queda anotada como posterior al MVP. El siguiente paso es completar
el recorrido manual de aceptación: agenda pública, flujo de asistente, estados
de cita, historial clínico y permisos por rol, registrando el resultado en la
matriz QA.

El 14/10 se verificó el prototipo de IA local en Docker con una respuesta real
del catálogo. El widget público también mostró una FAQ ficticia aprobada ante
dos errores leves de escritura. El modelo tardó 44.8 segundos en responder
desde frío: la latencia y la sustitución de contenido ficticio por información
validada siguen pendientes antes de cualquier publicación. La IA local todavía
no está configurada para el despliegue en Railway.
