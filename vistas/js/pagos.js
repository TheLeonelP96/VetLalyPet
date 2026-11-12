(function ($) {
    "use strict";

    var detalleNuevo = null;

    function tokenCsrf() {
        return $("meta[name='csrf-token']").attr("content") || "";
    }

    function mostrarError(mensaje) {
        Swal.fire({ icon: "error", title: "No se pudo completar", text: mensaje });
    }


    function mostrarDetalleCobro(detalle) {
        detalleNuevo = detalle;
        $("#nuevoMontoPagoVista").val("Bs " + detalle.montoCalculado);
        $("#nuevoResumenCobro").text(
            detalle.nombrem + " · " + detalle.propietario + " · " + detalle.tipoServicio
        );
        var $conceptos = $("#nuevosConceptosPago").empty();
        $.each(detalle.conceptos || [], function (_, concepto) {
            $conceptos.append($("<p>").text(concepto.concepto + " — Bs " + concepto.importe));
        });
    }

    function cargarDetalleCita(idCita) {
        detalleNuevo = null;
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

    $(document).on("submit", "#modalPago form", function (evento) {
        if (!$("#nuevaCitaPago").val() || !detalleNuevo || detalleNuevo.cobrable !== true) {
            evento.preventDefault();
            Swal.fire({
                icon: "warning",
                title: "Pago no disponible",
                text: "Seleccione una cita cobrable y espere a que se calcule el total."
            });
        }
    });

    $(document).on("click", ".btnEditPago", function () {
        $.ajax({
            url: "ajax/pagos.ajax.php",
            method: "POST",
            dataType: "json",
            data: { accion: "pago", IDpago: $(this).data("id"), csrf_token: tokenCsrf() }
        }).done(function (pago) {
            $("#IDpago").val(pago.IDpago);
            $("#editarMontoPago").val("Bs " + pago.montopago);
            $("#editarMetodoPago").val(pago.metodopago);
            $("#editarUsuarioPago").val(pago.IDusuario);
            $("#editarFechaPago").val(String(pago.fechapago).replace(" ", "T").slice(0, 16));
            $("#editarDetallePago").text(
                pago.nombrem + " · " + pago.propietario + " · " + pago.tipoServicio
            );
            $("#modPago").modal("show");
        }).fail(function (xhr) {
            mostrarError(xhr.responseJSON && xhr.responseJSON.error
                ? xhr.responseJSON.error : "No fue posible cargar el pago.");
        });
    });

    $("#modalPago, #modPago").on("hidden.bs.modal", function () {
        var formulario = $(this).find("form")[0];
        if (formulario) {
            formulario.reset();
        }
        if (this.id === "modalPago") {
            detalleNuevo = null;
            $("#nuevoMontoPagoVista").val("");
            $("#nuevosConceptosPago").empty();
        }
    });

    $(function () {
        var usuarioActual = Number(window.usuarioPagoActual || 0);
        if (usuarioActual && $('#nuevoUsuarioPago option[value="' + usuarioActual + '"]').length) {
            $("#nuevoUsuarioPago").val(String(usuarioActual));
        }
        var cita = window.citaPagoPreseleccionada;
        if (cita && $('#nuevaCitaPago option[value="' + cita + '"]').length) {
            $("#nuevaCitaPago").val(String(cita));
            cargarDetalleCita(cita).done(function () {
                $("#modalPago").modal("show");
            });
        }
    });
})(jQuery);
