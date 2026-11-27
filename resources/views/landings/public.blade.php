<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $landing->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #fff;
            color: #0f172a;
            font-family: "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
        }
        /* Cada sección ocupa todo el ancho; el ancho del contenido lo decide la propia sección. */
        .aviso {
            padding: 12px 16px;
            background: #dcfce7;
            color: #166534;
            text-align: center;
            font-weight: 600;
        }
        .aviso.error { background: #fee2e2; color: #991b1b; text-align: left; font-weight: 400; }
        .aviso.error ul { margin: 6px 0 0; padding-left: 20px; }
        img, video, iframe { max-width: 100%; }
        /* En móvil, las columnas se apilan una debajo de otra. */
        @media (max-width: 760px) {
            .lnd-cols { grid-template-columns: minmax(0, 1fr) !important; }
            section { padding-left: 16px !important; padding-right: 16px !important; }
        }
    </style>

    {{-- CSS personalizado que el usuario escribió para esta landing --}}
    <style>{!! $customCss !!}</style>
</head>
<body>
    {{-- Mensaje de confirmación tras enviar el formulario --}}
    @if (request()->query('ok'))
        <div class="aviso" role="status">¡Gracias! Hemos recibido tus datos.</div>
    @endif

    {{-- Errores de la validación en el servidor (si alguien se salta la del navegador) --}}
    @if ($errors->any())
        <div class="aviso error" role="alert">
            <strong>Revisa el formulario:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <main>
        {{-- El HTML ya viene saneado desde LandingRenderer --}}
        {!! $html !!}
    </main>
</body>
</html>
