<?php

class dao_configuraciones_as {
	
	public static function get_global (){
		//Se controla por trigger que sea Single Record.
		$sql = "SELECT *
				FROM AS_GLOBALES";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}	
	
	public static function get_primitivas ($filtro = array()){
		$sql = "SELECT * FROM AS_PRIMITIVAS";
		return toba::db()->consultar($sql);
	}
	
	public static function get_parametros ($filtro = array()){
		$sql = "SELECT * FROM AS_PARAMETROS";
		return toba::db()->consultar($sql);
	}
	
}

?>
