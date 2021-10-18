<?php

class dao_recursos_presupuestarios {
    
    static public function get_datos($filtro = [])
    {
    	$where = ' 1=1 ';
    	if (isset($filtro['id_padre'])){
    		$where .= " and cod_recurso_padre = ".$filtro['id_padre'];
    		unset($filtro['id_padre']);
    	}	
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'pr', '1=1');
        
        $sql = "SELECT  pr.*, 
        pkg_pr_recursos.activo (pr.cod_recurso) ui_activo,
         pkg_pr_recursos.figurativo (pr.cod_recurso) ui_figurativo,
         pkg_pr_recursos.imputable (pr.cod_recurso) ui_imputable,
         pkg_pr_recursos.devengar (pr.cod_recurso) ui_devengar,
         pkg_pr_recursos.rrhh (pr.cod_recurso) ui_rrhh,
         pkg_pr_fuentes.MASCARA_APLICAR(pr.cod_fuente_financiera) ui_cod_fuente_financiera,
         (SELECT nro_cuenta_corriente
            FROM kr_cuentas_corriente
           WHERE id_cuenta_corriente = pr.id_cuenta_corriente) ui_nro_cuenta,
         (SELECT rv_meaning
            FROM cg_ref_codes
           WHERE rv_domain = 'PR_ORIGEN_RECURSOS'
             AND rv_low_value = pkg_pr_recursos.origen (pr.cod_recurso))
                                                                    ui_origen,
            '['
         || pkg_pr_recursos.mascara_aplicar (pr.cod_recurso)
         || '] ' cod_masc,
            '['
         || pkg_pr_recursos.mascara_aplicar (pr.cod_recurso)
         || '] '
         || pr.descripcion AS descripcion_2
                FROM PR_RECURSOS pr
                WHERE $where
                order by cod_recurso asc;";
		
        $datos = toba::db()->consultar($sql);
        return $datos;      

    }
	static public function get_recurso_x_codigo ($cod_recurso){
		$sql ="SELECT * FROM PR_RECURSOS WHERE COD_RECURSO = $cod_recurso";
		$datos = toba::db()->consultar_fila($sql);
		return $datos;
	}
	
    static public function get_pr_recursos($filtro= array()){
        
        $where = ' 1=1 ';
    	if (isset($filtro['sin_padre'])){
    		$where .= " and cod_recurso_padre is null ";
    		unset($filtro['sin_padre']);
    	}	
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'pr', '1=1');
        
        $sql = "SELECT  pr.*, 
        pkg_pr_recursos.activo (pr.cod_recurso) ui_activo,
         pkg_pr_recursos.figurativo (pr.cod_recurso) ui_figurativo,
         pkg_pr_recursos.imputable (pr.cod_recurso) ui_imputable,
         pkg_pr_recursos.devengar (pr.cod_recurso) ui_devengar,
         pkg_pr_recursos.rrhh (pr.cod_recurso) ui_rrhh,
         pkg_pr_fuentes.MASCARA_APLICAR(pr.cod_fuente_financiera) ui_cod_fuente_financiera,
         (SELECT nro_cuenta_corriente
            FROM kr_cuentas_corriente
           WHERE id_cuenta_corriente = pr.id_cuenta_corriente) ui_nro_cuenta,
         (SELECT rv_meaning
            FROM cg_ref_codes
           WHERE rv_domain = 'PR_ORIGEN_RECURSOS'
             AND rv_low_value = pkg_pr_recursos.origen (pr.cod_recurso))
                                                                    ui_origen,
            '['
         || pkg_pr_recursos.mascara_aplicar (pr.cod_recurso)
         || '] ' cod_masc,
            '['
         || pkg_pr_recursos.mascara_aplicar (pr.cod_recurso)
         || '] '
         || pr.descripcion AS descripcion_2
                FROM PR_RECURSOS pr
                WHERE $where
                order by cod_recurso asc;";
		
        $datos = toba::db()->consultar($sql);
        return $datos;      
    }
    
    static public function get_lov_recursos_x_cod_recurso($cod_recurso) {            
        if (isset($cod_recurso)) {
            $sql = "SELECT pkg_pr_recursos.mascara_aplicar(pr.cod_recurso) ||' - '|| pkg_pr_recursos.cargar_descripcion(pr.cod_recurso) lov_descripcion,
            			   '[' || pkg_pr_recursos.MASCARA_APLICAR(pr.cod_recurso) ||'] ' || pr.descripcion as descripcion_2
                FROM PR_RECURSOS pr
                WHERE pr.cod_recurso = ".quote($cod_recurso) .";";

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
        
    static public function get_lov_recursos_x_nombre($nombre, $filtro = array()) {
        
        if (isset($nombre)) {
			$campos = array(
						'pr.cod_recurso',
						'pkg_pr_recursos.mascara_aplicar(pr.cod_recurso)',
						'pkg_pr_recursos.cargar_descripcion(pr.cod_recurso)',
						"pr.cod_recurso ||' - '|| pkg_pr_recursos.cargar_descripcion(pr.cod_recurso)",
						"pkg_pr_recursos.mascara_aplicar(pr.cod_recurso) ||' - '|| pkg_pr_recursos.cargar_descripcion(pr.cod_recurso)",
				);
			$where = ctr_construir_sentencias::construir_sentencia_busqueda($nombre, $campos, false);
        } else {
            $where = '1=1';
        }
        
        if (isset($filtro['cod_unidad_administracion']) && !empty($filtro['cod_unidad_administracion']) && isset($filtro['ejercicio']) && !empty($filtro['ejercicio']) && isset($filtro['fuente_financiera']) && !empty($filtro['fuente_financiera']) && isset($filtro['ui_sin_control_pres'])) {
            $where .= "  AND (    pkg_pr_recursos.cod_fuente (pr.cod_recurso) =
                                            " . $filtro['fuente_financiera'] . "
          AND pkg_pr_fuentes.afectacion_especifica
                                 (pkg_pr_recursos.cod_fuente (pr.cod_recurso)
                                 ) = 'S'
          AND (   (    '" . $filtro['ui_sin_control_pres'] . "' = 'S'
                   AND pkg_pr_recursos.imputable (pr.cod_recurso) = 'S'
                   AND pkg_pr_recursos.activo (pr.cod_recurso) = 'S'
                  )
               OR (    '" . $filtro['ui_sin_control_pres'] . "' = 'N'
                   AND EXISTS (
                          SELECT 1
                            FROM pr_movimientos_egresos
                           WHERE cod_unidad_administracion =
                                      " . $filtro['cod_unidad_administracion'] . "
                             AND id_ejercicio = " . $filtro['ejercicio'] . "
                             AND cod_recurso = pr.cod_recurso)
                  )
              )
         )";
            unset($filtro['ejercicio']);
            unset($filtro['ui_sin_control_pres']);
            unset($filtro['fuente_financiera']);
        }

        if (isset($filtro['devengado']) && isset($filtro['cod_fuente_financiera']) && isset($filtro['ui_sin_control_pres']) && isset($filtro['cod_partida']) && isset($filtro['id_entidad']) && isset($filtro['id_programa']) && isset($filtro['cod_fuente_financiera'])) {
            if (empty($filtro['id_compromiso']))
               $filtro['id_compromiso'] = 'NULL';   
            $where .= " AND (pkg_pr_recursos.cod_fuente(pr.cod_recurso) = ".$filtro['cod_fuente_financiera']." 
                        and pkg_pr_fuentes.afectacion_especifica(pkg_pr_recursos.cod_fuente(pr.cod_recurso)) = 'S' 
                        and (('".$filtro['ui_sin_control_pres']."' = 'S' 
                             and pkg_pr_recursos.imputable(pr.cod_recurso) = 'S' 
                             and pkg_pr_recursos.activo(pr.cod_recurso) = 'S') 
                             or ('".$filtro['ui_sin_control_pres']."' = 'N' 
                             and ((".$filtro['id_compromiso']." is null 
                             or (".$filtro['id_compromiso']." is not null 
                             and exists(select 1 from ad_compromisos adco, ad_compromisos_det adcode, ad_compromisos_imp adcoim 
                             where (adco.id_compromiso = ".$filtro['id_compromiso']." OR adco.id_compromiso_aju = ".$filtro['id_compromiso'].") 
                                    and adco.aprobado = 'S' and adco.anulado = 'N' 
                                    and adco.id_compromiso = adcode.id_compromiso 
                                    and adcode.id_compromiso = adcoim.id_compromiso 
                                    and adcode.id_detalle = adcoim.id_detalle 
                                    and adcode.cod_partida = ".$filtro['cod_partida']."
                                    and adcoim.id_entidad = ".$filtro['id_entidad']." 
                                    and adcoim.id_programa = ".$filtro['id_programa']." 
                                    and adcoim.cod_fuente_financiera = ".$filtro['cod_fuente_financiera']." 
                                    and adcoim.cod_recurso = PR.cod_recurso)))))))";
			unset($filtro['devengado']);
			unset($filtro['ui_sin_control_pres']);
        }
		
		if (isset($filtro['imputable'])) {
			$where .= " AND PKG_PR_RECURSOS.IMPUTABLE(PR.COD_RECURSO) = 'S' ";
			unset($filtro['imputable']);
		}

		if (isset($filtro['en_actividad'])) {
			$where .= " AND PKG_PR_RECURSOS.ACTIVO(PR.COD_RECURSO) = 'S' ";
			unset($filtro['en_actividad']);
		}
		
    	if (isset($filtro['para_reimputar'])) {
			$where .= " AND PKG_PR_RECURSOS.PARA_REIMPUTAR(PR.COD_RECURSO) =  'S' ";
			unset($filtro['para_reimputar']);
		}
		
		if (isset($filtro['cod_fuente_financiera'])) {
			$where .= " AND PKG_PR_RECURSOS.COD_FUENTE(PR.COD_RECURSO) = " . quote($filtro['cod_fuente_financiera']) . " ";
			unset($filtro['cod_fuente_financiera']);
		}
		
		if (isset($filtro['activo_imputable'])) {
			$where.= " AND pkg_pr_recursos.activo(cod_recurso) = 'S' 
					and pkg_pr_recursos.imputable(cod_recurso) = 'S'";
			
			unset($filtro['activo_imputable']);
		}
                
		if (isset($filtro['afectacion_especifica'])){
				$where .= " AND (pkg_pr_fuentes.afectacion_especifica(pkg_pr_recursos.cod_fuente(pr.cod_recurso)) = 'S')";
				unset($filtro['afectacion_especifica']);
		}
		
		unset($filtro['cod_unidad_administracion']);
		unset($filtro['fecha_comprobante']);
		unset($filtro['cod_partida']);
		unset($filtro['id_entidad']);
		unset($filtro['id_programa']);
		
        $sql = "SELECT  pr.cod_recurso, pkg_pr_recursos.mascara_aplicar(pr.cod_recurso) ||' - '|| pkg_pr_recursos.cargar_descripcion(pr.cod_recurso) as lov_descripcion
                FROM PR_RECURSOS pr
                WHERE $where
                ORDER BY lov_descripcion ASC;";  
        $datos = toba::db()->consultar($sql);
        
        return $datos;
    }
	
	static public function get_lov_recursos_con_saldo_x_nombre($nombre, $filtro = array()) 
	{
		if (isset($nombre)) {
			$campos = array(
						'pr.cod_recurso',
						'pkg_pr_recursos.mascara_aplicar(pr.cod_recurso)',
						'pkg_pr_recursos.cargar_descripcion(pr.cod_recurso)',
						"pr.cod_recurso ||' - '|| pkg_pr_recursos.cargar_descripcion(pr.cod_recurso)",
						"pkg_pr_recursos.mascara_aplicar(pr.cod_recurso) ||' - '|| pkg_pr_recursos.cargar_descripcion(pr.cod_recurso)",
				);
			$where = ctr_construir_sentencias::construir_sentencia_busqueda($nombre, $campos, true);
		} else {
			$where = '1=1';
		}
		
		// Determina si se muestra el saldo del recurso
		if (isset($filtro['cod_unidad_administracion']) && isset($filtro['cod_partida']) && isset($filtro['fecha_comprobante']) && isset($filtro['id_entidad']) && isset($filtro['id_programa']) && isset($filtro['cod_fuente_financiera'])) {	
			$lov_descripcion_saldo = "pkg_pr_recursos.mascara_aplicar(pr.cod_recurso) ||' - '|| pkg_pr_recursos.cargar_descripcion(pr.cod_recurso) || ' (' || TRIM(to_char(PKG_PR_TOTALES.SALDO_ACUMULADO_EGRESO(" . quote($filtro['cod_unidad_administracion']) . ", PKG_KR_EJERCICIOS.RETORNAR_EJERCICIO(" . quote($filtro['fecha_comprobante']) . "), " . quote($filtro['id_entidad']) . ", " . quote($filtro['id_programa']) . ", " . quote($filtro['cod_partida']) . ", " . quote($filtro['cod_fuente_financiera']) . ", pr.cod_recurso, 'PRES', SYSDATE), '$999,999,999,990.00')) ||')' as lov_descripcion_saldo";	
		} else {
			$lov_descripcion_saldo = "pkg_pr_recursos.mascara_aplicar(pr.cod_recurso) ||' - '|| pkg_pr_recursos.cargar_descripcion(pr.cod_recurso) as lov_descripcion_saldo";
		}

		if (isset($filtro['cod_unidad_administracion']) && !empty($filtro['cod_unidad_administracion']) && isset($filtro['ejercicio']) && !empty($filtro['ejercicio']) && isset($filtro['fuente_financiera']) && !empty($filtro['fuente_financiera']) && isset($filtro['ui_sin_control_pres'])) {
			$where .= "  AND (    pkg_pr_recursos.cod_fuente (pr.cod_recurso) =
											" . $filtro['fuente_financiera'] . "
		  AND pkg_pr_fuentes.afectacion_especifica
								 (pkg_pr_recursos.cod_fuente (pr.cod_recurso)
								 ) = 'S'
		  AND (   (    '" . $filtro['ui_sin_control_pres'] . "' = 'S'
				   AND pkg_pr_recursos.imputable (pr.cod_recurso) = 'S'
				   AND pkg_pr_recursos.activo (pr.cod_recurso) = 'S'
				  )
			   OR (    '" . $filtro['ui_sin_control_pres'] . "' = 'N'
				   AND EXISTS (
						  SELECT 1
							FROM pr_movimientos_egresos
						   WHERE cod_unidad_administracion =
									  " . $filtro['cod_unidad_administracion'] . "
							 AND id_ejercicio = " . $filtro['ejercicio'] . "
							 AND cod_recurso = pr.cod_recurso)
				  )
			  )
		 )";
			unset($filtro['ejercicio']);
			unset($filtro['ui_sin_control_pres']);
			unset($filtro['fuente_financiera']);
		}

		if (isset($filtro['devengado']) && isset($filtro['cod_fuente_financiera']) && isset($filtro['ui_sin_control_pres']) && isset($filtro['cod_partida']) && isset($filtro['id_entidad']) && isset($filtro['id_programa']) && isset($filtro['cod_fuente_financiera'])){
			if (empty($filtro['id_compromiso']))
			   $filtro['id_compromiso'] = 'NULL';   
			$where .= " AND (pkg_pr_recursos.cod_fuente(pr.cod_recurso) = ".$filtro['cod_fuente_financiera']." 
						and pkg_pr_fuentes.afectacion_especifica(pkg_pr_recursos.cod_fuente(pr.cod_recurso)) = 'S' 
						and (('".$filtro['ui_sin_control_pres']."' = 'S' 
							 and pkg_pr_recursos.imputable(pr.cod_recurso) = 'S' 
							 and pkg_pr_recursos.activo(pr.cod_recurso) = 'S') 
							 or ('".$filtro['ui_sin_control_pres']."' = 'N' 
							 and ((".$filtro['id_compromiso']." is null 
							 or (".$filtro['id_compromiso']." is not null 
							 and exists(select 1 from ad_compromisos adco, ad_compromisos_det adcode, ad_compromisos_imp adcoim 
							 where (adco.id_compromiso = ".$filtro['id_compromiso']." OR adco.id_compromiso_aju = ".$filtro['id_compromiso'].") 
									and adco.aprobado = 'S' and adco.anulado = 'N' 
									and adco.id_compromiso = adcode.id_compromiso 
									and adcode.id_compromiso = adcoim.id_compromiso 
									and adcode.id_detalle = adcoim.id_detalle 
									and adcode.cod_partida = ".$filtro['cod_partida']."
									and adcoim.id_entidad = ".$filtro['id_entidad']." 
									and adcoim.id_programa = ".$filtro['id_programa']." 
									and adcoim.cod_fuente_financiera = ".$filtro['cod_fuente_financiera']." 
									and adcoim.cod_recurso = PR.cod_recurso)))))))";
			unset($filtro['devengado']);
			unset($filtro['ui_sin_control_pres']);
		}

		if (isset($filtro['imputable'])) {
			$where .= " AND PKG_PR_RECURSOS.IMPUTABLE(PR.COD_RECURSO) = 'S' ";
			unset($filtro['imputable']);
		}

		if (isset($filtro['en_actividad'])) {
			$where .= " AND PKG_PR_RECURSOS.ACTIVO(PR.COD_RECURSO) = 'S' ";
			unset($filtro['en_actividad']);
		}

		if (isset($filtro['cod_fuente_financiera'])) {
			$where .= " AND PKG_PR_RECURSOS.COD_FUENTE(PR.COD_RECURSO) = " . quote($filtro['cod_fuente_financiera']) . " ";
			unset($filtro['cod_fuente_financiera']);
		}
		if (isset($filtro['afectacion_especifica'])){
			$where .= " AND (pkg_pr_fuentes.afectacion_especifica(pkg_pr_recursos.cod_fuente(pr.cod_recurso)) = 'S')";
			unset($filtro['afectacion_especifica']);
		}
		
		if (isset($filtro['exista_saldo_ingreso']) && $filtro['exista_saldo_ingreso'] == '1' && isset($filtro['cod_unidad_administracion']) && isset($filtro['fecha_comprobante'])) {
			$where .= " AND EXISTS (SELECT 1 
									FROM V_PR_SALDOS_INGRESOS V_SAIN 
									WHERE V_SAIN.ID_EJERCICIO = (	SELECT KE.ID_EJERCICIO 
																	FROM KR_EJERCICIOS KE
																	WHERE KE.ABIERTO = 'S' 
																	AND KE.CERRADO = 'N'
																	AND (" . quote($filtro['fecha_comprobante']) ." BETWEEN KE.FECHA_INICIO AND KE.FECHA_FIN)
																	AND ROWNUM <= 1
																)
									AND PR.COD_RECURSO = V_SAIN.COD_RECURSO 
									AND V_SAIN.COD_UNIDAD_ADMINISTRACION = " . quote($filtro['cod_unidad_administracion']) . ") ";
			unset($filtro['exista_saldo_ingreso']);
		}
		unset($filtro['cod_unidad_administracion']);
		unset($filtro['fecha_comprobante']);
		unset($filtro['cod_partida']);
		unset($filtro['id_entidad']);
		unset($filtro['id_programa']);

		$where .= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'ff', '1=1');

		$sql = "SELECT  pr.*, 
						$lov_descripcion_saldo
				FROM PR_RECURSOS pr
				WHERE $where
				ORDER BY lov_descripcion_saldo ASC;";  
		$datos = toba::db()->consultar($sql);

		return $datos;
    }
    
    
    /*
     * 
     * 
     * 
     * 
     */
    
    
	static public function cant_niveles (){
		$sql ="SELECT pkg_pr_recursos.cant_niveles AS cant_niveles FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cant_niveles']; 
	}
	
	static public function es_hoja ($cod_recurso){
		$sql = "SELECT pkg_pr_recursos.ES_HOJA($cod_recurso) AS ES_HOJA FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['es_hoja'];
	}
	
	static public function activo ($cod_recurso){
		$sql = "SELECT pkg_pr_recursos.ACTIVo($cod_recurso) AS activo FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['activo'];
	}
			
	static public function imputable ($cod_recurso){
		$sql = "SELECT pkg_pr_recursos.IMPUTABLE($cod_auxiliar) AS imputable FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['imputable'];
	}
	
	static public function get_hijos ($cod_recurso){
		$sql = "SELECT  pr.*, 
		 pkg_pr_fuentes.MASCARA_APLICAR(pr.cod_fuente_financiera) ui_cod_fuente_financiera,
         pkg_pr_recursos.activo (pr.cod_recurso) ui_activo,
         pkg_pr_recursos.figurativo (pr.cod_recurso) ui_figurativo,
         pkg_pr_recursos.imputable (pr.cod_recurso) ui_imputable,
         pkg_pr_recursos.devengar (pr.cod_recurso) ui_devengar,
         pkg_pr_recursos.rrhh (pr.cod_recurso) ui_rrhh,
         (SELECT nro_cuenta_corriente
            FROM kr_cuentas_corriente
           WHERE id_cuenta_corriente = pr.id_cuenta_corriente) ui_nro_cuenta,
         (SELECT rv_meaning
            FROM cg_ref_codes
           WHERE rv_domain = 'PR_ORIGEN_RECURSOS'
             AND rv_low_value = pkg_pr_recursos.origen (pr.cod_recurso))
                                                                    ui_origen,
            '['
         || pkg_pr_recursos.mascara_aplicar (pr.cod_recurso)
         || '] ' cod_masc,
            '['
         || pkg_pr_recursos.mascara_aplicar (pr.cod_recurso)
         || '] '
         || pr.descripcion AS descripcion_2
				from PR_RECURSOS pr
				where pr.cod_recurso_padre = $cod_recurso
				order by cod_recurso asc;";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	
 	static public function cargar_descripcion($cod_recurso){
	   	$sql ="SELECT pkg_pr_recursos.CARGAR_DESCRIPCION($cod_recurso) AS descripcion FROM DUAL;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['descripcion'];
   }
	
	static public function tiene_hijos($cod_recurso){
	   	$sql ="SELECT pkg_pr_recursos.tiene_hijos($cod_recurso) AS tiene_hijo FROM DUAL;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['tiene_hijo'];
   }
	static public function crear_recurso($codigo, 
										 $descripcion, 
										 $nivel, 
										 $cod_recurso_padre, 
										 $cod_fuente_financiera, 
										 $cod_organismo_financiero, 
										 $figurativo, 
										 $activo, 
										 $reimputacion, 
										 $ori, 
										 $dev, 
										 $id_cuenta_corriente, 
										 $rrhh, $con_transaccion = true){
       $sql = "BEGIN :resultado := pkg_pr_recursos.crear_recurso(:codigo, :descripcion, :nivel, :cod_recurso_padre, :cod_fuente_financiera, :cod_organismo_financiero, :figurativo, :activo, :reimputacion, :origen, :devengar, :id_cuenta_corriente, :rrhh);END;";

       $parametros = array ( array(  'nombre' => 'codigo', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => $codigo), 
       						 array(  'nombre' => 'descripcion', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 100,
                                     'valor' => $descripcion),
      						 array(  'nombre' => 'nivel', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 3,
                                     'valor' => $nivel),
      						 array(  'nombre' => 'cod_recurso_padre', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => $cod_recurso_padre),
      						 array(  'nombre' => 'cod_fuente_financiera', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 11,
                                     'valor' => $cod_fuente_financiera),
      						 array(  'nombre' => 'cod_organismo_financiero', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 11,
                                     'valor' => $cod_organismo_financiero),
      						 array(  'nombre' => 'figurativo', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => $figurativo),
      						 array(  'nombre' => 'activo', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => $activo),
      						 array(  'nombre' => 'reimputacion', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => $reimputacion) ,
      						 array(  'nombre' => 'origen', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 3,
                                     'valor' => $ori),
      						 array(  'nombre' => 'devengar', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => $dev),
      						 array(  'nombre' => 'id_cuenta_corriente', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 11,
                                     'valor' => $id_cuenta_corriente),
      						 array(  'nombre' => 'rrhh', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => $rrhh),
      						 array(  'nombre' => 'resultado', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 400,
                                     'valor' => ''));
	      						 
      	if ($con_transaccion)
      		toba::db()->abrir_transaccion();
      		
        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
        if ($con_transaccion){
        	if ($resultado[13]['valor'] != 'OK'){
        		toba::db()->abortar_transaccion();
        		toba::notificacion()->error($resultado[13]['valor']);
        	}else{
        		toba::db()->cerrar_transaccion();
        	}
        }
        return $resultado[13]['valor'];
   }
   
	static public function actualizar_recurso($codigo, $descripcion, $cod_fuente_financiera, $cod_organismo_financiero, $figurativo, $activo, 
										 	  $reimputacion, $ori, $dev, $id_cuenta_corriente, $rrhh, $con_transaccion = true){
	    $sql = "BEGIN :resultado := pkg_pr_recursos.actualizar_recurso(:codigo, :descripcion, :cod_fuente_financiera, :cod_organismo_financiero, :figurativo, :activo, :reimputacion, :origen, :devengar, :id_cuenta_corriente, :rrhh);END;";

    	$parametros = array ( array( 'nombre' => 'codigo', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => $codigo), 
       						 array(  'nombre' => 'descripcion', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 100,
                                     'valor' => $descripcion),
      						 array(  'nombre' => 'cod_fuente_financiera', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 11,
                                     'valor' => $cod_fuente_financiera),
      						 array(  'nombre' => 'cod_organismo_financiero', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 11,
                                     'valor' => $cod_organismo_financiero),
      						 array(  'nombre' => 'figurativo', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => $figurativo),
      						 array(  'nombre' => 'activo', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => $activo),
      						 array(  'nombre' => 'reimputacion', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => $reimputacion) ,
      						 array(  'nombre' => 'origen', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 3,
                                     'valor' => $ori),
      						 array(  'nombre' => 'devengar', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => $dev),
      						 array(  'nombre' => 'id_cuenta_corriente', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 11,
                                     'valor' => $id_cuenta_corriente),
      						 array(  'nombre' => 'rrhh', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => $rrhh),
      						 array(  'nombre' => 'resultado', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 400,
                                     'valor' => ''));
	      						 
      	if ($con_transaccion)
      		toba::db()->abrir_transaccion();
      		
        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
        if ($con_transaccion){
        	if ($resultado[11]['valor'] != 'OK'){
        		toba::db()->abortar_transaccion();
        		toba::notificacion()->error($resultado[11]['valor']);
        	}else{
        		toba::db()->cerrar_transaccion();
        	}
        }
        return $resultado[11]['valor'];
   }
   
	static public function cargar_recurso($cod_recurso){
       $sql = "BEGIN pkg_pr_recursos.cargar_recurso(:codigo, :descripcion, :nivel, :cod_recurso_padre, :cod_fuente_financiera, :cod_organismo_financiero, :figurativo, :activo, :reimputacion, :origen, :devengar, :id_cuenta_corriente, :rrhh);END;";

       $parametros = array ( array(  'nombre' => 'codigo', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => $cod_recurso), 
       						 array(  'nombre' => 'descripcion', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 100,
                                     'valor' => ''),
      						 array(  'nombre' => 'nivel', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 3,
                                     'valor' => ''),
      						 array(  'nombre' => 'cod_recurso_padre', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => ''),
      						 array(  'nombre' => 'cod_fuente_financiera', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 11,
                                     'valor' => ''),
      						 array(  'nombre' => 'cod_organismo_financiero', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 11,
                                     'valor' => ''),
      						 array(  'nombre' => 'figurativo', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => ''),
      						 array(  'nombre' => 'activo', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => ''),
      						 array(  'nombre' => 'reimputacion', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => '') ,
      						 array(  'nombre' => 'origen', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 3,
                                     'valor' => ''),
      						 array(  'nombre' => 'devengar', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => ''),
      						 array(  'nombre' => 'id_cuenta_corriente', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 11,
                                     'valor' => ''),
      						 array(  'nombre' => 'rrhh', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => ''));
	      						 
        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
        $datos = array (	"cod_recurso"=>$cod_recurso, 
	        				"descripcion"=>$resultado[1]['valor'], 
	        				"nivel"=>$resultado[2]['valor'], 
	        				"cod_recurso_padre"=>$resultado[3]['valor'],
	      	  				"cod_fuente_financiera"=>$resultado[4]['valor'], 
	        				"cod_organismo_financiero"=>$resultado[5]['valor'], 
	        				"figurativo"=>$resultado[6]['valor'],
	        				"activo"=>$resultado[7]['valor'],
					        "reimputacion"=>$resultado[8]['valor'],
					        "origen"=>$resultado[9]['valor'],
					        "devengar"=>$resultado[10]['valor'],
					        "id_cuenta_corriente"=>$resultado[11]['valor'],
					        "rrhh"=>$resultado[12]['valor']
	        				);
        return $datos;
   }
   
	static public function cargar_auxiliar($cod_recurso){
	   if (!empty($cod_recurso)){
	       $sql = "BEGIN pkg_pr_recursos.cargar_recurso(:cod_recurso, :descripcion, :nivel, :cod_recurso_padre, :cod_fuente_financiera, :cod_organismo_financiero,:figurativo,:activo, :reimputacion, :origen, :devengar, :id_cuenta_corriente, :rrhh);END;";        	

	       $parametros = array ( array(  'nombre' => 'cod_recurso', 
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 9),
	                                     'valor' => $cod_recurso, 
	       						 array(  'nombre' => 'descripcion', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 100,
	                                     'valor' => ''),
	      						 array(  'nombre' => 'nivel', 
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 3,
	                                     'valor' => ''),
	      						 array(  'nombre' => 'cod_recurso_padre', 
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 9,
	                                     'valor' => ''),
	      						 array(  'nombre' => 'cod_fuente_financiera', 
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 5,
	                                     'valor' => ''),
	      						 array(  'nombre' => 'cod_organismo_financiero', 
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 5,
	                                     'valor' => ''),
	      						 array(  'nombre' => 'figurativo', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 10,
	                                     'valor' => ''),
	      						 array(  'nombre' => 'activo', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 10,
	                                     'valor' => ''),
	      						 array(  'nombre' => 'reimputacion', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 10,
	                                     'valor' => '') ,
	      						 array(  'nombre' => 'origen', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 10,
	                                     'valor' => ''),
	      						 array(  'nombre' => 'devengar', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 10,
	                                     'valor' => ''),
	      						 array(  'nombre' => 'id_cuenta_corriente', 
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 11,
	                                     'valor' => ''),
	      						 array(  'nombre' => 'rrhh', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 10,
	                                     'valor' => '')
	      						 );
	      						   
	        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
	        $datos = array ("cod_auxiliar"=>$cod_recurso, 
	        				"descripcion"=>$resultado[1]['valor'], 
	        				"nivel"=>$resultado[2]['valor'], 
	        				"cod_recurso_padre"=>$resultado[3]['valor'],
	      	  				"cod_fuente_financiera"=>$resultado[4]['valor'], 
	        				"cod_organismo_financiero"=>$resultado[5]['valor'], 
	        				"figurativo"=>$resultado[6]['valor'],
	        				"activo"=>$resultado[7]['valor'],
					        "reimputacion"=>$resultado[8]['valor'],
					        "origen"=>$resultado[9]['valor'],
					        "devengar"=>$resultado[10]['valor'],
					        "id_cuenta_corriente"=>$resultado[11]['valor'],
					        "rrhh"=>$resultado[12]['valor']
	        				);
	        return $datos;
	   }else return null;
   }
	
	static public function get_nivel_recuso ($cod_recurso){
	     $sql = "SELECT NIVEL 
	             FROM PR_RECURSOS 
	             WHERE cod_recurso = $cod_recurso;";
	     $datos = toba::db()->consultar_fila($sql);
	     return $datos['nivel'];
    }
	
	static public function mascara_aplicar ($cod_recurso){
		$sql = "SELECT pkg_pr_recursos.mascara_aplicar($cod_recurso) COD_RECURSO FROM DUAL";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cod_recurso'];
	}
	
	static public function origen ($cod_recuso){
		$sql = "SELECT pkg_pr_recursos.ORIGEN($cod_recuso) origen FROM DUAL";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['origen'];
	}
	
	static public function devengar ($cod_recuso){
		$sql = "SELECT pkg_pr_recursos.devengar($cod_recuso) devengar FROM DUAL";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['devengar'];
	}
	static public function existe_recurso ($cod_recuso){
		$sql = "SELECT NVL (MIN (1), 0) cant
        		FROM pr_recursos r
       			WHERE r.cod_recurso = $cod_recuso";
		$datos = toba::db()->consultar_fila($sql);
		if ($datos['cant'] > 0)
			return true;
		else 
			return false;
	}
	
	static public function figurativo ($cod_recuso){
		$sql = "SELECT pkg_pr_recursos.figurativo($cod_recuso) figurativo FROM DUAL";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['figurativo'];
	}
	
	static public function rrhh ($cod_recuso){
		$sql = "SELECT pkg_pr_recursos.rrhh($cod_recuso) rrhh FROM DUAL";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['rrhh'];
	}
	
	static public function armar_codigo_recurso ($nivel, $cod_recurso, $cod_recurso_padre){
		if ($cod_recurso_padre == null)
			$cod_recurso_padre = 'NULL';
		$sql ="SELECT  pkg_pr_recursos.armar_codigo($nivel, $cod_recurso, $cod_recurso_padre) AS valor FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['valor']; 
	}
	
	static public function para_reimputar ($cod_recurso){
		/*
		$sql = "SELECT pkg_pr_recursos.PARA_REIMPUTAR($cod_recuso) para_reimputar FROM DUAL";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['para_reimputar'];*/
         try{
            $sql = "BEGIN :resultado := pkg_pr_recursos.PARA_REIMPUTAR(:cod_recurso); END;";        	
            $parametros = array ( array(  'nombre' => 'cod_recurso', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 20,
                                          'valor' => $cod_recurso),
                                  array(  'nombre' => 'resultado', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => ''),
                            );
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            return $resultado[1]['valor'];
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
        } 
	}
	
	static public function get_lov_organismos_financieros_x_codigo ($codigo){
		$sql = "SELECT KRORFI.COD_ORGANISMO_FINANCIERO ||' - '|| KRORFI.DESCRIPCION lov_descripcion
				FROM KR_ORGANISMOS_FINANCIEROS KRORFI
				WHERE krorfi.cod_organismo_financiero = $codigo";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}
	
	static public function get_lov_organismos_financieros_x_nombre ($nombre, $filtro = array ()){
        if (isset($nombre)) {
			$trans_id = ctr_construir_sentencias::construir_translate_ilike('KRORFI.COD_ORGANISMO_FINANCIERO', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('KRORFI.DESCRIPCION', $nombre);
			$where = "($trans_id OR $trans_descripcion)";
        }else {
			$where = ' 1=1 ';
		}
		
		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'KRORFI', '1=1');
        
		$sql = "SELECT KRORFI.*, KRORFI.COD_ORGANISMO_FINANCIERO ||' - '|| KRORFI.DESCRIPCION lov_descripcion
				FROM KR_ORGANISMOS_FINANCIEROS KRORFI
				WHERE $where";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	
	static public function eliminar ($cod_recurso)
	{
        $sql = "BEGIN 
        			:resultado := pkg_pr_recursos.eliminar_recurso(:cod_recurso); 
        		END;";      
        		  	
        $parametros = [ 
    		[ 'nombre' => 'resultado', 
              'tipo_dato' => PDO::PARAM_STR,
              'longitud' => 4000,
              'valor' => ''],
    		[ 'nombre' => 'cod_recurso', 
              'tipo_dato' => PDO::PARAM_STR,
              'longitud' => 20,
              'valor' => $cod_recurso
            ],
        ];
        return ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
	}

	static public function cambiar_estado_activo_hijos ($cod_recurso){
		try {
			$sql = "BEGIN pkg_pr_recursos.cambiar_estado_activo_hijos(:codigo);END;";
	        $parametros = [ ['nombre' => 'codigo', 
                             'tipo_dato' => PDO::PARAM_INT,
                             'longitud' => 9,
                             'valor' => $cod_recurso]];
	        
	        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
	       	return 'OK';
		}catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
        }catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
        }
		
	}
	
	static public function cambiar_estado_figur_hijos ($cod_recurso, $figurativo){
		try {
			$sql = "BEGIN pkg_pr_recursos.cambiar_estado_figur_hijos(:codigo, :figurativo);END;";
	        $parametros = array ( array( 'nombre' => 'codigo', 
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 9,
	                                     'valor' => $cod_recurso),
	        					  array( 'nombre' => 'figurativo', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 1,
	                                     'valor' => $figurativo));
	        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
			return 'OK';   
		}catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
        }catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
        }
	}
	
	static public function cambiar_estado_reimp_hijos ($cod_recurso, $reimputacion){
		try {
			$sql = "BEGIN pkg_pr_recursos.cambiar_estado_reimp_hijos(:codigo, :reimputacion);END;";
	        $parametros = array ( array( 'nombre' => 'codigo', 
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 9,
	                                     'valor' => $cod_recurso),
	        					  array( 'nombre' => 'reimputacion', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 1,
	                                     'valor' => $reimputacion));
	        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
			return 'OK'; 
		}catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
        }catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
        }
	}
	
	static public function cambiar_estado_origen_hijos ($cod_recurso, $origen){
		try {
			$sql = "BEGIN pkg_pr_recursos.cambiar_estado_origen_hijos(:codigo, :origen);END;";
	        $parametros = array ( array( 'nombre' => 'codigo', 
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 9,
	                                     'valor' => $cod_recurso),
	        					  array( 'nombre' => 'origen', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 3,
	                                     'valor' => $origen));
	        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
			return 'OK';
		}catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
        }catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
        }
	}
	
	static public function cambiar_estado_devengar_hijos ($cod_recurso, $devengar){
		try{
			$sql = "BEGIN pkg_pr_recursos.cambiar_estado_devengar_hijos(:codigo, :devengar);END;";
	        $parametros = array ( array( 'nombre' => 'codigo', 
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 9,
	                                     'valor' => $cod_recurso),
	        					  array( 'nombre' => 'devengar', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 1,
	                                     'valor' => $devengar));
	        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
			return 'OK';   
		}catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
        }catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
        }
	}
	static public function ultimo_del_nivel ($cod_recurso){
    	if ($cod_recurso == null)	
    		$cod_recurso = 'null';
    	$sql = "SELECT pkg_pr_recursos.ultimo_del_nivel ($cod_recurso) valor from dual;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['valor'];
    	
    }
	
  	static public function valor_del_nivel ($codigo, $nivel){
    	if ($codigo == null)	
    		$codigo = 'null';
    	if ($nivel == null)
    		$nivel = 'null';
    	if (isset($nivel)  && !empty($nivel)){
	    	$sql ="SELECT pkg_pr_recursos.valor_del_nivel($codigo, $nivel) valor from dual;";
	   		$datos = toba::db()->consultar_fila($sql);
	   		return $datos['valor'];
    	}else return null;
    }

    static public function codigo_a_nivel_hasta ($cod_recurso_hasta, $nivel_recurso){
		$sql = "SELECT pkg_pr_recursos.CODIGO_A_NIVEL_HASTA($cod_recurso_hasta, $nivel_recurso) AS codigo FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['codigo'];
	}
}

?>
