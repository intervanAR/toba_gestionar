<?php

class dao_retenciones {
	
	static public function get_lov_retenciones_x_id_retencion($id_retencion) {
        
        if (isset($id_retencion)) {
            $sql = "SELECT adrt.id_retencion||' - '||adrt.descripcion lov_descripcion
                    FROM ad_retenciones adrt
                    WHERE adrt.id_retencion = ".quote($id_retencion) .";";
            
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
	
    static public function get_lov_retenciones_x_nombre($nombre, $filtro = array()) 
	{ 
        
        if (isset($nombre)) {
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('id_retencion', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_codigo OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
        if (isset($filtro['para_cuentas_corrientes'])){
        	$where .= " AND (ADRT.AUTOMATICA = 'S' AND ADRT.ACTIVA = 'S')";
        	unset($filtro['para_cuentas_corrientes']);
        }
        
		if (isset($filtro['para_ordenes_pago'])){
			$where .=" AND  (    adrt.activa = 'S'
					   AND adrt.automatica = 'N'
					   AND adrt.nivel_retencion = 'O'
							 )";
		  unset($filtro['para_ordenes_pago']);
		}		
        $where .= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'adrt', '1=1');

        $sql = "SELECT  ADRT.ID_RETENCION ID_RETENCION,
						adrt.id_retencion||' - '||adrt.descripcion lov_descripcion
				FROM AD_RETENCIONES ADRT
				WHERE $where
                ORDER BY lov_descripcion ASC;";

        $datos = toba::db()->consultar($sql);

        return $datos;
        
    }
	
	static public function get_retenciones($filtro = array()) 
	{ 
		$where = '1=1';
        
		if (isset($filtro['desde_recibo_pago']) && isset($filtro['id_retencion_orden'])) {
			$where .= " AND (" . quote($filtro['id_retencion_orden']) . " <> '-1' OR (" . quote($filtro['id_retencion_orden']) . " = '-1' AND (ADRT.NIVEL_RETENCION = 'R' AND ADRT.ACTIVA = 'S')))";
			unset($filtro['desde_recibo_pago']);
			unset($filtro['id_retencion_orden']);
		}
		
		$where .= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'adrt', '1=1');

        $sql = "SELECT  adrt.descripcion descripcion_retencion, ADRT.*,
						ADTIRE.*,
						adrt.id_retencion||' - '||adrt.descripcion lov_descripcion,
						adrt.id_retencion||' - '||adrt.descripcion || 
						CASE 
							WHEN adrt.automatica = 'S' THEN '  (Automática)'
							ELSE ''
						END as lov_descripcion_aut
				FROM AD_RETENCIONES ADRT
				JOIN AD_TIPOS_RETENCION ADTIRE ON (ADRT.COD_TIPO_RETENCION = ADTIRE.COD_TIPO_RETENCION)
				WHERE $where
                ORDER BY adrt.id_retencion desc;";

        $datos = toba::db()->consultar($sql);

        return $datos;
        
    }
	
	static public function get_retenciones_2($filtro = array()) 
	{ 
		$where = '1=1';
        
		if (isset($filtro['desde_recibo_pago']) && isset($filtro['id_retencion_orden'])) {
			$where .= " AND (" . quote($filtro['id_retencion_orden']) . " <> '-1' OR (" . quote($filtro['id_retencion_orden']) . " = '-1' AND (ADRT.NIVEL_RETENCION = 'R' AND ADRT.ACTIVA = 'S')))";
			unset($filtro['desde_recibo_pago']);
			unset($filtro['id_retencion_orden']);
		}
		
		$where .= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'adrt', '1=1');

        $sql = "SELECT adrt.*, 
				       CASE
				             WHEN adrt.ACTIVA = 'S'
				                THEN 'Si'
				             ELSE 'No'
				       END AS activa_format,
				       CASE
				             WHEN adrt.PARA_EGRESOS = 'S'
				                THEN 'Si'
				             ELSE 'No'
				       END AS para_egresos_format,
				       CASE
				             WHEN adrt.SOBRE_NETO = 'S'
				                THEN 'Si'
				             ELSE 'No'
				       END AS sobre_neto_format,
				       CASE
				             WHEN adrt.USA_ESCALA = 'S'
				                THEN 'Si'
				             ELSE 'No'
				       END AS usa_escala_format,
				       CASE
				             WHEN adrt.CALC_ACUMULADO = 'S'
				                THEN 'Si'
				             ELSE 'No'
				       END AS calc_acumulado_format,
				       CASE
				             WHEN adrt.CALC_SOBRE_EXCEDENTE = 'S'
				                THEN 'Si'
				             ELSE 'No'
				       END AS calc_sobre_excedente_format,
				        CASE
				             WHEN adrt.rrhh = 'S'
				                THEN 'Si'
				             ELSE 'No'
				       END AS rrhh_format,
				        CASE
				             WHEN adrt.automatica = 'S'
				                THEN 'Si'
				             ELSE 'No'
				       END AS automatica_format,
				       CASE
				             WHEN adrt.CALC_SOBRE_PAGO_ACT = 'S'
				                THEN 'Si'
				             ELSE 'No'
				       END AS calc_sobre_pago_act_format,
				       adtr.DESCRIPCION as tipo_retencion
				  FROM ad_retenciones adrt, ad_tipos_retencion adtr
				 WHERE adrt.COD_TIPO_RETENCION = adtr.COD_TIPO_RETENCION and $where
              ORDER BY adrt.id_retencion desc;";

        $datos = toba::db()->consultar($sql);

        return $datos;
        
    }
	static public function get_retencion_x_id($id_retencion) {
        
        if (isset($id_retencion)) {
            $sql = "SELECT adrt.*
                    FROM ad_retenciones adrt
                    WHERE adrt.id_retencion = ".quote($id_retencion) .";";
            
            $datos = toba::db()->consultar_fila($sql);
            
			return $datos;
            
        } else {
            return array();
        }
        
    }
	
	static public function get_tipos_retenciones($filtro = array()) 
	{ 
		$where = '1=1';
        
		$where .= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'adtire', '1=1');

        $sql = "SELECT  ADTIRE.*,
						adtire.cod_tipo_retencion||' - '||adtire.descripcion lov_descripcion,
						adtire.tipo_cuenta_corriente
				        || ' ('
				        || (SELECT rv_meaning
				              FROM cg_ref_codes
				             WHERE rv_domain = 'KR_TIPO_CUENTA_CORRIENTE'
				               AND rv_low_value = adtire.tipo_cuenta_corriente)
				        || ')' tipo_cta_cte_format,
       decode(adtire.aux_con_cuenta, 'S','Si','No') aux_con_cuenta_format
				FROM AD_TIPOS_RETENCION ADTIRE
				WHERE $where
                ORDER BY lov_descripcion ASC;";

        $datos = toba::db()->consultar($sql);

        return $datos;
        
    }
	
	static public function get_es_automatica($id_retencion) {
        
        if (isset($id_retencion)) {
            $sql = "SELECT adrt.automatica
                    FROM ad_retenciones adrt
                    WHERE adrt.id_retencion = ".quote($id_retencion) .";";
            
            $datos = toba::db()->consultar_fila($sql);
            
            if (isset($datos) && !empty($datos) && isset($datos['automatica'])) {
                return $datos['automatica'];
            } else {
                return 'N';
            }
        } else {
            return 'N';
        }
    }
	
	static public function borrar_pago_retencion($id_retencion_pago, $con_transsaccion = false) 
	{
		if (isset($id_retencion_pago)) {
			$sql = "BEGIN :resultado := pkg_ad_retenciones.borrar_pago_retencion(:id_retencion_pago); END;";

			$parametros = array(array(  'nombre' => 'id_retencion_pago', 
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $id_retencion_pago),
								array(	'nombre' => 'resultado', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
				);
			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, 'Se borro un pago para la retención.', 'Error en el borrado del pago para la retención.', $con_transsaccion);
			return $resultado[1]['valor'];
		}
	}
	
	static public function generar_pago_retencion($id_retencion_pago, $con_transsaccion = false) 
	{
		if (isset($id_retencion_pago)) {
			$sql = "BEGIN :resultado := pkg_ad_retenciones.generar_pago_retencion(:id_retencion_pago); END;";

			$parametros = array(array(  'nombre' => 'id_retencion_pago', 
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $id_retencion_pago),
								array(	'nombre' => 'resultado', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
				);
			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, 'Se generó un pago para la retención.', 'Error en el generado del pago para la retención.', $con_transsaccion);
			return $resultado[1]['valor'];
		}
	}
        
  public static function get_aux_con_cuenta($id_retencion){
      if (isset($id_retencion)) {
            $sql = "SELECT adrt.aux_con_cuenta aux_con_cuenta
                    FROM ad_retenciones r,ad_tipos_retencion adrt
                    WHERE r.cod_tipo_retencion = adrt.cod_tipo_retencion AND r.id_retencion = ".quote($id_retencion) .";";
            
            $datos = toba::db()->consultar_fila($sql);
            
            if (isset($datos) && !empty($datos) && isset($datos['aux_con_cuenta'])) {
                return $datos['aux_con_cuenta'];
            } else {
                return 'N';
            }
        } else {
            return 'N';
        }
  }
  
   public static function get_aux_con_cuenta_x_tipo_ret($cod_tipo_retencion){
      if (isset($cod_tipo_retencion)) {
            $sql = "SELECT adrt.aux_con_cuenta aux_con_cuenta
                    FROM ad_tipos_retencion adrt
                    WHERE adrt.cod_tipo_retencion = ".quote($cod_tipo_retencion) .";";
            
            $datos = toba::db()->consultar_fila($sql);
            
            if (isset($datos) && !empty($datos) && isset($datos['aux_con_cuenta'])) {
                return $datos['aux_con_cuenta'];
            } else {
                return 'N';
            }
        } else {
            return 'N';
        }
  }
  
  public static function num_retencion_existente($id_retencion, $nro_comprobante){
	  if (isset($nro_comprobante)) {
			$sql = "BEGIN :resultado := PKG_AD_COMPROBANTES_PAGOS.num_retencion_existente(:id_retencion,:nro_comprobante); END;";

			$parametros = array(array(  'nombre' => 'id_retencion', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 32,
										'valor' => $id_retencion),
								array(  'nombre' => 'nro_comprobante', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 32,
										'valor' => $nro_comprobante),
								array(	'nombre' => 'resultado', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);
			$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);   
			return $resultado[2]['valor'];
		}else{
			return '';
		}
	}
  
  static public function get_descripcion_x_cod_tipo_retencion($cod_tipo_retencion) {
        
        if (isset($cod_tipo_retencion)) {
            $sql = "SELECT  adtire.descripcion
					FROM AD_TIPOS_RETENCION ADTIRE
					WHERE ADTIRE.cod_tipo_retencion = ".quote($cod_tipo_retencion) .";";
            
            $datos = toba::db()->consultar_fila($sql);
            
            if (isset($datos) && !empty($datos) && isset($datos['descripcion'])) {
                return $datos['descripcion'];
            } else {
                return '';
            }
        } else {
            return '';
        }
    }
	
	static public function get_retenciones_exportacion($filtro = array()) 
	{ 
		if (isset($filtro['cod_tipo_retencion']) && !empty($filtro['cod_tipo_retencion']) && isset($filtro['fecha_desde']) && !empty($filtro['fecha_desde']) && isset($filtro['fecha_hasta']) && !empty($filtro['fecha_hasta'])) {
			$where = "";
			if (isset($filtro['cod_unidad_ejecutora']) && !empty($filtro['cod_unidad_ejecutora'])) {
				$where .= " AND rp.cod_unidad_ejecutora = " . quote($filtro['cod_unidad_ejecutora']);
			}
			$sql = "SELECT	are.id_comprobante_pago,
							ar.id_retencion,
							rp.id_recibo_pago,
							are.nro_comprobante,
							atr.funcion_formato_salida,
							ap.id_proveedor
					FROM	ad_retenciones ar,
							ad_tipos_retencion atr,
							ad_retenciones_efectuadas are,
							ad_retenciones_pago arp,
							ad_recibos_pago rp,
							kr_cuentas_corriente kcc,
							ad_proveedores ap
					WHERE ar.cod_tipo_retencion = atr.cod_tipo_retencion
					AND ar.id_retencion = are.id_retencion
					AND are.id_retencion_pago = arp.id_retencion_pago
					AND arp.id_recibo_pago = rp.id_recibo_pago
					AND rp.id_cuenta_corriente = kcc.id_cuenta_corriente
					AND kcc.id_proveedor = ap.id_proveedor(+)
					AND are.importe > 0
					AND are.nro_comprobante IS NOT NULL
					AND rp.aprobado = 'S'
					AND rp.anulado = 'N'
					AND are.fecha_retencion BETWEEN " . quote($filtro['fecha_desde']) . " AND " . quote($filtro['fecha_hasta']) . "
					AND ar.cod_tipo_retencion = " . quote($filtro['cod_tipo_retencion']) . "
					$where;";

			$datos = toba::db()->consultar($sql);

			return $datos;
			
		} else {
			return array();
		}   
    }
	
	static public function exportar_retencion($id_comprobante_pago, $funcion_formato_salida, $nro_renglon, &$cadena = '') 
	{
		if (isset($id_comprobante_pago) && !empty($id_comprobante_pago) && isset($funcion_formato_salida) && !empty($funcion_formato_salida)) {
			$sql = "BEGIN :resultado := pkg_ad_retenciones.exportar_retencion(:id_comprobante_pago, :funcion_formato_salida, :nro_renglon, :l_cadena); END;";

			$parametros = array(array(  'nombre' => 'id_comprobante_pago', 
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $id_comprobante_pago),
								array(  'nombre' => 'funcion_formato_salida', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => $funcion_formato_salida),
								array(  'nombre' => 'nro_renglon', 
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 11,
										'valor' => $nro_renglon),
								array(  'nombre' => 'l_cadena', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								array(	'nombre' => 'resultado', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
				);
				
			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al exportar la retención.');
			$cadena = $resultado[3]['valor'];
			return $resultado[4]['valor'];
		}
	}
  
	
	static public function get_lov_tipo_retencion_x_codigo ($codigo){
		$sql = "SELECT ADTIRE.*, ADTIRE.COD_TIPO_RETENCION ||' - '||ADTIRE.DESCRIPCION ||' - '|| CG.RV_MEANING LOV_DESCRIPCION
				FROM AD_TIPOS_RETENCION ADTIRE, CG_REF_CODES CG
				WHERE ADTIRE.COD_TIPO_RETENCION = $codigo";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}
	
	static public function get_lov_tipo_retencion_x_nombre ($nombre, $filtro = array()){
		$where = "";
	  	if (isset($nombre)) {
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('ADTIRE.COD_TIPO_RETENCION', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('ADTIRE.DESCRIPCION', $nombre);
			$where = "($trans_codigo OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
		
		$sql = "SELECT ADTIRE.*, ADTIRE.COD_TIPO_RETENCION ||' - '||ADTIRE.DESCRIPCION ||' - '|| CG.RV_MEANING LOV_DESCRIPCION
				FROM AD_TIPOS_RETENCION ADTIRE, CG_REF_CODES CG
				WHERE $where and CG.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE' AND CG.RV_LOW_VALUE = ADTIRE.TIPO_CUENTA_CORRIENTE";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}


	public static function get_renteciones_cuentas_x_id_rentencion ($id_retencion){
		$sql = "SELECT adrcu.id_retencion,  adrcu.id_cuenta_corriente, adrcu.fecha_desde, adrcu.fecha_hasta,
				       KRCUE.DESCRIPCION
				  FROM AD_RETENCIONES_CUENTAS adrcu, KR_CUENTAS_CORRIENTE KRCUE
				 WHERE ADRCU.ID_CUENTA_CORRIENTE = KRCUE.ID_CUENTA_CORRIENTE AND id_retencion = $id_retencion
				 ORDER BY KRCUE.DESCRIPCION";
		return toba::db()->consultar($sql);
	}
}



?>
