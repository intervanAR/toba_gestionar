<?php

class dao_cambios_imputaciones {

    static public function get_comprobantes_cambio_imputacion_gastos($filtro = array()) {
        $where = "1=1";
        if (isset($filtro['observaciones'])) {
            $where .= "AND " . ctr_construir_sentencias::construir_translate_ilike('acig.observaciones', $filtro['observaciones']);
            unset($filtro['observaciones']);
        }
		
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'acig', '1=1');

        $sql = "SELECT	acig.*,
        				decode(acig.aprobado,'S','Si','No') aprobado_format,
        				decode(acig.anulado,'S','Si','No') anulado_format, 
						acig.id_cambio_imp_gasto id_cambio_imp, 
						to_char(acig.fecha_comprobante, 'dd/mm/yyyy') fecha_comprobante_format, 
						to_char(acig.fecha_anulacion, 'dd/mm/yyyy') fecha_anulacion_format, 
						atcig.descripcion tipo_cambio_des,
						trim(to_char(acig.importe, '$999,999,999,990.00')) as importe_format
                FROM AD_CAMBIOS_IMP_GASTOS acig
				JOIN AD_TIPOS_CAMBIOS_IMP_GAS atcig ON acig.tipo_cambio =  atcig.tipo_cambio
				WHERE  $where
				ORDER BY acig.id_cambio_imp_gasto DESC;";

        $datos = toba::db()->consultar($sql);

        return $datos;
    }
	
	public static function get_tipos_cambios_imputacion_gastos($filtro=array()) {
		$where = " 1=1 ";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ADTICAIMGA', '1=1');
        $sql_sel = "SELECT	ADTICAIMGA.* ,
							ADTICAIMGA.TIPO_CAMBIO || ' - ' || ADTICAIMGA.DESCRIPCION LOV_DESCRIPCION
                        ,krcc.NRO_CUENTA_CORRIENTE ||' - '||krcc.DESCRIPCION cuenta_corriente 
                        ,krtt.COD_TIPO_TRANSACCION||' - '||krtt.DESCRIPCION tipo_transaccion 
  FROM ad_tipos_cambios_imp_gas adticaimga, kr_cuentas_corriente krcc, KR_TIPOS_TRANSACCION krtt
  where adticaimga.ID_CUENTA_CORRIENTE = krcc.ID_CUENTA_CORRIENTE
  and adticaimga.COD_TIPO_TRANSACCION = krtt.COD_TIPO_TRANSACCION and $where
					ORDER BY adticaimga.DESCRIPCION";
        $datos = toba::db()->consultar($sql_sel);
        return $datos;
  	}
	
	public static function get_importes_encabezado_cambio_imputacion_gasto($id_cambio_imp_gasto) 
	{
        if (isset($id_cambio_imp_gasto)) {
            $sql_sel = "SELECT  acig.importe
					FROM ad_cambios_imp_gastos acig
					WHERE acig.id_cambio_imp_gasto = " . quote($id_cambio_imp_gasto) . ";";
            $datos = toba::db()->consultar_fila($sql_sel);
            return $datos;
        } else {
            return array();
        }
    }
	
	static public function get_datos_extras_cambio_imputacion_gasto_x_id($id_cambio_imp_gasto) 
	{
        if (isset($id_cambio_imp_gasto)) {
            $sql = "SELECT	acig.anulado,
							acig.aprobado,
							acig.fecha_anulacion
					FROM ad_cambios_imp_gastos acig
					WHERE acig.id_cambio_imp_gasto = " . quote($id_cambio_imp_gasto) . ";";

            $datos = toba::db()->consultar_fila($sql);

            return $datos;
        } else {
            return array();
        }
    }
	
	static public function get_cambio_imputacion_gasto_x_id($id_cambio_imp_gasto) 
	{
        if (isset($id_cambio_imp_gasto)) {
            $sql = "SELECT	acig.*
					FROM ad_cambios_imp_gastos acig
					WHERE acig.id_cambio_imp_gasto = " . quote($id_cambio_imp_gasto) . ";";

            $datos = toba::db()->consultar_fila($sql);
            return $datos;
        } else {
            return array();
        }
    }
	
	static public function get_cambio_imputacion_recurso_x_id($id_cambio_imp_recurso) 
	{
        if (isset($id_cambio_imp_recurso)) {
            $sql = "SELECT	acir.*
					FROM ad_cambios_imp_recursos acir
					WHERE acir.id_cambio_imp_recurso = " . quote($id_cambio_imp_recurso) . ";";

            $datos = toba::db()->consultar_fila($sql);
            return $datos;
        } else {
            return array();
        }
    }
	
	static public function confirmar_cambio_imputacion_gasto($id_cambio_imp_gasto) {
        if (isset($id_cambio_imp_gasto)) {
            $mensaje_error = 'Error en la confirmación del comprobante de cambio de imputación de gastos.';
            try {
                toba::db()->abrir_transaccion();
                $sql = "BEGIN :resultado := PKG_KR_TRANS_CMB.CONFIRMAR_CAMBIO_IMP_GASTO(:id_cambio_imp_gasto); END;";

                $parametros = array(array(	'nombre' => 'id_cambio_imp_gasto',
											'tipo_dato' => PDO::PARAM_INT,
											'longitud' => 32,
											'valor' => $id_cambio_imp_gasto),
									array(	'nombre' => 'resultado',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 4000,
											'valor' => ''),
								);

                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                $valor_resultado = $resultado[count($resultado) - 1]['valor'];
                if ($valor_resultado != 'OK') {
                    toba::notificacion()->error($valor_resultado);
                    toba::logger()->error($valor_resultado);
                    toba::db()->abortar_transaccion();
                } else {
					toba::notificacion()->info('El comprobante de cambio de imputación de gastos se confirmó exitosamente.');
					toba::db()->cerrar_transaccion();
				}
            } catch (toba_error_db $e_db) {
                toba::notificacion()->error($mensaje_error . ' ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
                toba::logger()->error($mensaje_error . ' ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
                toba::db()->abortar_transaccion();
            } catch (toba_error $e) {
                toba::notificacion()->error($mensaje_error . ' ' . $e->get_mensaje());
                toba::logger()->error($mensaje_error . ' ' . $e->get_mensaje());
                toba::db()->abortar_transaccion();
            }
        }
    }
	
	static public function anular_cambio_imputacion_gasto($id_cambio_imp_gasto, $fecha_anulacion, $con_transaccion = true) {
        if (isset($id_cambio_imp_gasto) && isset($fecha_anulacion)) {
            $sql = "BEGIN :resultado := PKG_KR_TRANS_CMB.ANULAR_CAMBIO_IMP_GASTO(:id_cambio_imp_gasto, to_date(substr(:fecha_anulacion,1,10),'yyyy-mm-dd')); END;";

            $parametros = array(array(	'nombre' => 'id_cambio_imp_gasto',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $id_cambio_imp_gasto),
								array(	'nombre' => 'fecha_anulacion',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 32,
										'valor' => $fecha_anulacion),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
							);
            $resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, 'El comprobante de cambio de imputación de gastos se anuló exitosamente.', 'Error en la anulación del comprobante de cambio de imputación de gastos.', $con_transaccion);
            return $resultado[2]['valor'];
        }
    }
	
	static public function get_comprobantes_cambio_imputacion_recursos($filtro = array(), $orden = array()) {
        
        $where = '';
		$sql1 = "SELECT PKG_KR_USUARIOS.in_ua_tiene_acceso(upper('".toba::usuario()->get_id()."')) unidades_ad FROM DUAL";
        $res = toba::db()->consultar_fila($sql1); 
        $where = "((acir.COD_UNIDAD_ADMINISTRACION IN ".$res['unidades_ad']."))";
        
        if (isset($filtro['observaciones'])) {
            $where .= "AND " . ctr_construir_sentencias::construir_translate_ilike('acir.observaciones', $filtro['observaciones']);
            unset($filtro['observaciones']);
        }
		
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'acir', '1=1');

        $sql = "SELECT	acir.*,
        				decode(acir.aprobado,'S','Si','No') aprobado_format,
        			    decode(acir.anulado,'S','Si','No') anulado_format, 
						acir.id_cambio_imp_recurso id_cambio_imp, 
						to_char(acir.fecha_comprobante, 'dd/mm/yyyy') fecha_comprobante_format, 
						to_char(acir.fecha_anulacion, 'dd/mm/yyyy') fecha_anulacion_format, 
						atcir.descripcion tipo_cambio_des,
						trim(to_char(acir.importe, '$999,999,999,990.00')) as importe_format
                FROM AD_CAMBIOS_IMP_RECURSOS acir
				JOIN AD_TIPOS_CAMBIOS_IMP_REC atcir ON acir.tipo_cambio =  atcir.tipo_cambio
				WHERE  $where
				ORDER BY acir.id_cambio_imp_recurso DESC;";

        $datos = toba::db()->consultar($sql);

        return $datos;
    }
	
	public static function get_tipos_cambios_imputacion_recursos($filtro=array()) {
		$where = " 1=1 ";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ADTICAIMRE', '1=1');
        $sql_sel = "SELECT adticaimre.*,
                              adticaimre.tipo_cambio
                           || ' - '
                           || adticaimre.descripcion lov_descripcion,
                           krtt.COD_TIPO_TRANSACCION ||' - '||krtt.DESCRIPCION tipo_transaccion
                      FROM ad_tipos_cambios_imp_rec adticaimre, kr_tipos_transaccion krtt
                     WHERE adticaimre.cod_tipo_transaccion = krtt.cod_tipo_transaccion and $where
					ORDER BY adticaimre.DESCRIPCION";
        $datos = toba::db()->consultar($sql_sel);
        return $datos;
  	}
	
	public static function get_importes_encabezado_cambio_imputacion_recurso($id_cambio_imp_recurso) 
	{
        if (isset($id_cambio_imp_recurso)) {
            $sql_sel = "SELECT  acir.importe
					FROM ad_cambios_imp_recursos acir
					WHERE acir.id_cambio_imp_recurso = " . quote($id_cambio_imp_recurso) . ";";
            $datos = toba::db()->consultar_fila($sql_sel);
            return $datos;
        } else {
            return array();
        }
    }
	
	static public function get_datos_extras_cambio_imputacion_recurso_x_id($id_cambio_imp_recurso) 
	{
        if (isset($id_cambio_imp_recurso)) {
            $sql = "SELECT	acir.anulado,
							acir.aprobado,
							acir.fecha_anulacion
					FROM ad_cambios_imp_recursos acir
					WHERE acir.id_cambio_imp_recurso = " . quote($id_cambio_imp_recurso) . ";";

            $datos = toba::db()->consultar_fila($sql);

            return $datos;
        } else {
            return array();
        }
    }
	
	static public function confirmar_cambio_imputacion_recurso($id_cambio_imp_recurso) {
        if (isset($id_cambio_imp_recurso)) {
            $mensaje_error = 'Error en la confirmación del comprobante de cambio de imputación de recursos.';
            try {
                toba::db()->abrir_transaccion();
                $sql = "BEGIN :resultado := PKG_KR_TRANS_CMB.CONFIRMAR_CAMBIO_IMP_RECURSO(:id_cambio_imp_recurso); END;";

                $parametros = array(array(	'nombre' => 'id_cambio_imp_recurso',
											'tipo_dato' => PDO::PARAM_INT,
											'longitud' => 32,
											'valor' => $id_cambio_imp_recurso),
									array(	'nombre' => 'resultado',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 4000,
											'valor' => ''),
								);

                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                $valor_resultado = $resultado[count($resultado) - 1]['valor'];
                if ($valor_resultado != 'OK') {
                    toba::notificacion()->error($valor_resultado);
                    toba::logger()->error($valor_resultado);
                    toba::db()->abortar_transaccion();
                } else {
					toba::notificacion()->info('El comprobante de cambio de imputación de recurso se confirmó exitosamente.');
					toba::db()->cerrar_transaccion();
				}
            } catch (toba_error_db $e_db) {
                toba::notificacion()->error($mensaje_error . ' ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
                toba::logger()->error($mensaje_error . ' ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
                toba::db()->abortar_transaccion();
            } catch (toba_error $e) {
                toba::notificacion()->error($mensaje_error . ' ' . $e->get_mensaje());
                toba::logger()->error($mensaje_error . ' ' . $e->get_mensaje());
                toba::db()->abortar_transaccion();
            }
        }
    }
	
	static public function anular_cambio_imputacion_recurso($id_cambio_imp_recurso, $fecha_anulacion, $con_transaccion = true) {
        if (isset($id_cambio_imp_recurso) && isset($fecha_anulacion)) {
            $sql = "BEGIN :resultado := PKG_KR_TRANS_CMB.ANULAR_CAMBIO_IMP_RECURSO(:id_cambio_imp_recurso, to_date(substr(:fecha_anulacion,1,10),'yyyy-mm-dd')); END;";

            $parametros = array(array(	'nombre' => 'id_cambio_imp_recurso',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $id_cambio_imp_recurso),
								array(	'nombre' => 'fecha_anulacion',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 32,
										'valor' => $fecha_anulacion),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
							);
            $resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, 'El comprobante de cambio de imputación de recursos se anuló exitosamente.', 'Error en la anulación del comprobante de cambio de imputación de recursos.', $con_transaccion);
            return $resultado[2]['valor'];
        }
    }
	
}

?>