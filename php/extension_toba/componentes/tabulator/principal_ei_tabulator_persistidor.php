<?php
/**
 * @author lgraziani
 */
class principal_ei_tabulator_persistidor
{
	const TYPE_INSERT = 'i';
	const TYPE_UPDATE = 'm';
	const TYPE_DELETE = 'd';

	public static function procesar($operaciones, $metadatos, $config, $cuadro)
	{
		self::puede_persistir($operaciones, $metadatos, $config, $cuadro);

		$notificaciones= self::guardar($operaciones, $metadatos, $config);

		if (count($notificaciones) == 0)
			$cuadro->after_guardar($operaciones, $metadatos, $config, $cuadro);

		return $notificaciones;
	}

	private static function guardar($operaciones, $metadatos, $config)
	{
		$tabla = $config['tabla_nombre'];
		$columnas_clave = $config['columnas_clave'];
		$notificaciones = [];

		ctr_procedimientos::ejecutar_transaccion_compuesta(
			'Error al procesar las operaciones del cuadro',
			function () use (
				$operaciones,
				$metadatos,
				$tabla,
				$columnas_clave,
				&$notificaciones
			) {
				foreach ($operaciones as $operacion) {
					switch ($operacion['type']) {
						case self::TYPE_DELETE:
							self::eliminar(
								$tabla,
								$operacion['original'],
								$columnas_clave
							);

							break;
						case self::TYPE_INSERT:
							unset($operacion['data']['id']);

							self::insertar(
								$tabla,
								$operacion['data'],
								$metadatos
							);

							break;
						case self::TYPE_UPDATE:
							unset($operacion['original']['id']);
							unset($operacion['data']['id']);

							$filas_afectadas = self::actualizar(
								$tabla,
								$operacion['data'],
								$operacion['original'],
								$metadatos,
								$columnas_clave
							);

							if (!$filas_afectadas) {
								$claves = array_map(
									function ($clave) use ($operacion) {
										return "$clave: {$operacion['original'][$clave]}";
									},
									$columnas_clave
								);
								$claves = implode(', ', $claves);

								array_push($notificaciones, [
									'tipo' => 'info',
									'mensaje' => "La fila con claves ($claves) no pudo ser actualizada porque se modificó antes por otro usuario o por un proceso del sistema.",
								]);
							}

							break;
					}
				}
			}
		);

		return $notificaciones;
	}

	private static function insertar($tabla, $data, $metadatos)
	{
		$data = self::procesar_datos($data, $metadatos);
		list($data, $params) = self::generar_props_de_datos($data, $metadatos);
		$sql = ctr_construir_sentencias::construir_sentencia_insert(
			$tabla,
			$data,
			array_keys($data),
			$params
		);

		toba::logger()->debug("[TABULATOR QUERY] INSERT: \n$sql");

		return Sequelvan::ejecutar($sql, $params);
	}

	private static function actualizar(
		$tabla,
		$data,
		$original,
		$metadatos,
		$columnas_clave
	) {
		$data = self::procesar_datos($data, $metadatos, $columnas_clave);
		$original = self::procesar_datos($original, $metadatos, $columnas_clave);
		$where = array_reduce(
			array_keys($original),
			function ($parcial, $fila) use ($original, $metadatos) {
				$config = $metadatos[array_search($fila, array_column($metadatos, 'field'))];

				if (
					isset($config['blob']) && $config['blob'] ||
					isset($config['clob']) && $config['clob']
				) {
					return $parcial;
				}
				$valor = $original[$fila];

				if ($valor !== "''") {
					return $parcial."
						AND $fila = $valor";
				}

				return $parcial."
					AND $fila is null";
			},
			'1=1'
		);
		list($data, $params) = self::generar_props_de_datos($data, $metadatos);

		$sql = ctr_construir_sentencias::construir_sentencia_update(
			$tabla,
			$data,
			$where,
			array_keys($data),
			$params
		);

		toba::logger()->debug("[TABULATOR QUERY] UPDATE: \n$sql");

		return Sequelvan::ejecutar($sql, $params);
	}

	private static function eliminar($tabla, $original, $columnas_clave)
	{
		$sql = ctr_construir_sentencias::construir_sentencia_delete(
			$tabla,
			$original,
			$columnas_clave
		);

		toba::logger()->debug("[TABULATOR QUERY] DELETE: \n$sql");

		ctr_procedimientos::ejecutar_transaccion_simple(null, $sql);
	}

	/**
	 * Arreglo de operaciones a ser persistidas.
	 *
	 * @param Array<$operacion> $operaciones
	 *
	 * @throws toba_error En caso de que haya una operación no permitida.
	 */
	private static function puede_persistir(&$operaciones, $metadatos, $config, $cuadro)
	{
		// Si está todo en falso lanzar error, no debería haber podido
		// realizar la operación
		if ($config['not_insert'] && $config['not_update'] && $config['not_delete']) {
			throw new toba_error("El cuadro {$config['class_name']} es de solo lectura y no permite persistir datos.");
		}

		$cuadro->tabulator__validar($operaciones);

		foreach ($operaciones as $key => &$operacion) {
			if ($operacion['type'] === self::TYPE_INSERT) {
				if ($config['not_insert']) {
					throw new toba_error(
						'Las operaciones de inserción no están permitidas'
					);
				}
				$operacion['data'] = $cuadro->procesar_datos_pre_insertar(
					$operacion['data']
				);
			}
			if ($operacion['type'] === self::TYPE_UPDATE) {
				if ($config['not_update']) {
					throw new toba_error(
						'Las operaciones de actualización no están permitidas'
					);
				}
				$datos = $cuadro->procesar_datos_pre_actualizar(
					$operacion['data'],
					$operacion['original']
				);

				$operacion['data'] = $datos['data'];
				$operacion['original'] = $datos['original'];
			}
			// La operación de eliminación no necesita
			// verificar ningún campo.
			if ($operacion['type'] === self::TYPE_DELETE) {
				if ($config['not_delete']) {
					throw new toba_error(
						'Las operaciones de eliminación no están permitidas'
					);
				}
				$operacion['original'] = $cuadro->procesar_datos_pre_eliminar(
					$operacion['original']
				);

				continue;
			}

			principal_ei_tabulator_validator::validar($metadatos, $operacion, $config);
		}
	}

	private static function generar_props_de_datos($data, $metadatos)
	{
		return array_reduce(
			array_keys($data),
			function ($partial, $clave) use ($metadatos) {
				$config = $metadatos[
					array_search(
						$clave,
						array_column($metadatos, 'field')
					)
				];

				if (!isset($config['blob']) || !$config['blob']) {
					return $partial;
				}

				array_push($partial[1], [
					'nombre' => ":$clave",
					'valor' => $partial[0][$clave],
					'tipo' => PDO::PARAM_LOB,
				]);
				$partial[0][$clave] = ":$clave";

				return $partial;
			},
			[$data, []]
		);
	}

	private static function procesar_datos($data, $metadatos, $columnas_clave = [])
	{
		foreach ($data as $key => $value) {
			$indice = array_search($key, array_column($metadatos, 'field'));
			$config = $indice === false ? [] : $metadatos[$indice];

			if (isset($config['blob']) && $config['blob']) {
				if (!isset($value) || is_null($value) || $value == '') {
					unset($data[$key]);

					continue;
				}
				$base64 = base64_decode(
					preg_replace('/^data:image\/\w+;base64,/', '', $data[$key])
				);
				$data[$key] = tmpfile();
				fwrite($data[$key], $base64);
				rewind($data[$key]);

				continue;
			}
			if (!isset($value) || is_null($value) || $value == '') {
				$data[$key] = "''";

				continue;
			}
			if (isset($config['clob']) && $config['clob']) {
				continue;
			}

			$value = quote($value);

			if (isset($config['formatter']) && $config['formatter'] === "'fecha'") {
				$es_timestamp =
					isset($config['editorParams'])
					&& isset($config['editorParams']['mask'])
					&& $config['editorParams']['mask'] === 'datetime';

				$data[$key] = $es_timestamp
					? "TO_DATE($value, 'dd/mm/yyyy HH24:MI:SS')"
					: "TO_DATE($value, 'dd/mm/yyyy')";

				continue;
			}

			$data[$key] = $value;
		}

		return $data;
	}
}
