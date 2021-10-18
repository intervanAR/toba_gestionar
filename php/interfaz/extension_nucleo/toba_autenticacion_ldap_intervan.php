<?php 

class toba_autenticacion_ldap_intervan extends toba_autenticacion_ldap
{
	protected $port;

	function __construct($server=null, $dn=null) {
	    parent::__construct($server, $dn);

	    $this->port = 389;

		//--- Levanto la CONFIGURACION de ldap.ini
		$archivo_ini_instalacion = toba::nucleo()->toba_instalacion_dir().'/ldap.ini';
		if (is_file( $archivo_ini_instalacion ) ) {
			$datos = parse_ini_file( $archivo_ini_instalacion,true);
			if (isset($datos['basicos']['port'])) {
				$this->port = $datos['basicos']['port'];
			}
		}
	}
	
	/**
	*	Realiza la autentificacion utilizando un servidor LDAP
	*	@return $value	Retorna TRUE o FALSE de acuerdo al estado de la autentifiacion
	*/
	function autenticar($id_usuario, $clave, $datos_iniciales=null)
	{
	    if (! extension_loaded('ldap')) {
			throw new toba_error("[Autenticación LDAP] no se encuentra habilitada la extensión LDAP");
		}
		
		$conexion = @ldap_connect($this->server, $this->port);
		ldap_set_option($conexion, LDAP_OPT_PROTOCOL_VERSION, 3);
		if (! $conexion) {
			toba::logger()->error('[Autenticación LDAP] No es posible conectarse con el servidor: '.ldap_error($conexion));
			return false;
		}
		//$bind = @ldap_bind($conexion);
		$bind = @ldap_bind($conexion, $this->bind_dn, $this->bind_pass); 
		if (! $bind) {
			toba::logger()->error('[Autenticación LDAP] No es posible conectarse con el servidor: '.ldap_error($conexion));
			return false;
		}

		$usuario_dn = dao_usuarios_ldap::get_usuario_dn($id_usuario);
		if ($usuario_dn == false) {
			toba::logger()->error("[Autenticación LDAP] No pude obtenerse el DN del usuario: ". ldap_error($conexion));
			return false;
		}

		$link_id = @ldap_bind($conexion, $usuario_dn, $clave);
		if ($link_id == false) {
			toba::logger()->error("[Autenticación LDAP] Usuario/Contraseña incorrecta: ".ldap_error($conexion));
			return false;
		}
		ldap_close($conexion);
		toba::logger()->debug("[Autenticación LDAP] OK");
		return true;
	}
}

?>