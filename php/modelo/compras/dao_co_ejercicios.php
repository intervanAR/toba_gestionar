<?php 

class dao_co_ejercicios 
{

	static public function get_ejercicios ($filtro = [], $orden = [])
	{
		$where = " 1=1 ";

		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'coe', ' 1=1 ');

		$sql = "SELECT coe.*, DECODE (coe.cerrado, 'S', 'Si', 'No') cerrado_format,
				       DECODE (coe.abierto, 'S', 'Si', 'No') abierto_format,
				       DECODE (coe.cerrado_parcial, 'S', 'Si', 'No') cerrado_parcial_format
				  FROM co_ejercicios coe
				 WHERE $where
				 order by coe.anio desc ";

		return toba::db()->consultar($sql);
	}

	static public function cierre_final ($anio, $con_transaccion = true)
	{
		$sql = "BEGIN :resultado := pkg_kr_ejercicios.compra_cierre_final(:anio); END;";
		$parametros = array(array(	'nombre' => 'anio',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $anio),
							array(	'nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''),);

		$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', '', $con_transaccion);
		return $resultado[1]['valor'];
	}

	static public function cierre_parcial ($anio, $con_transaccion = true)
	{
		$sql = "BEGIN :resultado := pkg_kr_ejercicios.compra_cierre_parcial(:anio); END;";
		$parametros = array(array(	'nombre' => 'anio',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $anio),
							array(	'nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''),);

		$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', '', $con_transaccion);
		return $resultado[1]['valor'];
	}

	static public function apertura ($anio, $con_transaccion = true)
	{
		$sql = "BEGIN :resultado := pkg_kr_ejercicios.compra_apertura(:anio); END;";
		$parametros = array(array(	'nombre' => 'anio',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $anio),
							array(	'nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''),);

		$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', '', $con_transaccion);
		return $resultado[1]['valor'];
	}

}

 ?>