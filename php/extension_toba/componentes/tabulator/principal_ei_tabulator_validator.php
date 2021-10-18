<?php
/**
 * @author lgraziani
 */
class principal_ei_tabulator_validator
{
	public function __construct()
	{
		throw new toba_error('La clase principal_ei_tabulator_validator sólo maneja métodos estáticos');
	}

	public static function validar($metadatos, $operacion, $config)
	{
		$errores = [];

		foreach ($metadatos as $metadato) {
			// Si el campo no tiene validadores, sigo
			if (!isset($metadato['validator'])) {
				continue;
			}
			$validadores = $metadato['validator'];

			foreach ($validadores as $validadorRaw) {
				list($validador, $param) = array_pad(
					explode(':', $validadorRaw),
					2,
					null
				);

				if (!method_exists('principal_ei_tabulator_validator', $validador)) {
					toba::logger()->error("[principal_ei_tabulator_validator] No existe el método de validación para el validador: $validador");
					continue;
				}
				self::$validador($operacion, $metadato, $param, $config, $errores);
			}
		}

		if (!empty($errores)) {
			throw new toba_error(json_encode([
				'id' => $operacion['data']['id'],
				'errores' => $errores,
			]));
		}
	}

	private static function push_error($campo, $mensaje, &$errores)
	{
		array_push(
			$errores,
			[
				'campo' => $campo,
				'mensaje' => mb_convert_encoding($mensaje, 'UTF-8'),
			]
		);
	}

	private static function required($operacion, $metadato, $param, $config, &$errores)
	{
		$campo = $metadato['field'];
		$descripcion = $metadato['title'];
		$datos = $operacion['data'];

		if (isset($datos[$campo]) && $datos[$campo] !== '') {
			return;
		}
		self::push_error(
			$campo,
			"El campo _{$descripcion}_ es obligatorio.",
			$errores
		);
	}

	private static function integer($operacion, $metadato, $param, $config, &$errores)
	{
		$campo = $metadato['field'];
		$descripcion = $metadato['title'];
		$datos = $operacion['data'];

		if (!isset($datos[$campo]) || $datos[$campo] === '') {
			return;
		}
		if (preg_match('/^-?\d+$/', $datos[$campo])) {
			return;
		}
		self::push_error(
			$campo,
			"El campo _{$descripcion}_ debe ser entero.",
			$errores
		);
	}

	private static function numeric($operacion, $metadato, $param, $config, &$errores)
	{
		$campo = $metadato['field'];
		$descripcion = $metadato['title'];
		$datos = $operacion['data'];

		if (!isset($datos[$campo]) || $datos[$campo] === '') {
			return;
		}
		if (is_numeric($datos[$campo])) {
			return;
		}
		self::push_error(
			$campo,
			"El campo _{$descripcion}_ debe ser numérico.",
			$errores
		);
	}

	private static function min($operacion, $metadato, $param, $config, &$errores)
	{
		$campo = $metadato['field'];
		$descripcion = $metadato['title'];
		$datos = $operacion['data'];

		if (!isset($datos[$campo]) || $datos[$campo] === '') {
			return;
		}
		if (intval($param) <= intval($datos[$campo])) {
			return;
		}
		self::push_error(
			$campo,
			"El campo _{$descripcion}_ debe ser mayor o igual a $param.",
			$errores
		);
	}

	private static function max($operacion, $metadato, $param, $config, &$errores)
	{
		$campo = $metadato['field'];
		$descripcion = $metadato['title'];
		$datos = $operacion['data'];

		if (!isset($datos[$campo]) || $datos[$campo] === '') {
			return;
		}
		if (intval($param) >= intval($datos[$campo])) {
			return;
		}
		self::push_error(
			$campo,
			"El campo _{$descripcion}_ debe ser menor o igual a $param.",
			$errores
		);
	}

	private static function combo_editable(
		$operacion,
		$metadato,
		$param,
		$config,
		&$errores
	) {
		// El contenido de un combo editable no tiene validación
		// porque no tiene un tipo de dato específico, ya que es un ID.
		// El validador `required` ya se encarga de verificar si el campo
		// está o no cargado.
	}

	private static function comparar_numero(
		$operacion,
		$metadato,
		$param,
		$config,
		&$errores
	) {
		$campo = $metadato['field'];
		$descripcion = $metadato['title'];
		$datos = $operacion['data'];
		list($param, $param_desc, $comparacion) = explode('@', $param);

		if (!isset($datos[$campo]) || !isset($datos[$param])) {
			return;
		}

		if (!isset($datos[$param])) {
			self::push_error(
				$campo,
				"El campo _{$param}_ ({$param_desc}) no existe.",
				$errores
			);
		}

		if ($comparacion === 'menor' && $datos[$campo] <= $datos[$param]) {
			return;
		}
		if ($comparacion === 'mayor' && $datos[$campo] >= $datos[$param]) {
			return;
		}

		self::push_error(
			$campo,
			"El campo _{$descripcion}_ debe ser $comparacion o igual a $param_desc.",
			$errores
		);
	}

	/**
	 * Formato 1: 'fecha_menor_que:nombre_del_campo@descripcion_del_campo'
	 * Formato 2: 'fecha_menor_que:fecha'.
	 */
	private static function fecha_menor_que(
		$operacion,
		$metadato,
		$param,
		$config,
		&$errores
	) {
		$campo = $metadato['field'];
		$descripcion = $metadato['title'];
		$datos = $operacion['data'];

		if (empty($datos[$campo])) {
			return;
		}

		// 1. Chequeo si el campo contiene una fecha con o sin hora.
		$es_timestamp =
			isset($metadato['editorParams'])
			&& isset($metadato['editorParams']['mask'])
			&& $metadato['editorParams']['mask'] === 'datetime';
		$formato_fecha = $es_timestamp ? 'd/m/Y H:i:s' : 'd/m/Y';
		$formato_desc = $es_timestamp ? 'dd/mm/aaaa hora:min:seg' : 'dd/mm/aaaa';

		self::comparar_fechas(
			'menor',
			$campo,
			$descripcion,
			$param,
			$formato_fecha,
			$formato_desc,
			$datos,
			$errores
		);
	}

	/**
	 * Formato 1: 'fecha_mayor_que:nombre_del_campo@descripcion_del_campo'
	 * Formato 2: 'fecha_mayor_que:fecha'.
	 */
	private static function fecha_mayor_que(
		$operacion,
		$metadato,
		$param,
		$config,
		&$errores
	) {
		$campo = $metadato['field'];
		$descripcion = $metadato['title'];
		$datos = $operacion['data'];

		if (empty($datos[$campo])) {
			return;
		}

		// 1. Chequeo si el campo contiene una fecha con o sin hora.
		$es_timestamp =
			isset($metadato['editorParams'])
			&& isset($metadato['editorParams']['mask'])
			&& $metadato['editorParams']['mask'] === 'datetime';
		$formato_fecha = $es_timestamp ? 'd/m/Y H:i:s' : 'd/m/Y';
		$formato_desc = $es_timestamp ? 'dd/mm/aaaa hora:min:seg' : 'dd/mm/aaaa';

		self::comparar_fechas(
			'mayor',
			$campo,
			$descripcion,
			$param,
			$formato_fecha,
			$formato_desc,
			$datos,
			$errores
		);
	}

	private static function comparar_fechas(
		$tipo_comparacion,
		$campo,
		$descripcion,
		$param,
		$formato_fecha,
		$formato_desc,
		$datos,
		&$errores
	) {
		$valor = DateTime::createFromFormat($formato_fecha, $datos[$campo]);

		// 2.1. Verifico que el valor cargado sea una fecha sin hora
		if ($valor === false) {
			self::push_error(
				$campo,
				"El campo _{$descripcion}_ debe tener una fecha con el formato '$formato_desc'.",
				$errores
			);

			return;
		}

		$fecha_comparacion = DateTime::createFromFormat($formato_fecha, $param);

		// 2.1.2. Si el valor a comparar no es una fecha, entonces
		// tiene que ser un parámetro
		if ($fecha_comparacion === false) {
			list($param, $param_desc) = explode('@', $param);

			// Si el parámetro hace referencia a una columna,
			// y esa columna no tiene datos, no se hace nada.
			if (empty($datos[$param])) {
				return;
			}
			$fecha_comparacion = DateTime::createFromFormat(
				$formato_fecha,
				$datos[$param]
			);

			if ($fecha_comparacion === false) {
				self::push_error(
					$campo,
					"El campo _{$param_desc}_ debe tener una fecha con el formato $formato_desc.",
					$errores
				);

				return;
			}
		}

		if (
			$tipo_comparacion === 'menor' && $fecha_comparacion < $valor
			|| $tipo_comparacion === 'mayor' && $fecha_comparacion > $valor
		) {
			$param_desc = isset($param_desc) ? $param_desc : $param;

			self::push_error(
				$campo,
				"El campo _{$descripcion}_ debe ser $tipo_comparacion o igual a _{$param_desc}_.",
				$errores
			);
		}
	}

	private static function unico($operacion, $metadato, $param, $config, &$errores)
	{
		$campo = $metadato['field'];
		$descripcion = $metadato['title'];
		$datos = $operacion['data'];
		$original = $operacion['original'];

		if (!isset($datos[$campo]) || $datos[$campo] == '') {
			return;
		}
		$valor = $datos[$campo];

		if (
			isset($original)
			&& isset($original[$campo])
			&& $valor === $original[$campo]
		) {
			return;
		}
		$original = empty($original) ? $datos : $original;
		$claves = array_reduce(
			$config['columnas_clave'],
			function ($partial, $clave) use ($original) {
				$partial[$clave] = $original[$clave];

				return $partial;
			},
			[]
		);

		if (
			!principal_ei_tabulator_consultar::si_valor_existe(
				$config['tabla_nombre'],
				$claves
			)
		) {
			return;
		}

		self::push_error(
			$campo,
			"El valor \"$valor\" del campo _{$descripcion}_ ya existe en el sistema.",
			$errores
		);
	}
}
