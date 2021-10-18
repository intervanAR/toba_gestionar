<?php

class dao_kr_general
{
    public static function get_cuit_generico()
    {
        return self::consultar_parametro_kr('CUIT_GENERICO');
    }

    public static function get_controlar_vencimiento_dev_factura()
    {
        return self::consultar_parametro_kr('CONTROLAR_DEV_FAC_VENC');
    }

    public static function genera_orden_pago_auto()
    {
        return self::consultar_parametro_kr('GEN_AUTO_ORDEN_PAGO');
    }

    public static function genera_recibo_pago_auto()
    {
        return self::consultar_parametro_kr('GEN_AUTO_RECIBO_PAGO');
    }

    public static function control_recursos_especificos()
    {
        return self::consultar_parametro_kr('CONTROL_RECURSOS_ESPECIFICOS');
    }

    public static function validar_cbu($cbu)
    {
        if (!empty($cbu)) {
            $sql = "SELECT PKG_KR_GENERAL.validar_cbu($cbu) resultado FROM DUAL;";
            $resultado = toba::db()->consultar_fila($sql);

            return $resultado['resultado'];
        } else {
            return null;
        }
    }

    public static function lugares_entrega_x_item()
    {
        return self::consultar_parametro_kr('LUGARES_ENTREGA_X_ITEM');
    }

    public static function permite_orden_sin_compra()
    {
        return self::consultar_parametro_kr('PERM_ORDEN_SIN_COMPRA');
    }

    public static function orden_estado_inicial()
    {
        return self::consultar_parametro_kr('ORDEN_ESTADO_INICIAL');
    }

    public static function orden_estado_final()
    {
        return self::consultar_parametro_kr('ORDEN_ESTADO_FINAL');
    }

    public static function orden_estado_final_nok()
    {
        return self::consultar_parametro_kr('ORDEN_ESTADO_FINAL_NOK');
    }

    public static function modif_en_pendiente()
    {
        return self::consultar_parametro_kr('MODIF_EN_PENDIENTE');
    }

    public static function cod_tipo_prev_modif()
    {
        return self::consultar_parametro_kr('COD_TIPO_PREV_MODIF');
    }

    public static function cod_tipo_comprobante_gas_fc()
    {
        return self::consultar_parametro_kr('COD_TIPO_COMPROBANTE_GAS_FC');
    }

    public static function hab_gasto_gen_dev_rec()
    {
        return self::consultar_parametro_kr('HAB_GASTO_GEN_DEV_REC');
    }


    private static function consultar_parametro_kr($parametro)
    {
        $sql = "SELECT PKG_KR_GENERAL.VALOR_PARAMETRO('$parametro') valor_parametro
				FROM DUAL;";
        $resultado = toba::db()->consultar_fila($sql);
        if (isset($resultado) && !empty($resultado) && isset($resultado['valor_parametro'])) {
            return $resultado['valor_parametro'];
        } else {
            return null;
        }
    }
}
