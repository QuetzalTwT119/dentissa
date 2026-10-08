# Política de respaldos de Dentissa

## Propósito y alcance actual

Esta política separa las copias de desarrollo de la protección necesaria para
operar con pacientes reales.

El proyecto mantiene dos formatos que no son intercambiables:

- `backup.ps1` / `restore.ps1`: SQLite y `storage/app`.
- `backup-mysql.ps1` / `restore-mysql.ps1`: dump SQL de la base MySQL activa.

El respaldo MySQL no incluye `storage/app` ni `.env`, y todavía no cifra el ZIP.
Sirve para desarrollo y para practicar una recuperación aislada, no como
protección completa de datos clínicos reales. Los respaldos locales SQLite se
pueden crear en una unidad externa:

```powershell
./scripts/backup.ps1
./scripts/backup.ps1 -Destination 'E:\DentissaBackups'
```

El respaldo de la base MySQL se crea de forma independiente:

```powershell
./scripts/backup-mysql.ps1 -Destination 'E:\DentissaBackups'
```

Restaura únicamente ZIPs generados por el propio script y mantenidos bajo
control del equipo: un dump SQL puede ejecutar instrucciones en la base destino
y el ZIP no tiene firma criptográfica.

La copia local sirve para recuperar errores durante desarrollo o demostración.
Si el ZIP permanece en el mismo equipo que la aplicación, no protege contra la
pérdida o daño de ese equipo.

## Propuesta para el MVP con datos ficticios

- Crear un respaldo antes de una demostración importante o de cambios que
  puedan afectar los datos.
- Si el equipo ya dispone de una unidad externa, usarla con
  `-Destination 'E:\DentissaBackups'` y desconectarla al terminar la copia.
- Conservar la copia local hasta haber comprobado el respaldo externo.
- No guardar ZIP de respaldos en GitHub, correo, chats ni carpetas públicas.
- Hacer una restauración de práctica antes de la entrega final. `restore-mysql.ps1`
  crea un schema nuevo `dentissa_restore_*`, sin reemplazar la base activa.

Esta rutina es manual; el proyecto todavía no programa respaldos ni elimina
copias antiguas automáticamente.

## Evidencia de práctica cifrada con Restic

El 08/10/2026 se probó Restic 0.19.1 con datos demo en la unidad `E:`. El
repositorio local `E:\DentissaBackups\restic-repo` se inicializó con una
contraseña que no se registra en el proyecto y se guardaron dos ZIP de
práctica. El snapshot `52a3dfcd` pasó `restic check --read-data`
(`no errors were found`). Se restauró
a una carpeta temporal y los SHA-256 de ambos archivos coincidieron con los
originales; después se eliminó la carpeta temporal.

Esta evidencia valida una práctica local de cifrado y restauración, no una
solución lista para producción: los ZIP originales siguen sin cifrar, el
repositorio está en la unidad `E:` sin confirmar que sea un destino fuera del
equipo, no incluye respaldos automáticos ni retención, y esta prueba no acredita
la recuperación integral de MySQL más `storage/app` desde una ubicación externa.
La contraseña no se guarda en Git ni en este documento.

## Requisitos antes de usar datos reales

El equipo debe confirmar y documentar estos puntos antes de cargar información
clínica real:

1. Elegir una unidad o repositorio externo que no dependa del mismo equipo.
2. Cifrar el respaldo en reposo y restringir quién puede leerlo. Una alternativa
   gratuita y de código abierto para copias cifradas es Restic; requiere instalar
   y configurar el repositorio y custodiar su contraseña fuera del equipo
   respaldado.
3. Guardar de forma segura la configuración necesaria para recuperar la
   aplicación, incluida `APP_KEY` y cualquier credencial de integración. Los
   scripts no incluyen `.env` deliberadamente.
4. Aprobar frecuencia y retención. Valor inicial sugerido: 7 copias diarias,
   4 semanales y 3 mensuales, sujeto a las necesidades de la clínica.
5. Probar una restauración al menos mensualmente y registrar fecha, responsable,
   duración y resultado.
6. Definir quién puede solicitar la restauración y cómo se documenta el
   incidente.

No usar pacientes reales hasta que el equipo haya elegido un destino externo,
cifrado y retención; incluya en el respaldo la base MySQL y `storage/app`, proteja
las claves necesarias y pruebe la recuperación completa desde ese destino. Los
scripts presentes no cifran ni rotan las copias; esas capacidades quedan
pendientes de configurar.

## Restauración

La restauración se realiza con:

```powershell
./scripts/restore.ps1 -Backup 'E:\DentissaBackups\dentissa-AAAAmmdd-HHmmss.zip'
```

El script valida el ZIP, solicita escribir `RESTAURAR` y crea una copia
preventiva del estado actual antes de sustituir SQLite y `storage/app`. Tras la
restauración, abrir la aplicación, comprobar pacientes/agenda/archivos y anotar
el resultado en la matriz QA.

Para probar una recuperación MySQL sin sobrescribir la base activa:

```powershell
./scripts/restore-mysql.ps1 -Backup 'E:\DentissaBackups\dentissa-mysql-AAAAmmdd-HHmmss.zip'
```

El script valida el ZIP y crea un schema aislado con prefijo
`dentissa_restore_`; conserva ese schema para inspección. No es un procedimiento
de corte a producción y no restaura archivos de `storage/app`. Los respaldos
SQL no cifrados deben tratarse como información sensible y eliminarse de forma
segura cuando ya no sean necesarios.
