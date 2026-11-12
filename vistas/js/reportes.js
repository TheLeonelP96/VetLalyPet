(function () {
    "use strict";
    if (window.jQuery && $.fn.DataTable) {
        $("#contenedorReportes .tabla-reporte").each(function () {
            var titulo = $(this).closest(".x_panel").find("h2").first().text().trim();
            $(this).DataTable({
                responsive: false,
                scrollX: true,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
                dom: "Bfrtip",
                buttons: [{
                    extend: "print",
                    text: '<i class="fa fa-print"></i> Imprimir tabla',
                    title: titulo,
                    className: "btn btn-default btn-sm",
                    action: function (evento, tabla, boton, configuracion) {
                        var restaurar = function () {
                            document.body.classList.remove("reporte-en-impresion");
                            window.removeEventListener("focus", restaurar);
                        };
                        document.body.classList.add("reporte-en-impresion");
                        window.addEventListener("focus", restaurar);
                        $.fn.dataTable.ext.buttons.print.action.call(this, evento, tabla, boton, configuracion);
                    },
                    customize: function (ventana) {
                        $(ventana.document.body).css({
                            background: "#fff",
                            color: "#222",
                            margin: "20px",
                            fontFamily: "Arial, sans-serif",
                            fontSize: "10pt"
                        });
                        $(ventana.document.body).find("h1").css({
                            color: "#222",
                            fontSize: "16pt",
                            marginBottom: "14px"
                        });
                        $(ventana.document.body).find("table").css({
                            width: "100%",
                            borderCollapse: "collapse",
                            fontSize: "9pt"
                        });
                        $(ventana.document.body).find("th").css({
                            backgroundColor: "#f2f2f2",
                            color: "#222",
                            border: "1px solid #999",
                            padding: "6px",
                            textAlign: "left"
                        });
                        $(ventana.document.body).find("td").css({
                            backgroundColor: "#fff",
                            color: "#222",
                            border: "1px solid #bbb",
                            padding: "5px"
                        });
                    }
                }],
                language: {
                    search: "Buscar:",
                    lengthMenu: "Mostrar _MENU_ registros",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    infoEmpty: "Sin registros para mostrar",
                    zeroRecords: "No se encontraron coincidencias",
                    emptyTable: "No existen registros para este periodo",
                    paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
                }
            });
        });
    }
    document.addEventListener("click", function (evento) {
        var botonTabla = evento.target.closest(".btnImprimirTablaReporte");
        if (botonTabla) {
            var reporte = botonTabla.closest(".reporte-tabla");
            if (!reporte) {
                return;
            }
            var impresionAnterior = document.getElementById("impresionTablaReporte");
            if (impresionAnterior) {
                impresionAnterior.remove();
            }
            var impresion = document.createElement("div");
            impresion.id = "impresionTablaReporte";
            impresion.appendChild(reporte.cloneNode(true));
            var periodo = document.querySelector("#contenedorReportes .filtros-reporte + p");
            if (periodo) {
                impresion.querySelector(".x_content").insertBefore(
                    periodo.cloneNode(true),
                    impresion.querySelector(".x_content").firstChild
                );
            }
            document.body.appendChild(impresion);
            document.body.classList.add("imprimiendo-tabla-reporte");
            window.print();
            return;
        }
        var boton = evento.target.closest(".btnImprimirReporte, .btnImprimirComprobante");
        if (boton) {
            window.print();
        }
    });
    window.addEventListener("afterprint", function () {
        document.body.classList.remove("imprimiendo-tabla-reporte");
        var impresion = document.getElementById("impresionTablaReporte");
        if (impresion) {
            impresion.remove();
        }
    });
})();
