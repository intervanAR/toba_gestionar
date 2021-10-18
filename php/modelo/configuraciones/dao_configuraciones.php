<?php

class dao_configuraciones
{
	public static function get_imputacion_presupuestaria()
	{
		$sql_sel = "SELECT  kc.*
				FROM kr_configuracion kc;";
		$datos = toba::db()->consultar_fila($sql_sel);
		if (isset($datos) && !empty($datos) && isset($datos['imputa_presup']) && strcasecmp($datos['imputa_presup'], 'S') == 0) {
			return true;
		} else {
			return false;
		}
	}

	public static function get_imputacion_centro_costos()
	{
		$sql_sel = "SELECT  kc.*
				FROM kr_configuracion kc;";
		$datos = toba::db()->consultar_fila($sql_sel);
		if (isset($datos) && !empty($datos) && isset($datos['imputa_costos']) && strcasecmp($datos['imputa_costos'], 'S') == 0) {
			return true;
		} else {
			return false;
		}
	}

	public static function get_valor($campo)
	{
		$campo = quote($campo);
		$sql = "
			SELECT valor
			FROM RE_CONFIGURACIONES
			WHERE campo = $campo
		";

		return toba::db()->consultar_fila($sql)['valor'];
	}
}
