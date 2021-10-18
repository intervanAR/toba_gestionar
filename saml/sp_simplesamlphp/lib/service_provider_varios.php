<?php

function obtener_proyecto_actual()
{
	$ini = parse_ini_file(dirname(__FILE__).'/../../../../../instalacion/saml.ini',true);
	if (isset($_SERVER['REQUEST_URI']) && isset($ini['basicos']['alias_sp_principal']) && strpos($_SERVER['REQUEST_URI'], $ini['basicos']['alias_sp_principal']) !== false){
		$proyecto = 'principal';
	} elseif (isset($_SERVER['REQUEST_URI']) && isset($ini['basicos']['alias_sp_administracion']) && strpos($_SERVER['REQUEST_URI'], $ini['basicos']['alias_sp_administracion']) !== false){
		$proyecto = 'administracion';
	} elseif (isset($_SERVER['REQUEST_URI']) && isset($ini['basicos']['alias_sp_compras']) && strpos($_SERVER['REQUEST_URI'], $ini['basicos']['alias_sp_compras']) !== false){
		$proyecto = 'compras';
	} elseif (isset($_SERVER['REQUEST_URI']) && isset($ini['basicos']['alias_sp_costos']) && strpos($_SERVER['REQUEST_URI'], $ini['basicos']['alias_sp_costos']) !== false){
		$proyecto = 'costos';
	} elseif (isset($_SERVER['REQUEST_URI']) && isset($ini['basicos']['alias_sp_contabilidad']) && strpos($_SERVER['REQUEST_URI'], $ini['basicos']['alias_sp_contabilidad']) !== false){
		$proyecto = 'contabilidad';
	} elseif (isset($_SERVER['REQUEST_URI']) && isset($ini['basicos']['alias_sp_presupuesto']) && strpos($_SERVER['REQUEST_URI'], $ini['basicos']['alias_sp_presupuesto']) !== false){
		$proyecto = 'presupuesto';
	} elseif (isset($_SERVER['REQUEST_URI']) && isset($ini['basicos']['alias_sp_rrhh']) && strpos($_SERVER['REQUEST_URI'], $ini['basicos']['alias_sp_rrhh']) !== false){
		$proyecto = 'rrhh';
	} elseif (isset($_SERVER['REQUEST_URI']) && isset($ini['basicos']['alias_sp_rentas']) && strpos($_SERVER['REQUEST_URI'], $ini['basicos']['alias_sp_rentas']) !== false){
		$proyecto = 'rentas';
	} elseif (isset($_SERVER['REQUEST_URI']) && isset($ini['basicos']['alias_sp_prueba']) && strpos($_SERVER['REQUEST_URI'], $ini['basicos']['alias_sp_prueba']) !== false){
		$proyecto = 'prueba';
	} else {
		$proyecto = isset($_SERVER['TOBA_PROYECTO'])?$_SERVER['TOBA_PROYECTO']:'';
	}
	return $proyecto;
}

?>
