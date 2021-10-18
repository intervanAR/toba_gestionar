<?php
class dao_matriz_economica_gastos {
	
	static public function get_partidas_economicas ($filtro = array()){
		$where = ' 1=1 ';
		
		if (isset($filtro) && !empty($filtro))
        	$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ppe', '1=1');
        
        $sql = "SELECT PPE.*, 
				       PKG_PR_PARTIDAS.MASCARA_APLICAR(PPE.COD_PARTIDA) COD_PARTIDA_MASC,
				       PKG_PR_ECONOMICO_GASTOS.MASCARA_APLICAR(PPE.COD_ECONOMICO) COD_ECONOMICO_MASC,
				       PP.DESCRIPCION PARTIDA_DESCRIPCION,
				       PRTP.DESCRIPCION TIPO_PROYECTO_DESCRIPCION,
				       PPEG.DESCRIPCION ECONOMICO_DESCRIPCION
				FROM PR_PARTIDAS_ECONOMICO PPE, PR_PARTIDAS PP, PR_TIPOS_PROYECTOS PRTP, PR_ECONOMICO_GASTOS PPEG
				WHERE $where AND PPE.COD_PARTIDA = PP.COD_PARTIDA 
				  AND PPE.COD_TIPO_PROYECTO = PRTP.COD_TIPO_PROYECTO 
				  AND PPE.COD_ECONOMICO = PPEG.COD_ECONOMICO
				ORDER BY PPE.COD_PARTIDA ASC";
       	return toba::db()->consultar($sql);
	}
	
	
	static public function get_lov_economico_gasto_x_codigo ($cod_economico){
		if (!is_null($cod_economico)){
			$sql = "SELECT '['|| PKG_PR_ECONOMICO_GASTOS.MASCARA_APLICAR(PREG.COD_ECONOMICO) ||'] '||PREG.DESCRIPCION LOV_DESCRIPCION
					FROM PR_ECONOMICO_GASTOS PREG
					WHERE PREG.COD_ECONOMICO = $cod_economico 
					ORDER BY LOV_DESCRIPCION";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}else{
			return null;
		}
	}
	
	static public function get_lov_economico_gasto_x_nombre ($nombre, $filtro = array()){
	 	if (isset($nombre)) {
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('PREG.COD_ECONOMICO', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('PREG.DESCRIPCION', $nombre);
			$where = "($trans_nro OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
        
        if (isset($filtro['activo'])){
        	$where .= " AND PKG_PR_ECONOMICO_GASTOS.ACTIVO(PREG.COD_ECONOMICO) = 'S' ";
        	unset($filtro['activo']);
        }
        
        if (isset($filtro) && !empty($filtro))
        	$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'PREG', '1=1');
        	
        $sql = "SELECT PREG.*, '['|| PKG_PR_ECONOMICO_GASTOS.MASCARA_APLICAR(PREG.COD_ECONOMICO) ||'] '||PREG.DESCRIPCION LOV_DESCRIPCION
					FROM PR_ECONOMICO_GASTOS PREG
					WHERE $where 
					ORDER BY LOV_DESCRIPCION";
		return toba::db()->consultar($sql);
	}
	
	
	static public function get_lov_tipo_proyecto_x_codigo ($cod_tipo_proyecto){
		if (!is_null($cod_tipo_proyecto)){
			$sql = "SELECT PRTP.COD_TIPO_PROYECTO ||' - '|| PRTP.DESCRIPCION LOV_DESCRIPCION
					  FROM PR_TIPOS_PROYECTOS PRTP
					 WHERE PRTP.COD_TIPO_PROYECTO = $cod_tipo_proyecto 
				  ORDER BY LOV_DESCRIPCION";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}else return null;
	}
	
	static public function get_lov_tipo_proyecto_x_nombre ($nombre, $filtro = array()){
	 	if (isset($nombre)) {
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('PRTP.COD_TIPO_PROYECTO', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('PRTP.DESCRIPCION', $nombre);
			$where = "($trans_nro OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
        
        if (isset($filtro) && !empty($filtro))
        	$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'PRTP', '1=1');
        	
        $sql = "SELECT PRTP.*, PRTP.COD_TIPO_PROYECTO ||' - '||PRTP.DESCRIPCION LOV_DESCRIPCION
		  	  	  FROM PR_TIPOS_PROYECTOS PRTP
				 WHERE $where 
			  ORDER BY LOV_DESCRIPCION";
		return toba::db()->consultar($sql);
	}
	
	
}
?>