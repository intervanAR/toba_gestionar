<?php

class dao_recibos_banco {

    static public function get_recibos_banco($filtro = array(), $orden=array()) 
	{
        $desde= null; $hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];
			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}
		
		$where = self::armar_where($filtro);
		
        $sql = "SELECT	rb.*, 
        				decode(rb.confirmado,'S','Si','No') confirmado_format,
        			    decode(rb.anulado,'S','Si','No') anulado_format,
						to_char(rb.fecha_comprobante, 'dd/mm/yyyy') fecha_comprobante_format, 
						to_char(rb.fecha_anulacion, 'dd/mm/yyyy') fecha_anulacion_format, 
						CASE
							WHEN rb.automatico = 'S' THEN 'Si'
							ELSE 'No'
						END automatico_format,
						trim(to_char(rb.importe, '$999,999,999,990.00')) as importe_format,
						trim(to_char(rb.importe_nominal, '$999,999,999,990.00')) as importe_nominal_format,
						km.descripcion moneda,
						atcb.cod_tipo_comprobante || ' - ' || atcb.descripcion tipo_comprobante,
						kcbd.nro_cuenta || ' - ' || kcbd.descripcion cuenta_banco_desde,
						kcbh.nro_cuenta || ' - ' || kcbh.descripcion cuenta_banco_hasta,
						kscbd.COD_SUB_CUENTA_BANCO || ' - ' || kscbd.descripcion sub_cuenta_banco_desde,
						kscbh.COD_SUB_CUENTA_BANCO || ' - ' || kscbh.descripcion sub_cuenta_banco_hasta,
						CASE
							WHEN rb.origen_recibo = 'PAG' THEN 'Pago'
							WHEN rb.origen_recibo = 'COB' THEN 'Cobro'
							WHEN rb.origen_recibo = 'TRA' THEN 'Transferencia fondos'
							ELSE ''
						END origen_recibo_format,
						CASE
							WHEN rb.origen_recibo = 'PAG' THEN rb.id_pago
							WHEN rb.origen_recibo = 'COB' THEN rb.id_cobro
							WHEN rb.origen_recibo = 'TRA' THEN rb.ID_TRANSFERENCIA_FONDOS
							ELSE null
						END id_origen_recibo
                FROM AD_RECIBOS_BANCO rb
				JOIN AD_TIPOS_COMPROBANTE_BANCO atcb ON rb.cod_tipo_comprobante =  atcb.cod_tipo_comprobante
				JOIN KR_MONEDAS km ON rb.cod_moneda =  km.cod_moneda
				LEFT JOIN KR_CUENTAS_BANCO kcbd ON rb.id_cuenta_banco =  kcbd.id_cuenta_banco
				LEFT JOIN KR_CUENTAS_BANCO kcbh ON rb.id_cuenta_banco_hasta =  kcbh.id_cuenta_banco
				LEFT JOIN KR_SUB_CUENTAS_BANCO kscbd ON rb.id_sub_cuenta_banco =  kscbd.id_sub_cuenta_banco
				LEFT JOIN KR_SUB_CUENTAS_BANCO kscbh ON rb.id_sub_cuenta_banco_hasta =  kscbh.id_sub_cuenta_banco
				WHERE  $where
				ORDER BY rb.id_recibo_banco DESC ";

        $sql= dao_varios::paginador($sql, null, $desde, $hasta, null, $orden);
	    $datos = toba::db()->consultar($sql);
        return $datos;
    }
	static public function armar_where ($filtro = array())
	{
        $where = "1=1";
        if (isset($filtro['observaciones'])) {
            $where .= "AND " . ctr_construir_sentencias::construir_translate_ilike('rb.observaciones', $filtro['observaciones']);
            unset($filtro['observaciones']);
        }
		if (isset($filtro['id_origen_recibo'])) {
			$where .= "AND (rb.id_pago = " . quote($filtro['id_origen_recibo']) . " OR rb.id_cobro = " . quote($filtro['id_origen_recibo']) . " OR rb.id_transferencia_fondos = " . quote($filtro['id_origen_recibo']) . ") ";
			unset($filtro['id_origen_recibo']);
		}
		if (isset($filtro['ids_comprobantes'])) {
            $where .= "AND rb.id_recibo_banco IN (" . $filtro['ids_comprobantes'] . ") ";
            unset($filtro['ids_comprobantes']);
        }
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'rb', '1=1');
        return $where;
	}
	
	static public function get_cantidad ($filtro = array())
	{
		$where = self::armar_where($filtro);
		$sql = " SELECT count(*) cantidad
				   FROM AD_RECIBOS_BANCO rb
						JOIN AD_TIPOS_COMPROBANTE_BANCO atcb ON rb.cod_tipo_comprobante =  atcb.cod_tipo_comprobante
						JOIN KR_MONEDAS km ON rb.cod_moneda =  km.cod_moneda
						LEFT JOIN KR_CUENTAS_BANCO kcbd ON rb.id_cuenta_banco =  kcbd.id_cuenta_banco
						LEFT JOIN KR_CUENTAS_BANCO kcbh ON rb.id_cuenta_banco_hasta =  kcbh.id_cuenta_banco
						LEFT JOIN KR_SUB_CUENTAS_BANCO kscbd ON rb.id_sub_cuenta_banco =  kscbd.id_sub_cuenta_banco
						LEFT JOIN KR_SUB_CUENTAS_BANCO kscbh ON rb.id_sub_cuenta_banco_hasta =  kscbh.id_sub_cuenta_banco
				  WHERE $where ";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cantidad'];
	}
	public static function get_importes_encabezado_recibo_banco($id_recibo_banco) 
	{
        if (isset($id_recibo_banco)) {
            $sql_sel = "SELECT  arb.importe,
								arb.importe_nominal,
								arb.cotizacion
					FROM ad_recibos_banco arb
					WHERE arb.id_recibo_banco = " . quote($id_recibo_banco) . ";";
            $datos = toba::db()->consultar_fila($sql_sel);
            return $datos;
        } else {
            return array();
        }
    }
	
	static public function get_datos_extras_recibo_banco_x_id($id_recibo_banco) 
	{
        if (isset($id_recibo_banco)) {
            $sql = "SELECT	arb.anulado,
							arb.confirmado,
							arb.automatico,
							CASE
								WHEN arb.origen_recibo = 'PAG' THEN arb.id_pago
								WHEN arb.origen_recibo = 'COB' THEN arb.id_cobro
								WHEN arb.origen_recibo = 'TRA' THEN arb.ID_TRANSFERENCIA_FONDOS
								ELSE null
							END id_origen_recibo,
							arb.fecha_anulacion
                        FROM AD_RECIBOS_BANCO arb
                        WHERE arb.id_recibo_banco = " . $id_recibo_banco;

            $datos = toba::db()->consultar_fila($sql);

            return $datos;
        } else {
            return array();
        }
    }
	
	static public function get_tipos_comprobantes_banco($filtro = array()) 
	{
        $where = " 1=1 ";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ADTICOBA', '1=1');
        $sql_sel = "SELECT	ADTICOBA.COD_TIPO_COMPROBANTE COD_TIPO_COMPROBANTE,
							ADTICOBA.DESCRIPCION DESCRIPCION
					FROM AD_TIPOS_COMPROBANTE_BANCO ADTICOBA
					WHERE $where
					ORDER BY DESCRIPCION";
        $datos = toba::db()->consultar($sql_sel);
        return $datos;
    }
	
	static public function get_origenes_recibo_banco() 
	{
		$datos = array(
						array(	'origen' => 'PAG',
								'descripcion' => 'Pago'),
						array(	'origen' => 'COB',
								'descripcion' => 'Cobro'),
						array(	'origen' => 'TRA',
								'descripcion' => 'transferencia fondos'),
		);
        return $datos;
    }
	
	public static function get_condicion_tipo_comprobante($cod_tipo_comprobante)
	{
		try{
			if (isset($cod_tipo_comprobante)&&(!empty($cod_tipo_comprobante))) {	
				$sql= "BEGIN pkg_ad_comprobantes_bancos.condicion_tipo_coprobante(:cod_tipo_comprobante,:tipo_comprobante_pago, :id_matriz_comp_pago, :figurativo_d, :figurativo_h, :cod_tipo_transaccion, :automatico, :imputa_mov); END;";

				$parametros = array (	array(  'nombre' => 'cod_tipo_comprobante', 
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => $cod_tipo_comprobante),
										array(	'nombre' => 'tipo_comprobante_pago',
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => ''),
										array(	'nombre' => 'id_matriz_comp_pago',
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => ''),
										array(	'nombre' => 'figurativo_d',
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => ''),
										array(	'nombre' => 'figurativo_h',
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => ''),
										array(	'nombre' => 'cod_tipo_transaccion',
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => ''),
										array(	'nombre' => 'automatico',
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => ''),
										array(	'nombre' => 'imputa_mov',
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => ''),
								);
				$resultados = toba::db()->ejecutar_store_procedure($sql, $parametros);   
				
				$datos = array();
				foreach ($resultados as $resultado) {
					$datos[$resultado['nombre']] = $resultado['valor'];
				}
				return $datos;

			} else{
					return array();
			}                
        } catch (toba_error_db $e_db) {
           // toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
          	return array();
        } catch (toba_error $e) {
            //toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
         	return array();
        } catch (PDOException $e_pdo) {
			toba::logger()->error('Error '.$e_pdo->getMessage());
			return array();
		}
	}
	
	public static function get_condicion_matriz($id_matriz_comp_pago)
	{
		try{
			if (isset($id_matriz_comp_pago)&&(!empty($id_matriz_comp_pago))) {	
				$sql= "BEGIN pkg_ad_comprobantes_bancos.condicion_matriz (:id_matriz_comp_pago, :estado_comprobante, :estado_hasta, :entrega_dif, :vencimiento_dif, :cuenta_origen, :tipo_cuenta_pred_o, :cuenta_destino, :tipo_cuenta_pred_d, :tipo_operacion); END;";

				$parametros = array (	array(  'nombre' => 'id_matriz_comp_pago', 
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => $id_matriz_comp_pago),
										array(	'nombre' => 'estado_comprobante',
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => ''),
										array(	'nombre' => 'estado_hasta',
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => ''),
										array(	'nombre' => 'entrega_dif',
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => ''),
										array(	'nombre' => 'vencimiento_dif',
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => ''),
										array(	'nombre' => 'cuenta_origen',
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => ''),
										array(	'nombre' => 'tipo_cuenta_pred_o',
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => ''),
										array(	'nombre' => 'cuenta_destino',
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => ''),
										array(	'nombre' => 'tipo_cuenta_pred_d',
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => ''),
										array(	'nombre' => 'tipo_operacion',
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => ''),
								);
				$resultados = toba::db()->ejecutar_store_procedure($sql, $parametros);   
				
				$datos = array();
				foreach ($resultados as $resultado) {
					$datos[$resultado['nombre']] = $resultado['valor'];
				}
				if (isset($datos['cuenta_origen']) && isset($datos['cuenta_destino']) && $datos['cuenta_origen'] <> $datos['cuenta_destino']) {
					$datos['tipo_cuenta_distinta'] = 'S';
				} else {
					$datos['tipo_cuenta_distinta'] = 'N';
				}
				return $datos;

			} else{
					return array();
			}                
        } catch (toba_error_db $e_db) {
           // toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
          	return array();
        } catch (toba_error $e) {
        //    toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
         	return array();
        } catch (PDOException $e_pdo) {
			toba::logger()->error('Error '.$e_pdo->getMessage());
			return array();
		}
	}
	
	public static function get_datos_cuenta_banco_predeterminada($cod_unidad_administracion, $tipo_cuenta_pred, $cod_moneda, $cod_unidad_ejecutora=null)
	{
		try{
			if (isset($cod_unidad_administracion)&&!empty($cod_unidad_administracion) && isset($tipo_cuenta_pred)&&!empty($tipo_cuenta_pred) && isset($cod_moneda)&&!empty($cod_moneda)) {
				if (!isset($cod_unidad_ejecutora)) {
					$cod_unidad_ejecutora = '';
				}
				$sql_1= "BEGIN :id_cuenta_banco := PKG_AD_COMPROBANTES_BANCOS.DETERMINAR_CUENTA_PREDEF(:cod_unidad_administracion, :cod_unidad_ejecutora, :tipo_cuenta_pred, :cod_moneda); END;";

				$parametros_1 = array (	array(  'nombre' => 'cod_unidad_administracion', 
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => $cod_unidad_administracion),
										array(  'nombre' => 'cod_unidad_ejecutora', 
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => $cod_unidad_ejecutora),
										array(	'nombre' => 'tipo_cuenta_pred',
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => $tipo_cuenta_pred),
										array(	'nombre' => 'cod_moneda',
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => $cod_moneda),
										array(	'nombre' => 'id_cuenta_banco',
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => ''),
								);
				$resultados_1 = toba::db()->ejecutar_store_procedure($sql_1, $parametros_1);
				
				if (isset($resultados_1) && !empty($resultados_1) && isset($resultados_1[4]['valor'])) {
					$sql_2= "BEGIN PKG_AD_COMPROBANTES_BANCOS.DATOS_CTA_BCO(:id_cuenta_banco, :nro_cuenta_banco, :descripcion, :tipo_cuenta_banco); END;";
					
					$parametros_2 = array (	array(  'nombre' => 'id_cuenta_banco', 
													'tipo_dato' => PDO::PARAM_STR,
													'longitud' => 20,
													'valor' => $resultados_1[4]['valor']),
											array(	'nombre' => 'nro_cuenta_banco',
													'tipo_dato' => PDO::PARAM_STR,
													'longitud' => 20,
													'valor' => ''),
											array(	'nombre' => 'descripcion',
													'tipo_dato' => PDO::PARAM_STR,
													'longitud' => 4000,
													'valor' => ''),
											array(	'nombre' => 'tipo_cuenta_banco',
													'tipo_dato' => PDO::PARAM_STR,
													'longitud' => 20,
													'valor' => ''),
									);
					$resultados_2 = toba::db()->ejecutar_store_procedure($sql_2, $parametros_2);
				} else {
					$resultados_2 = array();
				}
				
				$datos = array();
				foreach ($resultados_1 as $resultado) {
					$datos[$resultado['nombre']] = $resultado['valor'];
				}
				foreach ($resultados_2 as $resultado) {
					$datos[$resultado['nombre']] = $resultado['valor'];
				}
				return $datos;

			} else{
					return array();
			}                
        } catch (toba_error_db $e_db) {
         //   toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
          	return array();
        } catch (toba_error $e) {
    //        toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
         	return array();
        } catch (PDOException $e_pdo) {
			toba::logger()->error('Error '.$e_pdo->getMessage());
			return array();
		}
	}
	
	static public function confirmar_recibo_banco($id_recibo_banco) {
        if (isset($id_recibo_banco)) {
            $mensaje_error = 'Error en la confirmación del recibo de banco.';
            try {
                toba::db()->abrir_transaccion();
                $sql = "BEGIN :resultado := PKG_KR_TRANS_TESORERIA.CONFIRMAR_RECIBO_BANCO(:ID_RECIBO_BANCO); END;";

                $parametros = array(array('nombre' => 'ID_RECIBO_BANCO',
                        'tipo_dato' => PDO::PARAM_INT,
                        'longitud' => 32,
                        'valor' => $id_recibo_banco),
                    array('nombre' => 'resultado',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 4000,
                        'valor' => ''),
                );

                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                $valor_resultado = $resultado[count($resultado) - 1]['valor'];
                if ($valor_resultado != 'OK') {
                    toba::notificacion()->error($valor_resultado);
                    toba::logger()->error($valor_resultado);
                    toba::db()->abortar_transaccion();
                } else {
					toba::notificacion()->info('El recibo de banco se confirmó exitosamente.');
					toba::db()->cerrar_transaccion();
				}
            } catch (toba_error_db $e_db) {
                toba::notificacion()->error($mensaje_error . ' ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
                toba::logger()->error($mensaje_error . ' ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
                toba::db()->abortar_transaccion();
            } catch (toba_error $e) {
                toba::notificacion()->error($mensaje_error . ' ' . $e->get_mensaje());
                toba::logger()->error($mensaje_error . ' ' . $e->get_mensaje());
                toba::db()->abortar_transaccion();
            }
        }
    }

    static public function anular_recibo_banco($id_recibo_banco, $fecha_anulacion) {
        if (isset($id_recibo_banco) && isset($fecha_anulacion)) {
            $sql = "BEGIN :resultado := PKG_KR_TRANS_TESORERIA.ANULAR_RECIBO_BANCO(:id_recibo_banco, null, to_date(substr(:fecha_anulacion,1,10),'yyyy-mm-dd')); END;";
			
            $parametros = array(array('nombre' => 'id_recibo_banco',
                    'tipo_dato' => PDO::PARAM_INT,
                    'longitud' => 32,
                    'valor' => $id_recibo_banco),
                array('nombre' => 'fecha_anulacion',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $fecha_anulacion),
                array('nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''),
            );
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            return $resultado[2]['valor'];
        }
    }
	
	static public function get_recibo_banco_x_id($id_recibo_banco) {
        if (isset($id_recibo_banco)) {
            $sql = "SELECT	rb.*, 
							rb.id_recibo_banco||' - '||rb.nro_comprobante ||' - '||to_char(rb.fecha_comprobante,'dd/mm/rr')||' - '||rb.IMPORTE as lov_descripcion,
							rb.fecha_comprobante,
							rb.confirmado aprobado,
							rb.fecha_confirma fecha_aprueba,
							rb.usuario_confirma usuario_aprueba,
							to_char(rb.fecha_comprobante, 'dd/mm/yyyy') fecha_comprobante_format
                   FROM AD_RECIBOS_BANCO rb
				   WHERE rb.id_recibo_banco = $id_recibo_banco
                   ORDER BY lov_descripcion;";
            return toba::db()->consultar_fila($sql);
        } else {
            return array();
        }
    }
	
	static public function get_datos_tipo_comprobante_banco($cod_unidad_administracion, $cod_tipo_comprobante, $cod_moneda, $cod_unidad_ejecutora = null)
	{
		if (isset($cod_unidad_administracion) && isset($cod_tipo_comprobante) && isset($cod_moneda)) {
			$datos = dao_recibos_banco::get_condicion_tipo_comprobante($cod_tipo_comprobante);
			$datos = array_merge($datos, dao_recibos_banco::get_condicion_matriz($datos['id_matriz_comp_pago']));
			if (!empty($datos)) {
				if (isset($datos['cuenta_origen']) && $datos['cuenta_origen'] == 'N') {
					$datos_cuenta_banco_pred_origen = self::get_datos_cuenta_banco_predeterminada($cod_unidad_administracion, $datos['tipo_cuenta_pred_o'], $cod_moneda, $cod_unidad_ejecutora);
				} else {
					$datos_cuenta_banco_pred_origen = array();
				}
				if (isset($datos['cuenta_destino']) && $datos['cuenta_destino'] == 'N') {
					$datos_cuenta_banco_pred_destino = self::get_datos_cuenta_banco_predeterminada($cod_unidad_administracion, $datos['tipo_cuenta_pred_d'], $cod_moneda, $cod_unidad_ejecutora);
				} else {
					$datos_cuenta_banco_pred_destino = array();
				}
			} else {
				$datos_cuenta_banco_pred_origen = array();
				$datos_cuenta_banco_pred_destino = array();
			}
			
			if (isset($datos_cuenta_banco_pred_origen) && !empty($datos_cuenta_banco_pred_origen)) {
				$datos['id_cuenta_banco'] = $datos_cuenta_banco_pred_origen['id_cuenta_banco'];
				$datos['tipo_cuenta_banco_origen'] = $datos_cuenta_banco_pred_origen['tipo_cuenta_banco'];
			} else {
				$datos['id_cuenta_banco'] = null;
				$datos['tipo_cuenta_banco_origen'] = null;
			}
			if (isset($datos_cuenta_banco_pred_destino) && !empty($datos_cuenta_banco_pred_destino)) {
				$datos['id_cuenta_banco_hasta'] = $datos_cuenta_banco_pred_destino['id_cuenta_banco'];
				$datos['tipo_cuenta_banco_destino'] = $datos_cuenta_banco_pred_destino['tipo_cuenta_banco'];
			} else {
				$datos['id_cuenta_banco_hasta'] = null;
				$datos['tipo_cuenta_banco_destino'] = null;
			}
			return $datos;
		} else {
			return array();
		}
	}
	
	public static function get_movimiento_banco_x_id($id_movimiento_banco){
        if (isset($id_movimiento_banco)) {
            $sql = "SELECT	KRMOBA.NRO_MOVIMIENTO || ' (ID: ' || KRMOBA.ID_MOVIMIENTO_BANCO || ') ' || to_char(KRMOBA.FECHA_MOVIMIENTO,'dd/mm/rr') || ' (' || SUBSTR(KRMOBA.DETALLE,1,400) || ')    Debe: ' || trim(to_char(KRMOBA.DEBE, '$999,999,999,990.00')) || '    Importe: ' || trim(to_char(KRMOBA.HABER, '$999,999,999,990.00')) lov_descripcion
				FROM KR_MOVIMIENTOS_BANCO KRMOBA
				WHERE KRMOBA.id_movimiento_banco = ".quote($id_movimiento_banco) .";";
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
	
	public static function get_lov_movimientos_banco_x_nombre($nombre, $filtro=array())
	{
		if (isset($nombre)) {
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('id_movimiento_banco', $nombre);			
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_movimiento', $nombre);
			$where = "($trans_codigo OR $trans_nro)";
        } else {
            $where = '1=1';
        }
		
		if (isset($filtro['fecha_comprobante'])) {
			$where .= " AND KRMOBA.FECHA_MOVIMIENTO <= " . quote($filtro['fecha_comprobante']);
			unset($filtro['fecha_comprobante']);
		}
		
		if (isset($filtro['debe_positivo'])) {
			$where .= " AND KRMOBA.DEBE > 0 ";
			unset($filtro['debe_positivo']);
		}
		
		if (isset($filtro['sin_id_comprobante_pago'])) {
			$where .= " AND KRMOBA.ID_COMPROBANTE_PAGO IS NULL ";
			unset($filtro['sin_id_comprobante_pago']);
		}
		
		if (isset($filtro['sin_fecha_aplicacion_total'])) {
			$where .= " AND KRMOBA.FECHA_APLICACION_TOTAL IS NULL ";
			unset($filtro['sin_fecha_aplicacion_total']);
		}
		
		$where .= ' AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'KRMOBA', '1=1');

        $sql = "SELECT	KRMOBA.ID_MOVIMIENTO_BANCO,
						KRMOBA.NRO_MOVIMIENTO NRO_MOVIMIENTO,
						KRMOBA.FECHA_MOVIMIENTO FECHA_MOVIMIENTO,
						SUBSTR(KRMOBA.DETALLE,1,400) UI_DETALLE,
						KRMOBA.DEBE DEBE,
						KRMOBA.HABER IMPORTE,
						KRMOBA.NRO_MOVIMIENTO || ' (ID: ' || KRMOBA.ID_MOVIMIENTO_BANCO || ') ' || to_char(KRMOBA.FECHA_MOVIMIENTO,'dd/mm/rr') || ' (' || SUBSTR(KRMOBA.DETALLE,1,400) || ')    Debe: ' || trim(to_char(KRMOBA.DEBE, '$999,999,999,990.00')) || '    Importe: ' || trim(to_char(KRMOBA.HABER, '$999,999,999,990.00')) lov_descripcion
				FROM KR_MOVIMIENTOS_BANCO KRMOBA
				WHERE $where
                ORDER BY lov_descripcion ASC;";		
		
        $datos = toba::db()->consultar($sql);

        return $datos;
	}
	
	public static function get_datos_movimiento_banco_x_id($id_movimiento_banco){
        if (isset($id_movimiento_banco)) {
            $sql = "SELECT	KRMOBA.*,
							KRMOBA.haber importe,
							KRMOBA.NRO_MOVIMIENTO || ' (ID: ' || KRMOBA.ID_MOVIMIENTO_BANCO || ') ' || to_char(KRMOBA.FECHA_MOVIMIENTO,'dd/mm/rr') || ' (' || SUBSTR(KRMOBA.DETALLE,1,400) || ')    Debe: ' || trim(to_char(KRMOBA.DEBE, '$999,999,999,990.00')) || '    Importe: ' || trim(to_char(KRMOBA.HABER, '$999,999,999,990.00')) lov_descripcion
				FROM KR_MOVIMIENTOS_BANCO KRMOBA
				WHERE KRMOBA.id_movimiento_banco = ".quote($id_movimiento_banco) .";";
            return toba::db()->consultar_fila($sql);
        } else {
            return array();
        }
    }
	
	public static function set_importes_encabezado_recibo_banco($id_recibo_banco, $importe_nominal, $cotizaccion, $importe) 
	{
        if (isset($id_recibo_banco) && isset($importe_nominal) && isset($cotizaccion) && isset($importe)) {
            $sql_upd = "UPDATE ad_recibos_banco
						SET importe = " . quote($importe) . ",
							importe_nominal = " . quote($importe_nominal) . ",
							cotizacion = " . quote($cotizaccion) . "
						WHERE id_recibo_banco = " . quote($id_recibo_banco) . ";";
            toba::db()->ejecutar($sql_upd);
        }
    }
	
}

?>
