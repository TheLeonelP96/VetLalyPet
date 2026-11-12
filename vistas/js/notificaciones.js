(function () {
    "use strict";

    var alerta = document.querySelector(".app-flash");
    if (!alerta || typeof Swal === "undefined") {
        return;
    }

    var tipo = alerta.getAttribute("data-tipo") || "info";
    var iconos = { success: "success", danger: "error", warning: "warning", info: "info" };
    var titulos = {
        success: "Operación completada",
        danger: "No se pudo completar",
        warning: "Atención",
        info: "Información"
    };
    var copia = alerta.cloneNode(true);
    var cerrar = copia.querySelector(".close");
    if (cerrar) {
        cerrar.remove();
    }
    var mensaje = copia.textContent.trim();
    alerta.remove();

    Swal.fire({
        icon: iconos[tipo] || "info",
        title: titulos[tipo] || "Información",
        text: mensaje,
        confirmButtonText: "Aceptar",
        confirmButtonColor: "#337ab7"
    });
})();
