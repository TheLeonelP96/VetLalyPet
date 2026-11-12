(function ($) {
    "use strict";

    function tokenCsrf() {
        return $("meta[name='csrf-token']").attr("content") || "";
    }

    function filtrarVacunas($especie, $vacuna, valorSeleccionado) {
        var especie = String($especie.val() || "");
        $vacuna.find("option").each(function () {
            var $opcion = $(this);
            $opcion.toggle(!$opcion.val() || String($opcion.data("especie")) === especie);
        });
        $vacuna.prop("disabled", !especie).val(valorSeleccionado || "");
        if (valorSeleccionado && $vacuna.val() === null) {
            $vacuna.val("");
        }
    }

    $(document).on("change", ".especieEsquema", function () {
        var prefijo = this.id.indexOf("editar") === 0 ? "editar" : "nuevo";
        filtrarVacunas($(this), $("#" + prefijo + "VacunaEsquema"), "");
    });

    $(document).on("click", ".btnEditarEsquema", function () {
        $.ajax({
            url: "ajax/seguimientoVacunas.ajax.php", method: "POST", dataType: "json",
            data: { IDesquemaVac: $(this).data("id"), csrf_token: tokenCsrf() }
        }).done(function (esquema) {
            $("#editarIDesquemaVac").val(esquema.IDesquemaVac);
            $("#editarEspecieEsquema").val(esquema.IDespecie);
            filtrarVacunas($("#editarEspecieEsquema"), $("#editarVacunaEsquema"), String(esquema.IDvacuna));
            $("#editarNumeroDosis").val(esquema.numeroDosis);
            $("#editarEdadAplicacion").val(esquema.edadAplicacion);
            $("#editarUnidadEdad").val(esquema.unidadEdad);
            $("#editarIntervalo").val(esquema.intervaloSiguienteDias || "");
            var utilizado = Number(esquema.utilizado) === 1;
            $("#editarEspecieEsquema, #editarVacunaEsquema, #editarNumeroDosis").prop("disabled", utilizado);
            $("#avisoEsquemaUtilizado").toggle(utilizado);
            if (utilizado) {
                $("#modalEditarEsquema form").append(
                    $("<input>", {type: "hidden", class: "claveEsquemaTemporal", name: "IDvacunaEsquema", value: esquema.IDvacuna}),
                    $("<input>", {type: "hidden", class: "claveEsquemaTemporal", name: "numeroDosis", value: esquema.numeroDosis})
                );
            }
            $("#modalEditarEsquema").modal("show");
        }).fail(function (xhr) {
            Swal.fire({icon: "error", title: "No se pudo cargar", text: xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : "No fue posible cargar el esquema."});
        });
    });

    $(document).on("click", ".btnEliminarEsquema", function () {
        var id = $(this).data("id");
        if (Number($(this).data("utilizado")) === 1) {
            Swal.fire({icon: "info", title: "Dosis protegida", text: "No se puede eliminar porque ya figura en un historial de vacunación."});
            return;
        }
        Swal.fire({icon: "warning", title: "¿Eliminar esta dosis?", text: $(this).data("vacuna"), showCancelButton: true,
            confirmButtonColor: "#d9534f", cancelButtonColor: "#73879c", confirmButtonText: "Sí, eliminar", cancelButtonText: "Cancelar"
        }).then(function (resultado) {
            if (!resultado.isConfirmed) { return; }
            var formulario = $("<form>", {method: "POST", action: "SeguimientoVac"});
            formulario.append($("<input>", {type: "hidden", name: "csrf_token", value: tokenCsrf()}));
            formulario.append($("<input>", {type: "hidden", name: "accionEsquema", value: "eliminar"}));
            formulario.append($("<input>", {type: "hidden", name: "IDesquemaVac", value: id}));
            $(document.body).append(formulario); formulario.trigger("submit");
        });
    });

    $("#modalEditarEsquema").on("hidden.bs.modal", function () {
        $(this).find(".claveEsquemaTemporal").remove();
        $("#editarEspecieEsquema, #editarVacunaEsquema, #editarNumeroDosis").prop("disabled", false);
        $("#avisoEsquemaUtilizado").hide();
        var formulario = $(this).find("form")[0]; if (formulario) { formulario.reset(); }
    });

    function filtrarSeguimiento() {
        var cliente = $("#filtroPropietarioSeguimiento").val();
        var mascota = $("#filtroMascotaSeguimiento").val();
        var visibles = 0;
        $(".mascota-seguimiento").each(function () {
            var $panel = $(this);
            var mostrar = (!cliente || $panel.data("cliente") === Number(cliente))
                && (!mascota || $panel.data("mascota") === Number(mascota));
            $panel.toggle(mostrar);
            if (mostrar) {
                visibles++;
            }
        });
        $("#sinResultadosSeguimiento").toggle(visibles === 0);
    }

    $(document).on("change", "#filtroPropietarioSeguimiento", function () {
        var cliente = $(this).val();
        var $mascotas = $("#filtroMascotaSeguimiento option");
        $mascotas.each(function () {
            var $opcion = $(this);
            $opcion.toggle(!$opcion.val() || !cliente || String($opcion.data("cliente")) === cliente);
        });
        if ($("#filtroMascotaSeguimiento option:selected").is(":hidden")) {
            $("#filtroMascotaSeguimiento").val("");
        }
        filtrarSeguimiento();
    });

    $(document).on("change", "#filtroMascotaSeguimiento", filtrarSeguimiento);

    $(document).on("click", ".btnImprimirSeguimiento", function () {
        $(".mascota-seguimiento").removeClass("imprimir-seguimiento");
        $(this).closest(".mascota-seguimiento").addClass("imprimir-seguimiento");
        $(document.body).addClass("imprimiendo-seguimiento");
        window.print();
    });

    $(window).on("afterprint", function () {
        $(document.body).removeClass("imprimiendo-seguimiento");
        $(".mascota-seguimiento").removeClass("imprimir-seguimiento");
    });
})(jQuery);
