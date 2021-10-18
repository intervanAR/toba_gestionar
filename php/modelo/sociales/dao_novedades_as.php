<?php
class dao_novedades_as {
	
	public static function get_novedades ($filtro = array()){
		
		$where = "  1=1 ";
		if (isset($filtro) )
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'nov', '1=1');
			
		$sql = "SELECT nov.*,
				       tnov.descripcion tipo_novedad_format
				  FROM as_novedades nov, as_tipos_novedades tnov
				 WHERE nov.cod_tipo_novedad = tnov.cod_tipo_novedad and $where
  			  ORDER BY nov.id_novedad desc";
		
		return toba::db()->consultar($sql);	
	}
	
	public static function get_novedad ($id_novedad){
		$sql ="SELECT nov.*
				 FROM as_novedades nov
				WHERE nov.id_novedad = ".quote($id_novedad);
		return toba::db()->consultar_fila($sql);
	}
	
	public static function get_tipos_novedades ($filtro = array()){
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
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'TNOV', '1=1');
		
		$sql = "SELECT TNOV.*
				  FROM AS_TIPOS_NOVEDADES TNOV
				 WHERE $where
				 ORDER BY TNOV.COD_TIPO_NOVEDAD ASC ";
		
		$sql= dao_varios::paginador($sql, null, $desde, $hasta);
		$datos = toba::db()->consultar($sql);
		return $datos;		
	}
	
	public static function get_lov_tipo_novedad_x_codigo ($cod_tipo_novedad){
		
		$sql = "SELECT TNOV.COD_TIPO_NOVEDAD ||' - '|| TNOV.DESCRIPCION LOV_DESCRIPCION
				FROM AS_TIPOS_NOVEDADES TNOV
				WHERE TNOV.COD_TIPO_NOVEDAD = ".quote($cod_tipo_novedad);
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}
	
	public static function get_lov_tipos_novedades_x_nombre ($nombre, $filtro = array()){
		if (isset($nombre)) {
    		$trans_cod = ctr_construir_sentencias::construir_translate_ilike('tnov.cod_tipo_novedad', $nombre);
            $trans_nom = ctr_construir_sentencias::construir_translate_ilike('tnov.descripcion', $nombre);
            $where = "($trans_cod OR $trans_nom )";
        } else {
            $where = '1=1';
        }
        
        if (!empty($filtro))
        	$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro, "tnov", "1=1");
        	
		$sql = "SELECT TNOV.*, TNOV.COD_TIPO_NOVEDAD ||' - '|| TNOV.DESCRIPCION LOV_DESCRIPCION
				FROM AS_TIPOS_NOVEDADES TNOV
				WHERE $where
				ORDER BY LOV_DESCRIPCION";
		return toba::db()->consultar($sql);
	}
	
	static public function cargar_novedad ($datos){
		$sql = "INSERT INTO AS_NOVEDADES (ID_NOVEDAD,ID_SOLICITUD,FECHA,COD_TIPO_NOVEDAD,DESCRIPCION,VALORACION)
						VALUES (null,".quote($datos['id_solicitud']).",".quote($datos['fecha']).",".quote($datos['cod_tipo_novedad']).",".quote($datos['descripcion']).",".quote($datos['valoracion']).")";
		$resul = toba::db()->ejecutar($sql);
	}
	
	static public function actualizar_novedad ($datos){
		$sql = "UPDATE AS_NOVEDADES SET FECHA = ".quote($datos['fecha']).", COD_TIPO_NOVEDAD = ".quote($datos['cod_tipo_novedad']).", 
				DESCRIPCION = ".quote($datos['descripcion'])." , VALORACION= ".quote($datos['valoracion'])." 
				WHERE ID_NOVEDAD = ".quote($datos['id_novedad'])." and
				      ID_SOLICITUD = ".quote($datos['id_solicitud']);
				
		$resul = toba::db()->ejecutar($sql);
	}
	
	
}
?>