<?php

class dao_unidades_ejecutoras {

    static public function get_unidades_ejecutoras($filtro = array(), $fuente=null) {
        $where = "1=1";
		if (isset($filtro['con_ue_usuario']) && $filtro['con_ue_usuario'] == '1') {
            $usuario = toba::usuario()->get_id();
            $where .=" AND PKG_KR_USUARIOS.TIENE_UE((upper('" . $usuario . "')),kue.COD_UNIDAD_EJECUTORA) = 'S'";
            unset($filtro['con_ue_usuario']);
        }
        if (isset($filtro['usuario_activa'])) {
            $where .= " AND kue.ACTIVA = 'S'";
            unset($filtro['usuario_activa']);
        }
        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'kue', '1=1');
        $sql = "SELECT	kue.*,
						kue.COD_UNIDAD_EJECUTORA || ' - ' || kue.DESCRIPCION as lov_descripcion
                FROM kr_unidades_ejecutoras kue
                WHERE $where
				ORDER BY COD_UNIDAD_EJECUTORA ASC;";
        $datos = toba::db($fuente)->consultar($sql);
        return $datos;
    }

    static public function get_unidades_ejecutoras_lista($filtro = array()) {
        $where = "1=1";
        if (isset($filtro['con_ue_usuario']) && $filtro['con_ue_usuario'] == '1') {
            $usuario = toba::usuario()->get_id();
            $where .=" AND PKG_KR_USUARIOS.TIENE_UE((upper('" . $usuario . "')),KRUNEJ.COD_UNIDAD_EJECUTORA) = 'S'";
            unset($filtro['con_ue_usuario']);
        }
        if (isset($filtro['usuario_activa'])) {
            $where .= " AND KRUNEJ.ACTIVA = 'S'";
            unset($filtro['usuario_activa']);
        }
        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'KRUNEJ', '1=1');
        $sql = "SELECT KRUNEJ.COD_UNIDAD_EJECUTORA, KRUNEJ.COD_UNIDAD_EJECUTORA || ' - ' || KRUNEJ.DESCRIPCION as lov_descripcion
                FROM kr_unidades_ejecutoras KRUNEJ
                WHERE $where
                ORDER BY lov_descripcion ASC;";
        $datos = toba::db()->consultar($sql);
        $unidad_0 = array("cod_unidad_ejecutora" => 0, "lov_descripcion" => '-- Seleccione --');
        array_unshift($datos, $unidad_0);
        return $datos;
    }

    static public function get_lov_unidad_ejecutora($codigo) {
        if (isset($codigo)) {
            $sql = "SELECT	KRUNEJ.*, 
							KRUNEJ.COD_UNIDAD_EJECUTORA || ' - ' || KRUNEJ.DESCRIPCION as lov_descripcion
					FROM KR_UNIDADES_EJECUTORAS KRUNEJ
					WHERE KRUNEJ.COD_UNIDAD_EJECUTORA = " . quote($codigo) . ";";
            $datos = toba::db()->consultar_fila($sql);
            if (isset($datos) && !empty($datos) && isset($datos['lov_descripcion'])) {
                return $datos['lov_descripcion'];
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

    static public function get_lov_unidades_ejecutoras_x_nombre($nombre, $filtro) {
        if (isset($nombre)) {
            $trans_cod_ue = ctr_construir_sentencias::construir_translate_ilike('KRUNEJ.COD_UNIDAD_EJECUTORA', $nombre);
            $trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('KRUNEJ.DESCRIPCION', $nombre);
            $where = "($trans_cod_ue OR $trans_descricpcion)";
        } else {
            $where = "1=1";
        }
        if (isset($filtro['con_ue_usuario']) && $filtro['con_ue_usuario'] == '1') {
            $usuario = toba::usuario()->get_id();
            $where .=" AND PKG_KR_USUARIOS.TIENE_UE((upper('" . $usuario . "')),KRUNEJ.COD_UNIDAD_EJECUTORA) = 'S'";
            unset($filtro['con_ue_usuario']);
        }
        if (isset($filtro['usuario_activa'])) {
            $where .= " AND KRUNEJ.ACTIVA = 'S'";
            unset($filtro['usuario_activa']);
        }
        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'KRUNEJ', '1=1');
        $sql = "SELECT	KRUNEJ.*, KRUNEJ.cod_unidad_ejecutora || ' - ' || KRUNEJ.descripcion lov_descripcion
                FROM KR_UNIDADES_EJECUTORAS KRUNEJ
                WHERE  $where
                UNION 
                SELECT krunej.*, krunej.cod_unidad_ejecutora || ' - '|| krunej.descripcion lov_descripcion
                FROM kr_unidades_ejecutoras krunej
                WHERE krunej.COD_UNIDAD_EJECUTORA = 0
                ORDER BY lov_descripcion;";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }

    //Devuelve la unidad ejecutora correspondiente al usuario logueado en el sistema.
    static public function get_unidad_ejecutora_x_usuario() {
        $id_usuario = strtoupper(toba::usuario()->get_id());
        try {
            $sql = "BEGIN :resultado := pkg_kr_usuarios.ue_por_defecto(:id_usuario);END;";
            $parametros = array(array('nombre' => 'id_usuario',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $id_usuario),
                array('nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''),
            );
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            return $resultado[1]['valor'];
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
            toba::db()->abortar_transaccion();
        }
        return null;
    }
	
	static public function get_descripcion_unidad_ejecutora($codigo) {
        if (isset($codigo)) {
            $sql = "SELECT	KRUNEJ.DESCRIPCION
					FROM KR_UNIDADES_EJECUTORAS KRUNEJ
					WHERE KRUNEJ.COD_UNIDAD_EJECUTORA = " . quote($codigo) . ";";
            $datos = toba::db()->consultar_fila($sql);
            if (isset($datos) && !empty($datos) && isset($datos['descripcion'])) {
                return $datos['descripcion'];
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

}

?>
