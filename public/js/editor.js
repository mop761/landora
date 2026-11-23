/* Landora — Editor visual de landings (JavaScript propio)
   Mantiene el diseño como un árbol de objetos (JSON), lo dibuja en el lienzo,
   permite editarlo y lo guarda en el servidor.

   Estructura del diseño:
     page.sections[] -> cada sección tiene "cols" columnas (1-3)
     section.elements[] -> cada elemento indica en qué columna va ("col")
   El orden dentro de una columna es el orden en el que aparecen en elements[]. */
(function () {
    "use strict";
    var $ = function (s) { return document.querySelector(s); };
    var uid = function () { return Math.random().toString(36).slice(2, 8); };

    // Muestra de países para la vista previa del editor. La lista completa
    // (todos los países con su prefijo) vive en el servidor: App\Support\Countries.
    var COUNTRIES_SAMPLE = ["España (+34)", "Portugal (+351)", "Francia (+33)", "Italia (+39)",
        "Alemania (+49)", "Reino Unido (+44)", "Estados Unidos (+1)", "México (+52)",
        "Argentina (+54)", "Colombia (+57)"];

    // Convierte un texto (una opción por línea) en una lista de opciones.
    function optionList(opts) {
        return (opts || "").split("\n").map(function (o) { return o.trim(); }).filter(function (o) { return o.length; });
    }

    // Ids que usa el propio editor: no se asignan a elementos para no romperlo.
    var RESERVED_IDS = ["app", "paper", "props", "palette", "templates", "device", "customCss", "saveBtn", "toast", "userCss", "tree"];
    // Solo admite ids/clases con caracteres seguros (letras, números, guion y guion bajo).
    function cleanId(v) { v = (v || "").trim(); return /^[A-Za-z][A-Za-z0-9_-]{0,63}$/.test(v) ? v : ""; }
    function cleanClass(v) {
        return (v || "").split(/\s+/).filter(function (t) { return /^[A-Za-z_-][A-Za-z0-9_-]*$/.test(t); }).join(" ");
    }
    // Estilo común de cualquier elemento: márgenes, relleno, fondo y opacidad.
    function blockStyle(el) {
        var css = "";
        if (el.mt || el.mb) { css += "margin:" + (el.mt || 0) + "px 0 " + (el.mb || 0) + "px;"; }
        if (el.pad) { css += "padding:" + el.pad + "px;"; }
        if (el.bgc) { css += "background:" + el.bgc + ";"; }
        if (el.op != null && el.op < 100) { css += "opacity:" + (el.op / 100) + ";"; }
        return css;
    }
    // Una URL solo se usa dentro de url('...') si no trae comillas, paréntesis ni espacios.
    function cssUrl(v) { v = (v || "").trim(); return /^[^'"()\\\s]+$/.test(v) ? v : ""; }
    // Convierte un color hexadecimal en rgba() con la transparencia indicada.
    function hexToRgba(hex, a) {
        var hx = (hex || "").replace("#", "");
        if (hx.length === 3) { hx = hx[0] + hx[0] + hx[1] + hx[1] + hx[2] + hx[2]; }
        hx = hx.slice(0, 6);
        if (!/^[0-9a-fA-F]{6}$/.test(hx)) { hx = "000000"; }
        return "rgba(" + parseInt(hx.slice(0, 2), 16) + "," + parseInt(hx.slice(2, 4), 16) + "," + parseInt(hx.slice(4, 6), 16) + "," + Math.round(a * 100) / 100 + ")";
    }
    // Saca el identificador de un vídeo de YouTube a partir de su URL.
    function youtubeId(url) {
        var m = /(?:youtu\.be\/|youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/))([A-Za-z0-9_-]{11})/.exec(url || "");
        return m ? m[1] : "";
    }
    // Columnas del grid según el número de columnas y la proporción elegida.
    function gridCols(s) {
        if (s.cols === 1) { return "minmax(0,1fr)"; }
        if (s.cols === 3) { return "repeat(3,minmax(0,1fr))"; }
        var map = { "1-1": [1, 1], "3-2": [3, 2], "2-3": [2, 3], "2-1": [2, 1], "1-2": [1, 2] };
        var r = map[s.ratio] || [1, 1];
        return "minmax(0," + r[0] + "fr) minmax(0," + r[1] + "fr)";
    }

    /* ---------------- plantillas ---------------- */
    function tHero() {
        return { customCss: "", sections: [
            { id: uid(), cols: 1, ratio: "1-1", colGap: 40, valign: "start", widthMode: "px", widthVal: 640,
                bg: "#F0F9FF", padY: 56, gap: 18, elements: [
                { id: uid(), col: 0, type: "heading", tag: "h1", text: "Tu titular que convierte", size: 38, weight: 800, color: "#0F172A", align: "center" },
                { id: uid(), col: 0, type: "text", text: "Un subtítulo breve que explica tu oferta y anima a dejar los datos.", size: 17, color: "#475569", align: "center" },
                { id: uid(), col: 0, type: "form", align: "center", submitLabel: "Enviar", buttonBg: "#3B82F6", buttonColor: "#ffffff",
                    fields: [ { label: "Nombre", name: "nombre", type: "text", required: true }, { label: "Email", name: "email", type: "email", required: true } ] }
            ] }
        ] };
    }
    // Imagen de fondo + texto a la izquierda + formulario en tarjeta a la derecha.
    function tSplit() {
        return { customCss: "", sections: [
            { id: uid(), cols: 2, ratio: "3-2", colGap: 48, valign: "start", widthMode: "px", widthVal: 1000,
                bg: "#0F172A", bgImg: "", bgSize: "cover", bgPos: "center", imgOp: 60, padY: 64, gap: 20, elements: [
                { id: uid(), col: 0, type: "heading", tag: "h1", text: "FORMAMOS A LOS PROFESIONALES QUE MARCAN LA DIFERENCIA", size: 30, weight: 800, color: "#ffffff", align: "center" },
                { id: uid(), col: 0, type: "image", src: "", width: 60, align: "center" },
                { id: uid(), col: 0, type: "text", text: "Un texto de apoyo, separado del resto.", size: 16, color: "#ffffff", align: "center", mt: 40 },
                { id: uid(), col: 1, type: "form", align: "center", width: 420, title: "Obtén información",
                    intro: "Rellena el formulario y te contactamos sin compromiso.", titleColor: "#0F172A",
                    formBg: "#ffffff", formRadius: 18, formPad: 24, labelColor: "#334155",
                    submitLabel: "Quiero más detalles", buttonBg: "#E11D48", buttonColor: "#ffffff",
                    fields: [
                        { label: "Nombre", name: "nombre", type: "text", required: true },
                        { label: "Apellidos", name: "apellidos", type: "text", required: true },
                        { label: "Email", name: "email", type: "email", required: true },
                        { label: "País", name: "pais", type: "pais", required: true },
                        { label: "Teléfono", name: "telefono", type: "tel", required: true },
                        { label: "Elige tu máster", name: "master", type: "select", options: "Máster en Marketing\nMáster en Moda\nMáster en IA", required: true },
                        { label: "He leído y acepto la política de privacidad", name: "privacidad", type: "checkbox", required: true }
                    ] }
            ] }
        ] };
    }
    var TEMPLATES = { hero: tHero, split: tSplit };

    // Completa los datos que falten (también adapta diseños antiguos con disposición "en fila").
    function normalize(p) {
        p.sections.forEach(function (s) {
            if (s.cols == null) {
                if (s.dir === "row") {
                    s.cols = Math.min(Math.max(s.elements.length, 1), 3);
                    s.elements.forEach(function (el, i) { el.col = Math.min(i, s.cols - 1); });
                } else {
                    s.cols = 1;
                }
            }
            if (!s.ratio) { s.ratio = "1-1"; }
            if (s.colGap == null) { s.colGap = 40; }
            if (!s.valign) { s.valign = "start"; }
            if (!s.widthMode) { s.widthMode = "px"; s.widthVal = s.max || (s.cols > 1 ? 960 : 640); }
            if (s.gap == null) { s.gap = 16; }
            s.elements.forEach(function (el) {
                if (el.col == null) { el.col = 0; }
                if (el.col > s.cols - 1) { el.col = s.cols - 1; }
            });
        });
        return p;
    }

    /* ---------------- estado ---------------- */
    var loaded = (window.LANDORA && window.LANDORA.layout) ? window.LANDORA.layout : null;
    var page = normalize((loaded && loaded.sections) ? loaded : tHero());
    if (!page.customCss) { page.customCss = ""; }
    var sel = null;        // { s: índice sección, e: índice elemento o null }
    var device = "desktop";

    /* ---------------- dibujar en el lienzo ---------------- */
    function styleText(el) {
        return "font-size:" + el.size + "px;color:" + el.color + ";text-align:" + el.align + ";" +
            (el.weight ? "font-weight:" + el.weight + ";" : "") + "margin:0;line-height:1.35;";
    }
    function buildEl(el, si, ei) {
        var node;
        if (el.type === "heading") {
            node = document.createElement(el.tag || "h1");
            node.textContent = el.text; node.style.cssText = styleText(el);
        } else if (el.type === "text") {
            node = document.createElement("p");
            node.textContent = el.text; node.style.cssText = styleText(el) + "line-height:1.6;";
        } else if (el.type === "button") {
            var a = document.createElement("a"); a.href = "javascript:void(0)"; a.textContent = el.label;
            a.style.cssText = "display:inline-block;background:" + el.bg + ";color:" + el.color + ";border-radius:" +
                el.radius + "px;padding:12px 22px;font-weight:700;font-size:" + el.size + "px;text-decoration:none;";
            node = document.createElement("div"); node.style.textAlign = el.align; node.appendChild(a);
        } else if (el.type === "image") {
            node = document.createElement("div"); node.style.textAlign = el.align || "center";
            var im;
            if (el.src) { im = document.createElement("img"); im.src = el.src; im.alt = "";
                im.style.cssText = "display:inline-block;width:" + (el.width || 60) + "%;height:auto;border-radius:12px;vertical-align:middle;";
            } else { im = document.createElement("div");
                im.style.cssText = "display:inline-block;width:" + (el.width || 60) + "%;aspect-ratio:16/9;border-radius:12px;background:linear-gradient(135deg,#93C5FD,#3B82F6);";
            }
            node.appendChild(im);
        } else if (el.type === "video") {
            // En el editor mostramos una portada (miniatura de YouTube o un recuadro oscuro).
            node = document.createElement("div"); node.style.textAlign = el.align || "center";
            var vb = document.createElement("div");
            vb.style.cssText = "display:inline-block;position:relative;width:" + (el.width || 100) + "%;aspect-ratio:16/9;border-radius:12px;overflow:hidden;background:#0f172a center/cover no-repeat;vertical-align:middle;";
            var yt = youtubeId(el.url);
            if (yt) { vb.style.backgroundImage = "url('https://img.youtube.com/vi/" + yt + "/hqdefault.jpg')"; }
            vb.innerHTML = '<span style="position:absolute;inset:0;display:grid;place-items:center;color:#fff;font-size:42px;text-shadow:0 2px 8px rgba(0,0,0,.5)">▶</span>';
            node.appendChild(vb);
        } else if (el.type === "form") {
            node = document.createElement("form"); node.onsubmit = function (e) { e.preventDefault(); };
            node.style.maxWidth = (el.width || 420) + "px"; node.style.width = "100%";
            node.style.margin = el.align === "center" ? "0 auto" : (el.align === "right" ? "0 0 0 auto" : "0");
            if (el.formBg) { node.style.background = el.formBg; }
            if (el.borderWidth) { node.style.border = el.borderWidth + "px solid " + (el.borderColor || "#cbd5e1"); }
            node.style.borderRadius = (el.formRadius == null ? 8 : el.formRadius) + "px";
            node.style.padding = (el.formPad || 0) + "px";
            if (el.title) {
                var ft = document.createElement("h3"); ft.textContent = el.title;
                ft.style.cssText = "margin:0;font-size:22px;font-weight:700;color:" + (el.titleColor || "#0F172A") + ";";
                node.appendChild(ft);
            }
            if (el.intro) {
                var fi = document.createElement("p"); fi.textContent = el.intro;
                fi.style.cssText = "margin:0 0 4px;font-size:14px;line-height:1.5;white-space:pre-line;color:" + (el.labelColor || "#334155") + ";";
                node.appendChild(fi);
            }
            el.fields.forEach(function (f) {
                var w = document.createElement("div"); w.className = "fld";
                var l = document.createElement("label"); l.textContent = f.label + (f.required ? " *" : "");
                if (el.labelColor) { l.style.color = el.labelColor; }
                var inp;
                if (f.type === "select" || f.type === "pais") {
                    // Desplegable: país usa la muestra de países; select usa las opciones del usuario.
                    inp = document.createElement("select"); inp.disabled = true;
                    var opts = f.type === "pais" ? COUNTRIES_SAMPLE : optionList(f.options);
                    if (!opts.length) { opts = ["Opción"]; }
                    opts.forEach(function (o) { var op = document.createElement("option"); op.textContent = o; inp.appendChild(op); });
                } else {
                    inp = document.createElement("input"); inp.type = (f.type === "checkbox") ? "checkbox" : f.type;
                    inp.placeholder = f.label; inp.disabled = true;
                }
                if (f.type === "checkbox") { w.className = "fld chk"; w.appendChild(inp); w.appendChild(l); }
                else { w.appendChild(l); w.appendChild(inp); }
                node.appendChild(w);
            });
            var b = document.createElement("button"); b.type = "button"; b.textContent = el.submitLabel;
            b.style.cssText = "background:" + el.buttonBg + ";color:" + el.buttonColor + ";border:0;border-radius:8px;padding:12px 18px;font-weight:700;font-size:15px;cursor:pointer;margin-top:4px;";
            node.appendChild(b);
        }
        var box = document.createElement("div"); box.className = "el";
        if (sel && sel.s === si && sel.e === ei) { box.classList.add("sel"); }
        // Envoltorio con el estilo común del bloque y su id/clase personalizados.
        var wrap = document.createElement("div"); wrap.style.cssText = blockStyle(el);
        var cid = cleanId(el.cssId); if (cid && RESERVED_IDS.indexOf(cid) === -1) { wrap.id = cid; }
        var ccl = cleanClass(el.cssClass); if (ccl) { wrap.className = ccl; }
        wrap.appendChild(node); box.appendChild(wrap);
        box.addEventListener("click", function (ev) { ev.stopPropagation(); select(si, ei); });
        return box;
    }

    // Fondo de la sección: color y, si hay, imagen con una capa del mismo color encima
    // (la "visibilidad" de la imagen es la transparencia de esa capa).
    function sectionBg(s) {
        var css = "background-color:" + (s.bg || "#ffffff") + ";";
        var img = cssUrl(s.bgImg);
        if (img) {
            var ov = hexToRgba(s.bg || "#000000", 1 - (s.imgOp == null ? 100 : s.imgOp) / 100);
            css += "background-image:linear-gradient(" + ov + "," + ov + "),url('" + img + "');" +
                "background-size:" + (s.bgSize || "cover") + ";background-position:" + (s.bgPos || "center") + ";background-repeat:no-repeat;";
        }
        return css;
    }
    // Ancho del contenido de la sección: completo, en px o en %.
    function innerWidth(s) {
        if (s.widthMode === "full") { return "width:100%;"; }
        if (s.widthMode === "pct") { return "width:" + (s.widthVal || 90) + "%;"; }
        return "width:100%;max-width:" + (s.widthVal || 960) + "px;";
    }

    // Dibuja SOLO el lienzo (y el árbol de estructura), sin tocar el panel de propiedades.
    function renderCanvas() {
        var mobile = device === "mobile";
        var paper = $("#paper"); paper.className = "paper" + (mobile ? " mobile" : ""); paper.innerHTML = "";
        page.sections.forEach(function (s, si) {
            var sec = document.createElement("div");
            sec.className = "sec" + (sel && sel.s === si && sel.e === null ? " sel" : "");
            sec.style.cssText = sectionBg(s) + "padding:" + s.padY + "px " + (mobile ? 16 : 24) + "px;";
            var tag = document.createElement("span"); tag.className = "sec-tag"; tag.textContent = "Sección " + (si + 1); sec.appendChild(tag);
            // Contenedor de columnas (en móvil, siempre una sola columna).
            var inner = document.createElement("div");
            inner.style.cssText = innerWidth(s) + "margin:0 auto;display:grid;gap:" + s.colGap + "px;align-items:" + s.valign +
                ";grid-template-columns:" + (mobile ? "minmax(0,1fr)" : gridCols(s)) + ";";
            for (var c = 0; c < s.cols; c++) {
                var col = document.createElement("div");
                col.style.cssText = "display:flex;flex-direction:column;gap:" + s.gap + "px;min-width:0;";
                s.elements.forEach(function (el, ei) { if (el.col === c) { col.appendChild(buildEl(el, si, ei)); } });
                if (!col.children.length) { col.appendChild(h('<div class="col-empty">Columna ' + (c + 1) + ' vacía</div>')); }
                inner.appendChild(col);
            }
            sec.appendChild(inner);
            sec.addEventListener("click", function () { select(si, null); });
            paper.appendChild(sec);
        });
        applyCss();
        renderTree();
    }

    // Redibuja todo: lienzo + panel de propiedades. Se usa al cambiar la selección o la estructura.
    function render() { renderCanvas(); renderProps(); }

    /* ---------------- reordenar ---------------- */
    // Mueve un elemento arriba/abajo DENTRO DE SU COLUMNA (salta los de otras columnas).
    function moveInCol(si, ei, dir) {
        var a = page.sections[si].elements, col = a[ei].col, j = ei + dir;
        while (j >= 0 && j < a.length && a[j].col !== col) { j += dir; }
        if (j < 0 || j >= a.length) { return; }
        var t = a[j]; a[j] = a[ei]; a[ei] = t;
        if (sel && sel.s === si && sel.e === ei) { sel.e = j; }
        render();
    }
    function moveSection(si, dir) {
        var j = si + dir;
        if (j < 0 || j >= page.sections.length) { return; }
        var t = page.sections[j]; page.sections[j] = page.sections[si]; page.sections[si] = t;
        if (sel && sel.s === si) { sel.s = j; } else if (sel && sel.s === j) { sel.s = si; }
        render();
    }

    /* ---------------- árbol de estructura ---------------- */
    function snippet(el) {
        var t = (el.type === "heading" || el.type === "text") ? el.text : el.type === "button" ? el.label :
            el.type === "form" ? el.title : "";
        t = (t || "").replace(/\s+/g, " ").trim();
        return t.length > 22 ? t.slice(0, 22) + "…" : t;
    }
    function renderTree() {
        var tree = $("#tree"); if (!tree) { return; }
        tree.innerHTML = "";
        page.sections.forEach(function (s, si) {
            var sh = h('<div class="tr-sec' + (sel && sel.s === si && sel.e === null ? " on" : "") + '"><span class="tr-name">Sección ' + (si + 1) +
                '</span><span class="tr-act"><button type="button" class="up" title="Subir">↑</button><button type="button" class="dn" title="Bajar">↓</button></span></div>');
            sh.querySelector(".tr-name").onclick = function () { select(si, null); };
            sh.querySelector(".up").onclick = function () { moveSection(si, -1); };
            sh.querySelector(".dn").onclick = function () { moveSection(si, 1); };
            tree.appendChild(sh);
            for (var c = 0; c < s.cols; c++) {
                if (s.cols > 1) { tree.appendChild(h('<div class="tr-col">Columna ' + (c + 1) + '</div>')); }
                s.elements.forEach(function (el, ei) {
                    if (el.col !== c) { return; }
                    var it = h('<div class="tr-el' + (sel && sel.s === si && sel.e === ei ? " on" : "") + '"><span class="tr-name">' + labelOf(el) +
                        ' <small></small></span><span class="tr-act"><button type="button" class="up" title="Subir">↑</button><button type="button" class="dn" title="Bajar">↓</button></span></div>');
                    it.querySelector("small").textContent = snippet(el);
                    it.querySelector(".tr-name").onclick = function () { select(si, ei); };
                    it.querySelector(".up").onclick = function () { moveInCol(si, ei, -1); };
                    it.querySelector(".dn").onclick = function () { moveInCol(si, ei, 1); };
                    tree.appendChild(it);
                });
            }
        });
    }

    /* ---------------- panel de propiedades ---------------- */
    function h(html) { var d = document.createElement("div"); d.innerHTML = html.trim(); return d.firstChild; }
    function field(label, inner) { var w = document.createElement("div"); w.className = "prop";
        w.innerHTML = "<label>" + label + "</label>"; w.appendChild(inner); return w; }
    function colorInput(val, on) {
        var w = h('<div class="colorrow"><input type="color" value="' + val + '"><span class="val">' + val + '</span></div>');
        var i = w.querySelector("input");
        i.addEventListener("input", function () { w.querySelector(".val").textContent = i.value; on(i.value); });
        return w;
    }
    function textInput(val, on, ta) {
        var i = document.createElement(ta ? "textarea" : "input"); if (!ta) { i.type = "text"; }
        i.value = val; i.addEventListener("input", function () { on(i.value); }); return i;
    }
    function range(val, min, max, on) {
        var i = h('<input type="range" min="' + min + '" max="' + max + '" value="' + val + '">');
        i.addEventListener("input", function () { on(+i.value); }); return i;
    }
    // Botonera para elegir una opción entre varias: opts = [[valor, texto], ...].
    function segPick(opts, cur, on) {
        var w = document.createElement("div"); w.className = "seg small";
        opts.forEach(function (o) {
            var b = document.createElement("button"); b.type = "button"; b.textContent = o[1];
            if (String(o[0]) === String(cur)) { b.classList.add("on"); }
            b.onclick = function () { w.querySelectorAll("button").forEach(function (x) { x.classList.remove("on"); }); b.classList.add("on"); on(o[0]); };
            w.appendChild(b);
        });
        return w;
    }
    function alignSeg(val, on) { return segPick([["left", "Izq."], ["center", "Centro"], ["right", "Dcha."]], val, on); }
    function select(si, ei) { sel = { s: si, e: ei }; render(); }

    function renderProps() {
        var box = $("#props"); box.innerHTML = "";
        if (!sel) { box.innerHTML = '<div class="empty">Selecciona una sección o un elemento del lienzo (o del panel Estructura) para editar sus propiedades aquí.</div>'; return; }
        var s = page.sections[sel.s];

        if (sel.e === null) { // sección
            box.appendChild(h('<div class="panel-head"><span class="kind">Sección ' + (sel.s + 1) + '</span><div class="toolset">' +
                '<button class="icon-btn" id="secUp" title="Subir">↑</button><button class="icon-btn" id="secDn" title="Bajar">↓</button>' +
                '<button class="icon-btn" id="delSec" title="Eliminar sección">✕</button></div></div>'));
            box.appendChild(field("Columnas", segPick([[1, "1"], [2, "2"], [3, "3"]], s.cols, function (v) {
                s.cols = v; s.elements.forEach(function (el) { if (el.col > v - 1) { el.col = v - 1; } }); render();
            })));
            if (s.cols === 2) {
                box.appendChild(field("Proporción", segPick([["1-1", "50/50"], ["3-2", "60/40"], ["2-3", "40/60"], ["2-1", "66/33"], ["1-2", "33/66"]], s.ratio, function (v) { s.ratio = v; renderCanvas(); })));
            }
            if (s.cols > 1) {
                box.appendChild(field("Alineación vertical", segPick([["start", "Arriba"], ["center", "Centro"], ["end", "Abajo"]], s.valign, function (v) { s.valign = v; renderCanvas(); })));
                box.appendChild(field("Separación entre columnas", range(s.colGap, 0, 120, function (v) { s.colGap = v; renderCanvas(); })));
            }
            box.appendChild(field("Ancho del contenido", segPick([["full", "Completo"], ["px", "En px"], ["pct", "En %"]], s.widthMode, function (v) {
                s.widthMode = v; s.widthVal = v === "pct" ? 90 : 960; render();
            })));
            if (s.widthMode === "px") { box.appendChild(field("Ancho máximo (px)", range(s.widthVal, 320, 1600, function (v) { s.widthVal = v; renderCanvas(); }))); }
            if (s.widthMode === "pct") { box.appendChild(field("Ancho (%)", range(s.widthVal, 30, 100, function (v) { s.widthVal = v; renderCanvas(); }))); }
            box.appendChild(field("Color de fondo", colorInput(s.bg || "#ffffff", function (v) { s.bg = v; renderCanvas(); })));
            box.appendChild(field("Imagen de fondo (URL)", textInput(s.bgImg || "", function (v) { s.bgImg = v; renderCanvas(); })));
            box.appendChild(field("Ajuste de la imagen", segPick([["cover", "Cubrir"], ["contain", "Contener"], ["auto", "Original"]], s.bgSize || "cover", function (v) { s.bgSize = v; renderCanvas(); })));
            box.appendChild(field("Posición de la imagen", segPick([["center", "Centro"], ["top", "Arriba"], ["bottom", "Abajo"]], s.bgPos || "center", function (v) { s.bgPos = v; renderCanvas(); })));
            box.appendChild(field("Visibilidad de la imagen (%)", range(s.imgOp == null ? 100 : s.imgOp, 0, 100, function (v) { s.imgOp = v; renderCanvas(); })));
            box.appendChild(h('<div class="hint" style="margin:-6px 0 12px">Por debajo de 100, la imagen se mezcla con el color de fondo.</div>'));
            box.appendChild(field("Espaciado vertical", range(s.padY, 0, 200, function (v) { s.padY = v; renderCanvas(); })));
            box.appendChild(field("Separación entre elementos", range(s.gap, 0, 60, function (v) { s.gap = v; renderCanvas(); })));
            $("#secUp").onclick = function () { moveSection(sel.s, -1); };
            $("#secDn").onclick = function () { moveSection(sel.s, 1); };
            $("#delSec").onclick = function () { if (page.sections.length > 1) { page.sections.splice(sel.s, 1); sel = null; render(); } };
            return;
        }

        var el = s.elements[sel.e];
        box.appendChild(h('<div class="panel-head"><span class="kind">' + labelOf(el) + '</span><div class="toolset">' +
            '<button class="icon-btn" id="mvUp" title="Subir">↑</button>' +
            '<button class="icon-btn" id="mvDn" title="Bajar">↓</button>' +
            '<button class="icon-btn" id="delEl" title="Eliminar">✕</button></div></div>'));

        // Si la sección tiene varias columnas, se elige en cuál va el elemento.
        if (s.cols > 1) {
            var colOpts = [];
            for (var c = 0; c < s.cols; c++) { colOpts.push([c, "Columna " + (c + 1)]); }
            box.appendChild(field("Columna", segPick(colOpts, el.col, function (v) { el.col = v; render(); })));
        }

        if (el.type === "heading" || el.type === "text") {
            box.appendChild(field("Contenido", textInput(el.text, function (v) { el.text = v; renderCanvas(); }, true)));
            box.appendChild(field("Tamaño", range(el.size, 12, 64, function (v) { el.size = v; renderCanvas(); })));
            if (el.type === "heading") {
                box.appendChild(field("Etiqueta", segPick([["h1", "H1"], ["h2", "H2"]], el.tag, function (v) { el.tag = v; renderCanvas(); })));
                box.appendChild(field("Peso", range(el.weight, 300, 800, function (v) { el.weight = Math.round(v / 100) * 100; renderCanvas(); })));
            }
            box.appendChild(field("Color", colorInput(el.color, function (v) { el.color = v; renderCanvas(); })));
            box.appendChild(field("Alineación", alignSeg(el.align, function (v) { el.align = v; renderCanvas(); })));
        } else if (el.type === "button") {
            box.appendChild(field("Texto", textInput(el.label, function (v) { el.label = v; renderCanvas(); })));
            box.appendChild(field("Enlace (URL)", textInput(el.href, function (v) { el.href = v; })));
            var r = h('<div class="row2"></div>');
            r.appendChild(field("Fondo", colorInput(el.bg, function (v) { el.bg = v; renderCanvas(); })));
            r.appendChild(field("Texto", colorInput(el.color, function (v) { el.color = v; renderCanvas(); })));
            box.appendChild(r);
            box.appendChild(field("Redondeo", range(el.radius, 0, 30, function (v) { el.radius = v; renderCanvas(); })));
            box.appendChild(field("Alineación", alignSeg(el.align, function (v) { el.align = v; renderCanvas(); })));
        } else if (el.type === "image") {
            box.appendChild(field("URL de la imagen", textInput(el.src || "", function (v) { el.src = v; renderCanvas(); })));
            box.appendChild(field("Ancho (%)", range(el.width || 60, 10, 100, function (v) { el.width = v; renderCanvas(); })));
            box.appendChild(field("Alineación", alignSeg(el.align || "center", function (v) { el.align = v; renderCanvas(); })));
        } else if (el.type === "video") {
            box.appendChild(field("URL del vídeo (YouTube, Vimeo o .mp4)", textInput(el.url || "", function (v) { el.url = v; renderCanvas(); })));
            box.appendChild(field("Ancho (%)", range(el.width || 100, 30, 100, function (v) { el.width = v; renderCanvas(); })));
            box.appendChild(field("Alineación", alignSeg(el.align || "center", function (v) { el.align = v; renderCanvas(); })));
        } else if (el.type === "form") {
            box.appendChild(field("Título del formulario", textInput(el.title || "", function (v) { el.title = v; renderCanvas(); })));
            box.appendChild(field("Texto de introducción", textInput(el.intro || "", function (v) { el.intro = v; renderCanvas(); }, true)));
            box.appendChild(field("Color del título", colorInput(el.titleColor || "#0F172A", function (v) { el.titleColor = v; renderCanvas(); })));
            box.appendChild(field("Texto del botón", textInput(el.submitLabel, function (v) { el.submitLabel = v; renderCanvas(); })));
            var r2 = h('<div class="row2"></div>');
            r2.appendChild(field("Botón", colorInput(el.buttonBg, function (v) { el.buttonBg = v; renderCanvas(); })));
            r2.appendChild(field("Texto botón", colorInput(el.buttonColor, function (v) { el.buttonColor = v; renderCanvas(); })));
            box.appendChild(r2);
            box.appendChild(field("Ancho del formulario (px)", range(el.width || 420, 240, 800, function (v) { el.width = v; renderCanvas(); })));
            box.appendChild(field("Alineación", alignSeg(el.align || "center", function (v) { el.align = v; renderCanvas(); })));
            box.appendChild(field("Color de las etiquetas", colorInput(el.labelColor || "#334155", function (v) { el.labelColor = v; renderCanvas(); })));
            box.appendChild(field("Fondo del formulario", optColor(el.formBg, function (v) { el.formBg = v; renderCanvas(); })));
            var r3 = h('<div class="row2"></div>');
            r3.appendChild(field("Color del borde", colorInput(el.borderColor || "#cbd5e1", function (v) { el.borderColor = v; renderCanvas(); })));
            r3.appendChild(field("Grosor del borde", range(el.borderWidth || 0, 0, 6, function (v) { el.borderWidth = v; renderCanvas(); })));
            box.appendChild(r3);
            box.appendChild(field("Redondeo del formulario", range(el.formRadius == null ? 8 : el.formRadius, 0, 30, function (v) { el.formRadius = v; renderCanvas(); })));
            box.appendChild(field("Relleno interior", range(el.formPad || 0, 0, 48, function (v) { el.formPad = v; renderCanvas(); })));
            var fh = document.createElement("div"); fh.className = "prop"; fh.innerHTML = "<label>Campos del formulario</label>";
            el.fields.forEach(function (f, fi) {
                var c = h('<div class="fieldcard">' +
                    '<div class="fc-top"><input type="text" placeholder="Etiqueta">' +
                    '<button class="icon-btn fup" title="Subir">↑</button>' +
                    '<button class="icon-btn fdn" title="Bajar">↓</button>' +
                    '<button class="icon-btn fdel" title="Quitar">✕</button></div>' +
                    '<select class="ftype">' +
                    '<option value="text">Texto</option>' +
                    '<option value="email">Email</option>' +
                    '<option value="tel">Teléfono</option>' +
                    '<option value="select">Desplegable</option>' +
                    '<option value="pais">País (con prefijo)</option>' +
                    '<option value="checkbox">Casilla (consentimiento)</option>' +
                    '</select>' +
                    '<label class="mini"><input type="checkbox" class="freq" ' + (f.required ? "checked" : "") + '> Obligatorio</label>' +
                    '</div>');
                var lab = c.querySelector(".fc-top input");
                lab.value = f.label;
                lab.addEventListener("input", function () { f.label = lab.value; renderCanvas(); });
                c.querySelector(".fdel").onclick = function () { el.fields.splice(fi, 1); render(); };
                c.querySelector(".fup").onclick = function () { if (fi > 0) { var t = el.fields[fi - 1]; el.fields[fi - 1] = el.fields[fi]; el.fields[fi] = t; render(); } };
                c.querySelector(".fdn").onclick = function () { if (fi < el.fields.length - 1) { var t = el.fields[fi + 1]; el.fields[fi + 1] = el.fields[fi]; el.fields[fi] = t; render(); } };
                var selT = c.querySelector(".ftype"); selT.value = f.type;
                selT.onchange = function () {
                    f.type = selT.value;
                    if (f.type === "select" && !f.options) { f.options = "Opción 1\nOpción 2"; }
                    render();
                };
                c.querySelector(".freq").onchange = function (e) { f.required = e.target.checked; renderCanvas(); };
                // Si el campo es un desplegable "normal", mostramos sus opciones editables.
                if (f.type === "select") {
                    var optWrap = document.createElement("div"); optWrap.style.marginTop = "6px";
                    optWrap.innerHTML = '<label class="mini">Opciones (una por línea)</label>';
                    var ta = document.createElement("textarea"); ta.value = f.options || ""; ta.rows = 3;
                    ta.style.cssText = "width:100%;margin-top:4px;border:1px solid var(--line);border-radius:7px;padding:6px;font:inherit;font-size:12px;resize:vertical";
                    ta.addEventListener("input", function () { f.options = ta.value; renderCanvas(); });
                    optWrap.appendChild(ta); c.appendChild(optWrap);
                }
                fh.appendChild(c);
            });
            var add = h('<button class="addfield">+ Añadir campo</button>');
            add.onclick = function () { el.fields.push({ label: "Nuevo campo", name: "campo_" + uid(), type: "text", required: false }); render(); };
            fh.appendChild(add); box.appendChild(fh);
        }

        commonProps(box, el);

        $("#delEl").onclick = function () { s.elements.splice(sel.e, 1); sel = { s: sel.s, e: null }; render(); };
        $("#mvUp").onclick = function () { moveInCol(sel.s, sel.e, -1); };
        $("#mvDn").onclick = function () { moveInCol(sel.s, sel.e, 1); };
    }

    // Color opcional: casilla para activarlo/desactivarlo + selector de color.
    function optColor(val, on) {
        var w = h('<div class="colorrow"><input type="checkbox" ' + (val ? "checked" : "") + '><input type="color" value="' + (val || "#ffffff") + '"><span class="val">' + (val || "ninguno") + '</span></div>');
        var cb = w.querySelector('input[type="checkbox"]'), c = w.querySelector('input[type="color"]'), sp = w.querySelector(".val");
        function upd() { var v = cb.checked ? c.value : ""; sp.textContent = v || "ninguno"; on(v); }
        cb.onchange = upd;
        c.addEventListener("input", function () { cb.checked = true; upd(); });
        return w;
    }

    // Bloque de estilo común a todos los elementos.
    function commonProps(box, el) {
        var head = document.createElement("div"); head.className = "prop";
        head.innerHTML = '<label style="margin-top:10px;color:var(--accent);text-transform:uppercase;letter-spacing:.05em">Estilo del bloque</label>';
        box.appendChild(head);
        var rm = h('<div class="row2"></div>');
        rm.appendChild(field("Margen superior", range(el.mt || 0, 0, 120, function (v) { el.mt = v; renderCanvas(); })));
        rm.appendChild(field("Margen inferior", range(el.mb || 0, 0, 120, function (v) { el.mb = v; renderCanvas(); })));
        box.appendChild(rm);
        box.appendChild(field("Relleno", range(el.pad || 0, 0, 60, function (v) { el.pad = v; renderCanvas(); })));
        box.appendChild(field("Fondo del bloque", optColor(el.bgc, function (v) { el.bgc = v; renderCanvas(); })));
        box.appendChild(field("Opacidad", range(el.op == null ? 100 : el.op, 10, 100, function (v) { el.op = v; renderCanvas(); })));
        var ri = h('<div class="row2"></div>');
        ri.appendChild(field("ID", textInput(el.cssId || "", function (v) { el.cssId = v; renderCanvas(); })));
        ri.appendChild(field("Clase", textInput(el.cssClass || "", function (v) { el.cssClass = v; renderCanvas(); })));
        box.appendChild(ri);
    }

    function labelOf(el) { return { heading: "Título", text: "Texto", button: "Botón", image: "Imagen", video: "Vídeo", form: "Formulario" }[el.type] || "Elemento"; }

    /* ---------------- añadir / plantillas ---------------- */
    function addElement(type) {
        if (type === "section") {
            page.sections.push({ id: uid(), cols: 1, ratio: "1-1", colGap: 40, valign: "start", widthMode: "px", widthVal: 760,
                bg: "#ffffff", padY: 44, gap: 16, elements: [
                { id: uid(), col: 0, type: "heading", tag: "h2", text: "Nueva sección", size: 26, weight: 700, color: "#0F172A", align: "center" } ] });
            sel = { s: page.sections.length - 1, e: null }; render(); return;
        }
        var si = sel ? sel.s : page.sections.length - 1;
        var s = page.sections[si];
        // Va a la misma columna que el elemento seleccionado (o a la primera).
        var col = (sel && sel.e !== null) ? s.elements[sel.e].col : 0;
        var al = s.cols > 1 ? "left" : "center";
        var defaults = {
            heading: { type: "heading", tag: "h2", text: "Nuevo título", size: 28, weight: 700, color: "#0F172A", align: al },
            text: { type: "text", text: "Escribe aquí tu texto.", size: 16, color: "#475569", align: al },
            button: { type: "button", label: "Botón", href: "#", bg: "#3B82F6", color: "#ffffff", radius: 10, size: 16, align: al },
            image: { type: "image", src: "", width: 70, align: "center" },
            video: { type: "video", url: "", width: 100, align: "center" },
            form: { type: "form", align: "center", submitLabel: "Enviar", buttonBg: "#3B82F6", buttonColor: "#ffffff",
                fields: [ { label: "Email", name: "email", type: "email", required: true } ] }
        };
        var nuevo = defaults[type]; nuevo.id = uid(); nuevo.col = col;
        // Se inserta justo debajo del elemento seleccionado; si no hay, al final.
        var pos = (sel && sel.e !== null) ? sel.e + 1 : s.elements.length;
        s.elements.splice(pos, 0, nuevo);
        sel = { s: si, e: pos }; render();
    }

    /* ---------------- CSS personalizado ---------------- */
    function applyCss() {
        var tag = $("#userCss"); if (!tag) { tag = document.createElement("style"); tag.id = "userCss"; document.head.appendChild(tag); }
        tag.textContent = page.customCss || "";
    }

    /* ---------------- guardar en el servidor ---------------- */
    function toast(msg) {
        var t = $("#toast"); if (!t) { t = document.createElement("div"); t.id = "toast"; t.className = "toast"; document.body.appendChild(t); }
        t.textContent = msg; t.classList.add("show"); setTimeout(function () { t.classList.remove("show"); }, 1800);
    }
    function save() {
        var btn = $("#saveBtn"); btn.disabled = true; var prev = btn.textContent; btn.textContent = "Guardando…";
        fetch(window.LANDORA.saveUrl, {
            method: "PUT",
            headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": window.LANDORA.csrf },
            body: JSON.stringify({ layout: page })
        }).then(function (r) {
            if (!r.ok) { throw new Error("HTTP " + r.status); }
            return r.json();
        }).then(function () {
            btn.textContent = "Guardado ✓";
            setTimeout(function () { btn.textContent = prev; btn.disabled = false; }, 1200);
            toast("Diseño guardado correctamente.");
        }).catch(function (e) {
            console.error(e); btn.textContent = prev; btn.disabled = false;
            toast("No se pudo guardar. Inténtalo de nuevo.");
        });
    }

    /* ---------------- conexiones de eventos ---------------- */
    $("#palette").addEventListener("click", function (e) { var b = e.target.closest("[data-add]"); if (b) { addElement(b.dataset.add); } });
    $("#templates").addEventListener("click", function (e) { var b = e.target.closest("[data-tpl]"); if (b) { page = normalize(TEMPLATES[b.dataset.tpl]()); sel = null; $("#customCss").value = page.customCss || ""; render(); } });
    $("#device").addEventListener("click", function (e) { var b = e.target.closest("[data-dev]"); if (!b) { return; }
        device = b.dataset.dev; $("#device").querySelectorAll("button").forEach(function (x) { x.classList.toggle("on", x === b); }); renderCanvas(); });
    $("#customCss").addEventListener("input", function () { page.customCss = $("#customCss").value; applyCss(); });
    $("#saveBtn").addEventListener("click", save);

    /* ---------------- arranque ---------------- */
    $("#customCss").value = page.customCss || "";
    render();
})();
