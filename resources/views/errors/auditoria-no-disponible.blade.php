<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Operación no guardada — Dentissa</title>
    <style>
        body { margin: 0; padding: 2rem; color: #1f2937; background: #fff1f2; font-family: Arial, sans-serif; }
        main { max-width: 42rem; margin: 10vh auto; padding: 2rem; background: white; border-radius: .75rem; }
        h1 { margin-top: 0; font-size: 1.5rem; }
        button { margin-top: 1rem; padding: .7rem 1rem; border: 0; border-radius: .4rem; color: white; background: #1f2937; cursor: pointer; }
    </style>
</head>
<body>
    <main>
        <h1>No se guardaron los cambios</h1>
        <p>El sistema no pudo registrar la auditoría, así que revirtió la operación para conservar el historial completo.</p>
        <p>Inténtalo de nuevo más tarde. Si el problema continúa, avisa al administrador del sistema.</p>
        <button type="button" onclick="history.back()">Volver</button>
    </main>
</body>
</html>
