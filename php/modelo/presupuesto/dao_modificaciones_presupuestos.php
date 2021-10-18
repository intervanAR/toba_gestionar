<?php
class dao_modificaciones_presupuestos{

	static public function get_modificiones_presupusetos ($filtro=array()){
		$sql1 = "SELECT PKG_KR_USUARIOS.in_ua_tiene_acceso(upper('".toba::usuario()->get_id()."')) unidades_ad
                 FROM DUAL";
        $res = toba::db()->consultar_fila($sql1);
        $where = "(PRMO.COD_UNIDAD_ADMINISTRACION IN ".$res['unidades_ad'].")";
        if(isset($filtro))
			$where .= " AND ".ctr_construir_sentencias::get_where_filtro($filtro, 'PRMO', '1=1');
	    $sql = "SELECT PRMO.*,
			       		to_char(PRMO.fecha_aprueba, 'dd/mm/yyyy') fecha_aprueba_format,
			       		to_char(PRMO.fecha_anula, 'dd/mm/yyyy') fecha_anula_format,
			       		to_char(PRMO.fecha_carga, 'dd/mm/yyyy') fecha_carga_format,
			       		to_char(PRMO.fecha_modificacion, 'dd/mm/yyyy') fecha_modificacion_format,
      	 				to_char(PRMO.fecha_modificacion_anulacion, 'dd/mm/yyyy') fecha_mod_anula_format,
      	 				KREJ.DESCRIPCION AS ejercicio,
      	 				KRUNAD.DESCRIPCION AS unidad_administracion
				FROM PR_MODIFICACIONES PRMO
     				 LEFT JOIN KR_EJERCICIOS KREJ ON PRMO.ID_EJERCICIO = KREJ.ID_EJERCICIO
     				 LEFT JOIN KR_UNIDADES_ADMINISTRACION KRUNAD ON PRMO.COD_UNIDAD_ADMINISTRACION = KRUNAD.COD_UNIDAD_ADMINISTRACION
     			WHERE $where
     			ORDER BY PRMO.ID_MODIFICACION DESC;";
	            $datos = toba::db()->consultar($sql);
	            foreach ($datos as $key => $value) {
	            	if ($value['tipo_modificacion'] == 'A')
	            		$datos[$key]['tipo_modificacion_format'] ='Adición';
	            		elseif ($value['tipo_modificacion'] == 'T')
	            			$datos[$key]['tipo_modificacion_format'] = 'Traspaso';
	            			elseif ($value['tipo_modificacion'] == 'D')
	            				$datos[$key]['tipo_modificacion_format'] = 'Disminución';
	            }
	            return $datos;
	}

	static public function get_modificacion_presupuestaria_x_id ($id_modificacion){
		$sql = "SELECT *
				FROM PR_MODIFICACIONES
				WHERE ID_MODIFICACION = ".quote($id_modificacion).";";
		$datos = toba::db()->consultar_fila($sql);
		return $datos;
	}

	static public function get_lov_ejercicios_x_id ($id_ejercicio){
		if (isset($id_ejercicio)) {
            $sql = "SELECT KREJ.*, KREJ.NRO_EJERCICIO ||' - '|| KREJ.DESCRIPCION AS LOV_DESCRIPCION
					FROM KR_EJERCICIOS KREJ
					WHERE KREJ.ID_EJERCICIO = ".quote($id_ejercicio)."
					ORDER BY LOV_DESCRIPCION DESC;";
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

	static public function get_lov_ejercicios_x_nombre ($nombre, $filtro = array()){
        if (isset($nombre)) {
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_ejercicio', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_nro OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
      	$sql = "SELECT KREJ.*, KREJ.NRO_EJERCICIO ||' - '|| KREJ.DESCRIPCION AS LOV_DESCRIPCION
				FROM KR_EJERCICIOS KREJ
				WHERE KREJ.ABIERTO = 'S'
				      AND KREJ.CERRADO = 'N'
				      AND $where
				ORDER BY LOV_DESCRIPCION DESC;";
        $datos = toba::db()->consultar($sql);
        return $datos;
	}

  static public function aprobar_modificacion_presupuestaria ($id_modificacion) {
        if (isset($id_modificacion)) {
            try {
                $sql = "BEGIN :resultado := pkg_pr_formulacion.aprobar_modificacion(:id_modificacion);END;";
                $parametros = array(array('nombre' => 'id_modificacion',
                                        'tipo_dato' => PDO::PARAM_INT,
                                        'longitud' => 32,
                                        'valor' => $id_modificacion),
                                    array('nombre' => 'resultado',
                                        'tipo_dato' => PDO::PARAM_STR,
                                        'longitud' => 4000,
                                        'valor' => ''),
                                    );
                toba::db()->abrir_transaccion();
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                if ($resultado[1]['valor'] == 'OK'){
                    toba::db()->cerrar_transaccion();
                    toba::notificacion()->info("Modificacion Aprobada con exito.");
                }else{
                    toba::db()->abortar_transaccion();
                    toba::notificacion()->error("Error al aprobar la Modificacion: ".$resultado[1]['valor']);
                }
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
        }
    }

	static public function anular_modificacion_presupuestaria ($id_modificacion) {
        if (isset($id_modificacion)) {
            try {
                $sql = "BEGIN :resultado := pkg_pr_formulacion.anular_modificacion(:id_modificacion);END;";
                $parametros = array(array('nombre' => 'id_modificacion',
                                        'tipo_dato' => PDO::PARAM_INT,
                                        'longitud' => 32,
                                        'valor' => $id_modificacion),
                                    array('nombre' => 'resultado',
                                        'tipo_dato' => PDO::PARAM_STR,
                                        'longitud' => 4000,
                                        'valor' => ''),
                                    );
                toba::db()->abrir_transaccion();
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                if ($resultado[1]['valor'] == 'OK'){
                    toba::db()->cerrar_transaccion();
                    toba::notificacion()->info("Modificacion Anulada con exito.");
                }else{
                    toba::db()->abortar_transaccion();
                    toba::notificacion()->error($resultado[1]['valor']);
                }
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
        }
    }

	static public function sacar_pendiente ($id_modificacion) {
        if (isset($id_modificacion)) {
        	$sql = "BEGIN :resultado := pkg_pr_formulacion.sacar_pendiente_modificacion(:id_modificacion);END;";
            $parametros = array(array('nombre' => 'id_modificacion',
            	                      'tipo_dato' => PDO::PARAM_INT,
                                      'longitud' => 32,
                                      'valor' => $id_modificacion),
                                array('nombre' => 'resultado',
                                      'tipo_dato' => PDO::PARAM_STR,
                                      'longitud' => 4000,
                                      'valor' => ''),);

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros,'','',true);
      		 if (!empty($resultado)){
            	return $resultado[1]['valor'];
			}else{
				toba::notificacion()->error("La operacion no se pudo completar. Ver log del sistema.");
				return "La operacion no se pudo completar. Ver log del sistema.";
			}
        }
    }

	static public function poner_pendiente ($id_modificacion) {
        if (isset($id_modificacion)) {
        	$sql = "BEGIN :resultado := pkg_pr_formulacion.pendiente_modificacion(:id_modificacion);END;";
            $parametros = array(array('nombre' => 'id_modificacion',
            	                      'tipo_dato' => PDO::PARAM_INT,
                                      'longitud' => 32,
                                      'valor' => $id_modificacion),
                                array('nombre' => 'resultado',
                                      'tipo_dato' => PDO::PARAM_STR,
                                      'longitud' => 4000,
                                      'valor' => ''),);

			$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', true);
			if (!empty($resultado)){
            	return $resultado[1]['valor'];
			}else{
				toba::notificacion()->error("La operacion no se pudo completar. Ver log del sistema.");
				return "La operacion no se pudo completar. Ver log del sistema.";
			}
        }
    }

	static public function get_cant_recursos ($id_modificacion){
		$sql = "SELECT COUNT(1) as cant_recursos
    			FROM pr_modificaciones_recursos
    			WHERE id_modificacion = ".quote($id_modificacion).";";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cant_recursos'];
	}

	static public function get_cant_gastos ($id_modificacion){
		$sql = "SELECT COUNT(1) as cant_gastos
    			FROM pr_modificaciones_gastos
    			WHERE id_modificacion = ".quote($id_modificacion).";";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cant_gastos'];
	}
	////////////////////////////////////////////////////////////////
	/////////// UI_ITEMS   /////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function get_uis_fuente_financiera ($cod_recurso){
		//para la carga de ui_cod_fuente_finan y ui_desc_fuente_finan.
		$sql = "SELECT pkg_pr_fuentes.cargar_descripcion(pkg_pr_recursos.cod_fuente(".$cod_recurso.")) AS ui_desc_fuente_financ,
       				   pkg_pr_recursos.cod_fuente(".$cod_recurso.") AS ui_cod_fuente_financ
       			FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos;
	}
	static public function get_ui_cod_fuente_financ ($cod_recurso){
		$sql = "SELECT pkg_pr_recursos.cod_fuente(".$cod_recurso.") AS ui_cod_fuente_financ
       			FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['ui_cod_fuente_financ'];
	}
	static public function get_ui_desc_fuente_financ ($cod_recurso){
		$sql = "SELECT pkg_pr_fuentes.cargar_descripcion(pkg_pr_recursos.cod_fuente(".$cod_recurso.")) AS ui_desc_fuente_financ
       			FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['ui_desc_fuente_financ'];
	}
	static public function get_ui_afectacion_especifica ($cod_fuente_financiera){
		$sql = "SELECT pkg_pr_fuentes.afectacion_especifica(".$cod_fuente_financiera.") AS ui_afectacion_especifica
				FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['ui_afectacion_especifica'];
	}
    public static function get_nuevo_id_detalle($id_modificacion)
    {
        $sql = "
            SELECT NVL(MAX(NVL(id_detalle,0)),0) + 1 id_detalle
            FROM PR_MODIFICACIONES_GASTOS
            WHERE
                id_modificacion = '$id_modificacion'
        ";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['id_detalle'];

    }

}