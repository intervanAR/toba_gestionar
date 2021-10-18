<?php
/**
 * @author lgraziani
 */
class principal_ei_tabulator_consultar
{
	public static function todos_los_datos($sql, $config = [])
	{
		return Sequelvan::consultar(
			self::agrega_id_a_consulta($sql),
			[],
			$config
		);
	}

	public static function get_total_paginas($tabla, $tamanyo, $from, $filtros)
	{
		$sql = "
			SELECT CEIL(COUNT(*) / $tamanyo) cantidad
			FROM
				$from
		";
		$sql = self::agrega_filtro_a_consulta($tabla, $sql, $filtros);
		$sql = toba::perfil_de_datos()->filtrar($sql);

		return toba::db()->consultar_fila($sql)['cantidad'];
	}

	public static function por_pagina(
		$tabla,
		$tamanyo,
		$pagina,
		$sql,
		$filtros,
		$ordenamientos,
		$config = []
	) {
		$desde = ($pagina - 1) * $tamanyo + 1;
		$hasta = $pagina * $tamanyo;

		$sql = self::agrega_filtro_a_consulta($tabla, $sql, $filtros);
		$sql = self::agrega_ordenamiento_a_consulta($tabla, $sql, $ordenamientos);
		$sql = "
			SELECT tabla.*
			FROM (
				SELECT ROWNUM id, tab.*
				FROM ($sql) tab
			) tabla
			WHERE id BETWEEN $desde AND $hasta
		";

		return Sequelvan::consultar($sql, [], $config);
	}

	public static function si_valor_existe($tabla, $claves)
	{
		$where = array_reduce(
			array_keys($claves),
			function ($partial, $key) use ($claves) {
				$clave = quote($claves[$key]);

				return "$partial
					AND $key = $clave";
			},
			'				1=1'
		);
		$sql = "
			SELECT count(*) cantidad
			FROM $tabla
			WHERE
				$where
		";

		toba::logger()->debug("[TABULATOR QUERY] REGLA UNICO: \n$sql");

		return intval(toba::db()->consultar_fila($sql)['cantidad']) > 0;
	}

	private static function agrega_id_a_consulta($sql)
	{
		return preg_replace('/select/i', 'select rownum id,', $sql, 1);
	}

	private static function agrega_ordenamiento_a_consulta(
		$tabla,
		$sql,
		$ordenamientos
	) {
		if (empty($ordenamientos)) {
			return $sql;
		}

		$ordenamientos = array_map(function ($ordenamiento) {
			$dir = strtolower($ordenamiento['dir']);

			if ($dir !== 'asc' && $dir !== 'desc') {
				throw new toba_error("Dirección de ordenamiento `$dir` desconocida.");
			}

			return "{$ordenamiento['field']} $dir";
		}, $ordenamientos);
		$ordenamientos = implode(",\n", $ordenamientos);

		return "$sql
			ORDER BY
				$ordenamientos
		";
	}

	private static function procesar_filtro($clave, $filtros)
	{
		$filtro = $filtros[$clave];

		return isset($filtro['field'])
			? $filtro
			: [
				'field' => $clave,
				'value' => $filtro,
			];
	}

	private static function agrega_filtro_a_consulta($tabla, $sql, $filtros)
	{
		$where = array_map(function ($clave) use ($tabla, $filtros) {
			$filtro = self::procesar_filtro($clave, $filtros);

			// FIXME Refactorizar para que maneje mejor los distintos tipos de filtro
			$filtrador = isset($filtro['type']) ? $filtro['type'] : 'equals';
			$filtrador = $filtrador === '=' ? 'equals' : $filtrador;

			if (!method_exists('principal_ei_tabulator_consultar', $filtrador)) {
				toba::logger()->error("[principal_ei_tabulator_consultar] No existe el método de filtrado: $filtrador");

				return;
			}

			return self::$filtrador($tabla, $filtro);
		}, array_keys($filtros));

		return sql_concatenar_where($sql, $where);
	}

	private static function equals($tabla, $filtro)
	{
		$campo = preg_match('/\w+\.\w+/', $filtro['field']) ? $filtro['field'] : "$tabla.{$filtro['field']}";
		$value = quote($filtro['value']);

		return "$campo = $value";
	}

	private static function like($tabla, $filtro)
	{
		$campo = preg_match('/\w+\.\w+/', $filtro['field']) ? $filtro['field'] : "$tabla.{$filtro['field']}";
		$where = ctr_construir_sentencias::construir_translate_ilike(
			$campo,
			$filtro['value']
		);

		return $where;
	}

	private static function is_null($tabla, $filtro)
	{
		$campo = preg_match('/\w+\.\w+/', $filtro['field']) ? $filtro['field'] : "$tabla.{$filtro['field']}";

		return "$campo is null";
	}

	private static function is_not_null($tabla, $filtro)
	{
		$campo = preg_match('/\w+\.\w+/', $filtro['field']) ? $filtro['field'] : "$tabla.{$filtro['field']}";

		return "$campo is not null";
	}

	private static function combo_editable($tabla, $filtro)
	{
		return self::equals($tabla, $filtro);
	}
}
