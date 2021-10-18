<?php

class dao_viviendas_as {
	
	static public function get_viviendas ($filtro = array()){
		
		$where = " 1=1 ";
		if (isset($filtro) && !empty($filtro)){
			$where .= ' and '.ctr_construir_sentencias::get_where_filtro($filtro, 'V','1=1');
		}
		
		$sql = "SELECT v.*,
				       (SELECT rv_meaning
				          FROM cg_ref_codes
				         WHERE rv_domain = 'AS_TIPO_OCUPACION'
				           AND rv_low_value = v.tipo_ocupacion) AS tipo_ocupacion_format,
				       (SELECT rv_meaning
				          FROM cg_ref_codes
				         WHERE rv_domain = 'AS_TIPO_PISOS'
				           AND rv_low_value = v.tipo_pisos) AS tipo_pisos_format,
				       (SELECT rv_meaning
				          FROM cg_ref_codes
				         WHERE rv_domain = 'AS_TIPO_TECHOS'
				           AND rv_low_value = v.tipo_techos) AS tipo_techos_format,
				       (SELECT rv_meaning
				          FROM cg_ref_codes
				         WHERE rv_domain = 'AS_TIPO_PAREDES'
				           AND rv_low_value = v.tipo_paredes) AS tipo_paredes_format,
				       (SELECT rv_meaning
				          FROM cg_ref_codes
				         WHERE rv_domain = 'AS_TIPO_VIVIENDA'
				           AND rv_low_value = v.tipo_vivienda) AS tipo_vivienda_format,
				       (SELECT rv_meaning
				          FROM cg_ref_codes
				         WHERE rv_domain = 'AS_TIPO_ZONA'
				           AND rv_low_value = v.tipo_zona) AS tipo_zona_format,
				       CASE
				          WHEN v.tiene_cocina = 'S'
				             THEN 'Si'
				          ELSE 'No'
				       END tiene_cocina_format,
				       CASE
				          WHEN v.tiene_comedor = 'S'
				             THEN 'Si'
				          ELSE 'No'
				       END tiene_comedor_format,
				       CASE
				          WHEN v.tipo_banio = 'S'
				             THEN 'Si'
				          ELSE 'No'
				       END tipo_banio_format
				  FROM as_viviendas v
				 WHERE $where
			  ORDER BY v.ID_VIVIENDA ASC";
		
		return toba::db()->consultar($sql);	
	}		
	
	static public function get_lov_vivienda_x_id ($id_vivienda){
		$sql = "select v.nomenclatura lov_descripcion
				from as_viviendas v
				where v.id_vivienda = ".quote($id_vivienda);
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}
	
	static public function get_lov_vivienda_x_nombre ($nombre, $filtro = array()){
		if (isset($nombre)) {
    		$trans_cod = ctr_construir_sentencias::construir_translate_ilike('v.nomenclatura', $nombre);
            $where = "($trans_cod)";
        } else {
            $where = '1=1';
        }
        
        if (!empty($filtro))
        	$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro, "v", "1=1");
		$sql = "select v.id_vivienda, v.nomenclatura lov_descripcion
				from as_viviendas v
				where ".$where."
				order by lov_descripcion";
		return toba::db()->consultar($sql);
	}
	
	static public function get_vivienda_x_id ($id_vivienda){
		$sql = "SELECT * FROM AS_VIVIENDAS WHERE id_vivienda = $id_vivienda ";
		return toba::db()->consultar_fila($sql);
	}
	
	static public function buscar($dato){
		$sql ="select *
			     from as_viviendas
			   	where nomenclatura like '".$dato."%'
			 	order by nomenclatura";
		return toba::db()->consultar($sql);
	}
	
}

?>