<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso no autorizado — Dentissa</title>
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
            width: 4rem;
            height: 4rem;
            background: #fff1f2;
            color: #d75078;
            border-radius: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .code {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #d75078;
            background: #fdf2f4;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            margin-bottom: 0.75rem;
        }
        h1 {
            margin: 0 0 0.75rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: #2D2526;
        }
        p {
            margin: 0 0 1.25rem;
            font-size: 0.925rem;
            line-height: 1.6;
            color: #6b5e62;
        }
        .actions {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            margin-top: 1.5rem;
        }
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border: 0;
            border-radius: 0.75rem;
            color: white;
            background: #d75078;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-primary:hover {
            background: #bd3f64;
        }
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border: 1px solid #e9dfdb;
            border-radius: 0.75rem;
            color: #403539;
            background: white;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-secondary:hover {
            background: #FAF6F6;
        }
    </style>
</head>
<body>
    <main>
        <div class="icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>
        <div><span class="code">Error 403</span></div>
        <h1>Acceso reservado</h1>
        <p>No tienes los permisos necesarios para consultar esta sección o recurso clínico.</p>
        <div class="actions">
            <a href="{{ route('dashboard') }}" class="btn-primary">Ir al panel</a>
            <button type="button" onclick="history.back()" class="btn-secondary">Regresar</button>
        </div>
    </main>
</body>
</html>
