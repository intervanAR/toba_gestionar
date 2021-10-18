<?php

/**
 * @author lgraziani
 */
class test_ctr_construir_sentencias extends toba_test
{
	private static $snap;

	public function setUp()
	{
		self::$snap = new snapshot_tester(__DIR__, $this);
	}

	/**
	 * @override
	 */
	public static function get_descripcion()
	{
		return 'modelo/generico/ctr_construir_sentencias';
	}

	public function test__sql_update()
	{
		$tabla = 'RE_CALLES';
		$columnas = [
			'columna1' => 'valor1',
			'columna2' => 2,
			'columna3' => 'valor3',
			'columna4' => 4,
		];
		$where = 'id = 1';
		$sql = ctr_construir_sentencias::sql_update($tabla, $columnas, $where);

		self::$snap->match_snapshot($sql);
	}

	public function test__construir_sentencia_insert()
	{
		$tabla = 'RE_CALLES';
		$columnas = [
			'columna1' => 'valor1',
			'columna2' => 2,
			'columna3' => 'valor3',
			'columna4' => 4,
		];
		$sql = ctr_construir_sentencias::construir_sentencia_insert($tabla, $columnas);

		self::$snap->match_snapshot($sql);
	}
}
