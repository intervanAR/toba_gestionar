<?php

class dao_configuracion_ldap
{
	static function get_config_ldap() {
		$archivo = toba::nucleo()->toba_instalacion_dir().'/ldap_usuarios_intervan.ini';
		$ini_default = array(
			'basicos' => array(
				'port' => 389,
				'usuario' => 'USUARIO',
				'password' => 'PASSWORD',
				'validar_ip' => 0,
			),
			'datos_usuario' => array(
				'object_class' => 'top,person,posixAccount,inetOrgPerson,organizationalPerson',
				'base_dn_cliente' => '',
				'base_dn_partners' => '',
			),
			'datos_ip' => array(
//				'object_class' => 'top,person,posixAccount,inetOrgPerson,organizationalPerson',
				'base_dn' => '',
                'filter' => '(|(cn=%s))',
			),
			'perfiles_funcionales' => array(
				'filter_perfil' => '(|(cn=%s))',
				'object_class' => 'top,posixGroup',
				'prefijo_afi' => '',
				'prefijo_rentas' => '',
				'prefijo_rrhh' => '',
				'prefijo_ventas_agua' => '',
				'prefijo_sociales' => '',
				'base_dn_usuarios_ldap' => '',
				'prefijo_usuarios_ldap' => '',
			),
			'unidades_administracion' => array(
				'filter_perfil' => '(|(cn=%s))',
			),
			'unidades_ejecutoras' => array(
				'filter_perfil' => '(|(cn=%s))',
			),
			'ambitos_compra' => array(
				'filter_perfil' => '(|(cn=%s))',
			),
			'sectores_compra' => array(
				'filter_perfil' => '(|(cn=%s))',
				'object_class' => 'top,posixGroup',
			),
			'dependencias_rentas' => array(
				'filter_perfil' => '(|(cn=%s))',
			),
			'rrhh_ent_org' => array(
				'base_dn' => "ou=OR,ou=RRHH,ou=Servicios,ou=Sectores,dc=munirn,dc=com",
				'filter' => "(&(objectClass=posixGroup)(memberUid=%s))",
				'prefijo' => "or_",
				'filter_perfil' => '(|(cn=%s))',
				'digitos_organizacion' => 2,
				'object_class' => 'top,posixGroup',
			),
			'ventas_agua_emp_suc' => array(
				'base_dn' => "ou=VentasAgua,ou=Servicios,ou=Sectores,dc=munirn,dc=com",
				'filter' => "(&(objectClass=posixGroup)(memberUid=%s))",
				'prefijo' => "su_",
				'filter_perfil' => '(|(cn=%s))',
				'digitos_sucursal' => 3,
				'object_class' => 'top,posixGroup',
			),
			'sociales' => array(
				'base_dn' => "ou=SS,ou=Sociales,ou=Servicios,ou=Sectores,dc=munirn,dc=com",
				'filter' => "(&(objectClass=posixGroup)(memberUid=%s))",
				'prefijo' => "ss_",
				'filter_perfil' => '(|(cn=%s))',
			),
		);
		$ini = parse_ini_file($archivo, true);
		$ini['basicos'] = array_merge($ini_default['basicos'], $ini['basicos']);
		$ini['datos_usuario'] = array_merge($ini_default['datos_usuario'], $ini['datos_usuario']);
		$ini['perfiles_funcionales'] = array_merge($ini_default['perfiles_funcionales'], $ini['perfiles_funcionales']);
		$ini['unidades_administracion'] = array_merge($ini_default['unidades_administracion'], $ini['unidades_administracion']);
		$ini['unidades_ejecutoras'] = array_merge($ini_default['unidades_ejecutoras'], $ini['unidades_ejecutoras']);
		$ini['ambitos_compra'] = array_merge($ini_default['ambitos_compra'], $ini['ambitos_compra']);
		$ini['sectores_compra'] = array_merge($ini_default['sectores_compra'], $ini['sectores_compra']);
		$ini['dependencias_rentas'] = array_merge($ini_default['dependencias_rentas'], $ini['dependencias_rentas']);
		if (isset($ini['ventas_agua_emp_suc'])) {
			$ini['ventas_agua_emp_suc'] = array_merge($ini_default['ventas_agua_emp_suc'], $ini['ventas_agua_emp_suc']);
		}	
		$ini['rrhh_ent_org'] = array_merge($ini_default['rrhh_ent_org'], $ini['rrhh_ent_org']);
		$ini['sociales'] = array_merge($ini_default['sociales'], $ini['sociales']);
		
		$config_basicos = $ini['basicos'];
		$parametros_basicos = array('hostname', 'usuario', 'password', 'port');
		foreach ($parametros_basicos as $parametro) {
			if (!isset($config_basicos[$parametro])) {
				throw new toba_error("No se encuentra definido el parametro 'basicos->$parametro' en el archivo '$archivo'");
			}
		}
		$config_datos_usuario = $ini['datos_usuario'];
		$parametros_datos_usuario = array('base_dn', 'filter', 'object_class');
		foreach ($parametros_datos_usuario as $parametro) {
			if (!isset($config_datos_usuario[$parametro])) {
				throw new toba_error("No se encuentra definido el parametro 'datos_usuario->$parametro' en el archivo '$archivo'");
			}
		}
		$config_perfiles_funcionales = $ini['perfiles_funcionales'];
		$parametros_perfiles_funcionales = array('base_dn_afi', 'base_dn_rentas', 'filter');
		foreach ($parametros_perfiles_funcionales as $parametro) {
			if (!isset($config_perfiles_funcionales[$parametro])) {
				throw new toba_error("No se encuentra definido el parametro 'perfiles_funcionales->$parametro' en el archivo '$archivo'");
			}
		}
		$config_sectores = array();
		$config_sectores['unidades_administracion'] = $ini['unidades_administracion'];
		$config_sectores['unidades_ejecutoras'] = $ini['unidades_ejecutoras'];
		$config_sectores['ambitos_compra'] = $ini['ambitos_compra'];
		$config_sectores['sectores_compra'] = $ini['sectores_compra'];
		$config_sectores['dependencias_rentas'] = $ini['dependencias_rentas'];
		$parametros_sectores = array('base_dn', 'filter', 'prefijo');
		foreach ($config_sectores as $clave => $sector) {
			foreach ($parametros_sectores as $parametro) {
				if (!isset($sector[$parametro])) {
					throw new toba_error("No se encuentra definido el parametro '$clave -> $parametro' en el archivo '$archivo'");
				}
			}
		}
		return $ini;
	}
}

?>
