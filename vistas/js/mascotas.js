//Editar mascotas
$(document).on("click", ".btnEditMascotas", function()
{
    var IDmascota = $(this).attr("dataid");

    var datos = new FormData();

    datos.append("IDmascota", IDmascota);

    $.ajax({
        url:"ajax/mascotas.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false, 
        dataType:"json",
        success:function(respuesta)
        {
            console.log(respuesta);

            $("#IDmascota").val(respuesta["IDmascota"]);
            $("#editarMascota").val(respuesta["nombrem"]);
            $("#editarEspecie").val(respuesta["especie"]);
            $("#editarRaza").val(respuesta["raza"]);
            $("#editarFecha").val(respuesta["fecha_Registro"]);//5
            $("#editarEdad").val(respuesta["edad"]);
            $("#editarSexo").val(respuesta["sexo"]);
            $("#editarCliente").val(respuesta["IDcliente"]);
            $("#editarPeso").val(respuesta["peso"]);
            $("#editarFoto").val(respuesta["fotoM"]);
            $("#fotoActual").val(respuesta["fotoM"]);

            if(respuesta["fotoM"] != "") 
            {
                $(".previsualizar").attr("src", respuesta["fotoM"]);
            } else 
            {
                $(".previsualizar").attr("src", "vistas/img/images.jpg"); // Una imagen por defecto
            }
        }

    });
});
//Eliminar usuarios
$(document).on("click", ".btnEliminarMascota", function(){

    var IDmascota = $(this).attr("IDmascota");

    Swal.fire({
    title: "Esta seguro de eliminar la mascota?",
    text: "Aun puede cancelar la accion",
    icon:"warning" ,
    showCancelButton: true,
    cancelButtoncolor:"#3085d6",
    confirmButtoncolor:"#d33",
    cancelButtontext:"Cancelar",
    confirmButtonText: "Eliminar"
    
    }).then(function (result) {
        
        if(result.isConfirmed)
        {
            enviarAccionProtegida("index.php?ruta=Mascotas", {IDmascota: IDmascota, estadoMascota: 0});
        }
    });

}); 
$(document).on("click", ".btnDesactivarM", function(){

    var IDmascota = $(this).attr("IDmascota");

    Swal.fire({
        title: "¿Está seguro de eliminar al usuario?",
        text: "Aún puede cancelar la acción",
        icon: "warning", // Cambiado 'type' por 'icon' que usa SweetAlert2
        showCancelButton: true,
        confirmButtonColor: "#d33", // C mayúscula
        cancelButtonColor: "#3085d6",  // C mayúscula
        confirmButtonText: "Eliminar",
        cancelButtonText: "Cancelar" // T mayúscula
    }).then(function (result) {
        if(result.isConfirmed) {
            enviarAccionProtegida("index.php?ruta=Mascotas", {IDmascota: IDmascota, estadoMascota: 0});
        }
    });
});
$(document).on("click", ".btnActivarM", function(){

    var IDmascota = $(this).attr("IDmascota");

    Swal.fire({
    title: "Esta seguro de habilitar al usuario?",
    text: "Aun puede cancelar la accion",
    type:"warning" ,
    showCancelButton: true,
    cancelButtoncolor:"#3085d6",
    cancelButtontext:"Cancelar",
    confirmButtoncolor:"#d33",
    confirmButtonText: "Restaurar"
    
    }).then(function (result) {
        
        if(result.isConfirmed)
        {
            enviarAccionProtegida("index.php?ruta=Mascotas", {IDmascota: IDmascota, estadoMascota: 1});
        }
    });

}); 
