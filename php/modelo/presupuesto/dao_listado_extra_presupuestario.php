<?php

class dao_listado_extra_presupuestario extends dao_reportes_general {
	
	
	static public function get_lov_auxiliar_x_codigo ($cod_auxiliar){
		$sql = "SELECT KRAUEX.COD_AUXILIAR ||' - '|| KRAUEX.DESCRIPCION as lov_descripcion
				FROM KR_AUXILIARES_EXT KRAUEX
				WHERE COD_AUXILIAR = $cod_auxiliar
				ORDER BY COD_AUXILIAR";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}
	
	static public function get_lov_auxiliar_x_nombre ($nombre, $filtro = array()){
		$where ="";
    	if (isset($nombre)) {
			$trans_cod = ctr_construir_sentencias::construir_translate_ilike('cod_auxiliar', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_cod OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
        if ( ! empty($filtro) && isset($filtro['cod_auxiliar_desde'])){
        	$where .= " AND (KRAUEX.COD_AUXILIAR >= ".$filtro['cod_auxiliar_desde'].")";
        }
		$sql = "SELECT KRAUEX.*, KRAUEX.COD_AUXILIAR ||' - '|| KRAUEX.DESCRIPCION as lov_descripcion
				FROM KR_AUXILIARES_EXT KRAUEX
				WHERE $where 
				ORDER BY COD_AUXILIAR";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	
	
	
}

?>