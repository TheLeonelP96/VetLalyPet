(function ($) {
    "use strict";

    var HORA_INICIO = 7 * 60;
    var ULTIMA_HORA = 18 * 60 + 30;
    var solicitudesDisponibilidad = { crear: null, editar: null };

    function tokenCsrf() {
        return (window.configuracionCitas && window.configuracionCitas.csrf)
            || $("meta[name='csrf-token']").attr("content")
            || "";
    }

    function hoyLocal() {
        var hoy = new Date();
        return [
            hoy.getFullYear(),
            String(hoy.getMonth() + 1).padStart(2, "0"),
            String(hoy.getDate()).padStart(2, "0")
        ].join("-");
    }

    function esHoraPermitida(hora) {
        if (!/^\d{2}:\d{2}$/.test(hora || "")) {
            return false;
        }
        var partes = hora.split(":");
        var minutos = Number(partes[0]) * 60 + Number(partes[1]);
        return minutos >= HORA_INICIO && minutos <= ULTIMA_HORA && minutos % 30 === 0;
    }

    function asegurarOpcion($select, valor, texto) {
        if (valor === null || valor === undefined || valor === "") {
            return;
        }
        if ($select.find('option[value="' + String(valor) + '"]').length === 0) {
            $select.append($("<option>", {
                value: valor,
                text: texto + " (no disponible para nuevas citas)"
            }));
        }
        $select.val(String(valor));
    }

    function reiniciarMascotas($select, texto) {
        $select.empty()
            .append($("<option>", { value: "", text: texto }))
            .prop("disabled", true);
    }

    function cargarMascotas(idCliente, selector, seleccion) {
        var $select = $(selector);
        var $mensaje = selector === "#seleccionarMascota"
            ? $("#mensajeMascotasCrear")
            : $("#mensajeMascotasEditar");
        reiniciarMascotas($select, idCliente ? "Seleccionar mascota" : "Seleccione primero un cliente");
        $mensaje.text("").removeClass("text-danger text-success");
        if (!idCliente) {
            return;
        }
        var mascotas = (window.mascotasParaCitas || []).filter(function (mascota) {
            return String(mascota.IDcliente) === String(idCliente);
        });
        mascotas.forEach(function (mascota) {
            $select.append($("<option>", {
                value: mascota.IDmascota,
                text: mascota.nombrem
            }));
        });
        if (seleccion && seleccion.id) {
            asegurarOpcion($select, seleccion.id, seleccion.nombre || "Mascota");
        }
        var tieneMascotas = $select.find("option").filter(function () {
            return this.value !== "";
        }).length > 0;
        $select.prop("disabled", !tieneMascotas);
        $mensaje
            .text(tieneMascotas ? "Mascotas del cliente cargadas." : "Este cliente no tiene mascotas habilitadas.")
            .toggleClass("text-success", tieneMascotas)
            .toggleClass("text-danger", !tieneMascotas);
    }

    function controlesDisponibilidad(esEdicion) {
        var reactivando = esEdicion && $("#btnAccionAlterna").val() === "reactivar";
        var fechaNueva = $("#fechaCitaCrear").val() || $("#fechaCitaCrearTexto").text().trim();
        return {
            fecha: esEdicion ? $("#fechaCitaEditar").val() : fechaNueva,
            hora: esEdicion ? $("#editarHora").val() : $("#seleccionarHora").val(),
            usuario: esEdicion ? $("#editarUsuario").val() : $("#nuevoUsuario").val(),
            mensaje: esEdicion ? $("#disponibilidadEdicion") : $("#disponibilidadNueva"),
            boton: reactivando
                ? $("#btnAccionAlterna")
                : (esEdicion ? $("#btnGuardarCambios") : $("#btnGuardarCita"))
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
        if (controles.fecha < hoyLocal() || !esHoraPermitida(controles.hora)) {
            mostrarDisponibilidad(
                controles,
                false,
                "Seleccione una fecha futura y un bloque de 30 minutos entre 07:00 y 18:30."
            );
            return;
        }

        var datos = {
            accion: "disponibilidad",
            IDusuario: controles.usuario,
            fechaCita: controles.fecha,
            horaCita: controles.hora,
            csrf_token: tokenCsrf()
        };
        if (esEdicion) {
            datos.IDcitaExcluir = $("#idCitaEditar").val();
        }

        controles.mensaje.text("Comprobando disponibilidad...").removeClass("text-success text-danger");
        controles.boton.prop("disabled", true);
        solicitudesDisponibilidad[modo] = $.ajax({
            url: "ajax/citas.ajax.php",
            method: "POST",
            dataType: "json",
            data: datos
        }).done(function (respuesta) {
            mostrarDisponibilidad(controles, Boolean(respuesta.disponible), respuesta.mensaje);
        }).fail(function (_xhr, estado) {
            if (estado !== "abort") {
                mostrarDisponibilidad(controles, false, "No se pudo comprobar la disponibilidad.");
            }
        }).always(function () {
            solicitudesDisponibilidad[modo] = null;
        });
    }

    function configurarAcciones(estado, props, idCita) {
        var $alterno = $("#btnAccionAlterna");
        var $completar = $("#btnCompletarCita");
        var $guardar = $("#btnGuardarCambios");
        var $vacunacion = $("#btnRegistrarVacunacionCita");
        var $pago = $("#btnRegistrarPagoCita");
        $vacunacion.hide();
        $pago.hide();
        $completar.html('<i class="fa fa-check"></i> Completar');
        $alterno.removeClass("btn-danger btn-warning").prop("disabled", false);
        $("#estadoCitaTexto").text(estado);

        if (estado === "Cancelada") {
            $alterno.addClass("btn-warning")
                .html('<i class="fa fa-refresh"></i> Reactivar')
                .val("reactivar").show();
            $completar.hide();
            $guardar.hide();
        } else if (estado === "Completada") {
            $alterno.hide();
            $completar.hide();
            $guardar.hide();
            if (props.nombreServicio === "Vacunación") {
                $vacunacion.attr("href", "HistorialVac?cita=" + idCita).show();
            }
            if (props.nombreServicio !== "Vacunación" || props.vacunasAplicadas > 0) {
                $pago.attr("href", "Pagos?cita=" + idCita)
                    .html(props.pagosRegistrados > 0
                        ? '<i class="fa fa-eye"></i> Ver pago'
                        : '<i class="fa fa-money"></i> Registrar pago')
                    .show();
            }
        } else {
            $alterno.addClass("btn-danger")
                .html('<i class="fa fa-ban"></i> Cancelar')
                .val("cancelar").show();
            $completar.html(props.nombreServicio === "Vacunación"
                ? '<i class="fa fa-check"></i> Completar y registrar vacunación'
                : '<i class="fa fa-check"></i> Completar y cobrar').show();
            $guardar.show();
        }
    }

    function eventosCalendario() {
        return (window.citasDelServidor || []).map(function (cita) {
            var colores = {
                Pendiente: "#3c8dbc",
                Cancelada: "#dd4b39",
                Completada: "#00a65a"
            };
            var prefijo = cita.estadoCita === "Cancelada"
                ? "[Cancelada] "
                : (cita.estadoCita === "Completada" ? "[Completada] " : "");
            return {
                id: cita.IDcita,
                title: prefijo + cita.nombrem + " — " + cita.tipoServicio,
                start: cita.fechaAtencionIso,
                backgroundColor: colores[cita.estadoCita] || "#3c8dbc",
                borderColor: colores[cita.estadoCita] || "#3c8dbc",
                extendedProps: {
                    idMascota: cita.IDmascota,
                    nombreMascota: cita.nombrem,
                    idCliente: cita.IDcliente,
                    nombreCliente: cita.nombrec + " " + cita.apellidoc,
                    idUsuario: cita.IDusuario,
                    nombreUsuario: cita.nombreUsuario,
                    idServicio: cita.IDservicio,
                    nombreServicio: cita.tipoServicio,
                    estadoCita: cita.estadoCita,
                    motivo: cita.motivoCita,
                    vacunasAplicadas: Number(cita.vacunasAplicadas || 0),
                    pagosRegistrados: Number(cita.pagosRegistrados || 0)
                }
            };
        });
    }

    $(function () {
        var elemento = document.getElementById("calendar");
        if (!elemento) {
            return;
        }
        // La asociación cliente-mascota funciona aunque el calendario visual falle al iniciar.
        $(document).on("change", "#seleccionarCliente", function () {
            cargarMascotas($(this).val(), "#seleccionarMascota");
        });
        $(document).on("change", "#editarCliente", function () {
            cargarMascotas($(this).val(), "#editarMascotaSelect");
        });

        var calendario = new FullCalendar.Calendar(elemento, {
            initialView: "timeGridWeek",
            locale: "es",
            firstDay: 1,
            nowIndicator: true,
            selectable: true,
            selectMirror: true,
            editable: false,
            slotMinTime: "07:00:00",
            slotMaxTime: "19:00:00",
            slotDuration: "00:30:00",
            snapDuration: "00:30:00",
            defaultTimedEventDuration: "00:30:00",
            businessHours: { startTime: "07:00", endTime: "19:00", daysOfWeek: [0, 1, 2, 3, 4, 5, 6] },
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "timeGridDay,timeGridWeek,dayGridMonth,listWeek"
            },
            buttonText: { today: "Hoy", day: "Día", week: "Semana", month: "Mes", list: "Lista" },
            events: eventosCalendario(),
            select: function (info) {
                if (!(window.configuracionCitas && window.configuracionCitas.puedeCrear)) {
                    Swal.fire({
                        icon: "warning",
                        title: "No se puede programar",
                        text: "Verifique que existan cliente, mascota, veterinario y servicio habilitados."
                    });
                    calendario.unselect();
                    return;
                }
                var partes = info.startStr.split("T");
                var fecha = partes[0];
                var hora = partes[1] ? partes[1].substring(0, 5) : "07:00";
                if (fecha < hoyLocal() || !esHoraPermitida(hora)) {
                    Swal.fire({
                        icon: "warning",
                        title: "Horario no permitido",
                        text: "Las citas comienzan cada 30 minutos entre 07:00 y 18:30."
                    });
                    calendario.unselect();
                    return;
                }
                $("#fechaCitaCrear").val(fecha);
                $("#fechaCitaCrearTexto").text(fecha);
                $("#seleccionarHora").val(hora);
                $("#modalAgregarCita").modal("show");
                comprobarDisponibilidad(false);
            },
            eventClick: function (info) {
                var props = info.event.extendedProps;
                var partes = info.event.startStr.split("T");
                $("#idCitaEditar").val(info.event.id);
                $("#editarMotivo").val(props.motivo);
                $("#fechaCitaEditar").val(partes[0]);
                $("#editarHora").val((partes[1] || "07:00").substring(0, 5));
                asegurarOpcion($("#editarCliente"), props.idCliente, props.nombreCliente);
                asegurarOpcion($("#editarUsuario"), props.idUsuario, props.nombreUsuario);
                asegurarOpcion($("#editarServicio"), props.idServicio, props.nombreServicio);
                configurarAcciones(props.estadoCita, props, info.event.id);
                cargarMascotas(props.idCliente, "#editarMascotaSelect", {
                    id: props.idMascota,
                    nombre: props.nombreMascota
                });
                $("#modalEditarCita").modal("show");
                if (props.estadoCita !== "Completada") {
                    comprobarDisponibilidad(true);
                } else {
                    $("#disponibilidadEdicion").text("").removeClass("text-success text-danger");
                }
            }
        });

        $(document).on("change input", "#seleccionarHora, #nuevoUsuario", function () {
            comprobarDisponibilidad(false);
        });
        $(document).on("change input", "#fechaCitaEditar, #editarHora, #editarUsuario", function () {
            comprobarDisponibilidad(true);
        });

        $("#formAgregarCita").on("submit", function (evento) {
            if (!$("#fechaCitaCrear").val()) {
                $("#fechaCitaCrear").val($("#fechaCitaCrearTexto").text().trim());
            }
            if (!esHoraPermitida($("#seleccionarHora").val())) {
                evento.preventDefault();
                Swal.fire({
                    icon: "error",
                    title: "Hora fuera de atención",
                    text: "Use bloques de 30 minutos entre 07:00 y 18:30."
                });
            }
        });

        $("#formEditarCita").on("submit", function (evento) {
            var original = evento.originalEvent;
            var boton = original && original.submitter ? original.submitter : document.activeElement;
            var accion = $(boton).val();
            if (accion === "cancelar"
                && !window.confirm("¿Desea cancelar esta cita? La información se conservará.")) {
                evento.preventDefault();
                return;
            }
            if (accion !== "cancelar" && accion !== "completar"
                && !esHoraPermitida($("#editarHora").val())) {
                evento.preventDefault();
                Swal.fire({
                    icon: "error",
                    title: "Hora fuera de atención",
                    text: "Use bloques de 30 minutos entre 07:00 y 18:30."
                });
            }
        });

        $("#modalAgregarCita").on("hidden.bs.modal", function () {
            $("#formAgregarCita")[0].reset();
            $("#fechaCitaCrear").val("");
            $("#fechaCitaCrearTexto").text("");
            reiniciarMascotas($("#seleccionarMascota"), "Seleccione primero un cliente");
            $("#mensajeMascotasCrear").text("").removeClass("text-success text-danger");
            $("#disponibilidadNueva").text("").removeClass("text-success text-danger");
            $("#btnGuardarCita").prop("disabled", true);
            calendario.unselect();
        });

        calendario.render();
    });
})(jQuery);
