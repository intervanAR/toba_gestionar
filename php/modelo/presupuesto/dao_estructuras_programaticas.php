<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of dao_estructuras_programaticas
 *
 * @author ddiluca
 */
class dao_estructuras_programaticas {

    static public function get_estructuras($filtro = array()) {
        $where = "1=1";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'PRES', '1=1');
        $sql = "SELECT PRES.*
                FROM PR_ESTRUCTURAS PRES
                WHERE $where
                ORDER BY PRES.ID_ESTRUCTURA ASC";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }

    static public function get_estructura_x_id($id_estructura) {
        $sql = "SELECT PRES.*
                FROM PR_ESTRUCTURAS PRES
                WHERE PRES.ID_ESTRUCTURA = " . quote($id_estructura) . ";";
        $datos = toba::db()->consultar_fila($sql);
        return $datos;
    }

    static public function get_lov_estructuras_x_id($id_estructura) {
        $sql = "SELECT PRES.*, PRES.ID_ESTRUCTURA ||' - '|| PRES.DESCRIPCION AS LOV_DESRIPCION
                FROM PR_ESTRUCTURAS PRES                
                WHERE PRES.ID_ESTRUCTURA = " . quote($id_estructura) . "
                ORDER BY lov_descripcion ASC;";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }

    static public function get_lov_estructuras_x_nombre($nombre, $filtro = array()) {
        if (isset($nombre)) {
            $trans_id = ctr_construir_sentencias::construir_translate_ilike('id_estructura', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
            $where = "($trans_id OR $trans_descripcion)";
        } else
            $where = '1=1';
        $where .= 'AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'PRES', '1=1');
        $sql = "SELECT PRES.*, PRES.ID_ESTRUCTURA ||' - '|| PRES.DESCRIPCION AS LOV_DESCRIPCION
                FROM PR_ESTRUCTURAS PRES                
                WHERE $where
                ORDER BY lov_descripcion ASC;";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }

    static public function estructura_en_formulacion($id_estructura) {
        $sql = "SELECT PKG_PR_ESTRUCTURAS.ESTRUCTURA_EN_FORMULACION(" . quote($id_estructura) . ") AS en_formulacion FROM DUAL";
        $resultado = toba::db()->consultar_fila($sql);
        if (isset($resultado) && !empty($resultado)) {
            if ($resultado['en_formulacion'] == 'S') {
                return true;
            } elseif ($resultado['en_formulacion'] == 'N') {
                return false;
            }
        }
    }
	

    static public function copiar_estructuras($id_structura_a, $id_estructura_b) {
        try {
            $sql = "BEGIN :resultado := PKG_PR_ESTRUCTURAS.COPIAR_ESTRUCTURA(:ID_ESTRUCTURA_A, :ID_ESTRUCTURA_B); END;";
            $parametros = array(array('nombre' => 'ID_ESTRUCTURA_A',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $id_structura_a),
                array('nombre' => 'ID_ESTRUCTURA_B',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $id_estructura_b),
                array('nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''),
            );
            toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            if ($resultado[2]['valor'] == 'OK') {
                toba::db()->cerrar_transaccion();
            } else {
                toba::db()->abortar_transaccion();
            }
            return $resultado[2]['valor'];
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
            toba::db()->abortar_transaccion();
        }
    }

    static public function get_lov_programas_x_nombre($nombre, $filtro = array()) {
        if (isset($nombre)) {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('cod_programa', $nombre);
            $trans_codigo_masc = ctr_construir_sentencias::construir_translate_ilike('pkg_pr_programas.mascara_aplicar(pp.cod_programa)', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
            $where = "($trans_codigo OR $trans_codigo_masc OR $trans_descripcion)";
        } else
            $where = '1=1';
        $where .=" AND (PKG_PR_PROGRAMAS.ACTIVO(pp.ID_PROGRAMA) = 'S' 
                   AND PKG_PR_PROGRAMAS.IMPUTABLE(pp.ID_PROGRAMA) = 'S' 
                   AND pp.ID_ENTIDAD = " . $filtro['id_entidad'] . ")";
        unset($filtro['id_entidad']);
        $where .= 'AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'pp', '1=1');
        $sql = "SELECT  pp.*, 
                        pkg_pr_programas.mascara_aplicar(pp.cod_programa) ||' - '|| pkg_pr_programas.cargar_descripcion(pp.id_programa) as lov_descripcion
                FROM PR_PROGRAMAS pp
                WHERE $where
                ORDER BY lov_descripcion ASC;";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }
    
    
	///////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////
	/////////////////////     UI_ITEMS   //////////////////////////////////
	///////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////
	
    static public function programa_en_formulacion($id_estructura, $id_entidad, $id_programa) {
        $sql = "SELECT PKG_PR_ESTRUCTURAS.PROGRAMA_EN_FORMULACION(" . quote($id_estructura) . "," . quote($id_entidad) . "," . quote($id_programa) . ") ui_en_formulacion FROM DUAL";
        $resultado = toba::db()->consultar_fila($sql);
        return $resultado;
    }

    static public function entidad_en_formulacion($id_estructura, $id_entidad) {
        $sql = "SELECT PKG_PR_ESTRUCTURAS.ENTIDAD_EN_FORMULACION(" .quote($id_estructura). "," .quote($id_entidad). ") ui_en_formulacion FROM DUAL";
        $resultado = toba::db()->consultar_fila($sql);
        return $resultado;
    }

}

?>
