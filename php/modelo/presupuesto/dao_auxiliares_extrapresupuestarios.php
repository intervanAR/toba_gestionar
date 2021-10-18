<?php

class dao_auxiliares_extrapresupuestarios
{
	static public function get_datos($filtro=array())
	{
    	$where = ' 1=1 ';

    	if (isset($filtro['id_padre'])){
    		$where .= " and kae.cod_auxiliar_padre = ".$filtro['id_padre'];
    		unset($filtro['id_padre']);
    	}	
    	$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro, 'kae', '1=1');

	    $sql = "SELECT kae.*, 
	    			   pkg_pr_auxiliares.activo (kae.cod_auxiliar) ui_activo,
				       pkg_pr_auxiliares.imputable (kae.cod_auxiliar) ui_imputable,
				       pkg_pr_auxiliares.cc (kae.cod_auxiliar) ui_cc,
				       pkg_pr_auxiliares.mascara_aplicar (kae.cod_auxiliar) cod_auxiliar_masc,
				          '['
				       || pkg_pr_auxiliares.mascara_aplicar (kae.cod_auxiliar)
				       || '] '
				       || kae.descripcion AS descripcion_2
		    FROM kr_auxiliares_ext kae
		    WHERE $where
		    ORDER BY cod_auxiliar ASC;";
	    $datos = toba::db()->consultar($sql);
	    return $datos;
	}

	static public function get_auxiliares_extrapresupuestarios($filtro=array())
    	{
    	$where = ' 1=1 ';
    	if (isset($filtro['sin_padre'])){
    		$where .= " and cod_auxiliar_padre is null ";
    		unset($filtro['sin_padre']);
    	}	
    	if (!empty($filtro))	
	    	$where .= ctr_construir_sentencias::get_where_filtro($filtro, 'kae', ' and 1=1');
	    $sql = "SELECT kae.*, kae.cod_auxiliar || ' - ' || kae.descripcion as cod_auxiliar_descripcion,
	    				'[' || pkg_pr_auxiliares.mascara_aplicar(kae.cod_auxiliar) ||'] ' || kae.descripcion as descripcion_2
		    FROM kr_auxiliares_ext kae
		    WHERE $where
		    ORDER BY cod_auxiliar ASC;";
	    $datos = toba::db()->consultar($sql);
	    return $datos;
	}
	
	static public function get_auxiliares_extrapresupuestarios_x_nombre($nombre, $filtro = array())
	{
	    if (isset($nombre)) {
			$trans_cod_auxiliar = ctr_construir_sentencias::construir_translate_ilike('cod_auxiliar', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_cod_auxiliar OR $trans_descripcion)";
	    } else {
			$where = '1=1';
	    }
	    if (isset($filtro['imputable']) && $filtro['imputable'] == '1') {
			$where .= " AND pkg_pr_auxiliares.imputable(cod_auxiliar) = 'S' ";
			unset($filtro['imputable']);
	    }
	    if (isset($filtro['activo']) && $filtro['activo'] == '1') {
			$where .= " AND pkg_pr_auxiliares.activo(cod_auxiliar) = 'S' ";
			unset($filtro['activo']);
	    }
	    
		if (isset($filtro['cc'])) {
			$where .= " and PKG_PR_AUXILIARES.CC(COD_AUXILIAR) = '".$filtro['cc']."' ";
			unset($filtro['cc']);
	    }
            
		if (isset($filtro['para_ordenes_pago'])){
			$where .= " AND  (    pkg_pr_auxiliares.imputable (kae.cod_auxiliar) = 'S'
						AND pkg_pr_auxiliares.activo (kae.cod_auxiliar) = 'S'
						AND (   (   '".$filtro['tipo_cuenta_corriente']."' IN
														 ('P', 'C', 'J', 'G')
						AND NOT EXISTS (SELECT 1
										  FROM kr_cuentas_corriente
										 WHERE cod_auxiliar = kae.cod_auxiliar)
									   )
						OR ( '".$filtro['tipo_cuenta_corriente']."' = 'A'
					   AND EXISTS (SELECT 1
									 FROM kr_cuentas_corriente
									WHERE cod_auxiliar = kae.cod_auxiliar)
								  )
						   )
					   )";
			unset($filtro['para_ordenes_pago']);
			unset($filtro['tipo_cuenta_corriente']);
		}
		
		if (isset($filtro['tipo_cuenta_corriente'])){
			$where.= " 	AND ((upper('".$filtro['tipo_cuenta_corriente']."') in ('P','C','J','G') and PKG_PR_AUXILIARES.CC(kae.COD_AUXILIAR) = 'N' )
				or(upper('".$filtro['tipo_cuenta_corriente']."') = 'A' and PKG_PR_AUXILIARES.CC(kae.COD_AUXILIAR) = 'S' ))";
			
			unset($filtro['tipo_cuenta_corriente']);
		}
		
		if (isset($filtro['cod_tipo_recibo_pago'])) {
			$sql_atrp = "SELECT atrp.tipo_cuenta_corriente
						FROM AD_TIPOS_RECIBO_PAGO atrp
						WHERE atrp.cod_tipo_recibo = " . quote($filtro['cod_tipo_recibo_pago']) . " ";
			$where .= " AND((($sql_atrp) IN ('P','C', 'G', 'O') 
							AND NOT EXISTS(SELECT 1 FROM KR_CUENTAS_CORRIENTE WHERE COD_AUXILIAR = KAE.COD_AUXILIAR)) 
						OR (($sql_atrp) = 'A' 
							AND EXISTS(SELECT 1 FROM KR_CUENTAS_CORRIENTE WHERE COD_AUXILIAR = KAE.COD_AUXILIAR)) 
						OR (($sql_atrp) = 'J' 
							AND EXISTS(SELECT 1 FROM AD_CAJAS_CHICAS WHERE COD_AUXILIAR = KAE.COD_AUXILIAR)) )";
			unset($filtro['cod_tipo_recibo_pago']);
		}
            
	    $where .= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'kae', '1=1');
		
	    $sql = "SELECT kae.*, pkg_pr_auxiliares.mascara_aplicar(kae.cod_auxiliar) || ' - ' || kae.descripcion as lov_descripcion
		    FROM kr_auxiliares_ext kae
		    WHERE $where
		    ORDER BY lov_descripcion ASC;";
           
	    $datos = toba::db()->consultar($sql);
	    return $datos;
	}
	/*
	static public function get_cod_auxiliar_descrip_x_cod_auxiliar($cod_auxiliar)
    	{
	    if (isset($cod_auxiliar)) {
		$sql = "SELECT kae.cod_auxiliar || ' - ' || kae.descripcion as cod_auxiliar_descripcion
			FROM kr_auxiliares_ext kae
			WHERE cod_auxiliar = ".quote($cod_auxiliar) .";";
		$datos = toba::db()->consultar_fila($sql);
		if (isset($datos) && !empty($datos) && isset($datos['cod_auxiliar_descripcion'])) {
		    return $datos['cod_auxiliar_descripcion'];
		} else {
		    return '';
		}
	    } else {
		return '';
	    }
	}*/
	
	static public function get_auxiliar_desc_x_cod_auxiliar($cod_auxiliar){
	    if (isset($cod_auxiliar)) {
			$sql = "SELECT kae.cod_auxiliar || ' - ' || kae.descripcion as cod_auxiliar_descripcion
				FROM kr_auxiliares_ext kae
				WHERE cod_auxiliar = ".quote($cod_auxiliar) .";";
			$datos = toba::db()->consultar_fila($sql);
			
			if (isset($datos) && !empty($datos) && isset($datos['cod_auxiliar_descripcion'])) {
				return $datos['cod_auxiliar_descripcion'];
			} else {
				return '';
			}
	    } else {
			return '';
	    }
	}
	
	static public function get_lov_auxiliar_x_codigo($cod_auxiliar){     
        if (isset($cod_auxiliar)) {
            $sql = "SELECT KRAUEX.*, pkg_pr_auxiliares.mascara_aplicar(KRAUEX.cod_auxiliar) ||' - '|| KRAUEX.DESCRIPCION as lov_descripcion
                    FROM KR_AUXILIARES_EXT KRAUEX
                    WHERE KRAUEX.COD_AUXILIAR = ".quote($cod_auxiliar) .";";  
            $datos = toba::db()->consultar_fila($sql);
            if (isset($datos) && !empty($datos) && isset($datos['lov_descripcion'])) {
                return $datos['lov_descripcion'];
            } else {
                return '';
            }
        } else {
            return '';
        }
    }   
    
	static public function cant_niveles (){
		$sql ="SELECT pkg_pr_auxiliares.cant_niveles AS cant_niveles FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cant_niveles']; 
	}
	
	static public function es_hoja ($cod_auxiliar){
		$sql = "SELECT PKG_PR_AUXILIARES.ES_HOJA($cod_auxiliar) AS ES_HOJA FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['es_hoja'];
	}
	
	static public function activo ($cod_auxiliar){
		$sql = "SELECT PKG_PR_AUXILIARES.ACTIVo($cod_auxiliar) AS activo FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['activo'];
	}
	
	static public function imputable ($cod_auxiliar){
		$sql = "SELECT PKG_PR_AUXILIARES.IMPUTABLE($cod_auxiliar) AS imputable FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['imputable'];
	}
	
	static public function get_hijos ($cod_auxiliar){
		$sql = "select kae.*, '[' || pkg_pr_auxiliares.mascara_aplicar(kae.cod_auxiliar) ||'] ' || kae.descripcion as descripcion_2
				from kr_auxiliares_ext kae
				where kae.cod_auxiliar_padre = $cod_auxiliar
				order by kae.cod_auxiliar";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	static public function cc ($cod_auxiliar){
		$sql = "SELECT PKG_PR_AUXILIARES.CC($cod_auxiliar) AS CC FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cc'];
	}
	
 	static public function get_nivel_auxiliar ($cod_auxiliar){
	     $sql = "SELECT NIVEL 
	             FROM KR_AUXILIARES_EXT 
	             WHERE COD_AUXILIAR = $cod_auxiliar;";
	     $datos = toba::db()->consultar_fila($sql);
	     return $datos['nivel'];
    }
	
 	static public function cargar_descripcion_auxiliar($cod_auxiliar){
	   	$sql ="SELECT PKG_PR_AUXILIARES.CARGAR_DESCRIPCION($cod_auxiliar) AS descripcion FROM DUAL;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['descripcion'];
   }
   
	static public function tiene_hijos($cod_auxiliar){
	   	$sql ="SELECT pkg_pr_auxiliares.tiene_hijos($cod_auxiliar) AS tiene_hijo FROM DUAL;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['tiene_hijo'];
   }
   
	static public function cargar_auxiliar($cod_auxiliar){
		
       $sql = "BEGIN PKG_PR_AUXILIARES.CARGAR_AUXILIAR(:cod_auxiliar, :descripcion, :nivel, :cod_auxiliar_padre, :cuenta_corriente, :activo); END;";        	
       
       $parametros = array ( array(  'nombre' => 'cod_auxiliar', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 20,
                                     'valor' => $cod_auxiliar),
       
      						 array(  'nombre' => 'descripcion', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 100,
                                     'valor' => ''),
      						 
      						 array(  'nombre' => 'nivel', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 20,
                                     'valor' => ''),
      						 
      						 array(  'nombre' => 'cod_auxiliar_padre', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 20,
                                     'valor' => ''),
      						 
      						 array(  'nombre' => 'cuenta_corriente', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 20,
                                     'valor' => ''),
      						 
      						 array(  'nombre' => 'activo', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 20,
                                     'valor' => '')    						 
                            );
        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
        $datos = array ("cod_auxiliar"=>$cod_auxiliar, 
        				"descripcion"=>$resultado[1]['valor'], 
        				"nivel"=>$resultado[2]['valor'], 
        				"cod_auxiliar_padre"=>$resultado[3]['valor'],
        				"cuenta_corriente"=>$resultado[4]['valor'], 
        				"activo"=>$resultado[5]['valor']);
        return $datos;
   }
   
	static public function armar_codigo_auxiliar ($nivel, $cod_auxiliar, $cod_auxiliar_padre){
		if (empty($cod_auxiliar_padre))
			$cod_auxiliar_padre = 'null';
		$sql ="SELECT  PKG_PR_AUXILIARES.ARMAR_CODIGO($nivel, $cod_auxiliar, $cod_auxiliar_padre) AS valor FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['valor']; 
	}    
   
	static public function mascara_aplicar($cod_auxiliar){
    	$sql ="select pkg_pr_auxiliares.mascara_aplicar($cod_auxiliar) cod_auxiliar from dual;";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['cod_auxiliar'];
    }
	
	static public function eliminar ($cod_auxiliar)
    {
    	$resultado = [];
        toba::db()->abrir_transaccion();
        ctr_procedimientos::ejecutar_transaccion_compuesta(null, function () use ($cod_auxiliar, &$mensaje){
	    	try{
	        	if (dao_auxiliares_extrapresupuestarios::cc($cod_auxiliar) === 'S') {
	        		$resultado = dao_cuentas_corriente::eliminar_cuenta_corriente("AUX", $cod_auxiliar);
		            if ($resultado != 'S')
						throw new toba_error($resultado);
	        	}
	        	$resultado = dao_auxiliares_extrapresupuestarios::eliminar_auxiliar($cod_auxiliar, false);
	        	if ($resultado[0]['valor'] != 'OK')
		        	throw new toba_error($resultado[0]['valor']);
	        	
	        	toba::db()->cerrar_transaccion();
       		}catch (toba_error $e) {
                toba::db()->abortar_transaccion();
                toba::notificacion()->error($e->get_mensaje());
            }
   		});
    }
	static public function eliminar_auxiliar($cod_auxiliar)
	{
        $sql = "BEGIN 
        			:resultado := PKG_PR_AUXILIARES.ELIMINAR_AUXILIAR(:cod_auxiliar); 
        		END;";        	
        $parametros = [
        		[ 'nombre' => 'resultado', 
                  'tipo_dato' => PDO::PARAM_STR,
                  'longitud' => 4000,
                  'valor' => ''],
        		[ 'nombre' => 'cod_auxiliar', 
                  'tipo_dato' => PDO::PARAM_STR,
                  'longitud' => 20,
                  'valor' => $cod_auxiliar],
        ];
        return ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
	}
	
	static public function crear_auxiliar ($cod_auxiliar, $descripcion, $nivel, $cod_auxiliar_padre, $cuenta_corriente, $activo, $con_transaccion = true){
		try{
			$sql = "BEGIN :resultado := PKG_PR_AUXILIARES.CREAR_AUXILIAR(:cod_auxiliar, :descripcion, :nivel, :cod_auxiliar_padre, to_char(:cuenta_corriente), :activo);END;";		
			$parametros = array ( array(  'nombre' => 'cod_auxiliar', 
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 20,
											'valor' => $cod_auxiliar),
									array(  'nombre' => 'descripcion', 
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 100,
											'valor' => $descripcion),
									array(  'nombre' => 'nivel', 
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 20,
											'valor' => $nivel),
									array(  'nombre' => 'cod_auxiliar_padre', 
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 20,
											'valor' => $cod_auxiliar_padre),
									array(  'nombre' => 'cuenta_corriente', 
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 1,
											'valor' => $cuenta_corriente),
									array(  'nombre' => 'activo', 
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 20,
											'valor' => $activo),
									array(  'nombre' => 'resultado', 
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 1000,
											'valor' => '')
							);
			if ($con_transaccion)
				toba::db()->abrir_transaccion();

			$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros); 
			if ($con_transaccion){
				if ($resultado[6]['valor'] == 'OK'){
					toba::db()->cerrar_transaccion();
				}else{
					toba::db()->abortar_transaccion();
					toba::notificacion()->info($resultado[6]['valor']);
				}
			}
			return $resultado[6]['valor'];
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
        }
	}	
	
	static public function actualizar_auxiliar ($cod_auxiliar, $descripcion, $cuenta_corriente, $activo, $con_transaccion = true){
		try{
			$sql = "BEGIN :resultado := PKG_PR_AUXILIARES.ACTUALIZAR_AUXILIAR(:cod_auxiliar, :descripcion, :cuenta_corriente, :activo);END;";		
			$parametros = array (   array(  'nombre' => 'cod_auxiliar', 
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 20,
											'valor' => $cod_auxiliar),
			
									array(  'nombre' => 'descripcion', 
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 100,
											'valor' => $descripcion),
									
									array(  'nombre' => 'cuenta_corriente', 
											'tipo_dato' => PDO::PARAM_INT,
											'longitud' => 100,
											'valor' => $cuenta_corriente),
									
									array(  'nombre' => 'activo', 
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 20,
											'valor' => $activo),
									
									array(  'nombre' => 'resultado', 
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 1000,
											'valor' => '')
							);
			if ($con_transaccion)
				toba::db()->abrir_transaccion();
			$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros); 
			               
			if ($con_transaccion){
				if ($resultado[4]['valor'] == 'OK'){
					toba::db()->cerrar_transaccion();
				}else{
					toba::db()->abortar_transaccion();
					toba::notificacion()->info($resultado[4]['valor']);
				}
			}
			return $resultado[4]['valor'];
			
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
        }
	}	
	
	static public function cambiar_estado_activo_hijos ($cod_auxiliar, $activo){
		try{
			$sql = "BEGIN PKG_PR_AUXILIARES.CAMBIAR_ESTADO_ACTIVO_HIJOS(:cod_auxiliar, :activo);END;";		
			$parametros = array (   array(  'nombre' => 'cod_auxiliar', 
											'tipo_dato' => PDO::PARAM_INT,
											'longitud' => 20,
											'valor' => $cod_auxiliar),
			
									array(  'nombre' => 'activo', 
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 1,
											'valor' => $activo)
									
							);
			$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
			 
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            return false;
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
            return false;
        }
	}	
	

	static public function get_id_cuenta_corriente($origen,$cod_unidad_administracion, $cod_auxiliar){
		
		$sql = "select pkg_kr_cuentas_corriente.id_de_la_ctacte('$origen',$cod_unidad_administracion,$cod_auxiliar) id_cuenta_corriente from dual";
		
		$datos = toba::db()->consultar_fila($sql);
		return $datos['id_cuenta_corriente'];
	}
	
}
?>
