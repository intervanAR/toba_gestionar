<?php

class dao_ambitos_sectores {

    public static function get_ambitos_compra($filtro = array(), $fuente=null) {
        $where = " 1=1 ";
        
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ca', '1=1');
        $sql_sel = "SELECT  ca.*,
        					decode (ca.activo,'S','Si','No') activo_format,
        					(select cod_unidad_administracion ||' - '|| descripcion from kr_unidades_administracion 
        					  where cod_unidad_administracion = ca.cod_unidad_administracion) unidad_administracion,
							ca.cod_ambito || ' - ' || ca.descripcion lov_descripcion
					FROM co_ambitos ca
					WHERE $where
					ORDER BY ca.cod_ambito ASC;";
        $datos = toba::db($fuente)->consultar($sql_sel);
		return $datos;
    }
	
	public static function get_sectores_compra($filtro = array(), $fuente=null) {
        $where = " 1=1 ";
    
		if (isset($filtro['usuario_sector']) && !empty($filtro['usuario_sector'])){
			$where .= " and cod_sector = pkg_usuarios.sector_usuario(upper('".$filtro['usuario_sector']."'))";
			unset($filtro['usuario_sector']);
		}        
        
		if (isset($filtro['cod_ambito']) && $filtro['cod_ambito'] == 'null'){
			$where .= " and cs.cod_ambito is null ";
			unset($filtro['cod_ambito']);
		}
		
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'cs', '1=1');
        $sql_sel = "SELECT  cs.*,
							cs.cod_sector || ' - ' || cs.descripcion lov_descripcion,
							(SELECT cod_sector || ' - ' || descripcion
					           FROM co_sectores
					          WHERE cod_sector = cs.cod_sector_padre) cod_sector_padre_format,
					        (SELECT cod_nivel || ' - ' || descripcion
					           FROM co_niveles
					          WHERE cod_nivel = cs.cod_nivel) cod_nivel_format,
					        (SELECT cod_ambito || ' - ' || descripcion
					           FROM co_ambitos
					          WHERE cod_ambito = cs.cod_ambito) cod_ambito_format,
					        (SELECT cod_unidad_ejecutora || ' - ' || descripcion
					          FROM KR_UNIDADES_EJECUTORAS
					         WHERE cod_unidad_ejecutora = cs.cod_unidad_ejecutora) cod_unidad_ejecutora_format
					FROM co_sectores cs
					WHERE $where
					ORDER BY cs.cod_sector ASC;";
        $datos = toba::db($fuente)->consultar($sql_sel);
		return $datos;
    }

    public static function get_lov_sectores_compra($nombre, $filtro= array(), $fuente=null) {
        $where = " 1=1 ";
        
	 	if (isset($nombre)) {
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('cod_sector', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
            $where = "($trans_descripcion OR $trans_nro)";
        } else {
            $where = '1=1';
        }        
       
        $where .= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'cs', '1=1');  
        
		
		
        $sql_sel = "SELECT  cs.*,
							cs.cod_sector || ' - ' || cs.descripcion lov_descripcion,
							(SELECT cod_sector || ' - ' || descripcion
					           FROM co_sectores
					          WHERE cod_sector = cs.cod_sector_padre) cod_sector_padre_format,
					        (SELECT cod_nivel || ' - ' || descripcion
					           FROM co_niveles
					          WHERE cod_nivel = cs.cod_nivel) cod_nivel_format,
					        (SELECT cod_ambito || ' - ' || descripcion
					           FROM co_ambitos
					          WHERE cod_ambito = cs.cod_ambito) cod_ambito_format,
					        (SELECT cod_unidad_ejecutora || ' - ' || descripcion
					          FROM KR_UNIDADES_EJECUTORAS
					         WHERE cod_unidad_ejecutora = cs.cod_unidad_ejecutora) cod_unidad_ejecutora_format
					FROM co_sectores cs
					WHERE $where
					ORDER BY cs.cod_sector ASC;";
        $datos = toba::db($fuente)->consultar($sql_sel);
		return $datos;
    }
	
	public static function get_sectores_seq_compra($filtro = array()) {
        $where = " 1=1 ";
		
		if (isset($filtro['activo'])) {
			$where .= " AND css.activo = 'S' AND cs.activo = 'S' ";
			unset($filtro['activo']);
		}
		if (isset($filtro['imputable'])) {
			$where .= " AND Pkg_Sectores.imputable(cs.cod_sector) = 'S' ";
			unset($filtro['imputable']);
		}
		if (isset($filtro['sin_ue'])) {
			$where .= " AND cs.COD_UNIDAD_EJECUTORA is not null ";
			unset($filtro['sin_ue']);
		}
		if (isset($filtro['usuario_ambito'])) {
			$where .= " AND INSTR(Pkg_Usuarios.ambitos_usuario (" . quote(toba::usuario()->get_id()) . "), cs.cod_ambito) >0
						AND cs.cod_sector in (select cod_sector from co_usuarios_sectores where  USUARIO = upper(" . quote(toba::usuario()->get_id()) . ")) ";
			unset($filtro['usuario_ambito']);
		}
		
		if (isset($filtro['usuario_sector'])) {
			$where .= " AND cs.cod_sector in (select cod_sector from co_usuarios_sectores where  USUARIO = upper(" . quote(toba::usuario()->get_id()) . ")) ";
			unset($filtro['usuario_sector']);
		}
		
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'css', '1=1');
        $sql_sel = "SELECT  css.*,
							css.cod_sector || '".apex_qs_separador."' || css.seq_sector cod_seq_sector,
							css.cod_sector || '-' || css.descripcion lov_descripcion,
							cs.cod_sector || ' - ' || cs.descripcion lov_descripcion_sec
					FROM co_sectores_seq css
					JOIN co_sectores cs ON (cs.cod_sector = css.cod_sector)
					WHERE $where
					ORDER BY css.cod_sector ASC;";
        $datos = toba::db()->consultar($sql_sel);
		return $datos;
    }
	
	public static function get_ambitos_usuarios_compra($filtro = array()) {
        $where = " 1=1 ";
        
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'vcua', '1=1');
        $sql_sel = "SELECT  vcua.*,
							vcua.cod_ambito || '".apex_qs_separador."' || vcua.seq_ambito cod_seq_ambito,
							vcua.cod_ambito || ' - ' || vcua.amb_descripcion lov_descripcion
					FROM V_CO_USUARIOS_AMBITOS vcua
					WHERE $where
					ORDER BY vcua.cod_ambito ASC;";
        $datos = toba::db()->consultar($sql_sel);
		return $datos;
    }
	
	public static function get_sector_compra($cod_sector) {
        if (isset($cod_sector)) {
			$sql_sel = "SELECT  cs.*,
								cs.cod_sector || '-' || cs.descripcion lov_descripcion
						FROM co_sectores cs
						WHERE cs.cod_sector = " . quote($cod_sector) . ";";
			$datos = toba::db()->consultar_fila($sql_sel);
			return $datos;
		} else {
			return array();
		}
    }
    
    public static function get_v_usuario_sectores(){
    	$sql = "select *
    			from v_co_usuarios_sectores
    			where usuario = upper('".toba::usuario()->get_id()."')";
    	return toba::db()->consultar_fila($sql);
    }
    
    static public function get_lov_sectores_seq_compra_x_nombre ($nombre, $filtro = [])
    {
    	$where ="";
		if (isset($nombre)) {
			$trans_id = ctr_construir_sentencias::construir_translate_ilike('sesq.cod_sector', $nombre);
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('sesq.descripcion', $nombre);
			
			$where = "($trans_id OR $trans_nro)";
		} else {
			$where = '1=1';
		}

    	$usuario = toba::usuario()->get_id();
    	$sql = "SELECT sesq.*, sesq.cod_sector || '".apex_qs_separador."'|| sesq.seq_sector cod_seq_sector, sesq.cod_sector ||' - '|| sesq.descripcion lov_descripcion
				  FROM co_sectores_seq sesq, co_sectores l_sec
				 WHERE l_sec.cod_sector = sesq.cod_sector
				   AND sesq.activo = 'S'
				   AND l_sec.cod_unidad_ejecutora IS NOT NULL
				   AND pkg_sectores.imputable (l_sec.cod_sector) = 'S'
				   AND INSTR (pkg_usuarios.ambitos_usuario (upper('".$usuario."')), l_sec.cod_ambito) > 0 AND l_sec.activo = 'S' AND l_sec.cod_sector IN (SELECT cod_sector FROM co_usuarios_sectores WHERE usuario = UPPER ('".$usuario."'))
				   and $where"; //echo $sql;
    	return toba::db()->consultar($sql);

    }

    static public function get_lov_sectores_seq_compra_x_cod_seq_sector ($cod_seq_sector)
    {

    	$claves = explode(apex_qs_separador, $cod_seq_sector);
        if (count($claves) == 2) {
                $cod_sector = $claves[0];
                $seq_sector = $claves[1];
        } 
        
        $sql = "SELECT sesq.cod_sector ||' - '|| sesq.descripcion lov_descripcion
				  FROM co_sectores_seq sesq
				 WHERE sesq.cod_sector = $cod_sector and sesq.seq_sector = $seq_sector";
	 	$datos = toba::db()->consultar_fila($sql);
	 	return $datos['lov_descripcion'];

    }

    //***** ABMC Unidades  ******
    static public function get_unidades ($filtro = [])
    {
    	$where = " 1=1 ";
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'cu', '1=1');
    	$sql ="SELECT cu.*, cu.cod_unidad ||' - '|| cu.descripcion lov_descripcion
    			 FROM co_unidades cu 
    		    WHERE $where 
    		 order by cu.cod_unidad asc ";

		return toba::db()->consultar($sql);
    }

    static public function agregar_unidad ($cod_unidad, $descripcion){
    	try {

	    	$sql = "INSERT INTO CO_UNIDADES (COD_UNIDAD, DESCRIPCION) VALUES ($cod_unidad,'$descripcion')";
	    	$result = toba::db()->ejecutar($sql);

	    } catch (Exception $e) {
	    		toba::notificacion()->error($e->getMessage() );
	    }
    }

    static public function eliminar_unidad ($cod_unidad)
    {
    	if (!empty($cod_unidad)){
	    	try {

	    		$sql = "DELETE FROM co_unidades WHERE COD_UNIDAD = $cod_unidad";
	    		$result = toba::db()->ejecutar($sql);
	    		
	    	} catch (Exception $e) {
	    		toba::notificacion()->error($e->getMessage() );
	    	}
    	}
    }

    static public function get_niveles ()
    {
    	$sql = "SELECT niv.*, niv.cod_nivel ||' - '|| niv.descripcion descripcion2
    			  from co_niveles niv
    			  ";

		return toba::db()->consultar($sql);
    }

    public static function get_lov_sector_padre_x_cod_nivel($cod_nivel) {
        $where = " 1=1 ";
    	
		if (isset($cod_nivel)){
			$cod_nivel = $cod_nivel - 1;
			$where .= " and cod_nivel = $cod_nivel";
			
		}

        $sql_sel = "SELECT  cs.*,
							cs.cod_sector || ' - ' || cs.descripcion lov_descripcion,
							(SELECT cod_sector || ' - ' || descripcion
					           FROM co_sectores
					          WHERE cod_sector = cs.cod_sector_padre) cod_sector_padre_format,
					        (SELECT cod_nivel || ' - ' || descripcion
					           FROM co_niveles
					          WHERE cod_nivel = cs.cod_nivel) cod_nivel_format,
					        (SELECT cod_ambito || ' - ' || descripcion
					           FROM co_ambitos
					          WHERE cod_ambito = cs.cod_ambito) cod_ambito_format,
					        (SELECT cod_unidad_ejecutora || ' - ' || descripcion
					          FROM KR_UNIDADES_EJECUTORAS
					         WHERE cod_unidad_ejecutora = cs.cod_unidad_ejecutora) cod_unidad_ejecutora_format
					FROM co_sectores cs
					WHERE $where
					ORDER BY cs.cod_sector ASC;";
        $datos = toba::db()->consultar($sql_sel);
		return $datos;
    }
}

?>
