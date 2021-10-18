<?php
class dao_grupo_familiar_as {

	static public function get_grupos_familiares2($filtro = [], $orden = [])
	{

		$desde= null; 
		$hasta= null;
	    if(isset($filtro['numrow_desde'])){
	      $desde = $filtro['numrow_desde']; 
	      $hasta = $filtro['numrow_hasta'];
	      unset($filtro['numrow_desde']); 
	      unset($filtro['numrow_hasta']);
	    }
	    
	    $where = self::get_where($filtro);

	    $sql = "SELECT GF.*, to_char(gf.fecha_alta,'dd/mm/yyyy') fecha_alta_format
	    		    , to_char(gf.fecha_baja,'dd/mm/yyyy') fecha_baja_format
	    		    ,(SELECT ca.DESCRIPCION||' '||dir.nro||', '||loc.descripcion||', '||pro.DESCRIPCION||', '||pa.DESCRIPCION   
                  FROM as_calles ca, as_localidades loc, as_provincias pro, as_direcciones dir, as_barrios ba, as_paises pa
                 WHERE dir.cod_calle = ca.cod_calle
            		   AND ca.cod_localidad = loc.cod_localidad
					   AND loc.cod_provincia = pro.cod_provincia
					   AND ba.cod_barrio = dir.cod_barrio and pro.COD_PAIS = pa.COD_PAIS and id_direccion = gf.id_direccion ) direccion_format
				  FROM AS_GRUPOS_FAMILIARES GF
			 	 WHERE $where
	    ";
	    $sql = dao_varios::paginador($sql, null, $desde, $hasta, null, $orden);

        $datos = toba::db()->consultar($sql);
        return $datos;
	}

	static private function get_where ($filtro = [])
	{
	    $where = "1=1"; 
	    $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'GF', '1=1');
	    return $where;
	}

	static public function get_cantidad ($filtro = [])
	{
		$where = self::get_where($filtro);
	    $sql = "SELECT COUNT(*) cant 
		        FROM AS_GRUPOS_FAMILIARES GF
		        WHERE $where";
	    $datos = toba::db()->consultar_fila($sql);
	    return $datos['cant'];
	}

	static public function get_grupos_familiares($filtro = array()){
		$where = " 1=1 ";
		
		if (isset($filtro['id_beneficiario'])){
			//Filtra los grupos familiares en los que estuvo el beneficiario
			$where .=" and gf.id_grupo_familiar in (SELECT ID_GRUPO_FAMILIAR
														 FROM AS_INTEGRANTES
                                                      WHERE ID_BENEFICIARIO = ".$filtro['id_beneficiario'].")";
			unset($filtro['id_beneficiario']);
		}
		
		if (isset($filtro) && !empty($filtro)){
			$where .= ' and '.ctr_construir_sentencias::get_where_filtro($filtro, 'gf','1=1');
		}
		
		$sql = " SELECT  gf.*, DECODE (i.fecha_baja, NULL, 'S', 'N') AS activo,
			         DECODE (i.fecha_baja, NULL, 'Si', 'No') activo_format,
			         TO_CHAR (i.fecha_alta, 'YYYY/MM/DD') fecha_alta_integrante,
			         TO_CHAR (i.fecha_baja, 'YYYY/MM/DD') fecha_baja_integrante,
			         ben.nombre nombre_beneficiario,
			         to_CHAR(ben.nro_documento,'99G999G999G999') nro_documento,
			         ben.id_beneficiario,
			         (select nomenclatura from as_viviendas where id_vivienda = gf.id_vivienda) nomenclatura,
			         (SELECT DISTINCT    c.descripcion
			                          || ' '
			                          || d.nro
			                          || ', '
			                          || l.descripcion
			                          || ' ('
			                          || pro.descripcion
			                          || ', '
			                          || p.descripcion
			                          || ')'
			                     FROM as_direcciones d,
			                          as_calles c,
			                          as_barrios b,
			                          as_provincias pro,
			                          as_paises p,
			                          as_localidades l
			                    WHERE d.id_direccion = gf.id_direccion
			                      AND d.cod_calle = c.cod_calle
			                      AND c.cod_localidad = l.cod_localidad
			                      AND l.cod_provincia = pro.cod_provincia
			                      AND pro.cod_pais = p.cod_pais) direccion
			    FROM as_grupos_familiares gf, as_integrantes i, as_beneficiarios ben
			    where gf.id_grupo_familiar = i.id_grupo_familiar and i.id_beneficiario = ben.id_beneficiario AND $where 				
			    ORDER BY gf.id_grupo_familiar DESC";
		return toba::db()->consultar($sql);
	}
	
	static public function get_grupo_familiar ($id_grupo_familiar){
		$sql = "SELECT GRUPF.*
			  	  FROM AS_GRUPOS_FAMILIARES GRUPF
				 WHERE GRUPF.ID_GRUPO_FAMILIAR = ".quote($id_grupo_familiar);
		return toba::db()->consultar_fila($sql);
	}
	
	static public function integrante_activo ($id_grupo_familiar, $id_integrante){
		
		$sql = "SELECT NVL (SUM (1), 0) activo
				  FROM as_integrantes
				 WHERE id_beneficiario = ".quote($id_integrante)." 
				   AND id_grupo_familiar = ".quote($id_grupo_familiar)." 
				   AND fecha_baja IS NULL";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['activo'];
	}
	
	/**
	 * 
	 * Retorna los grupos familiares del beneficiario indicando si se encuentra activo o no para cada grupo
	 * @param unknown_type $id_beneficiario
	 */
	static public function get_grupos_familaires_x_beneficiario($id_beneficiario){
		if (isset($id_beneficiario)){
			$sql = "SELECT   gf.*, decode(i.fecha_baja,null,'S','N') AS activo, decode(i.fecha_baja,null,'Si','No') activo_format,
					         to_char(i.fecha_alta, 'YYYY/MM/DD') fecha_alta_integrante,
					         to_char(i.fecha_baja, 'YYYY/MM/DD') fecha_baja_integrante,
					         (select nomenclatura from as_viviendas where id_vivienda = gf.id_vivienda) nomenclatura,
					         (SELECT DISTINCT    c.descripcion
					                          || ' '
					                          || d.nro
					                          || ', '
					                          || l.descripcion
					                          || ' ('
					                          || pro.descripcion
					                          || ', '
					                          || p.descripcion
					                          || ')'
					                     FROM as_direcciones d,
					                          as_calles c,
					                          as_barrios b,
					                          as_provincias pro,
					                          as_paises p,
					                          as_localidades l
					                    WHERE d.id_direccion = gf.id_direccion
					                      AND d.cod_calle = c.cod_calle
					                      AND c.cod_localidad = l.cod_localidad
					                      AND l.cod_provincia = pro.cod_provincia
					                      AND pro.cod_pais = p.cod_pais) direccion
					    FROM as_grupos_familiares gf, as_integrantes i
					   WHERE id_beneficiario = ".quote($id_beneficiario)."
					     AND gf.id_grupo_familiar = i.id_grupo_familiar
					   order by i.fecha_alta desc, i.fecha_baja desc";
		
			return toba::db()->consultar($sql); 
		}else{
			return array();
		}
	}
	
	/**
	 * Retorna el grupo familiar activo del beneficiario 
	 */
	static public function get_grupo_familiar_x_beneficiario($id_beneficiario){
		if (!is_null($id_beneficiario) || !empty($id_beneficiario)){
			$sql = "SELECT grupf.*, cal.DESCRIPCION ||' '||dir.NRO||' ('||loc.DESCRIPCION||', '||pro.DESCRIPCION ||')' as direccion
					  FROM as_grupos_familiares grupf, as_integrantes inte, as_direcciones dir, as_barrios bar, as_calles cal, as_provincias pro, as_localidades loc, as_paises pa
					 WHERE grupf.id_grupo_familiar = inte.id_grupo_familiar
					   and grupf.ID_DIRECCION = dir.ID_DIRECCION
					   and dir.COD_CALLE = cal.COD_CALLE
					   and dir.COD_BARRIO = bar.cod_barrio
					   and bar.COD_LOCALIDAD = loc.cod_localidad
					   and loc.COD_PROVINCIA = pro.COD_PROVINCIA
					   and pro.COD_PAIS = pa.COD_PAIS
					   AND inte.id_beneficiario = ".quote($id_beneficiario)."
					   AND inte.fecha_baja IS NULL
					   AND grupf.fecha_baja IS NULL";
			return toba::db()->consultar_fila($sql);
		}else{
			return null;
		}
	}
	
	/**
	 * 
	 * Retorna los integrantes del grupo familiar
	 * @param int $id_grupo_familiar
	 */
	static public function get_integrantes ($id_grupo_familiar){
		if (!is_null($id_grupo_familiar) || !empty($id_grupo_familiar)){
			
			$sql = "SELECT inte.ID, inte.id_grupo_familiar, inte.id_beneficiario,
					       to_char(inte.fecha_alta, 'YYYY/MM/DD') fecha_alta, inte.fecha_baja, inte.observacion obs_integrante,
					       benef.id_beneficiario, benef.nombre, benef.tipo_documento,
					       benef.nro_documento, benef.tipo_fiscal, benef.clave_fiscal,
					       benef.sexo, to_char(benef.fecha_nacimiento,'YYYY/MM/DD') fecha_nacimiento, benef.email, benef.cbu,
					       benef.nivel_instruccion, benef.tipo_discapacidad,
					       benef.tipo_enfermedad, benef.embarazo, benef.meses,
					       benef.observacion obs_beneficiario, benef.afi_beneficiario,
					       benef.cod_pais, benef.fecha_residencia_loc,
					       to_char(benef.fecha_residencia_loc, 'YYYY/MM/DD') fecha_residencia_loc_format,
					       benef.fecha_residencia_pais,
					       to_char(benef.fecha_residencia_pais, 'YYYY/MM/DD') fecha_residencia_pais_format, benef.tipo_residencia,
					       benef.estado_civil,
					       (SELECT rv_meaning
					          FROM cg_ref_codes
					         WHERE rv_low_value = inte.rol AND rv_domain = 'AS_ROL') rol,
					       (SELECT rv_meaning
                              FROM cg_ref_codes
                             WHERE rv_low_value = benef.nivel_instruccion AND rv_domain = 'AS_NIVEL_INSTRUCCION') nivel_instruccion_format,
					       (SELECT rv_meaning
					          FROM cg_ref_codes
					         WHERE rv_low_value = inte.parentezco
					           AND rv_domain = 'AS_PARENTEZCO') parentezco
					  FROM as_integrantes inte, as_beneficiarios benef
					 WHERE inte.id_grupo_familiar = ".quote($id_grupo_familiar)."
					   AND inte.id_beneficiario = benef.id_beneficiario
					ORDER BY benef.nombre";
			return toba::db()->consultar($sql);
		}else{
			
			return null;
		}
	}
	
	/**
	 * Recupera integrante por Nro Documento en un Grupo Familiar determinado.
	 * @param $nro_documento
	 * @param $id_grupo_familiar
	 */
	static public function get_integrante_x_documento_en_grupo ($nro_documento, $id_grupo_familiar){
		$sql = "select int.*
				  from as_integrantes int, as_beneficiarios ben
				 where int.id_beneficiario = ben.id_beneficiario
				   and int.id_grupo_familiar = ".quote($id_grupo_familiar)." 
				   and ben.nro_documento = ".quote($nro_documento);
		$datos = toba::db()->consultar_fila($sql);
		if (!empty($datos))
			return $datos;
		else
			return 0;	
	}
	
	static public function get_integrante_x_id($id){
		$sql = "SELECT inte.ID, inte.id_grupo_familiar, inte.id_beneficiario,
					       inte.fecha_alta, inte.fecha_baja, inte.observacion obs_integrante,
					       benef.id_beneficiario, benef.nombre, benef.tipo_documento,
					       benef.nro_documento, benef.tipo_fiscal, benef.clave_fiscal,
					       benef.sexo, benef.fecha_nacimiento, benef.email, benef.cbu,
					       benef.nivel_instruccion, benef.tipo_discapacidad,
					       benef.tipo_enfermedad, benef.embarazo, benef.meses,
					       benef.observacion obs_beneficiario, benef.afi_beneficiario,
					       benef.cod_pais, benef.fecha_residencia_loc,
					       to_char(benef.fecha_residencia_loc, 'YYYY/MM/DD') fecha_residencia_loc_format,
					       benef.fecha_residencia_pais,
					       to_char(benef.fecha_residencia_pais, 'YYYY/MM/DD') fecha_residencia_pais_format, benef.tipo_residencia,
					       benef.estado_civil,
					       (SELECT rv_meaning
					          FROM cg_ref_codes
					         WHERE rv_low_value = inte.rol AND rv_domain = 'AS_ROL') rol,
					       (SELECT rv_meaning
                              FROM cg_ref_codes
                             WHERE rv_low_value = benef.nivel_instruccion AND rv_domain = 'AS_NIVEL_INSTRUCCION') nivel_instruccion_format,
					       (SELECT rv_meaning
					          FROM cg_ref_codes
					         WHERE rv_low_value = inte.parentezco
					           AND rv_domain = 'AS_PARENTEZCO') parentezco
					  FROM as_integrantes inte, as_beneficiarios benef
					 WHERE inte.id = ".quote($id)."
					   AND inte.id_beneficiario = benef.id_beneficiario
					ORDER BY benef.nombre";
		
		return toba::db()->consultar_fila($sql);
	}
	
	
	
	static public function agregar_vivienda ($id_grupo_familiar, $id_vivienda){
		if (!is_null($id_vivienda) && !is_null($id_grupo_familiar)){
			$sql = "UPDATE AS_GRUPOS_FAMILIARES SET ID_VIVIENDA = ".quote($id_vivienda)." WHERE ID_GRUPO_FAMILIAR = ".quote($id_grupo_familiar);
			return toba::db()->ejecutar($sql);
		}else{
			return 0;
		}
	}
	
	static public function tiene_igual_vivienda ($id_grupo_familiar, $id_vivienda){
		$sql = "SELECT 1 tiene
			      FROM AS_GRUPOS_FAMILIARES 
			     WHERE ID_GRUPO_FAMILIAR = $id_grupo_familiar 
			       AND ID_VIVIENDA = $id_vivienda ";
		
		$datos = toba::db()->consultar_fila($sql);
		return $datos['tiene'];		
	}
	
	static public function get_lov_grupo_familiar_x_nombre ($nombre, $filtro = array()){
		if (isset($nombre)) {
    		$trans_cod = ctr_construir_sentencias::construir_translate_ilike('GRUPF.id_grupo_familiar', $nombre);
            $trans_nom = ctr_construir_sentencias::construir_translate_ilike('GRUPF.NOMBRE', $nombre);
            $where = "($trans_cod OR $trans_nom )";
        } else {
            $where = '1=1';
        }
        
        if (!empty($filtro))
        	$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro, "GRUPF", "1=1");
        	
        $sql = "SELECT GRUPF.*, GRUPF.ID_GRUPO_FAMILIAR ||' - '|| GRUPF.NOMBRE AS LOV_DESCRIPCION
        		  FROM AS_GRUPOS_FAMILIARES GRUPF
        		 WHERE $where
         	  ORDER BY LOV_DESCRIPCION";
        return toba::db()->consultar($sql);
	}
	
	static public function get_lov_grupo_familiar_x_id ($id_grupos_familiar){
		$sql = "SELECT GRUPF.ID_GRUPO_FAMILIAR ||' - '|| GRUPF.NOMBRE AS LOV_DESCRIPCION
        		  FROM AS_GRUPOS_FAMILIARES GRUPF
        		 WHERE grupf.id_grupo_familiar = ".quote($id_grupos_familiar);
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}
	
	static public function alta_integrante ($id_grupo_familiar, $id_beneficiario, $rol, $parentezco, $fecha, $observacion, $con_transaccion = true){
		if (is_null($observacion))
			$observacion ='';
		$sql = "BEGIN :resultado := pkg_as_grupos_familiares.alta_integrante(:id_grupo_familiar, :id_beneficiario, :rol, :parentezco, :fecha, :observacion);END;";
		$parametros = array (array(  'nombre' => 'id_grupo_familiar', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 12,
                                     'valor' => $id_grupo_familiar),
		
      						 array(  'nombre' => 'id_beneficiario', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 12,
                                     'valor' => $id_beneficiario),
      						 
      						 array(  'nombre' => 'rol', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 3,
                                     'valor' => $rol),
      						 
      						 array(  'nombre' => 'parentezco', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 3,
                                     'valor' => $parentezco),
      						 
      						 array(  'nombre' => 'observacion', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4000,
                                     'valor' => $observacion),
      						 
      						  array(  'nombre' => 'fecha', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 200,
                                     'valor' => $fecha),
      						  
      						 array(  'nombre' => 'resultado', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4000,
                                     'valor' => ''));
      	$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', $con_transaccion);
      	if (isset($resultado[6]['valor']))
      		return $resultado[6]['valor'];
      	else
      		return null;	
      	
	}
	

	static public function baja_integrante ($id, $con_transaccion = true){
		
		$sql = "BEGIN :resultado := pkg_as_grupos_familiares.baja_integrante(:id);END;";
		$parametros = array (array(  'nombre' => 'id', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 12,
                                     'valor' => $id),
      						 array(  'nombre' => 'resultado', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4000,
                                     'valor' => ''));
      	$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', $con_transaccion);
      	return $resultado[1]['valor'];
	}
	
	static public function get_ui_parentezco ($valor){
		$sql = "SELECT RV_MEANING AS PARENTEZCO_FORMAT
				 FROM CG_REF_CODES
				 WHERE RV_DOMAIN = 'AS_PARENTEZCO'
				   AND RV_LOW_VALUE = ".quote($valor);
		return toba::db()->consultar_fila($sql);
	}
	
	static public function get_ui_rol ($valor){
		$sql = "SELECT RV_MEANING AS ROL_FORMAT
				 FROM CG_REF_CODES
				 WHERE RV_DOMAIN = 'AS_ROL'
				   AND RV_LOW_VALUE = ".quote($valor);
		return toba::db()->consultar_fila($sql);
	}
}

?>