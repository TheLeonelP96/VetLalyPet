(function ($) {
    "use strict";

    function tokenCsrf() {
        return $("meta[name='csrf-token']").attr("content") || "";
    }

    function mostrarError(mensaje) {
        Swal.fire({ icon: "error", title: "No se pudo completar", text: mensaje });
    }

    $(document).on("click", ".btnEditServicio", function () {
        $.ajax({
            url: "ajax/servicios.ajax.php",
            method: "POST",
            dataType: "json",
            data: { IDservicio: $(this).data("id"), csrf_token: tokenCsrf() }
        }).done(function (servicio) {
            var esVacunacion = servicio.tipoServicio === "Vacunación";
            $("#IDservicio").val(servicio.IDservicio);
            $("#editarServicio").val(servicio.tipoServicio).prop("readonly", esVacunacion);
            $("#editarDescripcionServicio").val(servicio.descripcion);
            $("#editarPrecioServicio").val(servicio.precio).prop("readonly", esVacunacion);
            $("#editarDuracionServicio").val(servicio.duracionMinutos);
            $("#ayudaServicioProtegido, #ayudaPrecioVacunacion").toggle(esVacunacion);
            $("#modServicio").modal("show");
        }).fail(function (xhr) {
            mostrarError(xhr.responseJSON && xhr.responseJSON.error
                ? xhr.responseJSON.error : "No fue posible cargar el servicio.");
        });
    });

    $(document).on("click", ".btnEstadoServicio", function () {
        var idServicio = $(this).data("id");
        var estado = Number($(this).data("estado"));
        var habilitar = estado === 1;
        Swal.fire({
            icon: "warning",
            title: habilitar ? "¿Habilitar este servicio?" : "¿Deshabilitar este servicio?",
            text: habilitar
                ? "Volverá a estar disponible al programar citas."
                : "Ya no podrá elegirse en citas nuevas, pero sus datos anteriores se conservarán.",
            showCancelButton: true,
            confirmButtonColor: habilitar ? "#26b99a" : "#d9534f",
            cancelButtonColor: "#73879c",
            confirmButtonText: habilitar ? "Sí, habilitar" : "Sí, deshabilitar",
            cancelButtonText: "Cancelar"
        }).then(function (resultado) {
            if (!resultado.isConfirmed) {
                return;
            }
            var formulario = $("<form>", { method: "POST", action: "Servicios" });
            formulario.append($("<input>", { type: "hidden", name: "csrf_token", value: tokenCsrf() }));
            formulario.append($("<input>", { type: "hidden", name: "IDservicio", value: idServicio }));
            formulario.append($("<input>", { type: "hidden", name: "estadoServicio", value: estado }));
            $(document.body).append(formulario);
            formulario.trigger("submit");
        });
    });

    $("#modServicio").on("hidden.bs.modal", function () {
        var formulario = $(this).find("form")[0];
        if (formulario) {
            formulario.reset();
        }
        $("#editarServicio, #editarPrecioServicio").prop("readonly", false);
        $("#ayudaServicioProtegido, #ayudaPrecioVacunacion").hide();
    });
})(jQuery);
