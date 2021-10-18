<?php
/**
 * @author lgraziani
 */
final class Sequelvan
{
	public function __construct($joins)
	{
		throw new toba_error('La clase Sequelvan es estática');
	}

	public static function consultar($sql, $parametros = [], $config = [])
	{
		toba::logger()->debug('================= Sequelvan consultar() START =================');

		$config = is_array($config) ? $config : [];
		$config['clob'] = isset($config['clob']) ? $config['clob'] : [];
		$config['blob'] = isset($config['blob']) ? $config['blob'] : [];
		$config['todo'] = true;

		$sent = self::pdo()->prepare(self::restriccion_funcional($sql));

		self::bind_params($parametros, $sent);

		try {
			// TODO Agregar try/catch con notificaciones amigables
			$sent->execute();

			// TODO Remover cuando pasemos a PHP 5.5 o superior
			toba::logger()->debug('================= Sequelvan consultar() FINISH =================');

			return self::fetch_with_lobs($sent, $config);
		} catch (Exception $err) {
			SequelvanError::procesar($err, $sent);

			// TODO Mover al finally cuando pasemos a PHP 5.5 o superior
			toba::logger()->debug('================= Sequelvan consultar() FINISH =================');
		}
	}

	public static function consultar_fila($sql, $parametros = [], $config = [])
	{
		toba::logger()->debug('================= Sequelvan consultar_fila() START =================');

		$config = is_array($config) ? $config : [];
		$config['clob'] = isset($config['clob']) ? $config['clob'] : [];
		$config['blob'] = isset($config['blob']) ? $config['blob'] : [];
		$config['todo'] = false;

		$sent = self::pdo()->prepare(self::restriccion_funcional($sql));

		self::bind_params($parametros, $sent);

		try {
			// TODO Agregar try/catch con notificaciones amigables
			$sent->execute();

			// TODO Remover cuando pasemos a PHP 5.5 o superior
			toba::logger()->debug('================= Sequelvan consultar_fila() FINISH =================');

			return self::fetch_with_lobs($sent, $config);
		} catch (Exception $err) {
			SequelvanError::procesar($err, $sent);

			// TODO Mover al finally cuando pasemos a PHP 5.5 o superior
			toba::logger()->debug('================= Sequelvan consultar_fila() FINISH =================');
		}
	}

	public static function ejecutar($sql, $parametros = [])
	{
		toba::logger()->debug('================= Sequelvan ejecutar() START =================');
		$sent = self::pdo()->prepare($sql);

		self::bind_params($parametros, $sent);

		try {
			// TODO Agregar try/catch con notificaciones amigables
			$sent->execute();

			// TODO Remover cuando pasemos a PHP 5.5 o superior
			toba::logger()->debug('================= Sequelvan ejecutar() FINISH =================');

			return $sent->rowCount();
		} catch (Exception $err) {
			SequelvanError::procesar($err, $sent);

			// TODO Mover al finally cuando pasemos a PHP 5.5 o superior
			toba::logger()->debug('================= Sequelvan ejecutar() FINISH =================');
		}
	}

	private static function pdo()
	{
		return toba::db()->get_pdo();
	}

	private static function restriccion_funcional($sql)
	{
		return toba::perfil_de_datos()->filtrar($sql);
	}

	/**
	 * Refactorizar cuando se solucione https://bugs.php.net/bug.php?id=46728
	 *
	 * La solución ideal debería no tener que hacer un fetch por cada
	 * fila, sino más bien recuperar todo, y por cada fila, procesar los recursos.
	 */
	private static function fetch_with_lobs($sent, $config)
	{
		if (empty($config['clob']) && empty($config['blob'])) {
			$method = $config['todo'] ? 'fetchAll' : 'fetch';

			return $sent->$method(PDO::FETCH_ASSOC);
		}
		if (!$config['todo']) {
			return self::process_blob(
				$sent->fetch(PDO::FETCH_ASSOC),
				$config
			);
		}
		$datos = [];

		while ($row = $sent->fetch(PDO::FETCH_ASSOC)) {
			$datos[] = self::process_blob($row, $config);
		}

		return $datos;
	}

	private static function process_blob($row, $config)
	{
		foreach ($row as $name => $value) {
			if (in_array($name, $config['clob']) && is_resource($value)) {
				$row[$name] = stream_get_contents($value);
			}
			if (in_array($name, $config['blob']) && is_resource($value)) {
				$row[$name] = is_null($value)
					? $value
					: base64_encode(stream_get_contents($value));
			}
		}

		return $row;
	}

	private static function bind_params($parametros, &$sent)
	{
		toba::logger()->debug($parametros);

		array_walk($parametros, function($param) use(&$sent) {
			// TODO Validar estructura de parámetros

			$sent->bindParam(
				$param['nombre'],
				$param['valor'],
				isset($param['tipo']) ? $param['tipo'] : PDO::PARAM_STR
			);
		});
	}
}
