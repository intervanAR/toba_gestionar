<?php

class dao_unidades_administracion {

    static public function get_lov_unidades_administracion($codigo) {
        if (isset($codigo)) {
            $sql = "SELECT kua.*, kua.cod_unidad_administracion || ' - ' || kua.descripcion as lov_descripcion
		    FROM KR_UNIDADES_ADMINISTRACION kua
		    WHERE kua.cod_unidad_administracion = " . quote($codigo) . ";";
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

    static public function get_lov_unidades_administracion_x_nombre($nombre, $filtro) {
        if (isset($nombre)) {
            $trans_cod_ua = ctr_construir_sentencias::construir_translate_ilike('krunad.cod_unidad_administracion', $nombre);
            $trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('krunad.descripcion', $nombre);
            $where = "($trans_cod_ua OR $trans_descricpcion)";
        } else {
            $where = "1=1";
        }
        
		if (isset($filtro['con_ua_usuario']) && $filtro['con_ua_usuario'] == '1') {
			$usuario = toba::usuario()->get_id();
       		$where .=" AND EXISTS (	SELECT 1 
									FROM kr_usuarios_ua 
									WHERE cod_unidad_administracion = krunad.cod_unidad_administracion 
									AND usuario =upper('$usuario')
									)";
			
			unset($filtro['con_ua_usuario']);
		}
		
		if (isset($filtro['activa_usuario'])) {
			$usuario = toba::usuario()->get_id();
			$where.= " AND PKG_PR_UNIDADES_ADMINISTRACION.ACTIVA(krunad.COD_UNIDAD_ADMINISTRACION) = 'S'
						AND PKG_KR_USUARIOS.TIENE_UA(upper('".$usuario."'),krunad.COD_UNIDAD_ADMINISTRACION) = 'S'";
			
			unset($filtro['activa_usuario']);
		}
		
		if (isset($filtro['activa'])){
			$where .= " and PKG_PR_UNIDADES_ADMINISTRACION.ACTIVA(COD_UNIDAD_ADMINISTRACION) = 'S'";
			unset($filtro['activa']);
		}
		
		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'krunad', '1=1');
        
		$sql = "SELECT	krunad.*, 
						krunad.cod_unidad_administracion || ' - ' || krunad.descripcion lov_descripcion
				FROM kr_unidades_administracion krunad
                WHERE  $where
                ORDER BY lov_descripcion;";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }
	
	static public function get_des_unidad_administracion_x_codigo($codigo) {
        if (isset($codigo)) {
            $sql = "SELECT	kua.*, 
							kua.cod_unidad_administracion || ' - ' || kua.descripcion as cod_des_unidad_administracion
					FROM KR_UNIDADES_ADMINISTRACION kua
					WHERE kua.cod_unidad_administracion = " . quote($codigo) . ";";
            $datos = toba::db()->consultar_fila($sql);
            if (isset($datos) && !empty($datos) && isset($datos['cod_des_unidad_administracion'])) {
                return $datos['cod_des_unidad_administracion'];
            } else {
                return '';
            }
        } else {
            return '';
        }
    }
    //Devuelve la unidad de Administracion correspondiente al usuario logueado en el sistema
    static public function get_unidad_administracion_x_usuario (){   
            $id_usuario = strtoupper(toba::usuario()->get_id());
            try { 
                $sql = "BEGIN :resultado := pkg_kr_usuarios.ua_por_defecto(:id_usuario);END;";		
                $parametros = array ( array(  'nombre' => 'id_usuario', 
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 32,
                                                'valor' => $id_usuario),
                                      array(  'nombre' => 'resultado', 
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 4000,
                                                'valor' => ''),
                                );
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);     
                return $resultado[1]['valor'];
            } catch (toba_error_db $e_db) {
                toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
                toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
                toba::db()->abortar_transaccion();
            } catch (toba_error $e) {
                toba::notificacion()->error('Error '.$e->get_mensaje());
                toba::logger()->error('Error '.$e->get_mensaje());
                toba::db()->abortar_transaccion();
            }
           return null;
    }
    
    public static function get_unidad_administracion_default(){

        $sql = "select usu.cod_unidad_administracion cod_unidad_administracion
                from kr_usuarios usu, kr_unidades_administracion adm
                where usu.cod_unidad_administracion = adm.cod_unidad_administracion and usu.usuario = upper('".toba::usuario()->get_id()."');";

        $datos = toba::db()->consultar_fila($sql);
        
        return $datos['cod_unidad_administracion'];
    }
	
	static public function get_unidades_administracion($filtro=array(), $fuente=null) {
        $where = "1=1";
        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'krunad', '1=1');
        
		$sql = "SELECT	krunad.*, 
						krunad.cod_unidad_administracion || ' - ' || krunad.descripcion lov_descripcion
				FROM kr_unidades_administracion krunad
			    WHERE  $where";
        $datos = toba::db($fuente)->consultar($sql);
        return $datos;
    }

}

?>
