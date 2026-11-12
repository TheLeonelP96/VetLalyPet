<?php
// Prepara las alertas que se muestran a los usuarios según sus permisos.

final class ControladorAlertas
{
    public static function ctrAlertasActuales(): array
    {
        if (!Seguridad::autenticado()) {
            return [];
        }
        return ModeloAlertas::mdlObtenerAlertas(
            (int) $_SESSION['IDusuario'],
            (int) $_SESSION['IDrol']
        );
    }
}
