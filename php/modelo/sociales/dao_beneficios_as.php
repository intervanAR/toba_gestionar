<?php
class dao_beneficios_as {
	
	public static function get_beneficios ($filtro = array()){
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
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'ben', '1=1');
		
		$sql = "SELECT BEN.COD_BENEFICIO, 
				       BEN.NOMBRE, 
				       BEN.DESCRIPCION, 
				       PRO.COD_PROGRAMA ||' - '|| PRO.nombre AS PROGRAMA,
				       TBEN.COD_TIPO_BENEFICIO ||' - '|| TBEN.DESCRIPCION AS TIPO_BENEFICIO
				  FROM AS_BENEFICIOS BEN
				       LEFT JOIN AS_TIPOS_BENEFICIOS TBEN ON TBEN.COD_TIPO_BENEFICIO = BEN.COD_TIPO_BENEFICIO
				       LEFT JOIN AS_PROGRAMAS_SOCIALES PRO ON PRO.COD_PROGRAMA = BEN.COD_PROGRAMA
				 WHERE $where
				 ORDER BY BEN.COD_BENEFICIO ASC;";
		
		$sql = dao_varios::paginador($sql, null, $desde, $hasta);
		$datos = toba::db()->consultar($sql);
		return $datos;	
	}
	
	public static function get_articulo_x_beneficio($cod_beneficio, $cod_articulo){
		
		$sql = "SELECT artben.*,
				       (SELECT rv_meaning
				          FROM cg_ref_codes
				         WHERE rv_domain = 'AS_UNIDAD_MEDIDA'
				           AND rv_low_value = art.unidad_medida) unidad_medida_format
				  FROM as_articulos_beneficios artben, as_articulos art
				 WHERE art.cod_articulo = artben.cod_articulo 
				 	   and artben.cod_beneficio = ".quote($cod_beneficio)."
				       and artben.cod_articulo = ".quote($cod_articulo);
		
		return toba::db()->consultar_fila($sql);
	}
	
	public static function get_benegicio_x_id ($id){
		$sql = "SELECT asb.*, (SELECT clase_beneficio
				                 FROM as_tipos_beneficios
				                WHERE cod_tipo_beneficio = asb.cod_tipo_beneficio) beneficio
				  FROM as_beneficios asb 
				 WHERE asb.COD_BENEFICIO = $id";
		return toba::db()->consultar_fila($sql);
	}
	
	static public function get_lov_beneficio_x_id ($cod_beneficio){
		$sql = "SELECT ben.cod_beneficio|| ' - '|| ben.descripcion|| ' - '|| tben.clase_beneficio|| ' / '|| tben.frecuencia lov_descripcion,ben.*
			    FROM as_beneficios ben, as_tipos_beneficios tben
			   WHERE ben.cod_tipo_beneficio = tben.cod_tipo_beneficio and BEN.COD_BENEFICIO = ".quote($cod_beneficio);
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}
	static public function get_lov_beneficio_x_nombre ($nombre, $filtro = array()){
		if (isset($nombre)) {
    		$trans_cod = ctr_construir_sentencias::construir_translate_ilike('BEN.COD_BENEFICIO', $nombre);
            $trans_nom = ctr_construir_sentencias::construir_translate_ilike('ben.descripcion', $nombre);
            $where = "($trans_cod OR $trans_nom )";
        } else {
            $where = '1=1';
        }
        
        $where .=" and " . ctr_construir_sentencias::get_where_filtro($filtro, 'ben', '1=1');
        
		$sql = "SELECT ben.cod_beneficio
			         || ' - '
			         || ben.descripcion
			         || ' - '
			         || tben.clase_beneficio
			         || ' / '
			         || tben.frecuencia lov_descripcion,
			         ben.*
			     FROM as_beneficios ben, as_tipos_beneficios tben, as_programas_sociales pro
   				WHERE ben.cod_tipo_beneficio = tben.cod_tipo_beneficio
         		  and ben.cod_programa = pro.cod_programa and pro.ACTIVO = 'S' and $where
			  	 ORDER BY LOV_DESCRIPCION";
		return toba::db()->consultar($sql);
	}
	
	static public function get_lov_programa_x_codigo ($cod_programa){
		$sql = "SELECT pro.COD_programa ||' - '|| pro.nombre LOV_DESCRIPCION 
                    FROM AS_programas_sociales pro
                 WHERE pro.cod_programa = ".quote($cod_programa);
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}
	
	static public function get_lov_programa_x_nombre ($nombre, $filtro = array()){
		if (isset($nombre)) {
    		$trans_cod = ctr_construir_sentencias::construir_translate_ilike('pro.cod_programa', $nombre);
            $trans_nom = ctr_construir_sentencias::construir_translate_ilike('pro.descripcion', $nombre);
            $where = "($trans_cod OR $trans_nom )";
        } else {
            $where = '1=1';
        }
        
        $where .=" and " . ctr_construir_sentencias::get_where_filtro($filtro, 'pro', '1=1');
        
		$sql = "SELECT pro.*, pro.cod_programa ||' - '|| pro.nombre LOV_DESCRIPCION
			  	 FROM AS_programas_sociales pro
			  	 WHERE $where
			  	 ORDER BY LOV_DESCRIPCION";
		return toba::db()->consultar($sql);
	}
	
	
	
	public static function get_tipo_beneficio_x_id ($id){
		$sql = "SELECT B.*, S.COD_SECTOR ||' - '|| S.NOMBRE SECTOR_NOMBRE, to_char(sysdate, 'YYYY') anio
			      FROM AS_TIPOS_BENEFICIOS B, AS_SECTORES S
			     WHERE B.COD_SECTOR = S.COD_SECTOR AND B.COD_TIPO_BENEFICIO = $id ";
		return toba::db()->consultar_fila($sql);
	}
	
	public static function get_tipo_beneficio_x_beneficio ($cod_beneficio){
		$sql = "SELECT b.*, s.cod_sector || ' - ' || s.nombre sector_nombre,
				       TO_CHAR (SYSDATE, 'YYYY') anio
				  FROM as_tipos_beneficios b, as_sectores s, as_beneficios benef
				 WHERE b.cod_sector = s.cod_sector
				   AND b.cod_tipo_beneficio = benef.cod_tipo_beneficio
				   AND benef.cod_beneficio = $cod_beneficio ";
		return toba::db()->consultar_fila($sql);
	}
	
	public static function get_monto_activo ($cod_beneficio){
		$sql = "select *
				  from as_montos 
				 where cod_beneficio = ".quote($cod_beneficio)." and activo = 'S'";
		$datos = toba::db()->consultar_fila($sql);
		if (!empty($datos))
			return $datos;
		else 	
			return null;
	}
	
	public static function get_lov_tipos_beneficios_x_id ($id){
		if (!is_null($id)){
			$sql = "SELECT TBF.COD_TIPO_BENEFICIO ||' - '|| TBF.DESCRIPCION AS LOV_DESCRIPCION
					FROM AS_TIPOS_BENEFICIOS TBF
					WHERE TBF.COD_TIPO_BENEFICIO = $id ";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}else{
			return null;
		}
	}
	
	public static function get_lov_tipos_beneficios_x_nombre ($nombre, $filtro = array()){
		if (isset($nombre)) {
    		$trans_cod = ctr_construir_sentencias::construir_translate_ilike('tbf.cod_tipo_beneficio', $nombre);
            $trans_nom = ctr_construir_sentencias::construir_translate_ilike('tbf.descripcion', $nombre);
            $where = "($trans_cod OR $trans_nom )";
        } else {
            $where = '1=1';
        }
        if (!empty($filtro))
        	$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro, "tbf", "1=1");
        	
        $sql = "SELECT TBF.*, TBF.COD_TIPO_BENEFICIO ||' - '|| TBF.DESCRIPCION AS LOV_DESCRIPCION
				FROM AS_TIPOS_BENEFICIOS TBF
				WHERE $where ORDER BY LOV_DESCRIPCION";
        $datos = toba::db()->consultar($sql);
	    return $datos;
	}
	
	public static function get_tipos_beneficios ($filtro = array()){
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
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'TBENF', '1=1');
			
		$sql = "SELECT TBENF.COD_TIPO_BENEFICIO, 
					   SUBSTR(TBENF.DESCRIPCION,0, 50) AS DESCRIPCION, 
					   SEC.COD_SECTOR || ' - ' || SEC.NOMBRE AS SECTOR,
					   TBENF.FRECUENCIA, TBENF.MES_INICIO, TBENF.MESES,
				       CASE WHEN TBENF.MONTO_FIJO = 'S' THEN 
				           'Si'
				       ELSE
				           'No'
				       END MONTO_FIJO,
				       (SELECT RV_MEANING 
				          FROM CG_REF_CODES 
				         WHERE RV_LOW_VALUE = TBENF.CLASE_BENEFICIO AND RV_DOMAIN = 'AS_BENEFICIO' ) AS clase_benef_format,
				       (SELECT RV_MEANING FROM CG_REF_CODES WHERE RV_LOW_VALUE = TBENF.TIPO_BECA AND RV_DOMAIN = 'AS_TIPO_BECA' ) AS TIPO_BECA_FORMAT
	              FROM AS_TIPOS_BENEFICIOS TBENF
	                   LEFT JOIN AS_SECTORES SEC ON SEC.COD_SECTOR = TBENF.COD_SECTOR
	            WHERE $where
	            ORDER BY COD_TIPO_BENEFICIO ASC";
		
		$sql= dao_varios::paginador($sql, null, $desde, $hasta);
		$datos = toba::db()->consultar($sql);
		return $datos;	
	}
	
	public static function get_ui_beneficio ($cod_tipo_beneficio){
		//Retorna 'beneficio' de la tabla 'as_tipos_beneficio'
		$sql = "SELECT CLASE_BENEFICIO ui_beneficio
				  FROM AS_TIPOS_BENEFICIOS 
				 WHERE COD_TIPO_BENEFICIO = ".quote($cod_tipo_beneficio);
		return toba::db()->consultar_fila($sql);
	}
	
}
?>