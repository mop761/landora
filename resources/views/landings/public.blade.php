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
            background: #eef2f7;
            color: #0f172a;
            font-family: "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
        }
        /* El "lienzo" reproduce el mismo contenedor que se ve en el editor,
           para que la página publicada coincida con lo que diseñó el usuario. */
        .paper {
            background: #fff;
            max-width: 880px;
            margin: 24px auto;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(15,23,42,.06), 0 6px 18px rgba(15,23,42,.06);
        }
        @media (max-width: 880px) {
            .paper { margin: 0; border-radius: 0; }
        }
    </style>

    {{-- CSS personalizado que el usuario escribió para esta landing --}}
    <style>{!! $customCss !!}</style>
</head>
<body>
    <div class="paper">
        {{-- El HTML ya viene saneado desde LandingRenderer --}}
        {!! $html !!}
    </div>
</body>
</html>
