(function ($) {
    "use strict";

    function tokenCsrf() {
        return $("meta[name='csrf-token']").attr("content") || "";
    }

    function alertaError(mensaje) {
        Swal.fire({ icon: "error", title: "No se pudo completar", text: mensaje });
    }

    function normalizarTurno(turno) {
        return String(turno || "").toLowerCase() === "horario continuo"
            ? "Horario continuo"
            : turno;
    }

    $(document).on("change", ".nuevaFoto", function () {
        var input = this;
        var archivo = input.files && input.files[0];
        if (!archivo) {
            return;
        }
        if (["image/jpeg", "image/png"].indexOf(archivo.type) === -1) {
            input.value = "";
            alertaError("La imagen debe ser JPG o PNG.");
            return;
        }
        if (archivo.size > 2000000) {
            input.value = "";
            alertaError("La imagen no debe superar los 2 MB.");
            return;
        }

        var lector = new FileReader();
        lector.onload = function (evento) {
            $(input).closest(".form-group").find(".previsualizar").attr("src", evento.target.result);
        };
        lector.readAsDataURL(archivo);
    });

    $(document).on("click", ".btnEditUsuario", function () {
        var datos = new FormData();
        datos.append("IDusuario", $(this).data("id"));
        datos.append("csrf_token", tokenCsrf());

        $.ajax({
            url: "ajax/usuarios.ajax.php",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json"
        }).done(function (usuario) {
            $("#IDusuario").val(usuario.IDusuario);
            $("#editarNombre").val(usuario.nombre_U);
            $("#editarApellido").val(usuario.apellido_U);
            $("#editarTelefono").val(usuario.telefono_U);
            $("#editarEmail").val(usuario.email_U);
            $("#editarPassword").val("");
            $("#editarTurno").val(normalizarTurno(usuario.turno_U));
            $("#editarRol").val(usuario.IDrol);
            $("#modUsuario .previsualizar").attr(
                "src",
                usuario.foto || "vistas/js/img/img.jpg"
            );
            $("#modUsuario").modal("show");
        }).fail(function (xhr) {
            var mensaje = xhr.responseJSON && xhr.responseJSON.error
                ? xhr.responseJSON.error
                : "No fue posible cargar el usuario.";
            alertaError(mensaje);
        });
    });

    function enviarEstado(idUsuario, estado) {
        var formulario = $("<form>", { method: "POST", action: "Usuarios" });
        formulario.append($("<input>", { type: "hidden", name: "csrf_token", value: tokenCsrf() }));
        formulario.append($("<input>", { type: "hidden", name: "IDusuario", value: idUsuario }));
        formulario.append($("<input>", { type: "hidden", name: "estadoUsuario", value: estado }));
        $(document.body).append(formulario);
        formulario.trigger("submit");
    }

    $(document).on("click", ".btnEstadoUsuario", function () {
        var idUsuario = $(this).data("id");
        var estado = Number($(this).data("estado"));
        var habilitar = estado === 1;

        Swal.fire({
            icon: "warning",
            title: habilitar ? "¿Habilitar esta cuenta?" : "¿Deshabilitar esta cuenta?",
            text: habilitar
                ? "El usuario podrá volver a iniciar sesión."
                : "El usuario no podrá iniciar sesión hasta que se habilite nuevamente.",
            showCancelButton: true,
            confirmButtonColor: habilitar ? "#26b99a" : "#d9534f",
            cancelButtonColor: "#73879c",
            confirmButtonText: habilitar ? "Sí, habilitar" : "Sí, deshabilitar",
            cancelButtonText: "Cancelar"
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                enviarEstado(idUsuario, estado);
            }
        });
    });

    $("#miModal, #modUsuario").on("hidden.bs.modal", function () {
        var formulario = $(this).find("form")[0];
        if (formulario) {
            formulario.reset();
        }
        $(this).find(".previsualizar").attr("src", "vistas/js/img/img.jpg");
    });
})(jQuery);
