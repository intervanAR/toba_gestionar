<?php 

class dao_co_seguridad
{

    public static function get_configuracion()
    {
        $sql = " SELECT s.*
                   FROM sse_objetos_validar_toba s ";
                   
        return principal_ei_tabulator_consultar::todos_los_datos($sql);
    }
}
	
?>