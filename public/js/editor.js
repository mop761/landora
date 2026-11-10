/* Landora — Editor visual de landings (JavaScript propio)
   Mantiene el diseño como un árbol de objetos (JSON), lo dibuja en el lienzo,
   permite editarlo y lo guarda en el servidor. */
(function () {
    "use strict";
    var $ = function (s) { return document.querySelector(s); };
    var uid = function () { return Math.random().toString(36).slice(2, 8); };

    /* ---------------- plantillas ---------------- */
    function tHero() {
        return { customCss: "", sections: [
            { id: uid(), dir: "column", bg: "#F0F9FF", padY: 56, gap: 18, align: "center", max: 640, elements: [
                { id: uid(), type: "heading", tag: "h1", text: "Tu titular que convierte", size: 38, weight: 800, color: "#0F172A", align: "center" },
                { id: uid(), type: "text", text: "Un subtítulo breve que explica tu oferta y anima a dejar los datos.", size: 17, color: "#475569", align: "center" },
                { id: uid(), type: "form", align: "center", submitLabel: "Enviar", buttonBg: "#3B82F6", buttonColor: "#ffffff",
                    fields: [ { label: "Nombre", name: "nombre", type: "text", required: true }, { label: "Email", name: "email", type: "email", required: true } ] }
            ] }
        ] };
    }
    function tSplit() {
        return { customCss: "", sections: [
            { id: uid(), dir: "row", bg: "#0F172A", padY: 60, gap: 40, align: "center", max: 960, elements: [
                { id: uid(), type: "heading", tag: "h1", text: "Tu producto merece una gran página", size: 34, weight: 800, color: "#ffffff", align: "left", grow: true },
                { id: uid(), type: "form", align: "left", submitLabel: "Empezar", buttonBg: "#3B82F6", buttonColor: "#ffffff", grow: true,
                    fields: [ { label: "Email", name: "email", type: "email", required: true } ] }
            ] }
        ] };
    }
    var TEMPLATES = { hero: tHero, split: tSplit };

    /* ---------------- estado ---------------- */
    var loaded = (window.LANDORA && window.LANDORA.layout) ? window.LANDORA.layout : null;
    var page = (loaded && loaded.sections) ? loaded : tHero();
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
        } else if (el.type === "form") {
            node = document.createElement("form"); node.onsubmit = function (e) { e.preventDefault(); };
            node.style.maxWidth = "420px"; node.style.margin = el.align === "center" ? "0 auto" : "0";
            el.fields.forEach(function (f) {
                var w = document.createElement("div"); w.className = "fld";
                var l = document.createElement("label"); l.textContent = f.label + (f.required ? " *" : "");
                var inp = document.createElement("input"); inp.type = f.type === "textarea" ? "text" : f.type;
                inp.placeholder = f.label; inp.disabled = true;
                w.appendChild(l); w.appendChild(inp); node.appendChild(w);
            });
            var b = document.createElement("button"); b.type = "button"; b.textContent = el.submitLabel;
            b.style.cssText = "background:" + el.buttonBg + ";color:" + el.buttonColor + ";border:0;border-radius:8px;padding:12px 18px;font-weight:700;font-size:15px;cursor:pointer;margin-top:4px;";
            node.appendChild(b);
        }
        var box = document.createElement("div"); box.className = "el" + (el.grow ? " grow" : "");
        if (sel && sel.s === si && sel.e === ei) { box.classList.add("sel"); }
        box.appendChild(node);
        box.addEventListener("click", function (ev) { ev.stopPropagation(); select(si, ei); });
        return box;
    }

    // Dibuja SOLO el lienzo (sin tocar el panel de propiedades).
    function renderCanvas() {
        var paper = $("#paper"); paper.className = "paper" + (device === "mobile" ? " mobile" : ""); paper.innerHTML = "";
        page.sections.forEach(function (s, si) {
            var sec = document.createElement("div");
            sec.className = "sec" + (sel && sel.s === si && sel.e === null ? " sel" : "");
            sec.dataset.dir = s.dir;
            sec.style.background = s.bg; sec.style.paddingTop = s.padY + "px"; sec.style.paddingBottom = s.padY + "px";
            sec.style.gap = (s.gap || 16) + "px";
            sec.style.alignItems = s.dir === "row" ? "center" : (s.align === "center" ? "center" : s.align === "right" ? "flex-end" : "flex-start");
            if (s.dir === "column") { sec.style.maxWidth = (s.max || 640) + "px"; sec.style.marginLeft = "auto"; sec.style.marginRight = "auto"; }
            var tag = document.createElement("span"); tag.className = "sec-tag"; tag.textContent = "Sección"; sec.appendChild(tag);
            s.elements.forEach(function (el, ei) { sec.appendChild(buildEl(el, si, ei)); });
            sec.addEventListener("click", function () { select(si, null); });
            paper.appendChild(sec);
        });
        applyCss();
    }

    // Redibuja todo: lienzo + panel de propiedades. Se usa al cambiar la selección o la estructura.
    function render() { renderCanvas(); renderProps(); }

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
    function alignSeg(val, on) {
        var w = h('<div class="seg small"><button data-a="left">Izq.</button><button data-a="center">Centro</button><button data-a="right">Dcha.</button></div>');
        w.querySelectorAll("button").forEach(function (b) {
            if (b.dataset.a === val) { b.classList.add("on"); }
            b.onclick = function () { w.querySelectorAll("button").forEach(function (x) { x.classList.remove("on"); }); b.classList.add("on"); on(b.dataset.a); };
        });
        return w;
    }
    function select(si, ei) { sel = { s: si, e: ei }; render(); }

    function renderProps() {
        var box = $("#props"); box.innerHTML = "";
        if (!sel) { box.innerHTML = '<div class="empty">Selecciona una sección o un elemento del lienzo para editar sus propiedades aquí.</div>'; return; }
        var s = page.sections[sel.s];

        if (sel.e === null) { // sección
            box.appendChild(h('<div class="panel-head"><span class="kind">Sección</span><div class="toolset"><button class="icon-btn" id="delSec" title="Eliminar sección">✕</button></div></div>'));
            box.appendChild(field("Color de fondo", colorInput(s.bg, function (v) { s.bg = v; renderCanvas(); })));
            box.appendChild(field("Espaciado vertical", range(s.padY, 10, 110, function (v) { s.padY = v; renderCanvas(); })));
            box.appendChild(field("Separación entre elementos", range(s.gap || 16, 0, 50, function (v) { s.gap = v; renderCanvas(); })));
            var dir = h('<div class="seg small"><button data-d="column">Apilado</button><button data-d="row">En fila</button></div>');
            dir.querySelectorAll("button").forEach(function (b) { if (b.dataset.d === s.dir) { b.classList.add("on"); }
                b.onclick = function () { s.dir = b.dataset.d; render(); }; });
            box.appendChild(field("Disposición", dir));
            if (s.dir === "column") { box.appendChild(field("Alineación", alignSeg(s.align || "center", function (v) { s.align = v; renderCanvas(); }))); }
            $("#delSec").onclick = function () { if (page.sections.length > 1) { page.sections.splice(sel.s, 1); sel = null; render(); } };
            return;
        }

        var el = s.elements[sel.e];
        box.appendChild(h('<div class="panel-head"><span class="kind">' + labelOf(el) + '</span><div class="toolset">' +
            '<button class="icon-btn" id="mvUp" title="Subir">↑</button>' +
            '<button class="icon-btn" id="mvDn" title="Bajar">↓</button>' +
            '<button class="icon-btn" id="delEl" title="Eliminar">✕</button></div></div>'));

        if (el.type === "heading" || el.type === "text") {
            box.appendChild(field("Contenido", textInput(el.text, function (v) { el.text = v; renderCanvas(); }, true)));
            box.appendChild(field("Tamaño (" + el.size + "px)", range(el.size, 12, 64, function (v) { el.size = v; renderCanvas(); })));
            if (el.type === "heading") {
                var tg = h('<div class="seg small"><button data-t="h1">H1</button><button data-t="h2">H2</button></div>');
                tg.querySelectorAll("button").forEach(function (b) { if (b.dataset.t === el.tag) { b.classList.add("on"); } b.onclick = function () { el.tag = b.dataset.t; renderCanvas(); }; });
                box.appendChild(field("Etiqueta", tg));
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
            box.appendChild(field("Redondeo (" + el.radius + "px)", range(el.radius, 0, 30, function (v) { el.radius = v; renderCanvas(); })));
            box.appendChild(field("Alineación", alignSeg(el.align, function (v) { el.align = v; renderCanvas(); })));
        } else if (el.type === "image") {
            box.appendChild(field("URL de la imagen", textInput(el.src || "", function (v) { el.src = v; renderCanvas(); })));
            box.appendChild(field("Ancho (" + (el.width || 60) + "%)", range(el.width || 60, 20, 100, function (v) { el.width = v; renderCanvas(); })));
            box.appendChild(field("Alineación", alignSeg(el.align || "center", function (v) { el.align = v; renderCanvas(); })));
        } else if (el.type === "form") {
            box.appendChild(field("Texto del botón", textInput(el.submitLabel, function (v) { el.submitLabel = v; renderCanvas(); })));
            var r2 = h('<div class="row2"></div>');
            r2.appendChild(field("Botón", colorInput(el.buttonBg, function (v) { el.buttonBg = v; renderCanvas(); })));
            r2.appendChild(field("Texto botón", colorInput(el.buttonColor, function (v) { el.buttonColor = v; renderCanvas(); })));
            box.appendChild(r2);
            var fh = document.createElement("div"); fh.className = "prop"; fh.innerHTML = "<label>Campos del formulario</label>";
            el.fields.forEach(function (f, fi) {
                var c = h('<div class="fieldcard"><div class="fc-top"><input type="text" value="' + f.label + '" placeholder="Etiqueta"><button class="icon-btn" title="Quitar">✕</button></div>' +
                    '<select><option value="text">Texto</option><option value="email">Email</option><option value="tel">Teléfono</option><option value="checkbox">Casilla (consentimiento)</option></select>' +
                    '<label class="mini"><input type="checkbox" ' + (f.required ? "checked" : "") + '> Obligatorio</label></div>');
                var lab = c.querySelector(".fc-top input");
                var del = c.querySelector(".fc-top button");
                lab.addEventListener("input", function () { f.label = lab.value; renderCanvas(); });
                del.onclick = function () { el.fields.splice(fi, 1); render(); };
                var selT = c.querySelector("select"); selT.value = f.type; selT.onchange = function () { f.type = selT.value; renderCanvas(); };
                c.querySelector(".mini input").onchange = function (e) { f.required = e.target.checked; renderCanvas(); };
                fh.appendChild(c);
            });
            var add = h('<button class="addfield">+ Añadir campo</button>');
            add.onclick = function () { el.fields.push({ label: "Nuevo campo", name: "campo_" + uid(), type: "text", required: false }); render(); };
            fh.appendChild(add); box.appendChild(fh);
        }

        $("#delEl").onclick = function () { s.elements.splice(sel.e, 1); sel = { s: sel.s, e: null }; render(); };
        $("#mvUp").onclick = function () { if (sel.e > 0) { var a = s.elements; var t = a[sel.e - 1]; a[sel.e - 1] = a[sel.e]; a[sel.e] = t; sel.e--; render(); } };
        $("#mvDn").onclick = function () { var a = s.elements; if (sel.e < a.length - 1) { var t = a[sel.e + 1]; a[sel.e + 1] = a[sel.e]; a[sel.e] = t; sel.e++; render(); } };
    }
    function labelOf(el) { return { heading: "Título", text: "Texto", button: "Botón", image: "Imagen", form: "Formulario" }[el.type]; }

    /* ---------------- añadir / plantillas ---------------- */
    function addElement(type) {
        if (type === "section") {
            page.sections.push({ id: uid(), dir: "column", bg: "#ffffff", padY: 44, gap: 16, align: "center", max: 640, elements: [
                { id: uid(), type: "heading", tag: "h2", text: "Nueva sección", size: 26, weight: 700, color: "#0F172A", align: "center" } ] });
            sel = { s: page.sections.length - 1, e: null }; render(); return;
        }
        var si = sel ? sel.s : page.sections.length - 1;
        var s = page.sections[si];
        var defaults = {
            heading: { id: uid(), type: "heading", tag: "h2", text: "Nuevo título", size: 28, weight: 700, color: "#0F172A", align: s.dir === "row" ? "left" : "center" },
            text: { id: uid(), type: "text", text: "Escribe aquí tu texto.", size: 16, color: "#475569", align: s.dir === "row" ? "left" : "center" },
            button: { id: uid(), type: "button", label: "Botón", href: "#", bg: "#3B82F6", color: "#ffffff", radius: 10, size: 16, align: s.dir === "row" ? "left" : "center" },
            image: { id: uid(), type: "image", src: "", width: 70, align: "center" },
            form: { id: uid(), type: "form", align: s.dir === "row" ? "left" : "center", submitLabel: "Enviar", buttonBg: "#3B82F6", buttonColor: "#ffffff",
                fields: [ { label: "Email", name: "email", type: "email", required: true } ] }
        };
        s.elements.push(defaults[type]); sel = { s: si, e: s.elements.length - 1 }; render();
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
    $("#templates").addEventListener("click", function (e) { var b = e.target.closest("[data-tpl]"); if (b) { page = TEMPLATES[b.dataset.tpl](); sel = null; $("#customCss").value = page.customCss || ""; render(); } });
    $("#device").addEventListener("click", function (e) { var b = e.target.closest("[data-dev]"); if (!b) { return; }
        device = b.dataset.dev; $("#device").querySelectorAll("button").forEach(function (x) { x.classList.toggle("on", x === b); }); renderCanvas(); });
    $("#customCss").addEventListener("input", function () { page.customCss = $("#customCss").value; applyCss(); });
    $("#saveBtn").addEventListener("click", save);

    /* ---------------- arranque ---------------- */
    $("#customCss").value = page.customCss || "";
    render();
})();
