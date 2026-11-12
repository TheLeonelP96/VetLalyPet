(function ($) {
    "use strict";

    $(document).on("click", ".btnCerrarSesion", function (evento) {
        evento.preventDefault();
        var destino = this.href;
        Swal.fire({
            icon: "question",
            title: "¿Cerrar sesión?",
            text: "Saldra del sistema y la sesión se cerrará.",
            showCancelButton: true,
            confirmButtonColor: "#d9534f",
            cancelButtonColor: "#73879c",
            confirmButtonText: "Sí, cerrar sesión",
            cancelButtonText: "Cancelar"
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                window.location.href = destino;
            }
        });
    });
})(jQuery);
