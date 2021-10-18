<?php 

class dao_kr_primitivas {

	static public function get_primitivas ($filtro = []){
		$where = "1=1";

		if (isset($filtro['primitiva'])){
			$where .=" and upper(krp.primitiva) like upper('%".$filtro['primitiva']."%')";
			unset($filtro['primitiva']);
		}
		if (isset($filtro['descripcion'])){
			$where .=" and upper(krp.descripcion) like upper('%".$filtro['descripcion']."%')";
			unset($filtro['descripcion']);
		}
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'krp', '1=1');

		$sql = "SELECT krp.primitiva, krp.descripcion
				  FROM kr_primitivas krp
				 WHERE $where 
			  ORDER BY krp.primitiva ";
			
		$datos = toba::db()->consultar($sql);
		return $datos;
	}


}

?>