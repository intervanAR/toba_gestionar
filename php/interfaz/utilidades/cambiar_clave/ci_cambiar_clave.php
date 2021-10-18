<?php
class ci_cambiar_clave extends principal_ci
{
	protected $s__usuario;
	protected $s__forzar_cambio;
	protected $s__datos;
	
	function ini()
	{
		parent::ini();
	}

	function ini__operacion()
	{
		$forzar_cambio = toba::memoria()->get_parametro('forzar_cambio');
		if (isset($forzar_cambio) && !empty($forzar_cambio)) {
			$this->s__forzar_cambio = $forzar_cambio;
		} else {
			$this->s__forzar_cambio = null;
		}
		$usuario = toba::memoria()->get_parametro('usuario');
		if (isset($usuario) && !empty($usuario)) {
			$this->s__usuario = $usuario;
		} else {
			$this->s__usuario = toba::usuario()->get_id();
		}
		
		parent::ini__operacion();
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
	}

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		if (isset($this->s__forzar_cambio) && !empty($this->s__forzar_cambio)) {
			$pantalla->evento('cancelar')->ocultar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__guardar()
	{
		if (isset($this->s__forzar_cambio) && !empty($this->s__forzar_cambio)) { // cambio de clave desde el login
			if (toba::manejador_sesiones()->invocar_autenticar($this->s__usuario, $this->s__datos['password_anterior'], null)) {					//Si la clave anterior coincide	
				//Verifico que no intenta volver a cambiarla antes del periodo permitido
				$dias_minimos = toba::proyecto()->get_parametro('proyecto', 'dias_minimos_validez_clave', false);
				if (! is_null($dias_minimos)) {
					if (! toba_usuario::verificar_periodo_minimo_cambio($this->s__usuario, $dias_minimos)) {
						toba::notificacion()->agregar('No transcurrio el período minimo para poder volver a cambiar su contraseña. Intentelo en otra ocasión');
						return;
					}
				}		
				try {
					$this->cambiar_clave($this->s__usuario, $this->s__datos['password']);
				} catch(toba_error_pwd_conformacion_invalida $e) {
					toba::logger()->info($e->getMessage());
					toba::notificacion()->agregar($e->getMessage(), 'error');
					return;
				}
			} else {
				throw new toba_error_usuario('La clave ingresada no es correcta');
			}
			toba::vinculador()->navegar_a('principal', '1000290', array('logout' => '1'));
		} else { // cambio de clave desde la opcion del sistema
			$this->cambiar_clave($this->s__usuario, $this->s__datos['password']);
		}
	}
	
	function evt__cancelar()
	{
		toba::vinculador()->navegar_a('principal', '2');
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(principal_ei_formulario $form)
	{
		if (isset($this->s__forzar_cambio) && !empty($this->s__forzar_cambio)) {
		} else {
			$form->desactivar_efs(array('password_anterior'));
		}
		//$largo_clave = toba::instancia()->get_largo_minimo_password();
		//$form->ef('password')->set_expreg(toba_usuario::get_exp_reg_pwd($largo_clave));
		//$form->ef('password')->set_descripcion("La clave debe tener no menos de $largo_clave caracteres, entre letras mayusculas, minusculas, numeros y simbolos");
	}

	function evt__formulario__modificacion($datos)
	{
		//toba_usuario::verificar_composicion_clave($datos['password'], toba::instancia()->get_largo_minimo_password());
		$this->s__datos = $datos;
	}
	
	private function cambiar_clave($usuario, $password) {
		try {
			//Obtengo los dias de validez de la nueva clave
			$dias = toba::proyecto()->get_parametro('proyecto', 'dias_validez_clave', false);
			//toba_usuario::verificar_clave_no_utilizada($password, $usuario);
			toba_usuario::reemplazar_clave_vencida($password, $usuario, $dias);
			if (isset($password)) {
				dao_usuarios_ldap::set_password_usuario($usuario, $password);
				toba::notificacion()->info('La contraseña se modifico exitosamente.');
			}
		} catch (toba_error $e) {
			toba::logger()->error($e->get_mensaje());
			toba::notificacion()->error($e->get_mensaje());
		}
	}

}

?>