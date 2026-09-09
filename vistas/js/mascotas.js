(function ($) {
    "use strict";

    function tokenCsrf() {
        return $("meta[name='csrf-token']").attr("content") || "";
    }

    function mostrarError(mensaje) {
        Swal.fire({ icon: "error", title: "No se pudo completar", text: mensaje });
    }

    function filtrarRazas(selectorEspecie, selectorRaza, razaSeleccionada) {
        var idEspecie = String($(selectorEspecie).val() || "");
        var $raza = $(selectorRaza);
        $raza.find("option[data-especie]").each(function () {
            var visible = String($(this).data("especie")) === idEspecie;
            $(this).prop("disabled", !visible).toggle(visible);
        });
        $raza.val(razaSeleccionada ? String(razaSeleccionada) : "");
    }

    $(document).on("change", "#nuevaEspecie", function () {
        filtrarRazas("#nuevaEspecie", "#nuevaRaza");
    });

    $(document).on("change", "#editarEspecie", function () {
        filtrarRazas("#editarEspecie", "#editarRaza");
    });

    $(document).on("click", ".btnEditMascota", function () {
        $.ajax({
            url: "ajax/mascotas.ajax.php",
            method: "POST",
            data: {
                IDmascota: $(this).data("id"),
                csrf_token: tokenCsrf()
            },
            dataType: "json"
        }).done(function (mascota) {
            $("#IDmascota").val(mascota.IDmascota);
            $("#editarMascota").val(mascota.nombrem);
            $("#editarFechaRegistro").val(mascota.fecha_Registro);
            $("#editarFechaNacimiento").val(mascota.fechaNac_m);
            $("#editarPeso").val(mascota.peso);
            $("#editarSexo").val(mascota.sexo);
            $("#editarDescripcion").val(mascota.descripcion || "");
            $("#editarColor").val(mascota.color || "");
            $("#editarCliente").val(mascota.IDcliente);
            $("#editarEspecie").val(mascota.IDespecie);
            filtrarRazas("#editarEspecie", "#editarRaza", mascota.IDraza);
            $("#modMascota").modal("show");
        }).fail(function (xhr) {
            mostrarError(xhr.responseJSON && xhr.responseJSON.error
                ? xhr.responseJSON.error
                : "No fue posible cargar la mascota.");
        });
    });

    $(document).on("click", ".btnEstadoMascota", function () {
        var idMascota = $(this).data("id");
        var estado = Number($(this).data("estado"));
        var habilitar = estado === 1;
        Swal.fire({
            icon: "warning",
            title: habilitar ? "¿Habilitar esta mascota?" : "¿Deshabilitar esta mascota?",
            text: habilitar
                ? "Volverá a estar disponible para nuevas operaciones."
                : "Su historial y sus datos se conservarán.",
            showCancelButton: true,
            confirmButtonColor: habilitar ? "#26b99a" : "#d9534f",
            cancelButtonColor: "#73879c",
            confirmButtonText: habilitar ? "Sí, habilitar" : "Sí, deshabilitar",
            cancelButtonText: "Cancelar"
        }).then(function (resultado) {
            if (!resultado.isConfirmed) {
                return;
            }
            var formulario = $("<form>", { method: "POST", action: "Mascotas" });
            formulario.append($("<input>", { type: "hidden", name: "csrf_token", value: tokenCsrf() }));
            formulario.append($("<input>", { type: "hidden", name: "IDmascota", value: idMascota }));
            formulario.append($("<input>", { type: "hidden", name: "estadoMascota", value: estado }));
            $(document.body).append(formulario);
            formulario.trigger("submit");
        });
    });

    $("#modalMascota, #modMascota").on("hidden.bs.modal", function () {
        var formulario = $(this).find("form")[0];
        if (formulario) {
            formulario.reset();
        }
        var prefijo = this.id === "modalMascota" ? "nueva" : "editar";
        filtrarRazas("#" + prefijo + "Especie", "#" + prefijo + "Raza");
    });

    filtrarRazas("#nuevaEspecie", "#nuevaRaza");
    filtrarRazas("#editarEspecie", "#editarRaza");
})(jQuery);
