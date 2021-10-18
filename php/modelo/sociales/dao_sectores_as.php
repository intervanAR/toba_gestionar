<?php
class dao_sectores_as {
	
	public static function get_sectores ($filtro = array()){
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
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'SC', '1=1');
			
		$sql = "SELECT SC.*
				FROM AS_SECTORES SC
				WHERE $where
				ORDER BY COD_SECTOR ASC;";
		$sql= dao_varios::paginador($sql, null, $desde, $hasta);
		$datos = toba::db()->consultar($sql);
		return $datos;	
	}
	
	public static function get_lov_sectores_x_id ($id){
		if (!is_null($id)){
			$sql = "SELECT SC.COD_SECTOR ||' - '|| SC.NOMBRE AS LOV_DESCRIPCION
					FROM AS_SECTORES SC
					WHERE SC.COD_SECTOR = $id ";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}else{
			return null;
		}
	}
	
	public static function get_lov_sectores_x_nombre ($nombre=null, $filtro = array(), $fuente=null){
		if (isset($nombre)) {
    		$trans_cod = ctr_construir_sentencias::construir_translate_ilike('sc.cod_sector', $nombre);
            $trans_nom = ctr_construir_sentencias::construir_translate_ilike('sc.nombre', $nombre);
            $where = "($trans_cod OR $trans_nom )";
        } else {
            $where = '1=1';
        }
        
        if (isset($filtro['permitidos']) && isset($filtro['usuario'])){
        	$where .= " AND SC.COD_SECTOR IN (SELECT COD_SECTOR
        									  FROM AS_USUARIOS_SECTORES
        									  WHERE USUARIO = '".$filtro['usuario']."') ";
        	unset($filtro['permitidos']);
        	unset($filtro['usuario']);
        }
        
        if (!empty($filtro))
        	$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro, "SC", "1=1");
        	
        $sql = "SELECT SC.*, SC.COD_SECTOR ||' - '|| SC.NOMBRE AS LOV_DESCRIPCION
				FROM AS_SECTORES SC
				WHERE $where ORDER BY LOV_DESCRIPCION";
        
        $datos = toba::db($fuente)->consultar($sql);
	    return $datos;
	}
}
?>