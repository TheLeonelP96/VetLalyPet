//import { cache } from "react";

$(".nuevaFoto").change(function()
{
    //validar el tipo de archivo de la foto
    var imagen=this.files[0];
    if(imagen["type"]!="image/jpeg" && imagen["type"]!= "image/png")
    {
        $(".nuevaFoto").val("");
        swal({
            title: "Error al subir!!",
            text:"La imagen debe ser jpg o png",
            type: "error",
            //showConfirmButton: true,    
            showConfirmButton: "cerrar"
        }

        );
    }
    else if(imagen["size"]>=2000000)
    {
        $(".nuevaFoto").val("");
         swal({
            title: "Error al subir!!",
            text:"La imagen no debe pesar más de 2mb",
            type: "error",
            //showConfirmButton: true,
            showConfirmButton: "cerrar"
        }

        );
    }
    else
    {
        var datosImagen = new FileReader;
        datosImagen.readAsDataURL(imagen);
        $(datosImagen).on("load", function(event)
        {
            var rutaImagen=event.target.result;
            $(".previsualizar").attr("src", rutaImagen);
        });
    }
});

//editar usuario

$(document).on("click", ".btnEditUsuario", function()
{
    var IDusuario = $(this).attr("data-id");

    var datos = new FormData();

    datos.append("IDusuario", IDusuario);

    $.ajax({
        url:"ajax/usuarios.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false, 
        dataType:"json",
        success:function(respuesta)
        {
            console.log(respuesta);

            $("#IDusuario").val(respuesta["IDusuario"]);
            $("#editarNombre").val(respuesta["nombre"]);
            $("#editarApellido").val(respuesta["apellido"]);
            $("#editarUsuario").val(respuesta["Usunombre"]);
            $("#editarTelefono").val(respuesta["telefono"]);//5
            $("#editarEmail").val(respuesta["correo"]);
            $("#editarPassword").val("");
            $("#editarTurno").val(respuesta["turno"]);
            $("#editarFoto").val(respuesta["foto"]);
            $("#fotoActual").val(respuesta["foto"]);

            if(respuesta["foto"]!="")
            {
                $(".previsualizar").attr("src", respuesta["foto"]);
            }
            
        }

    });
});
function enviarAccionProtegida(ruta, campos) {
    var form = $("<form>", { method: "POST", action: ruta });
    var token = $("meta[name='csrf-token']").attr("content");
    form.append($("<input>", { type: "hidden", name: "csrf_token", value: token }));
    Object.keys(campos).forEach(function(nombre) {
        form.append($("<input>", { type: "hidden", name: nombre, value: campos[nombre] }));
    });
    $(document.body).append(form);
    form.trigger("submit");
}

//Eliminar usuarios
$(document).on("click", ".btnEliminarUsuario", function(){

    var IDusuario = $(this).attr("IDusuario");

    Swal.fire({
    title: "Esta seguro de eliminar al usuario?",
    text: "Aun puede cancelar la accion",
    type:"warning" ,
    showCancelButton: true,
    cancelButtoncolor:"#3085d6",
    cancelButtontext:"Cancelar",
    confirmButtoncolor:"#d33",
    confirmButtonText: "Eliminar"
    
    }).then(function (result) {
        
        if(result.isConfirmed)
        {
            enviarAccionProtegida("index.php?ruta=Usuarios", {IDusuario: IDusuario, estadoUsuario: 0});
        }
    });

}); 
//  Desabilitar usuario
$(document).on("click", ".btnDesactivar", function(){

    var IDusuario = $(this).attr("IDusuario");

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
            enviarAccionProtegida("index.php?ruta=Usuarios", {IDusuario: IDusuario, estadoUsuario: 0});
        }
    });
});
$(document).on("click", ".btnActivar", function(){

    var IDusuario = $(this).attr("IDusuario");

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
            enviarAccionProtegida("index.php?ruta=Usuarios", {IDusuario: IDusuario, estadoUsuario: 1});
        }
    });

}); 
