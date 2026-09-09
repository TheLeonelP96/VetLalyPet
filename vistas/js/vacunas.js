(function ($) {
    "use strict";

    function tokenCsrf() {
        return $("meta[name='csrf-token']").attr("content") || "";
    }

    function mostrarError(mensaje) {
        Swal.fire({ icon: "error", title: "No se pudo completar", text: mensaje });
    }

    $(document).on("click", ".btnEditVacuna", function () {
        $.ajax({
            url: "ajax/vacunas.ajax.php",
            method: "POST",
            data: {
                IDvacuna: $(this).data("id"),
                csrf_token: tokenCsrf()
            },
            dataType: "json"
        }).done(function (vacuna) {
            $("#IDvacuna").val(vacuna.IDvacuna);
            $("#editarVacuna").val(vacuna.nombreVac);
            $("#editarDescripcionVacuna").val(vacuna.Descripcion);
            $("#editarPrecioVacuna").val(vacuna.preciovac);
            $("#editarStockVacuna").val(vacuna.stock);
            $("#editarEspecieVacuna").val(vacuna.IDespecie);
            $("#modVacuna").modal("show");
        }).fail(function (xhr) {
            mostrarError(xhr.responseJSON && xhr.responseJSON.error
                ? xhr.responseJSON.error
                : "No fue posible cargar la vacuna.");
        });
    });

    $(document).on("click", ".btnEstadoVacuna", function () {
        var idVacuna = $(this).data("id");
        var estado = Number($(this).data("estado"));
        var habilitar = estado === 1;

        Swal.fire({
            icon: "warning",
            title: habilitar ? "¿Habilitar esta vacuna?" : "¿Deshabilitar esta vacuna?",
            text: habilitar
                ? "Volverá a estar disponible sin perder sus datos."
                : "La vacuna dejará de estar disponible, pero se conservará su información.",
            showCancelButton: true,
            confirmButtonColor: habilitar ? "#26b99a" : "#d9534f",
            cancelButtonColor: "#73879c",
            confirmButtonText: habilitar ? "Sí, habilitar" : "Sí, deshabilitar",
            cancelButtonText: "Cancelar"
        }).then(function (resultado) {
            if (!resultado.isConfirmed) {
                return;
            }
            var formulario = $("<form>", { method: "POST", action: "Vacunas" });
            formulario.append($("<input>", { type: "hidden", name: "csrf_token", value: tokenCsrf() }));
            formulario.append($("<input>", { type: "hidden", name: "IDvacuna", value: idVacuna }));
            formulario.append($("<input>", { type: "hidden", name: "estadoVacuna", value: estado }));
            $(document.body).append(formulario);
            formulario.trigger("submit");
        });
    });

    $("#modVacuna").on("hidden.bs.modal", function () {
        var formulario = $(this).find("form")[0];
        if (formulario) {
            formulario.reset();
        }
    });
})(jQuery);
