<?php

class dao_bp_procesos
{
    public static function get_comprobantes($id_proceso, $id_actividad)
    {
        if (!isset($id_proceso) || !isset($id_actividad)) {
            return;
        }
        $sql = 'BEGIN :resultado := PKG_BP_PROCESOS.retornar_comprobantes(:p_id_proceso, :p_id_actividad); END;';

        $parametros = [[
            'nombre' => 'p_id_proceso',
            'tipo_dato' => PDO::PARAM_INT,
            'longitud' => 32,
            'valor' => $id_proceso,
        ], [
            'nombre' => 'p_id_actividad',
            'tipo_dato' => PDO::PARAM_INT,
            'longitud' => 32,
            'valor' => $id_actividad,
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
            'Error al obtener los comprobantes para el proceso y la actividad.'
        );
        if (isset($resultado[2]['valor'])) {
            return $resultado[2]['valor'];
        } else {
            return '0';
        }
    }

    public static function get_comprobantes_anteriores($id_proceso, $id_actividad)
    {
        if (!isset($id_proceso) || !isset($id_actividad)) {
            return;
        }
        $sql = 'BEGIN :resultado := PKG_BP_PROCESOS.retornar_comprob_ant(:p_id_proceso, :p_id_actividad); END;';

        $parametros = [[
            'nombre' => 'p_id_proceso',
            'tipo_dato' => PDO::PARAM_INT,
            'longitud' => 32,
            'valor' => $id_proceso,
        ], [
            'nombre' => 'p_id_actividad',
            'tipo_dato' => PDO::PARAM_INT,
            'longitud' => 32,
            'valor' => $id_actividad,
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
            'Error al obtener los comprobantes para el proceso y la actividad.'
        );

        return $resultado[2]['valor'];
    }

    public static function get_valores_posibles($cod_proceso, $cod_actividad, $cod_campo)
    {
        if (!isset($cod_proceso) || !isset($cod_actividad)) {
            return;
        }
        $sql = 'BEGIN :resultado := PKG_BP_PROCESOS.valores_posibles(:cod_proceso, :cod_actividad, :cod_campo); END;';

        $parametros = [[
            'nombre' => 'cod_proceso',
            'tipo_dato' => PDO::PARAM_INT,
            'longitud' => 32,
            'valor' => $cod_proceso,
        ], [
            'nombre' => 'cod_actividad',
            'tipo_dato' => PDO::PARAM_INT,
            'longitud' => 32,
            'valor' => $cod_actividad,
        ], [
            'nombre' => 'cod_campo',
            'tipo_dato' => PDO::PARAM_INT,
            'longitud' => 32,
            'valor' => $cod_campo,
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
            'Error al obtener los valores posibles para el proceso, la actividad y el campo.'
        );

        return $resultado[3]['valor'];
    }

    public static function get_valor_por_defecto($cod_proceso, $cod_actividad, $cod_campo)
    {
        if (!isset($cod_proceso) || !isset($cod_actividad)) {
            return;
        }
        $sql = 'BEGIN :resultado := PKG_BP_PROCESOS.valor_por_defecto(:cod_proceso, :cod_actividad, :cod_campo); END;';

        $parametros = [[
            'nombre' => 'cod_proceso',
            'tipo_dato' => PDO::PARAM_INT,
            'longitud' => 32,
            'valor' => $cod_proceso,
        ], [
            'nombre' => 'cod_actividad',
            'tipo_dato' => PDO::PARAM_INT,
            'longitud' => 32,
            'valor' => $cod_actividad,
        ], [
            'nombre' => 'cod_campo',
            'tipo_dato' => PDO::PARAM_INT,
            'longitud' => 32,
            'valor' => $cod_campo,
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
            'Error al obtener el valor por defecto para el proceso, la actividad y el campo.'
        );

        if (isset($resultado[3]['valor']) && !empty($resultado[3]['valor'])) {
            return $resultado[3]['valor'];
        }
    }

    public static function insertar_comprobante(
        $id_proceso, $id_actividad, $id_comprobante, $cod_proceso, $cod_actividad
    ) {
        if (
            !isset($id_proceso)
            || !isset($id_actividad)
            || !isset($id_comprobante)
            || !isset($cod_proceso)
            || !isset($cod_actividad)
        ) {
            return;
        }
        $mensaje_error = 'Error al completar el comprobante.';

        try {
            $sql = 'BEGIN :resultado := PKG_BP_PROCESOS.insertar_comprobante (:p_id_proceso, :p_id_actividad, :p_id_comprobante, :p_cod_proceso, :p_cod_actividad); END;';

            $parametros = [[
                'nombre' => 'p_id_proceso',
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $id_proceso,
            ], [
                'nombre' => 'p_id_actividad',
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $id_actividad,
            ], [
                'nombre' => 'p_id_comprobante',
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $id_comprobante,
            ], [
                'nombre' => 'p_cod_proceso',
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $cod_proceso,
            ], [
                'nombre' => 'p_cod_actividad',
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $cod_actividad,
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
                'Error al insertar el comprobante para el proceso y la actividad.',
                false
            );
            $valor_resultado = $resultado[5]['valor'];

            if ($valor_resultado != 'OK') {
                throw new toba_error($valor_resultado);
            }

            toba::notificacion()->info(
                "El comprobante $id_comprobante se completo exitosamente."
            );
        } catch (toba_error_db $e_db) {
            echo 'ERROR 111';
            toba::notificacion()->error($mensaje_error.' '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error($mensaje_error.' '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            throw $e_db;
        } catch (toba_error $e) {
            echo 'ERROR 222';
            toba::notificacion()->error($mensaje_error.' '.$e->get_mensaje());
            toba::logger()->error($mensaje_error.' '.$e->get_mensaje());
            throw $e;
        }
    }

    public static function borrar_comprobante(
        $id_proceso, $id_actividad, $id_comprobante
    ) {
        if (!isset($id_proceso) || !isset($id_actividad) || !isset($id_comprobante)) {
            return;
        }
        $mensaje_error = 'Error al borrar el comprobante.';
        try {
            $sql = 'BEGIN :resultado := PKG_BP_PROCESOS.borrar_comprobante(:p_id_proceso, :p_id_actividad, :p_id_comprobante); END;';

            $parametros = [[
                'nombre' => 'p_id_proceso',
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $id_proceso,
            ], [
                'nombre' => 'p_id_actividad',
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $id_actividad,
            ], [
                'nombre' => 'p_id_comprobante',
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $id_comprobante,
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
                'Error al borrar el comprobante para el proceso y la actividad.',
                false
            );
            $valor_resultado = $resultado[3]['valor'];

            if ($valor_resultado != 'OK') {
                throw new toba_error($valor_resultado);
            }
            toba::notificacion()->info('El comprobante se borró exitosamente.');
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error($mensaje_error.' '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error($mensaje_error.' '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            throw $e_db;
        } catch (toba_error $e) {
            toba::notificacion()->error($mensaje_error.' '.$e->get_mensaje());
            toba::logger()->error($mensaje_error.' '.$e->get_mensaje());
            throw $e;
        }
    }
}
