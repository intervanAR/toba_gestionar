<?php

class dao_comprobantes_pago {

    static public function get_datos_comprobante_pago_x_id($id_comprobante_pago) {
        if (isset($id_comprobante_pago)) {
            $sql = "SELECT	vadcopa.*,
							adcopa.fecha_anula,
							to_char(adcopa.fecha_anula, 'dd/mm/rrrr') fecha_anula_format,
							NULL id_transaccion
                   FROM V_AD_COMPROBANTES_PAGO vadcopa
				   JOIN AD_COMPROBANTES_PAGO adcopa ON (vadcopa.id_comprobante_pago = adcopa.id_comprobante_pago)
                   WHERE vadcopa.id_comprobante_pago = $id_comprobante_pago;";
            $datos_comprobante = toba::db()->consultar_fila($sql);

            if (isset($datos_comprobante) && !empty($datos_comprobante)) {
                if ($datos_comprobante['tipo_comprobante_pago'] == 'CHP') {
                    $datos = dao_cheques_propios::get_datos_cheque_propio_x_id($datos_comprobante['id_comprobante_pago']);
                } elseif ($datos_comprobante['tipo_comprobante_pago'] == 'CHT') {
                    $datos = dao_cheques_terceros::get_datos_cheque_tercero_x_id($datos_comprobante['id_comprobante_pago']);
                } elseif ($datos_comprobante['tipo_comprobante_pago'] == 'LOT') {
                    $datos = array();
                } elseif ($datos_comprobante['tipo_comprobante_pago'] == 'COD') {
                    $datos = array();
                } elseif ($datos_comprobante['tipo_comprobante_pago'] == 'TDE') {
                    $datos = array();
                } elseif ($datos_comprobante['tipo_comprobante_pago'] == 'CRR') {
                    $datos = array();
                } elseif ($datos_comprobante['tipo_comprobante_pago'] == 'CRE') {
                    $datos = dao_creditos_emitidos::get_datos_credito_emitido_x_id($datos_comprobante['id_comprobante_pago']);
                } elseif ($datos_comprobante['tipo_comprobante_pago'] == 'RER') {
                    $datos = array();
                } elseif ($datos_comprobante['tipo_comprobante_pago'] == 'REE') {
                    $datos = array();
                } elseif ($datos_comprobante['tipo_comprobante_pago'] == 'DOP') {
                    $datos = dao_documentos_propio::get_datos_documento_propio_x_id($datos_comprobante['id_comprobante_pago']);
                } elseif ($datos_comprobante['tipo_comprobante_pago'] == 'DOT') {
                    $datos = array();
                } elseif ($datos_comprobante['tipo_comprobante_pago'] == 'TRR') {
                    $datos = array();
                } elseif ($datos_comprobante['tipo_comprobante_pago'] == 'TRE') {
                    $datos = dao_transferencias_efectuadas::get_datos_transferencia_efectuadas_x_id($datos_comprobante['id_comprobante_pago']);
                } else {
                    $datos = array();
                }
            } else {
                $datos = array();
            }

            return array_merge($datos_comprobante, $datos);
        } else {
            return array();
        }
    }

    static public function get_tipo_comprobante_pago_x_id($id_comprobante_pago) {
        $datos = self::get_datos_comprobante_pago_x_id($id_comprobante_pago);
        if (isset($datos['tipo_comprobante_pago'])) {
            return $datos['tipo_comprobante_pago'];
        } else {
            return '';
        }
    }

    static public function calcular_retenciones_pago($id_recibo_pago, $id_cuenta_corriente, $fecha_recibo) {
        if (isset($id_recibo_pago) && isset($id_cuenta_corriente) && isset($fecha_recibo)) {
            $sql = "BEGIN :resultado := PKG_AD_COMPROBANTES_PAGOS.CALC_RETENCIONES_PAGO(:id_recibo_pago, :id_cuenta_corriente, to_date(substr(:fecha_recibo,1,10),'yyyy-mm-dd')); END;";

            $parametros = array(array('nombre' => 'id_recibo_pago',
                    'tipo_dato' => PDO::PARAM_INT,
                    'longitud' => 32,
                    'valor' => $id_recibo_pago),
                array('nombre' => 'id_cuenta_corriente',
                    'tipo_dato' => PDO::PARAM_INT,
                    'longitud' => 32,
                    'valor' => $id_cuenta_corriente),
                array('nombre' => 'fecha_recibo',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $fecha_recibo),
                array('nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''),
            );
            $resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error en la generación de retenciones del recibo de pago.');
            return $resultado[3]['valor'];
        }
    }

    public static function get_datos_cheque_propio_x_id($id_comprobante_pago) {
        if (isset($id_comprobante_pago)) {
            $sql = "select chpr.*, to_char(chpr.fecha_emision,'dd/mm/yyyy') fecha_emision_2, to_char(chpr.fecha_vencimiento,'dd/mm/yyyy') fecha_vencimiento_2,chba.id_chequera_banco, ch.nro_cheque
					from ad_cheques_propios chpr, ad_cheques ch, ad_chequeras_banco chba
					where chpr.id_cheque = ch.id_cheque
					and   ch.id_chequera_banco = chba.id_chequera_banco
					and   chpr.id_comprobante_pago = $id_comprobante_pago;";

            return toba::db()->consultar_fila($sql);
        } else {
            return array();
        }
    }

    public static function crear_comprobante_pago($cod_moneda, $tipo_comprobante_pago, $cod_unidad_administracion, $cod_unidad_ejecutora = null, $id_cuenta_banco, $id_sub_cuenta_banco = null, $id_cuenta_banco_destino = null, $id_sub_cuenta_banco_destino = null, $con_transaccion = false) {
        try {
            if (isset($cod_unidad_administracion) && (!empty($cod_unidad_administracion))) {
                if ($con_transaccion) {
                    toba::db()->abrir_transaccion();
                }
                if (!isset($cod_unidad_ejecutora) || empty($cod_unidad_ejecutora)) {
                    $cod_unidad_ejecutora = "";
                }
                if (!isset($id_cuenta_banco) || empty($id_cuenta_banco)) {
                    $id_cuenta_banco = "";
                }
                if (!isset($id_sub_cuenta_banco) || empty($id_sub_cuenta_banco)) {
                    $id_sub_cuenta_banco = "";
                }
                if (!isset($id_cuenta_banco_destino) || empty($id_cuenta_banco_destino)) {
                    $id_cuenta_banco_destino = "";
                }
                if (!isset($id_sub_cuenta_banco_destino) || empty($id_sub_cuenta_banco_destino)) {
                    $id_sub_cuenta_banco_destino = "";
                }

                $sql = "BEGIN pkg_ad_comprobantes_pagos.crear_comprobante(:id_comprobante_pago, :cod_moneda, :cod_unidad_administracion, :cod_unidad_ejecutora, :tipo_comprobante_pago, :id_cuenta_banco, :id_sub_cuenta_banco, :id_cuenta_banco_destino, :id_sub_cuenta_banco_destino, null, null, PKG_AD_COMPROBANTES_PAGOS.estado_inicial_comp_pago, :user, to_date(substr(:fecha,1,10),'yyyy-mm-dd')); END;";

                $usuario = strtoupper(toba::usuario()->get_id());

                $parametros = array(array('nombre' => 'fecha',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 32,
                        'valor' => date('y-m-d')),
                    array('nombre' => 'id_comprobante_pago',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => ''),
                    array('nombre' => 'cod_moneda',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $cod_moneda),
                    array('nombre' => 'cod_unidad_administracion',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $cod_unidad_administracion),
                    array('nombre' => 'cod_unidad_ejecutora',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $cod_unidad_ejecutora),
                    array('nombre' => 'tipo_comprobante_pago',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $tipo_comprobante_pago),
                    array('nombre' => 'id_cuenta_banco',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $id_cuenta_banco),
                    array('nombre' => 'id_sub_cuenta_banco',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $id_sub_cuenta_banco),
                    array('nombre' => 'id_cuenta_banco_destino',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $id_cuenta_banco_destino),
                    array('nombre' => 'id_sub_cuenta_banco_destino',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $id_sub_cuenta_banco_destino),
                    array('nombre' => 'user',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $usuario),
                );
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

                if ($con_transaccion) {
                    toba::db()->cerrar_transaccion();
                }

                return $resultado[1]['valor'];
            } else {
                return '';
            }
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            if ($con_transaccion) {
                toba::db()->abortar_transaccion();
            }
            return '';
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
            if ($con_transaccion) {
                toba::db()->abortar_transaccion();
            }
            return '';
        }
    }

    public static function actualizar_comprobante_pago($id_comprobante_pago, $cod_moneda, $tipo_comprobante_pago, $cod_unidad_administracion, $cod_unidad_ejecutora = null, $id_cuenta_banco = null, $id_sub_cuenta_banco = null, $id_cuenta_banco_destino = null, $id_sub_cuenta_banco_destino = null, $con_transaccion = false) {
        try {
            if (isset($cod_unidad_administracion) && (!empty($cod_unidad_administracion))) {
                if ($con_transaccion) {
                    toba::db()->abrir_transaccion();
                }
                if (!isset($cod_unidad_ejecutora) || empty($cod_unidad_ejecutora)) {
                    $cod_unidad_ejecutora = "";
                }
                if (!isset($id_cuenta_banco) || empty($id_cuenta_banco)) {
                    $id_cuenta_banco = "";
                }
                if (!isset($id_sub_cuenta_banco) || empty($id_sub_cuenta_banco)) {
                    $id_sub_cuenta_banco = "";
                }
                if (!isset($id_cuenta_banco_destino) || empty($id_cuenta_banco_destino)) {
                    $id_cuenta_banco_destino = "";
                }
                if (!isset($id_sub_cuenta_banco_destino) || empty($id_sub_cuenta_banco_destino)) {
                    $id_sub_cuenta_banco_destino = "";
                }

                $sql = "BEGIN :resultado := pkg_ad_comprobantes_pagos.actualizar_comprobante(:id_comprobante_pago, :cod_moneda, :cod_unidad_administracion, :cod_unidad_ejecutora, :tipo_comprobante_pago, :id_cuenta_banco, :id_sub_cuenta_banco, :id_cuenta_banco_destino, :id_sub_cuenta_banco_destino, null, null, PKG_AD_COMPROBANTES_PAGOS.estado_inicial_comp_pago, :user, to_date(substr(:fecha,1,10),'yyyy-mm-dd')); END;";

                $usuario = strtoupper(toba::usuario()->get_id());

                $parametros = array(array('nombre' => 'fecha',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 32,
                        'valor' => date('y-m-d')),
                    array('nombre' => 'id_comprobante_pago',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $id_comprobante_pago),
                    array('nombre' => 'cod_moneda',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $cod_moneda),
                    array('nombre' => 'cod_unidad_administracion',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $cod_unidad_administracion),
                    array('nombre' => 'cod_unidad_ejecutora',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $cod_unidad_ejecutora),
                    array('nombre' => 'tipo_comprobante_pago',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $tipo_comprobante_pago),
                    array('nombre' => 'id_cuenta_banco',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $id_cuenta_banco),
                    array('nombre' => 'id_sub_cuenta_banco',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $id_sub_cuenta_banco),
                    array('nombre' => 'id_cuenta_banco_destino',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $id_cuenta_banco_destino),
                    array('nombre' => 'id_sub_cuenta_banco_destino',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $id_sub_cuenta_banco_destino),
                    array('nombre' => 'user',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $usuario),
                    array('nombre' => 'resultado',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 4000,
                        'valor' => ''),
                );
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

                if ($con_transaccion) {
                    toba::db()->cerrar_transaccion();
                }

                return $resultado[11]['valor'];
            } else {
                return '';
            }
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            if ($con_transaccion) {
                toba::db()->abortar_transaccion();
            }
            return '';
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
            if ($con_transaccion) {
                toba::db()->abortar_transaccion();
            }
            return '';
        }
    }

    public static function eliminar_comprobante_pago($id_comprobante_pago, $con_transaccion = false) {
        try {
            if (isset($id_comprobante_pago) && (!empty($id_comprobante_pago))) {
                if ($con_transaccion) {
                    toba::db()->abrir_transaccion();
                }
                $sql = "BEGIN :resultado := PKG_AD_COMPROBANTES_PAGOS.ELIMINAR_COMPROBANTE(:ID_COMPROBANTE_PAGO); END;";

                $parametros = array(array('nombre' => 'id_comprobante_pago',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $id_comprobante_pago),
                    array('nombre' => 'resultado',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 4000,
                        'valor' => ''),
                );
                $resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, null, null, false, 'S');

                if ($con_transaccion) {
                    toba::db()->cerrar_transaccion();
                }

                if (isset($resultado[1]['valor'])) {
                    return $resultado[1]['valor'];
                } else {
                    return '';
                }
            } else {
                return '';
            }
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            if ($con_transaccion) {
                toba::db()->abortar_transaccion();
            }
            return '';
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
            if ($con_transaccion) {
                toba::db()->abortar_transaccion();
            }
            return '';
        }
    }

    public static function get_comprobante_pago_x_id($id_comprobante_pago) {
        if (isset($id_comprobante_pago)) {
            $sql = "SELECT acp.id_comprobante_pago, acp.tipo_comprobante_pago
                    FROM AD_COMPROBANTES_PAGO acp
                    WHERE acp.id_comprobante_pago = " . quote($id_comprobante_pago) . ";";
            $datos = toba::db()->consultar_fila($sql);

            if (isset($datos) && !empty($datos)) {
                if ($datos['tipo_comprobante_pago'] == 'CHP') {
                    return dao_cheques_propios::get_cheque_propio_x_id($datos['id_comprobante_pago']);
                }
                if ($datos['tipo_comprobante_pago'] == 'CHT') {
                    return dao_cheques_terceros::get_cheque_tercero_x_id($datos['id_comprobante_pago']);
                }
                if ($datos['tipo_comprobante_pago'] == 'LOT') {
                    return dao_lotes_efectivo::get_lote_efectivo_x_id($datos['id_comprobante_pago']);
                }
                if ($datos['tipo_comprobante_pago'] == 'COD') {
                    return dao_compensaciones_deuda::get_compensacion_deuda_x_id($datos['id_comprobante_pago']);
                }
                if ($datos['tipo_comprobante_pago'] == 'TDE') {
                    return dao_traspasos_deuda::get_traspaso_deuda_x_id($datos['id_comprobante_pago']);
                }
                if ($datos['tipo_comprobante_pago'] == 'CRR') {
                    return dao_creditos_recibido::get_credito_recibido_x_id($datos['id_comprobante_pago']);
                }
                if ($datos['tipo_comprobante_pago'] == 'CRE') {
                    return dao_creditos_emitidos::get_credito_emitido_x_id($datos['id_comprobante_pago']);
                }
                if ($datos['tipo_comprobante_pago'] == 'RER') {
                    return dao_retenciones_recibidas::get_retencion_recibida_x_id($datos['id_comprobante_pago']);
                }
                if ($datos['tipo_comprobante_pago'] == 'REE') {
                    return dao_retenciones_efectuadas::get_retencion_efectuada_x_id($datos['id_comprobante_pago']);
                }
                if ($datos['tipo_comprobante_pago'] == 'DOP') {
                    return dao_documentos_propio::get_documento_propio_x_id($datos['id_comprobante_pago']);
                }
                if ($datos['tipo_comprobante_pago'] == 'DOT') {
                    return dao_documentos_terceros::get_documento_tercero_x_id($datos['id_comprobante_pago']);
                }
                if ($datos['tipo_comprobante_pago'] == 'TRR') {
                    return dao_transferencias_recibidas::get_transferencia_recibida_x_id($datos['id_comprobante_pago']);
                }
                if ($datos['tipo_comprobante_pago'] == 'TRE') {
                    return dao_transferencias_efectuadas::get_transferencia_efectuadas_x_id($datos['id_comprobante_pago']);
                }
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

    public static function get_comprobantes_pago_x_nombre_medio_pago($nombre, $cod_medio_pago, $origen, $cod_unidad_administracion) {
               
        if (isset($cod_medio_pago) && isset($origen) && isset($cod_unidad_administracion)) {
            $condiciones_comprobante = dao_medios_pago::get_condiciones_comprobante_x_medio_pago($cod_medio_pago, $origen);
            
            $medio_pago = dao_medios_pago::get_medios_pago(array("cod_medio_pago" => $cod_medio_pago));

            $filtro = array("cod_unidad_administracion" => $cod_unidad_administracion, "id_cobro" => 'null', "crea_comprobante" => $condiciones_comprobante['crea_comprobante']);

            if ($medio_pago[0]['tipo_comprobante_pago'] == 'CHP') {
                return dao_cheques_propios::get_lov_cheques_propios_x_nombre($nombre, $filtro);
            }
            if ($medio_pago[0]['tipo_comprobante_pago'] == 'CHT') {
                return dao_cheques_terceros::get_lov_cheques_terceros_x_nombre($nombre, $filtro);
            }
            if ($medio_pago[0]['tipo_comprobante_pago'] == 'LOT') {
                return dao_lotes_efectivo::get_lov_lotes_efectivo_x_nombre($nombre, $filtro);
            }
            if ($medio_pago[0]['tipo_comprobante_pago'] == 'COD') {
                return dao_compensaciones_deuda::get_lov_compensaciones_deuda_x_nombre($nombre, $filtro);
            }
            if ($medio_pago[0]['tipo_comprobante_pago'] == 'TDE') {
                return dao_traspasos_deuda::get_lov_traspasos_deuda_x_nombre($nombre, $filtro);
            }
            if ($medio_pago[0]['tipo_comprobante_pago'] == 'CRR') {
                return dao_creditos_recibido::get_lov_creditos_recibido_x_nombre($nombre, $filtro);
            }
            if ($medio_pago[0]['tipo_comprobante_pago'] == 'CRE') {
                return dao_creditos_emitidos::get_lov_creditos_emitidos_x_nombre($nombre, $filtro);
            }
            if ($medio_pago[0]['tipo_comprobante_pago'] == 'RER') {
                return dao_retenciones_recibidas::get_lov_retenciones_recibidas_x_nombre($nombre, $filtro);
            }
            if ($medio_pago[0]['tipo_comprobante_pago'] == 'REE') {
                return dao_retenciones_efectuadas::get_lov_retenciones_efectuadas_x_nombre($nombre, $filtro);
            }
            if ($medio_pago[0]['tipo_comprobante_pago'] == 'DOP') {
                return dao_documentos_propio::get_lov_documentos_propio_x_nombre($nombre, $filtro);
            }
            if ($medio_pago[0]['tipo_comprobante_pago'] == 'DOT') {
                return dao_documentos_terceros::get_lov_documentos_terceros_x_nombre($nombre, $filtro);
            }
            if ($medio_pago[0]['tipo_comprobante_pago'] == 'TRR') {
                return dao_transferencias_recibidas::get_lov_transferencias_recibidas_x_nombre($nombre, $filtro);
            }
            if ($medio_pago[0]['tipo_comprobante_pago'] == 'TRE') {
                return dao_transferencias_efectuadas::get_lov_transferencias_efectuadas_x_nombre($nombre, $filtro);
            }
        }
        return array();
    }

    public static function get_lov_forma_emision_cheque_x_cod($cod_forma_emision) {
        if (isset($cod_forma_emision)) {
            $sql = "SELECT c.*, c.cod_forma_emision||' - '||c.descripcion lov_descripcion
                        FROM AD_FORMAS_EMISION_CHEQUE c
                        WHERE cod_forma_emision = " . quote($cod_forma_emision) . ";";

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

    static public function get_lov_formas_emision_cheque_x_nombre($nombre, $filtro = array()) {
        if (isset($nombre)) {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('cod_forma_emision', $nombre);
            $trans_nombre = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
            $where = "($trans_codigo OR $trans_nombre)";
        } else {
            $where = '1=1';
        }

        $where .= 'AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'c', '1=1');

        $sql = "SELECT c.*, c.cod_forma_emision||' - '||c.descripcion lov_descripcion
				FROM AD_FORMAS_EMISION_CHEQUE c
				WHERE $where
				ORDER BY lov_descripcion ASC;";

        $datos = toba::db()->consultar($sql);
        return $datos;
    }

    static public function get_lov_formas_emision_cheque($filtro = array()) {
        $where = '1=1';

        $where .= 'AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'c', '1=1');

        $sql = "SELECT c.*, c.cod_forma_emision||' - '||c.descripcion lov_descripcion
				FROM AD_FORMAS_EMISION_CHEQUE c
				WHERE $where
				ORDER BY lov_descripcion ASC;";

        $datos = toba::db()->consultar($sql);
        return $datos;
    }

    static public function get_clases_documentos($filtro = array()) {
        $where = "1=1";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'acd', '1=1');

        $sql = "SELECT acd.*,
						acd.cod_clase_documento || ' - ' || acd.descripcion as lov_descripcion
                FROM AD_CLASES_DOCUMENTO acd
                WHERE  $where";

        $datos = toba::db()->consultar($sql);

        return $datos;
    }

    public static function get_lov_comprobantes_pago_x_nombre($nombre, $filtro = array()) {
        if (isset($nombre)) {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('id_comprobante_pago', $nombre);
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_comprobante', $nombre);
            $where = "($trans_codigo OR $trans_nro)";
        } else {
            $where = '1=1';
        }
        if (isset($filtro['cod_uni_ejecutora']) && $filtro['cod_uni_ejecutora'] == 0) {
            $filtro['cod_uni_ejecutora'] = 'NULL';
        }
        if (isset($filtro['transferencia_fondos'])) {
            $where .="  AND vacp.cod_unidad_administracion = " . $filtro['cod_uni_admin'] . "
                        AND vacp.tipo_comprobante_pago = '" . $filtro['tipo_comprobante_pago'] . "'
                        AND vacp.estado = 'CARG'
                        AND (   " . $filtro['cod_uni_ejecutora'] . " IS NULL
                             OR vacp.cod_unidad_ejecutora = " . $filtro['cod_uni_ejecutora'] . "
                            )";
            unset($filtro['transferencia_fondos']);
            unset($filtro['cod_uni_ejecutora']);
            unset($filtro['cod_uni_admin']);
            unset($filtro['tipo_comprobante_pago']);
        }
        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'vacp', '1=1');

        $sql = "SELECT  vacp.*, 
                        '#' || vacp.id_comprobante_pago
         || ' ( '
         || vacp.TIPO_COMPROBANTE_PAGO
         || ' ' ||vacp.nro_comprobante ||')'
         AS lov_descripcion
                FROM V_AD_COMPROBANTES_PAGO vacp
				JOIN CG_REF_CODES cg ON (cg.RV_DOMAIN = 'AD_TIPO_COMPROB_PAGO' AND cg.RV_LOW_VALUE = vacp.TIPO_COMPROBANTE_PAGO)
                WHERE $where
                ORDER BY lov_descripcion ASC;";

        $datos = toba::db()->consultar($sql);

        return $datos;
    }
	
	public static function get_lov_comprobantes_pago($filtro = array()) {
        $where = "1=1";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'acp', '1=1');

        $sql = "SELECT  acp.*, '#'||acp.id_comprobante_pago
                        || ' ('
                        || acp.TIPO_COMPROBANTE_PAGO 
                        || ' '
                        || (select nro_comprobante
                            from v_ad_comprobantes_pago
                            where tipo_comprobante_pago = acp.tipo_comprobante_pago
                            and id_comprobante_pago = acp.id_comprobante_pago)  
                        || ')' lov_descripcion
				   FROM ad_comprobantes_pago acp, kr_cuentas_banco k
				  WHERE acp.id_cuenta_banco_destino = k.id_cuenta_banco 
		            AND  $where
				ORDER BY acp.id_comprobante_pago";

        $datos = toba::db()->consultar($sql);

        return $datos;
    }

    static public function saldo_comprobante_pago($id_comprobante_pago, $id_cuenta_banco) {
        if (isset($id_comprobante_pago) && isset($id_cuenta_banco)) {
            try {
                $sql = "BEGIN :resultado := pkg_kr_transacciones.saldo_comprobante_pago(:id_comprobante_pago, :id_cuenta_banco); END;";

                $parametros = array(array('nombre' => 'id_comprobante_pago',
                        'tipo_dato' => PDO::PARAM_INT,
                        'longitud' => 32,
                        'valor' => $id_comprobante_pago),
                    array('nombre' => 'id_cuenta_banco',
                        'tipo_dato' => PDO::PARAM_INT,
                        'longitud' => 32,
                        'valor' => $id_cuenta_banco),
                    array('nombre' => 'resultado',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 4000,
                        'valor' => ''),
                );
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                return $resultado[2]['valor'];
            } catch (PDOException $e) {
                return 0;
            }
        } else {
            return 0;
        }
    }
	
	static public function get_saldo_ordenes_ret_comprobante_pago($id_recibo_pago) {
        if (isset($id_recibo_pago)) {
            try {
                $sql = "select nvl(sum(importe),0) importe_orden
					from ad_recibos_pago_op
					where id_recibo_pago = ".quote($id_recibo_pago);
				
				$rta = toba::db()->consultar_fila($sql);
				$importe_orden= $rta['importe_orden'];
				
				$sql="select nvl(sum(importe_nominal),0) importe_pagos
					from   ad_pagos
					where  id_recibo_pago = ".quote($id_recibo_pago);
				
				$rta = toba::db()->consultar_fila($sql);
                
				$importe_pagos= $rta['importe_pagos'];
				
                return $importe_orden - $importe_pagos;
            } catch (PDOException $e) {
                return 0;
            }
        } else {
            return 0;
        }
    }


    /*
     * MATRIZ DE ESTADO, COMPROBANTE DE PAGO.
     */

    static public function get_matriz_estado_comp_pago($filtro = []){  
        $where = "1=1";
        
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'admcompp', '1=1');

        $sql = "SELECT admcompp.*,
                       CASE
                          WHEN admcompp.entrega_dif = 'S'
                             THEN 'Si'
                          WHEN admcompp.entrega_dif = 'N'
                             THEN 'No'
                          ELSE ''
                       END entrega_dif_format,
                       CASE
                          WHEN admcompp.vencimiento_dif = 'S'
                             THEN 'Si'
                          WHEN admcompp.vencimiento_dif = 'N'
                             THEN 'No'
                          ELSE ''
                       END vencimiento_dif_format,
                       CASE
                          WHEN admcompp.cuenta_origen = 'S'
                             THEN 'Si'
                          WHEN admcompp.cuenta_origen = 'N'
                             THEN 'No'
                          ELSE ''
                       END cuenta_origen_format,
                       CASE
                          WHEN admcompp.tipo_cuenta_pred_o = 'S'
                             THEN 'Si'
                          WHEN admcompp.tipo_cuenta_pred_o = 'N'
                             THEN 'No'
                          ELSE ''
                       END tipo_cuenta_pred_o_format,
                       CASE
                          WHEN admcompp.cuenta_destino = 'S'
                             THEN 'Si'
                          WHEN admcompp.cuenta_destino = 'N'
                             THEN 'No'
                          ELSE ''
                       END cuenta_destino_format,
                       estado.descripcion estado_format,
                       estadoh.descripcion estado_hasta_format
                  FROM ad_matriz_comp_pago admcompp,
                       ad_estados_comp_pago estado,
                       ad_estados_comp_pago estadoh
                 WHERE admcompp.estado = estado.estado
                   AND admcompp.estado_hasta = estadoh.estado and $where
                order by admcompp.ID_MATRIZ_COMP_PAGO desc ";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }

    static public function get_estados_comprobantes ($filtro = [])
    {
        $where = "1=1";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'adecp', '1=1');
        $sql = "SELECT adecp.*, DECODE (adecp.en_caja, 'S', 'Si', 'No') en_caja_format,
                       DECODE (adecp.FINAL, 'S', 'Si', 'No') final_format,
                       DECODE (adecp.inicial, 'S', 'Si', 'No') inicial_format,
                       DECODE (adecp.transito, 'S', 'Si', 'No') transito_format
                  FROM ad_estados_comp_pago adecp
                 WHERE $where
              ORDER BY adecp.estado";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }

    public static function set_id_cuenta_banco($id_cuenta_banco,$id_comprobante_pago){


            $sql = ("UPDATE ad_cobros
                    set id_cuenta_banco = ".$id_cuenta_banco."
                    WHERE id_comprobante_pago = ".$id_comprobante_pago."");

          // ctr_procedimientos::ejecutar_transaccion_simple('No se pudo guardar.',$sql);
        dao_varios::ejecutar_sql($sql, false);

         
    // //return toba::db()->consultar($sql);
    
}


}

?>
