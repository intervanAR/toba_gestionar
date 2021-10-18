<?php 
class dao_procesos {

	static public function refundicion_ctas_resultado($id_ejercicio, $con_transaccion = true)
	{
		$sql = "BEGIN :resultado := PKG_PROCESO.refundicion_ctas_resultado(:id_ejercicio); END;";
		$parametros = [[
			'nombre' => 'resultado',
			'tipo_dato' => PDO::PARAM_STR,
			'longitud' => 4000,
			'valor' => '',
		],[
			'nombre' => 'id_ejercicio',
			'tipo_dato' => PDO::PARAM_INT,
			'longitud' => 32,
			'valor' => $id_ejercicio,
		],];
		ctr_procedimientos::ejecutar_procedimiento('Refundición Cuentas', $sql, $parametros, $con_transaccion);
	}

	static public function asiento_cierre_ejercicio($id_ejercicio, $con_transaccion = true)
	{
		$sql = "BEGIN :resultado := PKG_PROCESO.asiento_cierre_ejercicio(:id_ejercicio); END;";
		$parametros = [[
			'nombre' => 'resultado',
			'tipo_dato' => PDO::PARAM_STR,
			'longitud' => 4000,
			'valor' => '',
		],[
			'nombre' => 'id_ejercicio',
			'tipo_dato' => PDO::PARAM_INT,
			'longitud' => 32,
			'valor' => $id_ejercicio,
		],];
		ctr_procedimientos::ejecutar_procedimiento('Cierre del Ejercicio', $sql, $parametros, $con_transaccion);
	}

	static public function asiento_apertura_ejercicio($id_ejercicio, $con_transaccion = true)
	{
		$sql = "BEGIN :resultado := PKG_PROCESO.asiento_apertura_ejercicio(:id_ejercicio); END;";
		$parametros = [[
			'nombre' => 'resultado',
			'tipo_dato' => PDO::PARAM_STR,
			'longitud' => 4000,
			'valor' => '',
		],[
			'nombre' => 'id_ejercicio',
			'tipo_dato' => PDO::PARAM_INT,
			'longitud' => 32,
			'valor' => $id_ejercicio,
		],];
		ctr_procedimientos::ejecutar_procedimiento('Apertura del Ejercicio', $sql, $parametros, $con_transaccion);
	}

	static public function renumerar_asientos($id_ejercicio, $con_transaccion = true)
	{
		$sql = "BEGIN :resultado := PKG_PROCESO.renumerar_asientos(:id_ejercicio); END;";
		$parametros = [[
			'nombre' => 'resultado',
			'tipo_dato' => PDO::PARAM_STR,
			'longitud' => 4000,
			'valor' => '',
		],[
			'nombre' => 'id_ejercicio',
			'tipo_dato' => PDO::PARAM_INT,
			'longitud' => 32,
			'valor' => $id_ejercicio,
		],];
		ctr_procedimientos::ejecutar_procedimiento('Renumerar Asientos', $sql, $parametros, $con_transaccion);
	}
}

?>