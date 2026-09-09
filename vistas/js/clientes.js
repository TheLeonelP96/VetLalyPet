(function ($) {
    "use strict";

    function tokenCsrf() {
        return $("meta[name='csrf-token']").attr("content") || "";
    }

    function mostrarError(mensaje) {
        Swal.fire({ icon: "error", title: "No se pudo completar", text: mensaje });
    }

    $(document).on("click", ".btnEditCliente", function () {
        $.ajax({
            url: "ajax/clientes.ajax.php",
            method: "POST",
            data: {
                IDcliente: $(this).data("id"),
                csrf_token: tokenCsrf()
            },
            dataType: "json"
        }).done(function (cliente) {
            $("#IDcliente").val(cliente.IDcliente);
            $("#editarCliente").val(cliente.nombrec);
            $("#editarApellidoc").val(cliente.apellidoc);
            $("#editarTelefonoCliente").val(cliente.telefono_c);
            $("#editarRegistro").val(cliente.registro);
            $("#modCliente").modal("show");
        }).fail(function (xhr) {
            mostrarError(xhr.responseJSON && xhr.responseJSON.error
                ? xhr.responseJSON.error
                : "No fue posible cargar el cliente.");
        });
    });

    $(document).on("click", ".btnEstadoCliente", function () {
        var idCliente = $(this).data("id");
        var estado = Number($(this).data("estado"));
        var habilitar = estado === 1;
        Swal.fire({
            icon: "warning",
            title: habilitar ? "¿Habilitar este cliente?" : "¿Deshabilitar este cliente?",
            text: habilitar
                ? "Volverá a estar disponible para nuevas operaciones."
                : "Sus datos y mascotas se conservarán.",
            showCancelButton: true,
            confirmButtonColor: habilitar ? "#26b99a" : "#d9534f",
            cancelButtonColor: "#73879c",
            confirmButtonText: habilitar ? "Sí, habilitar" : "Sí, deshabilitar",
            cancelButtonText: "Cancelar"
        }).then(function (resultado) {
            if (!resultado.isConfirmed) {
                return;
            }
            var formulario = $("<form>", { method: "POST", action: "Clientes" });
            formulario.append($("<input>", { type: "hidden", name: "csrf_token", value: tokenCsrf() }));
            formulario.append($("<input>", { type: "hidden", name: "IDcliente", value: idCliente }));
            formulario.append($("<input>", { type: "hidden", name: "estadoCliente", value: estado }));
            $(document.body).append(formulario);
            formulario.trigger("submit");
        });
    });

    $("#modCliente").on("hidden.bs.modal", function () {
        var formulario = $(this).find("form")[0];
        if (formulario) {
            formulario.reset();
        }
    });
})(jQuery);
