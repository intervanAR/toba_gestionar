<?php

/**
 * Description of dao_transferencias_fondo
 *
 * @author lwolcan
 */
class dao_transferencias_fondo {

	static public function get_tranferencias_fondo($filtro = array(), $orden = array()) {
		//quote($dato)
		
		$desde= null;
		$hasta= null;
		if(isset($filtro['desde'])){
			$desde= $filtro['desde'];
			$hasta= $filtro['hasta'];

			unset($filtro['desde']);
			unset($filtro['hasta']);
		}
		$where = "1=1";

		if (isset($filtro['cod_unidad_ejecutora']) && $filtro['cod_unidad_ejecutora'] == '0') {
			$filtro['cod_unidad_ejecutora'] = NULL;
		}
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'atf', '1=1');

		$sql_auxiliar_ua = "select (PKG_KR_USUARIOS.in_ua_tiene_acceso(upper('" . toba::usuario()->get_id() . "'))) unidades_ua from dual";
		$conjunto_unidades_ua = toba::db()->consultar_fila($sql_auxiliar_ua);

		$sql_auxiliar_ue = "select (PKG_KR_USUARIOS.in_ue_tiene_acceso(upper('" . toba::usuario()->get_id() . "'))) unidades_ue from dual";
		$conjunto_unidades_ue = toba::db()->consultar_fila($sql_auxiliar_ue);

		$sql = "SELECT atf.id_transferencia_fondos, 
        atf.cod_unidad_administracion||'-'||krua.descripcion cod_unidad_administracion,  
       atf.nro_comprobante,
       to_char(atf.fecha_comprobante, 'dd/mm/yyyy') fecha_comprobante,        
       atf.id_cuenta_banco||'-'||kcb.descripcion id_cuenta_banco, 
       atf.id_cuenta_banco_hasta, 
       atf.cod_moneda,
       trim(to_char(atf.importe_nominal, '$999,999,999,990.00'))  importe_nominal,  
       trim(to_char(atf.importe, '$999,999,999,990.00'))  importe, 
       atf.cotizacion, 
       atf.cod_medio_pago,
       atf.id_comprobante_pago, 
       atf.usuario_carga, 
       to_char(atf.fecha_carga, 'dd/mm/yyyy') fecha_carga,        
       atf.confirmada,
       atf.usuario_confirma, 
       to_char(atf.fecha_confirma, 'dd/mm/yyyy') fecha_confirma,        
       atf.anulada, 
       to_char(atf.fecha_anulacion, 'dd/mm/yyyy') fecha_anulacion,       
       atf.usuario_anula, 
       to_char(atf.fecha_anula, 'dd/mm/yyyy') fecha_anula,
       atf.observaciones, 
       atf.id_sub_cuenta_banco,
       atf.id_sub_cuenta_banco_hasta, 
       atf.cod_unidad_ejecutora, 
       atf.id_expediente,
       decode(atf.confirmada,'S','Si','No') confirmada_format,
       decode(atf.anulada,'S','Si','No') anulada_format  
  FROM ad_transferencias_fondos atf,kr_unidades_administracion krua, kr_cuentas_banco kcb
           WHERE atf.id_cuenta_banco = kcb.id_cuenta_banco 
           and atf.cod_unidad_administracion = krua.cod_unidad_administracion
           AND   atf.COD_UNIDAD_ADMINISTRACION in " . $conjunto_unidades_ua['unidades_ua'] . "
           AND   (atf.cod_unidad_ejecutora in " . $conjunto_unidades_ue['unidades_ue'] . " OR pkg_kr_usuarios.usuario_tiene_ues(" . quote(toba::usuario()->get_id()) . ")='N')    
           AND  $where
           order by atf.id_transferencia_fondos desc";
		
		$sql= dao_varios::paginador($sql, null, $desde, $hasta,null, $orden);
		$datos = toba::db()->consultar($sql);

		/* foreach ($datos as $clave => $dato) {
		  $datos[$clave]['des_clase_comprobante'] = self::get_descripcion_clase_comprobante($datos[$clave]['clase_comprobante']);
		  } */
		return $datos;
	}

	static public function get_datos_extras_transferencia_fondos_x_id($id_transferencia_fondos) {
		if (isset($id_transferencia_fondos)) {
			$sql = "SELECT	p.anulada,
							p.confirmada,							
                        FROM AD_TRANSFERENCIAS_FONDOS p
                        WHERE p.id_transferencia_fondos = " . quote($id_transferencia_fondos);

			$datos = toba::db()->consultar_fila($sql);

			return $datos;
		} else {
			return array();
		}
	}

	static public function get_transferencia_fondos_pago_x_id($id_transferencia_fondos) {
		if (isset($id_transferencia_fondos)) {
			$sql = "SELECT	t.*,
                            to_char(t.fecha_comprobante,'dd/mm/yyyy') fecha_comprobante_format,
							amp.*,
							t.fecha_comprobante as fecha_emision,
							t.fecha_comprobante as fecha_vencimiento
                        FROM AD_transferencias_fondos t
						JOIN AD_MEDIOS_PAGO amp ON (t.cod_medio_pago = amp.cod_medio_pago)
                        WHERE t.id_transferencia_fondos = " . quote($id_transferencia_fondos) . ";";

			return toba::db()->consultar_fila($sql);
		} else {
			return array();
		}
	}

	static public function aprobar_transferencia_fondos($id_transferencia_fondos, $con_trnasaccion = true) {

		$sql = "BEGIN :resultado := pkg_kr_transferencias.confirmar_transferencia(:id_transferencia_fondos); END;";
    $parametros = array(array('nombre' => 'id_transferencia_fondos',
        'tipo_dato' => PDO::PARAM_STR,
        'longitud' => 32,
        'valor' => $id_transferencia_fondos),
      array('nombre' => 'resultado',
        'tipo_dato' => PDO::PARAM_STR,
        'longitud' => 4000,
        'valor' => ''),
    );
    $resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','',$con_trnasaccion);
    return $resultado[1]['valor'];
	}
	
	
  static public function anular_trasnferencia_fondo($id_transferencia_fondos, $fecha, $con_trnasaccion = false) {
     $sql = "BEGIN :resultado := pkg_kr_transferencias.anular_transferencia(:id_transferencia_fondos, to_date(substr(:fecha,1,10),'yyyy-mm-dd')); END;";
            $parametros = array(array('nombre' => 'id_transferencia_fondos',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $id_transferencia_fondos),
                array('nombre' => 'fecha',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $fecha),
                array('nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''),
            );
             $resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','',$con_trnasaccion);
          
            return $resultado[2]['valor'];
       
    }
	
	static public function modificar_fecha_anular_trasnferencia_fondo($id_transferencia_fondos, $fecha) {

        try {
            toba::db()->abrir_transaccion();
            $sql = "BEGIN :resultado := pkg_kr_transferencias.modificar_fecha_anulacion(:id_transferencia_fondos, to_date(substr(:fecha,1,10),'yyyy-mm-dd')); END;";
            $parametros = array(array('nombre' => 'id_transferencia_fondos',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $id_transferencia_fondos),
                array('nombre' => 'fecha',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $fecha),
                array('nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''),
            );
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

            toba::db()->cerrar_transaccion();

            //return $resultado[1]['valor'];

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
    
    static public function get_tipo_comprobante_pago ($id_transferencia_fondo){
    	$sql =" SELECT adcp.TIPO_COMPROBANTE_PAGO
				FROM AD_TRANSFERENCIAS_FONDOS adtf, AD_COMPROBANTES_PAGO ADCP
				WHERE ADTF.ID_COMPROBANTE_PAGO = ADCP.ID_COMPROBANTE_PAGO 
				      AND ADTF.ID_TRANSFERENCIA_FONDOS = $id_transferencia_fondo";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['tipo_comprobante_pago'];
    }
    
	static public function fue_impreso ($id_comprobante_pago){
    	$sql =" select impreso
				from ad_cheques_propios 
				where id_comprobante_pago = $id_comprobante_pago";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['impreso'];
    }


    static public function get_nro_comprobante ($id_comprobante_pago){
      $sql = "select nro_comprobante
              from v_ad_comprobantes_pago
              where id_comprobante_pago = ".$id_comprobante_pago;
      $datos = toba::db()->consultar_fila($sql);
      return $datos['nro_comprobante'];
    }
}

?>
