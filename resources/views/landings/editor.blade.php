<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Editor · {{ $landing->name }} · Landora</title>
    <link rel="stylesheet" href="{{ asset('css/editor.css') }}">
</head>
<body>
<div id="app">
    <div class="toolbar">
        <div class="brand">
            <span class="mark"></span>
            <div>Landora<br><small>Editor · {{ $landing->name }}</small></div>
        </div>
        <div class="tb-spacer"></div>
        <div class="seg" id="device">
            <button data-dev="desktop" class="on">Escritorio</button>
            <button data-dev="mobile">Móvil</button>
        </div>
        <a class="btn" href="{{ route('landings.index') }}">&larr; Volver</a>
        <button class="btn primary" id="saveBtn">Guardar</button>
    </div>

    <div class="main">
        <aside class="rail left">
            <div class="group">
                <h3>Añadir elemento</h3>
                <div class="palette" id="palette">
                    <button class="pal-btn" data-add="heading"><span class="ico">H</span>Título</button>
                    <button class="pal-btn" data-add="text"><span class="ico">¶</span>Texto</button>
                    <button class="pal-btn" data-add="button"><span class="ico">▭</span>Botón</button>
                    <button class="pal-btn" data-add="image"><span class="ico">▣</span>Imagen</button>
                    <button class="pal-btn" data-add="form"><span class="ico">≣</span>Formulario</button>
                    <button class="pal-btn" data-add="section"><span class="ico">▤</span>Sección</button>
                </div>
                <div class="hint">Se añade a la sección seleccionada (o al final).</div>
            </div>
            <div class="group">
                <h3>Plantillas base</h3>
                <div class="tpl" id="templates">
                    <button class="tpl-btn" data-tpl="hero">Título + formulario</button>
                    <button class="tpl-btn" data-tpl="split">Título izq. / form dcha.</button>
                </div>
                <div class="hint">Reemplaza el diseño actual.</div>
            </div>
            <div class="group">
                <h3>CSS personalizado</h3>
                <textarea class="css" id="customCss" placeholder="/* parche de estilos para esta landing */"></textarea>
            </div>
        </aside>

        <section class="stage">
            <div class="paper" id="paper"></div>
        </section>

        <aside class="rail right" id="props">
            <div class="empty">Selecciona una sección o un elemento del lienzo para editar sus propiedades aquí.</div>
        </aside>
    </div>
</div>

<script>
    window.LANDORA = {
        layout: @json($landing->layout),
        saveUrl: "{{ route('landings.layout', $landing) }}",
        csrf: document.querySelector('meta[name=csrf-token]').getAttribute('content')
    };
</script>
<script src="{{ asset('js/editor.js') }}"></script>
</body>
</html>
