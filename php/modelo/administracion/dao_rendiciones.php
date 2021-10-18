<?php

/**
 * Description of dao_rendiciones
 *
 * @author ddiluca
 */
class dao_rendiciones {
    //put your code here
    public function get_rendiciones_anticipos($filtro=array()){

        if (!empty($filtro)){
            if (isset($filtro['fecha_imputacion'])) {
                $where .= "AND rant.fecha_imputacion = to_date(".quote($filtro['fecha_imputacion']).", 'YYYY-MM-DD') ";
                unset($filtro['fecha_imputacion']);
            }
            if (isset($filtro['fecha_anulacion'])) {
                $where .= "AND rant.fecha_anulacion = to_date(".quote($filtro['fecha_anulacion']).", 'YYYY-MM-DD') ";
                unset($filtro['fecha_anulacion']);
            }
            if (isset($filtro['observaciones'])) {
                $where .= "AND " . ctr_construir_sentencias::construir_translate_ilike('rant.observaciones', $filtro['observaciones']);
                unset($filtro['observaciones']);
            }
             if (isset($filtro['nro_rendicion'])) {
                $where .= "AND " . ctr_construir_sentencias::construir_translate_ilike('rant.nro_rendicion', $filtro['nro_rendicion']);
                unset($filtro['nro_rendicion']);
            }
            $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'rant', '1=1');
        }
	    $sql = "SELECT  rant.*,
                            ua.descripcion unidad_administracion,
                            ue.descripcion unidad_ejecutora,
                            to_char(rant.fecha_rendicion, 'dd/mm/yyyy') fecha_comprobante_format,
                            to_char(rant.fecha_aprueba, 'dd/mm/yyyy') fecha_aprueba_format,
                            to_char(rant.fecha_anulacion, 'dd/mm/yyyy') fecha_anula_format,
                            to_char(rant.fecha_carga, 'dd/mm/yyyy') fecha_carga_format,
                            to_char(rant.fecha_imputacion, 'dd/mm/yyyy') fecha_imputacion_format,
                            cant.nro_comprobante nro_comprobante_anticipo
                     FROM   ad_rendiciones_anticipo rant
                            LEFT JOIN KR_UNIDADES_ADMINISTRACION ua ON rant.cod_unidad_administracion = ua.cod_unidad_administracion
                            LEFT JOIN KR_UNIDADES_EJECUTORAS ue ON rant.cod_unidad_ejecutora = ue.cod_unidad_ejecutora
                            LEFT JOIN AD_COMPROBANTES_ANTICIPO cant ON rant.ID_COMPROBANTE_ANTICIPO = cant.id_comprobante_anticipo;";
            $datos = toba::db()->consultar($sql);
            return $datos;
    }

    static public function get_tipos_rendicion($filtro = []){
        $where = "1=1";
        $where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro,'adtr', '1=1');
        $sql = "SELECT adtr.*, DECODE (adtr.fact_pagas, 'S', 'Si', 'No') fact_pagas_format,
                       DECODE (adtr.fact_impagas, 'S', 'Si', 'No') fact_impagas_format,
                       DECODE (adtr.fact_anticipos, 'S', 'Si', 'No') fact_anticipos_format,
                       adtr.tipo_rendicion||' - '||adtr.descripcion lov_descripcion
                  FROM ad_tipo_rendicion adtr
                 WHERE $where
                 order by adtr.tipo_rendicion";
        return toba::db()->consultar($sql);
    }

    public static function get_rendiciones($filtro = [], $orden = [])
    {
        $desde = null;
        $hasta = null;
        if(isset($filtro['numrow_desde'])){
            $desde = $filtro['numrow_desde'];
            $hasta = $filtro['numrow_hasta'];
            unset($filtro['numrow_desde']);
            unset($filtro['numrow_hasta']);
        }
        $where = self::armar_where($filtro);

        $sql = "
            SELECT are.*
                , (select ua.cod_unidad_administracion||' - '||ua.descripcion
                    from KR_UNIDADES_ADMINISTRACION ua
                    where are.cod_unidad_administracion = ua.cod_unidad_administracion) unidad_administracion_desc
                , (select uad.cod_unidad_administracion||' - '||uad.descripcion
                    from KR_UNIDADES_ADMINISTRACION uad
                    where are.cod_unidad_administracion_des = uad.cod_unidad_administracion)
                    unidad_administracion_des_desc
                , (select ue.cod_unidad_ejecutora||' - '||ue.descripcion
                    from KR_UNIDADES_EJECUTORAS ue
                    where are.cod_unidad_ejecutora = ue.cod_unidad_ejecutora)
                    unidad_ejecutora_desc

            FROM AD_RENDICIONES are
            WHERE $where
        ";
        $sql = dao_varios::paginador($sql, null, $desde, $hasta, null, $orden);
        toba::logger()->debug('SQL AD_RENDICIONES '. $sql);
        $datos = toba::db()->consultar($sql);
        return $datos;
    }

    public static function armar_where($filtro = array())
    {
        $sql1 = "SELECT PKG_KR_USUARIOS.in_ua_tiene_acceso(upper('".toba::usuario()->get_id()."')) unidades_ad,
                        PKG_KR_USUARIOS.USUARIO_TIENE_UES(upper('".toba::usuario()->get_id()."')) usuario_ues,
                        PKG_KR_USUARIOS.in_ue_tiene_acceso(upper('".toba::usuario()->get_id()."')) unidades_eje_us
                   FROM DUAL";
        $res = toba::db()->consultar_fila($sql1);
        $where = "((are.COD_UNIDAD_ADMINISTRACION IN ".$res['unidades_ad'].") AND (('".$res['usuario_ues']."' ='N') OR (are.COD_UNIDAD_EJECUTORA IN ".$res['unidades_eje_us'].")))";

        if (isset($filtro['fecha_rendicion'])) {
            $where .= " AND are.fecha_rendicion = to_date(".quote($filtro['fecha_rendicion']).", 'YYYY-MM-DD') ";
            unset($filtro['fecha_rendicion']);
        }
        if (isset($filtro['fecha_anulacion'])) {
            $where .= " AND are.fecha_anulacion = to_date(".quote($filtro['fecha_anulacion']).", 'YYYY-MM-DD') ";
            unset($filtro['fecha_anulacion']);
        }
        if (isset($filtro['nro_rendicion'])) {
            $where .= " AND " . ctr_construir_sentencias::construir_translate_ilike('are.nro_rendicion', $filtro['nro_rendicion']);
            unset($filtro['nro_rendicion']);
        }
        if (isset($filtro['observaciones'])) {
            $where .= " AND " . ctr_construir_sentencias::construir_translate_ilike('are.observaciones', $filtro['observaciones']);
            unset($filtro['observaciones']);
        }
        if (!empty($filtro)) {
            $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'are', '1=1');
        }

        return $where;
    }

    public static function get_cantidad($filtro = [])
    {
        $where = self::armar_where($filtro);
        $sql = "
            SELECT count(*) cantidad
            FROM AD_RENDICIONES are
            WHERE $where
        ";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['cantidad'];
    }

    public static function establecer_pendiente_rendicion($id_rendicion)
    {
        $id_rendicion = quote($id_rendicion);
        $mensaje_error = 'Error al establecer como Pendiente la Rendición.';

        $sql = "
            BEGIN
                :resultado := pkg_ad_rendiciones.establecer_pendiente_rendicion(
                    $id_rendicion
                );
            END;
        ";

        $parametros = [[
            'nombre' => 'resultado',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 400,
            'valor' => '',
        ]];

        ctr_procedimientos::ejecutar_procedimiento(
            $mensaje_error,
            $sql,
            $parametros
        );
    }

    public static function sacar_pendiente_rendicion($id_rendicion)
    {
        $id_rendicion = quote($id_rendicion);
        $mensaje_error = 'Error al establecer como No Pendiente la Rendición.';

        $sql = "
            BEGIN
                :resultado := pkg_ad_rendiciones.suprimir_pendiente_rendicion(
                    $id_rendicion
                );
            END;
        ";

        $parametros = [[
            'nombre' => 'resultado',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 400,
            'valor' => '',
        ]];

        ctr_procedimientos::ejecutar_procedimiento(
            $mensaje_error,
            $sql,
            $parametros
        );
    }

    public static function confirmar_rendicion($id_rendicion)
    {
        $id_rendicion = quote($id_rendicion);
        $mensaje_error = 'Error al Confirmar la Rendición.';

        $sql = "
            BEGIN
                :resultado := pkg_ad_rendiciones.confirmar_rendicion(
                    $id_rendicion
                );
            END;
        ";

        $parametros = [[
            'nombre' => 'resultado',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 32000,
            'valor' => '',
        ]];

        ctr_procedimientos::ejecutar_procedimiento(
            $mensaje_error,
            $sql,
            $parametros
        );
    }

    public static function anular_rendicion($id_rendicion, $fecha_anulacion)
    {
        $id_rendicion = quote($id_rendicion);
        $fecha_anulacion = quote($fecha_anulacion);
        $mensaje_error = 'Error al Anular la Rendición.';

        $sql = "
            BEGIN
                :resultado := pkg_ad_rendiciones.anular_rendicion(
                    $id_rendicion
                    , $fecha_anulacion
                );
            END;
        ";

        $parametros = [[
            'nombre' => 'resultado',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 400,
            'valor' => '',
        ]];

        $res = ctr_procedimientos::ejecutar_procedimiento(
            $mensaje_error,
            $sql,
            $parametros
        );

        return pos($res)['valor'];
    }

    public static function get_afi_fecha_anulacion(){
        $sql = "select PKG_GENERAL.VALOR_PARAMETRO_KR('AFI_FECHA_ANULACION_DEF') fecha from dual";
        $datos = toba::db()->consultar_fila($sql);
        if ($datos['fecha'] === 'FECHA_COMPROBANTE') {
            return true;
        }else{
            return false;
        }
    }

    public static function get_ad_facturas($filtro = [])
    {
        $datos = [];
        $where = " 1=1 ";

        if (isset($filtro['not_exists'])) {
            $where .= " AND not exists
            ( select 1 FROM ad_rend_fact rf, ad_rendiciones ren
               WHERE rf.id_rendicion = ren.id_rendicion
                 AND ren.anulada = 'N'
                 AND rf.id_factura = adfa.id_factura
            ) ";
            unset($filtro['not_exists']);
        }

        if (isset($filtro['cod_ue_null'])) {
            $cod_unidad_ejecutora = ($filtro['cod_unidad_ejecutora']) ? $filtro['cod_unidad_ejecutora'] : '';
            $where .= " AND (cod_unidad_ejecutora = '$cod_unidad_ejecutora' or '$cod_unidad_ejecutora' is null) ";
            unset($filtro['cod_ue_null']);
            unset($filtro['cod_unidad_ejecutora']);
        }

        if (!empty($filtro)) {
            $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'adfa', '1=1');
        }

        $sql = "
            SELECT adfa.*
            FROM ad_facturas adfa
            WHERE
                $where
        ";
        toba::logger()->debug('Facturas Admistracion ************* ' . $sql);
        $datos = toba::db()->consultar($sql);
        return $datos;
    }

    public static function get_lov_rendicion_x_id($id_rendicion)
    {
       if (isset($id_rendicion)) {
           $sql = "
                SELECT ren.*
                FROM AD_RENDICIONES ren
                WHERE ren.id_rendicion = ".quote($id_rendicion);
           $datos = toba::db()->consultar_fila($sql);
           return $datos;
        }
    }

    public static function get_lov_facturas_x_nombre($nombre, $filtro = [])
    {
        if (isset($nombre)) {
            $trans_id = ctr_construir_sentencias::construir_translate_ilike('ADFA.ID_FACTURA', $nombre);
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('ADFA.NRO_FACTURA', $nombre);
            $trans_fecha_emision = ctr_construir_sentencias::construir_translate_ilike('ADFA.FECHA_EMISION', $nombre);
            $trans_importe = ctr_construir_sentencias::construir_translate_ilike('ADFA.IMPORTE', $nombre);
            $where = "($trans_id OR $trans_nro OR $trans_fecha_emision OR $trans_importe)";
        } else {
            $where = '1=1';
        }

        if (isset($filtro['facturas_rendicion'])) {
            $where.= "
                AND not exists(
                    SELECT 1
                    FROM ad_rend_fact rf, ad_rendiciones ren
                    WHERE rf.id_rendicion = ren.id_rendicion
                        AND ren.anulada = 'N'
                        AND rf.id_factura = adfa.id_factura
            )";
            unset($filtro['facturas_rendicion']);
        }

        if (isset($filtro['cod_unidad_ejecutora'])){
            $where.= " AND (".$filtro['cod_unidad_ejecutora']." IS NULL
                OR adfa.cod_unidad_ejecutora = ".$filtro['cod_unidad_ejecutora']." )";
            unset($filtro['cod_unidad_ejecutora']);
        }

        $where .= ' AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'ADFA', '1=1');

        $sql = "
            SELECT  ADFA.*,
                adfa.id_factura ||' - '||
                CASE
                    WHEN ADFA.nro_factura IS NOT NULL THEN
                        SUBSTR(LPAD (ADFA.nro_factura, 12, 0),1,4) || '-' || SUBSTR(LPAD (ADFA.nro_factura, 12, 0),5,12)
                    ELSE ''
                END ||'  '||
                to_char(adfa.fecha_emision,'DD/MM/YYYY') || '  ' || l_krex.nro_expediente || ' - ' ||  L_ADPR.RAZON_SOCIAL as lov_descripcion
            FROM AD_FACTURAS ADFA, AD_PROVEEDORES L_ADPR, KR_EXPEDIENTES L_KREX_PAGO, KR_EXPEDIENTES L_KREX
            WHERE ADFA.ID_PROVEEDOR = L_ADPR.ID_PROVEEDOR AND
                  ADFA.ID_EXPEDIENTE_PAGO = L_KREX_PAGO.ID_EXPEDIENTE (+) AND
                  ADFA.ID_EXPEDIENTE = L_KREX.ID_EXPEDIENTE (+) and $where
            ORDER BY lov_descripcion ASC
        ";
        toba::logger()->debug('SQL FACTURAS REND '. $sql);
        $datos = toba::db()->consultar($sql);
        return $datos;
    }
}
?>
