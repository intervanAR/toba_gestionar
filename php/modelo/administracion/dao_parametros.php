<?php  
	
class dao_parametros {

	static public function get_parametros ($filtro = array())
	{

		$where = "1=1";
		
		if (isset($filtro['valor']) && !empty($filtro['valor'])){
			$where .= " and upper(p.valor) like '%".strtoupper($filtro['valor'])."%'";
			unset($filtro['valor']);
		}
		if (isset($filtro['descripcion']) && !empty($filtro['descripcion'])){
			$where .= " and upper(p.descripcion) like '%".strtoupper($filtro['descripcion'])."%'";
			unset($filtro['descripcion']);
		}
		if (isset($filtro['parametro']) && !empty($filtro['parametro'])){
			$where .= " and upper(p.parametro) like '%".strtoupper($filtro['parametro'])."%'";
			unset($filtro['parametro']);
		}
		
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'P', '1=1');

		$sql = "SELECT P.*
				  FROM KR_PARAMETROS P
				 WHERE $where 
			  ORDER BY P.PARAMETRO ";
			
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

}

?>