<?php

class dao_fuentes_financieras {

	static public function get_datos($filtro = array()){
        $where= "1=1";
        
        if (isset($filtro['id_padre'])){
    		$where .= " and ff.cod_fuente_financiera_padre = ".$filtro['id_padre'];
    		unset($filtro['id_padre']);
    	}	
        
        if(isset($filtro))
        	$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ff', '1=1');
        
        $sql = "SELECT ff.*, 
        		pkg_pr_fuentes.activa (ff.cod_fuente_financiera) ui_activa,
		       pkg_pr_fuentes.afectacion_especifica (ff.cod_fuente_financiera) ui_afectacion_especifica,
		       pkg_pr_fuentes.imputable (ff.cod_fuente_financiera) ui_imputable,
		       pkg_pr_fuentes.rrhh (ff.cod_fuente_financiera) ui_rrhh,
		       pkg_pr_fuentes.MASCARA_APLICAR(ff.cod_fuente_financiera) cod_masc,
		          '['
		       || pkg_pr_fuentes.mascara_aplicar (ff.cod_fuente_financiera)
		       || '] '
		       || ff.descripcion AS descripcion_2       
                FROM PR_FUENTES_FINANCIERAS ff
                WHERE  $where";
        
        $datos = toba::db()->consultar($sql);
        
        return $datos;
    }
    
    static public function get_fuentes($filtro = array()){
        $where= "1=1";
        
        if (isset($filtro['sin_padre'])){
        	$where .= " and ff.cod_fuente_financiera_padre is null ";
        	unset($filtro['sin_padre']);
        }
        
        if(isset($filtro))
        	$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ff', '1=1');
        
        $sql = "SELECT ff.*, '['|| pkg_pr_fuentes.mascara_aplicar (ff.cod_fuente_financiera)|| '] '|| ff.descripcion AS descripcion_2
                FROM PR_FUENTES_FINANCIERAS ff
                WHERE  $where";
        
        $datos = toba::db()->consultar($sql);
        
        return $datos;
    }
    
    static public function get_lov_fuentes_x_cod_fuente($cod_fuente) {
        
        if (isset($cod_fuente)) {
            $sql = "SELECT ff.*, 
            			pkg_pr_fuentes.mascara_aplicar(ff.cod_fuente_financiera) ||' - '|| pkg_pr_fuentes.cargar_descripcion(ff.cod_fuente_financiera) lov_descripcion
                    FROM PR_FUENTES_FINANCIERAS ff
                    WHERE cod_fuente_financiera = ".quote($cod_fuente) .";";
            
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
        
        
    static public function get_lov_fuentes_x_nombre($nombre, $filtro = array()) 
	{ 
        
        if (isset($nombre)) {
			$campos = array(
						'ff.cod_fuente_financiera',
						'pkg_pr_fuentes.mascara_aplicar(ff.cod_fuente_financiera)',
						'pkg_pr_fuentes.cargar_descripcion(ff.cod_fuente_financiera)',
						"ff.cod_fuente_financiera ||' - '|| pkg_pr_fuentes.cargar_descripcion(ff.cod_fuente_financiera)",
						"pkg_pr_fuentes.mascara_aplicar(ff.cod_fuente_financiera) ||' - '|| pkg_pr_fuentes.cargar_descripcion(ff.cod_fuente_financiera)",
				);
			$where = ctr_construir_sentencias::construir_sentencia_busqueda($nombre, $campos, false);
        } else {
            $where = '1=1';
        }
        if (isset($filtro['cod_unidad_administracion']) && !empty($filtro['cod_unidad_administracion']) && isset($filtro['ejercicio']) && !empty($filtro['ejercicio'])){
            $where .= " AND (   (   '". $filtro['ui_sin_control_pres']."' = 'S'
              AND pkg_pr_fuentes.imputable (ff.cod_fuente_financiera) =
                                                                           'S'
              AND pkg_pr_fuentes.activa (ff.cod_fuente_financiera) = 'S'
             )
          OR (    '". $filtro['ui_sin_control_pres']."' = 'N'
              AND EXISTS (
                     SELECT 1
                       FROM pr_movimientos_egresos
                      WHERE cod_unidad_administracion =
                                      ".$filtro['cod_unidad_administracion']."
                        AND id_ejercicio = ".$filtro['ejercicio']."
                        AND cod_fuente_financiera =
                                                  ff.cod_fuente_financiera)
             )
         )";
            unset($filtro['cod_unidad_administracion']);
            unset($filtro['ejercicio']);
        }
        if (isset($filtro['devengado'])){
            if (empty($filtro['id_compromiso']))
                $filtro['id_compromiso'] = 'NULL';
            $where .= " AND (('".$filtro['ui_sin_control_pres']."' = 'S' 
                           and pkg_pr_fuentes.imputable(ff.cod_fuente_financiera) = 'S' 
                           and pkg_pr_fuentes.activa(ff.cod_fuente_financiera) = 'S') 
                           or ('".$filtro['ui_sin_control_pres']."' = 'N' and ((".$filtro['id_compromiso']." is null or (".$filtro['id_compromiso']." is not null 
                                     and exists(select 1 from ad_compromisos adco, ad_compromisos_det adcode, ad_compromisos_imp adcoim where (adco.id_compromiso = ".$filtro['id_compromiso']." OR adco.id_compromiso_aju = ".$filtro['id_compromiso'].") 
                                                        and adco.aprobado = 'S' and adco.anulado = 'N'  and adco.id_compromiso = adcode.id_compromiso and adcode.id_compromiso = adcoim.id_compromiso  and adcode.id_detalle = adcoim.id_detalle 
                                                        and adcode.cod_partida = '".$filtro['cod_partida']."' and adcoim.id_entidad = '".$filtro['id_entidad']."' and adcoim.id_programa = ".$filtro['id_programa']." and adcoim.cod_fuente_financiera = ff.cod_fuente_financiera))))))";
            unset($filtro['devengado']);
            unset($filtro['cod_partida']);
            unset($filtro['id_compromiso']);
            unset($filtro['ui_sin_control_pres']);
            unset($filtro['id_entidad']);
            unset($filtro['id_programa']);
            
        }

        if (isset($filtro['cod_un_ad'])) {
            
            $where.= " AND ( '".$filtro ['ui_sin_control_pres']."' = 'S' and pkg_pr_fuentes.imputable(ff.cod_fuente_financiera) = 'S'
                    and pkg_pr_fuentes.activa(ff.cod_fuente_financiera) = 'S') or ( '".$filtro['ui_sin_control_pres']."' = 'N' and
                    ((p_id_preventivo is null or (p_id_preventivo is not null
                    and exists(select  1 from ad_preventivos adpr, ad_preventivos_det adprde, ad_preventivos_imp adprim
                    where (adpr.id_preventivo = p_id_preventivo OR adpr.id_preventivo_aju = p_id_preventivo)
                    AND adpr.ANULADO = 'N' AND adpr.APROBADO = 'S' AND adpr.id_preventivo = adprde.id_preventivo AND adprde.id_preventivo = adprim.id_preventivo  and adprde.id_detalle = adprim.id_detalle
                    and adprde.cod_partida =".$filtro['cod_partida']." and adprim.id_entidad =".$filtro['id_entidad']." and adprim.id_programa =".$filtro['id_programa']." and adprim.cod_fuente_financiera = ff.cod_fuente_financiera)))
                    and exists(select 1 from pr_movimientos_egresos where cod_unidad_administracion = ".$filtro['cod_un_ad']." and id_ejercicio = ".$filtro['ui_id_ejercicio']."
                    and cod_fuente_financiera = ff.cod_fuente_financiera)))";
        }
        
        if (isset($filtro['id_preventivo'])) {
                if (empty($filtro['id_preventivo'])){
                        $where = str_replace("p_id_preventivo", "null", $where, $count);
                }else{
                        $where = str_replace("p_id_preventivo", $filtro['id_preventivo'], $where, $count);
                }
        }else{
            $where = str_replace("p_id_preventivo", "null", $where, $count);
        }

        if (isset($filtro['imputable'])) {
                $where .= " AND PKG_PR_FUENTES.IMPUTABLE(ff.COD_FUENTE_FINANCIERA) = 'S' ";
                unset($filtro['imputable']);
        }

        if (isset($filtro['en_actividad'])) {
                $where .= " AND PKG_PR_FUENTES.ACTIVA(ff.COD_FUENTE_FINANCIERA) = 'S' ";
                unset($filtro['en_actividad']);
        }
		
        unset($filtro['cod_un_ad']);
        unset($filtro['cod_partida']);
        unset($filtro['ui_id_ejercicio']);
        unset($filtro['id_preventivo']);
        unset($filtro['ui_sin_control_pres']);
        unset($filtro['id_entidad']);
        unset($filtro['id_programa']);
        
        

        $where .= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'ff', '1=1');

        $sql = "SELECT  ff.cod_fuente_financiera, 
                        pkg_pr_fuentes.mascara_aplicar(ff.cod_fuente_financiera) ||' - '|| pkg_pr_fuentes.cargar_descripcion(ff.cod_fuente_financiera) as lov_descripcion
                FROM PR_FUENTES_FINANCIERAS ff
                WHERE $where
                ORDER BY lov_descripcion ASC;";

        $datos = toba::db()->consultar($sql);

        return $datos;
        
    }
	
	static public function get_lov_fuentes_con_saldo_x_nombre($nombre, $filtro = array()) 
	{ 
		if (isset($filtro['cod_unidad_administracion']) && isset($filtro['cod_partida']) && isset($filtro['fecha_comprobante']) && isset($filtro['id_entidad']) && isset($filtro['id_programa'])) { 
			
			$cod_unidad_administracion = $filtro['cod_unidad_administracion'];
			$fecha_comprobante = $filtro['fecha_comprobante'];
			$cod_partida = $filtro['cod_partida'];
			$id_entidad = $filtro['id_entidad'];
			$id_programa = $filtro['id_programa'];
			
			if (isset($nombre)) {
				$campos = array(
						'ff.cod_fuente_financiera',
						'pkg_pr_fuentes.mascara_aplicar(ff.cod_fuente_financiera)',
						'pkg_pr_fuentes.cargar_descripcion(ff.cod_fuente_financiera)',
						"ff.cod_fuente_financiera ||' - '|| pkg_pr_fuentes.cargar_descripcion(ff.cod_fuente_financiera)",
						"pkg_pr_fuentes.mascara_aplicar(ff.cod_fuente_financiera) ||' - '|| pkg_pr_fuentes.cargar_descripcion(ff.cod_fuente_financiera)",
				);
				$where = ctr_construir_sentencias::construir_sentencia_busqueda($nombre, $campos, true);
			} else {
				$where = '1=1';
			}
			if (isset($filtro['cod_unidad_administracion']) && !empty($filtro['cod_unidad_administracion']) && isset($filtro['ejercicio']) && !empty($filtro['ejercicio'])){
				$where .= " AND (   (   '". $filtro['ui_sin_control_pres']."' = 'S'
				  AND pkg_pr_fuentes.imputable (ff.cod_fuente_financiera) =
																			   'S'
				  AND pkg_pr_fuentes.activa (ff.cod_fuente_financiera) = 'S'
				 )
			  OR (    '". $filtro['ui_sin_control_pres']."' = 'N'
				  AND EXISTS (
						 SELECT 1
						   FROM pr_movimientos_egresos
						  WHERE cod_unidad_administracion =
										  ".$filtro['cod_unidad_administracion']."
							AND id_ejercicio = ".$filtro['ejercicio']."
							AND cod_fuente_financiera =
													  ff.cod_fuente_financiera)
				 )
			 )";
				unset($filtro['cod_unidad_administracion']);
				unset($filtro['ejercicio']);
			}
			if (isset($filtro['devengado'])){
				if (empty($filtro['id_compromiso']))
					$filtro['id_compromiso'] = 'NULL';
				$where .= " AND (('".$filtro['ui_sin_control_pres']."' = 'S' 
							   and pkg_pr_fuentes.imputable(ff.cod_fuente_financiera) = 'S' 
							   and pkg_pr_fuentes.activa(ff.cod_fuente_financiera) = 'S') 
							   or ('".$filtro['ui_sin_control_pres']."' = 'N' and ((".$filtro['id_compromiso']." is null or (".$filtro['id_compromiso']." is not null 
										 and exists(select 1 from ad_compromisos adco, ad_compromisos_det adcode, ad_compromisos_imp adcoim where (adco.id_compromiso = ".$filtro['id_compromiso']." OR adco.id_compromiso_aju = ".$filtro['id_compromiso'].") 
															and adco.aprobado = 'S' and adco.anulado = 'N'  and adco.id_compromiso = adcode.id_compromiso and adcode.id_compromiso = adcoim.id_compromiso  and adcode.id_detalle = adcoim.id_detalle 
															and adcode.cod_partida = '".$filtro['cod_partida']."' and adcoim.id_entidad = '".$filtro['id_entidad']."' and adcoim.id_programa = ".$filtro['id_programa']." and adcoim.cod_fuente_financiera = ff.cod_fuente_financiera))))))";
				unset($filtro['devengado']);
				unset($filtro['cod_partida']);
				unset($filtro['id_compromiso']);
				unset($filtro['ui_sin_control_pres']);
				unset($filtro['id_entidad']);
				unset($filtro['id_programa']);
			}
			
			if (isset($filtro['cod_un_ad'])) {
				$where.= " AND ( '".$filtro ['ui_sin_control_pres']."' = 'S' and pkg_pr_fuentes.imputable(ff.cod_fuente_financiera) = 'S'
						and pkg_pr_fuentes.activa(ff.cod_fuente_financiera) = 'S') or ( '".$filtro['ui_sin_control_pres']."' = 'N' and
						((p_id_preventivo is null or (p_id_preventivo is not null
						and exists(select  1 from ad_preventivos adpr, ad_preventivos_det adprde, ad_preventivos_imp adprim
						where (adpr.id_preventivo = p_id_preventivo OR adpr.id_preventivo_aju = p_id_preventivo)
						AND adpr.ANULADO = 'N' AND adpr.APROBADO = 'S' AND adpr.id_preventivo = adprde.id_preventivo AND adprde.id_preventivo = adprim.id_preventivo  and adprde.id_detalle = adprim.id_detalle
						and adprde.cod_partida =".$filtro['cod_partida']." and adprim.id_entidad =".$filtro['id_entidad']." and adprim.id_programa =".$filtro['id_programa']." and adprim.cod_fuente_financiera = ff.cod_fuente_financiera)))
						and exists(select 1 from pr_movimientos_egresos where cod_unidad_administracion = ".$filtro['cod_un_ad']." and id_ejercicio = ".$filtro['ui_id_ejercicio']."
						and cod_fuente_financiera = ff.cod_fuente_financiera)))";
			}
			
			if (isset($filtro['id_preventivo'])) {
				if (empty($filtro['id_preventivo'])){
					$where = str_replace("p_id_preventivo", "null", $where, $count);
				}else{
					$where = str_replace("p_id_preventivo", $filtro['id_preventivo'], $where, $count);
				}
			}
			
			if (isset($filtro['imputable'])) {
				$where .= " AND PKG_PR_FUENTES.IMPUTABLE(ff.COD_FUENTE_FINANCIERA) = 'S' ";
				unset($filtro['imputable']);
			}
			
			if (isset($filtro['en_actividad'])) {
				$where .= " AND PKG_PR_FUENTES.ACTIVA(ff.COD_FUENTE_FINANCIERA) = 'S' ";
				unset($filtro['en_actividad']);
			}
			
			unset($filtro['cod_un_ad']);
			unset($filtro['cod_partida']);
			unset($filtro['ui_id_ejercicio']);
			unset($filtro['id_preventivo']);
			unset($filtro['ui_sin_control_pres']);
			unset($filtro['cod_unidad_administracion']);
			unset($filtro['fecha_comprobante']);
			unset($filtro['cod_partida']);
			unset($filtro['id_entidad']);
			unset($filtro['id_programa']);
			
			$where .= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'ff', '1=1');
			
			$sql = "SELECT  ff.*, 
							pkg_pr_fuentes.mascara_aplicar(ff.cod_fuente_financiera) ||' - '|| pkg_pr_fuentes.cargar_descripcion(ff.cod_fuente_financiera) || ' (' || TRIM(to_char(PKG_PR_TOTALES.SALDO_ACUMULADO_EGRESO(" . quote($cod_unidad_administracion) . ", PKG_KR_EJERCICIOS.RETORNAR_EJERCICIO(" . quote($fecha_comprobante) . "), " . quote($id_entidad) . ", " . quote($id_programa) . ", " . quote($cod_partida) . ", ff.cod_fuente_financiera, NULL, 'PRES', SYSDATE), '$999,999,999,990.00')) ||')' as lov_descripcion_saldo
					FROM PR_FUENTES_FINANCIERAS ff
					WHERE $where
					ORDER BY lov_descripcion_saldo ASC;";
			$datos = toba::db()->consultar($sql);
			
			return $datos;
		} else {
			return array();
		}
	}
	
	static public function get_es_afectacion_especifica_x_cod_fuente($cod_fuente) {
        
        if (isset($cod_fuente)) {
            $sql = "SELECT pkg_pr_fuentes.afectacion_especifica(ff.cod_fuente_financiera) afectacion_especifica
                    FROM PR_FUENTES_FINANCIERAS ff
                    WHERE cod_fuente_financiera = ".quote($cod_fuente) .";";
            $datos = toba::db()->consultar_fila($sql);
            
            if (isset($datos) && !empty($datos) && isset($datos['afectacion_especifica'])) {
                return $datos['afectacion_especifica'];
            } else {
                return 'N';
            }
        } else {
            return 'N';
        }
        
    }
    
    
	static public function cant_niveles (){
		$sql ="SELECT PKG_PR_FUENTES.cant_niveles AS cant_niveles FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cant_niveles']; 
	}
	static public function es_hoja ($cod_fuente){
		if (!is_null($cod_fuente))
			$sql = "SELECT PKG_PR_FUENTES.ES_HOJA($cod_fuente) AS ES_HOJA FROM DUAL;";
		else
			$sql = "SELECT PKG_PR_FUENTES.ES_HOJA(null) AS ES_HOJA FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['es_hoja'];
	}
	static public function activo ($cod_fuente){
		if (!is_null($cod_fuente))
			$sql = "SELECT PKG_PR_FUENTES.ACTIVA($cod_fuente) AS activo FROM DUAL;";
		else
			$sql = "SELECT PKG_PR_FUENTES.ACTIVA(null) AS activo FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['activo'];
	}
	static public function imputable ($cod_fuente){
		if (!is_null($cod_fuente))
			$sql = "SELECT PKG_PR_FUENTES.IMPUTABLE($cod_fuente) AS imputable FROM DUAL;";
		else
			$sql = "SELECT PKG_PR_FUENTES.IMPUTABLE(null) AS imputable FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['imputable'];
	}
	static public function get_hijos ($cod_fuente){
		$sql = "select PRF.*, '[' || PKG_PR_FUENTES.MASCARA_APLICAR(PRF.COD_FUENTE_FINANCIERA) ||'] ' || PRF.descripcion as descripcion_2
				from PR_FUENTES_FINANCIERAS PRF
				where PRF.COD_FUENTE_FINANCIERA_PADRE = $cod_fuente
				order by cod_fuente_financiera asc;";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
 	static public function cargar_descripcion($cod_fuente){
	   	$sql ="SELECT PKG_PR_FUENTES.CARGAR_DESCRIPCION($cod_fuente) AS descripcion FROM DUAL;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['descripcion'];
    }
	static public function tiene_hijos($cod_fuente){
	   	$sql ="SELECT PKG_PR_FUENTES.tiene_hijos($cod_fuente) AS tiene_hijo FROM DUAL;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['tiene_hijo'];
    }
    static public function get_nivel ($cod_fuente){
	     $sql = "SELECT NIVEL 
	             FROM PR_FUENTES_FINANCIERAS
	             WHERE COD_FUENTE_FINANCIERA = $cod_fuente;";
	     $datos = toba::db()->consultar_fila($sql);
	     return $datos['nivel'];
    }
	static public function mascara_aplicar ($cod_fuente){
		$sql = "SELECT PKG_PR_FUENTES.mascara_aplicar($cod_fuente) cod_fuente FROM DUAL";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cod_fuente'];
	}
	
	static public function valor_del_nivel ($cod_fuente, $nivel){
		$sql ="SELECT PKG_PR_FUENTES.VALOR_DEL_NIVEL($cod_fuente, $nivel) AS valor FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['valor']; 
	}
	
	static public function ultimo_del_nivel ($nivel){
		if (is_null($nivel))
			$sql ="SELECT PKG_PR_FUENTES.ULTIMO_DEL_NIVEL(NULL) AS valor FROM DUAL;";
		else 
			$sql ="SELECT PKG_PR_FUENTES.ULTIMO_DEL_NIVEL($nivel) AS valor FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['valor']; 
	}
	
	static public function existe ($cod_fuente){
		$sql = "SELECT NVL (MIN (1), 0) cant
        		FROM PR_FUENTES_FINANCIERAS
       			WHERE COD_FUENTE_FINANCIERA = $cod_fuente";
		$datos = toba::db()->consultar_fila($sql);
		if ($datos['cant'] > 0)
			return true;
		else 
			return false;
	}
	static public function armar_codigo ($nivel, $cod_fuente, $cod_fuente_padre){
		if ($cod_fuente_padre == null)
			$cod_fuente_padre = 'NULL';
		$sql ="SELECT  PKG_PR_FUENTES.armar_codigo($nivel, $cod_fuente, $cod_fuente_padre) AS valor FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['valor']; 
	}
	
	static public function rrhh ($cod_fuente){
		if ($cod_fuente == null)
			$cod_fuente = 'NULL';
		$sql ="SELECT  PKG_PR_FUENTES.RRHH($cod_fuente) AS valor FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['valor']; 
	}
	static public function afectacion_especifica ($cod_fuente){
		if ($cod_fuente == null)
			$cod_fuente = 'NULL';
		$sql ="SELECT  PKG_PR_FUENTES.afectacion_especifica($cod_fuente) AS valor FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['valor']; 
	}
	static public function eliminar ($cod_fuente)
	{
        $sql = "BEGIN 
        			:resultado := PKG_PR_FUENTES.eliminar_fuente(:cod_fuente); 
        		END;";   

        $parametros = [ 
        		[ 'nombre' => 'resultado', 
                  'tipo_dato' => PDO::PARAM_STR,
                  'longitud' => 4000,
                  'valor' => ''],
        		[ 'nombre' => 'cod_fuente', 
                  'tipo_dato' => PDO::PARAM_STR,
                  'longitud' => 20,
                  'valor' => $cod_fuente],
            ];
        return ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
	}
	
	
	static public function cargar_fuente($cod_fuente){
       $sql = "BEGIN PKG_PR_FUENTES.CARGAR_FUENTE(:codigo, :descripcion, :nivel, :cod_fuente_padre,:afectacion, :activa, :rrhh);END;";
       $parametros = array ( array(  'nombre' => 'codigo', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => $cod_fuente), 
       						 array(  'nombre' => 'descripcion', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 100,
                                     'valor' => ''),
      						 array(  'nombre' => 'nivel', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 3,
                                     'valor' => ''),
      						 array(  'nombre' => 'cod_fuente_padre', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => ''),
      						  array(  'nombre' => 'afectacion', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => ''),
      						 array(  'nombre' => 'activa', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => ''),
      						 array(  'nombre' => 'rrhh', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => ''));
	      						 
        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
        $datos = array (	"cod_fuente_financiera"=>$cod_fuente, 
	        				"descripcion"=>$resultado[1]['valor'], 
	        				"nivel"=>$resultado[2]['valor'], 
         					"cod_fuente_financiera_padre"=>$resultado[3]['valor'],
        					"afectacion_especifica"=>$resultado[4]['valor'],
	        				"activa"=>$resultado[5]['valor'],
       						"rrhh"=>$resultado[6]['valor']);
        return $datos;
   }
	
	static public function actualizar_fuente ($cod_fuente, $descripcion,$afectacion, $activa, $rrhh, $con_transaccion = true){
		try{
       		$sql = "BEGIN :resultado := PKG_PR_FUENTES.ACTUALIZAR_FUENTE(:codigo, :descripcion,:afectacion, :activa, :rrhh); END;";
       
	        $parametros = array (array(  'nombre' => 'codigo', 
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 9,
	                                     'valor' => $cod_fuente), 
	       						 array(  'nombre' => 'descripcion', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 100,
	                                     'valor' => $descripcion),
	       						  array(  'nombre' => 'afectacion', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 4,
	                                     'valor' => $afectacion),
	      						 array(  'nombre' => 'activa', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 4,
	                                     'valor' => $activa),
	      						  array(  'nombre' => 'rrhh', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 4,
	                                     'valor' => $rrhh),
	      						 array(  'nombre' => 'resultado', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 4000,
	                                     'valor' => ''));
	      	if ($con_transaccion)					 
            	toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            if ($resultado[5]['valor'] == 'OK'){
            	if ($con_transaccion){
                	toba::db()->cerrar_transaccion();
            	}
                 toba::notificacion()->info($resultado[5]['valor']);
	        }else{
	        	if ($con_transaccion){
	    	    	toba::db()->abortar_transaccion();
	        	}
                toba::notificacion()->error($resultado[5]['valor']);
            }
            
            return $resultado[5]['valor'];
            
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            if ($con_transaccion)
            	toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
            if ($con_transaccion)
            	toba::db()->abortar_transaccion();
        }   
	}
	
	static public function cambiar_estado_activo_hijos ($cod_fuente, $con_transaccion = true){
		try{
       		$sql = "BEGIN PKG_PR_FUENTES.CAMBIAR_ESTADO_ACTIVA_HIJOS($cod_fuente); END;";
       
	        $parametros = array (array(  'nombre' => 'codigo', 
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 9,
	                                     'valor' => $cod_fuente));
	      	if ($con_transaccion)				 
            	toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, array());
           	if ($con_transaccion)
               	toba::db()->cerrar_transaccion();
               	
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            if ($con_transaccion)
            	toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
            if ($con_transaccion)
            	toba::db()->abortar_transaccion();
        }   
	}
	
	static public function cambiar_estado_afecta_hijos ($cod_fuente, $afectacion, $con_transaccion = true){
		try{
       		$sql = "BEGIN PKG_PR_FUENTES.CAMBIAR_ESTADO_AFECTA_HIJOS(:codigo, :afectacion); END;";
       		$parametros = array ( array(  'nombre' => 'codigo', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => $cod_fuente), 
       						 array(  'nombre' => 'afectacion', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => $afectacion));
	      	if ($con_transaccion)				 
            	toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
           	if ($con_transaccion)
               	toba::db()->cerrar_transaccion();
               	
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            if ($con_transaccion)
            	toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
            if ($con_transaccion)
            	toba::db()->abortar_transaccion();
        }   
	}
   static public function crear_fuente($cod_fuente, $descripcion, $nivel, $cod_fuente_padre,$afectacion, $activa, $rrhh, $con_transaccion = true){
   	try{
       $sql = "BEGIN :resultado := PKG_PR_FUENTES.CREAR_FUENTE(:codigo, :descripcion, :nivel, :cod_fuente_padre,:afectacion, :activa, :rrhh); END;";
       
       $parametros = array ( array(  'nombre' => 'codigo', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => $cod_fuente), 
       						 array(  'nombre' => 'descripcion', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 100,
                                     'valor' => $descripcion),
      						 array(  'nombre' => 'nivel', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 3,
                                     'valor' => $nivel),
      						 array(  'nombre' => 'cod_fuente_padre', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => $cod_fuente_padre),
      						 array(  'nombre' => 'activa', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4,
                                     'valor' => $activa),
      						 array(  'nombre' => 'afectacion', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4,
                                     'valor' => $afectacion),
      						 array(  'nombre' => 'rrhh', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4,
                                     'valor' => $rrhh),
      						 array(  'nombre' => 'resultado', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4000,
                                     'valor' => ''));
      		if ($con_transaccion)				 
            	toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            if ($resultado[7]['valor'] == 'OK'){
            	if ($con_transaccion)
                	toba::db()->cerrar_transaccion();
	        }else{
	        	if ($con_transaccion)
	    	    	toba::db()->abortar_transaccion();
                toba::notificacion()->error($resultado[5]['valor']);
            }
            return $resultado[7]['valor'];
            
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            if ($con_transaccion)
            	toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
            if ($con_transaccion)
            	toba::db()->abortar_transaccion();
        }   
    } 

    /* Consultas nuevas para optimizacion */
	static public function getDatos ($filtro = [])
    {
    	$sql = "
    		SELECT LEVEL,FF.* 
    		FROM PR_FUENTES_FINANCIERAS FF
        		CONNECT BY PRIOR FF.COD_FUENTE_FINANCIERA=FF.COD_FUENTE_FINANCIERA_PADRE
        		START WITH FF.COD_FUENTE_FINANCIERA_PADRE IS NULL
        	ORDER BY LEVEL, FF.COD_FUENTE_FINANCIERA";
        return toba::db()->consultar($sql);
    }
}

?>
