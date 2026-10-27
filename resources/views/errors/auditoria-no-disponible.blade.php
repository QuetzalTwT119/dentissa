<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>Operación no guardada — Dentissa</title>
    <style>
        body {
            margin: 0;
            padding: 2rem;
            color: #2D2526;
            background: #FAF6F6;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            box-sizing: border-box;
        }
        main {
            max-width: 32rem;
            width: 100%;
            padding: 2.5rem;
            background: white;
            border-radius: 1.5rem;
            border: 1px solid #e9dfdb;
            box-shadow: 0 10px 25px -5px rgba(45, 37, 38, 0.05);
            text-align: center;
        }
        .icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3.5rem;
            height: 3.5rem;
            background: #fff1f2;
            color: #d75078;
            border-radius: 1rem;
            margin-bottom: 1.5rem;
        }
        h1 {
            margin: 0 0 0.75rem;
            font-size: 1.35rem;
            font-weight: 700;
            color: #2D2526;
        }
        p {
            margin: 0 0 1rem;
            font-size: 0.925rem;
            line-height: 1.6;
            color: #6b5e62;
        }
        button {
            margin-top: 1.25rem;
            padding: 0.75rem 1.5rem;
            border: 0;
            border-radius: 0.75rem;
            color: white;
            background: #d75078;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover {
            background: #bd3f64;
        }
    </style>
</head>
<body>
    <main>
        <div class="icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h1>No se guardaron los cambios</h1>
        <p>El sistema no pudo registrar la auditoría, así que revirtió la operación para conservar el historial completo.</p>
        <p>Inténtalo de nuevo más tarde. Si el problema continúa, avisa al administrador del sistema.</p>
        <button type="button" onclick="history.back()">Volver</button>
    </main>
</body>
</html>
