<?php

class ctr_construir_sentencias
{
	public static function consultar_x_paginado($sql, $filtro = [])
	{
		// agrego limit y offset
		$where_desde_hasta = ' 1 = 1 ';
		if (isset($filtro['numrow_hasta'])) {
			$where_desde_hasta .= ' AND numfila <= '.quote($filtro['numrow_hasta']);
			unset($filtro['numrow_hasta']);
		}
		if (isset($filtro['numrow_desde'])) {
			$where_desde_hasta .= ' AND numfila > '.quote($filtro['numrow_desde']);
			unset($filtro['numrow_desde']);
		}
		$sql_paginado = "	SELECT sql_sin_paginar1.*
							FROM (	SELECT	sql_sin_paginar2.*,
											rownum numfila
									FROM ($sql) sql_sin_paginar2
								) sql_sin_paginar1
							WHERE $where_desde_hasta;";

		return toba::db()->consultar($sql_paginado);
	}

	public static function get_where_filtro($filtro = [], $prefijo_tabla = '', $default = '1=1', $campos_like = [])
	{
		$where = [];

		unset($filtro['numrow_desde']);
		unset($filtro['numrow_hasta']);

		if (!is_array($filtro)) {
			return $default;
		}

		foreach ($filtro as $campo => $valor) {
			if (isset($valor) && !is_null($valor)) {
				if ($prefijo_tabla) {
					$campo_pre = $prefijo_tabla.'.'.$campo;
				}
				if (is_array($valor)) {
					$where[] = $campo_pre.' IN ('.implode(', ', $valor).') ';
				} else {
					if (in_array($campo, $campos_like)) {
						$where[] = self::construir_translate_ilike($campo_pre, $valor);
					} else {
						$where[] = $campo_pre.' = '.quote($valor);
					}
				}
			}
		}
		return empty($where) ? $default : implode(' AND ', $where);
	}

	// Devuelve una cadena conformada con LIKE comparando un campo con una cadena de forma de poder igualar
	// cadenas con acentos y otros caracteres especiales.
	// Se usa para conformar un "query de búsqueda" en Oracle
	public static function construir_translate_ilike($campo, $busqueda, $invertir_orden = false)
	{
		$cadena_especiales = quote(ctr_formatear_cadenas::CADENA_ESPECIALES);
		$cadena_basicos = quote(ctr_formatear_cadenas::CADENA_BASICOS);
		if ($invertir_orden === true) {
			$busqueda_quote = quote($busqueda);
			$transl_campo = "LOWER(TRANSLATE('%'||".$campo."||'%', ".$cadena_especiales.', '.$cadena_basicos.'))';
			$transl_busca = 'LOWER(TRANSLATE('.$busqueda_quote.', '.$cadena_especiales.', '.$cadena_basicos.'))';

			return '('.$transl_busca.' LIKE '.$transl_campo.')';
		} else {
			$busqueda_quote = quote('%'.strtolower(ctr_formatear_cadenas::reemplazar_caracteres_especiales($busqueda)).'%');
			$transl_campo = 'LOWER(TRANSLATE('.$campo.', '.$cadena_especiales.', '.$cadena_basicos.'))';
			$transl_busca = $busqueda_quote;

			return '('.$transl_campo.' LIKE '.$transl_busca.')';
		}
	}

	public static function construir_sentencia_busqueda($nombre, $campos = [], $excluir_texto_parentesis = false)
	{
		if ($excluir_texto_parentesis) {
			$nombre = trim(preg_replace("/\(([\w\s+-.;,:.%áéíóúñ$]*)\)/", '', $nombre));
		}
		$arr_condiciones = [];
		foreach ($campos as $campo) {
			$arr_condiciones[] = self::construir_translate_ilike($campo, $nombre);
		}
		if (!empty($arr_condiciones)) {
			return ' ('.implode(' OR ', $arr_condiciones).') ';
		} else {
			return '';
		}
	}

	public static function armar_order_by($sql, $order)
	{
		if (empty($order)) {
			return $sql;
		}
		$sentido = $order['sentido'] == 'des' ? 'desc' : 'asc';
		$clausula_order =
			strpos(strtolower($sql), 'order by') === false ? 'ORDER BY' : ',';

		return "$sql
			$clausula_order {$order['columna']} $sentido
		";
	}

	public static function construir_sentencia_set($columnas = [], $prefijo_tabla = '')
	{
		$seters = [];

		foreach ($columnas as $campo => $valor) {
			if (isset($valor) && !is_null($valor)) {
				if ($prefijo_tabla) {
					$campo_pre = $prefijo_tabla.'.'.$campo;
				}

				$valorSet = quote($valor);
				if (is_array($valor)) {
					if (isset($valor['es_fecha'])) {
						//ojo, solo chequea con isset
							$valorSet = "to_date('".$valor['fecha']."','YYYY-MM-DD')";
					}
					if (isset($valor['es_fecha_hora'])) {
							$valorSet = "to_date('".$valor['fecha']."','YYYY-MM-DD hh24:mi:ss')";
					}
				}
				$seters[] = $campo_pre.' = '.$valorSet;
			}
		}

		return empty($seters) ? '' : implode(' , ', $seters);
	}

	/**
	 * Devuelve una sentencia INSERT.
	 *
	 * @param string $tabla El nombre de la tabla principal.
	 * @param Object $columnas Los datos a insertar.
	 * @param array [$raw_cols=[]]
	 *  Listado de columnas que no deben aplicarse
	 *  la función `quote`.
	 * @return string El query de inserción.
	 */
	public static function construir_sentencia_insert(
		$tabla,
		$columnas,
		$raw_cols = [],
		$params = []
	) {
		$columnas_values = [];
		$values = [];
		$returns = [
			'columns' => [],
			'values' => [],
		];

		foreach ($columnas as $campo => $valor) {
			$param_key = array_search($valor, array_column($params, 'nombre'));

			if ($param_key !== false && $params[$param_key]['tipo'] == PDO::PARAM_LOB) {
				$columnas_values[] = $campo;
				$values[] = 'EMPTY_BLOB()';
				$returns['columns'][] = $campo;
				$returns['values'][] = $valor;

				continue;
			}
			if (isset($valor)) {
				$columnas_values[] = $campo;
				$values[] = in_array($campo, $raw_cols) ? $valor : quote($valor);
			}
		}

		if (empty($returns['columns'])) {
			$returns = '';
		} else {
			$returns['columns'] = implode(', ', $returns['columns']);
			$returns['values'] = implode(', ', $returns['values']);
			$returns = "RETURNING {$returns['columns']} INTO {$returns['values']}";
		}

		$stm_columnas = empty($columnas_values) ? '' : implode(' , ', $columnas_values);
		$stm_values = empty($values) ? '' : implode(' , ', $values);

		return "INSERT INTO $tabla ($stm_columnas) VALUES ($stm_values) $returns";
	}

	public static function sql_update($tabla, $columnas, $where, $raw_cols = [])
	{
		toba::logger()->warning(
			"[DEPRECADO] Considere reemplazar la llamada a `ctr_construir_sentencias::sql_update` por `ctr_construir_sentencias::construir_sentencia_update`. Para más información acceder a http://10.1.1.20/documentacion y buscar la clase y función."
		);

		return self::construir_sentencia_update($tabla, $columnas, $where, $raw_cols);
	}

	/**
	 * Devuelve una sentencia UPDATE.
	 *
	 * @param string $tabla El nombre de la tabla principal.
	 * @param Object $columnas Los datos a actualizar.
	 * @param string $where El filtro de la consulta.
	 * @param array [$raw_cols=[]]
	 *  Listado de columnas que no deben aplicarse
	 *  la función `quote`.
	 * @return string El query de actualización.
	 */
	public static function construir_sentencia_update(
		$tabla,
		$columnas,
		$where,
		$raw_cols = [],
		$params = []
	) {
		$sets = [];
		$returns = [
			'columns' => [],
			'values' => [],
		];

		foreach ($columnas as $campo => $valor) {
			$param_key = array_search($valor, array_column($params, 'nombre'));

			if ($param_key !== false && $params[$param_key]['tipo'] == PDO::PARAM_LOB) {
				$sets[] = "$campo = EMPTY_BLOB()";
				$returns['columns'][] = $campo;
				$returns['values'][] = $valor;

				continue;
			}
			if (isset($valor)) {
				$valor = in_array($campo, $raw_cols) ? $valor : quote($valor);
				$sets[] = "$campo = $valor";
			}
		}
		$set = empty($sets) ? '' : implode(', ', $sets);

		if (empty($returns['columns'])) {
			$returns = '';
		} else {
			$returns['columns'] = implode(', ', $returns['columns']);
			$returns['values'] = implode(', ', $returns['values']);
			$returns = "RETURNING {$returns['columns']} INTO {$returns['values']}";
		}

		return "UPDATE $tabla SET $set WHERE $where $returns";
	}

	/**
	 * Devuelve una sentencia DELETE.
	 *
	 * @param string $tabla El nombre de la tabla.
	 * @param Object $original Los datos originales de la fila.
	 * @param array $columnas_clave Las claves que son clave.
	 * @param array [$raw_cols=[]]
	 *  Listado de columnas que no deben aplicarse
	 *  la función `quote`.
	 * @return string El query de eliminación.
	 */
	public static function construir_sentencia_delete(
		$tabla,
		$original,
		$columnas_clave,
		$raw_cols = []
	) {
		$where = array_reduce(
			$columnas_clave,
			function($parcial, $clave) use($original, $raw_cols) {
				$valor = $original[$clave];

				if (!in_array($valor, $raw_cols) && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $valor)) {
					$valor = quote($valor);
					return $parcial . "
						AND $clave = to_date($valor, 'DD/MM/YYYY')
					";
				}
				$valor = in_array($valor, $raw_cols) ? $valor : quote($valor);

				return $parcial . "
					AND $clave = $valor
				";
			},
			'1=1'
		);

		return "
			DELETE FROM $tabla
			WHERE $where
		";
	}
}
