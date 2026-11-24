/* Landora — gráficos de analítica (JavaScript propio sobre la librería Chart.js).
   Lee los datos que deja la vista en window.LANDORA_STATS y dibuja:
   - un gráfico de líneas con visitantes únicos y leads por día;
   - un gráfico circular con el reparto móvil / escritorio (si la página lo incluye). */
(function () {
    "use strict";
    var datos = window.LANDORA_STATS;
    if (!datos || !window.Chart) { return; }

    // "2026-11-24" -> "24/11"
    function corta(fecha) { var p = fecha.split("-"); return p[2] + "/" + p[1]; }

    var diario = document.getElementById("graficoDiario");
    if (diario && datos.serie) {
        new Chart(diario, {
            type: "line",
            data: {
                labels: datos.serie.dias.map(corta),
                datasets: [
                    { label: "Visitantes únicos", data: datos.serie.unicos, borderColor: "#3B82F6",
                      backgroundColor: "rgba(59,130,246,.12)", fill: true, tension: 0.3, pointRadius: 2 },
                    { label: "Leads", data: datos.serie.leads, borderColor: "#E11D48",
                      backgroundColor: "#E11D48", tension: 0.3, pointRadius: 3, yAxisID: "y1" }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: "index", intersect: false },   // al pasar el ratón muestra los dos valores del día
                // Dos ejes: los leads son muchos menos que las visitas y en el mismo eje se verían planos.
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: "Visitantes" } },
                    y1: { position: "right", beginAtZero: true, ticks: { precision: 0 },
                          grid: { drawOnChartArea: false }, title: { display: true, text: "Leads" } }
                },
                plugins: { legend: { position: "bottom" } }
            }
        });
    }

    var disp = document.getElementById("graficoDispositivos");
    if (disp && datos.dispositivos) {
        new Chart(disp, {
            type: "doughnut",
            data: {
                labels: ["Móvil", "Escritorio"],
                datasets: [{ data: [datos.dispositivos.mobile, datos.dispositivos.desktop],
                             backgroundColor: ["#3B82F6", "#93C5FD"], borderWidth: 0 }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: "62%",
                       plugins: { legend: { position: "bottom" } } }
        });
    }
})();
