<?php

class ctr_funciones_basicas
{
	private $color_anulado = 'rgb(204, 0, 0)';
	private $color_aprobado = 'rgb(0, 150, 0)';

	public function set_color_anulado($color_rgb)
	{
		$this->color_anulado = $color_rgb;
	}

	public function set_color_aprobado($color_rgb)
	{
		$this->color_aprobado = $color_rgb;
	}

	public function get_color_anulado()
	{
		return $this->color_anulado;
	}

	public function get_color_aprobado()
	{
		return $this->color_aprobado;
	}

	public static function matriz_to_array($matriz, $campo)
	{
		if (empty($matriz)) {
			return [];
		} else {
			$lista = [];
			foreach ($matriz as $mat) {
				if (isset($mat[$campo])) {
					$lista[] = $mat[$campo];
				}
			}

			return $lista;
		}
	}

	//Elimina los duplicados del arreglo
	public static function eliminar_duplicados_arreglo($arr)
	{
		$salida = [];
		$tam = sizeof($arr);
		$indice = 0;

		for ($i = 0; $i < $tam; ++$i) {
			if (empty($salida)) {
				$salida[$indice] = $arr[$i];
				++$indice;
			} elseif (!(in_array($arr[$i], $salida))) {
				$salida[$indice] = $arr[$i];
				++$indice;
			}
		}

		return $salida;
	}

	public static function formatear_cuadro_ordenes_compras($datos_cuadro, $campo_estado = null, $campo_finaliada = null)
	{
		foreach ($datos_cuadro as $clave => $dato) {
			if (isset($campo_estado) && !empty($campo_estado) && isset($dato[$campo_estado]) && strcasecmp($dato[$campo_estado], 'ANUL') == 0) {
				$dato_formateado = self::formatear_color($dato, 'rgb(204, 0, 0)');
			} elseif (isset($campo_estado) && !empty($campo_estado) && isset($dato[$campo_estado]) && strcasecmp($dato[$campo_estado], 'ENTR') == 0 && strcasecmp($dato[$campo_finaliada], 'S') == 0) {
				$dato_formateado = self::formatear_color($dato, 'rgb(0, 150, 0)');
			} else {
				$dato_formateado = $dato;
			}
			$datos_cuadro[$clave] = $dato_formateado;
		}

		return $datos_cuadro;
	}

	public function formatear_cuadro($datos_cuadro, $campo_aprobado = null, $campo_anulado = null)
	{
		foreach ($datos_cuadro as $clave => $dato) {
			if (isset($campo_anulado) && !empty($campo_anulado) && isset($dato[$campo_anulado]) && strcasecmp($dato[$campo_anulado], 'S') == 0) {
				$dato_formateado = self::formatear_color($dato, $this->get_color_anulado());
			} elseif (isset($campo_aprobado) && !empty($campo_aprobado) && isset($dato[$campo_aprobado]) && strcasecmp($dato[$campo_aprobado], 'S') == 0) {
				$dato_formateado = self::formatear_color($dato, $this->get_color_aprobado());
			} else {
				$dato_formateado = $dato;
			}
			$datos_cuadro[$clave] = $dato_formateado;
		}

		return $datos_cuadro;
	}

	public static function formatear_color($dato_cuadro, $color = 'rgb(0, 0, 0)')
	{
		foreach ($dato_cuadro as $clave => $dato) {
			$dato_cuadro[$clave] = "<span style='color: $color;'>".$dato.'</span>';
		}

		return $dato_cuadro;
	}

	/**
	 * @return {boolean|null}
	 *  - Devuelve `true` en caso de ser un arreglo secuencial.
	 *  - Devuelve `false`  en caso de ser un arreglo asociativo.
	 *  - Devuelve `null` en caso de ser otro tipo de dato.
	 */
	public static function es_arreglo_secuencial($arreglo)
	{
		if (!is_array($arreglo)) {
			return null;
		}

		if (empty($arreglo)) {
			return true;
		}

		return array_keys($arreglo) === range(0, count($arreglo) - 1);
	}

	/**
	 * Convierte la codificación del array a UTF-8. Útil para poder utilizar
	 * json_enconde sin que devuelva falso.
	 *
	 * @param array $array
	 * @return array
	 */
	public static function utf8_data_converter($array)
	{
		toba::logger()->warning('ctr_funciones_basicas::utf8_data_converter recorre el arreglo completo de datos y cambia la codificación de cada valor a UTF-8. Por favor, cambie la configuración de la conexión de Oracle para que utilice UTF-8 como encoder de forma que no tenga que utilizar esta función.');
		array_walk_recursive($array, function(&$item, $key) {
			if (!mb_detect_encoding($item, 'utf-8', true)) {
				$item = mb_convert_encoding($item, 'utf-8');
			}
		});

		return $array;
	}
	public static function ansi_data_converter($array)
	{
		toba::logger()->warning('ctr_funciones_basicas::ansi_data_converter recorre el arreglo completo de datos y cambia la codificación de cada valor a UTF-8. Por favor, cambie la configuración de la conexión de Oracle para que utilice UTF-8 como encoder de forma que no tenga que utilizar esta función.');
		array_walk_recursive($array, function(&$item, $key) {
			if (!mb_detect_encoding($item, 'Windows-1252', true)) {
				$item = mb_convert_encoding($item, 'Windows-1252', 'utf-8');
			}
		});

		return $array;
	}
}
