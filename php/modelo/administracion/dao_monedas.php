<?php

class dao_monedas {

    public static function get_monedas($filtro = array()) {

        $where = "1= 1";

        $where.= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'm', '1=1');

        $sql = "SELECT m.*, decode(m.moneda_principal,'S','Si','No') moneda_principal_format
				FROM KR_MONEDAS m
                WHERE $where";

        //print($sql);
        $datos = toba::db()->consultar($sql);
        /*
          foreach ($datos as $clave => $dato) {
          $datos[$clave]['des_clase_comprobante'] = self::get_descripcion_clase_comprobante($datos[$clave]['clase_comprobante']);
          } */

        return $datos;
    }

    public static function obtener_moneda_principal() {
        $sql = "SELECT cod_moneda
				FROM kr_monedas
			   WHERE moneda_principal = 'S';";

        $datos = toba::db()->consultar_fila($sql);
        return $datos['cod_moneda'];
    }

    public static function get_cotizacion_referencia($fecha_comprobante, $cod_moneda) {
        try {
            if (isset($fecha_comprobante) && (!empty($fecha_comprobante)) && isset($cod_moneda) && (!empty($cod_moneda))) {
                $sql = "BEGIN :resultado := PKG_AD_MONEDAS.COTIZACION_REFERENCIA(:cod_moneda, to_date(substr(:fecha_comprobante,1,10),'yyyy-mm-dd')); END;";

                $parametros = array(array('nombre' => 'fecha_comprobante',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 32,
                        'valor' => $fecha_comprobante),
                    array('nombre' => 'cod_moneda',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $cod_moneda),
                    array('nombre' => 'resultado',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 4000,
                        'valor' => ''),
                );
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

                return $resultado[2]['valor'];
            } else {
                return '';
            }
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            return '1';
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
            return '1';
        }
    }

    public static function cotizacion($cod_moneda, $fecha_imputacion)
    {
        if (!empty($cod_moneda) && !empty($fecha_imputacion)) {
            $sql = "
                SELECT NVL (a.cotizacion, 1) valor
                FROM kr_monedas_cotizaciones a
                WHERE a.cod_moneda = '$cod_moneda'
                    AND a.fecha_vigencia <= to_date('$fecha_imputacion', 'yyyy-mm-dd')
                    AND NOT EXISTS (
                        SELECT b.cod_moneda
                        FROM kr_monedas_cotizaciones b
                        WHERE b.cod_moneda = '$cod_moneda'
                            AND b.fecha_vigencia <= to_date('$fecha_imputacion', 'yyyy-mm-dd')
                            AND a.fecha_vigencia < b.fecha_vigencia
                    )
            ";
            $datos = toba::db()->consultar_fila($sql);
            return $datos['valor'];
        }
    }
}
?>
