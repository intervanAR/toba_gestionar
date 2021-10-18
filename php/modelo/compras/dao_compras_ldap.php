<?php

class dao_compras_ldap
{
	static function modificar_sector($cod_sector, $datos)
	{
        $config = dao_configuracion_ldap::get_config_ldap();
        $base_dns = self::get_base_dns();
		$conexion = db_ldap::get_conexion_autenticada($config['basicos']['hostname'], $config['basicos']['usuario'], $config['basicos']['password'], $config['basicos']['port']);

		foreach ($base_dns as $base_dn) {
			if (db_ldap::existe_entrada_hijos($conexion, $base_dn, $config['sectores_compra']['filter_perfil'], self::get_cod_sector_ldap($cod_sector))) {
				try {
					$dn_sector = self::get_dn_sector($cod_sector, $base_dn);
					db_ldap::set_entrada($conexion, $dn_sector, self::map_atributos_toba_to_ldap($datos));
                    return true;
				} catch (toba_error $e) {
					db_ldap::cerrar_conexion($conexion);
					throw new toba_error('No se pudo modificar el sector. Contactese con el administrador.');
				}
			}
		}

        db_ldap::cerrar_conexion($conexion);
        throw new toba_error('El sector no existe en el servidor LDAP. Contactese con el administrador.');
	}
	
	static function agregar_sector($cod_sector, $datos)
	{
		$config = dao_configuracion_ldap::get_config_ldap();
        $base_dn = self::get_base_dns()[0];
		$conexion = db_ldap::get_conexion_autenticada($config['basicos']['hostname'], $config['basicos']['usuario'], $config['basicos']['password'], $config['basicos']['port']);
		
		if (!db_ldap::existe_entrada_hijos($conexion, $base_dn, $config['sectores_compra']['filter_perfil'], self::get_cod_sector_ldap($cod_sector))) {
			try {
				$dn_sector = self::get_dn_sector($cod_sector, $base_dn);
				$datos_ldap = self::map_atributos_toba_to_ldap($datos);
				$datos_ldap["objectclass"] = explode(',', $config['sectores_compra']['object_class']);
				$datos_ldap["gidnumber"] = rand();
				db_ldap::add_entrada($conexion, $dn_sector, $datos_ldap);
			} catch (toba_error $e) {
				db_ldap::cerrar_conexion($conexion);
				throw new toba_error('No se pudo agregar el sector. Contactese con el administrador.');
			}
		} else {
			db_ldap::cerrar_conexion($conexion);
			throw new toba_error('El sector ya existe en el servidor LDAP. Contactese con el administrador.');
		}
		db_ldap::cerrar_conexion($conexion);
		return true;
	}
	
	static function eliminar_sector($cod_sector)
	{
		$config = dao_configuracion_ldap::get_config_ldap();
        $base_dns = self::get_base_dns();
		$conexion = db_ldap::get_conexion_autenticada($config['basicos']['hostname'], $config['basicos']['usuario'], $config['basicos']['password'], $config['basicos']['port']);

		foreach ($base_dns as $base_dn) {
            if (db_ldap::existe_entrada_hijos($conexion, $base_dn, $config['sectores_compra']['filter_perfil'], self::get_cod_sector_ldap($cod_sector))) {
                try {
                    $dn_sector = self::get_dn_sector($cod_sector, $base_dn);
                    db_ldap::delete_entrada($conexion, $dn_sector);
                    return true;
                } catch (toba_error $e) {
                    db_ldap::cerrar_conexion($conexion);
                    throw new toba_error('No se pudo eliminar el sector. Contactese con el administrador.');
                }
            }
        }

		db_ldap::cerrar_conexion($conexion);
		throw new toba_error('El sector no existe en el servidor LDAP. Contactese con el administrador.');
	}
	
	static function existe_sector($cod_sector)
	{
		$config = dao_configuracion_ldap::get_config_ldap();
        $base_dns = self::get_base_dns();
		$conexion = db_ldap::get_conexion_autenticada($config['basicos']['hostname'], $config['basicos']['usuario'], $config['basicos']['password'], $config['basicos']['port']);

		foreach ($base_dns as $base_dn) {
            if (db_ldap::existe_entrada_hijos($conexion, $base_dn, $config['sectores_compra']['filter_perfil'], self::get_cod_sector_ldap($cod_sector))) {
                return true;
            }
        }
		return false;
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////////////
	
	static private function get_cod_sector_ldap($cod_sector) {
		$config = dao_configuracion_ldap::get_config_ldap();
		return $config['sectores_compra']['prefijo'] . $cod_sector;
	}
	
	static private function get_dn_sector($cod_sector, $base_dn) {
		return 'cn=' . self::get_cod_sector_ldap($cod_sector) . ',' . $base_dn;
	}

    static private function get_base_dns() {
        $config = dao_configuracion_ldap::get_config_ldap();
        $base_dns = array($config['sectores_compra']['base_dn']);
        return $base_dns;
    }
	
	static private function map_atributos_toba_to_ldap($atributos_toba) {
		$atributos_ldap = array();
		foreach ($atributos_toba as $clave => $atributo_toba) {
			if (isset($atributo_toba)) {
				switch ($clave) {
					case 'descripcion': {
						$atributos_ldap["description"] = $atributo_toba;
						break;
					}
				}
			}
		}
		return $atributos_ldap;
	}
}
?>
