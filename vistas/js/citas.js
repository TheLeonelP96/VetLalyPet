(function ($) {
    "use strict";

    var HORA_INICIO = 7 * 60;
    var ULTIMA_HORA = 18 * 60 + 30;
    var solicitudesMascotas = {};
    var solicitudesDisponibilidad = { crear: null, editar: null };

    function esHoraPermitida(hora) {
        if (!/^\d{2}:\d{2}$/.test(hora || "")) {
            return false;
        }
        var partes = hora.split(":");
        var minutos = Number(partes[0]) * 60 + Number(partes[1]);
        return minutos >= HORA_INICIO && minutos <= ULTIMA_HORA && minutos % 30 === 0;
    }

    function hoyLocal() {
        var hoy = new Date();
        return [
            hoy.getFullYear(),
            String(hoy.getMonth() + 1).padStart(2, "0"),
            String(hoy.getDate()).padStart(2, "0")
        ].join("-");
    }

    function reiniciarMascotas($select, texto) {
        $select.empty().append($("<option>", { value: "", text: texto }));
    }

    function cargarMascotas(idCliente, selector, idSeleccionado) {
        var $select = $(selector);
        var clave = selector.replace(/[^a-z0-9]/gi, "");

        if (solicitudesMascotas[clave]) {
            solicitudesMascotas[clave].abort();
        }
        reiniciarMascotas($select, idCliente ? "Cargando mascotas..." : "Seleccione una mascota");
        if (!idCliente) {
            return;
        }

        solicitudesMascotas[clave] = $.ajax({
            url: "ajax/citas.ajax.php",
            method: "POST",
            dataType: "json",
            data: { accion: "mascotas_cliente", IDcliente: idCliente }
        }).done(function (respuesta) {
            reiniciarMascotas($select, "Seleccione una mascota");
            (respuesta.mascotas || []).forEach(function (mascota) {
                $select.append($("<option>", {
                    value: mascota.IDmascota,
                    text: mascota.nombrem
                }));
            });
            if (idSeleccionado) {
                $select.val(String(idSeleccionado));
            }
        }).fail(function (_xhr, estado) {
            if (estado !== "abort") {
                reiniciarMascotas($select, "No se pudieron cargar las mascotas");
            }
        }).always(function () {
            solicitudesMascotas[clave] = null;
        });
    }

    function controlesDisponibilidad(esEdicion) {
        var reactivando = esEdicion && $("#btnAccionAlterna").val() === "reactivar";
        return {
            fecha: esEdicion ? $("#fechaCitaEditar").val() : $("#fechaCitaCrear").val(),
            hora: esEdicion ? $("#editarHora").val() : $("#seleccionarHora").val(),
            usuario: esEdicion ? $("#editarUsuario").val() : $("#nuevoUsuario").val(),
            mensaje: esEdicion ? $("#disponibilidadEdicion") : $("#disponibilidadNueva"),
            boton: reactivando ? $("#btnAccionAlterna") : (esEdicion ? $("#btnGuardarCambios") : $("#btnGuardarCita"))
        };
    }

    function mostrarDisponibilidad(controles, disponible, texto) {
        controles.mensaje
            .text(texto)
            .toggleClass("text-success", disponible)
            .toggleClass("text-danger", !disponible);
        controles.boton.prop("disabled", !disponible);
    }

    function comprobarDisponibilidad(esEdicion) {
        var modo = esEdicion ? "editar" : "crear";
        var controles = controlesDisponibilidad(esEdicion);

        if (solicitudesDisponibilidad[modo]) {
            solicitudesDisponibilidad[modo].abort();
        }
        if (!controles.fecha || !controles.hora || !controles.usuario) {
            controles.mensaje.text("").removeClass("text-success text-danger");
            controles.boton.prop("disabled", true);
            return;
        }
        if (controles.fecha < hoyLocal()) {
            mostrarDisponibilidad(controles, false, "No puede programar o reactivar citas en días pasados.");
            return;
        }
        if (!esHoraPermitida(controles.hora)) {
            mostrarDisponibilidad(controles, false, "Use bloques de 30 minutos entre 07:00 y 18:30.");
            return;
        }

        var datos = {
            accion: "disponibilidad",
            IDusuario: controles.usuario,
            fechaCita: controles.fecha,
            horaCita: controles.hora
        };
        if (esEdicion) {
            datos.IDcitasExcluir = $("#idCitaEditar").val();
        }

        controles.mensaje.text("Comprobando disponibilidad...").removeClass("text-success text-danger");
        controles.boton.prop("disabled", true);
        solicitudesDisponibilidad[modo] = $.ajax({
            url: "ajax/citas.ajax.php",
            method: "POST",
            dataType: "json",
            data: datos
        }).done(function (respuesta) {
            if (respuesta.horarioValido && respuesta.disponible) {
                mostrarDisponibilidad(controles, true, "Veterinario disponible en este horario.");
            } else {
                mostrarDisponibilidad(
                    controles,
                    false,
                    respuesta.horarioValido
                        ? "El veterinario ya tiene una cita en esta fecha y hora."
                        : "Horario o veterinario no válido."
                );
            }
        }).fail(function (_xhr, estado) {
            if (estado !== "abort") {
                mostrarDisponibilidad(controles, false, "No se pudo comprobar la disponibilidad.");
            }
        }).always(function () {
            solicitudesDisponibilidad[modo] = null;
        });
    }

    function configurarAcciones(estado) {
        var $alterno = $("#btnAccionAlterna");
        var $completar = $("#btnCompletarCita");
        var $guardar = $("#btnGuardarCambios");

        $alterno.removeClass("btn-danger btn-warning").prop("disabled", false);
        if (estado === "Cancelada") {
            $alterno.addClass("btn-warning").html('<i class="fa fa-refresh"></i> Reactivar Cita').val("reactivar").show();
            $completar.hide();
            $guardar.hide();
        } else if (estado === "Completada") {
            $alterno.hide();
            $completar.hide();
            $guardar.hide();
        } else {
            $alterno.addClass("btn-danger").html('<i class="fa fa-ban"></i> Cancelar Cita').val("cancelar").show();
            $completar.show();
            $guardar.show();
        }
    }

    function eventosCalendario() {
        return (window.citasDelServidor || []).map(function (cita) {
            var colores = { Cancelada: "#dd4b39", Completada: "#00a65a" };
            var color = colores[cita.estadoCita] || cita.colorCita || "#3c8dbc";
            return {
                id: cita.IDcitas,
                title: cita.estadoCita === "Completada" ? "[✔] " + cita.motivoCita : cita.motivoCita,
                start: cita.fechaAtencion,
                backgroundColor: color,
                borderColor: color,
                idMascota: cita.IDmascota,
                idCliente: cita.IDcliente,
                idUsuario: cita.IDusuario,
                estadoCita: cita.estadoCita,
                motivoOriginal: cita.motivoCita
            };
        });
    }

    $(function () {
        var calendarEl = document.getElementById("calendar");
        if (!calendarEl) {
            return;
        }

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: "timeGridWeek",
            locale: "es",
            slotMinTime: "07:00:00",
            slotMaxTime: "19:00:00",
            slotDuration: "00:30:00",
            editable: false,
            selectable: true,
            headerToolbar: { right: "timeGridDay,dayGridMonth,timeGridWeek,list" },
            events: eventosCalendario(),
            select: function (info) {
                var partes = info.startStr.split("T");
                var fecha = partes[0];
                var hora = partes[1] ? partes[1].substring(0, 5) : "07:00";
                if (fecha < hoyLocal()) {
                    Swal.fire({ icon: "error", title: "Fecha no permitida", text: "No puede programar citas en días pasados." });
                    calendar.unselect();
                    return;
                }
                if (!esHoraPermitida(hora)) {
                    Swal.fire({ icon: "warning", title: "Horario no permitido", text: "Use bloques de 30 minutos entre 07:00 y 18:30." });
                    calendar.unselect();
                    return;
                }

                $("#fechaCitaCrear").val(fecha);
                $("#seleccionarHora").val(hora);
                $("#modalAgregarCita").modal("show");
                comprobarDisponibilidad(false);
            },
            eventClick: function (info) {
                var props = info.event.extendedProps;
                var partes = info.event.startStr.split("T");
                var hora = (partes[1] || "07:00").substring(0, 5);

                $("#idCitaEditar").val(info.event.id);
                $("#editarMotivo").val(props.motivoOriginal);
                $("#fechaCitaEditar").val(partes[0]);
                $("#editarHora").val(hora);
                $("#editarCliente").val(props.idCliente);
                $("#editarUsuario").val(props.idUsuario);
                configurarAcciones(props.estadoCita);
                cargarMascotas(props.idCliente, "#editarMascotaSelect", props.idMascota);
                $("#modalEditarCita").modal("show");
                comprobarDisponibilidad(true);
            }
        });

        $(document).on("change", "#seleccionarCliente", function () {
            cargarMascotas($(this).val(), "#seleccionarMascota");
        });
        $(document).on("change", "#editarCliente", function () {
            cargarMascotas($(this).val(), "#editarMascotaSelect");
        });
        $(document).on("change input", "#seleccionarHora, #nuevoUsuario", function () {
            comprobarDisponibilidad(false);
        });
        $(document).on("change input", "#fechaCitaEditar, #editarHora, #editarUsuario", function () {
            comprobarDisponibilidad(true);
        });

        $("#btnAccionAlterna").on("click", function (evento) {
            if ($(this).val() === "cancelar"
                && !window.confirm("¿Desea cancelar esta cita? La información se conservará.")) {
                evento.preventDefault();
            }
        });

        $("#formAgregarCita").on("submit", function (evento) {
            if (!esHoraPermitida($("#seleccionarHora").val())) {
                evento.preventDefault();
                Swal.fire({ icon: "error", title: "Hora fuera de atención", text: "Use bloques de 30 minutos entre 07:00 y 18:30." });
            }
        });

        $("#formEditarCita").on("submit", function (evento) {
            var submitter = evento.originalEvent && evento.originalEvent.submitter;
            var accion = submitter ? submitter.value : $(document.activeElement).val();
            if (accion === "cancelar") {
                return;
            }
            if (!esHoraPermitida($("#editarHora").val())) {
                evento.preventDefault();
                Swal.fire({ icon: "error", title: "Hora fuera de atención", text: "Use bloques de 30 minutos entre 07:00 y 18:30." });
            }
        });

        calendar.render();
    });
})(jQuery);
