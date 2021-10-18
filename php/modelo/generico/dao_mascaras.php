<?php

class dao_mascaras
{
    public static function validar_mascara($tipo_comprobante, $valor)
    {
        if (!isset($tipo_comprobante) || !isset($valor)) {
            return 'NOTOK: No se ha definido el tipo de comprobante y el valor.';
        }
        $sql = 'BEGIN :resultado := pkg_mascaras.validar_mascara (:tipo_comprobante, :valor);  END;';

        $parametros = [[
            'nombre' => 'tipo_comprobante',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 32,
            'valor' => $tipo_comprobante,
        ], [
            'nombre' => 'valor',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 32,
            'valor' => $valor,
        ], [
            'nombre' => 'resultado',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 4000,
            'valor' => '',
        ]];

        $resultado = ctr_procedimientos::ejecutar_procedure_mensajes(
            $sql,
            $parametros,
            '',
            'Error al validar la mascara.',
            false
        );
        if (!isset($resultado[2]['valor']) || empty($resultado[2]['valor'])) {
            return 'NOTOK: error al ejecutar el metodo de validación.';
        }

        return $resultado[2]['valor'];
    }

    public static function get_mascara($tipo_comprobante)
    {
        $sql = "
            SELECT PKG_MASCARAS.retornar_mascara('$tipo_comprobante') mascara
            FROM DUAL
        ";
        $resultado = toba::db()->consultar_fila($sql);

        if (isset($resultado['mascara'])) {
            return $resultado['mascara'];
        }
    }
}
