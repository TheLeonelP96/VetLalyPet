(function ($) {
    "use strict";

    function tokenCsrf() {
        return $("meta[name='csrf-token']").attr("content") || "";
    }

    function mostrarError(mensaje) {
        Swal.fire({ icon: "error", title: "No se pudo completar", text: mensaje });
    }

    function fechaLegible(fecha) {
        var partes = String(fecha || "").split(" ");
        return partes[0] + (partes[1] ? " " + partes[1].slice(0, 5) : "");
    }

    function cargarOpciones(idMascota, vacunaSelector, citaSelector, seleccion, historial) {
        var $vacuna = $(vacunaSelector);
        var $cita = $(citaSelector);
        $vacuna.prop("disabled", true).html('<option value="">Cargando vacunas...</option>');
        $cita.prop("disabled", true).html('<option value="">Cargando citas...</option>');

        return $.ajax({
            url: "ajax/historialVacunas.ajax.php",
            method: "POST",
            dataType: "json",
            data: { accion: "opciones_mascota", IDmascota: idMascota, csrf_token: tokenCsrf() }
        }).done(function (datos) {
            $vacuna.empty().append('<option value="">Seleccione una vacuna</option>');
            $.each(datos.vacunas || [], function (_, vacuna) {
                $vacuna.append($("<option>", {
                    value: vacuna.IDvacuna,
                    text: vacuna.nombreVac + " · stock " + vacuna.stock + " · Bs " + vacuna.preciovac
                }));
            });
            if (historial && $vacuna.find('option[value="' + historial.IDvacuna + '"]').length === 0) {
                $vacuna.append($("<option>", { value: historial.IDvacuna, text: historial.nombreVac + " · vacuna registrada" }));
            }
            $vacuna.prop("disabled", false).val(seleccion && seleccion.vacuna ? seleccion.vacuna : "");

            $cita.empty().append('<option value="">Sin cita relacionada</option>');
            $.each(datos.citas || [], function (_, cita) {
                $cita.append($("<option>", {
                    value: cita.IDcita,
                    text: fechaLegible(cita.fechaAtencion) + " · " + cita.veterinario + " · " + cita.estadoCita
                }));
            });
            $cita.prop("disabled", false).val(seleccion && seleccion.cita ? seleccion.cita : "");
        }).fail(function (xhr) {
            $vacuna.html('<option value="">No fue posible cargar vacunas</option>');
            $cita.html('<option value="">Sin cita relacionada</option>');
            mostrarError(xhr.responseJSON && xhr.responseJSON.error
                ? xhr.responseJSON.error : "No fue posible cargar las opciones de la mascota.");
        });
    }

    $(document).on("change", "#nuevaMascotaHistorial", function () {
        var id = $(this).val();
        if (!id) {
            $("#nuevaVacunaHistorial").prop("disabled", true).html('<option value="">Seleccione primero una mascota</option>');
            $("#nuevaCitaHistorial").prop("disabled", true).html('<option value="">Sin cita relacionada</option>');
            return;
        }
        cargarOpciones(id, "#nuevaVacunaHistorial", "#nuevaCitaHistorial");
    });

    $(document).on("change", "#editarMascotaHistorial", function () {
        var id = $(this).val();
        if (id) {
            cargarOpciones(id, "#editarVacunaHistorial", "#editarCitaHistorial");
        }
    });

    $(document).on("click", ".btnEditHistorialVac", function () {
        $.ajax({
            url: "ajax/historialVacunas.ajax.php",
            method: "POST",
            dataType: "json",
            data: { accion: "historial", IDhistorialVac: $(this).data("id"), csrf_token: tokenCsrf() }
        }).done(function (historial) {
            $("#IDhistorialVac").val(historial.IDhistorialVac);
            $("#editarMascotaHistorial").val(historial.IDmascota);
            $("#editarFechaAplicacion").val(historial.fechaAplicacion);
            $("#editarFechaProximaDosis").val(historial.fechaProximaDosis || "");
            $("#editarObservacionVacuna").val(historial.observaciones || "");
            cargarOpciones(
                historial.IDmascota,
                "#editarVacunaHistorial",
                "#editarCitaHistorial",
                { vacuna: historial.IDvacuna, cita: historial.IDcita || "" },
                historial
            ).done(function () {
                $("#modHistorialVac").modal("show");
            });
        }).fail(function (xhr) {
            mostrarError(xhr.responseJSON && xhr.responseJSON.error
                ? xhr.responseJSON.error : "No fue posible cargar el historial.");
        });
    });

    $("#modalHistorialVac, #modHistorialVac").on("hidden.bs.modal", function () {
        var formulario = $(this).find("form")[0];
        if (formulario) {
            formulario.reset();
        }
    });

    $(function () {
        var cita = window.citaVacunacionPreseleccionada;
        if (!cita || $('#nuevaMascotaHistorial option[value="' + cita.IDmascota + '"]').length === 0) {
            return;
        }
        $("#nuevaMascotaHistorial").val(String(cita.IDmascota));
        $("#nuevaFechaAplicacion").val(cita.fecha);
        cargarOpciones(
            cita.IDmascota,
            "#nuevaVacunaHistorial",
            "#nuevaCitaHistorial",
            { cita: cita.IDcita }
        ).done(function () {
            $("#modalHistorialVac").modal("show");
        });
    });
})(jQuery);
