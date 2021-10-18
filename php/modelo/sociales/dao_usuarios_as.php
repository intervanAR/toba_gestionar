<?php
class dao_usuarios_as {
	
	public static function get_usuarios($filtro = array()){
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
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'U', '1=1');
			
		$sql = "SELECT U.*, S.NOMBRE AS NOMBRE_SECTOR
				  FROM AS_USUARIOS U, AS_SECTORES S
				 WHERE U.COD_SECTOR = S.COD_SECTOR AND $where 
				 ORDER BY USUARIO ASC";
		return toba::db()->consultar($sql);
	}
	
	static function get_datos_usuario($nombre_usuario, $fuente=null)
	{
		$sql = "SELECT * 
				FROM AS_USUARIOS 
				WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")";
	    return toba::db($fuente)->consultar_fila($sql);
	}
	
	static function get_sector($nombre_usuario)
	{
		$sql = "SELECT cod_sector 
				FROM AS_USUARIOS 
				WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")";
	    $datos = toba::db()->consultar_fila($sql);
	    return $datos['cod_sector'];
	}
	
	static function get_sectores_usuarios($nombre_usuario, $fuente=null)
	{
		$sql = "SELECT U.*, S.nombre nombre_sector
				FROM AS_USUARIOS_SECTORES U, AS_SECTORES S 
				WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")
				   AND u.cod_sector = s.cod_sector";
	    return toba::db($fuente)->consultar($sql);
	}
	
}

?>