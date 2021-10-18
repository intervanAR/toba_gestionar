<?php
class dao_conciliacion_bancaria {

	static public function get_conciliaciones($filtro = array()){
		$where =" 1=1 ";

		if (isset($filtro['observaciones'])){
			$where .= " and upper(adc.observaciones) like upper('%".$filtro['observaciones']."%')";
			unset($filtro['observaciones']);
		}

		if (isset($filtro) && $filtro != null)
			$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro,'ADC', " 1=1 ");
		$sql = "SELECT ADC.*,
				       to_char(ADC.fecha, 'DD/MM/YYYY') as fecha_format,
				       to_char(ADC.fecha_confirmado, 'DD/MM/YYYY') as fecha_confirmado_format,
				       to_char(ADC.fecha_anulado, 'DD/MM/YYYY') as fecha_anulado_format,
				       to_char(ADC.fecha_anula, 'DD/MM/YYYY') as fecha_anula_format,
				       to_char(ADC.fecha_carga, 'DD/MM/YYYY') as fecha_carga_format,
				       KRUA.DESCRIPCION as unidad_administracion,
				       krcb.NRO_CUENTA as nro_cuenta_banco,
				       krcb.DESCRIPCION as descripcion_cuenta
				FROM AD_CONCILIACIONES ADC
				     LEFT JOIN KR_UNIDADES_ADMINISTRACION KRUA ON ADC.COD_UNIDAD_ADMINISTRACION = KRUA.COD_UNIDAD_ADMINISTRACION
				     LEFT JOIN KR_CUENTAS_BANCO KRCB ON ADC.ID_CUENTA_BANCO = KRCB.ID_CUENTA_BANCO
				WHERE $where
				ORDER BY ADC.ID_CONCILIACION DESC;";

		$datos = toba::db()->consultar($sql);
		return $datos;

	}
	static public function get_conciliacion_x_id($id_conciliacion){
		if (isset($id_conciliacion)){
			$sql = "SELECT *
					FROM AD_CONCILIACIONES
					WHERE id_conciliacion = $id_conciliacion";
			$datos = toba::db()->consultar_fila($sql);
			return $datos;
		}else return null;
	}

	static public function get_datos_extra_detalle ($id_detalle){
		$sql = "SELECT det.id_conciliacion, det.id_detalle, det.id_movimiento_banco,
						       TO_CHAR (det.fecha_movimiento_banco,
						                'DD/MM/YYYY'
						               ) AS fecha_movimiento_banco,
						       krmov.detalle, krmov.debe ui_debe, krmov.haber ui_haber, '#'||krmov.ID_COMPROBANTE_PAGO ||' ('||adcomp.TIPO_COMPROBANTE_PAGO ||')' comprobante_pago
		  FROM ad_conciliaciones_det det, kr_movimientos_banco krmov left join ad_comprobantes_pago adcomp on krmov.ID_COMPROBANTE_PAGO = adcomp.ID_COMPROBANTE_PAGO
						 WHERE det.id_movimiento_banco = krmov.id_movimiento_banco and det.id_detalle = ".quote($id_detalle);
		return toba::db()->consultar_fila($sql);
	}

	static public function get_detalle_conciliaciones($id_conciliacion){
		$sql = "SELECT det.id_conciliacion, det.id_detalle, det.id_movimiento_banco,
				       TO_CHAR (det.fecha_movimiento_banco,
				                'DD/MM/YYYY'
				               ) AS fecha_movimiento_banco,
				       krmov.detalle, krmov.debe ui_debe, krmov.haber ui_haber, '#'||krmov.ID_COMPROBANTE_PAGO ||' ('||adcomp.TIPO_COMPROBANTE_PAGO ||')' comprobante_pago
  FROM ad_conciliaciones_det det, kr_movimientos_banco krmov left join ad_comprobantes_pago adcomp on krmov.ID_COMPROBANTE_PAGO = adcomp.ID_COMPROBANTE_PAGO
				 WHERE det.id_movimiento_banco = krmov.id_movimiento_banco and id_conciliacion = ".quote($id_conciliacion);
		return toba::db()->consultar($sql);
	}
	static public function get_lov_cuenta_banco_x_id($id_cuenta_banco)
	{
       if (isset($id_cuenta_banco) && !empty($id_cuenta_banco)) {
           $sql = "SELECT kcb.id_cuenta_banco ||' - '|| kcb.nro_cuenta ||' - '||kcb.descripcion as lov_descripcion
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


	static public function get_lov_cuenta_banco_x_nombre ($nombre, $filtro = array ()){
		if (isset($nombre)) {
			$trans_id = ctr_construir_sentencias::construir_translate_ilike('id_cuenta_banco', $nombre);
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_cuenta', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_id OR $trans_nro OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
		$sql = "SELECT KRCUBA.*, KRCUBA.ID_CUENTA_BANCO ||' - '|| KRCUBA.NRO_CUENTA ||' - '|| KRCUBA.DESCRIPCION AS lov_descripcion
				FROM KR_CUENTAS_BANCO KRCUBA
				WHERE $where AND (KRCUBA.ACTIVA = 'S'
				      AND KRCUBA.TIPO_CUENTA_BANCO = 'BAN'
				      AND ".$filtro['cod_unidad_administracion']." = KRCUBA.COD_UNIDAD_ADMINISTRACION
				      AND (pkg_kr_usuarios.usuario_tiene_ues(" . quote(toba::usuario()->get_id()) . ")='N' OR pkg_kr_usuarios.tiene_ue(" . quote(toba::usuario()->get_id()) . ",KRCUBA.cod_unidad_ejecutora)='S'))
				ORDER BY lov_descripcion";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}


	static public function get_lov_movimiento_x_id($id_movimiento_banco)
	{
       if (isset($id_movimiento_banco) && !empty($id_movimiento_banco)) {
           $sql = "SELECT krmoba.nro_movimiento || ' - '|| substr(krmoba.detalle,1,45) || ' - '|| TO_CHAR (krmoba.fecha_movimiento, 'DD/MM/YYYY') AS lov_descripcion
					FROM KR_MOVIMIENTOS_BANCO KRMOBA
					WHERE KRMOBA.ID_MOVIMIENTO_BANCO = $id_movimiento_banco";
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

	static public function get_lov_movimiento_x_nombre ($nombre, $filtro = array ()){
		$where = '';
		if (isset($nombre)) {
			//$trans_id = ctr_construir_sentencias::construir_translate_ilike('id_movimiento_banco', $nombre);
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_movimiento', $nombre);
			//$trans_desc = ctr_construir_sentencias::construir_translate_ilike('detalle', $nombre);
			$trans_fecha = ctr_construir_sentencias::construir_translate_ilike('fecha_movimiento', $nombre);
			//$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('DETALLE', $nombre);
			$where = "($trans_nro OR $trans_fecha)";
        } else {
            $where = ' 1=1 ';
        }

        if (isset($filtro['fecha_conciliacion']) && !empty($filtro['fecha_conciliacion'])){
        	$where .=" AND KRMOBA.fecha_movimiento <= to_date('".$filtro['fecha_conciliacion']."','YYYY/MM/DD') ";
        	unset($filtro['fecha_conciliacion']);
        }

		$sql = "SELECT KRMOBA.*, krmoba.id_movimiento_banco ||'-'|| krmoba.nro_movimiento ||' - '|| substr(krmoba.detalle,1,45) ||' - '|| TO_CHAR (krmoba.fecha_movimiento, 'DD/MM/YYYY') AS lov_descripcion
				FROM KR_MOVIMIENTOS_BANCO KRMOBA
				WHERE $where and (KRMOBA.ANULADO = 'N' AND KRMOBA.ID_CUENTA_BANCO = ".$filtro['id_cuenta_banco']."
				       AND PKG_KR_CUENTAS_CORRIENTE.movimiento_en_conciliacion(KRMOBA.ID_MOVIMIENTO_BANCO) = 'N')";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}




	static public function get_movimientos_cuenta_banco ($id_cuenta_banco, $id_conciliacion, $filtro = array()){
		$where = " 1=1 ";
		if (isset($filtro['fecha_conciliacion'])){
			$where .=" AND krmob.FECHA_MOVIMIENTO <= TO_DATE('".$filtro['fecha_conciliacion']."','YYYY/MM/DD') ";
			unset($filtro['fecha_conciliacion']);
		}
		if (isset($filtro['detalle'])){
			$where .=" AND upper(krmob.detalle) like upper('%".$filtro['detalle']."%')";
			unset($filtro['detalle']);
		}

		$where .= " and " . ctr_construir_sentencias::get_where_filtro($filtro, 'KRMOB', '1=1');
		$sql = "SELECT KRMOB.ID_MOVIMIENTO_BANCO,
					   KRMOB.NRO_MOVIMIENTO,
					    to_char(KRMOB.FECHA_MOVIMIENTO, 'YYYY/MM/DD') FECHA_MOVIMIENTO,
					   KRMOB.DETALLE, KRMOB.DEBE,
					   KRMOB.HABER
				FROM kr_movimientos_banco KRMOB
		LEFT JOIN AD_COMPROBANTES_PAGO ADCOP ON (ADCOP.ID_COMPROBANTE_PAGO = KRMOB.ID_COMPROBANTE_PAGO
		AND EXISTS (SELECT 1
      		FROM ad_comprobantes_pago acp, ad_cheques_propios acpr,ad_cheques ac
          WHERE acp.id_comprobante_pago = acpr.id_comprobante_pago(+)
        AND acpr.id_cheque = ac.id_cheque(+)
        AND AC.NRO_CHEQUE = (select ac.nro_cheque
 		from kr_movimientos_banco kmb,
	         ad_comprobantes_pago acp,
	         ad_cheques_propios acpr,
	         ad_cheques ac
     where kmb.id_comprobante_pago = acp.id_comprobante_pago(+)
      AND acp.id_comprobante_pago = acpr.id_comprobante_pago(+)
	  AND acpr.id_cheque = ac.id_cheque(+)
   	  AND KMB.ID_MOVIMIENTO_BANCO = KRMOB.ID_MOVIMIENTO_BANCO )
      AND acp.id_comprobante_pago = KRMOB.id_comprobante_pago))
				WHERE KRMOB.ANULADO = 'N'
					  AND $where
		              AND KRMOB.ID_CUENTA_BANCO = ".$id_cuenta_banco."
		              AND PKG_KR_CUENTAS_CORRIENTE.movimiento_en_conciliacion(KRMOB.ID_MOVIMIENTO_BANCO) = 'N'
		              AND NOT EXISTS (SELECT 1
		                              FROM AD_CONCILIACIONES_DET ACD
		                              WHERE ACD.ID_CONCILIACION = ".$id_conciliacion."
		                              AND ACD.ID_MOVIMIENTO_BANCO = KRMOB.ID_MOVIMIENTO_BANCO)";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

	static public function get_ui_nro_comprobante($id_movimiento_banco){
		if (isset($id_movimiento_banco)){
					$sql =  "SELECT CASE acp.tipo_comprobante_pago
							          WHEN 'TRR'
							             THEN tr.nro_transferencia
							          WHEN 'TRE'
							             THEN te.nro_transferencia
							          ELSE ac.nro_cheque
							       END ui_nro_comprobante
							  FROM kr_movimientos_banco kmb,
							       ad_comprobantes_pago acp,
							       ad_cheques_propios acpr,
							       ad_cheques ac,
							       ad_transferencias_recibidas tr,
							       ad_transferencias_efectuadas te
							 WHERE kmb.id_comprobante_pago = acp.id_comprobante_pago(+)
							   AND acp.id_comprobante_pago = acpr.id_comprobante_pago(+)
							   AND acp.id_comprobante_pago = tr.id_comprobante_pago(+)
							   AND acp.id_comprobante_pago = te.id_comprobante_pago(+)
							   AND acpr.id_cheque = ac.id_cheque(+)
							   AND kmb.id_movimiento_banco = $id_movimiento_banco";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['ui_nro_comprobante'];
		}else return null;
	}

	static public function get_ui_observacion($id_movimiento_banco){
		if (isset($id_movimiento_banco)){
			$sql =  "SELECT tr.observacion AS ui_observacion
					  FROM kr_movimientos_banco kmb,
					       ad_comprobantes_pago acp,
					       ad_transferencias_recibidas tr
					 WHERE kmb.id_comprobante_pago = acp.id_comprobante_pago(+)
					   AND acp.id_comprobante_pago = tr.id_comprobante_pago(+)
					   AND kmb.id_movimiento_banco = $id_movimiento_banco";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['ui_observacion'];
		}else return null;
	}

	static public function insertar_detalle ($datos_det){

		$sqls = array ();
		for ($i = 0; $i < sizeof($datos_det); $i++) {
			$sqls[$i] = "insert into ad_conciliaciones_det (id_conciliacion, id_movimiento_banco, fecha_movimiento_banco)
					     values (".$datos_det[$i]['id_conciliacion'].", ".$datos_det[$i]['id_movimiento_banco'].", (select fecha
																				                   from ad_conciliaciones
																				                   where id_conciliacion = ".$datos_det[$i]['id_conciliacion']."));";
		}
		$errores = toba::db()->ejecutar_transaccion($sqls);
		if ($errores > 0){
			toba::notificacion()->info("Error al guardar en Base de Datos");
		}
	}


	static public function confirmar_conciliacion ($id_conciliacion){
		if (isset($id_conciliacion)) {
            try {
                $sql = "BEGIN :resultado := pkg_ad_comprobantes_bancos.confirmar_conciliacion(:id_conciliacion);END;";
                $parametros = array(  array('nombre' => 'id_conciliacion',
					                        'tipo_dato' => PDO::PARAM_INT,
					                        'longitud' => 32,
					                        'valor' => $id_conciliacion),
					                  array('nombre' => 'resultado',
					                        'tipo_dato' => PDO::PARAM_STR,
					                        'longitud' => 4000,
					                        'valor' => ''),
					                );
	            toba::db()->abrir_transaccion();
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                if ($resultado[1]['valor'] == 'OK'){
                    toba::db()->cerrar_transaccion();
                }else{
                    toba::db()->abortar_transaccion();
                    toba::notificacion()->error("No se pudo confirmar la conciliación. ".$resultado[1]['valor']);
                }
                return $resultado[1]['valor'];
            } catch (toba_error_db $e_db) {
                toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
                toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
                toba::db()->abortar_transaccion();
            } catch (toba_error $e) {
                toba::notificacion()->error('Error ' . $e->get_mensaje());
                toba::logger()->error('Error ' . $e->get_mensaje());
                toba::db()->abortar_transaccion();
            }
        }
	}

	static public function desconfirmar_conciliacion ($id_conciliacion){
		if (isset($id_conciliacion)) {
            try {
                $sql = "BEGIN :resultado := pkg_ad_comprobantes_bancos.desconfirmar_conciliacion(:id_conciliacion);END;";
                $parametros = array(  array('nombre' => 'id_conciliacion',
					                        'tipo_dato' => PDO::PARAM_INT,
					                        'longitud' => 32,
					                        'valor' => $id_conciliacion),
					                  array('nombre' => 'resultado',
					                        'tipo_dato' => PDO::PARAM_STR,
					                        'longitud' => 4000,
					                        'valor' => ''),
					                );
	            toba::db()->abrir_transaccion();
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                if ($resultado[1]['valor'] == 'OK'){
                    toba::db()->cerrar_transaccion();
                }else{
                    toba::db()->abortar_transaccion();
                    toba::notificacion()->error($resultado[1]['valor']);
                }
                return $resultado[1]['valor'];
            } catch (toba_error_db $e_db) {
                toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
                toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
                toba::db()->abortar_transaccion();
            } catch (toba_error $e) {
                toba::notificacion()->error('Error ' . $e->get_mensaje());
                toba::logger()->error('Error ' . $e->get_mensaje());
                toba::db()->abortar_transaccion();
            }
        }
	}
	static public function anular_conciliacion ($id_conciliacion, $fecha, $con_transaccion = true){
		if (isset($id_conciliacion) && isset($fecha)) {
            try {
                $sql = "BEGIN :resultado := pkg_ad_comprobantes_bancos.anular_conciliacion(:id_conciliacion,:fecha);END;";
                $parametros = array(  array('nombre' => 'id_conciliacion',
					                        'tipo_dato' => PDO::PARAM_INT,
					                        'longitud' => 32,
					                        'valor' => $id_conciliacion),
                					  array('nombre' => 'fecha',
					                        'tipo_dato' => PDO::PARAM_INT,
					                        'longitud' => 32,
					                        'valor' => $fecha),
					                  array('nombre' => 'resultado',
					                        'tipo_dato' => PDO::PARAM_STR,
					                        'longitud' => 4000,
					                        'valor' => ''),
					                );
				if ($con_transaccion)
	            	toba::db()->abrir_transaccion();

                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                if ($con_transaccion){
	                if ($resultado[2]['valor'] == 'OK'){
	                    toba::db()->cerrar_transaccion();
	                }else{
	                    toba::db()->abortar_transaccion();
	                }
                }
                return $resultado[2]['valor'];
            } catch (toba_error_db $e_db) {
                toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
                toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
                toba::db()->abortar_transaccion();
            } catch (toba_error $e) {
                toba::notificacion()->error('Error ' . $e->get_mensaje());
                toba::logger()->error('Error ' . $e->get_mensaje());
                toba::db()->abortar_transaccion();
            }
        }
	}

	static public function get_fecha ($id_conciliacion){
		$sql = "SELECT to_char(trunc(fecha),'YYYY-MM-DD') fecha
			    FROM AD_CONCILIACIONES
			    WHERE ID_CONCILIACION = $id_conciliacion;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['fecha'];
	}

	//-------------------------------------------------------------------------------------------
	//--------------------  UI ITEMS ------------------------------------------------------------
	//-------------------------------------------------------------------------------------------

	static public function get_ui_nro_movimiento ($id_movimiento_banco){
		$sql = "SELECT NRO_MOVIMIENTO as ui_nro_movimiento
				FROM KR_MOVIMIENTOS_BANCO
				WHERE ID_MOVIMIENTO_BANCO = $id_movimiento_banco;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['ui_nro_movimiento'];
	}
	static public function get_ui_haber ($id_movimiento_banco){
		$sql = "SELECT HABER as ui_haber
				FROM KR_MOVIMIENTOS_BANCO
				WHERE ID_MOVIMIENTO_BANCO = $id_movimiento_banco;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['ui_haber'];
	}
	static public function get_ui_debe ($id_movimiento_banco){
		$sql = "SELECT DEBE as ui_debe
				FROM KR_MOVIMIENTOS_BANCO
				WHERE ID_MOVIMIENTO_BANCO = $id_movimiento_banco;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['ui_debe'];
	}
	static public function get_ui_detalle ($id_movimiento_banco){
		$sql = "SELECT DETALLE as ui_detalle
				FROM KR_MOVIMIENTOS_BANCO
				WHERE ID_MOVIMIENTO_BANCO = $id_movimiento_banco;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['ui_detalle'];
	}

	//-----------------------------------------------------------
	//--------------------- AUXILIARES --------------------------
	//-----------------------------------------------------------

	public static function get_cantidad_movimientos($id_conciliacion){
		if (!is_null($id_conciliacion)){
			$sql = "SELECT COUNT(1) CANTIDAD
					  FROM AD_CONCILIACIONES_DET
					 WHERE ID_CONCILIACION = $id_conciliacion";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['cantidad'];
		}else{
			return 0;
		}
	}

	static public function get_ui_nro_movimiento_banco ($id_movimiento_banco){
		$sql = "SELECT NRO_MOVIMIENTO as ui_nro_movimiento
				FROM KR_MOVIMIENTOS_BANCO
				WHERE ID_MOVIMIENTO_BANCO = $id_movimiento_banco;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos;
	}

	public static function get_conciliaciones_det($filtro = array())
	{
		$where =" 1=1 ";

		if (isset($filtro) && !empty($filtro))
			$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro,'ADC', " 1=1 ");

		$sql = "
			SELECT ADC.*, to_char(ADC.fecha_movimiento_banco, 'DD/MM/YYYY') AS fecha_movimiento_banco
			FROM AD_CONCILIACIONES_DET ADC
			WHERE $where
		";
		$datos = toba::db()->consultar($sql);

		foreach ($datos as $key => $value) {
			$movimiento = dao_conciliacion_bancaria::get_datos_extra_detalle($datos[$key]['id_detalle']);
			$datos[$key]['ui_nro_movimiento'] = self::get_ui_nro_movimiento_banco($datos[$key]['id_movimiento_banco'])['ui_nro_movimiento'];
			$datos[$key]['detalle'] = $movimiento['detalle'];
			$datos[$key]['comprobante_pago'] = $movimiento['comprobante_pago'];
			$datos[$key]['ui_debe'] = $movimiento['ui_debe'];
			$datos[$key]['ui_haber'] = $movimiento['ui_haber'];
		}

		return $datos;
	}
}
?>