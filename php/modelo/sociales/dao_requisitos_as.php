<?php
class dao_requisitos_as {
	
	public static function get_requisitos($filtro = array()){
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
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'req', '1=1');
		
		$sql = "select req.*
				from as_requisitos req
				WHERE $where
				ORDER BY REQ.COD_REQUISITO ASC ";
		
		$sql= dao_varios::paginador($sql, null, $desde, $hasta);
		$datos = toba::db()->consultar($sql);
		return $datos;	
	}
	
	public static function get_lov_requisito_x_id ($id){
		if (!is_null($id)){
			$sql = "SELECT req.cod_requisito ||' - '||req.descripcion as lov_descripcion
					from as_requisitos req
					WHERE REQ.COD_REQUISITO = $id ";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}else{
			return null;
		}
	}
	
	public static function get_lov_requisito_x_nombre ($nombre, $filtro = array()){
		if (isset($nombre)) {
    		$trans_cod = ctr_construir_sentencias::construir_translate_ilike('req.cod_requisito', $nombre);
            $trans_nom = ctr_construir_sentencias::construir_translate_ilike('req.descripcion', $nombre);
            $where = "($trans_cod OR $trans_nom )";
        } else {
            $where = '1=1';
        }
        if (!empty($filtro))
        	$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro, "req", "1=1");
        	
        $sql = "SELECT REQ.*, req.cod_requisito ||' - '||req.descripcion as lov_descripcion
				from as_requisitos req
				WHERE $where ORDER BY LOV_DESCRIPCION";
        $datos = toba::db()->consultar($sql);
	    return $datos;
	}
	
}
?>