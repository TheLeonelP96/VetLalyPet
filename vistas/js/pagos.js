(function ($) {
    "use strict";

    var detalleNuevo = null;
    var pagoEditado = null;

    function tokenCsrf() {
        return $("meta[name='csrf-token']").attr("content") || "";
    }

    function mostrarError(mensaje) {
        Swal.fire({ icon: "error", title: "No se pudo completar", text: mensaje });
    }

    function generarQr(selector, idCita, monto) {
        var elemento = document.querySelector(selector);
        if (!elemento) {
            return;
        }
        elemento.innerHTML = "";
        if (typeof window.QRCode !== "function") {
            elemento.textContent = "No fue posible cargar el generador de QR.";
            return;
        }
        new window.QRCode(elemento, {
            text: "LALYPETS\nCITA:" + idCita + "\nMONTO:" + monto + " BOB",
            width: 180,
            height: 180,
            colorDark: "#000000",
            colorLight: "#ffffff"
        });
    }

    function actualizarMetodo($metodo) {
        var $formulario = $metodo.closest("form");
        var $grupoReferencia = $formulario.find(".referenciaPagoGrupo");
        var $referencia = $grupoReferencia.find(".referenciaPago");
        var $grupoQr = $formulario.find(".qrPagoGrupo");
        var metodo = $metodo.val();
        var efectivo = metodo === "Efectivo";
        var esQr = metodo === "QR";

        $referencia.prop("required", !!metodo && !efectivo);
        $grupoReferencia.toggle(!!metodo && !efectivo);
        $grupoQr.toggle(esQr);
        if (efectivo) {
            $referencia.val("");
        }
        if (esQr) {
            if ($formulario.closest("#modalPago").length && detalleNuevo) {
                generarQr("#qrPagoNuevo", detalleNuevo.IDcita, detalleNuevo.montoCalculado);
            } else if (pagoEditado) {
                generarQr("#qrPagoEditar", pagoEditado.IDcita, pagoEditado.montopago);
            }
        }
    }

    function mostrarDetalleCobro(detalle) {
        detalleNuevo = detalle;
        $("#nuevoMontoPagoVista").val("Bs " + detalle.montoCalculado);
        $("#nuevoResumenCobro").text(
            detalle.nombrem + " · " + detalle.propietario + " · " + detalle.tipoServicio
        );
        var $conceptos = $("#nuevosConceptosPago").empty();
        if (detalle.tipoServicio === "Vacunación") {
            $conceptos.append('<h5>Vacunas aplicadas</h5>');
            $.each(detalle.vacunas || [], function (_, vacuna) {
                $conceptos.append($("<p>").text(vacuna.nombreVac + " — Bs " + vacuna.preciovac));
            });
        } else {
            $conceptos.append($("<p>").text(detalle.tipoServicio + " — Bs " + detalle.precioServicio));
        }
        $("#btnRegistrarPago").prop("disabled", !detalle.cobrable);
        actualizarMetodo($("#modalPago .metodoPago"));
    }

    function cargarDetalleCita(idCita) {
        detalleNuevo = null;
        $("#btnRegistrarPago").prop("disabled", true);
        $("#nuevoMontoPagoVista").val("");
        $("#nuevosConceptosPago").empty();
        if (!idCita) {
            $("#nuevoResumenCobro").text("Seleccione una cita para calcular el cobro.");
            return $.Deferred().reject().promise();
        }
        $("#nuevoResumenCobro").text("Calculando cobro...");
        return $.ajax({
            url: "ajax/pagos.ajax.php",
            method: "POST",
            dataType: "json",
            data: { accion: "detalle_cita", IDcita: idCita, csrf_token: tokenCsrf() }
        }).done(mostrarDetalleCobro).fail(function (xhr) {
            $("#nuevoResumenCobro").text("No fue posible calcular este cobro.");
            mostrarError(xhr.responseJSON && xhr.responseJSON.error
                ? xhr.responseJSON.error : "No fue posible calcular el pago.");
        });
    }

    $(document).on("change", "#nuevaCitaPago", function () {
        cargarDetalleCita($(this).val());
    });

    $(document).on("click", ".btnCobrarCita", function () {
        var idCita = String($(this).data("cita"));
        $("#nuevaCitaPago").val(idCita);
        cargarDetalleCita(idCita).done(function () {
            $("#modalPago").modal("show");
        });
    });

    $(document).on("change", ".metodoPago", function () {
        actualizarMetodo($(this));
    });

    $(document).on("click", ".btnEditPago", function () {
        $.ajax({
            url: "ajax/pagos.ajax.php",
            method: "POST",
            dataType: "json",
            data: { accion: "pago", IDpago: $(this).data("id"), csrf_token: tokenCsrf() }
        }).done(function (pago) {
            pagoEditado = pago;
            $("#IDpago").val(pago.IDpago);
            $("#editarMontoPago").val("Bs " + pago.montopago);
            $("#editarMetodoPago").val(pago.metodopago);
            $("#editarReferenciaPago").val(pago.referenciapago || "");
            $("#editarFechaPago").val(String(pago.fechapago).replace(" ", "T").slice(0, 16));
            $("#editarDetallePago").text(
                pago.nombrem + " · " + pago.propietario + " · " + pago.tipoServicio
            );
            actualizarMetodo($("#editarMetodoPago"));
            $("#modPago").modal("show");
        }).fail(function (xhr) {
            mostrarError(xhr.responseJSON && xhr.responseJSON.error
                ? xhr.responseJSON.error : "No fue posible cargar el pago.");
        });
    });

    $("#modalPago, #modPago").on("shown.bs.modal", function () {
        actualizarMetodo($(this).find(".metodoPago"));
    }).on("hidden.bs.modal", function () {
        var formulario = $(this).find("form")[0];
        if (formulario) {
            formulario.reset();
        }
        if (this.id === "modalPago") {
            detalleNuevo = null;
            $("#nuevoMontoPagoVista").val("");
            $("#nuevosConceptosPago").empty();
            $("#btnRegistrarPago").prop("disabled", true);
        }
    });

    $(function () {
        var cita = window.citaPagoPreseleccionada;
        if (cita && $('#nuevaCitaPago option[value="' + cita + '"]').length) {
            $("#nuevaCitaPago").val(String(cita));
            cargarDetalleCita(cita).done(function () {
                $("#modalPago").modal("show");
            });
        }
    });
})(jQuery);
