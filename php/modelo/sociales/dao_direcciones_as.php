<?php
class dao_direcciones_as {
	
	
	static public function get_direcciones ($filtro = array()){
		$where = "  1=1 ";
		
		if (isset($filtro['ui_pais']) && !empty($filtro['ui_pais'])){
			$where .= " and pro.cod_pais = '".$filtro['ui_pais']."'";
			unset($filtro['ui_pais']);
		}
		if (isset($filtro['ui_provincia']) && !empty($filtro['ui_provincia'])){
			$where .= " and pro.cod_provincia = '".$filtro['ui_provincia']."'";
			unset($filtro['ui_provincia']);
		}
		if (isset($filtro['ui_localidad']) && !empty($filtro['ui_localidad'])){
			$where .= " and loc.cod_localidad = '".$filtro['ui_localidad']."'";
			unset($filtro['ui_localidad']);
		}
		
		if (isset($filtro) )
			$where .= " and ". ctr_construir_sentencias::get_where_filtro($filtro, 'dir', '1=1');
		
		$sql = "SELECT dir.id_direccion, dir.tipo, dir.cod_barrio, dir.cod_calle, dir.nro,
				       dir.piso, dir.departamento, dir.escalera, dir.ubicacion,
				       dir.codigo_postal, dir.observacion, loc.cod_localidad,
				       pro.cod_provincia, pro.cod_pais, 
				       ba.DESCRIPCION barrio_descripcion,
				       pro.DESCRIPCION provincia_descripcion,
				       loc.DESCRIPCION localidad_descripcion,
				       (select descripcion from as_paises where cod_pais = pro.cod_pais) as pais_descripcion,
				       (select rv_meaning from cg_ref_codes where rv_low_value = dir.TIPO and rv_domain = 'AS_TIPO_DIRECCION') tipo_descripcion
				  FROM as_calles ca, as_localidades loc, as_provincias pro, as_direcciones dir, as_barrios ba
				 WHERE $where and dir.cod_calle = ca.cod_calle
					   AND ca.cod_localidad = loc.cod_localidad
					   AND loc.cod_provincia = pro.cod_provincia
					   AND ba.cod_barrio = dir.cod_barrio 
			  ORDER BY id_direccion DESC ";
		return toba::db()->consultar($sql);
	}
	
	static public function guardar_direccion ($datos){
		/* Esta funcion INSERTA en caso de ser necesario una direccion y retorna un id. 
		NO ACTUALIZA datos sobre un registro cargado. */
		$where = ' 1=1 ';
		foreach ($datos as $key => $value) {
			if (is_null($value)){
				$where .= " and $key is null ";
			}else{
				$where .= " and $key = '$value'";
			}
		}
		
		$sql = "SELECT id_direccion
				  FROM as_direcciones
				 WHERE $where ";
		
		$result = toba::db()->consultar_fila($sql);
		//Si existe direccion con esos parametros devuelvo ese id
		if (isset($result['id_direccion']) && !is_null($result['id_direccion'])){
			return $result['id_direccion'];
		}else{
			//Si no existe inserto y retorno el id
			
			//Armo el SQL insert.
			$sql = "insert into as_direcciones (";
			$cant = count($datos);  
			foreach ($datos as $key => $value) {
				$sql .= $key;
				if ($cant > 1)
					$sql .=",";
				$cant--;
			}
			
			$sql .= ") values (";
			$cant = count($datos);
			foreach ($datos as $key => $value) {
				$sql .= quote($value);
				if ($cant > 1)
					$sql .=",";
				$cant--;
			}
			$sql .= ")";
			
			//Ejecuto SQL;
			$afectados = toba::db()->ejecutar($sql);
			if ($afectados){
				$sql = "SELECT ID_DIRECCION 
						  FROM AS_DIRECCIONES
						 WHERE $where ";
				$datos = toba::db()->consultar_fila($sql);
				return $datos['id_direccion'];
			}
		}
		return 0;
	} 
	
	static public function get_direccion ($id_direccion){
		$sql = "SELECT dir.id_direccion, dir.tipo d_tipo, dir.cod_barrio d_cod_barrio,
				       dir.cod_calle d_cod_calle, dir.nro d_nro, dir.piso d_piso,
				       dir.departamento d_departamento, dir.escalera d_escalera,
				       dir.ubicacion d_ubicacion, dir.codigo_postal d_codigo_postal,
				       dir.observacion d_observacion, loc.cod_localidad ui_localidad, 
				       pro.descripcion ui_provincia_des,
				       pro.cod_provincia ui_provincia, pro.cod_pais ui_pais, ca.DESCRIPCION ui_calle_des
				       ,loc.DESCRIPCION ui_localidad_des, pa.DESCRIPCION ui_pais_des
				  FROM as_calles ca, as_localidades loc, as_provincias pro, as_direcciones dir, as_barrios ba, as_paises pa
				 WHERE dir.cod_calle = ca.cod_calle
					   AND ca.cod_localidad = loc.cod_localidad
					   AND loc.cod_provincia = pro.cod_provincia
					   AND ba.cod_barrio = dir.cod_barrio and pro.COD_PAIS = pa.COD_PAIS and id_direccion = $id_direccion ";
		return toba::db()->consultar_fila($sql);
	}
	
	static public function get_paises (){
		$sql = "SELECT * FROM AS_PAISES ORDER BY DESCRIPCION ASC";
		return toba::db()->consultar($sql);
	}
	
	static public function get_provincias ($cod_pais){
		$sql = "SELECT * FROM AS_PROVINCIAS WHERE COD_PAIS = '$cod_pais' ORDER BY DESCRIPCION ASC";
		return toba::db()->consultar($sql);
	}
	
	static public function get_localidades($cod_provincia){
		$sql = "SELECT * FROM AS_LOCALIDADES WHERE COD_PROVINCIA = '$cod_provincia' ORDER BY DESCRIPCION ASC";
		return toba::db()->consultar($sql);
	}
	
	static public function get_calles ($filtro, $cod_localidad){
		$sql = "SELECT * 
		          FROM AS_CALLES 
				 WHERE COD_LOCALIDAD = $cod_localidad 
				   AND substr(descripcion,1,2) <> '__'
				   AND DESCRIPCION LIKE '%' || UPPER('$filtro') || '%'
				 ORDER BY DESCRIPCION ASC";
		return toba::db()->consultar($sql);
	}
	
	static public function get_calle ($cod_calle){
		$sql = "SELECT DESCRIPCION 
		          FROM AS_CALLES 
				 WHERE COD_CALLE = $cod_calle";
		return toba::db()->consultar($sql);
	}
	
	static public function get_barrios ($filtro, $cod_localidad){
		$sql = "SELECT * FROM AS_BARRIOS WHERE COD_LOCALIDAD = $cod_localidad AND UPPER(DESCRIPCION) LIKE '%' || UPPER('$filtro') || '%' ORDER BY DESCRIPCION ASC";
		return toba::db()->consultar($sql);
	}
	static public function get_barrio ($cod_barrio){
		$sql = "SELECT DESCRIPCION FROM AS_BARRIOS WHERE COD_BARRIO = $cod_barrio";
		return toba::db()->consultar($sql);
	}
	
	static public function get_lov_calles_x_codigo ($cod_calle){
		if (!is_null($cod_calle)){
			$sql = "SELECT C.DESCRIPCION AS LOV_DESCRIPCION 
					  FROM AS_CALLES C 
					 WHERE C.COD_CALLE = $cod_calle ";
		}else{
			return '';
		}
	}
}

?>