<?php

class dao_totales {


	public static function total_acumulado_egreso($cod_unidad_administracion,$id_ejercicio,$id_entidad,$id_programa,$cod_partida,$cod_fuente_financiera,$cod_recurso,$etapa){

		$sql = "BEGIN :resultado := pkg_pr_totales.total_acumulado_egreso(:cod_unidad_administracion, :id_ejercicio, :id_entidad, :id_programa, :cod_partida, :cod_fuente_financiera, :cod_recurso, :etapa, sysdate);END;";        	
		$parametros = array ( array(  'nombre' => 'cod_unidad_administracion', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $cod_unidad_administracion),
							  array(  'nombre' => 'id_ejercicio', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $id_ejercicio),
							  array(  'nombre' => 'id_entidad', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $id_entidad),
							  array(  'nombre' => 'id_programa', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $id_programa),
							  array(  'nombre' => 'cod_partida', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $cod_partida),
							  array(  'nombre' => 'cod_fuente_financiera', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $cod_fuente_financiera),
							  array(  'nombre' => 'cod_recurso', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $cod_recurso),
							  array(  'nombre' => 'etapa', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $etapa),
							  array(  'nombre' => 'resultado', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 4000,
		                              'valor' => ''));

		$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', false);
		return $resultado[count($resultado)-1]['valor'];

	}

	public static function saldo_acumulado_egreso ($cod_unidad_administracion,$id_ejercicio,$id_entidad,$id_programa,$cod_partida,$cod_fuente_financiera,$cod_recurso,$etapa){
		$sql = "BEGIN :resultado := pkg_pr_totales.saldo_acumulado_egreso(:cod_unidad_administracion, :id_ejercicio, :id_entidad, :id_programa, :cod_partida, :cod_fuente_financiera, :cod_recurso, :etapa, sysdate);END;";        	
		$parametros = array ( array(  'nombre' => 'cod_unidad_administracion', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $cod_unidad_administracion),
							  array(  'nombre' => 'id_ejercicio', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $id_ejercicio),
							  array(  'nombre' => 'id_entidad', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $id_entidad),
							  array(  'nombre' => 'id_programa', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $id_programa),
							  array(  'nombre' => 'cod_partida', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $cod_partida),
							  array(  'nombre' => 'cod_fuente_financiera', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $cod_fuente_financiera),
							  array(  'nombre' => 'cod_recurso', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $cod_recurso),
							  array(  'nombre' => 'etapa', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $etapa),
							  array(  'nombre' => 'resultado', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 4000,
		                              'valor' => ''));

		$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', false);
		return $resultado[count($resultado)-1]['valor'];
	}

	public static function total_acumulado_ingreso_fte($cod_unidad_administracion,$id_ejercicio,$cod_fuente_financiera,$etapa){
		$sql = "BEGIN :resultado := pkg_pr_totales.total_acumulado_ingreso_fte(:cod_unidad_administracion, :id_ejercicio, :cod_fuente_financiera, :etapa, sysdate);END;"; 

		$parametros = array ( array(  'nombre' => 'cod_unidad_administracion', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $cod_unidad_administracion),
							  array(  'nombre' => 'id_ejercicio', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $id_ejercicio),
							  array(  'nombre' => 'cod_fuente_financiera', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $cod_fuente_financiera),
							  array(  'nombre' => 'etapa', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $etapa),
							  array(  'nombre' => 'resultado', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 4000,
		                              'valor' => ''));

		$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', false);
		return $resultado[count($resultado)-1]['valor'];

	}

	public static function total_acumulado_egreso_fte($cod_unidad_administracion,$id_ejercicio,$cod_fuente_financiera,$cod_recurso,$etapa){
		$sql = "BEGIN :resultado := pkg_pr_totales.total_acumulado_egreso_fte(:cod_unidad_administracion, :id_ejercicio, :cod_fuente_financiera, :cod_recurso, :etapa, sysdate);END;"; 

		$parametros = array ( array(  'nombre' => 'cod_unidad_administracion', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $cod_unidad_administracion),
							  array(  'nombre' => 'id_ejercicio', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $id_ejercicio),
							  array(  'nombre' => 'cod_fuente_financiera', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $cod_fuente_financiera),
							  array(  'nombre' => 'etapa', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $etapa),
							  array(  'nombre' => 'cod_recurso', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $cod_recurso),
							  array(  'nombre' => 'resultado', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 4000,
		                              'valor' => ''));

		$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', false);
		return $resultado[count($resultado)-1]['valor'];

	}

	public static function saldo_acumulado_egreso_fte($cod_unidad_administracion,$id_ejercicio,$cod_fuente_financiera,$cod_recurso,$etapa){
		$sql = "BEGIN :resultado := pkg_pr_totales.saldo_acumulado_egreso_fte(:cod_unidad_administracion, :id_ejercicio, :cod_fuente_financiera, :cod_recurso, :etapa, sysdate);END;"; 

		$parametros = array ( array(  'nombre' => 'cod_unidad_administracion', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $cod_unidad_administracion),
							  array(  'nombre' => 'id_ejercicio', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $id_ejercicio),
							  array(  'nombre' => 'cod_fuente_financiera', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $cod_fuente_financiera),
							  array(  'nombre' => 'cod_recurso', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $cod_recurso),
							  array(  'nombre' => 'etapa', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $etapa),
							  array(  'nombre' => 'resultado', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 4000,
		                              'valor' => ''));

		$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', false);
		return $resultado[count($resultado)-1]['valor'];

	}


	public static function total_acumulado_ingreso ($cod_unidad_administracion,$id_ejercicio,$id_entidad,$cod_recurso,$etapa){
		$sql = "BEGIN :resultado := pkg_pr_totales.total_acumulado_ingreso(:cod_unidad_administracion, :id_ejercicio, :id_entidad, :cod_recurso, :etapa, sysdate);END;";        	
		$parametros = array ( array(  'nombre' => 'cod_unidad_administracion', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $cod_unidad_administracion),
							  array(  'nombre' => 'id_ejercicio', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $id_ejercicio),
							  array(  'nombre' => 'id_entidad', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $id_entidad),
							  array(  'nombre' => 'cod_recurso', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $cod_recurso),
							  array(  'nombre' => 'etapa', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 12,
		                              'valor' => $etapa),
							  array(  'nombre' => 'resultado', 
		                              'tipo_dato' => PDO::PARAM_STR,
		                              'longitud' => 4000,
		                              'valor' => ''));

		$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', false);
		return $resultado[count($resultado)-1]['valor'];
	}
}

?>