<?php
class dao_solicitudes_as {
	
	
	
	static public function get_solicitud ($id_solicitud){
		if (!is_null($id_solicitud)){
			$sql = "select * 
					  from as_solicitudes 
					 where id_solicitud = ".quote($id_solicitud);
			return toba::db()->consultar_fila($sql);
		}else{
			return array();
		}
	}
	
	static public function get_articulos_aprobados ($id_solicitud){
		$sql = "SELECT aap.*, art.*,
				       (SELECT rv_meaning
				          FROM cg_ref_codes
				         WHERE rv_domain = 'AS_UNIDAD_MEDIDA'
				           AND rv_low_value = art.unidad_medida) unidad_medida_format
				  FROM as_articulos_aprobados aap, as_articulos art
				 WHERE aap.cod_articulo = art.cod_articulo and aap.id_solicitud = ".quote($id_solicitud)."
				 ORDER BY art.descripcion";
		return toba::db()->consultar($sql);
	}
	
	static public function actualizar_resumen_tecnico($id_solicitud, $resumen){
		$sql = "UPDATE AS_SOLICITUDES SET RESUMEN_TECNICO = ".quote($resumen)." WHERE ID_SOLICITUD = ".quote($id_solicitud);
		return toba::db()->ejecutar($sql);
	}
	
	static public function actualizar_resumen_social($id_solicitud, $resumen){
		$sql = "UPDATE AS_SOLICITUDES SET RESUMEN_SOCIAL = ".quote($resumen)." WHERE ID_SOLICITUD = ".quote($id_solicitud);
		return toba::db()->ejecutar($sql);
	}
	static public function vincular_vivienda($id_solicitud, $id_vivienda){
		$sql = "UPDATE AS_SOLICITUDES SET ID_VIVIENDA = ".quote($id_vivienda)." WHERE ID_SOLICITUD = ".quote($id_solicitud);
		return toba::db()->ejecutar($sql);
	}
	static public function vincular_vivienda_con($id_solicitud, $id_vivienda){
		$sql = "UPDATE AS_SOLICITUDES SET ID_VIVIENDA_CON = ".quote($id_vivienda)." WHERE ID_SOLICITUD = ".quote($id_solicitud);
		return toba::db()->ejecutar($sql);
	}
	static public function get_solicitudes ($filtro = array()){
		$where = "  1=1 ";
		
		if (isset($filtro['beneficiarios']) && !empty($filtro['beneficiarios'])){
			$where .= " and sol.id_beneficiario in (".$filtro['beneficiarios'].")";
			unset($filtro['beneficiarios']);
		}
		
		if (isset($filtro['para_seleccion']) && !empty($filtro['para_seleccion'])){ 
			$where .= " and sol.id_solicitud not in (select id_solicitud from AS_SOLICITUDES_SELECCIONADAS where id_seleccion = ".$filtro['para_seleccion'].")";
			$where .= " and sol.COD_BENEFICIO in (select b.cod_beneficio
                              from as_beneficios b, as_tipos_beneficios tb 
                             where b.cod_tipo_beneficio = tb.cod_tipo_beneficio and tb.cod_tipo_beneficio = ".quote($filtro['cod_tipo_beneficio']).")";
			unset($filtro['para_seleccion']);
			unset($filtro['cod_tipo_beneficio']);
		}
		
		if (isset($filtro) )
			$where .= " AND ". ctr_construir_sentencias::get_where_filtro($filtro, 'SOL', '1=1');
		
		$sql = "SELECT sol.*, TO_CHAR (sol.fecha_estado, 'YYYY/MM/DD') fecha_estado_format,
				       TO_CHAR (sol.fecha_inicio, 'YYYY/MM/DD') fecha_inicio_format,
				       TO_CHAR (sol.fecha_fin, 'YYYY/MM/DD') fecha_fin_format,
				       TO_CHAR (sol.fecha_inscripcion, 'YYYY/MM/DD') fecha_inscripcion_format,
				       CASE sol.reinscripcion
				          WHEN 'S'
				             THEN 'Si'
				          ELSE 'No'
				       END reinscripcion_format,
				       CASE sol.renovacion
				          WHEN 'S'
				             THEN 'Si'
				          ELSE 'No'
				       END renovacion_format,
				       CASE sol.beneficiario_prog_ant
				          WHEN 'S'
				             THEN 'Si'
				          ELSE 'No'
				       END beneficiario_prog_ant_format,
				       sec.cod_sector || ' - ' || sec.nombre sector_format,
				       ben.nombre || ' - ' || ben.nro_documento beneficiario_format,
				       ben.nombre nombre_beneficiario,
				       ben.nro_documento nro_documento_ben,
				          beneficio.cod_beneficio
				       || ' - '
				       || beneficio.descripcion beneficio_format,
				       (SELECT rv_meaning
				          FROM cg_ref_codes
				         WHERE rv_low_value = sol.estado
				           AND rv_domain = 'AS_ESTADO_SOLICITUD') estado_format,
       				   GRUPF.NOMBRE NOMBRE_GRUPO_FAMILIAR,
       				   (select BENPA.NOMBRE ||' - '|| BENPA.NRO_DOCUMENTO 
                          from as_beneficiarios benpa 
                         where benpa.id_beneficiario = sol.id_beneficiario_pago) beneficiario_pago_format
				  FROM as_solicitudes sol,
                       AS_GRUPOS_FAMILIARES GRUPF,
                       as_sectores sec,
                       as_beneficiarios ben,
                       as_beneficios beneficio
                 WHERE sol.cod_sector = sec.cod_sector
                   AND sol.id_beneficiario = ben.id_beneficiario
                   and SOL.ID_GRUPO_FAMILIAR = GRUPF.ID_GRUPO_FAMILIAR
                   AND sol.cod_beneficio = beneficio.cod_beneficio AND $where  
			  ORDER BY ID_SOLICITUD DESC ";
		$sql_pd = toba::perfil_de_datos()->filtrar($sql);
		return toba::db()->consultar($sql_pd);
		
	}
	
	
	static public function get_lov_solicitudes_x_nombre ($nombre, $filtro = array()){
		if (isset($nombre)) {
    		$trans_cod = ctr_construir_sentencias::construir_translate_ilike('SOL.numero', $nombre);
            $trans_nom = ctr_construir_sentencias::construir_translate_ilike('ben.nombre', $nombre);
            $trans_doc = ctr_construir_sentencias::construir_translate_ilike('ben.nro_documento', $nombre);
            $where = "($trans_cod OR $trans_nom OR $trans_doc)";
        } else {
            $where = '1=1';
        }
        
        //Los para ordenes de entrega (beneficio de INS o MAT).
        if (isset($filtro['orden_entrega'])){
        	$where .=" and sol.cod_beneficio IN (SELECT benef.cod_beneficio
										           FROM as_beneficios benef, as_tipos_beneficios tben
										          WHERE benef.cod_tipo_beneficio = tben.cod_tipo_beneficio
										            AND tben.clase_beneficio IN ('MAT', 'INS'))";
        	unset($filtro['orden_entrega']);
        }
        
        if (!empty($filtro))
        	$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro, "SOL", "1=1");
        	
        $sql = "SELECT sol.*, sol.numero || ' - '|| ben.nombre || ' - '|| ben.nro_documento ||' - '|| b.descripcion lov_descripcion
				  FROM as_solicitudes sol, as_beneficiarios ben, as_beneficios b
				 WHERE sol.id_beneficiario = ben.id_beneficiario and sol.cod_beneficio = b.cod_beneficio and $where  
			  ORDER BY lov_descripcion DESC ";
        
        $datos = toba::db()->consultar($sql);
	    return $datos;
	}
	
	static public function get_lov_solicitud_x_id ($id_solicitud){
		
		$sql = "SELECT sol.*, sol.numero || ' - '|| ben.nombre || ' - '|| ben.nro_documento ||' - '|| b.descripcion lov_descripcion
				  FROM as_solicitudes sol, as_beneficiarios ben, as_beneficios b
				 WHERE sol.id_beneficiario = ben.id_beneficiario and sol.cod_beneficio = b.cod_beneficio and SOL.ID_SOLICITUD = ".quote($id_solicitud);
		
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}
	
	
	static public function get_lov_articulos_aprobados_x_nombre ($nombre , $filtro = array()){
		if (isset($nombre)) {
    		$trans_cod = ctr_construir_sentencias::construir_translate_ilike('ART.cod_ARTICULO', $nombre);
            $trans_nom = ctr_construir_sentencias::construir_translate_ilike('ART.descripcion', $nombre);
            $where = "($trans_cod OR $trans_nom )";
        } else {
            $where = '1=1';
        }
        
        if (isset($filtro['id_solicitud'])){
        	$where .= " and ARTAP.ID_SOLICITUD = ".quote($filtro['id_solicitud']);

	        if (isset($filtro['saldo_positivo'])){
	        	$where .=" and PKG_AS_SOLICITUDES.entregados_restantes(".quote($filtro['id_solicitud']).", art.cod_articulo) > 0 ";
	        	unset($filtro['saldo_positivo']);
	        }
        	unset($filtro['id_solicitud']);
        }


        
        if (!empty($filtro))
        	$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro, "ART", "1=1");
        	
        $sql = "SELECT ART.*, ART.COD_ARTICULO ||' - '|| ART.DESCRIPCION AS LOV_DESCRIPCION
				FROM AS_ARTICULOS ART, AS_ARTICULOS_APROBADOS ARTAP
				WHERE $where AND ART.COD_ARTICULO = ARTAP.COD_ARTICULO 
				ORDER BY ART.DESCRIPCION";
        
        $datos = toba::db()->consultar($sql);
	    return $datos;
	}
	
	
	public static function get_ui_beneficio ($cod_beneficio){
		//Retorna el codigo tipo beneficio ('MAT', 'BEC' ,...)
		$sql = "SELECT TIP.CLASE_BENEFICIO ui_beneficio
				  FROM AS_TIPOS_BENEFICIOS TIP, AS_BENEFICIOS BEN
				  WHERE BEN.COD_TIPO_BENEFICIO = TIP.COD_TIPO_BENEFICIO and ben.cod_beneficio = $cod_beneficio";
		$datos = toba::db()->consultar_fila($sql);
		return $datos;
	}
	
	static public function asociar_vivienda ($id_solicitud, $id_vivienda,  $con_transaccion = true){
		$sql = "BEGIN :resultado := pkg_as_solicitudes.asociar_vivienda(:id_solicitud, :id_vivienda);END;";
		$parametros = array ( array(  'nombre' => 'id_solicitud', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 12,
                                     'valor' => $id_solicitud),
		
      						 array(  'nombre' => 'id_vivienda', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 12,
                                     'valor' => $id_vivienda),
      						 
      						 array(  'nombre' => 'resultado', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4000,
                                     'valor' => ''));
      	$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', $con_transaccion);
      	return $resultado[2]['valor'];
	} 
	
	static public function desvincular_vivienda ($id_solicitud, $con_transaccion = true){
		$sql = "BEGIN :resultado := pkg_as_solicitudes.desvincular_vivienda(:id_solicitud);END;";
		$parametros = array ( array(  'nombre' => 'id_solicitud', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 12,
                                     'valor' => $id_solicitud),
		
      						 array(  'nombre' => 'resultado', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4000,
                                     'valor' => ''));
      	$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', $con_transaccion);
      	return $resultado[1]['valor'];
	} 
	
	static public function asociar_vivienda_con ($id_solicitud, $id_vivienda,  $con_transaccion = true){
		$sql = "BEGIN :resultado := pkg_as_solicitudes.asociar_vivienda_con(:id_solicitud, :id_vivienda);END;";
		$parametros = array ( array( 'nombre' => 'id_solicitud', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 12,
                                     'valor' => $id_solicitud),
		
      						 array(  'nombre' => 'id_vivienda', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 12,
                                     'valor' => $id_vivienda),
      						 
      						 array(  'nombre' => 'resultado', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4000,
                                     'valor' => ''));
      	$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', $con_transaccion);
      	return $resultado[2]['valor'];
	} 
	
	static public function desvincular_vivienda_con ($id_solicitud, $con_transaccion = true){
		$sql = "BEGIN :resultado := pkg_as_solicitudes.desvincular_vivienda_con(:id_solicitud);END;";
		$parametros = array ( array( 'nombre' => 'id_solicitud', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 12,
                                     'valor' => $id_solicitud),
		
      						 array(  'nombre' => 'resultado', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4000,
                                     'valor' => ''));
      	$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', $con_transaccion);
      	return $resultado[1]['valor'];
	} 
	
	static public function evaluar_solicitud ($id_solicitud, $con_transaccion = true){
		$sql = "BEGIN :resultado := pkg_as_solicitudes.evaluar_solicitud(:id_solicitud);END;";
		$parametros = array ( array(  'nombre' => 'id_solicitud', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 12,
                                     'valor' => $id_solicitud),
		
      						 array(  'nombre' => 'resultado', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4000,
                                     'valor' => ''));
      	$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', $con_transaccion);
      	return $resultado[1]['valor'];
	} 
	
	static public function aprobar_solicitud ($id_solicitud, $con_transaccion = true){
		$sql = "BEGIN :resultado := pkg_as_solicitudes.aprobar_solicitud(:id_solicitud);END;";
		$parametros = array ( array(  'nombre' => 'id_solicitud', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 12,
                                     'valor' => $id_solicitud),
		
      						 array(  'nombre' => 'resultado', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4000,
                                     'valor' => ''));
      	$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', $con_transaccion);
      	return $resultado[1]['valor'];
	} 
	
	static public function rechazar_solicitud ($id_solicitud, $fecha, $con_transaccion = true){
		$sql = "BEGIN :resultado := pkg_as_solicitudes.rechazar_solicitud(:id_solicitud, :fecha);END;";
		$parametros = array ( array(  'nombre' => 'id_solicitud', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 12,
                                     'valor' => $id_solicitud),
		
							 array(  'nombre' => 'fecha', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 100,
                                     'valor' => $fecha),
		
      						 array(  'nombre' => 'resultado', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4000,
                                     'valor' => ''));
      	$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', $con_transaccion);
      	return $resultado[2]['valor'];
	} 
	
	static public function estado_carga ($id_solicitud, $con_transaccion = true){
		$sql = "BEGIN :resultado := pkg_as_solicitudes.estado_carga(:id_solicitud);END;";
		$parametros = array ( array(  'nombre' => 'id_solicitud', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 12,
                                     'valor' => $id_solicitud),
		
      						 array(  'nombre' => 'resultado', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4000,
                                     'valor' => ''));
      	$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', $con_transaccion);
      	return $resultado[1]['valor'];
	} 

	static public function permite_renovar ($id_solicitud){
		$sql ="SELECT 'S' permite
				  FROM as_solicitudes
				 WHERE estado = 'APR'
				   and fecha_fin <= sysdate
				   and id_solicitud = $id_solicitud";
		$datos = toba::db()->consultar_fila($sql);
		if ($datos['permite'] == 'S')
			return true;
		else
			return false;
	}

	static public function permite_reinscribir ($id_solicitud){
		$sql ="SELECT 'S' permite
				  FROM as_solicitudes
				 WHERE estado = 'REC'
				  and id_solicitud = $id_solicitud";
		$datos = toba::db()->consultar_fila($sql);

		if ($datos['permite'] == 'S')
			return true;
		else
			return false;
	}
	
}

?>