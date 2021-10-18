<?php

class usuario_intervan extends toba_usuario_basico
{
	
	//----------------------------------------------------------------------------------
	
	function __construct($id_usuario)
	{
		
		// registrar el autoload del proyecto principal
		$proyecto = toba::proyecto()->get_id();
		if ($proyecto != 'principal') {
			$pm = toba::puntos_montaje()->get('principal');
		} else {
			$pm = toba::puntos_montaje()->get('proyecto');
		}
		$pm->registrar_autoload();
		
		dao_usuarios_intervan::sincronizar_usuario_ldap($id_usuario, toba::proyecto()->get_id());
		
		parent::__construct($id_usuario);
		
		if (toba_usuario::verificar_clave_vencida($id_usuario)) {
			throw new  toba_error_login_contrasenia_vencida('La contrase�a actual del usuario ha caducado');
		}
		
		// Libero la conexion a la base de datos Oracle para forzar una reconexion
		if (method_exists(toba::fuente(), 'destruir_db')) {
		    toba::fuente()->destruir_db();
        }
	}
	
	static function existe_usuario($id_usuario)
	{
		dao_usuarios_intervan::sincronizar_usuario_ldap($id_usuario, toba::proyecto()->get_id());
		return parent::existe_usuario($id_usuario);
	}
	
}
?>