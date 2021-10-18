<?php
class dao_asignaciones_as {
	
	public static function get_tipos_asignaciones ($filtro = array()){
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
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'TASG', '1=1');
		
		$sql = "SELECT TASG.COD_TIPO_ASIGNACION,
				       TASG.DESCRIPCION,
				       TASG.PUNTAJE, 
				       TASG.ANIOS_PUNTAJE,
				       TASG.ACTIVO,
				       CASE WHEN TASG.ACTIVO = 'S' THEN
				            'Si'
				       ELSE
				            'No'
				       END ACTIVO_FORMAT,
				       (SELECT RV_MEANING 
				          FROM CG_REF_CODES 
				          WHERE RV_LOW_VALUE = TASG.TIPO AND RV_DOMAIN = 'AS_TIPO_ASIGNACION') AS TIPO_FORMAT
				FROM AS_TIPOS_ASIGNACIONES TASG
				  WHERE $where
			  ORDER BY COD_TIPO_ASIGNACION ASC";
		
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	
	public static function activar_tipo_asignacion($cod_tipo_asignacion){
		$sql = "UPDATE AS_TIPOS_ASIGNACIONES SET ACTIVO = 'S' WHERE COD_TIPO_ASIGNACION = $cod_tipo_asignacion";
		return toba::db()->ejecutar($sql);
	}
	public static function desactivar_tipo_asignacion($cod_tipo_asignacion){
		$sql = "UPDATE AS_TIPOS_ASIGNACIONES SET ACTIVO = 'N' WHERE COD_TIPO_ASIGNACION = $cod_tipo_asignacion";
		return toba::db()->ejecutar($sql);
	}
	
}
?>