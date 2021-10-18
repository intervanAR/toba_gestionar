<?php

/**
 * Description of dao_medios_pago
 *
 * @author hmargiotta
 */
class dao_medios_pago {

    static public function get_medios_pago($filtro = array()) {
        $where = "1=1";

        if (isset($filtro['cobro'])) {
            $where.= " AND pkg_ad_comprobantes_pagos.medio_pago_valido(cod_medio_pago,'COB') = 'S'";
            unset($filtro['cobro']);
        }

        if (isset($filtro['pago'])) {
            $where.= " AND (pkg_ad_comprobantes_pagos.medio_pago_valido(cod_medio_pago,'PAG') = 'S' OR tipo_comprobante_pago = 'REE')";
            unset($filtro['pago']);
        }

        if (isset($filtro['transferencias_fondos'])) {
            $where.= " AND pkg_ad_comprobantes_pagos.medio_pago_valido(cod_medio_pago,'TRA') = 'S'";
            unset($filtro['transferencias_fondos']);
        }

        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'mp', '1=1');

        $sql = "SELECT   mp.*, mp.cod_medio_pago || ' - ' || mp.descripcion lov_descripcion,
                     DECODE (mp.activo, 'S', 'Si', 'No') activo_format,
                     CASE
                        WHEN mp.entrega_dif = 'S'
                           THEN 'Si'
                        WHEN mp.entrega_dif = 'N'
                           THEN 'No'
                        ELSE ''
                     END entrega_dif_format,
                     CASE
                        WHEN mp.vencimiento_dif = 'S'
                           THEN 'Si'
                        WHEN mp.vencimiento_dif = 'N'
                           THEN 'No'
                        ELSE ''
                     END vencimiento_dif_format,
                     CASE
                        WHEN mp.cod_auxiliar IS NOT NULL
                           THEN    kraux.cod_auxiliar
                                || ' - '
                                || kraux.descripcion
                        ELSE ''
                     END auxiliar_format
                FROM ad_medios_pago mp LEFT JOIN kr_auxiliares_ext kraux
                     ON mp.cod_auxiliar = kraux.cod_auxiliar
                WHERE  $where
				ORDER BY mp.cod_medio_pago desc";

        $datos = toba::db()->consultar($sql);

        return $datos;
    }

    static public function get_medio_pago_x_cod($cod_medio_pago) {
        if (isset($cod_medio_pago)) {
            $sql = "SELECT mp.*, mp.descripcion as lov_descripcion
                    FROM AD_MEDIOS_PAGO mp
                    WHERE mp.cod_medio_pago = " . quote($cod_medio_pago) . ";";
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

    static public function get_lov_medios_pago_x_nombre($nombre, $filtro = array()) {
        if (isset($nombre)) {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('cod_medio_pago', $nombre);
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
            $where = "($trans_codigo OR $trans_nro)";
        } else {
            $where = '1=1';
        }

        if (isset($filtro['cobro'])) {
            $where.= " AND pkg_ad_comprobantes_pagos.medio_pago_valido(cod_medio_pago,'COB') = 'S'";

            unset($filtro['cobro']);
        }

        if (isset($filtro['transferencia_fondos'])) {
            $where .= " AND (EXISTS (
                        SELECT 1
                          FROM ad_medios_pago_operaciones mepaop
                         WHERE mepaop.tipo_operacion = 'TRA'
                           AND mepaop.crea_automaticamente = 'N'
                           AND mp.cod_medio_pago = mepaop.cod_medio_pago)
         )";
            unset($filtro['transferencia_fondos']);
        }
        if (isset($filtro['existe_medio_pago_operaciones'])) {
            $where .= " AND (EXISTS (SELECT 1 
                            FROM AD_MEDIOS_PAGO_OPERACIONES AMPO
                           WHERE AMPO.COD_MEDIO_PAGO = mp.COD_MEDIO_PAGO
                             AND AMPO.TIPO_OPERACION = 'PAG'
                             AND AMPO.CREA_COMPROBANTE = 'S')
         )";
            unset($filtro['existe_medio_pago_operaciones']);
        }

        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'mp', '1=1');

        $sql = "SELECT  mp.*, mp.descripcion as lov_descripcion
				FROM AD_MEDIOS_PAGO mp
                WHERE $where
                ORDER BY lov_descripcion ASC;";

        $datos = toba::db()->consultar($sql);

        return $datos;
    }

    static public function get_condiciones_comprobante_x_medio_pago($cod_medio_pago, $origen) {
        if (isset($cod_medio_pago) && isset($origen)) {
            $sql = "BEGIN pkg_ad_comprobantes_pagos.condiciones_comprobante(:cod_medio_pago, :origen, :crea_comprobante, :crea_automaticamente, :indicar_cuenta_banco); END;";
            $parametros = array(array('nombre' => 'cod_medio_pago',
                    'tipo_dato' => PDO::PARAM_INT,
                    'longitud' => 10,
                    'valor' => $cod_medio_pago),
                array('nombre' => 'origen',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 10,
                    'valor' => $origen),
                array('nombre' => 'crea_comprobante',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 3,
                    'valor' => ''),
                array('nombre' => 'crea_automaticamente',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 3,
                    'valor' => ''),
                array('nombre' => 'indicar_cuenta_banco',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 3,
                    'valor' => ''),
                array('nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 3,
                    'valor' => 'OK')
            );
            $resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al buscar las condiciones del comprobante.', false);
            return array('crea_comprobante' => $resultado[2]['valor'],
                'crea_automaticamente' => $resultado[3]['valor'],
                'indicar_cuenta_banco' => $resultado[4]['valor']);
        }
    }

    static public function get_datos_medio_pago_x_cod($cod_medio_pago) {
        if (isset($cod_medio_pago)) {
            $sql = "SELECT mp.*, mp.descripcion as lov_descripcion
                    FROM AD_MEDIOS_PAGO mp
                    WHERE mp.cod_medio_pago = " . quote($cod_medio_pago) . ";";
            $datos = toba::db()->consultar_fila($sql);
            return $datos;
        } else {
            return array();
        }
    }


    static public function get_lov_matriz_comp_pago_x_id ($id_matriz_comp_pago){
        $sql = "SELECT admacopa.ID_MATRIZ_COMP_PAGO ||' - '|| l_adescopa.DESCRIPCION ||' - '||l_adescopa2.DESCRIPCION lov_descripcion        
            FROM ad_matriz_comp_pago admacopa,
                 ad_estados_comp_pago l_adescopa,
                 ad_estados_comp_pago l_adescopa2
           WHERE admacopa.estado = l_adescopa.estado
             AND admacopa.estado_hasta = l_adescopa2.estado and admacopa.id_matriz_comp_pago = ".$id_matriz_comp_pago."
        order by lov_descripcion";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['lov_descripcion'];
    }
    static public function get_lov_matriz_comp_pago_x_nombre ($nombre, $filtro = [])
    {

        if (isset($nombre)) 
        {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('id_matriz_comp_pago', $nombre);
            $estado = ctr_construir_sentencias::construir_translate_ilike('admacopa.estado', $nombre);
             $estado_hasta = ctr_construir_sentencias::construir_translate_ilike('admacopa.estado_hasta', $nombre);
            $where = "($trans_codigo OR $estado OR $estado_hasta)";
        } else 
        {
            $where = '1=1';
        }

        if (isset($filtro['para_medios_de_pago'])){
            $where .=" AND (admacopa.tipo_operacion = '".$filtro['tipo_operacion']."'
                          AND (('".$filtro['tipo_operacion']."' = 'PAG'
                                   AND admacopa.tipo_cuenta_pred_o =
                                                              '".$filtro['tipo_cuenta_pred']."'
                                  )
                               OR ('".$filtro['tipo_operacion']."' = 'COB'
                                   AND admacopa.tipo_cuenta_pred_d =
                                                              '".$filtro['tipo_cuenta_pred']."'
                                  )
                              )
                         )";

            unset($filtro['para_medios_de_pago']);
            unset($filtro['tipo_cuenta_pred']);
            unset($filtro['tipo_operacion']);
        }

        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'admacopa', '1=1');

        $sql = "SELECT admacopa.*, admacopa.ID_MATRIZ_COMP_PAGO ||' - '|| l_adescopa.DESCRIPCION ||' - '||l_adescopa2.DESCRIPCION lov_descripcion        
            FROM ad_matriz_comp_pago admacopa,
                 ad_estados_comp_pago l_adescopa,
                 ad_estados_comp_pago l_adescopa2
           WHERE admacopa.estado = l_adescopa.estado
             AND admacopa.estado_hasta = l_adescopa2.estado and $where
        order by lov_descripcion";
        return toba::db()->consultar($sql);
    }

    static public function get_lov_tipo_comp_banco_x_codigo ($cod_tipo_comprobante){

        $sql = "select adtcb.*
                  from ad_tipos_comprobante_banco adtcb
                  where adtcb.cod_tipo_comprobante = ".$cod_tipo_comprobante."
                order by lov_descripcion";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['lov_descripcion'];
    }

    static public function get_lov_tipo_comp_banco_x_nombre ($nombre, $filtro = [])
    {

        if (isset($nombre)) 
        {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('adtcb.cod_tipo_comprobante', $nombre);
            $desc = ctr_construir_sentencias::construir_translate_ilike('adtcb.descripcion', $nombre);
             
            $where = "($trans_codigo OR $desc)";
        } else 
        {
            $where = '1=1';
        }

        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'admacopa', '1=1');

        $sql = "SELECT admacopa.*, admacopa.ID_MATRIZ_COMP_PAGO ||' - '|| l_adescopa.DESCRIPCION ||' - '||l_adescopa2.DESCRIPCION lov_descripcion        
            FROM ad_matriz_comp_pago admacopa,
                 ad_estados_comp_pago l_adescopa,
                 ad_estados_comp_pago l_adescopa2
           WHERE admacopa.estado = l_adescopa.estado
             AND admacopa.estado_hasta = l_adescopa2.estado and $where
        order by lov_descripcion";
        return toba::db()->consultar($sql);
    }


}

?>
