<?php
class dao_articulos_as {
	
	public static function get_articulos ($filtro = array()){
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
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'ART', '1=1');
		
		$sql = "SELECT ART.COD_ARTICULO,
				       SUBSTR(ART.DESCRIPCION ,0,60) AS DESCRIPCION_FORMAT,
				       (SELECT RV_MEANING 
				          FROM CG_REF_CODES 
				         WHERE RV_DOMAIN = 'AS_UNIDAD_MEDIDA' 
				           AND RV_LOW_VALUE = ART.UNIDAD_MEDIDA) UNIDAD_MEDIDA_FORMAT,
				       ART.AFI_ARTICULO
				FROM AS_ARTICULOS ART
				WHERE $where 
				ORDER BY COD_ARTICULO ASC ";
		return toba::db()->consultar($sql);
	}
	
	public static function get_lov_articulos_x_id ($id){
		if (!is_null($id)){
			$sql = "SELECT ART.COD_ARTICULO ||' - '|| ART.DESCRIPCION AS LOV_DESCRIPCION
					FROM AS_ARTICULOS ART
					WHERE ART.COD_ARTICULO = $id ";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}else{
			return '';
		}
	}
	public static function get_lov_articulos_x_nombre ($nombre, $filtro = array()){
		
		if (isset($nombre)) {
    		$trans_cod = ctr_construir_sentencias::construir_translate_ilike('ART.cod_ARTICULO', $nombre);
            $trans_nom = ctr_construir_sentencias::construir_translate_ilike('ART.descripcion', $nombre);
            $where = "($trans_cod OR $trans_nom )";
        } else {
            $where = '1=1';
        }
        
        if (isset($filtro['cod_beneficio']) && !empty($filtro['cod_beneficio'])){
        	$where .= " and art.cod_articulo in (SELECT cod_articulo
        										   FROM as_articulos_beneficios
   												  WHERE cod_beneficio = ".quote($filtro['cod_beneficio']).")";
        }
        
        unset($filtro['cod_beneficio']);
        if (!empty($filtro))
        	$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro, "ART", "1=1");
        	
        $sql = "SELECT ART.*, ART.COD_ARTICULO ||' - '|| ART.DESCRIPCION AS LOV_DESCRIPCION
				FROM AS_ARTICULOS ART
				WHERE $where 
				ORDER BY ART.DESCRIPCION";
        
        $datos = toba::db()->consultar($sql);
	    return $datos;
	}
	
	static public function get_ui_descripcion_unidad ($cod_articulo){
		//Retorna descripcion de la unidad de medida
		$sql = "select rc.rv_meaning unidad
				  from as_articulos art, cg_ref_codes rc 
				 where art.cod_articulo = ".$cod_articulo." and rv_domain = 'AS_UNIDAD_MEDIDA' and rv_low_value = art.unidad_medida";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['unidad'];
	}
	
	public static function get_lov_articulo_afi_x_codigo ($cod_articulo){
		$sql = "SELECT artf.cod_articulo ||' - '|| artf.descripcion lov_descripcion
				  FROM co_articulos@FINANCIERO artf
				 where artf.cod_articulo = $cod_articulo ";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}
	
	public static function get_lov_articulos_afi_x_nombre ($nombre, $filtro = array()){
		if (isset($nombre)) { 
   			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('art.cod_articulo', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('art.descripcion', $nombre);
            $where = "($trans_codigo OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
        
    	if (isset($filtro['articulo_desde'])){
    		$where .= " and art.cod_articulo >= ".$filtro['articulo_desde'];
    		unset($filtro['articulo_desde']);
    	}
    	
    	if (isset($filtro['primero'])){
    		$where .= " and art.cod_articulo = (select min(cod_articulo) 
    											  from co_articulos 
    											 where activo = 'S')";
    		unset($filtro['primero']);
    	}
    	
    	if (isset($filtro['ultimo'])){
    		$where .= " and art.cod_articulo = (select max(cod_articulo) 
    											  from co_articulos 
    											 where activo = 'S')";
    		unset($filtro['ultimo']);
    	}
    	
    	$where .= ' and '.ctr_construir_sentencias::get_where_filtro($filtro, 'art');
    	
    	$sql = "select art.*, art.cod_articulo ||' - '|| art.descripcion lov_descripcion
    			from co_articulos@FINANCIERO art
    			where $where
    			order by art.cod_articulo";
    	return toba::db()->consultar($sql);
	}
}
?>