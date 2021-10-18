<?php

class dao_cuentas_banco {
	
	static public function get_modelos_cheques($filtro= array())
	{    
        $where= "1=1";
		
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ADM', '1=1');
        
        $sql = "SELECT	ADM.*, ADM.descripcion ||' ('||ADM.id_modelo||')' as lov_descripcion
				FROM AD_MODELOS_CHEQUE ADM				
                WHERE  $where";
        
        $datos = toba::db()->consultar($sql);
        
        return $datos;        
    }
	
	static public function get_cuentas_banco($filtro= array())
	{    
        $where= "1=1";
		$sql1 = "SELECT PKG_KR_USUARIOS.in_ua_tiene_acceso(upper('".toba::usuario()->get_id()."')) unidades_ad FROM DUAL";
		$res = toba::db()->consultar_fila($sql1); 
		$where = "KRCUBA.COD_UNIDAD_ADMINISTRACION IN ".$res['unidades_ad'];
		
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'KRCUBA', '1=1');
        
        $sql = "SELECT KRCUBA.*, KRUA.DESCRIPCION AS UNIDAD_ADMINISTRACION,KRUE.DESCRIPCION AS UNIDAD_EJECUTORA,
				       KRMO.SIMBOLO AS SIMBOLO_MONEDA, KRBCO.DESCRIPCION AS BANCO,  krcuba.nro_cuenta ||' - '||krcuba.descripcion as lov_descripcion
				FROM KR_CUENTAS_BANCO KRCUBA 
				LEFT OUTER JOIN KR_BANCOS L_KRBA ON KRCUBA.ID_BANCO = L_KRBA.ID_BANCO
				LEFT JOIN KR_UNIDADES_ADMINISTRACION KRUA ON KRCUBA.COD_UNIDAD_ADMINISTRACION = KRUA.COD_UNIDAD_ADMINISTRACION
				LEFT JOIN KR_UNIDADES_EJECUTORAS KRUE ON KRCUBA.COD_UNIDAD_EJECUTORA = KRUE.COD_UNIDAD_EJECUTORA
				LEFT JOIN KR_MONEDAS KRMO ON KRCUBA.COD_MONEDA = KRMO.COD_MONEDA
				LEFT JOIN KR_BANCOS KRBCO ON KRCUBA.ID_BANCO = KRBCO.ID_BANCO
                WHERE  $where
                ORDER BY ID_CUENTA_BANCO DESC";
        
        $datos = toba::db()->consultar($sql);
        
        return $datos;        
    }
    
    static public function obtener_id_conciliacion ($id_movimiento_banco){
    	$sql = "SELECT PKG_KR_CUENTAS_BANCO.obtener_id_conciliacion($id_movimiento_banco) id_conciliacion FROM DUAL;";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['id_conciliacion'];
    }
	
	static public function get_cuenta_banco_x_id($id_cuenta_banco)
	{
       if (isset($id_cuenta_banco)) {
           $sql = "SELECT	KRCUBA.*, KRCUBA.nro_cuenta ||' - '|| KRCUBA.descripcion as lov_descripcion
					FROM KR_CUENTAS_BANCO KRCUBA 
                   WHERE KRCUBA .id_cuenta_banco = $id_cuenta_banco
                   ORDER BY id_cuenta_banco ASC;";  
           return toba::db()->consultar_fila($sql);
       } else {
           return array();
       }
    }
     
	static public function get_id_nro_descripcion_cuenta_banco_x_id($id_cuenta_banco)
	{
       if (isset($id_cuenta_banco) && !empty($id_cuenta_banco)) {
           $sql = "SELECT	kcb.nro_cuenta ||' - '||kcb.descripcion as lov_descripcion
                   FROM KR_CUENTAS_BANCO kcb
                   WHERE kcb.id_cuenta_banco = $id_cuenta_banco
                   ORDER BY lov_descripcion;";  
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
	
	static public function get_lov_cuentas_banco_x_nombre($nombre, $filtro = array()) 
	{
		
		if (isset($nombre)) {
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('id_cuenta_banco', $nombre);
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_cuenta', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_codigo OR $trans_nro OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
		
		if (isset($filtro['trr'])){ //transferencias recibidas
			if(isset($filtro['cod_unidad_ejecutora'])&&(empty($filtro['cod_unidad_ejecutora']))){
				$filtro['cod_unidad_ejecutora']= 'null';
			}else{
				if (!isset($filtro['cod_unidad_ejecutora'])){
					$filtro['cod_unidad_ejecutora']= 'null';
				}
			}
			
			$where.=" AND (".$filtro['cod_unidad_ejecutora']." IS NULL OR kcb.cod_unidad_ejecutora = ".$filtro['cod_unidad_ejecutora']." ) ";
			unset($filtro['trr']);
			unset($filtro['cod_unidad_ejecutora']);
		}
		
		if (isset($filtro['not_in_tipo_cuenta_banco'])) {
			$where .= " AND tipo_cuenta_banco NOT IN {$filtro['not_in_tipo_cuenta_banco']} ";
			unset($filtro['not_in_tipo_cuenta_banco']);
		}
		
		if (isset($filtro['valida_tipo_cuenta']) && isset($filtro['tipo_cuenta_distinta'])) {
			$where .= "AND (" . quote($filtro['valida_tipo_cuenta']) . " = 'N' 
							or 
							(" . quote($filtro['valida_tipo_cuenta']) . " = 'S' 
								and (" . quote($filtro['tipo_cuenta_distinta']) . " = 'S' 
									and (kcb.tipo_cuenta_banco <> NVL(" . quote($filtro['tipo_cuenta_global']) . ",0)) 
									or 
									(" . quote($filtro['tipo_cuenta_distinta']) . " = 'N' 
										and (" . quote($filtro['tipo_cuenta_global']) . " is null 
											or 
											kcb.tipo_cuenta_banco = " . quote($filtro['tipo_cuenta_global']) . "
										)
									)
								)
							))";
			unset($filtro['valida_tipo_cuenta']);
			unset($filtro['tipo_cuenta_distinta']);
			unset($filtro['tipo_cuenta_global']);
		}
		if (isset($filtro['chequeras'])){
			$where .= " AND (kcb.TIPO_CUENTA_BANCO = 'BAN' AND kcb.ACTIVA = 'S' AND (pkg_kr_usuarios.usuario_tiene_ues(" . quote(toba::usuario()->get_id()) . ")='N' OR pkg_kr_usuarios.tiene_ue(" . quote(toba::usuario()->get_id()) . ",kcb.cod_unidad_ejecutora)='S'))";
			unset($filtro['chequeras']);
		}
		if (isset($filtro['tipo_comprobante_pago']) && !empty($filtro['tipo_comprobante_pago'])) {
			$where .= "AND EXISTS (SELECT 1 FROM ad_comprobantes_pago ACP WHERE ACP.id_cuenta_banco = kcb.id_cuenta_banco AND ACP.tipo_comprobante_pago = " . quote($filtro['tipo_comprobante_pago']) . ") ";
			unset($filtro['tipo_comprobante_pago']);
		}
		
		$where .= ' AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'kcb', '1=1');

        $sql = "SELECT	kcb.*,
						kcb.nro_cuenta ||' - '||kcb.descripcion as lov_descripcion
				FROM KR_CUENTAS_BANCO kcb
				WHERE $where
				ORDER BY lov_descripcion;";		
		
        $datos = toba::db()->consultar($sql);

        return $datos;
		
	}

	static public function get_movimientos_banco ($id_cuenta_banco, $filtro = []){

		$where = " 1=1 ";
		if (isset($filtro['detalle']) && !empty($filtro['detalle'])){
			$where .=" and upper(krmov.detalle) like upper('%".$filtro['detalle']."%')";
			unset($filtro['detalle']);
		}
		$where .= ' AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'krmov', '1=1');

		$sql = "SELECT krmov.*, decode(krmov.anulado, 'S','Si','No') anulado_format
				  FROM kr_movimientos_banco krmov
				 WHERE krmov.id_cuenta_banco = $id_cuenta_banco and $where";
		return toba::db()->consultar($sql);
	}

	static public function get_aplicaciones_bebe ($id_movimiento_banco){
		if (isset($id_movimiento_banco)){
			$sql = "SELECT DISTINCT a.*, b.nro_movimiento, b.detalle, b.nro_movimiento
					FROM kr_aplicaciones_bancos a, kr_movimientos_banco b
					WHERE a.id_movimiento_banco = $id_movimiento_banco
					AND a.id_movimiento_banco_apl = b.id_movimiento_banco";
			$datos = toba::db()->consultar($sql);
			return $datos;
		}else return null;
	}

	static public function get_aplicaciones_haber ($id_movimiento_banco){
		if (isset($id_movimiento_banco)){
			$sql = "SELECT DISTINCT A.*, B.NRO_MOVIMIENTO, B.DETALLE, b.nro_movimiento
					FROM KR_APLICACIONES_bancos A, KR_MOVIMIENTOS_banco B  
					WHERE A.ID_MOVIMIENTO_banco_APL = $id_movimiento_banco
					      AND A.ID_MOVIMIENTO_banco = B.ID_MOVIMIENTO_banco";
			$datos = toba::db()->consultar($sql);
			return $datos;
		}else return null;
	}
	
	/////////////////////////////////
	////SUB CUENTAS BANCOS
	////////////////////////////////
	
	static public function get_sub_cuentas_banco($filtro= array())
	{    
        $where= "1=1";
		
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'KRCUBA', '1=1');
        
        $sql = "SELECT	KRCUBA.*
				FROM KR_CUENTAS_BANCO KRCUBA 
				LEFT OUTER JOIN KR_BANCOS L_KRBA ON KRCUBA.ID_BANCO = L_KRBA.ID_BANCO
                WHERE  $where";
        
        $datos = toba::db()->consultar($sql);
        
        return $datos;        
    }
	
	static public function get_sub_cuentas_banco_listado($filtro= array())
	{    
        $where= "1=1";
		
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'KRSUCUBA', '1=1');
        
        $sql = "SELECT	KRSUCUBA.*, krsucuba.cod_sub_cuenta_banco ||' - '||krsucuba.descripcion as lov_descripcion
				FROM KR_SUB_CUENTAS_BANCO KRSUCUBA 				
                WHERE  $where";
        
        $datos = toba::db()->consultar($sql);
        
        return $datos;        
    }
	
	static public function get_sub_cuenta_banco_x_id($id_sub_cuenta_banco)
	{
       if (isset($id_sub_cuenta_banco)) {
           $sql = "SELECT	scb.*
					FROM KR_SUB_CUENTAS_BANCO scb
                   WHERE scb .id_sub_cuenta_banco = $id_sub_cuenta_banco
                   ORDER BY id_sub_cuenta_banco ASC;";  
           return toba::db()->consultar_fila($sql);
       } else {
           return array();
       }
    } 
	
	static public function get_id_nro_descripcion_sub_cuenta_banco_x_id($id_sub_cuenta_banco)
	{
       if (isset($id_sub_cuenta_banco) && !empty($id_sub_cuenta_banco)) {
           $sql = "SELECT	scb.id_sub_cuenta_banco||' - '||scb.descripcion as lov_descripcion
                   FROM KR_SUB_CUENTAS_BANCO scb
                   WHERE scb.id_sub_cuenta_banco = $id_sub_cuenta_banco
                   ORDER BY lov_descripcion;";  
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
	
	static public function get_lov_sub_cuentas_banco_x_nombre($nombre, $filtro = array()) 
	{
		if (isset($nombre)) {
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('id_sub_cuenta_banco', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_codigo OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
		
		$where .= ' AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'scb', '1=1');

        $sql = "SELECT	scb.*, scb.id_sub_cuenta_banco||' - '||scb.descripcion as lov_descripcion
				FROM KR_SUB_CUENTAS_BANCO scb
				WHERE $where
				ORDER BY lov_descripcion;";		
        $datos = toba::db()->consultar($sql);

        return $datos;
		
	}
	
	////////////////////////////////
	///////////////////////////////
	//////////////////////////////
	
	static public function get_chequeras_cuenta_banco($filtro= array())
	{    
        $where= "1=1";
		
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ADCHBA', '1=1');
        
        $sql = "SELECT	ADCHBA.*,
						ADCHBA.id_chequera_banco || ' (Cheques desde ' || ADCHBA.nro_cheque_desde || ' hasta ' || ADCHBA.nro_cheque_hasta || ')' || CASE WHEN adchba.pago_diferido = 'S' THEN ' - (Pago diferido)' ELSE '' END lov_descripcion
				FROM AD_CHEQUERAS_BANCO ADCHBA
                WHERE  $where
				ORDER BY lov_descripcion ASC;";
        
        $datos = toba::db()->consultar($sql);
        
        return $datos;        
    }
	
	static public function get_datos_chequeras_cuenta_banco($id_chequera_banco)
	{    
        if (isset($id_chequera_banco)) {
			$sql = "SELECT	ADCHBA.*,
							ADCHBA.id_chequera_banco || ' (Cheques desde ' || ADCHBA.nro_cheque_desde || ' hasta ' || ADCHBA.nro_cheque_hasta || ')' || CASE WHEN adchba.pago_diferido = 'S' THEN ' - (Pago diferido)' ELSE '' END lov_descripcion
					FROM AD_CHEQUERAS_BANCO ADCHBA
					WHERE  ADCHBA.id_chequera_banco = " . quote($id_chequera_banco) .";";
			
			$datos = toba::db()->consultar_fila($sql);

			return $datos;        
		} else {
			return array();
		}
	}
	
	static public function get_lov_chequera_cuenta_banco_x_id($id_chequera_banco)
	{
       if (isset($id_chequera_banco)) {
           $sql = "SELECT	ADCHBA.*,
							ADCHBA.id_chequera_banco || ' (Cheques desde ' || ADCHBA.nro_cheque_desde || ' hasta ' || ADCHBA.nro_cheque_hasta || ')' || CASE WHEN adchba.pago_diferido = 'S' THEN ' - (Pago diferido)' ELSE '' END lov_descripcion
					FROM AD_CHEQUERAS_BANCO ADCHBA
					WHERE  ADCHBA.id_chequera_banco = " . quote($id_chequera_banco) .";";
		   
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
	
	
	
	static public function get_id_nro_cheque_x_id($id_cheque)
	{
       if (isset($id_cheque)) {
           $sql = "SELECT   case when cb.SERIE is not null then
					             'Nro. '||ac.nro_cheque ||' (Serie '|| cb.SERIE ||' )' 
					        else
					             'Nro. '||ac.nro_cheque
					        end lov_descripcion
                     FROM AD_CHEQUES ac, AD_CHEQUERAS_BANCO cb
					WHERE cb.id_chequera_banco= ac.id_chequera_banco and ac.id_cheque = $id_cheque
                   ORDER BY lov_descripcion;";  
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
	
	static public function get_lov_cheques_x_nombre($nombre, $filtro = array()) 
	{
		if (isset($nombre)) {
			//$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('id_cheque', $nombre);
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_cheque', $nombre);
			$where = "($trans_nro)";
        } else {
            $where = '1=1';
        }
		
		if(isset($filtro['id_cuenta_banco'])){
			$where.= ' AND cb.id_cuenta_banco= '.quote($filtro['id_cuenta_banco']);
			unset($filtro['id_cuenta_banco']);
		}
		if(isset($filtro['chequera_activa'])){
			$where.= ' AND cb.activa= '.quote($filtro['chequera_activa']);
			unset($filtro['chequera_activa']);
		}
		
		$where .= ' AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'ac', '1=1');

        $sql = "SELECT	ac.*, 
        			case when cb.SERIE is not null then
			             'Nro. '||ac.nro_cheque ||' (Serie '|| cb.SERIE ||' )' 
			        else
			             'Nro. '||ac.nro_cheque
			        end lov_descripcion
				FROM AD_CHEQUES ac, AD_CHEQUERAS_BANCO cb
				WHERE cb.id_chequera_banco= ac.id_chequera_banco
				AND $where
				ORDER BY ac.nro_cheque asc;";
        $datos = toba::db()->consultar($sql);

        return $datos;
		
	}
	
	//Se obtiene la cantidad de cheques libres consecutivos de la chequera 
	//seleccionada comenzando con el cheque seleccionado.
	static public function get_cantidad_cheques_libres_consecutivos($id_chequera_banco, $id_cheque_inicial)
	{
		if (isset($id_chequera_banco) && isset($id_cheque_inicial)) {
            $sql = "SELECT	pkg_ad_cheques.cheques_libres_consecutivos('$id_chequera_banco', '$id_cheque_inicial') as cantidad
                    FROM DUAL;";

            $datos = toba::db()->consultar_fila($sql);
			if (isset($datos) && !empty($datos) && isset($datos['cantidad'])) {
				return $datos['cantidad'];
			} else {
				return 0;
			}
        } else {
            return 0;
        }  
	}
	
	static public function asignar_cheques_banco($datos) 
	{
		if (isset($datos) && !empty($datos) && isset($datos['id_recibo_pago']) && isset($datos['id_cuenta_banco']) && isset($datos['pago_diferido']) && isset($datos['cod_moneda']) && isset($datos['id_chequera_banco']) && isset($datos['id_cheque']) && isset($datos['cantidad_cheques']) && isset($datos['marcar_impreso'])) {
			if (!isset($datos['id_comprobante_pago'])) {
				$datos['id_comprobante_pago'] = "";
			}
			$sql = "BEGIN :resultado := pkg_ad_cheques.asignar_cheques(	:id_recibo_pago, :id_comprobante_pago,:id_cuenta_banco, :pago_diferido, :cod_moneda, :id_chequera_banco, :id_cheque, :cantidad_cheques,	:marcar_impreso); END;";

			$parametros = array(array(  'nombre' => 'id_recibo_pago', 
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $datos['id_recibo_pago']),
								array(  'nombre' => 'id_comprobante_pago', 
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $datos['id_comprobante_pago']),
								array(  'nombre' => 'id_cuenta_banco', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 11,
										'valor' => $datos['id_cuenta_banco']),
								array(  'nombre' => 'pago_diferido', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 1,
										'valor' => $datos['pago_diferido']),
								array(  'nombre' => 'cod_moneda', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 3,
										'valor' => $datos['cod_moneda']),
								array(  'nombre' => 'id_chequera_banco', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 11,
										'valor' => $datos['id_chequera_banco']),
								array(  'nombre' => 'id_cheque', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 11,
										'valor' => $datos['id_cheque']),
								array(  'nombre' => 'cantidad_cheques', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 11,
										'valor' => $datos['cantidad_cheques']),
								array(  'nombre' => 'marcar_impreso', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 1,
										'valor' => $datos['marcar_impreso']),
								array(	'nombre' => 'resultado', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
				);
			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, 'La asignación de cheques finalizó exitosamente.', 'Error en la asignacion de cheques.');
			return $resultado[9]['valor'];
		}
	}
	
	static public function asignar_cheques_tr($datos) 
	{
		if (isset($datos) && !empty($datos) && isset($datos['id_comprobante_pago']) && isset($datos['id_cuenta_banco']) && isset($datos['cod_moneda']) && isset($datos['id_chequera_banco']) && isset($datos['id_cheque']) && isset($datos['marcar_impreso'])) {
			
			$sql = "BEGIN :resultado := pkg_ad_cheques.asignar_cheques_tr(:id_comprobante_pago,:id_cuenta_banco, :cod_moneda, :id_chequera_banco, :id_cheque, :marcar_impreso); END;";

			$parametros = array(array(  'nombre' => 'id_comprobante_pago', 
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $datos['id_comprobante_pago']),
								array(  'nombre' => 'id_cuenta_banco', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 11,
										'valor' => $datos['id_cuenta_banco']),
								array(  'nombre' => 'cod_moneda', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 3,
										'valor' => $datos['cod_moneda']),
								array(  'nombre' => 'id_chequera_banco', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 11,
										'valor' => $datos['id_chequera_banco']),
								array(  'nombre' => 'id_cheque', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 11,
										'valor' => $datos['id_cheque']),
								array(  'nombre' => 'marcar_impreso', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 1,
										'valor' => $datos['marcar_impreso']),
								array(	'nombre' => 'resultado', 
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
				);
			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, 'La asignación de cheques finalizó exitosamente.', 'Error en la asignacion de cheques.');
			return $resultado[6]['valor'];
		}
	}

	static public function get_cuentas_predefinidas ($filtro = []){
		$where = "1=1";
        
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'admcp', '1=1');

        $sql = "SELECT   admcp.*,
				         admcp.cod_unidad_administracion || ' - ' || krua.descripcion ua,
				         (SELECT cod_unidad_ejecutora || ' - ' || descripcion
				            FROM kr_unidades_ejecutoras krue
				           WHERE admcp.cod_unidad_ejecutora = krue.cod_unidad_ejecutora) ue,
				         krmo.descripcion moneda,
				            krcb.id_cuenta_banco
				         || ' - '
				         || krcb.descripcion
				         || ' (NroCta: '
				         || krcb.nro_cuenta
				         || (SELECT ', ' || descripcion || ')'
				               FROM kr_bancos
				              WHERE id_banco = krcb.id_banco)
				         || ')' cuenta_banco
				    FROM ad_matriz_cuentas_pred admcp,
				         kr_monedas krmo,
				         kr_unidades_administracion krua,
				         kr_cuentas_banco krcb
				   WHERE admcp.cod_moneda = krmo.cod_moneda
				     AND admcp.id_cuenta_banco = krcb.id_cuenta_banco
				     AND admcp.cod_unidad_administracion = krua.cod_unidad_administracion
				     AND $where
				ORDER BY admcp.cod_unidad_administracion,
				         admcp.cod_moneda,
				         admcp.tipo_cuenta_pred,
				         admcp.cod_unidad_ejecutora";

        $datos = toba::db()->consultar($sql);
        return $datos;
	}
}

?>
