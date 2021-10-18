<?php

class db_ldap
{
    public static function get_conexion($hostname, $port = 389)
    {
        $conexion = ldap_connect($hostname, $port);
        if (!$conexion) {
            throw new toba_error('No se puede conectar al servidor LDAP.');
        }

        return $conexion;
    }

    public static function get_conexion_autenticada($hostname, $usuario, $password, $port = 389)
    {
        $conexion = self::get_conexion($hostname, $port);
        // realizo la autenticacion
        self::autenticar($conexion, $usuario, $password);

        return $conexion;
    }

    public static function cerrar_conexion($conexion)
    {
        try {
            ldap_close($conexion);
        } catch (Exception $e) {
            throw new toba_error($e->getMessage());
        }
    }

    public static function autenticar($conexion, $usuario, $password)
    {
        ldap_set_option($conexion, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conexion, LDAP_OPT_REFERRALS, 0);

        // realizando la autenticacion
        $ldapbind = ldap_bind($conexion, $usuario, $password);

        // verificacion el resultado
        if ($ldapbind) {
            return true;
        } else {
            throw new toba_error('No se puede autenticar al servidor LDAP. Verifique el usuario y password');
        }
    }

    public static function buscar($conexion, $base_dn, $filter, array $attributes = null)
    {
        try {
            if (isset($conexion) && isset($base_dn) && isset($filter)) {
                return ldap_search($conexion, utf8_encode($base_dn), array_a_utf8($filter), array_a_utf8($attributes));
            } else {
                throw new toba_error('Error al buscar en el servidor LDAP: defina la conexion, la base y el filtro.');
            }
        } catch (Exception $e) {
            throw new toba_error($e->getMessage());
        }
    }

    public static function buscar_hijos($conexion, $base_dn, $filter, array $attributes = null)
    {
        try {
            if (isset($conexion) && isset($base_dn) && isset($filter)) {
                return ldap_list($conexion, utf8_encode($base_dn), array_a_utf8($filter), array_a_utf8($attributes));
            } else {
                throw new toba_error('Error al buscar hijos en el servidor LDAP: defina la conexion, la base y el filtro.');
            }
        } catch (Exception $e) {
            throw new toba_error($e->getMessage());
        }
    }

    public static function get_entradas($conexion, $busqueda)
    {
        try {
            if (isset($conexion) && isset($busqueda)) {
                return ldap_get_entries($conexion, $busqueda);
            } else {
                throw new toba_error('Error al determinar las entradas en el servidor LDAP: defina la conexion y busqueda.');
            }
        } catch (Exception $e) {
            throw new toba_error($e->getMessage());
        }
    }

    public static function set_entrada($conexion, $dn, $entrada)
    {
        self::abm_entrada('modif', $conexion, $dn, $entrada, 'Error al modificar la entrada en el servidor LDAP');
    }

    public static function add_entrada($conexion, $dn, $entrada)
    {
        self::abm_entrada('alta', $conexion, $dn, $entrada, 'Error al agregar la entrada en el servidor LDAP');
    }

    public static function delete_entrada($conexion, $dn)
    {
        self::abm_entrada('baja', $conexion, $dn, null, 'Error al eliminar la entrada en el servidor LDAP');
    }

    public static function existe_entrada($conexion, $base_dn, $filter, $valor_filter)
    {
        $busqueda = self::buscar($conexion, $base_dn, sprintf($filter, $valor_filter), ['*']);

        $entradas = self::get_entradas($conexion, $busqueda);
        if (isset($entradas['count']) && $entradas['count'] == 1 && !empty($entradas[0])) {
            return true;
        }

        return false;
    }

    public static function existe_entrada_hijos($conexion, $base_dn, $filter, $valor_filter)
    {
        $busqueda = self::buscar_hijos($conexion, $base_dn, sprintf($filter, $valor_filter), ['*']);

        $entradas = self::get_entradas($conexion, $busqueda);
        if (isset($entradas['count']) && $entradas['count'] == 1 && !empty($entradas[0])) {
            return true;
        }

        return false;
    }

    public static function buscar_y_get_entradas($conexion, $base_dn, $filter, $valor_filter, $valores_obtener)
    {
        $busqueda = self::buscar($conexion, $base_dn, sprintf($filter, $valor_filter), $valores_obtener);

        return self::get_entradas($conexion, $busqueda);
    }

    public static function buscar_hijos_y_get_entradas($conexion, $base_dn, $filter, $valor_filter, $valores_obtener)
    {
        $busqueda = self::buscar_hijos($conexion, $base_dn, sprintf($filter, $valor_filter), $valores_obtener);

        return self::get_entradas($conexion, $busqueda);
    }

    private static function abm_entrada($tipo, $conexion, $dn, $entrada, $mensaje)
    {
        try {
            if (isset($conexion) && isset($dn)) {
                $res = false;
                switch ($tipo) {
                    case 'modif': {
                        $res = ldap_modify($conexion, utf8_encode($dn), array_a_utf8($entrada));
                        break;
                    }
                    case 'alta': {
                        $res = ldap_add($conexion, utf8_encode($dn), array_a_utf8($entrada));
                        break;
                    }
                    case 'baja': {
                        $res = ldap_delete($conexion, utf8_encode($dn));
                        break;
                    }
                }
                if ($res === false) {
                    throw new toba_error($mensaje);
                }
            } else {
                throw new toba_error("$mensaje: defina la conexion, el dn y la entrada.");
            }
        } catch (Exception $e) {
            toba::logger()->error($e->getMessage());
            throw new toba_error($e->getMessage());
        }
    }
}
