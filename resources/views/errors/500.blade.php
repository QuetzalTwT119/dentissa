<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('partials.favicon')
    <title>Error inesperado — Dentissa</title>
    <style>
        @font-face { font-family: "Fraunces"; font-weight: 500; font-display: swap; src: url("/fonts/fraunces-latin-500-normal.woff2") format("woff2"); }
        body {
            margin: 0;
            padding: 2rem;
            color: #2B2224;
            background: #FBF7F3;
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
            border-radius: 2rem;
            border: 1px solid #ECE0DA;
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
            font-family: "Fraunces", Georgia, serif;
            font-weight: 500;
            font-size: 1.85rem;
            line-height: 1.2;
            color: #2B2224;
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
            border-radius: 9999px;
            color: white;
            background: #c13866;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-primary:hover {
            background: #a12a54;
        }
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border: 1px solid #ECE0DA;
            border-radius: 9999px;
            color: #403539;
            background: white;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-secondary:hover {
            background: #FBF7F3;
        }
    </style>
</head>
<body>
    <main>
        <div class="icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="12 2 2 22 22 22 12 2"></polygon>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
        </div>
        <div><span class="code">Error 500</span></div>
        <h1>Ocurrió un problema</h1>
        <p>El servidor no pudo procesar tu solicitud en este momento. Por favor vuelve a intentarlo o contacta a la clínica si persiste.</p>
        <div class="actions">
            <a href="/" class="btn-primary">Ir a inicio</a>
            <button type="button" onclick="history.back()" class="btn-secondary">Regresar</button>
        </div>
    </main>
</body>
</html>
