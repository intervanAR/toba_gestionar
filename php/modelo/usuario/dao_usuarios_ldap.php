<?php

class dao_usuarios_ldap
{
	static function get_datos_usuario($nombre_usuario)
	{
		$config = dao_configuracion_ldap::get_config_ldap();
		$base_dns = self::get_base_dns();
		$conexion = db_ldap::get_conexion($config['basicos']['hostname'], $config['basicos']['port']);

		foreach ($base_dns as $base_dn) {
            $entradas = db_ldap::buscar_hijos_y_get_entradas($conexion, $base_dn, $config['datos_usuario']['filter'], $nombre_usuario, array("cn","mail","uid"));
            if (isset($entradas['count']) && $entradas['count'] == 1 && !empty($entradas[0])) {
                if (isset($entradas[0]["uid"][0]) && isset($entradas[0]["cn"][0])) {
                    db_ldap::cerrar_conexion($conexion);
                	return array(	'uid' => $entradas[0]["uid"][0],
                        'cn' => $entradas[0]["cn"][0],
                        'mail' => isset($entradas[0]["mail"][0])?$entradas[0]["mail"][0]:''
                    );
                }
            }
		}

		db_ldap::cerrar_conexion($conexion);
		throw new toba_error('El usuario no existe en el servidor LDAP. Contactese con el administrador.');
	}

	static function get_usuario_dn($nombre_usuario)
	{
		$config = dao_configuracion_ldap::get_config_ldap();
		$base_dns = self::get_base_dns();
		$conexion = db_ldap::get_conexion($config['basicos']['hostname'], $config['basicos']['port']);

		foreach ($base_dns as $base_dn) {
            $entradas = db_ldap::buscar_hijos_y_get_entradas($conexion, $base_dn, $config['datos_usuario']['filter'], $nombre_usuario, array("cn","mail","uid"));
            if (isset($entradas['count']) && $entradas['count'] == 1 && !empty($entradas[0])) {
                if (isset($entradas[0]["uid"][0]) && isset($entradas[0]["cn"][0])) {
                    db_ldap::cerrar_conexion($conexion);
                	return self::get_dn_usuario($nombre_usuario, $base_dn);
                }
            }
		}

		db_ldap::cerrar_conexion($conexion);
		return false;
	}
	
	static function get_datos_usuarios()
	{
		$config = dao_configuracion_ldap::get_config_ldap();
        $base_dns = self::get_base_dns();
		$conexion = db_ldap::get_conexion($config['basicos']['hostname'], $config['basicos']['port']);

		$usuarios = array();
		foreach ($base_dns as $base_dn) {
            $entradas = db_ldap::buscar_hijos_y_get_entradas($conexion, $base_dn, $config['datos_usuario']['filter'], '*', array("cn","mail","uid"));
            if (isset($entradas['count']) && $entradas['count'] >= 0) {
                for ($j=0; $j<$entradas["count"]; $j++) {
                    if (isset($entradas[$j]["uid"][0]) && isset($entradas[$j]["cn"][0])) {
                        $usuarios[] = array(
                            'uid' => $entradas[$j]["uid"][0],
                            'cn' => $entradas[$j]["cn"][0],
                            'mail' => isset($entradas[$j]["mail"][0]) ? $entradas[$j]["mail"][0] : '',
                            'uid_cn' => $entradas[$j]["uid"][0] . ' - ' . $entradas[$j]["cn"][0]
                        );
                    } else {
                        db_ldap::cerrar_conexion($conexion);
                    	throw new toba_error('Error al obtener los usuarios del servidor LDAP. Contactese con el administrador.');
                	}
                }
            }
        }
        db_ldap::cerrar_conexion($conexion);
	    return $usuarios;
	}
	
	static function get_perfiles_modulo($modulo) {
		$config = dao_configuracion_ldap::get_config_ldap();
		$conexion = db_ldap::get_conexion($config['basicos']['hostname'], $config['basicos']['port']);
		$base_dn = self::get_base_dn_modulo($modulo);

	    if (! is_null($base_dn)){
		$entradas = db_ldap::buscar_y_get_entradas($conexion, $base_dn, "(|(cn=%s))", '*', array ("cn", "description"));		
		}
		db_ldap::cerrar_conexion($conexion);
		
		$perfiles_modulo = array();
		if (isset($entradas) && !empty($entradas) && isset($entradas["count"])) {
			for ($j=0; $j<$entradas["count"]; $j++) {
				$perfil_id = self::get_perfil_id($modulo, $entradas[$j]["cn"][0]);
				if (isset($perfil_id)) {
					$perfiles_modulo[] = array(
						'cn' => $perfil_id,
						'cn_desc' => $perfil_id . (isset($entradas[$j]["description"][0]) && !empty($entradas[$j]["description"][0]) ? ' - ' . $entradas[$j]["description"][0] : ''),
						);
				}
			}
		}
		return $perfiles_modulo;
	}
	
	static function existe_perfil_modulo($perfil_id, $modulo)
	{
		$config = dao_configuracion_ldap::get_config_ldap();
		$conexion = db_ldap::get_conexion_autenticada($config['basicos']['hostname'], $config['basicos']['usuario'], $config['basicos']['password'], $config['basicos']['port']);
		$base_dn = self::get_base_dn_modulo($modulo);
		
		if (db_ldap::existe_entrada($conexion, $base_dn, $config['perfiles_funcionales']['filter_perfil'], self::get_perfil_ldap_x_modulo($modulo, $perfil_id))) {
			db_ldap::cerrar_conexion($conexion);
			return true;
		}
		
		db_ldap::cerrar_conexion($conexion);
		return false;
	}
	
	static function get_perfiles_modulo_usuario($modulo, $uid) {
		$config = dao_configuracion_ldap::get_config_ldap();
		$conexion = db_ldap::get_conexion($config['basicos']['hostname'], $config['basicos']['port']);
		$base_dn = self::get_base_dn_modulo($modulo);
		if (!is_null($base_dn)) {
		  $entradas = db_ldap::buscar_y_get_entradas($conexion, $base_dn, $config['perfiles_funcionales']['filter'], $uid, array ("cn"));		
		}
		db_ldap::cerrar_conexion($conexion);
		
		$perfiles_modulo = array();
		if (isset($entradas) && !empty($entradas) && isset($entradas["count"])) {
			for ($j=0; $j<$entradas["count"]; $j++) {
				$perfil_id = self::get_perfil_id($modulo, $entradas[$j]["cn"][0]);
				if (isset($perfil_id)) {
					$perfiles_modulo[] = array(
						'cn' => $perfil_id,
						);
				}
			}
		}
		return $perfiles_modulo;
	}
    
	static function get_perfiles_funcionales($uid, $modulo='') {
		
		$config = dao_configuracion_ldap::get_config_ldap();
		$base_dn = self::get_base_dn_modulo($modulo);
        $perfiles_funcionales = array();
		if (!isset($base_dn) || empty($base_dn)) return $perfiles_funcionales;
		
        $conexion = db_ldap::get_conexion($config['basicos']['hostname'], $config['basicos']['port']);
        $entradas = db_ldap::buscar_y_get_entradas($conexion, $base_dn, $config['perfiles_funcionales']['filter'], $uid, array ("cn"));
		db_ldap::cerrar_conexion($conexion);

		if (isset($entradas) && !empty($entradas) && isset($entradas["count"])) {
			for ($j=0; $j<$entradas["count"]; $j++) {
				$perfil_id = self::get_perfil_id($modulo, $entradas[$j]["cn"][0]);
				if (isset($perfil_id)) {
					$perfiles_funcionales[] = $perfil_id;
				}
			}
		}
		return $perfiles_funcionales;
	}
	
	static function get_perfiles_funcionales_usuario($uid) {
		return array_merge(self::get_perfiles_funcionales($uid, 'AFI'), self::get_perfiles_funcionales($uid, 'RENTAS'), self::get_perfiles_funcionales($uid, 'RRHH'), self::get_perfiles_funcionales($uid, 'SOCIALES'), self::get_perfiles_funcionales($uid, 'VENTAS_AGUA'));
	}
        
	static function get_unidades_administracion($uid) {
		$config = dao_configuracion_ldap::get_config_ldap();
		$conexion = db_ldap::get_conexion($config['basicos']['hostname'], $config['basicos']['port']);
		$entradas = db_ldap::buscar_y_get_entradas($conexion, $config['unidades_administracion']['base_dn'], $config['unidades_administracion']['filter'], $uid, array ("cn"));		
		db_ldap::cerrar_conexion($conexion);
		
		$unidades_administracion = array();
		if (isset($entradas) && !empty($entradas) && isset($entradas["count"])) {
			for ($j=0; $j<$entradas["count"]; $j++) {
				if (isset($entradas[$j]["cn"][0])) {
					$unidades_administracion[] = intval(str_replace($config['unidades_administracion']['prefijo'], '', $entradas[$j]["cn"][0]));
				}
			}
		}
		return $unidades_administracion;
	}
	
	static function get_unidades_ejecutoras($uid) {
		$config = dao_configuracion_ldap::get_config_ldap();
		$conexion = db_ldap::get_conexion($config['basicos']['hostname'], $config['basicos']['port']);
		$entradas = db_ldap::buscar_y_get_entradas($conexion, $config['unidades_ejecutoras']['base_dn'], $config['unidades_ejecutoras']['filter'], $uid, array ("cn"));
		db_ldap::cerrar_conexion($conexion);
		
		$unidades_ejecutoras = array();
		if (isset($entradas) && !empty($entradas) && isset($entradas["count"])) {
			for ($j=0; $j<$entradas["count"]; $j++) {
				if (isset($entradas[$j]["cn"][0])) {
					$unidades_ejecutoras[] = intval(str_replace($config['unidades_ejecutoras']['prefijo'], '', $entradas[$j]["cn"][0]));
				}
			}
		}
		return $unidades_ejecutoras;
	}
	
	static function get_ambitos_compra($uid) {
		$config = dao_configuracion_ldap::get_config_ldap();
		$conexion = db_ldap::get_conexion($config['basicos']['hostname'], $config['basicos']['port']);
		$entradas = db_ldap::buscar_y_get_entradas($conexion, $config['ambitos_compra']['base_dn'], $config['ambitos_compra']['filter'], $uid, array ("cn"));
		db_ldap::cerrar_conexion($conexion);
		
		$ambitos_compra = array();
		if (isset($entradas) && !empty($entradas) && isset($entradas["count"])) {
			for ($j=0; $j<$entradas["count"]; $j++) {
				if (isset($entradas[$j]["cn"][0])) {
					$ambitos_compra[] = intval(str_replace($config['ambitos_compra']['prefijo'], '', $entradas[$j]["cn"][0]));
				}
			}
		}
		return $ambitos_compra;
	}
	
	static function get_sectores_compra($uid) {
		$config = dao_configuracion_ldap::get_config_ldap();
		$conexion = db_ldap::get_conexion($config['basicos']['hostname'], $config['basicos']['port']);
		$entradas = db_ldap::buscar_y_get_entradas($conexion, $config['sectores_compra']['base_dn'], $config['sectores_compra']['filter'], $uid, array ("cn"));
		db_ldap::cerrar_conexion($conexion);
		
		$sectores_compra = array();
		if (isset($entradas) && !empty($entradas) && isset($entradas["count"])) {
			for ($j=0; $j<$entradas["count"]; $j++) {
				if (isset($entradas[$j]["cn"][0])) {
					$sectores_compra[] = intval(str_replace($config['sectores_compra']['prefijo'], '', $entradas[$j]["cn"][0]));
				}
			}
		}
		return $sectores_compra;
	}
	
	static function get_dependencias_rentas($uid) {
		$config = dao_configuracion_ldap::get_config_ldap();
		$conexion = db_ldap::get_conexion($config['basicos']['hostname'], $config['basicos']['port']);
		$entradas = db_ldap::buscar_y_get_entradas($conexion, $config['dependencias_rentas']['base_dn'], $config['dependencias_rentas']['filter'], $uid, array ("cn"));
		db_ldap::cerrar_conexion($conexion);
		
		$dependencias_rentas = array();
		if (isset($entradas) && !empty($entradas) && isset($entradas["count"])) {
			for ($j=0; $j<$entradas["count"]; $j++) {
				if (isset($entradas[$j]["cn"][0])) {
					$dependencias_rentas[] = intval(str_replace($config['dependencias_rentas']['prefijo'], '', $entradas[$j]["cn"][0]));
				}
			}
		}
		return $dependencias_rentas;	
	}
	
	static function get_ent_org_rrhh($uid) {
		$config = dao_configuracion_ldap::get_config_ldap();
		$conexion = db_ldap::get_conexion($config['basicos']['hostname'], $config['basicos']['port']);
		$entradas = db_ldap::buscar_y_get_entradas($conexion, $config['rrhh_ent_org']['base_dn'], $config['rrhh_ent_org']['filter'], $uid, array ("cn"));
		db_ldap::cerrar_conexion($conexion);
		
		$ent_org_rrhh = array();
		if (isset($entradas) && !empty($entradas) && isset($entradas["count"])) {
			for ($j=0; $j<$entradas["count"]; $j++) {
				if (isset($entradas[$j]["cn"][0])) {
					$ent_org_rrhh[] = str_replace($config['rrhh_ent_org']['prefijo'], '', $entradas[$j]["cn"][0]);
				}
			}
		}
		return $ent_org_rrhh;
	}
	
	static function get_emp_suc_ventas_agua($uid) {
		$config = dao_configuracion_ldap::get_config_ldap();
		$conexion = db_ldap::get_conexion($config['basicos']['hostname'], $config['basicos']['port']);
		$entradas = db_ldap::buscar_y_get_entradas($conexion, $config['ventas_agua_emp_suc']['base_dn'], $config['ventas_agua_emp_suc']['filter'], $uid, array ("cn"));
		db_ldap::cerrar_conexion($conexion);
		
		$emp_suc_ventas_agua = array();
		if (isset($entradas) && !empty($entradas) && isset($entradas["count"])) {
			for ($j=0; $j<$entradas["count"]; $j++) {
				if (isset($entradas[$j]["cn"][0])) {
					$emp_suc_ventas_agua[] = str_replace($config['ventas_agua_emp_suc']['prefijo'], '', $entradas[$j]["cn"][0]);
				}
			}
		}
		return $emp_suc_ventas_agua;
	}
	
	static function get_sectores_sociales($uid) {
		$config = dao_configuracion_ldap::get_config_ldap();
		$conexion = db_ldap::get_conexion($config['basicos']['hostname'], $config['basicos']['port']);
		$entradas = db_ldap::buscar_y_get_entradas($conexion, $config['sociales']['base_dn'], $config['sociales']['filter'], $uid, array ("cn"));
		db_ldap::cerrar_conexion($conexion);
		
		$sectores_sociales = array();
		if (isset($entradas) && !empty($entradas) && isset($entradas["count"])) {
			for ($j=0; $j<$entradas["count"]; $j++) {
				if (isset($entradas[$j]["cn"][0])) {
					$sectores_sociales[] = str_replace($config['sociales']['prefijo'], '', $entradas[$j]["cn"][0]);
				}
			}
		}
		return $sectores_sociales;
	}
	
	static function get_perfiles_sector($sector) {
		$config = dao_configuracion_ldap::get_config_ldap();
		$conexion = db_ldap::get_conexion($config['basicos']['hostname'], $config['basicos']['port']);
		if (isset($config[$sector]['base_dn'])){
		  $entradas = db_ldap::buscar_y_get_entradas($conexion, $config[$sector]['base_dn'], "(|(cn=%s))", '*', array ("cn", "description"));
		}	
		db_ldap::cerrar_conexion($conexion);
		
		$perfiles_sector = array();
		if (isset($entradas) && !empty($entradas) && isset($entradas["count"])) {
			for ($j=0; $j<$entradas["count"]; $j++) {
				if (isset($entradas[$j]["cn"][0])) {
					$cn = str_replace($config[$sector]['prefijo'], '', $entradas[$j]["cn"][0]);
					$perfiles_sector[] = array(
						'cn' => $cn,
						'cn_desc' => $cn . (isset($entradas[$j]["description"]) ? ' - ' . $entradas[$j]["description"][0] : ''),
						);
				}
			}
		}
		return $perfiles_sector;
	}
	
	static function get_perfiles_sector_usuario($sector, $uid) {
		$config = dao_configuracion_ldap::get_config_ldap();
		$conexion = db_ldap::get_conexion($config['basicos']['hostname'], $config['basicos']['port']);
		if (isset($config[$sector]['base_dn'])) {
		   $entradas = db_ldap::buscar_y_get_entradas($conexion, $config[$sector]['base_dn'], $config[$sector]['filter'], $uid, array ("cn", "description"));	
	    }
		db_ldap::cerrar_conexion($conexion);
		
		$perfiles_sector = array();
		if (isset($entradas) && !empty($entradas) && isset($entradas["count"])) {
			for ($j=0; $j<$entradas["count"]; $j++) {
				if (isset($entradas[$j]["cn"][0])) {
					$cn = str_replace($config[$sector]['prefijo'], '', $entradas[$j]["cn"][0]);
					$perfiles_sector[] = array(
						'cn' => $cn,
						'cn_desc' => $cn . (isset($entradas[$j]["description"]) ? ' - ' . $entradas[$j]["description"][0] : ''),
						);
				}
			}
		}
		return $perfiles_sector;
	}
	
	static function set_password_usuario($nombre_usuario, $password_plano)
	{
		$config = dao_configuracion_ldap::get_config_ldap();
		$base_dns = self::get_base_dns();
		$conexion = db_ldap::get_conexion_autenticada($config['basicos']['hostname'], $config['basicos']['usuario'], $config['basicos']['password'], $config['basicos']['port']);

		foreach ($base_dns as $base_dn) {
            if (db_ldap::existe_entrada_hijos($conexion, $base_dn, $config['datos_usuario']['filter'], $nombre_usuario)) {
                try {
                    $dn_usuario = self::get_dn_usuario($nombre_usuario, $base_dn);
                    $entrada = array();
                    $entrada["userPassword"] = "{SHA}" . base64_encode(pack("H*", sha1($password_plano)));
                    db_ldap::set_entrada($conexion, $dn_usuario, $entrada);
                    return true;
                } catch (toba_error $e) {
                    db_ldap::cerrar_conexion($conexion);
                    throw new toba_error('No se pudo cambiar el password del usuario. Contactese con el administrador.');
                }
            }
        }

		db_ldap::cerrar_conexion($conexion);
		throw new toba_error('El usuario no existe en el servidor LDAP. Contactese con el administrador.');
	}
	
	static function modificar_usuario($nombre_usuario, $datos)
	{
        $config = dao_configuracion_ldap::get_config_ldap();
        $base_dns = self::get_base_dns();
		$conexion = db_ldap::get_conexion_autenticada($config['basicos']['hostname'], $config['basicos']['usuario'], $config['basicos']['password'], $config['basicos']['port']);

		foreach ($base_dns as $base_dn) {
			if (db_ldap::existe_entrada_hijos($conexion, $base_dn, $config['datos_usuario']['filter'], $nombre_usuario)) {
				try {
					$dn_usuario = self::get_dn_usuario($nombre_usuario, $base_dn);
					db_ldap::set_entrada($conexion, $dn_usuario, $datos);
                    return true;
				} catch (toba_error $e) {
					db_ldap::cerrar_conexion($conexion);
					throw new toba_error('No se pudo modificar el usuario. Contactese con el administrador.');
				}
			}
		}

        db_ldap::cerrar_conexion($conexion);
        throw new toba_error('El usuario no existe en el servidor LDAP. Contactese con el administrador.');
	}
	
	static function agregar_usuario($nombre_usuario, $datos)
	{
		$config = dao_configuracion_ldap::get_config_ldap();
        $base_dn = self::get_base_dns()[0];
		$conexion = db_ldap::get_conexion_autenticada($config['basicos']['hostname'], $config['basicos']['usuario'], $config['basicos']['password'], $config['basicos']['port']);
		
		if (!db_ldap::existe_entrada_hijos($conexion, $base_dn, $config['datos_usuario']['filter'], $nombre_usuario)) {
			try {
				$dn_usuario = self::get_dn_usuario($nombre_usuario, $base_dn);
				$datos["objectclass"] = explode(',', $config['datos_usuario']['object_class']);
				$datos["uidNumber"] = rand();
				$datos["gidnumber"] = 0;
				$datos["homedirectory"] = "/home/" . str_replace('ñ', 'ni', $nombre_usuario);
				$datos["loginshell"] = str_replace('ñ', 'ni', $nombre_usuario);
				db_ldap::add_entrada($conexion, $dn_usuario, $datos);
			} catch (toba_error $e) {
				db_ldap::cerrar_conexion($conexion);
				throw new toba_error('No se pudo agregar el usuario. Contactese con el administrador.');
			}
		} else {
			db_ldap::cerrar_conexion($conexion);
			throw new toba_error('El usuario ya existe en el servidor LDAP. Contactese con el administrador.');
		}
		db_ldap::cerrar_conexion($conexion);
		return true;
	}
	
	static function eliminar_usuario($nombre_usuario)
	{
		$config = dao_configuracion_ldap::get_config_ldap();
        $base_dns = self::get_base_dns();
		$conexion = db_ldap::get_conexion_autenticada($config['basicos']['hostname'], $config['basicos']['usuario'], $config['basicos']['password'], $config['basicos']['port']);

		foreach ($base_dns as $base_dn) {
            if (db_ldap::existe_entrada_hijos($conexion, $base_dn, $config['datos_usuario']['filter'], $nombre_usuario)) {
                try {
                    $dn_usuario = self::get_dn_usuario($nombre_usuario, $base_dn);
                    db_ldap::delete_entrada($conexion, $dn_usuario);
                    return true;
                } catch (toba_error $e) {
                    db_ldap::cerrar_conexion($conexion);
                    throw new toba_error('No se pudo eliminar el usuario. Contactese con el administrador.');
                }
            }
        }

		db_ldap::cerrar_conexion($conexion);
		throw new toba_error('El usuario no existe en el servidor LDAP. Contactese con el administrador.');
	}
	
	static function existe_usuario($nombre_usuario)
	{
		$config = dao_configuracion_ldap::get_config_ldap();
        $base_dns = self::get_base_dns();
		$conexion = db_ldap::get_conexion_autenticada($config['basicos']['hostname'], $config['basicos']['usuario'], $config['basicos']['password'], $config['basicos']['port']);

		foreach ($base_dns as $base_dn) {
            if (db_ldap::existe_entrada_hijos($conexion, $base_dn, $config['datos_usuario']['filter'], $nombre_usuario)) {
                return true;
            }
        }
		return false;
	}
	
	static function map_atributos_toba_to_ldap($atributos_toba, $genera_password=false) {
		$atributos_ldap = array();
		foreach ($atributos_toba as $clave => $atributo_toba) {
			if (isset($atributo_toba)) {
				switch ($clave) {
					case 'email': {
						$atributos_ldap["mail"] = $atributo_toba;
						break;
					}
					case 'usuario': {
						$atributos_ldap["uid"] = $atributo_toba;
						break;
					}
					case 'member_uid_add': {
						$atributos_ldap["member_uid_add"] = $atributo_toba;
						break;
					}
					case 'member_uid_del': {
						$atributos_ldap["member_uid_del"] = $atributo_toba;
						break;
					}
					case 'clave': {
						if ($genera_password) {
							$atributos_ldap["userPassword"] = "{SHA}" . base64_encode( pack( "H*", sha1( $atributo_toba ) ) );
						}
						break;
					}
					case 'descripcion': {
						$atributos_ldap["description"] = $atributo_toba;
						break;
					}
					case 'nombre': {
						$atributos_ldap["cn"] = $atributo_toba;
						$nombres_arr = explode(' ', $atributo_toba);
						$atributos_ldap["givenName"] = $nombres_arr[0];
						if (isset($nombres_arr[1])) {
							$atributos_ldap["sn"] = $nombres_arr[1];
						} else {
							$atributos_ldap["sn"] = $nombres_arr[0];
						}
						break;
					}
					case 'usuario_grupo_acc': {
						$atributos_ldap["cn"] = $atributo_toba;
						break;
					}
				}
			}
		}
		return $atributos_ldap;
	}
	
	static function existe_perfil($perfil_id, $proyecto)
	{
		if (self::proyecto_excluido_sincronizacion($proyecto)) return false;

		$config = dao_configuracion_ldap::get_config_ldap();
		$conexion = db_ldap::get_conexion_autenticada($config['basicos']['hostname'], $config['basicos']['usuario'], $config['basicos']['password'], $config['basicos']['port']);
		
		$modulos = self::get_modulos_intervan($proyecto);
		
		foreach ($modulos as $modulo) {
			$base_dn = self::get_base_dn_modulo($modulo);
			if (isset($base_dn) && db_ldap::existe_entrada($conexion, $base_dn, $config['perfiles_funcionales']['filter_perfil'], self::get_perfil_ldap_x_modulo($modulo, $perfil_id))) {
				db_ldap::cerrar_conexion($conexion);
				return true;
			}
		}
		
		db_ldap::cerrar_conexion($conexion);
		return false;
	}
	
	static function agregar_perfil($perfil_id, $proyecto, $datos)
	{
		$config = dao_configuracion_ldap::get_config_ldap();
		$conexion = db_ldap::get_conexion_autenticada($config['basicos']['hostname'], $config['basicos']['usuario'], $config['basicos']['password'], $config['basicos']['port']);
		
		$modulos = self::get_modulos_intervan($proyecto);
		
		foreach ($modulos as $modulo) {
			$base_dn = self::get_base_dn_modulo($modulo);
			$perfil_ldap = self::get_perfil_ldap_x_modulo($modulo, $perfil_id);
			if (!db_ldap::existe_entrada($conexion, $base_dn, $config['perfiles_funcionales']['filter_perfil'], $perfil_ldap)) {
				try {
					$dn_perfil = self::get_dn_perfil($perfil_ldap, $base_dn);
					$datos["objectclass"] = explode(',', $config['perfiles_funcionales']['object_class']);
					$datos["gidNumber"] = rand();
					if (isset($datos['cn'])) {
						$datos['cn'] = $perfil_ldap;
					}
					db_ldap::add_entrada($conexion, $dn_perfil, $datos);
				} catch (toba_error $e) {
					db_ldap::cerrar_conexion($conexion);
					throw new toba_error('No se pudo agregar el perfil. Contactese con el administrador.');
				}
			} else {
				db_ldap::cerrar_conexion($conexion);
				throw new toba_error('El perfil ya existe en el servidor LDAP. Contactese con el administrador.');
			}
		}
		
		db_ldap::cerrar_conexion($conexion);
		return true;
	}
	
	static function modificar_perfil($perfil_id, $proyecto, $datos)
	{
		$modulos = self::get_modulos_intervan($proyecto);	
		return self::modificar_perfil_x_modulos($perfil_id, $modulos, $datos);
	}
	
	static function modificar_perfil_x_modulos($perfil_id, $modulos, $datos)
	{
		$config = dao_configuracion_ldap::get_config_ldap();
		$conexion = db_ldap::get_conexion_autenticada($config['basicos']['hostname'], $config['basicos']['usuario'], $config['basicos']['password'], $config['basicos']['port']);
		
		foreach ($modulos as $modulo) {
			$base_dn = self::get_base_dn_modulo($modulo);
			$perfil_ldap = self::get_perfil_ldap_x_modulo($modulo, $perfil_id);
			$entradas = db_ldap::buscar_y_get_entradas($conexion, $base_dn, $config['perfiles_funcionales']['filter_perfil'], $perfil_ldap, array("*"));  
			if (isset($entradas['count']) && $entradas['count'] == 1 && !empty($entradas[0])) {
				try {
					$datos_ldap = array(
						'memberUid' => self::get_member_uid_perfil($entradas[0]),
					);
					if (isset($datos['member_uid_add'])) {
						$datos_ldap['memberUid'] = array_unique(array_merge($datos_ldap['memberUid'], $datos['member_uid_add']));
						unset($datos['member_uid_add']);
					}
					if (isset($datos['member_uid_del'])) {
						$datos_ldap['memberUid'] = array_values(array_diff($datos_ldap['memberUid'], $datos['member_uid_del']));
						unset($datos['member_uid_del']);
					}
					$dn_perfil = self::get_dn_perfil($perfil_ldap, $base_dn);
					$datos_modif = array_merge($datos_ldap, $datos);
					if (isset($datos_modif['cn'])) {
						$datos_modif['cn'] = $perfil_ldap;
					}
					db_ldap::set_entrada($conexion, $dn_perfil, $datos_modif);
				} catch (toba_error $e) {
					db_ldap::cerrar_conexion($conexion);
					throw new toba_error('No se pudo modificar el perfil. Contactese con el administrador.');
				}
			}
		}
		db_ldap::cerrar_conexion($conexion);
		return true;
	}
	
	static function eliminar_perfil($perfil_id, $proyecto)
	{
		$config = dao_configuracion_ldap::get_config_ldap();
		$conexion = db_ldap::get_conexion_autenticada($config['basicos']['hostname'], $config['basicos']['usuario'], $config['basicos']['password'], $config['basicos']['port']);
		
		$modulos = self::get_modulos_intervan($proyecto);
		
		foreach ($modulos as $modulo) {
			$base_dn = self::get_base_dn_modulo($modulo);
			$perfil_ldap = self::get_perfil_ldap_x_modulo($modulo, $perfil_id);
			if (db_ldap::existe_entrada($conexion, $base_dn, $config['perfiles_funcionales']['filter_perfil'], $perfil_ldap)) {
				try {
					$dn_perfil = self::get_dn_perfil($perfil_ldap, $base_dn);
					db_ldap::delete_entrada($conexion, $dn_perfil);
				} catch (toba_error $e) {
					db_ldap::cerrar_conexion($conexion);
					throw new toba_error('No se pudo eliminar el perfil. Contactese con el administrador.');
				}
			} else {
				db_ldap::cerrar_conexion($conexion);
				throw new toba_error('El perfil no existe en el servidor LDAP. Contactese con el administrador.');
			}
		}
		db_ldap::cerrar_conexion($conexion);
		return true;
	}
	
	static function get_proyectos_sinc_ldap() {
		return array(
			'administracion',
			'compras',
			'costos',
			'contabilidad',
			'presupuesto',
			'principal',
			'rentas',
			'rrhh',
			'sociales',
			'ventas_agua'
		);
	}
	
	static function get_modulos_intervan($proyecto) {
		$modulos = array();
		switch ($proyecto) {
			case 'administracion':
			case 'compras':
			case 'costos':
			case 'contabilidad':
			case 'presupuesto':
				$modulos[] = 'AFI';
				break;
			case 'principal':
				$modulos[] = 'AFI';
				$modulos[] = 'RENTAS';
				$modulos[] = 'RRHH';
				$modulos[] = 'SOCIALES';
				$modulos[] = 'VENTAS_AGUA';
				break;
			case 'rentas':
				$modulos[] = 'RENTAS';
				break;
			case 'rrhh':
				$modulos[] = 'RRHH';
				break;
			case 'sociales':
				$modulos[] = 'SOCIALES';
				break;
			case 'usuarios_ldap':
				$modulos[] = 'USUARIOS_LDAP';
				break;
			case 'ventas_agua':
				$modulos[] = 'VENTAS_AGUA';
				break;
			default:
		}
		return $modulos;
	}
	
	static function existe_usuario_perfil($nombre_usuario, $perfil_id, $proyecto)
	{
		$config = dao_configuracion_ldap::get_config_ldap();
		$conexion = db_ldap::get_conexion_autenticada($config['basicos']['hostname'], $config['basicos']['usuario'], $config['basicos']['password'], $config['basicos']['port']);
		
		$modulos = self::get_modulos_intervan($proyecto);
		
		foreach ($modulos as $modulo) {
			$perfiles_usuario = self::get_perfiles_funcionales($nombre_usuario, $modulo);
			if (in_array($perfil_id, $perfiles_usuario)) {
				db_ldap::cerrar_conexion($conexion);
				return true;
			}
		}
		
		db_ldap::cerrar_conexion($conexion);
		return false;
	}
	
	static function existe_perfil_sector($perfil_id, $sector)
	{
		$config = dao_configuracion_ldap::get_config_ldap();
		$conexion = db_ldap::get_conexion_autenticada($config['basicos']['hostname'], $config['basicos']['usuario'], $config['basicos']['password'], $config['basicos']['port']);
		
		if (db_ldap::existe_entrada($conexion, $config[$sector]['base_dn'], $config[$sector]['filter_perfil'], $config[$sector]['prefijo'] . $perfil_id)) {
			db_ldap::cerrar_conexion($conexion);
			return true;
		}
		
		db_ldap::cerrar_conexion($conexion);
		return false;
	}
	
	static function modificar_perfil_x_sector($perfil_id, $sector, $datos)
	{
		$config = dao_configuracion_ldap::get_config_ldap();
		$conexion = db_ldap::get_conexion_autenticada($config['basicos']['hostname'], $config['basicos']['usuario'], $config['basicos']['password'], $config['basicos']['port']);
		
		$base_dn = $config[$sector]['base_dn'];
		$entradas = db_ldap::buscar_y_get_entradas($conexion, $base_dn, $config[$sector]['filter_perfil'], $config[$sector]['prefijo'] . $perfil_id, array("*"));  
		if (isset($entradas['count']) && $entradas['count'] == 1 && !empty($entradas[0])) {
			try {
				$datos_ldap = array(
					'memberUid' => self::get_member_uid_perfil($entradas[0]),
				);
				if (isset($datos['member_uid_add'])) {
					$datos_ldap['memberUid'] = array_unique(array_merge($datos_ldap['memberUid'], $datos['member_uid_add']));
					unset($datos['member_uid_add']);
				}
				if (isset($datos['member_uid_del'])) {
					$datos_ldap['memberUid'] = array_values(array_diff($datos_ldap['memberUid'], $datos['member_uid_del']));
					unset($datos['member_uid_del']);
				}
				$dn_perfil = self::get_dn_perfil($config[$sector]['prefijo'] . $perfil_id, $base_dn);
				db_ldap::set_entrada($conexion, $dn_perfil, array_merge($datos_ldap, $datos));
			} catch (toba_error $e) {
				db_ldap::cerrar_conexion($conexion);
				throw new toba_error('No se pudo modificar el perfil. Contactese con el administrador.');
			}
		}

		db_ldap::cerrar_conexion($conexion);
		return true;
	}
	
	static function get_digitos_organizacion_rrhh() {
		$config = dao_configuracion_ldap::get_config_ldap();
		return $config['rrhh_ent_org']['digitos_organizacion'];
	}
	
	static function get_digitos_sucursales_ventas_agua() {
		$config = dao_configuracion_ldap::get_config_ldap();
		return $config['ventas_agua_emp_suc']['digitos_sucursal'];
	}

	static function proyecto_excluido_sincronizacion($proyecto) {
        $config = dao_configuracion_ldap::get_config_ldap();
		if ($proyecto == 'usuarios_ldap') {
            $base_dn_usuarios_ldap = $config['perfiles_funcionales']['base_dn_usuarios_ldap'];
			return !$base_dn_usuarios_ldap || empty($base_dn_usuarios_ldap);
		}
		return false;
	}

    static function get_datos_IPs()
    {
        $config = dao_configuracion_ldap::get_config_ldap();
        $base_dn = self::get_dn_ips();
        $conexion = db_ldap::get_conexion($config['basicos']['hostname'], $config['basicos']['port']);

        $ips = array();
		$entradas = db_ldap::buscar_hijos_y_get_entradas($conexion, $base_dn, $config['datos_ip']['filter'], '*', array("cn","iphostnumber"));
		if (isset($entradas['count']) && $entradas['count'] >= 0) {
			for ($j=0; $j<$entradas["count"]; $j++) {
				if (isset($entradas[$j]["cn"][0])) {
					$ips[] = array(
						'cn' => $entradas[$j]["cn"][0],
						'ip_host_number' => isset($entradas[$j]["iphostnumber"][0]) ? $entradas[$j]["iphostnumber"][0] : '',
					);
				} else {
					db_ldap::cerrar_conexion($conexion);
					throw new toba_error('Error al obtener las ips del servidor LDAP. Contactese con el administrador.');
				}
			}
        }
        db_ldap::cerrar_conexion($conexion);
        return $ips;
    }

    static public function posee_acceso_sistema_por_IP() {
        $config = dao_configuracion_ldap::get_config_ldap();
		if (!$config['basicos']['validar_ip']) return true;

		// testing
//		$ips_validas = array('192.168.1.*', '192.169.*.*', '127.0.0.1', '127.0.1.10');
//		var_dump(self::es_ip_valida('127.0.0.1', $ips_validas)); // true
//      var_dump(self::es_ip_valida('192.168.1.176', $ips_validas)); // true
//      var_dump(self::es_ip_valida('192.169.54.22', $ips_validas)); // true
//      var_dump(self::es_ip_valida('192.169.154.122', $ips_validas)); // true
//      var_dump(self::es_ip_valida('127.0.1.10', $ips_validas)); // true
//		var_dump(self::es_ip_valida('192.168.2.1', $ips_validas)); // false
//		var_dump(self::es_ip_valida('127.0.0.2', $ips_validas)); // false
//		var_dump(self::es_ip_valida('127.0.1.11', $ips_validas)); // false

        $ip_cliente = self::get_direccion_IP_cliente();
        $datos_ips = self::get_datos_IPs();
		$ips_validas = ctr_funciones_basicas::matriz_to_array($datos_ips, 'ip_host_number');
		return self::es_ip_valida($ip_cliente, $ips_validas);
    }

	///////////////////////////////////////////////////////////////////////////////////////////////////
	
	static private function get_dn_usuario($nombre_usuario, $base_dn) {
		return 'uid=' . $nombre_usuario . ',' . $base_dn;
	}

    static private function get_base_dns() {
        $config = dao_configuracion_ldap::get_config_ldap();
        $base_dns = array();
        if (isset($config['datos_usuario']['base_dn_cliente']) && !empty($config['datos_usuario']['base_dn_cliente'])) {
        	$base_dns[] = $config['datos_usuario']['base_dn_cliente'];
		}
        $base_dns[] = $config['datos_usuario']['base_dn'];
        if (isset($config['datos_usuario']['base_dn_partners']) && !empty($config['datos_usuario']['base_dn_partners'])) {
            $base_dns[] = $config['datos_usuario']['base_dn_partners'];
        }
        return $base_dns;
    }
	
	static private function get_dn_ips() {
        $config = dao_configuracion_ldap::get_config_ldap();
        return $config['datos_ip']['base_dn'];
    }

	static private function get_dn_perfil($id_perfil, $base_dn) {
		return 'cn=' . $id_perfil . ',' . $base_dn;
	}
	
	private static function get_base_dn_modulo($modulo) {
		$config = dao_configuracion_ldap::get_config_ldap();
		switch ($modulo) {
			case 'AFI':
				$base_dn = $config['perfiles_funcionales']['base_dn_afi'];
				break;
			case 'RENTAS':
				$base_dn = $config['perfiles_funcionales']['base_dn_rentas'];
				break;
			case 'RRHH':
				$base_dn = $config['perfiles_funcionales']['base_dn_rrhh'];
				break;
			case 'VENTAS_AGUA':
			    if (isset($config['perfiles_funcionales']['base_dn_ventas_agua'])){
				    $base_dn = $config['perfiles_funcionales']['base_dn_ventas_agua'];
			    } else {
			    	$base_dn = null;
			    } 
				break;
			case 'SOCIALES':
				$base_dn = $config['perfiles_funcionales']['base_dn_sociales'];
				break;
			case 'USUARIOS_LDAP':
				$base_dn = $config['perfiles_funcionales']['base_dn_usuarios_ldap'];
				break;
			default:
				$base_dn = null;
		}
		return $base_dn;
	}
	
	private static function get_prefijo_perfiles_funcionales_x_modulo($modulo) {
		$config = dao_configuracion_ldap::get_config_ldap();
		switch ($modulo) {
			case 'AFI':
				$perfil_ldap = $config['perfiles_funcionales']['prefijo_afi'];
				break;
			case 'RENTAS':
				$perfil_ldap = $config['perfiles_funcionales']['prefijo_rentas'];
				break;
			case 'RRHH':
				$perfil_ldap = $config['perfiles_funcionales']['prefijo_rrhh'];
				break;
			case 'SOCIALES':
				$perfil_ldap = $config['perfiles_funcionales']['prefijo_sociales'];
				break;
			case 'VENTAS_AGUA':
				$perfil_ldap = $config['perfiles_funcionales']['prefijo_ventas_agua'];
				break;
			case 'USUARIOS_LDAP':
				$perfil_ldap = $config['perfiles_funcionales']['prefijo_usuarios_ldap'];
				break;
			default:
				$perfil_ldap = null;
		}
		return $perfil_ldap;
	}
	
	private static function get_perfil_ldap_x_modulo($modulo, $perfil) {
		return self::get_prefijo_perfiles_funcionales_x_modulo($modulo) . $perfil;
	}
	
	private static function get_member_uid_perfil($perfil) {
		$member_uids = array();
		if (isset($perfil) && !empty($perfil) && isset($perfil["memberuid"])) {
			for ($j=0; $j<$perfil["memberuid"]["count"]; $j++) {
			   if (isset($perfil["memberuid"][$j])) {
				   $member_uids[] = $perfil["memberuid"][$j];
			   }
			}
		}
		return $member_uids;
	}
	
	private static function get_perfil_id($modulo, $perfil_ldap) {
		$prefijo = self::get_prefijo_perfiles_funcionales_x_modulo($modulo);
		if (self::es_perfil_ldap_valido($modulo, $perfil_ldap)) {
			return substr($perfil_ldap, strlen($prefijo));
		} 
		return null;
	}
	
	private static function es_perfil_ldap_valido($modulo, $perfil_ldap) {
		$prefijo = self::get_prefijo_perfiles_funcionales_x_modulo($modulo);
		return substr($perfil_ldap, 0, strlen($prefijo)) == $prefijo;
	}

    static private function get_direccion_IP_cliente()
    {
        if (!empty($_SERVER ['HTTP_CLIENT_IP'] ))
            $ip=$_SERVER ['HTTP_CLIENT_IP'];
		elseif (!empty($_SERVER ['HTTP_X_FORWARDED_FOR'] ))
            $ip=$_SERVER ['HTTP_X_FORWARDED_FOR'];
        else
            $ip=$_SERVER ['REMOTE_ADDR'];

        return $ip;
    }

    static private function es_ip_valida($ip_cliente, $ips_validas) {
        foreach ($ips_validas as $ip_valida) {
        	if ($ip_valida === $ip_cliente) return true;
            if (strpos($ip_valida, '*') !== false) {
            	$ip_cliente_int = ip2long($ip_cliente);
                $ip_valida_superior_int = ip2long(str_replace('*', '255', $ip_valida));
                $ip_valida_inferior_int = ip2long(str_replace('*', '0', $ip_valida));
                if ($ip_cliente_int >= $ip_valida_inferior_int && $ip_cliente_int <= $ip_valida_superior_int) {
                	return true;
				}
            }
        }
        return false;
	}
}

?>
