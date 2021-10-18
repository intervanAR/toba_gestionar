<?php 

class dao_co_usuarios 
{

	public static function get_usuarios($filtro = array(), $fuente=null) {
        $where = " 1=1 ";
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'cou', '1=1');
        $sql_sel = "SELECT  cou.*
					FROM co_usuarios cou
					WHERE $where
					ORDER BY cou.usuario ASC;";
        $datos = toba::db($fuente)->consultar($sql_sel);
		return $datos;
    }

}

?>