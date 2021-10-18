<?php 

class dao_matriz_estado 
{

	public static function get_matriz_estado($filtro = array()) {
        $where = " 1=1 ";
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'com', '1=1');
        $sql_sel = "SELECT com.*
  					  FROM co_matriz_estados com
					WHERE $where
					ORDER BY com.nro_entrada DESC;";
        return toba::db()->consultar($sql_sel);
    }


    static public function get_descripcion_estado($tipo_comprobante, $estado)
    {
    	$sql = "SELECT PKG_GENERAL.significado_dominio('CO_ESTADO_'||PKG_GENERAL.valor_parametro('$tipo_comprobante'), '$estado') valor from dual";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['valor'];
    }

}

?>