<?php
/**
 * @author lgraziani
 */
final class SequelvanError
{
	public static function procesar($err, $sent)
	{
		$code = "err_{$sent->errorInfo()[1]}";

		if (!method_exists('SequelvanError', $code)) {
			$code = 'defecto';
		}
		toba::logger()->crit($err);

		self::$code($err);
	}

	private static function err_907()
	{
		throw new toba_error('La consulta está mal construida: falta paréntesis derecho.');
	}

	private static function err_904($err)
	{
		throw new toba_error("La consulta está mal construida: \n\n{$err->getMessage()}");
	}

	private static function err_918()
	{
		throw new toba_error('La consulta a la base de datos tiene columnas definidas de forma ambigua');
	}

	private static function err_1008()
	{
		throw new toba_error('La consulta a la base de datos no tiene todas las variables definidas');
	}

	private static function defecto($err)
	{
		throw new toba_error("La consulta a la base de datos falló: \n\n{$err->getMessage()}");
	}
}
