<?php

class dao_ordenes_pago {

    static public function get_ordenes_pago($filtro = array(),$orden = array()) {
		$desde= null;
		$hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}

		$where = self::armar_where($filtro);

        $sql = "SELECT adop.id_orden_pago,
        adop.cod_unidad_administracion||'-'||krua.DESCRIPCION cod_unidad_administracion,
        adop.nro_orden_pago,
        adop.cod_tipo_orden_pago ||'-'||atop.descripcion cod_tipo_orden_pago,
        adop.id_cuenta_corriente,
        adop.id_comprobante_caja_chica,
        adop.id_comprobante_anticipo,
        adop.cod_auxiliar,
        to_char(adop.fecha_orden_pago, 'dd/mm/yyyy') fecha_orden_pago,
        trim(to_char(adop.importe, '$999,999,999,990.00'))  importe,
        trim(to_char(adop.importe_retenciones, '$999,999,999,990.00')) importe_retenciones,
        adop.usuario_carga,
        to_char(adop.fecha_carga, 'dd/mm/yyyy') fecha_carga,
        adop.aprobada,
        adop.usuario_aprueba,
        to_char(adop.fecha_aprueba, 'dd/mm/yyyy') fecha_aprueba,
        adop.anulada,
        to_char(adop.fecha_anulacion, 'dd/mm/yyyy') fecha_anulacion,
        adop.usuario_anula,
        to_char(adop.fecha_anulacion, 'dd/mm/yyyy') fecha_anula,
        adop.observaciones,
        trim(to_char(adop.importe_pagos, '$999,999,999,990.00')) importe_pagos,
        adop.id_expediente,
        adop.clase_orden_pago,
        adop.a_compensar,
        adop.usuario_compensacion,
        to_char(adop.fecha_compensacion, 'dd/mm/yyyy') fecha_compensacion,
        adop.id_expediente_pago,
        adop.cod_unidad_ejecutora,
        decode(adop.aprobada,'S','Si','No') aprobada_format,
        decode(adop.anulada,'S','Si','No') anulada_format
   FROM ad_ordenes_pago adop, kr_unidades_administracion krua, ad_tipos_orden_pago atop
   WHERE adop.cod_unidad_administracion = krua.cod_unidad_administracion
   AND   adop.cod_tipo_orden_pago = atop.cod_tipo_orden_pago
   AND  $where
   order by adop.id_orden_pago desc";

		$sql= dao_varios::paginador($sql, null, $desde, $hasta, null, $orden);
        $datos = toba::db()->consultar($sql);

        /* foreach ($datos as $clave => $dato) {
          $datos[$clave]['des_clase_comprobante'] = self::get_descripcion_clase_comprobante($datos[$clave]['clase_comprobante']);
          } */
        return $datos;
    }

    static public function armar_where ($filtro = array()){
        $where = "1=1";

    	if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}

        if (isset($filtro['cod_unidad_ejecutora']) && $filtro['cod_unidad_ejecutora'] == '0') {
            $filtro['cod_unidad_ejecutora'] = NULL;
        }

		if (isset($filtro['ids_comprobantes'])) {
            $where .= "AND adop.id_orden_pago IN (" . $filtro['ids_comprobantes'] . ") ";
            unset($filtro['ids_comprobantes']);
        }

        if (isset($filtro['id_beneficiario'])) {
            $where .= "AND exists (select 1 from ad_ordenes_pago_ben opb where adop.id_orden_pago = opb.id_orden_pago AND opb.id_beneficiario =" . $filtro['id_beneficiario'] .")";
            unset($filtro['id_beneficiario']);
        }

        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ADOP', '1=1');

        $sql_auxiliar_ua = "select (PKG_KR_USUARIOS.in_ua_tiene_acceso(upper('" . toba::usuario()->get_id() . "'))) unidades_ua from dual";
        $conjunto_unidades_ua = toba::db()->consultar_fila($sql_auxiliar_ua);

        $sql_auxiliar_ue = "select (PKG_KR_USUARIOS.in_ue_tiene_acceso(upper('" . toba::usuario()->get_id() . "'))) unidades_ue from dual";
        $conjunto_unidades_ue = toba::db()->consultar_fila($sql_auxiliar_ue);

        $where .= " AND adop.COD_UNIDAD_ADMINISTRACION in " . $conjunto_unidades_ua['unidades_ua'] . "
				    AND (adop.cod_unidad_ejecutora in " . $conjunto_unidades_ue['unidades_ue'] . " OR pkg_kr_usuarios.usuario_tiene_ues(upper('" . toba::usuario()->get_id() . "'))='N')";
        return $where;
    }

    static public function get_cantidad_ordenes ($filtro = array()){
    	$where = self::armar_where($filtro);
    	$sql = "
            SELECT count(adop.id_orden_pago) cantidad
    	    FROM ad_ordenes_pago adop, kr_unidades_administracion krua, ad_tipos_orden_pago atop, ad_ordenes_pago_ben opb
			WHERE
                adop.cod_unidad_administracion = krua.cod_unidad_administracion
			    AND adop.cod_tipo_orden_pago = atop.cod_tipo_orden_pago
                AND adop.id_orden_pago = opb.id_orden_pago
                AND $where ";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['cantidad'];
    }

    static public function get_id_recibo_generado ($id_orden_pago){
    	$sql = "SELECT rpop.id_recibo_pago
                  FROM ad_recibos_pago_op rpop, ad_recibos_pago arp
                 WHERE rpop.id_recibo_pago = arp.id_recibo_pago
                   AND rpop.id_orden_pago = $id_orden_pago
                   AND rpop.id_recibo_pago = arp.id_recibo_pago
                   AND arp.anulado = 'N'
                   AND arp.aprobado = 'S' ";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['id_recibo_pago'];
    }

    static public function get_ordenes_pago_x_id($id_orden_pago) {
        if (isset($id_orden_pago)) {
            $sql = "SELECT c.*
                        FROM AD_ORDENES_PAGO c
                        WHERE c.id_orden_pago= " . quote($id_orden_pago);

            $datos = toba::db()->consultar_fila($sql);

            return $datos;
        } else {
            return array();
        }
    }

    static public function get_datos_extras_ordenes_pago_x_id($id_orden_pago) {
        if (isset($id_orden_pago)) {
            $sql = "SELECT	p.anulada,
							p.aprobada,
							saldo_orden_pago(p.ID_ORDEN_PAGO) as importe_saldo
                        FROM AD_ORDENES_PAGO p
                        WHERE p.id_orden_pago= " . quote($id_orden_pago);

            $datos = toba::db()->consultar_fila($sql);

            return $datos;
        } else {
            return array();
        }
    }

    static public function get_id_nro_orden_pago_x_id($id_orden_pago) {
        if (isset($id_orden_pago)) {
            $sql = "SELECT	ADORPA.*,
							ADORPA.id_orden_pago||' (Nro. '||ADORPA.nro_orden_pago ||') - '||to_char(ADORPA.fecha_orden_pago,'dd/mm/rr')||' ('||trim(to_char(saldo_orden_pago(ADORPA.ID_ORDEN_PAGO), '$999,999,999,990.00')) ||')' as lov_descripcion
                   FROM AD_ORDENES_PAGO ADORPA
                   WHERE ADORPA.id_orden_pago = " . quote($id_orden_pago) . "
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

    static public function get_lov_ordenes_pago_x_nombre($nombre, $filtro = array()) {
        if (isset($nombre)) {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('ADORPA.id_orden_pago', $nombre);
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('ADORPA.nro_orden_pago', $nombre);
            $where = "($trans_codigo OR $trans_nro)";
        } else {
            $where = '1=1';
        }

        if (isset($filtro['fecha_recibo_pago'])) {
            $where .= " AND ADORPA.fecha_orden_pago <= " . quote($filtro['fecha_recibo_pago']) . " ";
            if (isset($filtro['ejercicio_anterior']) && isset($filtro['presupuestario'])) {
                $where .= " AND ((ADTIORPA.ejercicio_anterior = 'N' AND " . quote($filtro['ejercicio_anterior']) . " = 'N' AND ADTIORPA.presupuestario = " . quote($filtro['presupuestario']) . ")
							 OR (((ADTIORPA.ejercicio_anterior = 'S' AND pkg_kr_ejercicios.retornar_nro_ejercicio(" . quote($filtro['fecha_recibo_pago']) . ") >= pkg_kr_ejercicios.retornar_nro_ejercicio(ADORPA.fecha_orden_pago))
								OR (ADTIORPA.ejercicio_anterior = 'N' AND pkg_kr_ejercicios.retornar_nro_ejercicio(" . quote($filtro['fecha_recibo_pago']) . ") > pkg_kr_ejercicios.retornar_nro_ejercicio(ADORPA.fecha_orden_pago)))
								AND(not exists (select 1
												from AD_ORDENES_PAGO_CG opcg
												where opcg.id_orden_pago = ADORPA.id_orden_pago
												and exists (select *
															from AD_COMPROBANTES_GASTO cg
															where cg.id_comprobante_gasto_rei = opcg.id_comprobante_gasto
															and cg.aprobado='S'
															and cg.anulado='N')
												)
									)
								)
							) ";
                unset($filtro['ejercicio_anterior']);
                unset($filtro['presupuestario']);
            }
            unset($filtro['fecha_recibo_pago']);
        }

        if (isset($filtro['excluir_recibo_pago']) && isset($filtro['id_recibo_pago'])) {
            $where .= " AND NOT EXISTS (SELECT 1
										FROM AD_RECIBOS_PAGO_OP adrepaop
										WHERE adrepaop.id_orden_pago = ADORPA.id_orden_pago
										AND adrepaop.id_recibo_pago = " . quote($filtro['id_recibo_pago']) . ") ";
            unset($filtro['id_recibo_pago']);
            unset($filtro['excluir_recibo_pago']);
        }

        if (isset($filtro['con_saldo'])) {
            $where .= " AND saldo_orden_pago(ADORPA.id_orden_pago) > 0 ";
            unset($filtro['con_saldo']);
        }

		if (isset($filtro['ids_comprobantes'])) {
            $where .= "AND adorpa.id_orden_pago IN (" . $filtro['ids_comprobantes'] . ") ";
            unset($filtro['ids_comprobantes']);
        }

        if (isset($filtro['id_expediente'])){
            if (empty($filtro['id_expediente']) || is_null($filtro['id_expediente']))
                $filtro['id_expediente'] = 'NULL';

            $where .= " And (ADORPA.id_expediente = ".$filtro['id_expediente']." OR ADORPA.id_expediente IS NULL OR ".$filtro['id_expediente']." IS NULL)";

            unset($filtro['id_expediente']);
        }

        if (isset($filtro['id_expediente_pago'])){
            if (empty($filtro['id_expediente_pago']) || is_null($filtro['id_expediente_pago']))
                $filtro['id_expediente_pago'] = 'NULL';

            $where .= " And (ADORPA.id_expediente_pago = ".$filtro['id_expediente_pago']." OR ADORPA.id_expediente_pago IS NULL OR ".$filtro['id_expediente_pago']." IS NULL)";

            unset($filtro['id_expediente_pago']);
        }


        if (array_key_exists('tipo_aplicacion', $filtro))
        {
            $where .=" AND (   pkg_general.valor_parametro_kr ('CONTROLAR_TIPO_APL_RECIBO') = 'N'
                          OR (    pkg_general.valor_parametro_kr ('CONTROLAR_TIPO_APL_RECIBO') = 'S'
                              AND ( ";
            if (is_null($filtro['tipo_aplicacion'])){
                $where .=" adtiorpa.origen_orden_pago IN ('ANT', 'REP', 'OTR')";
            }else{
                $where .=" '".$filtro['tipo_aplicacion']."' = 'CGA' AND adtiorpa.origen_orden_pago = 'CGA'
                        OR '".$filtro['tipo_aplicacion']."' = 'RCO' AND adtiorpa.origen_orden_pago = 'RCO'
                        OR '".$filtro['tipo_aplicacion']."' = 'CRE' AND adtiorpa.origen_orden_pago = 'CRE'";
            }
            $where .=" )))";
            unset($filtro['tipo_aplicacion']);
        }

        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'ADORPA', '1=1');

        $sql = "SELECT  ADORPA.*,
						ADORPA.id_orden_pago||' (Nro. '||ADORPA.nro_orden_pago ||') - '||to_char(ADORPA.fecha_orden_pago,'dd/mm/rr')||' ('||trim(to_char(saldo_orden_pago(ADORPA.ID_ORDEN_PAGO), '$999,999,999,990.00')) ||')' as lov_descripcion
				FROM AD_ORDENES_PAGO ADORPA
				JOIN AD_TIPOS_ORDEN_PAGO ADTIORPA ON (ADORPA.COD_TIPO_ORDEN_PAGO = ADTIORPA.COD_TIPO_ORDEN_PAGO)
                WHERE $where
				ORDER BY lov_descripcion ASC;";
        $datos = toba::db()->consultar($sql);

        return $datos;
    }

    public static function get_cc_tiene_beneficiarios($id_cuenta_corriente) {

        if ((isset($id_cuenta_corriente)) && (!empty($id_cuenta_corriente))) {

            $sql = "select count(1) cant
                       from ad_beneficiarios_pago
                       where id_cuenta_corriente = " . $id_cuenta_corriente . ";";

            $res = toba::db()->consultar($sql);


            return array("cc_tiene_beneficiario" => $res[0]["cant"]);
        }
    }

    static public function get_retenciones_orden_pago($filtro = array()) {
        $where = " AND 1=1";

        if (isset($filtro['id_recibo_pago'])) {
            $where .= "AND (ADREOR.ID_ORDEN_PAGO IN ( SELECT ID_ORDEN_PAGO FROM AD_RECIBOS_PAGO_OP WHERE ID_RECIBO_PAGO = " . quote($filtro['id_recibo_pago']) . "))";
            unset($filtro['id_recibo_pago']);
        }

        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ADREOR', '1=1');

        $sql = "SELECT	ADREOR.*,
						ADREOR.ID_RETENCION_ORDEN || ' - (' || L_ADRT.DESCRIPCION || ')' lov_descripcion,
						L_KRCTCT.nro_cuenta_corriente || ' - ' || L_KRCTCT.descripcion cuenta_corriente
				FROM	AD_RETENCIONES_ORDEN ADREOR,
						AD_RETENCIONES L_ADRT,
						KR_CUENTAS_CORRIENTE L_KRCTCT
				WHERE ADREOR.ID_RETENCION = L_ADRT.ID_RETENCION
				AND ADREOR.ID_CUENTA_CORRIENTE = L_KRCTCT.ID_CUENTA_CORRIENTE
				$where
				ORDER BY lov_descripcion";

        $datos = toba::db()->consultar($sql);

        return $datos;
    }

    public static function cargar_beneficiarios($id_orden_pago) {
        try {
            toba::db()->abrir_transaccion();
            $sql = "BEGIN :resultado := Pkg_ordenes_pago.cargar_beneficiarios(:id_orden_pago); END;";
            $parametros = array(array('nombre' => 'id_orden_pago',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $id_orden_pago),
                array('nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''),
            );
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

            if ($resultado[1]['valor'] != 'OK') {
                toba::db()->abortar_transaccion();
            } else {
                toba::db()->cerrar_transaccion();
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

    public static function cargar_recibos_cobro($id_orden_pago, $fecha_desde, $fecha_hasta) {
        try {
            toba::db()->abrir_transaccion();
            $sql = "BEGIN :resultado := Pkg_ordenes_pago.cargar_recibos_cobro(:id_orden_pago,to_date(:fecha_desde,'YYYY-MM-DD'),to_date(:fecha_hasta,'YYYY-MM-DD')); END;";
            $parametros = array(array('nombre' => 'id_orden_pago',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $id_orden_pago),
                array('nombre' => 'fecha_desde',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $fecha_desde),
               array('nombre' => 'fecha_hasta',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $fecha_hasta),
                array('nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''),
            );
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

            if ($resultado[3]['valor'] != 'OK') {
                toba::db()->abortar_transaccion();
            } else {
                toba::db()->cerrar_transaccion();
            }

            return $resultado[3]['valor'];
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

    public static function anular_orden_pago($id_orden_pago, $fecha, $con_transaccion=true) {
        try {
			if ($con_transaccion) {
				toba::db()->abrir_transaccion();
			}
            $sql = "BEGIN :resultado := pkg_kr_trans_tesoreria.anular_orden_pago(:id_orden_pago,to_date(:fecha,'YYYY-MM-DD')); END;";
            $parametros = array(array('nombre' => 'id_orden_pago',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $id_orden_pago),
                array('nombre' => 'fecha',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $fecha),
                array('nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''),
            );
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            if ($resultado[2]['valor'] != 'OK') {
				if ($con_transaccion) {
					toba::db()->abortar_transaccion();
				}
            } else {
				if ($con_transaccion) {
					toba::db()->cerrar_transaccion();
				}
            }

            //return $resultado[1]['valor'];

            return $resultado[2]['valor'];
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            if ($con_transaccion) {
				toba::db()->abortar_transaccion();
			} else {
				throw $e_db;
			}
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
            if ($con_transaccion) {
				toba::db()->abortar_transaccion();
			} else {
				throw $e;
			}
        }
    }

    public static function cargar_retenciones($id_orden_pago, $id_cuenta_corriente, $fecha_orden_pago) {
        try {
            toba::db()->abrir_transaccion();
            $sql = "BEGIN :resultado := pkg_ad_comprobantes_pagos.calc_reten_ord_pago(:id_orden_pago,:id_cuenta_corriente,to_date(substr(:fecha_orden_pago,1,10),'yyyy-mm-dd')); END;";
            $parametros = array(array('nombre' => 'id_orden_pago',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $id_orden_pago),
                array('nombre' => 'id_cuenta_corriente',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $id_cuenta_corriente),
                array('nombre' => 'fecha_orden_pago',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $fecha_orden_pago),
                array('nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''),
            );
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

            if ($resultado[3]['valor'] != 'OK') {
                toba::db()->abortar_transaccion();
            } else {
                toba::db()->cerrar_transaccion();
            }

            return $resultado[3]['valor'];
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

    public static function cambiar_estado_compensado($id_orden_pago) {
        try {
            toba::db()->abrir_transaccion();
            $sql = "BEGIN :resultado := pkg_ordenes_pago.cambiar_estado_compensacion(:id_orden_pago); END;";
            $parametros = array(array('nombre' => 'id_orden_pago',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $id_orden_pago),
                array('nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''),
            );
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

            if ($resultado[1]['valor'] != 'OK') {
                toba::db()->abortar_transaccion();
            } else {
                toba::db()->cerrar_transaccion();
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

    public static function recursos_especificos($id_orden_pago)
    {
        $sql = "BEGIN
                    :resultado := pkg_ordenes_pago.controlar_recursos_especificos(:id_orden_pago);
                END;";
            $parametros =
                [
                    ['nombre' => 'resultado',
                     'tipo_dato' => PDO::PARAM_STR,
                     'longitud' => 4000,
                     'valor' => ''
                    ],
                    ['nombre' => 'id_orden_pago',
                     'tipo_dato' => PDO::PARAM_STR,
                     'longitud' => 32,
                     'valor' => $id_orden_pago],
                ];
        ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
    }

    public static function confirmar_orden_pago($id_orden_pago)
    {
        $sql = "BEGIN
                    :resultado := Pkg_Kr_Trans_Tesoreria.confirmar_orden_pago(:id_orden_pago);
                END;";
            $parametros =
                [
                    ['nombre' => 'resultado',
                     'tipo_dato' => PDO::PARAM_STR,
                     'longitud' => 4000,
                     'valor' => ''
                    ],
                    ['nombre' => 'id_orden_pago',
                     'tipo_dato' => PDO::PARAM_STR,
                     'longitud' => 32,
                     'valor' => $id_orden_pago],
                ];
        ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
    }

    public static function trans_permite_auto_generar_rp($id_orden_pago) {
        try {
            $sql = "BEGIN :resultado := pkg_kr_trans_auto.trans_permite_auto_generar_rp(:id_orden_pago); END;";
            $parametros = array(array('nombre' => 'id_orden_pago',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $id_orden_pago),
                array('nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''),
            );
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

            return $resultado[1]['valor'];
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
        }
    }

    public static function genera_recibo_pago($id_orden_pago, $fecha_recibo, $es_efectivo, $calc_retencion) {
        try {
            $rp = '';
            toba::db()->abrir_transaccion();
            $sql = "BEGIN :resultado := pkg_kr_trans_auto.trans_auto_generar_recibo_pago(:id_orden_pago,to_date(substr(:fecha_recibo,1,10),'yyyy-mm-dd'),:es_efectivo,:calc_retencion, :rp); END;";
            $parametros = array(
                array('nombre' => 'id_orden_pago',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $id_orden_pago),
                array('nombre' => 'fecha_recibo',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $fecha_recibo),
                array('nombre' => 'es_efectivo',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $es_efectivo),
                array('nombre' => 'calc_retencion',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $calc_retencion),
                array('nombre' => 'rp',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $rp),
                array('nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''),
            );

            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

            if ($resultado[5]['valor'] != 'OK') {
                toba::db()->abortar_transaccion();
            } else {
                toba::db()->cerrar_transaccion();
            }

            return $resultado;
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

    public static function get_importe_ordenado($id_comprobante_gasto) {
        if (isset($id_comprobante_gasto)) {
            $sql = "SELECT pkg_kr_transacciones.saldo_ordenado(" . quote($id_comprobante_gasto) . ") as importe
                               FROM dual;";
            $datos = toba::db()->consultar_fila($sql);

            return $datos["importe"];
        } else {
            return 0;
        }
    }

    static public function get_saldo_orden_pago ($id_orden_pago){
    	$sql = "SELECT id_orden_pago, saldo_orden_pago(ADORPA.ID_ORDEN_PAGO) as importe
				FROM AD_ORDENES_PAGO ADORPA
				WHERE ID_ORDEN_PAGO = $id_orden_pago";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['importe'];
    }

    public static function get_importe_orden_pago($id_orden_pago) {
        if (isset($id_orden_pago)) {
            $sql = "select nvl(sum(importe),0) importe
                     from AD_ORDENES_PAGO
                     where id_orden_pago = " . quote($id_orden_pago) . ";";
            $datos = toba::db()->consultar_fila($sql);

            return $datos["importe"];
        } else {
            return 0;
        }
    }

    public static function get_importe_retenciones_pago($id_orden_pago) {
        if (isset($id_orden_pago)) {
            $sql = "select nvl(sum(importe_retenciones),0) importe_retenciones
                     from AD_ORDENES_PAGO
                     where id_orden_pago = " . quote($id_orden_pago) . ";";
            $datos = toba::db()->consultar_fila($sql);

            return $datos["importe_retenciones"];
        } else {
            return 0;
        }
    }

    public static function get_importe_comprobante_recurso($id_comprobante_recurso) {
        if (isset($id_comprobante_recurso)) {
            $sql = "select nvl(sum(importe),0) importe
                     from AD_COMPROBANTES_RECURSO
                     where id_comprobante_recurso = " . quote($id_comprobante_recurso) . ";";
            $datos = toba::db()->consultar_fila($sql);

            return $datos["importe"];
        } else {
            return 0;
        }
    }

    public static function get_importe_pagos($id_orden_pago) {
        if (isset($id_orden_pago)) {
            $sql = "select nvl(sum(importe_pagos),0) importe_pagos
                     from AD_ORDENES_PAGO
                     where id_orden_pago = " . quote($id_orden_pago) . ";";
            $datos = toba::db()->consultar_fila($sql);

            return $datos["importe_pagos"];
        } else {
            return 0;
        }
    }

    public static function get_importe_facturas($id_factura) {
        if (isset($id_factura)) {
            $sql = "SELECT pkg_ad_facturas.saldo_a_ordenar(" . quote($id_factura) . ") importe_neto
                        FROM dual;";
            $datos = toba::db()->consultar_fila($sql);

            return $datos["importe_neto"];
        } else {
            return 0;
        }
    }

    public static function get_importe_netos($id_orden_pago) {
        if (isset($id_orden_pago)) {
            $sql = "select NVL(importe, 0) - NVL(importe_retenciones, 0) - NVL(importe_pagos, 0) neto
                     from AD_ORDENES_PAGO
                     where id_orden_pago = " . quote($id_orden_pago) . ";";
            $datos = toba::db()->consultar_fila($sql);

            return $datos["neto"];
        } else {
            return 0;
        }
    }

    public static function get_importe_caja_chica($id_comprobante_caja_chica) {
        if (isset($id_comprobante_caja_chica)) {
            $sql = "SELECT NVL(importe, 0) importe
                    FROM ad_comprobantes_caja_chica
                   WHERE id_comprobante_caja_chica = " . quote($id_comprobante_caja_chica) . ";";
            $datos = toba::db()->consultar_fila($sql);
            return $datos["importe"];
        } else {
            return 0;
        }
    }

    public static function get_importe_anticipo($id_comprobante_anticipo) {
        if (isset($id_comprobante_anticipo)) {
            $sql = "SELECT NVL(importe, 0) importe
                    FROM ad_comprobantes_anticipo
                   WHERE id_comprobante_anticipo = " . quote($id_comprobante_anticipo) . ";";
            $datos = toba::db()->consultar_fila($sql);
            return $datos["importe"];
        } else {
            return 0;
        }
    }

    public static function get_importe_beneficiarios($id_orden_pago) {
        if (isset($id_orden_pago)) {
            $sql = "select NVL(importe, 0) - NVL(importe_retenciones, 0) importe
                     from AD_ORDENES_PAGO
                     where id_orden_pago = " . quote($id_orden_pago) . ";";
            $datos = toba::db()->consultar_fila($sql);
            return $datos["importe"];
        } else {
            return 0;
        }
    }

    public static function get_importe_aplicaciones_pago($id_recibo_pago) {
        if (isset($id_recibo_pago)) {
            $sql = "SELECT pkg_kr_transacciones.saldo_transaccion
                                             (rp.id_transaccion,
                                              rp.id_cuenta_corriente,
                                              sysdate
                                             ) importe
                      FROM ad_recibos_pago rp
                     WHERE rp.id_recibo_pago =" . quote($id_recibo_pago) . ";";
            $datos = toba::db()->consultar_fila($sql);
            return $datos["importe"];
        } else {
            return 0;
        }
    }

    public static function get_importe_recibos_cobro($id_recibo_cobro) {
        if (isset($id_recibo_cobro)) {
            $sql = "SELECT pkg_kr_transacciones.saldo_transaccion
                                             (rc.id_transaccion,
                                              rc.id_cuenta_corriente,
                                              sysdate
                                             ) importe
                      FROM ad_recibos_cobro rc
                     WHERE rc.id_recibo_cobro =" . quote($id_recibo_cobro) . ";";
            $datos = toba::db()->consultar_fila($sql);
            return $datos["importe"];
        } else {
            return 0;
        }
    }

    static public function get_cancelaciones($id_orden_pago) {

        $sql = "SELECT   arp.*,
                                arp.id_recibo_pago
                             || ' - '
                             || arp.nro_recibo
                             || ' - '
                             || TO_CHAR (arp.fecha_recibo, 'dd/mm/rr')
                             || ' - '
                             || arp.importe AS lov_descripcion
                        FROM ad_recibos_pago_op rpop, ad_recibos_pago arp
                       WHERE rpop.id_recibo_pago = arp.id_recibo_pago
                        AND arp.anulado = 'N'
                        AND arp.aprobado = 'S'
                        AND rpop.id_orden_pago = " . quote($id_orden_pago) . "
                    ORDER BY lov_descripcion;";
        $datos = toba::db()->consultar($sql);

        return $datos;
    }

    static public function get_tipo_cta_cte($id_retencion) {
        if (isset($id_retencion)) {
            $sql = "SELECT atr.tipo_cuenta_corriente
                        FROM ad_retenciones a, ad_tipos_retencion atr
                       WHERE a.cod_tipo_retencion = atr.cod_tipo_retencion AND a.id_retencion = " . quote($id_retencion) . ";";
            $datos = toba::db()->consultar_fila($sql);
        }
        if (isset($datos) && !empty($datos) && isset($datos['tipo_cuenta_corriente'])) {
            return $datos['tipo_cuenta_corriente'];
        }
    }

    static public function tiene_recibos_creados($id_orden_pago) {
        if (isset($id_orden_pago)) {
            $sql = "SELECT count(1) valor
                        FROM ad_recibos_pago_op arpo, ad_recibos_pago arp
                       WHERE arpo.id_orden_pago = " . quote($id_orden_pago) . "
                       AND arpo.ID_RECIBO_PAGO = arp.ID_RECIBO_PAGO and arp.ANULADO ='N' and arp.APROBADO = 'S'";
            $datos = toba::db()->consultar_fila($sql);
        }
        if (isset($datos) && !empty($datos) && isset($datos['valor'])) {
            return $datos['valor'];
        }
    }

    static public function get_lov_tipos_orden_pago($codigo) {
        if (isset($codigo)) {
            $sql = "SELECT ADTOP.*, ADTOP.cod_tipo_orden_pago || ' - ' || ADTOP.descripcion || ' - (Origen:' || ADTOP.origen_orden_pago ||')' as lov_descripcion
		FROM AD_TIPOS_ORDEN_PAGO ADTOP
		WHERE ADTOP.cod_tipo_orden_pago = " . quote($codigo) . ";";
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

    static public function get_lov_tipos_orden_pago_x_id($codigo) {
        if (isset($codigo)) {
            $sql = "SELECT ADTOP.*, ADTOP.cod_tipo_orden_pago || ' - ' || ADTOP.descripcion || ' - (Origen:' || ADTOP.origen_orden_pago ||')' as lov_descripcion
		FROM AD_TIPOS_ORDEN_PAGO ADTOP
		WHERE ADTOP.cod_tipo_orden_pago = " . quote($codigo) . ";";
            $datos = toba::db()->consultar_fila($sql);
            if (isset($datos) && !empty($datos) && isset($datos['lov_descripcion'])) {
                return $datos;
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

    static public function get_lov_tipos_orden_pago_x_nombre($nombre, $filtro = array()) {
        if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('cod_tipo_orden_pago', $nombre);
            $trans_des = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
            $where = "( $trans_cod OR $trans_des )";
        } else {
            $where = '1=1';
        }

        if (isset($filtro['clase_orden_pago'])) {
            $where .=" AND ADTOP.clase_orden_pago = '" . $filtro['clase_orden_pago'] . "'";
        }
        unset($filtro['clase_orden_pago']);

        if (isset($filtro['automatico'])) {
            $where .=" AND ADTOP.automatico = '" . $filtro['automatico'] . "'";
        }

        unset($filtro['automatico']);

        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'ADTOP', '1=1');

        $sql = "SELECT ADTOP.*, ADTOP.cod_tipo_orden_pago || ' - ' || ADTOP.descripcion || ' - (Origen:' || ADTOP.origen_orden_pago ||')' as lov_descripcion
		FROM AD_TIPOS_ORDEN_PAGO ADTOP
		WHERE $where
                ORDER BY lov_descripcion;";

        $datos = toba::db()->consultar($sql);

        return $datos;
    }

    static public function get_origen_orden_pago($id_tipo_orden_pago) {

        $sql = "SELECT adtop.origen_orden_pago,adtop.tipo_cuenta_corriente, adtop.presupuestario, adtop.asocia_factura
		FROM AD_TIPOS_ORDEN_PAGO ADTOP
		WHERE ADTOP.cod_tipo_orden_pago = '" . $id_tipo_orden_pago . "';";

        $datos = toba::db()->consultar_fila($sql);

        return $datos;
    }

    static public function get_auxiliar($id_cuenta_corriente, $tipo_cuenta_corriente, $cod_unidad_administracion){
        if (isset($id_cuenta_corriente) && isset($tipo_cuenta_corriente) && isset($cod_unidad_administracion)){
            $sql = "";

            if ($tipo_cuenta_corriente == 'A'){

                $sql = "select auex.cod_auxiliar, auex.descripcion
                        from kr_cuentas_corriente cuco, kr_auxiliares_ext auex
                        where cuco.cod_auxiliar = auex.cod_auxiliar
                            and cuco.cod_unidad_administracion = ".quote($cod_unidad_administracion)."
                            and cuco.id_cuenta_corriente = ".quote($id_cuenta_corriente)."
                            and pkg_pr_auxiliares.imputable(auex.cod_auxiliar) = 'S'
                            and pkg_pr_auxiliares.activo(auex.cod_auxiliar) = 'S'";

            }elseif($tipo_cuenta_corriente == 'J'){

                $sql = "select auex.cod_auxiliar, auex.descripcion
                        from kr_cuentas_corriente cuco, ad_cajas_chicas cach, kr_auxiliares_ext auex
                        where cuco.id_caja_chica = cach.id_caja_chica and cach.cod_auxiliar = auex.cod_auxiliar
                            and cuco.cod_unidad_administracion = ".quote($cod_unidad_administracion)."
                            and cuco.id_cuenta_corriente = ".quote($id_cuenta_corriente)." and pkg_pr_auxiliares.imputable(auex.cod_auxiliar) = 'S'
                            and pkg_pr_auxiliares.activo(auex.cod_auxiliar) = 'S'";
            }

            if (!empty($sql))
                return toba::db()->consultar_fila($sql);

        }

        return array("cod_auxiliar"=>'');
    }

    static public function get_recibos_pagos($id_orden_pago){
    	$sql = "SELECT orc.*,
    adrecb.nro_recibo,
    to_char(adrecb.fecha_comprobante,'DD/MM/YYYY')fecha_comprobante ,
      trim(to_char(orc.importe, '$999,999,999,990.00')) as importe_format
				  FROM ad_ordenes_pago_rc orc, ad_recibos_cobro adrecb
				 WHERE orc.id_recibo_cobro = adrecb.id_recibo_cobro
				 and orc.id_orden_pago = ".quote($id_orden_pago);
    	return toba::db()->consultar($sql);
    }

    public static function get_tipos_orden_pago ($filtro = [])
    {
        toba::db()->abrir_transaccion();

        $where = "1=1";

        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ADTOP', '1=1');

        $sql = "SELECT adtop.*
                    ,(SELECT rv_meaning
                      FROM cg_ref_codes
                     WHERE rv_domain = 'KR_TIPO_CUENTA_CORRIENTE'
                       AND rv_low_value = adtop.tipo_cuenta_corriente) tipo_cuenta_corriente_format,
                   DECODE (adtop.presupuestario, 'S', 'Si', 'No') presupuestario_format,
                   DECODE (adtop.ejercicio_anterior,
                           'S', 'Si',
                           'No'
                          ) ejercicio_anterior_format,
                   DECODE (adtop.asocia_factura, 'S', 'Si', 'No') asocia_factura_format,
                   DECODE (adtop.automatico, 'S', 'Si', 'No') automatico_format
                  FROM ad_tipos_orden_pago adtop
                WHERE $where
                order by adtop.cod_tipo_orden_pago ";
        return toba::db()->consultar($sql);
    }

    static public function cargar_devengado_gastos($id_orden_pago, $fecha_desde, $fecha_hasta)
    {
        ctr_procedimientos::ejecutar_transaccion_compuesta("Error al cargar los devengados", function() use ($id_orden_pago, $fecha_desde, $fecha_hasta){
            //Recupera datos de la Orden de Pago.
            $sql = "SELECT *
                      FROM AD_ORDENES_PAGO
                     WHERE ID_ORDEN_PAGO = ".quote($id_orden_pago);
            $orden_pago = toba::db()->consultar_fila($sql);

            //Recupera datos del Tipo de Orden Pago.
            $sql = "SELECT *
                    FROM AD_TIPOS_ORDEN_PAGO
                    WHERE COD_TIPO_ORDEN_PAGO = '".$orden_pago['cod_tipo_orden_pago']."'";
            $tipo_orden_pago = toba::db()->consultar_fila($sql);

            //Arma el filtro para los devengados a Cargar.
            if (empty($orden_pago['id_expediente'])){$orden_pago['id_expediente']='null';}
            if (empty($orden_pago['id_expediente_pago'])){$orden_pago['id_expediente_pago']='null';}
            if (empty($orden_pago['cod_unidad_ejecutora'])){$orden_pago['cod_unidad_ejecutora']='null';}

            $where = " (cg.aprobado = 'S' AND cg.anulado = 'N' AND cg.fecha_comprobante <= to_date('".substr($orden_pago['fecha_orden_pago'],0,10)."','YYYY/MM/DD')
                 AND ".$orden_pago['cod_unidad_administracion']." = cg.cod_unidad_administracion
                 AND (   (   '".$tipo_orden_pago['ejercicio_anterior']."'  = 'N'
                 AND pkg_kr_ejercicios.retornar_nro_ejercicio(to_date('".substr($orden_pago['fecha_orden_pago'],0,10)."','YYYY/MM/DD')) =
                   pkg_kr_ejercicios.retornar_nro_ejercicio(cg.fecha_comprobante))
                   OR (    '".$tipo_orden_pago['ejercicio_anterior']."' = 'S'
                 AND pkg_kr_ejercicios.retornar_nro_ejercicio(to_date('".substr($orden_pago['fecha_orden_pago'],0,10)."','YYYY/MM/DD')) >
                   pkg_kr_ejercicios.retornar_nro_ejercicio(cg.fecha_comprobante))
                   )
                 AND cg.id_cuenta_corriente = ".$orden_pago['id_cuenta_corriente']."
                 AND cg.importe > 0
                 AND pkg_kr_transacciones.saldo_transaccion
                                (cg.id_transaccion,
                                 cg.id_cuenta_corriente,
                                 SYSDATE
                                ) > 0
                 AND pkg_kr_transacciones.saldo_ordenado (cg.id_comprobante_gasto) > 0
                 AND (   cg.id_expediente = ".$orden_pago['id_expediente']."
                   OR cg.id_expediente IS NULL
                   OR ".$orden_pago['id_expediente']." IS NULL
                   )
                 AND (   cg.id_expediente_pago =  ".$orden_pago['id_expediente_pago']."
                   OR cg.id_expediente_pago IS NULL
                   OR ".$orden_pago['id_expediente_pago']." IS NULL
                   )
            AND (   ".$orden_pago['cod_unidad_ejecutora']." IS NULL
              OR cg.cod_unidad_ejecutora = ".$orden_pago['cod_unidad_ejecutora']." )
            AND pkg_ad_comprobantes_gasto.esta_perimido(cg.id_comprobante_gasto, SYSDATE) = 'N'
            AND NOT EXISTS (
               SELECT 1
                 FROM ad_comprobantes_gasto cg1
                WHERE cg1.id_comprobante_gasto_rei = cg.id_comprobante_gasto
                AND cg1.aprobado = 'S'
                AND cg1.anulado = 'N')
            AND NOT EXISTS (
               SELECT 1
                 FROM ad_comprobantes_gasto cg1
                WHERE cg1.id_comprobante_gasto =
                               cg.id_comprobante_gasto_aju
                AND cg1.aprobado = 'S'
                AND cg1.anulado = 'N')
            )
            AND cg.id_caja_chica is null ";


            if (!is_null($fecha_desde)){
                $where .=" and cg.fecha_comprobante >= to_date('".$fecha_desde."', 'yyyy/mm/dd')";
            }
            if (!is_null($fecha_hasta)){
                $where .=" and cg.fecha_comprobante <= to_date('".$fecha_hasta."', 'yyyy/mm/dd')";
            }


            //Recupera los devengados a Cargar.
            $sql = "SELECT cg.ID_COMPROBANTE_GASTO, cg.importe
                      FROM AD_COMPROBANTES_GASTO cg
                     WHERE $where and cg.id_comprobante_gasto not in
                        (SELECT id_comprobante_gasto
                           FROM AD_ORDENES_PAGO_CG
                          WHERE ID_ORDEN_PAGO = ".quote($id_orden_pago).")
                     ORDER BY cg.fecha_comprobante ASC ";

            $comprobantes_gasto = toba::db()->consultar($sql);

            //Asocia los comprobante a la Orden de Pago.
            foreach ($comprobantes_gasto as $comprobante) {
                $sql = "INSERT INTO AD_ORDENES_PAGO_CG (ID_ORDEN_PAGO, ID_COMPROBANTE_GASTO, IMPORTE) VALUES (".quote($id_orden_pago).", ".quote($comprobante['id_comprobante_gasto']).", (SELECT nvl(pkg_kr_transacciones.saldo_ordenado (id_comprobante_gasto),0) FROM AD_COMPROBANTES_GASTO WHERE ID_COMPROBANTE_GASTO = ".quote($comprobante['id_comprobante_gasto']).") )";
                toba::db()->ejecutar($sql);
            }


        });

        return "OK";
    }

    static private function ejcutar_carga_devengados($id_orden_pago)
    {
        //Recupera datos de la Orden de Pago.
        $sql = "SELECT *
                  FROM AD_ORDENES_PAGO
                 WHERE ID_ORDEN_PAGO = ".quote($id_orden_pago);
        $orden_pago = toba::db()->consultar_fila($sql);

        //Recupera datos del Tipo de Orden Pago.
        $sql = "SELECT *
                FROM AD_TIPOS_ORDEN_PAGO
                WHERE COD_TIPO_ORDEN_PAGO = '".$orden_pago['cod_tipo_orden_pago']."'";
        $tipo_orden_pago = toba::db()->consultar_fila($sql);

        //Arma el filtro para los devengados a Cargar.
        if (empty($orden_pago['id_expediente'])){$orden_pago['id_expediente']='null';}
        if (empty($orden_pago['id_expediente_pago'])){$orden_pago['id_expediente_pago']='null';}
        if (empty($orden_pago['cod_unidad_ejecutora'])){$orden_pago['cod_unidad_ejecutora']='null';}



        $where = " (cg.aprobado = 'S' AND cg.anulado = 'N' AND cg.fecha_comprobante <= to_date('".substr($orden_pago['fecha_orden_pago'],0,10)."','YYYY/MM/DD')
             AND ".$orden_pago['cod_unidad_administracion']." = cg.cod_unidad_administracion
             AND (   (   '".$tipo_orden_pago['ejercicio_anterior']."'  = 'N'
             AND pkg_kr_ejercicios.retornar_nro_ejercicio(to_date('".substr($orden_pago['fecha_orden_pago'],0,10)."','YYYY/MM/DD')) =
               pkg_kr_ejercicios.retornar_nro_ejercicio(cg.fecha_comprobante))
               OR (    '".$tipo_orden_pago['ejercicio_anterior']."' = 'S'
             AND pkg_kr_ejercicios.retornar_nro_ejercicio(to_date('".substr($orden_pago['fecha_orden_pago'],0,10)."','YYYY/MM/DD')) >
               pkg_kr_ejercicios.retornar_nro_ejercicio(cg.fecha_comprobante))
               )
             AND cg.id_cuenta_corriente = ".$orden_pago['id_cuenta_corriente']."
             AND cg.importe > 0
             AND pkg_kr_transacciones.saldo_transaccion
                            (cg.id_transaccion,
                             cg.id_cuenta_corriente,
                             SYSDATE
                            ) > 0
             AND pkg_kr_transacciones.saldo_ordenado (cg.id_comprobante_gasto) > 0
             AND (   cg.id_expediente = ".$orden_pago['id_expediente']."
               OR cg.id_expediente IS NULL
               OR ".$orden_pago['id_expediente']." IS NULL
               )
             AND (   cg.id_expediente_pago =  ".$orden_pago['id_expediente_pago']."
               OR cg.id_expediente_pago IS NULL
               OR ".$orden_pago['id_expediente_pago']." IS NULL
               )
        AND (   ".$orden_pago['cod_unidad_ejecutora']." IS NULL
          OR cg.cod_unidad_ejecutora = ".$orden_pago['cod_unidad_ejecutora']." )
        AND pkg_ad_comprobantes_gasto.esta_perimido(cg.id_comprobante_gasto, SYSDATE) = 'N'
        AND NOT EXISTS (
           SELECT 1
             FROM ad_comprobantes_gasto cg1
            WHERE cg1.id_comprobante_gasto_rei = cg.id_comprobante_gasto
            AND cg1.aprobado = 'S'
            AND cg1.anulado = 'N')
        AND NOT EXISTS (
           SELECT 1
             FROM ad_comprobantes_gasto cg1
            WHERE cg1.id_comprobante_gasto =
                           cg.id_comprobante_gasto_aju
            AND cg1.aprobado = 'S'
            AND cg1.anulado = 'N')
        )
        AND cg.id_caja_chica is null ";


        //Recupera los devengados a Cargar.
        $sql = "SELECT cg.ID_COMPROBANTE_GASTO, cg.importe
                  FROM AD_COMPROBANTES_GASTO cg
                 WHERE $where and cg.id_comprobante_gasto not in
                    (SELECT id_comprobante_gasto
                       FROM AD_ORDENES_PAGO_CG
                      WHERE ID_ORDEN_PAGO = ".quote($id_orden_pago).")
                 ORDER BY cg.fecha_comprobante ASC ";

        $comprobantes_gasto = toba::db()->consultar($sql);


        //Asocia los comprobante a la Orden de Pago.
        foreach ($comprobantes_gasto as $comprobante) {
            $sql = "INSERT INTO AD_ORDENES_PAGO_CG (ID_ORDEN_PAGO, ID_COMPROBANTE_GASTO, IMPORTE) VALUES (".quote($id_orden_pago).", ".quote($comprobante['id_comprobante_gasto']).", ".quote($comprobante['importe'])." )";
        }
    }

    public static function generar_pago_retenciones ($id_recibo_pago, $fecha)
    {
        $sql = "BEGIN :resultado := pkg_ordenes_pago.generar_pago_retenciones(:id_recibo_pago, :fecha); END;";
        $parametros = [
            ['nombre' => 'resultado',
                'tipo_dato' => PDO::PARAM_STR,
                'longitud' => 4000,
                'valor' => ''],
            ['nombre' => 'id_recibo_pago',
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $id_recibo_pago],
            ['nombre' => 'fecha',
                'tipo_dato' => PDO::PARAM_STR,
                'longitud' => 32,
                'valor' => $fecha],
        ];
        ctr_procedimientos::ejecutar_procedimiento('No se pudo generar el Recibo.',$sql,$parametros);
    }

}

?>
