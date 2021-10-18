<?php


class dao_auditoria {
	public static function get_tablas(){
		$sql="SELECT *
			FROM v_TABLAS";
		
		$datos= toba::db()->consultar($sql);
		
		return $datos;
	}
	
	public static function get_campos_x_tabla($tabla){
		$sql="SELECT t.*, nvl(c.posicion,0) pk
		FROM V_TAB_COLUMNS t, V_COLUMNAS_PK c
		WHERE t.tabla= ".quote($tabla)."
		AND c.tabla(+)= t.tabla
		AND c.campo(+)= t.campo";
		
		$datos= toba::db()->consultar($sql);
		
		return $datos;
	}
	
	public static function get_auditoria($filtro= array()){
		$where= "1 = 1";
		$where.= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'au', '1=1');
        
        $sql = "SELECT au.*
                FROM AUDITORIA_2 au
                WHERE $where";
      
	    $datos = toba::db()->consultar($sql);
	        
	    return $datos;
	}
}

?>
