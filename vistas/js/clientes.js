//Editar Clientes
$(document).on("click", ".btnEditClientes", function()
{
    var IDcliente = $(this).attr("datid");

    var datos = new FormData();

    datos.append("IDcliente", IDcliente);

    $.ajax({
        url:"ajax/clientes.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false, 
        dataType:"json",
        success:function(respuesta)
        {
            console.log(respuesta);

            $("#IDcliente").val(respuesta["IDcliente"]);
            $("#editarCliente").val(respuesta["nombrec"]);
            $("#editarApellidoc").val(respuesta["apellidoc"]);
            $("#editarTelefono").val(respuesta["telefono"]);
            $("#editarRegistro").val(respuesta["registro"]);//5
           
	
        }

    });
});
//Eliminar usuarios
$(document).on("click", ".btnEliminarCliente", function(){

    var IDcliente = $(this).attr("IDcliente");

    Swal.fire({
    title: "Esta seguro de eliminar al dueño?",
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
            enviarAccionProtegida("index.php?ruta=Clientes", {IDcliente: IDcliente, eliminarCliente: 1});
        }
    });

}); 
