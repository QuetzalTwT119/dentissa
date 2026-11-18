# Plan de entrega de Dentissa

## Objetivo

Entregar Dentissa v1.0 estable, demostrable y desplegable el **5 de diciembre
de 2026**, con cero defectos críticos abiertos. El 17/11/2026 el usuario
confirmó esta fecha. El adelanto al 31 de octubre registrado el 16/10/2026 ya no
aplica; el calendario acelerado de octubre queda solo como referencia histórica.

La meta inmediata es cerrar el MVP funcional antes de agregar funciones
nuevas.

## Estado actual

- Flujos principales implementados: citas públicas e internas, asistente,
  pacientes, historial clínico y PDF.
- Datos demo reproducibles mediante seeders.
- Entorno Docker documentado para Docker Desktop y WSL2; la aplicación local usa
  MySQL 8.4 y la suite puede correrse en SQLite o en un schema MySQL aislado.
- Última suite MySQL completa registrada (14/10/2026): **95 pruebas y 379
  aserciones** aprobadas en el schema aislado `dentissa_test`. También pasó
  SQLite con 94 pruebas/374 aserciones y una omisión esperada (concurrencia).
  GitHub Actions #9 aprobó ambos jobs con una cobertura anterior.
- El trabajo de respaldo y restauración está diferido y no forma parte de esta
  integración.
- Autorización interna de citas y auditoría de operaciones sensibles cubiertas
  por pruebas.
- La reconstrucción de la imagen debe hacerse después de cambios que se copian
  durante el build:

  ```bash
  docker compose up -d --build app
  ```

- Validación reejecutada el 16/10/2026 sobre la imagen reconstruida: SQLite
  143 pruebas/701 aserciones con 1 omisión esperada; MySQL 8.4 aislado
  `dentissa_test`, 144 pruebas/706 aserciones aprobadas, incluida concurrencia.
  Pint aprobó 183 archivos y `git diff --check` quedó limpio.

## Calendario de trabajo

### Calendario vigente — 17 de noviembre al 5 de diciembre

Estado el 17/11/2026: el rediseño y el pulido de la interfaz ya están en
`master` (PR #35 y #51; matriz QA-67 y QA-68). Lo que falta pesa más en
operación que en diseño.

- **17–21 nov — Respaldo y restauración (bloqueante):** cerrar el ensayo
  cifrado con datos ficticios y recuperar sin tocar la base activa (QA-23, 27,
  31 y 32). **Hecho el 18/11/2026** con Google Drive real (Manual Técnico
  14.2); QA-31 y 32 (Restic) quedan diferidos porque los sustituye QA-66.
- **22–26 nov — Despliegue:** configurar Railway, comprobar
  `php artisan dentissa:diagnostico --remoto` y probar el respaldo a Google
  Drive desde el servidor (QA-50 y QA-66).
- **27–29 nov — Aceptación en dispositivos reales:** recorrer en teléfono
  físico y tableta la portada, agendar cita, el panel por rol, las tablas y la
  galería (QA-44 a 48, 56 a 58 y 67), registrando la evidencia en la matriz.
- **30 nov – 2 dic — Candidato de entrega:** congelar funciones nuevas,
  regresión completa (SQLite, MySQL y CI), revisar manuales y ensayar el guion
  de `GUION-DEMO.md`.
- **3–4 dic — Margen:** solo defectos críticos; confirmar que repositorio,
  imagen Docker, despliegue y documentación coinciden.
- **5 dic:** entrega de Dentissa v1.0.

### Calendario acelerado — 16 al 31 de octubre (histórico)

- **16–19 oct:** cerrar defectos bloqueantes, alinear Docker con el código
  vigente y completar pruebas de agenda, auditoría, permisos y datos.
- **20–23 oct:** terminar comprobaciones responsivas y el recorrido de
  aceptación; resolver el procedimiento seguro de respaldo/restauración con
  datos ficticios.
- **24–27 oct:** preparar y validar el despliegue de demostración en Railway;
  la publicación final puede requerir acceso del usuario a su cuenta.
- **28–30 oct:** regresión completa, revisión de manuales, accesibilidad básica
  y corrección de defectos críticos/importantes.
- **31 oct:** congelar alcance y entregar el candidato estable; no añadir
  funciones nuevas.

El calendario original que aparece a continuación queda como referencia
histórica y no debe usarse para fijar la fecha actual de entrega.

### 1. Cierre de alcance y matriz QA — 7 al 12 de octubre

- Confirmar con el equipo qué funciones entran en la entrega.
- Convertir cada flujo en criterios de aceptación verificables.
- Clasificar pendientes por prioridad: bloqueante, importante y mejora.
- No incorporar funciones nuevas fuera del alcance sin registrarlas como
  posteriores al MVP.

### 2. Reglas, permisos y datos — 13 al 22 de octubre

- Revisar permisos por rol para citas, pacientes e historial.
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
- Preparar datos demo, instrucciones de instalación y guion de demostración
  (ver `GUION-DEMO.md`: reparto, preparación T-2/T-1, recorrido de 7 minutos,
  planes B y preguntas probables).

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
atómicos; ante una falla, el cambio se revierte. El 14/10 se endureció el
servicio para rechazar auditorías fuera de una transacción activa o desde una
conexión distinta. El 14/10 se corrigió la validación del teléfono en la agenda
pública para aceptar formatos mexicanos comunes y rechazar longitudes/caracteres
inválidos. Se preparó la integración de WhatsApp para normalizar teléfonos
mexicanos, rechazar formatos incorrectos y no incluir teléfonos ni nombres en
los logs; HTTP simulado valida el correo de respaldo sin enviar mensajes reales.
Suite completa: MySQL 95/379 y SQLite 94/374 con una omisión esperada. El
siguiente paso es completar el recorrido manual de aceptación:
agenda pública, flujo de asistente, estados de cita, historial clínico y
permisos por rol, registrando el resultado en la matriz QA.

El 14/10 se verificó el prototipo de IA local en Docker con una respuesta real
del catálogo. El widget público también mostró una FAQ ficticia aprobada ante
dos errores leves de escritura. El modelo tardó 44.8 segundos en responder
desde frío: la latencia y la sustitución de contenido ficticio por información
validada siguen pendientes antes de cualquier publicación. La IA local todavía
no está configurada para el despliegue en Railway.

El 15/10 el equipo decidió Railway como destino para una demostración escolar
breve con datos ficticios. Se separó el contenedor local (`Dockerfile.local`)
del runtime Laravel administrado por Railpack. Se añadió un seeder exclusivo
para la presentación con admin, asistente y registros ficticios; requiere
habilitación y contraseñas seguras por variables privadas. Docker local, rutas
de salud/agenda y suite completa contra MySQL 8.4 aislado (113 aprobadas, 473
aserciones) pasaron. Sigue pendiente configurar Railway desde el panel; todavía
no hay un servicio desplegado. No usar datos clínicos reales;
respaldo/restauración sigue diferido.

El 16/10/2026 se reconstruyeron `app`, `reverb` y `telegram` desde el código
vigente. La app responde HTTP 200 en `/up`, Reverb acepta conexiones en el
puerto 8080 y MySQL está saludable. Pint aprobó 183 archivos; SQLite aprobó
143 pruebas/701 aserciones con 1 omisión esperada y MySQL 8.4 aislado aprobó
144/706, incluida concurrencia. En la etapa responsiva se ajustaron el encabezado
de Testimonios y las cargas de adjuntos/fotos de Historial y Casos; las pruebas
de navegación responsiva pasan 5/20. El sitio también responde HTTP 200 en la
IP local `192.168.0.80:8000` (inicio, agenda y healthcheck). No se tocó la base
operativa ni se enviaron notificaciones. Falta aceptación visual final en
dispositivos físicos y cerrar los recorridos responsivos QA-44/45/46/56/57/58;
el control visual de navegador no estuvo disponible en esta sesión. Siguen
pendientes el ensayo cifrado de respaldo/restauración (QA-23/27/31/32) y la
configuración de Railway tras comprobar acceso y costos. No anunciar la versión
como lista hasta cerrar bloqueantes y registrar evidencia.
