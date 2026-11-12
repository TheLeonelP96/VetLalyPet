(function ($) {
    "use strict";

    var advertenciasSeleccionadas = [];

    function tokenCsrf() {
        return $("meta[name='csrf-token']").attr("content") || "";
    }

    function mostrarError(mensaje) {
        Swal.fire({ icon: "error", title: "No se pudo completar", text: mensaje });
    }

    function textoMascota(cita) {
        return cita.nombrem + " · " + cita.nombre_Especie + " · " + cita.propietario;
    }

    function asegurarCita($select, historial) {
        var valor = String(historial.IDcita);
        if ($select.find('option[value="' + valor + '"]').length === 0) {
            $select.append($("<option>", {
                value: valor,
                text: String(historial.fechaAtencion).slice(0, 16).replace("T", " ")
                    + " · " + historial.nombrem + " · " + historial.propietario
            }));
        }
        $select.val(valor);
    }

    function cargarVacunasMultiples(idCita) {
        var $contenedor = $("#nuevasVacunasHistorial");
        $contenedor.prop("disabled", true).empty().append($('<option disabled>Cargando vacunas...</option>'))
            .trigger("change.select2");
        $("#nuevaMascotaDerivada").text("Cargando datos de la mascota...");
        advertenciasSeleccionadas = [];
        $("#resumenAdvertenciasEsquemaVac").hide();
        return $.ajax({
            url: "ajax/historialVacunas.ajax.php",
            method: "POST",
            dataType: "json",
            data: { accion: "opciones_cita", IDcita: idCita, csrf_token: tokenCsrf() }
        }).done(function (datos) {
            if (!datos.cita) {
                $contenedor.empty().append($('<option disabled>La cita no está disponible para registrar vacunaciones.</option>'))
                    .trigger("change.select2");
                $("#nuevaMascotaDerivada").text("No fue posible obtener la mascota de la cita.");
                return;
            }
            $("#nuevaMascotaDerivada").text("Mascota: " + textoMascota(datos.cita));
            $contenedor.empty();
            if (!datos.vacunas || datos.vacunas.length === 0) {
                $contenedor.append($('<option disabled>No quedan vacunas compatibles disponibles para esta cita.</option>'))
                    .trigger("change.select2");
                return;
            }
            $.each(datos.vacunas, function (_, vacuna) {
                $contenedor.append($("<option>", {
                    value: vacuna.IDvacuna,
                    text: vacuna.nombreVac + " · dosis " + vacuna.numeroDosisSiguiente
                    + (vacuna.esRefuerzo ? " (refuerzo)" : "") + " · stock " + vacuna.stock
                }).data("vacuna", vacuna));
            });
            $contenedor.prop("disabled", false).trigger("change.select2");
        }).fail(function (xhr) {
            $contenedor.empty().append($('<option disabled>No fue posible cargar las vacunas.</option>'))
                .trigger("change.select2");
            $("#nuevaMascotaDerivada").text("No fue posible obtener la mascota de la cita.");
            mostrarError(xhr.responseJSON && xhr.responseJSON.error
                ? xhr.responseJSON.error : "No fue posible cargar las opciones de la cita.");
        });
    }

    function actualizarAdvertencias() {
        advertenciasSeleccionadas = [];
        $("#nuevasVacunasHistorial option:selected").each(function () {
            var vacuna = $(this).data("vacuna") || {};
            if (!vacuna.advertencia) {
                return;
            }
            advertenciasSeleccionadas.push({
                vacuna: vacuna.nombreVac || "Vacuna",
                mensaje: vacuna.advertencia
            });
        });
        var cantidad = advertenciasSeleccionadas.length;
        $("#btnVerAdvertenciasEsquemaVac")
            .text(cantidad === 1 ? "Revisar 1 advertencia" : "Revisar " + cantidad + " advertencias");
        $("#resumenAdvertenciasEsquemaVac").toggle(cantidad > 0);
    }

    $(document).on("click", "#btnVerAdvertenciasEsquemaVac", function () {
        if (advertenciasSeleccionadas.length === 0) {
            return;
        }
        var $lista = $("<div>", { "class": "text-left" });
        advertenciasSeleccionadas.forEach(function (advertencia) {
            var $parrafo = $("<p>");
            $parrafo.append($("<strong>").text(advertencia.vacuna + ": "));
            $parrafo.append(document.createTextNode(advertencia.mensaje));
            $lista.append($parrafo);
        });
        Swal.fire({
            icon: "warning",
            title: "Advertencias del esquema",
            html: $lista[0],
            confirmButtonText: "Entendido"
        });
    });

    function cargarOpciones(idCita, vacunaSelector, mascotaSelector, vacunaSeleccionada, historial) {
        var $vacuna = $(vacunaSelector);
        var $mascota = $(mascotaSelector);
        $vacuna.prop("disabled", true).html('<option value="">Cargando vacunas...</option>');
        $mascota.text("Cargando datos de la mascota...");
        return $.ajax({
            url: "ajax/historialVacunas.ajax.php",
            method: "POST",
            dataType: "json",
            data: { accion: "opciones_cita", IDcita: idCita, csrf_token: tokenCsrf() }
        }).done(function (datos) {
            if (!datos.cita && historial) {
                datos.cita = historial;
            }
            if (!datos.cita) {
                $mascota.text("La cita no está disponible para registrar vacunaciones.");
                return;
            }
            $mascota.text("Mascota: " + textoMascota(datos.cita));
            $vacuna.empty().append('<option value="">Seleccione una vacuna</option>');
            $.each(datos.vacunas || [], function (_, vacuna) {
                $vacuna.append($("<option>", {
                    value: vacuna.IDvacuna,
                    text: vacuna.nombreVac + " · stock " + vacuna.stock + " · Bs " + vacuna.preciovac
                }));
            });
            if (historial && $vacuna.find('option[value="' + historial.IDvacuna + '"]').length === 0) {
                $vacuna.append($("<option>", {
                    value: historial.IDvacuna,
                    text: historial.nombreVac + " · vacuna registrada"
                }));
            }
            $vacuna.prop("disabled", false).val(vacunaSeleccionada || "");
        }).fail(function (xhr) {
            $vacuna.html('<option value="">No fue posible cargar vacunas</option>');
            $mascota.text("No fue posible obtener la mascota de la cita.");
            mostrarError(xhr.responseJSON && xhr.responseJSON.error
                ? xhr.responseJSON.error : "No fue posible cargar las opciones de la cita.");
        });
    }

    $(document).on("change", "#nuevaCitaHistorial", function () {
        var idCita = $(this).val();
        if (!idCita) {
            $("#nuevasVacunasHistorial").prop("disabled", true).empty()
                .append($('<option disabled>Seleccione primero una cita.</option>')).trigger("change.select2");
            $("#nuevaMascotaDerivada").text("Seleccione una cita para obtener la mascota.");
            return;
        }
        cargarVacunasMultiples(idCita);
    });

    $(document).on("change", "#nuevasVacunasHistorial", function () {
        actualizarAdvertencias();
    });

    $(document).on("submit", "#modalHistorialVac form", function (evento) {
        var cita = $("#nuevaCitaHistorial").val();
        var vacunas = $("#nuevasVacunasHistorial").val() || [];
        var fecha = $("#nuevaFechaAplicacion").val();
        if (!cita || vacunas.length === 0 || !fecha) {
            evento.preventDefault();
            Swal.fire({
                icon: "warning",
                title: "Datos incompletos",
                text: "Seleccione una cita, al menos una vacuna y la fecha de aplicación."
            });
        }
    });

    $(document).on("change", "#editarCitaHistorial", function () {
        var idCita = $(this).val();
        if (idCita) {
            cargarOpciones(idCita, "#editarVacunaHistorial", "#editarMascotaDerivada");
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
            asegurarCita($("#editarCitaHistorial"), historial);
            $("#editarFechaAplicacion").val(historial.fechaAplicacion);
            $("#editarFechaProximaDosis").val(historial.fechaProximaDosis || "");
            $("#editarObservacionVacuna").val(historial.observaciones || "");
            cargarOpciones(historial.IDcita, "#editarVacunaHistorial", "#editarMascotaDerivada",
                historial.IDvacuna, historial).done(function () {
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
        if (this.id === "modalHistorialVac") {
            $("#nuevasVacunasHistorial").prop("disabled", true).empty()
                .append($('<option disabled>Seleccione primero una cita.</option>')).trigger("change.select2");
            advertenciasSeleccionadas = [];
            $("#resumenAdvertenciasEsquemaVac").hide();
        }
    });

    $(function () {
        if ($.fn.select2 && $("#nuevasVacunasHistorial").length) {
            $("#nuevasVacunasHistorial").select2({
                width: "100%",
                placeholder: "Seleccione una o varias vacunas",
                closeOnSelect: false,
                language: "es",
                dropdownParent: $("#modalHistorialVac")
            });
        }
        var cita = window.citaVacunacionPreseleccionada;
        if (!cita || $('#nuevaCitaHistorial option[value="' + cita.IDcita + '"]').length === 0) {
            return;
        }
        $("#nuevaCitaHistorial").val(String(cita.IDcita));
        $("#nuevaFechaAplicacion").val(cita.fecha);
        cargarVacunasMultiples(cita.IDcita).done(function () {
            $("#modalHistorialVac").modal("show");
        });
    });
})(jQuery);
