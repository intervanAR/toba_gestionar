<?php

class dao_usuarios_toba
{

	static function existe_usuario_toba($nombre_usuario)
	{
	    $datos_usuario = toba::instancia()->get_info_autenticacion($nombre_usuario);
		if (!empty($datos_usuario)) {
		    return true;
		} else {
		    return false;
		}
	}
	
	static function crear_usuario_toba($nombre_usuario, $nombres, $email, $proyecto, $grupos_acceso=array(), $metodo_autentificacion='bcrypt')
	{
		if (self::existe_usuario_toba($nombre_usuario)) {
			throw new toba_error_usuario('Ya existe un usuario toba registrado con ese ID, intente otro por favor');
		} else {
			try {
				toba::instancia()->get_db()->abrir_transaccion();
				
				$clave = encriptar_con_sal(self::randomPassword(), $metodo_autentificacion);

				//DOY DE ALTA EL USUARIO EN LA INSTANCIA
				$sql = "INSERT INTO apex_usuario(usuario, clave, nombre, autentificacion, email) 
						VALUES ('{$nombre_usuario}', '$clave', '{$nombres}', '{$metodo_autentificacion}', '{$email}')";

				toba::logger()->debug("Alta usuario toba: $sql");
				toba::instancia()->get_db()->ejecutar($sql);
				
				self::modificar_grupos_acceso_toba($nombre_usuario, $proyecto, $grupos_acceso, false);

				toba::instancia()->get_db()->cerrar_transaccion();
			} catch (toba_error $e) {
				toba::instancia()->get_db()->abortar_transaccion();
				throw $e;
			}
		}
	}
	
	static function get_grupos_acceso_usuario($nombre_usuario, $proyecto) 
	{
		if (isset($nombre_usuario)) {
			$sql = "SELECT *
					FROM apex_usuario_proyecto
					WHERE proyecto = " . quote($proyecto) . "
					AND usuario = " . quote($nombre_usuario) . ";";
			return toba::instancia()->get_db()->consultar($sql);
		} else {
			return array();
		}
	}
	
	static function get_perfiles_funcionales_proyecto($proyecto)
	{
		$proyecto_quote = quote($proyecto);
		$sql = "SELECT	uga.usuario_grupo_acc as  grupo_acceso
				FROM 	apex_usuario_grupo_acc uga
				WHERE	uga.proyecto = $proyecto_quote
				ORDER BY uga.usuario_grupo_acc ASC;";
		$grupos_acceso = toba::instancia()->get_db()->consultar($sql);
		return ctr_funciones_basicas::matriz_to_array($grupos_acceso, 'grupo_acceso');
	}
	
	static function modificar_grupos_acceso_toba($nombre_usuario, $proyecto, $grupos_acceso=array(), $con_transaccion = true)
	{
		if (!self::existe_usuario_toba($nombre_usuario)) {
			throw new toba_error_usuario('No existe un usuario toba registrado con ese ID para modificar los perfiles funcionales');
		} else {
			try {
				if ($con_transaccion) {
					toba::instancia()->get_db()->abrir_transaccion();
				}
				
				$perfiles_funcionales_toba = self::get_perfiles_funcionales_proyecto($proyecto);
				//ELIMINO LOS PERFILES FUNCIONALES
				$sql = "DELETE 
						FROM apex_usuario_proyecto
						WHERE proyecto = " . quote($proyecto) . "
						AND usuario = " . quote($nombre_usuario) . ";";

				toba::logger()->debug("Elimino los grupos del usuario: $sql");
				toba::instancia()->get_db()->ejecutar($sql);
				
				// ASIGNO LOS PERFILES FUNCIONALES
				foreach ($grupos_acceso as $grupo_acceso) {
					if (!in_array($grupo_acceso, $perfiles_funcionales_toba)) {
						toba::logger()->error("No existe el perfil funcional $grupo_acceso en el proyecto $proyecto. Contactese con el administrador.");
					} else {
						$sql_proy = "INSERT INTO apex_usuario_proyecto(proyecto, usuario, usuario_grupo_acc, usuario_perfil_datos) 
									VALUES('{$proyecto}', '{$nombre_usuario}', '$grupo_acceso', 'no')";

						toba::logger()->debug("Asigno perfil de acceso al proyecto: $sql_proy");
						toba::instancia()->get_db()->ejecutar($sql_proy);
					}
				}
				if ($con_transaccion) {
					toba::instancia()->get_db()->cerrar_transaccion();
				}
			} catch (toba_error $e) {
				if ($con_transaccion) {
					toba::instancia()->get_db()->abortar_transaccion();
				}
				throw $e;
			}
		}
	}
	
	static function get_perfiles_funcionales_usuario($usuario, $proyecto) {
		$modulos = dao_usuarios_ldap::get_modulos_intervan($proyecto);
		if (empty($modulos)) {
			$grupos_acceso = ctr_funciones_basicas::matriz_to_array(self::get_grupos_acceso_usuario($usuario, $proyecto), 'usuario_grupo_acc');
		} else {
			$grupos_acceso = array();
			foreach ($modulos as $modulo) {
				$grupos_acceso = array_merge($grupos_acceso, dao_usuarios_ldap::get_perfiles_funcionales($usuario, $modulo));
			}
		}
		return $grupos_acceso;
	}
	
	private static function randomPassword() {
		$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		$pass = array(); //remember to declare $pass as an array
		$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
		for ($i = 0; $i < 8; $i++) {
			$n = rand(0, $alphaLength);
			$pass[] = $alphabet[$n];
		}
		return implode($pass); //turn the array into a string
	}
	
}

?>
