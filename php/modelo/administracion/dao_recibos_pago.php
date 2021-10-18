<?php

class dao_recibos_pago {

    static public function get_recibos_pago($filtro = array(), $orden = array()) {
        $desde= null; $hasta= null;
        if(isset($filtro['numrow_desde'])){
            $desde= $filtro['numrow_desde'];
            $hasta= $filtro['numrow_hasta'];
            unset($filtro['numrow_desde']);
            unset($filtro['numrow_hasta']);
        }
        
        $where = self::armar_where($filtro);
        $sql = "SELECT  rp.*,
                        decode(rp.aprobado,'S','Si','No') aprobado_format,
                        decode(rp.anulado,'S','Si','No') anulado_format, 
                        to_char(rp.fecha_recibo, 'dd/mm/yyyy') fecha_recibo_format, 
                        to_char(rp.fecha_anulacion_recibo, 'dd/mm/yyyy') fecha_anulacion_recibo_format, 
                        pkg_kr_transacciones.saldo_transaccion(rp.id_transaccion, rp.id_cuenta_corriente, sysdate) saldo_transaccion,
                        atrp.descripcion tipo_recibo,
                        ke.nro_expediente as nro_expediente,
                        kep.nro_expediente as nro_expediente_pago,
                        kcc.nro_cuenta_corriente || ' - ' || kcc.descripcion as cuenta_corriente,
                        kae.cod_auxiliar || ' - ' || kae.descripcion as auxiliar,
                        CASE
                            WHEN rp.automatico = 'S' THEN 'Si'
                            ELSE 'No'
                        END automatico_format,
                        trim(to_char(rp.importe, '$999,999,999,990.00')) as importe_format,
                        trim(to_char(rp.importe_retenciones_orden, '$999,999,999,990.00')) as importe_ret_orden_format,
                        trim(to_char(rp.importe_retenciones_pago, '$999,999,999,990.00')) as importe_ret_pago_format
                FROM AD_RECIBOS_PAGO rp
                JOIN AD_TIPOS_RECIBO_PAGO atrp ON rp.cod_tipo_recibo =  atrp.cod_tipo_recibo
                JOIN KR_CUENTAS_CORRIENTE kcc ON rp.id_cuenta_corriente = kcc.id_cuenta_corriente
                LEFT JOIN kr_expedientes ke ON rp.id_expediente = ke.id_expediente
                LEFT JOIN kr_expedientes kep ON rp.id_expediente_pago = kep.id_expediente
                LEFT JOIN kr_auxiliares_ext kae ON rp.cod_auxiliar = kae.cod_auxiliar
                WHERE  $where
                ORDER BY rp.id_recibo_pago DESC";

        $sql= dao_varios::paginador($sql, null, $desde, $hasta, null, $orden);
        $datos = toba::db()->consultar($sql);

        return $datos;
    }
    static public function armar_where ($filtro = array())
    {
        $where = "1=1";
        if (isset($filtro['observaciones'])) {
            $where .= "AND " . ctr_construir_sentencias::construir_translate_ilike('rp.observaciones', $filtro['observaciones']);
            unset($filtro['observaciones']);
        }
        if (isset($filtro['ids_comprobantes'])) {
            $where .= "AND rp.id_recibo_pago IN (" . $filtro['ids_comprobantes'] . ") ";
            unset($filtro['ids_comprobantes']);
        }
        if (isset($filtro['not_ids_comprobantes'])) {
            $where .= "AND rp.id_recibo_pago NOT IN (" . $filtro['not_ids_comprobantes'] . ") ";
            unset($filtro['not_ids_comprobantes']);
        }
        if (isset($filtro['cod_tipos_recibos'])) {
            $where .= "AND rp.cod_tipo_recibo IN (" . $filtro['cod_tipos_recibos'] . ") ";
            unset($filtro['cod_tipos_recibos']);
        }
        if (isset($filtro['con_saldo']) && $filtro['con_saldo'] == 1) {
            $where .= " AND pkg_kr_transacciones.saldo_transaccion(rp.id_transaccion, rp.id_cuenta_corriente, sysdate) > 0 ";
            unset($filtro['con_saldo']);
        }
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'rp', '1=1');
        return $where;
    }
    
    static public function get_cantidad ($filtro = array())
    {
        $where = self::armar_where($filtro);
        $sql = " select count(*) cantidad
                   FROM AD_RECIBOS_PAGO rp
                        JOIN AD_TIPOS_RECIBO_PAGO atrp ON rp.cod_tipo_recibo =  atrp.cod_tipo_recibo
                        JOIN KR_CUENTAS_CORRIENTE kcc ON rp.id_cuenta_corriente = kcc.id_cuenta_corriente
                        LEFT JOIN kr_expedientes ke ON rp.id_expediente = ke.id_expediente
                        LEFT JOIN kr_expedientes kep ON rp.id_expediente_pago = kep.id_expediente
                        LEFT JOIN kr_auxiliares_ext kae ON rp.cod_auxiliar = kae.cod_auxiliar
                  WHERE $where ";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['cantidad'];
    }
    
    static public function get_recibo_pago_x_id($id_recibo_pago) {
        if (isset($id_recibo_pago)) {
            $sql = "SELECT  rp.*, 
                            rp.id_recibo_pago||' - '||rp.nro_recibo ||' - '||to_char(rp.fecha_recibo,'dd/mm/rr')||' - '||rp.IMPORTE as lov_descripcion,
                            rp.fecha_recibo fecha_comprobante,
                            rp.fecha_carga,
                            to_char(rp.fecha_recibo, 'dd/mm/yyyy') fecha_comprobante_format,
                            kcc.tipo_cuenta_corriente,
                            to_char(rp.fecha_aprueba, 'dd/mm/yyyy hh24:mi:ss') fecha_aprueba_format
                   FROM AD_RECIBOS_PAGO rp
                   JOIN KR_CUENTAS_CORRIENTE kcc ON (rp.id_cuenta_corriente = kcc.id_cuenta_corriente)
                   WHERE rp.id_recibo_pago = $id_recibo_pago
                   ORDER BY lov_descripcion;";
            return toba::db()->consultar_fila($sql);
        } else {
            return array();
        }
    }

    static public function get_id_nro_recibo_pago_x_id($id_recibo_pago) {
        if (isset($id_recibo_pago)) {
            $sql = "SELECT  rp.*,'#'||rp.id_recibo_pago||' '
         || '('||SUBSTR (concat_all('select DISTINCT cp.nro_comprobante from ad_recibos_pago arp, ad_pagos ap, v_ad_comprobantes_pago cp where arp.id_recibo_pago = ap.id_recibo_pago and  ap.id_comprobante_pago = cp.id_comprobante_pago and arp.id_recibo_pago = '|| rp.id_recibo_pago|| ' ',','),1,2000)
         || ') '
         || TO_CHAR (rp.fecha_recibo, 'dd/mm/rr')
         || ' - '
         || (pkg_general.SIGNIFICADO_DOMINIO('KR_TIPO_CUENTA_CORRIENTE', l_krctct.TIPO_CUENTA_CORRIENTE)) ||' '|| l_krctct.NRO_CUENTA_CORRIENTE ||' - '|| trim(to_char(rp.importe, '$999,999,999,990.00'))  AS lov_descripcion
                   FROM AD_RECIBOS_PAGO rp, kr_cuentas_corriente l_krctct 
                  WHERE rp.id_cuenta_corriente = l_krctct.id_cuenta_corriente 
                    and rp.id_recibo_pago = $id_recibo_pago
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

    static public function get_lov_recibos_pago_x_nombre($nombre, $filtro = array()) {
        if (isset($nombre)) {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('id_recibo_pago', $nombre);
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_recibo', $nombre);
            $trans_nro_cuenta = ctr_construir_sentencias::construir_translate_ilike('l_krctct.nro_cuenta_corriente', $nombre);
            $where = "($trans_codigo OR $trans_nro OR $trans_nro_cuenta)";
        } else {
            $where = '1=1';
        }

        if (isset($filtro['presupuestario'])) {
            
            if (is_null($filtro['cod_auxiliar']))
                $filtro['cod_auxiliar'] = 'NULL';
            
            $where.= " AND PKG_AD_COMPROBANTES_PAGOS.muestro_pago(rp.ANULADO, rp.APROBADO, '" . $filtro['presupuestario'] . "',
                    rp.IMPORTE, 
                    Pkg_Kr_Transacciones.saldo_transaccion (rp.ID_TRANSACCION, rp.ID_CUENTA_CORRIENTE, NULL),
                    '" . $filtro['tipo_cuenta_corriente'] . "', '" . $filtro['ejercicio_anterior'] . "',
                    rp.FECHA_RECIBO, to_date(substr('" . $filtro['fecha_comprobante'] . "',1,10),'yyyy-mm-dd'),
                    rp.ID_CUENTA_CORRIENTE , " . $filtro['id_cuenta_corriente'] . ") = 'S'
                    AND PKG_KR_USUARIOS.TIENE_UA(upper('" . $filtro['usuario'] . "'),RP.COD_UNIDAD_ADMINISTRACION) = 'S'
                    AND rp.COD_AUXILIAR =" . $filtro['cod_auxiliar'] . "
                    AND (p_unidad_ejecutora IS NULL OR rp.cod_unidad_ejecutora = p_unidad_ejecutora)";

            if (isset($filtro['cod_unidad_ejecutora'])) {
                if (empty($filtro['cod_unidad_ejecutora'])) {
                    $where = str_replace("p_unidad_ejecutora", "null", $where, $count);
                } else {
                    $where = str_replace("p_unidad_ejecutora", $filtro['cod_unidad_ejecutora'], $where, $count);
                }
            } else {
                $where = str_replace("p_unidad_ejecutora", "null", $where, $count);
            }

            unset($filtro['presupuestario']);
            unset($filtro['tipo_cuenta_corriente']);
            unset($filtro['ejercicio_anterior']);
            unset($filtro['fecha_comprobante']);
            unset($filtro['usuario']);
            unset($filtro['id_cuenta_corriente']);
            unset($filtro['cod_auxiliar']);
            unset($filtro['cod_unidad_ejecutora']);
        }
        if (isset($filtro['para_ordenes_pago'])) {
            if ($filtro['cod_uni_ejecutora']==0) {
                $filtro['cod_uni_ejecutora'] = 'NULL';
            }
            $where .= " AND (    EXISTS (
                                    SELECT 1
                                      FROM ad_tipos_recibo_pago
                                     WHERE rp.cod_tipo_recibo = cod_tipo_recibo
                                       AND presupuestario = 'N')
                             AND pkg_kr_transacciones.saldo_transaccion
                                                                       (rp.id_transaccion,
                                                                        rp.id_cuenta_corriente,
                                                                        SYSDATE
                                                                       ) > 0
                             AND rp.aprobado = 'S'
                             AND rp.anulado = 'N'
                             AND " . $filtro['cod_uni_admin'] . " = rp.cod_unidad_administracion
                             AND (   " . $filtro['id_cta_cte'] . " = rp.id_cuenta_corriente
                                  OR (    l_krctct.tipo_cuenta_corriente = 'A'
                                      AND NOT EXISTS (
                                             SELECT 1
                                               FROM ad_recibos_pago_op rpop, ad_ordenes_pago op
                                              WHERE rpop.id_orden_pago = op.id_orden_pago
                                                AND rpop.id_recibo_pago = rp.id_recibo_pago
                                                AND (   op.id_comprobante_anticipo IS NOT NULL
                                                     OR op.id_comprobante_caja_chica IS NOT NULL
                                                    ))
                                     )
                                 )
                             AND (   " . $filtro['cod_uni_ejecutora'] . " IS NULL
                                  OR rp.cod_unidad_ejecutora =
                                                                " . $filtro['cod_uni_ejecutora'] . "
                                 )
                            )";
            $where .= " and rp.fecha_recibo <= to_date('".$filtro['fecha_orden_pago']."','yyyy-mm-dd') ";
            unset($filtro['para_ordenes_pago']);
            unset($filtro['fecha_orden_pago']);
            unset($filtro['cod_uni_admin']);
            unset($filtro['id_cta_cte']);
            unset($filtro['cod_uni_ejecutora']);
        }
        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'rp', '1=1');

        $sql = "SELECT   rp.*,'#'||rp.id_recibo_pago||' '
         || '('||SUBSTR (concat_all('select DISTINCT cp.nro_comprobante from ad_recibos_pago arp, ad_pagos ap, v_ad_comprobantes_pago cp where arp.id_recibo_pago = ap.id_recibo_pago and  ap.id_comprobante_pago = cp.id_comprobante_pago and arp.id_recibo_pago = '|| rp.id_recibo_pago|| ' ',','),1,2000)
         || ') '
         || TO_CHAR (rp.fecha_recibo, 'dd/mm/rr')
         || ' - '
         || (pkg_general.SIGNIFICADO_DOMINIO('KR_TIPO_CUENTA_CORRIENTE', l_krctct.TIPO_CUENTA_CORRIENTE)) ||' '|| l_krctct.NRO_CUENTA_CORRIENTE ||' - '|| trim(to_char(rp.importe, '$999,999,999,990.00'))  AS lov_descripcion
                FROM AD_RECIBOS_PAGO rp, kr_cuentas_corriente l_krctct 
                WHERE rp.id_cuenta_corriente = l_krctct.id_cuenta_corriente 
                  AND $where 
                ORDER BY lov_descripcion ASC;";
        $datos = toba::db()->consultar($sql);

        return $datos;
    }

    static public function get_lov_tipos_recibo_x_id ($cod_tipo_recibo){

        $sql = "SELECT ADTIREPA.COD_TIPO_RECIBO || ' - ' || ADTIREPA.DESCRIPCION LOV_DESCRIPCION
                    FROM AD_TIPOS_RECIBO_PAGO ADTIREPA
                    WHERE adtirepa.COD_tipo_RECIBO = '".$cod_tipo_recibo."'";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['lov_descripcion'];
    }

    static public function get_lov_tipos_recibos_x_nombre ($nombre, $filtro = array()){
        if (isset($nombre)) {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('ADTIREPA.cod_tipo_recibo', $nombre);
            $trans_des = ctr_construir_sentencias::construir_translate_ilike('ADTIREPA.descripcion', $nombre);
            $where = "($trans_codigo OR $trans_des)";
        } else {
            $where = '1=1';
        }

        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ADTIREPA', '1=1');
        $sql_sel = "SELECT  ADTIREPA.COD_TIPO_RECIBO COD_TIPO_RECIBO,
                            ADTIREPA.DESCRIPCION DESCRIPCION,
                            ADTIREPA.TIPO_CUENTA_CORRIENTE TIPO_CUENTA_CORRIENTE,
                            ADTIREPA.PRESUPUESTARIO PRESUPUESTARIO,
                            ADTIREPA.EJERCICIO_ANTERIOR EJERCICIO_ANTERIOR,
                            ADTIREPA.TIPO_APLICACION TIPO_APLICACION,
                            ADTIREPA.COD_TIPO_RECIBO || ' - ' || ADTIREPA.DESCRIPCION LOV_DESCRIPCION
                    FROM AD_TIPOS_RECIBO_PAGO ADTIREPA
                    WHERE $where
                    ORDER BY DESCRIPCION";
        $datos = toba::db()->consultar($sql_sel);
        return $datos;
    }

    static function get_tipos_recibos_pago($filtro = array()) {
        $where = " 1=1 ";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ADTIREPA', '1=1');
        $sql_sel = " SELECT adtirepa.cod_tipo_recibo cod_tipo_recibo,
                            adtirepa.descripcion descripcion,
                            adtirepa.automatico,
                            adtirepa.cod_tipo_transaccion,
                            adtirepa.cod_tipo_transaccion_ajusta,
                            decode(adtirepa.automatico,'S','Si','No') automatico_format,
                            decode(adtirepa.presupuestario,'S','Si','No') presupuestario_format,
                            decode(adtirepa.ejercicio_anterior,'S','Si','No') ajercicio_anterior_format,
                            (select cod_tipo_transaccion ||' - '|| descripcion from KR_TIPOS_TRANSACCION where cod_tipo_transaccion = adtirepa.COD_TIPO_TRANSACCION ) tipo_transaccion_format,
                            (select cod_tipo_transaccion ||' - '|| descripcion from KR_TIPOS_TRANSACCION where cod_tipo_transaccion = adtirepa.COD_TIPO_TRANSACCION_AJUSTA ) tipo_transaccion_aju_format,
                                   (SELECT rv_meaning
                              FROM cg_ref_codes
                             WHERE rv_domain = 'KR_TIPO_CUENTA_CORRIENTE'
                               AND rv_low_value = adtirepa.tipo_cuenta_corriente) tipo_cuenta_corriente_format,
                            adtirepa.tipo_cuenta_corriente tipo_cuenta_corriente,
                            adtirepa.presupuestario presupuestario,
                            adtirepa.ejercicio_anterior ejercicio_anterior,
                            adtirepa.tipo_aplicacion tipo_aplicacion,
                            adtirepa.cod_tipo_recibo,
                            ADTIREPA.COD_TIPO_RECIBO || ' - ' || ADTIREPA.DESCRIPCION LOV_DESCRIPCION
                    FROM AD_TIPOS_RECIBO_PAGO ADTIREPA
                    WHERE $where
                    ORDER BY DESCRIPCION";
        $datos = toba::db()->consultar($sql_sel);
        return $datos;
    }

    public static function get_importes_encabezado_recibo_pago($id_recibo_pago) {
        if (isset($id_recibo_pago)) {
            $sql_sel = "SELECT  arp.importe,
                                arp.importe_retenciones_orden,
                                arp.importe_retenciones_pago
                    FROM ad_recibos_pago arp
                    WHERE arp.id_recibo_pago = " . quote($id_recibo_pago) . ";";
            $datos = toba::db()->consultar_fila($sql_sel);
            return $datos;
        } else {
            return array();
        }
    }

    static function get_datos_tipo_recibo_pago_x_tipo_recibo_pago($cod_tipo_recibo_pago) {
        if (isset($cod_tipo_recibo_pago)) {
            $sql_sel = "SELECT  ADTIREPA.*
                        FROM AD_TIPOS_RECIBO_PAGO ADTIREPA
                        WHERE ADTIREPA.cod_tipo_recibo = " . quote($cod_tipo_recibo_pago) . ";";
            $datos = toba::db()->consultar_fila($sql_sel);
            return $datos;
        } else {
            return array();
        }
    }

    static public function get_datos_extras_recibo_pago_x_id($id_recibo_pago) {
        if (isset($id_recibo_pago)) {
            $sql = "SELECT  arp.anulado,
                            arp.aprobado,
                            arp.automatico,
                            arp.fecha_anulacion_recibo
                        FROM AD_RECIBOS_PAGO arp
                        WHERE arp.id_recibo_pago = " . $id_recibo_pago;

            $datos = toba::db()->consultar_fila($sql);

            return $datos;
        } else {
            return array();
        }
    }

    
    static public function aprobar_recibo_pago($id_recibo_pago, $con_transaccion = true)
    {
        $sql = "BEGIN 
                    :resultado := PKG_KR_TRANS_TESORERIA.CONFIRMAR_RECIBO_PAGO(:ID_RECIBO_PAGO); 
                END;";
        $parametros = [
                        [ 'nombre' => 'resultado',
                          'tipo_dato' => PDO::PARAM_STR,
                          'longitud' => 4000,
                          'valor' => ''
                        ],
                        [ 'nombre' => 'ID_RECIBO_PAGO',
                          'tipo_dato' => PDO::PARAM_INT,
                          'longitud' => 32,
                          'valor' => $id_recibo_pago
                        ],
                    ];
        ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
    }
    
    static public function controlar_recursos_especificos ($id_recibo_pago, $con_transaccion = true)
    {
        $sql = "BEGIN 
                    :resultado := PKG_RECIBOS_PAGO.CONTROLAR_RECURSOS_ESPECIFICOS(:ID_RECIBO_PAGO); 
                END;";
        $parametros = 
                [
                    ['nombre' => 'resultado',
                     'tipo_dato' => PDO::PARAM_STR,
                     'longitud' => 4000,
                     'valor' => ''
                    ],
                    ['nombre' => 'ID_RECIBO_PAGO',
                     'tipo_dato' => PDO::PARAM_INT,
                     'longitud' => 32,
                     'valor' => $id_recibo_pago
                    ],
                ];
        ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);              
    }

    static public function aprobar ($id_recibo_pago, $acepta_control = 'N')
    {
        $mensaje = "OK";

        toba::db()->abrir_transaccion();
        ctr_procedimientos::ejecutar_transaccion_compuesta(null, function () use ($id_recibo_pago,$acepta_control, &$mensaje){

            // Aprobar comprobante
            dao_recibos_pago::aprobar_recibo_pago($id_recibo_pago);
            try{
                dao_recibos_pago::controlar_recursos_especificos($id_recibo_pago);
                toba::db()->cerrar_transaccion();
            }catch (toba_error $e) {
                if ($acepta_control == 'N'){
                    toba::db()->abortar_transaccion();
                    $mensaje = $e->get_mensaje();
                }
                if ($acepta_control == 'S'){
                    toba::db()->cerrar_transaccion();
                }
            }
        });
        return $mensaje;
    }

    static public function anular_recibo_pago($id_recibo_pago, $fecha_anulacion, $con_transaccion=true) {
        if (isset($id_recibo_pago) && isset($fecha_anulacion)) {
            $sql = "BEGIN :resultado := PKG_KR_TRANS_TESORERIA.ANULAR_RECIBO_PAGO(:id_recibo_pago, to_date(substr(:fecha_anulacion,1,10),'yyyy-mm-dd')); END;";

            $parametros = array(array('nombre' => 'id_recibo_pago',
                    'tipo_dato' => PDO::PARAM_INT,
                    'longitud' => 32,
                    'valor' => $id_recibo_pago),
                array('nombre' => 'fecha_anulacion',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $fecha_anulacion),
                array('nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''),
            );
            $resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, 'El recibo de pago se anuló exitosamente.', 'Error en la anulación del recibo de pago.', $con_transaccion);
            return $resultado[2]['valor'];
        }
    }

    static public function get_datos_pago_recibo_pago($id_pago) {
        if (isset($id_pago)) {
            $sql = "SELECT  ap.*,
                            amp.*
                        FROM AD_PAGOS ap
                        JOIN AD_MEDIOS_PAGO amp ON (ap.cod_medio_pago = amp.cod_medio_pago)
                        WHERE ap.id_pago = " . $id_pago;

            $datos = toba::db()->consultar_fila($sql);

            return $datos;
        } else {
            return array();
        }
    }

    static public function get_pagos_recibo_pago_x_id($id_recibo_pago) {
        if (isset($id_recibo_pago)) {
            $sql = "SELECT  ap.*,
                            amp.COD_MEDIO_PAGO || ' - ' || amp.DESCRIPCION medio_pago,
                            km.DESCRIPCION moneda,
                            CASE
                                WHEN ab.id_beneficiario IS NOT NULL THEN ab.id_beneficiario || ' - ' || ab.nombre
                                ELSE ''
                            END as beneficiario,
                            CASE
                                WHEN kcb.id_cuenta_banco IS NOT NULL THEN kcb.id_cuenta_banco || ' - ' || kcb.descripcion
                                ELSE ''
                            END as cuenta_banco,
                            CASE
                                WHEN kscb.id_sub_cuenta_banco IS NOT NULL THEN kscb.id_sub_cuenta_banco || ' - ' || kscb.descripcion
                                ELSE ''
                            END as sub_cuenta_banco,
                            vacp.nro_comprobante,
                            amp.TIPO_COMPROBANTE_PAGO
                        FROM AD_PAGOS ap
                        JOIN AD_MEDIOS_PAGO amp ON (ap.cod_medio_pago = amp.cod_medio_pago)
                        JOIN KR_MONEDAS km ON (ap.cod_moneda = km.cod_moneda)
                        LEFT OUTER JOIN ad_beneficiarios ab ON (ab.id_beneficiario = ap.id_beneficiario)
                        LEFT OUTER JOIN kr_cuentas_banco kcb ON (kcb.id_cuenta_banco = ap.id_cuenta_banco)
                        LEFT OUTER JOIN kr_sub_cuentas_banco kscb ON (kscb.id_sub_cuenta_banco = ap.id_sub_cuenta_banco)
                        LEFT OUTER JOIN v_ad_comprobantes_pago vacp ON (vacp.id_comprobante_pago = ap.id_comprobante_pago)
                        WHERE ap.id_recibo_pago = " . quote($id_recibo_pago) . "
                        ORDER BY ap.id_pago ASC;";

            $datos = toba::db()->consultar($sql);

            return $datos;
        } else {
            return array();
        }
    }

    static public function get_aplicaciones_recibo_pago_x_id($id_recibo_pago) {
        if (isset($id_recibo_pago)) {
            $sql = "SELECT  aap.*, 
                            CASE
                                WHEN aap.tipo_aplicacion_pago = 'CGA' THEN aap.id_comprobante_gasto
                                WHEN aap.tipo_aplicacion_pago = 'CRE' THEN aap.id_comprobante_recurso
                                WHEN aap.tipo_aplicacion_pago = 'RCO' THEN aap.id_recibo_cobro
                            END as id_comprobante,
                            CASE
                                WHEN aap.tipo_aplicacion_pago = 'CGA' THEN (SELECT nro_comprobante FROM ad_comprobantes_gasto WHERE id_comprobante_gasto = aap.id_comprobante_gasto)
                                WHEN aap.tipo_aplicacion_pago = 'CRE' THEN (SELECT nro_comprobante FROM ad_comprobantes_recurso WHERE id_comprobante_recurso = aap.id_comprobante_recurso)
                                WHEN aap.tipo_aplicacion_pago = 'RCO' THEN (SELECT nro_recibo FROM ad_recibos_cobro WHERE id_recibo_cobro = aap.id_recibo_cobro)
                            END as nro_comprobante,
                            to_char(aap.fecha_aplicacion,'dd/mm/rrrr') as fecha_aplicacion_format,
                            trim(to_char(aap.importe, '$999,999,999,990.00')) importe_format,
                            CASE
                                WHEN aap.aplicado = 'S' THEN 'Si'
                                ELSE 'No'
                            END aplicado_format
                   FROM AD_APLICACIONES_PAGO aap
                   WHERE aap.id_recibo_pago = $id_recibo_pago;";
            $datos = toba::db()->consultar($sql);
            foreach ($datos as $clave => $dato) {
                $datos[$clave]['tipo_aplicacion_pago_desc'] = self::get_descripcion_tipo_aplicacione_pago($dato['tipo_aplicacion_pago']);
                $datos[$clave]['origen_aplicacion_desc'] = self::get_descripcion_origen_aplicacion($dato['origen_aplicacion']);
            }
            return $datos;
        } else {
            return array();
        }
    }

    static public function get_tipos_aplicaciones_pago() {
        $datos = array(array('tipo_aplicacion_pago' => 'CGA', 'descripcion' => 'Comprobante de gastos'),
            array('tipo_aplicacion_pago' => 'CRE', 'descripcion' => 'Comprobante de recursos'),
            array('tipo_aplicacion_pago' => 'RCO', 'descripcion' => 'Recibo de cobro')
        );
        return $datos;
    }
    
    static public function get_tipo_comprobante_pago ($cod_medio_pago){
        $sql = "SELECT tipo_comprobante
                  FROM ad_medios_pago 
                 WHERE cod_medio_pago = $cod_medio_pago";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['tipo_comprobante'];
    }

    static public function get_descripcion_tipo_aplicacione_pago($tipo_aplicacion_pago) {
        $datos = self::get_tipos_aplicaciones_pago();
        foreach ($datos as $clase) {
            if ($clase['tipo_aplicacion_pago'] == $tipo_aplicacion_pago) {
                return $clase['descripcion'];
            }
        }
        return '';
    }

    static public function get_origenes_aplicaciones_pago() {
        $datos = array(array('origen_aplicacion' => 'RAN', 'descripcion' => 'Rendición de anticipo'),
            array('origen_aplicacion' => 'RCC', 'descripcion' => 'Rendición de caja chica'),
            array('origen_aplicacion' => 'REN', 'descripcion' => 'Rendición'),
            array('origen_aplicacion' => 'ORD', 'descripcion' => 'Orden de pago'),
            array('origen_aplicacion' => 'PAG', 'descripcion' => 'Recibo de pago'),
        );
        return $datos;
    }

    static public function get_descripcion_origen_aplicacion($origen_aplicacion) {
        $datos = self::get_origenes_aplicaciones_pago();
        foreach ($datos as $clase) {
            if ($clase['origen_aplicacion'] == $origen_aplicacion) {
                return $clase['descripcion'];
            }
        }
        return '';
    }

    static public function get_datos_retencion_x_id_pago($id_pago) {
        if (isset($id_pago)) {
            $sql = "Select R.*
                    From Ad_Retenciones_Pago Rp,
                         Ad_Retenciones R,
                         ad_pagos ap
                    Where Rp.Id_Retencion = R.Id_Retencion 
                    And Rp.Id_Retencion_Pago = ap.id_retencion_pago
                    AND ap.id_pago = " . quote($id_pago) . "
                    ORDER BY ap.id_pago ASC;";

            $datos = toba::db()->consultar_fila($sql);
            return $datos;
        } else {
            return array();
        }
    }
    static public function get_pago ($id_pago) {
        if (isset($id_pago)) {
            $sql = "SELECT id_comprobante_pago, id_retencion_pago
                    FROM ad_pagos
                    where ID_PAGO = $id_pago;";
            $datos = toba::db()->consultar_fila($sql);
            return $datos;
        } else {
            return array();
        }
    }   
    
    static public function get_liquido_ff_recibo_pago_x_id($id_recibo_pago) {
        if (isset($id_recibo_pago)) {
            $sql = "SELECT LFF.ID_RECIBO_PAGO ID_RECIBO_PAGO,
                            LFF.COD_RECURSO COD_RECURSO,
                            LFF.COD_FUENTE_FINANCIERA COD_FUENTE_FINANCIERA,
                            LFF.DESCRIPCION_FF DESCRIPCION_FF,
                            LFF.COD_RECURSO_MAS COD_RECURSO_MAS,
                            L_PRRE.DESCRIPCION L_PRRE_DESCRIPCION,
                            LFF.IMPORTE IMPORTE,
                            lff.cod_fuente_financiera ||' - '|| lff.descripcion_ff as fuente_financiamiento,
                            CASE
                                WHEN lff.cod_recurso IS NOT NULL THEN pkg_pr_recursos.mascara_aplicar(L_PRRE.cod_recurso) ||' - '|| pkg_pr_recursos.cargar_descripcion(L_PRRE.cod_recurso)
                                ELSE ''
                            END as recurso
                    FROM V_LIQUIDO_FF LFF 
                    LEFT OUTER JOIN PR_RECURSOS L_PRRE ON LFF.COD_RECURSO = L_PRRE.COD_RECURSO 
                    WHERE  LFF.id_recibo_pago = " . quote($id_recibo_pago) . ";";
            $datos = toba::db()->consultar($sql);
            return $datos;
        } else {
            return array();
        }
    }

    static public function eliminar_pago_recibo_pago($id_pago, $con_transaccion = true) {
        if (isset($id_pago) && !empty($id_pago)) {
            $mensaje_error = 'Error eliminando el pago.';
            try {
                if ($con_transaccion) {
                    toba::db()->abrir_transaccion();
                }
                $datos_pago = dao_recibos_pago::get_datos_pago_recibo_pago($id_pago);
                if (isset($datos_pago) && !empty($datos_pago)) {
                    if (isset($datos_pago['id_comprobante_pago'])) {
                        dao_cheques_propios::habilitar_cheque($datos_pago['id_comprobante_pago'], !$con_transaccion);
                    }

                    // Elimino el pago
                    $sql_del = "DELETE FROM ad_pagos
                                WHERE id_pago = " . quote($id_pago) . ";";
                    toba::db()->ejecutar($sql_del);
                    
                    // obtengo las condiciones del comprobante
                    $condiciones_comprobante = dao_medios_pago::get_condiciones_comprobante_x_medio_pago($datos_pago['cod_medio_pago'], 'PAG');
                    // Si esta seteado el comprobante de pago entonces lo elimino (elimina comprobante de pago y comprobante)
                    if ($condiciones_comprobante['crea_comprobante'] == 'S' && isset($datos_pago['id_comprobante_pago']) && !empty($datos_pago['id_comprobante_pago'])) { // si se creo el comprobante
                         // eliminacion del comprobante de pago
                        $resultado = dao_comprobantes_pago::eliminar_comprobante_pago($datos_pago['id_comprobante_pago'], false);
                        // Si retorna con error arroja una axcepcion y corta el proceso
                        if (strcasecmp($resultado, 'S') <> 0){
                            throw new toba_error('Error en la eliminacion del comprobante de pago. '. $resultado);
                        }
                    }
                }
                if ($con_transaccion) {
                    toba::db()->cerrar_transaccion();
                }
            } catch (toba_error_db $e_db) {
                toba::notificacion()->error($mensaje_error . ' ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
                toba::logger()->error($mensaje_error . ' ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
                if ($con_transaccion) {
                    toba::db()->abortar_transaccion();
                }
            } catch (toba_error $e) {
                toba::notificacion()->error($mensaje_error . ' ' . $e->get_mensaje());
                toba::logger()->error($mensaje_error . ' ' . $e->get_mensaje());
                if ($con_transaccion) {
                    toba::db()->abortar_transaccion();
                }
            }
        }
    }
    
    static public function get_reporte_retencion_pago ($id_retencion_pago){
        if (!is_null($id_retencion_pago)){
            $sql = "Select R.Reporte reporte
                    From Ad_Retenciones_Pago Rp, Ad_Retenciones R          
                    Where Rp.Id_Retencion = R.Id_Retencion 
                          And Rp.Id_Retencion_Pago = ".quote($id_retencion_pago).";";
            $datos = toba::db()->consultar_fila($sql);
            return $datos['reporte'];
        }else return '';
    }

    static public function set_fecha_retencion($id_recibo_pago,$fecha_retencion){
        
        toba::db()->abrir_transaccion();

            $sql = ("UPDATE ad_retenciones_efectuadas
            set fecha_retencion = TO_DATE('".$fecha_retencion."','yyyy-MM-dd') 
            where id_comprobante_pago IN (SELECT  ap.id_comprobante_pago
                        FROM AD_PAGOS ap
                        JOIN AD_MEDIOS_PAGO amp ON (ap.cod_medio_pago = amp.cod_medio_pago)
                        JOIN KR_MONEDAS km ON (ap.cod_moneda = km.cod_moneda)
                        LEFT OUTER JOIN ad_beneficiarios ab ON (ab.id_beneficiario = ap.id_beneficiario)
                        LEFT OUTER JOIN kr_cuentas_banco kcb ON (kcb.id_cuenta_banco = ap.id_cuenta_banco)
                        LEFT OUTER JOIN kr_sub_cuentas_banco kscb ON (kscb.id_sub_cuenta_banco = ap.id_sub_cuenta_banco)
                        LEFT OUTER JOIN v_ad_comprobantes_pago vacp ON (vacp.id_comprobante_pago = ap.id_comprobante_pago)
                        WHERE ap.id_recibo_pago = $id_recibo_pago
                        )");


            $rta = dao_varios::ejecutar_sql($sql, false);

            if ($rta !== "OK"){
            toba::db()->abortar_transaccion();
            toba::notificacion()->error($rta);
            return;
        }

        toba::db()->cerrar_transaccion();
    }
}

?>
