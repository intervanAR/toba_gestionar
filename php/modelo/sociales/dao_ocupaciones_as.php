<?php
class dao_ocupaciones_as {
	
	public static function get_ocupaciones ($filtro = array()){
		$desde= null;
		$hasta= null;
		if(isset($filtro['desde'])){
			$desde= $filtro['desde'];
			$hasta= $filtro['hasta'];

			unset($filtro['desde']);
			unset($filtro['hasta']);
		}
		
		$where = "  1=1 ";
		if (isset($filtro) )
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'O', '1=1');
		
		$sql = "SELECT O.* 
				  FROM AS_OCUPACIONES O
				 WHERE $where 
		      ORDER BY COD_OCUPACION ASC ";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	
	
}

?>