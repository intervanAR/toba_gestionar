<?php

class dao_secuencias
{
    public static function get_es_auto_secuencia_sector(
        $cod_sector, $tipo_comprobante, $tipo_compra = null
    ) {
        if (!isset($cod_sector) || !isset($tipo_comprobante)) {
            return;
        }
        if (!isset($tipo_compra)) {
            $tipo_compra = '';
        }

        $sql = 'BEGIN :resultado := PKG_SECUENCIAS.es_auto_secuencia_sector(:p_sector,:p_tipo_comprobante ,:p_tipo_compra); END;';

        $parametros = [[
            'nombre' => 'p_sector',
            'tipo_dato' => PDO::PARAM_INT,
            'longitud' => 32,
            'valor' => $cod_sector,
        ], [
            'nombre' => 'p_tipo_comprobante',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 32,
            'valor' => $tipo_comprobante,
        ], [
            'nombre' => 'p_tipo_compra',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 32,
            'valor' => $tipo_compra,
        ], [
            'nombre' => 'resultado',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 4000,
            'valor' => '',
        ]];
        $resultado = ctr_procedimientos::ejecutar_functions_mensajes(
            $sql,
            $parametros,
            '',
            'Error al determinar si es autosecuencia para el sector.'
        );

        if (!isset($resultado[3]['valor'])) {
            return 'N';
        }

        return $resultado[3]['valor'];
    }

    public static function get_id_secuencia_sector(
        $cod_sector, $tipo_comprobante, $tipo_compra = null
    ) {
        if (!isset($cod_sector) || !isset($tipo_comprobante)) {
            return;
        }
        if (!isset($tipo_compra)) {
            $tipo_compra = '';
        }
        $sql = 'BEGIN :resultado := PKG_SECUENCIAS.id_secuencia_sector(:p_sector,:p_tipo_comprobante ,:p_tipo_compra); END;';

        $parametros = [[
            'nombre' => 'p_sector',
            'tipo_dato' => PDO::PARAM_INT,
            'longitud' => 32,
            'valor' => $cod_sector,
        ], [
            'nombre' => 'p_tipo_comprobante',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 32,
            'valor' => $tipo_comprobante,
        ], [
            'nombre' => 'p_tipo_compra',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 32,
            'valor' => $tipo_compra,
        ], [
            'nombre' => 'resultado',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 4000,
            'valor' => '',
        ]];
        $resultado = ctr_procedimientos::ejecutar_functions_mensajes(
            $sql,
            $parametros,
            '',
            'Error al determinar id secuencia de sector.'
        );

        if (!isset($resultado[3]['valor'])) {
            return 'N';
        }

        return $resultado[3]['valor'];
    }

    public static function get_es_secuencia_anual(
        $cod_sector, $tipo_comprobante, $tipo_compra = null
    ) {
        if (!isset($cod_sector) || !isset($tipo_comprobante)) {
            return;
        }
        if (!isset($tipo_compra)) {
            $tipo_compra = '';
        }
        $sql = 'BEGIN :resultado := PKG_SECUENCIAS.es_secuencia_anual(:p_sector,:p_tipo_comprobante ,:p_tipo_compra); END;';

        $parametros = [[
            'nombre' => 'p_sector',
            'tipo_dato' => PDO::PARAM_INT,
            'longitud' => 32,
            'valor' => $cod_sector,
        ], [
            'nombre' => 'p_tipo_comprobante',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 32,
            'valor' => $tipo_comprobante,
        ], [
            'nombre' => 'p_tipo_compra',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 32,
            'valor' => $tipo_compra,
        ], [
            'nombre' => 'resultado',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 4000,
            'valor' => '',
        ]];
        $resultado = ctr_procedimientos::ejecutar_functions_mensajes(
            $sql,
            $parametros,
            '',
            'Error al determinar id secuencia de sector.'
        );

        if (!isset($resultado[3]['valor'])) {
            return 'N';
        }

        return $resultado[3]['valor'];
    }
}
