<?php

/**
 * Description of dao_comprobantes_anticipo
 *
 * @author lwolcan
 */
class dao_comprobantes_anticipo {

    public static function get_comprobantes_anticipo($filtro = array(),$orden = array()) {
    	
   		$desde= null;
		$hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}
		
        $where = self::armar_where($filtro);
        
        $sql = "SELECT  cant.*,
                        ua.descripcion unidad_administracion,
                        ue.descripcion unidad_ejecutora,
                        to_char(cant.fecha_anticipo, 'dd/mm/yyyy') fecha_anticipo_format,
                        to_char(cant.fecha_aprueba, 'dd/mm/yyyy') fecha_aprueba_format,
                        to_char(cant.fecha_anulacion, 'dd/mm/yyyy') fecha_anula_format,
                        to_char(cant.fecha_carga, 'dd/mm/yyyy') fecha_carga_format,
                        tca.descripcion tipo_comprobante,
                        ben.nombre beneficiario,
                        tcant.cod_auxiliar,
                        cant.nro_comprobante nro_comprobante_anticipo,
                        decode(cant.aprobado,'S','Si','No') aprobado_format,
        				decode(cant.anulado,'S','Si','No') anulado_format
                 FROM   AD_COMPROBANTES_ANTICIPO cant
                        LEFT JOIN KR_UNIDADES_ADMINISTRACION ua ON cant.cod_unidad_administracion = ua.cod_unidad_administracion
                        LEFT JOIN KR_UNIDADES_EJECUTORAS ue ON cant.cod_unidad_ejecutora = ue.cod_unidad_ejecutora
                        LEFT JOIN AD_TIPOS_COMPROB_ANTICIPO tca ON tca.cod_tipo_comprobante = cant.cod_tipo_comprobante
                        LEFT JOIN AD_BENEFICIARIOS ben ON ben.id_beneficiario = cant.id_beneficiario
                        LEFT JOIN AD_TIPOS_COMPROB_ANTICIPO tcant ON tcant.COD_TIPO_COMPROBANTE = cant.cod_tipo_comprobante
                WHERE " . $where . "
                ORDER BY cant.id_comprobante_anticipo DESC";
        
        $sql= dao_varios::paginador($sql, null, $desde, $hasta,null,$orden);
        $datos = toba::db()->consultar($sql);
        return $datos;
    }
    
	static public function armar_where ($filtro = array())
	{
		$where = " 1=1 ";
        if (isset($filtro['ids_comprobantes'])) {
			$where .= "AND cant.id_comprobante_anticipo IN (" . $filtro['ids_comprobantes'] . ") ";
			unset($filtro['ids_comprobantes']);
		}
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'cant', '1=1');
		return $where;
	}
	
	static public function get_cantidad ($filtro = array())
	{
		$where = self::armar_where($filtro);
		$sql = " select count(*) cantidad
				   FROM   AD_COMPROBANTES_ANTICIPO cant
                        LEFT JOIN KR_UNIDADES_ADMINISTRACION ua ON cant.cod_unidad_administracion = ua.cod_unidad_administracion
                        LEFT JOIN KR_UNIDADES_EJECUTORAS ue ON cant.cod_unidad_ejecutora = ue.cod_unidad_ejecutora
                        LEFT JOIN AD_TIPOS_COMPROB_ANTICIPO tca ON tca.cod_tipo_comprobante = cant.cod_tipo_comprobante
                        LEFT JOIN AD_BENEFICIARIOS ben ON ben.id_beneficiario = cant.id_beneficiario
                        LEFT JOIN AD_TIPOS_COMPROB_ANTICIPO tcant ON tcant.COD_TIPO_COMPROBANTE = cant.cod_tipo_comprobante
                 WHERE " . $where;
			
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cantidad'];
	}
    
    static public function tiene_orden_pago($id_comprobante_anticipo){
        //Devuelve TRUE si el comprobante tiene una orden de pago asociada. FALSE en caso contrario.
        $sql = "SELECT COUNT(op.id_orden_pago) orden
                FROM ad_comprobantes_anticipo ca, AD_ORDENES_PAGO op
                WHERE ca.id_comprobante_anticipo = op.id_comprobante_anticipo 
                      AND ca.id_comprobante_anticipo = ".quote($id_comprobante_anticipo).";";
        $resultado = toba::db()->consultar_fila($sql);
        if (intval($resultado['orden']) > 0)
            return true;
        else return false;
    }

    static public function get_orden_pago ($id_comprobante_anticipo){
        $sql = "SELECT   adop.id_orden_pago
                  FROM ad_ordenes_pago adop
                 WHERE adop.id_comprobante_anticipo = ".quote($id_comprobante_anticipo)."
                   AND adop.anulada = 'N'
              ORDER BY adop.id_orden_pago DESC";
        $datos = toba::db()->consultar($sql);
        if (!empty($datos))
            return $datos[0]['id_orden_pago'];
        else 
            return null;
    }
    
    static public function get_comprobante_anticipo_x_id($id_comprobante_anticipo) {
        if ($id_comprobante_anticipo != null) {
            $sql = "SELECT  cant.*,
                    ua.descripcion unidad_administracion,
                    ue.descripcion unidad_ejecutora,
                    to_char(cant.fecha_anticipo, 'dd/mm/yyyy') fecha_anticipo_format,
                    to_char(cant.fecha_aprueba, 'dd/mm/yyyy') fecha_aprueba_format,
                    to_char(cant.fecha_anulacion, 'dd/mm/yyyy') fecha_anula_format,
                    to_char(cant.fecha_carga, 'dd/mm/yyyy') fecha_carga_format,
                    tca.descripcion tipo_comprobante,
                    ben.nombre beneficiario,
                    cant.nro_comprobante nro_comprobante_anticipo
             FROM   AD_COMPROBANTES_ANTICIPO cant
                    LEFT JOIN KR_UNIDADES_ADMINISTRACION ua ON cant.cod_unidad_administracion = ua.cod_unidad_administracion
                    LEFT JOIN KR_UNIDADES_EJECUTORAS ue ON cant.cod_unidad_ejecutora = ue.cod_unidad_ejecutora
                    LEFT JOIN AD_TIPOS_COMPROB_ANTICIPO tca ON tca.cod_tipo_comprobante = cant.cod_tipo_comprobante
                    LEFT JOIN AD_BENEFICIARIOS ben ON ben.id_beneficiario = cant.id_beneficiario
             WHERE  cant.id_comprobante_anticipo = " . $id_comprobante_anticipo . "
             ORDER BY cant.id_comprobante_anticipo DESC;";
            $datos = toba::db()->consultar_fila($sql);
            return $datos;
        }else
            return null;
    }

    static public function generar_orden_pago_anticipo($id_comprobante_anticipo, $fecha_anticipo) {
        try {
            if (isset($id_comprobante_anticipo) && isset($fecha_anticipo)) {
                $sql = "BEGIN :resultado := PKG_KR_TRANS_AUTO.trans_auto_generar_ord_pag_ant(:id_comprobante_anticipo,:fecha_anticipo,:genero_orden);END;";
                $parametros = array(array('nombre' => 'id_comprobante_anticipo',
                        'tipo_dato' => PDO::PARAM_INT,
                        'longitud' => 32,
                        'valor' => $id_comprobante_anticipo),
                    array('nombre' => 'fecha_anticipo',
                        'tipo_dato' => PDO::PARAM_INT,
                        'longitud' => 32,
                        'valor' => $fecha_anticipo),
                    array('nombre' => 'genero_orden',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 32,
                        'valor' => ''),
                    array('nombre' => 'resultado',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 4000,
                        'valor' => ''),
                );
            toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            if ($resultado[3]['valor'] == 'OK'){
                toba::db()->cerrar_transaccion();
                toba::notificacion()->info("Se genero la orden de pago.");
            }else{
                toba::db()->abortar_transaccion();
                toba::notificacion()->error("Error al generar orden de pago: ".$resultado[3]['valor']);
            }
            return $resultado[3]['valor'];
            }
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

    
    
    static public function aprobar_comprobante_anticipo($id_comprobante_anticipo, $con_transaccion = true) {
        if (isset($id_comprobante_anticipo)) {
            try {
                $sql = "BEGIN :resultado := pkg_ad_anticipos.confirmar_comp_anticipo(:id_comprobante_anticipo);END;";
                $parametros = array(array('nombre' => 'id_comprobante_anticipo',
                                        'tipo_dato' => PDO::PARAM_INT,
                                        'longitud' => 32,
                                        'valor' => $id_comprobante_anticipo),
                                    array('nombre' => 'resultado',
                                        'tipo_dato' => PDO::PARAM_STR,
                                        'longitud' => 4000,
                                        'valor' => ''));
                if ($con_transaccion)                 
                	toba::db()->abrir_transaccion();
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                if ($resultado[1]['valor'] <> 'OK'){
					throw new toba_error('Error aprobando comprobante de anticipo. '. $resultado[1]['valor']); 
				}else{
					if ($con_transaccion) {
						toba::db()->cerrar_transaccion();
					}
					return $resultado[1]['valor'];
				}
					
			} catch (toba_error_db $e_db) {
				if ($con_transaccion) {
					toba::db()->abortar_transaccion();
				}
				toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
				toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());            
			} catch (toba_error $e) {
				if ($con_transaccion) {
					toba::db()->abortar_transaccion();
				}
				toba::notificacion()->error('Error '.$e->get_mensaje());
				toba::logger()->error('Error '.$e->get_mensaje());            
			}
        }else{
            return 'Id anticipo no definido';
        }  
    }

    
    
    static public function get_lov_comprobante_anticipo($codigo) {
        if (isset($codigo)) {
            $sql = "SELECT ADCOAN.*, ADCOAN.id_comprobante_anticipo || ' - ' || ADCOAN.nro_comprobante as lov_descripcion
		FROM AD_COMPROBANTES_ANTICIPO ADCOAN
		WHERE ADCOAN.id_comprobante_anticipo = " . quote($codigo) . ";";
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

    static public function get_descripcion_tipo_comprobante($cod_comprobante) {
        $sql = "SELECT tca.*, tca.cod_tipo_comprobante || '-' || tca.descripcion as lov_descripcion
                FROM AD_TIPOS_COMPROB_ANTICIPO tca
                WHERE tca.cod_tipo_comprobante = " . $cod_comprobante . ";";
        $datos = toba::db()->consultar_fila($sql);
        if (isset($datos) && !empty($datos['lov_descripcion'])) {
            return $datos['lov_descripcion'];
        } else {
            return '';
        }
    }

    static public function get_lov_tipos_comprobante_anticipo($nombre, $filtro = array()) {
        if (isset($nombre)) {
            $cod_comp = ctr_construir_sentencias::construir_translate_ilike('TCA.COD_TIPO_COMPROBANTE', $nombre);
            $trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('TCA.DESCRIPCION', $nombre);
            $where = "($cod_comp OR $trans_descricpcion)";
        } else {
            $where = "1=1";
        }
        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'krunad', '1=1');
        $sql = "SELECT tca.*, tca.cod_tipo_comprobante || '-' || tca.descripcion as lov_descripcion
                FROM AD_TIPOS_COMPROB_ANTICIPO tca
                WHERE  $where
                ORDER BY lov_descripcion;";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }

    static public function get_lov_preventivo_x_nombre($nombre, $filtro = array()) {
        if (isset($nombre)) {
            $trans_id = ctr_construir_sentencias::construir_translate_ilike('id_preventivo', $nombre);
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_preventivo', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('observaciones', $nombre);
            $where = "( $trans_id OR $trans_nro OR $trans_descripcion )";
        } else {
            $where = '1=1';
        }
        $where .= " AND ADPV.APROBADO = 'S' AND ADPV.ANULADO = 'N' AND saldo_preventivo(ADPV.ID_PREVENTIVO) > 0 ";
        if (isset($filtro['cod_unidad_administracion']) && !empty($filtro['cod_unidad_administracion'])) {
            $where .= " AND adpv.cod_unidad_administracion = " . $filtro['cod_unidad_administracion'];
            unset($filtro['cod_unidad_administracion']);
        }
        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'ADPV', '1=1');
        $sql = "SELECT  ADPV.*,ADPV.ID_PREVENTIVO || ' - ' || 
                              ADPV.NRO_PREVENTIVO ||' - '|| 
                              substr(observaciones,1,2000)|| ' - ' || 
                              ADPV.ID_EXPEDIENTE lov_descripcion
                  FROM  AD_PREVENTIVOS ADPV
                  WHERE $where
                  ORDER BY lov_descripcion;";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }

    static public function generar_nro_comprobante($cod_unidad_administracion) {
        $sql = " SELECT NVL(MAX(NRO_COMPROBANTE),0) + 1 as nro_comprobante
                 FROM AD_COMPROBANTES_ANTICIPO
                 WHERE NVL(COD_UNIDAD_ADMINISTRACION, 1) = NVL(" . $cod_unidad_administracion . ", 1); ";
        $datos = toba::db()->consultar($sql);
        return $datos[0]['nro_comprobante'];
    }

    static public function anular_comprobante_anticipo($id_comprobante_anticipo, $fecha, $con_transaccion = true) {
  		if ($con_transaccion) {
            toba::db()->abrir_transaccion();
        }
        $sql = "BEGIN :resultado := pkg_ad_anticipos.anular_comp_anticipo(:id_comprobante_anticipo, trunc(to_date(substr(:fecha,1,10),'yyyy-mm-dd')));END;";
        $parametros = array(array('nombre' => 'id_comprobante_anticipo',
                'tipo_dato' => PDO::PARAM_STR,
                'longitud' => 32,
                'valor' => $id_comprobante_anticipo),
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
        if ($con_transaccion) {
	        if ($resultado[2]['valor'] == 'OK'){
	            toba::db()->cerrar_transaccion();
	            toba::notificacion()->info("Comprobante Anulado con exito.");
	        }else{
	            toba::db()->abortar_transaccion();
	            toba::notificacion()->error("Error al Anular el comprobante: ".$resultado[2]['valor']);
	        }
        }
        return $resultado[2]['valor'];
    }

    static public function get_lov_comprobante_anticipo_pago_x_nombre($nombre, $filtro = array()) {
        if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('id_comprobante_anticipo', $nombre);
            $trans_des = ctr_construir_sentencias::construir_translate_ilike('nro_comprobante', $nombre);
            $where = "( $trans_cod OR $trans_des )";
        } else {
            $where = '1=1';
        }


        if (isset($filtro['para_ordenes_pago'])) {
            if ($filtro['cod_uni_ejecutora']!=0) {
                $where .= " AND (    adcoan.aprobado = 'S'
                AND adcoan.anulado = 'N'
                AND EXISTS (
                       SELECT 1
                         FROM kr_cuentas_corriente cuco,
                              kr_auxiliares_ext auex,
                              ad_tipos_comprob_anticipo ticoan
                        WHERE cuco.cod_auxiliar = auex.cod_auxiliar
                          AND auex.cod_auxiliar = ticoan.cod_auxiliar
                          AND adcoan.cod_tipo_comprobante =
                                                           ticoan.cod_tipo_comprobante
                          AND cuco.id_cuenta_corriente = " . $filtro['id_cta_cte'] . ")
                AND NOT EXISTS (
                       SELECT 1
                         FROM ad_ordenes_pago
                        WHERE id_comprobante_anticipo = adcoan.id_comprobante_anticipo
                          AND aprobada = 'S'
                          AND anulada = 'N')
                AND (   adcoan.cod_unidad_ejecutora = " . $filtro['cod_uni_ejecutora'] . "
                    )
               )";
            } else {
                $where .= " AND (    adcoan.aprobado = 'S'
                AND adcoan.anulado = 'N'
                AND EXISTS (
                       SELECT 1
                         FROM kr_cuentas_corriente cuco,
                              kr_auxiliares_ext auex,
                              ad_tipos_comprob_anticipo ticoan
                        WHERE cuco.cod_auxiliar = auex.cod_auxiliar
                          AND auex.cod_auxiliar = ticoan.cod_auxiliar
                          AND adcoan.cod_tipo_comprobante =
                                                           ticoan.cod_tipo_comprobante
                          AND cuco.id_cuenta_corriente = " . $filtro['id_cta_cte'] . ")
                AND NOT EXISTS (
                       SELECT 1
                         FROM ad_ordenes_pago
                        WHERE id_comprobante_anticipo = adcoan.id_comprobante_anticipo
                          AND aprobada = 'S'
                          AND anulada = 'N')
                AND (   adcoan.cod_unidad_ejecutora is null  )
               )";
            }
            unset($filtro['para_ordenes_pago']);
            unset($filtro['id_cta_cte']);
            unset($filtro['cod_uni_ejecutora']);
        }
        if (isset($filtro['para_rendicion_anticipo_rein'])){
            $where .= " AND (adcoan.cod_unidad_administracion = ".$filtro['cod_unidad_administracion']." 
                                AND adcoan.aprobado = 'S'
                                AND adcoan.anulado = 'N'
                                AND NOT EXISTS (
                                       SELECT 1
                                         FROM ad_rendiciones_anticipo
                                        WHERE id_comprobante_anticipo = adcoan.id_comprobante_anticipo
                                          AND aprobada = 'S'
                                          AND anulada = 'N')
                                AND NOT EXISTS (
                                       SELECT 1
                                         FROM ad_rendiciones_anticipo
                                        WHERE id_comprobante_anticireintegro = adcoan.id_comprobante_anticipo
                                          AND aprobada = 'S'
                                          AND anulada = 'N')
                                AND pkg_ad_anticipos.obtener_cuenta_comprobante
                                                (".$filtro['id_comprobante_anticipo'].") = pkg_ad_anticipos.obtener_cuenta_comprobante
                                                (adcoan.id_comprobante_anticipo)
                                AND (adcoan.id_comprobante_anticipo <> ".$filtro['id_comprobante_anticipo'].")
                                AND EXISTS (
                                       SELECT 1
                                         FROM ad_ordenes_pago
                                        WHERE id_comprobante_anticipo = adcoan.id_comprobante_anticipo
                                          AND aprobada = 'S'
                                          AND anulada = 'N'
                                          AND saldo_orden_pago (id_orden_pago) = 0)
                                
                               )";
            if (isset($filtro['cod_unidad_ejecutora'])){
                $where .= " AND (".$filtro['cod_unidad_ejecutora']." IS NULL OR adcoan.cod_unidad_ejecutora = ".$filtro['cod_unidad_ejecutora'].")";
                unset($filtro['cod_unidad_ejecutora']);
            }
            unset($filtro['para_rendicion_anticipo_rein']);
            unset($filtro['cod_uni_ejecutora']);
            unset($filtro['id_comprobante_anticipo']);

        }
        if (isset($filtro['rendicion_anticipo'])){
            if (empty($filtro['cod_unidad_ejecutora'])){
                $where .= " AND ADCOAN.cod_unidad_administracion = ".$filtro['cod_unidad_administracion']."
                            AND ADCOAN.aprobado = 'S' 
                            AND ADCOAN.anulado = 'N'
                            AND not exists(select 1 from ad_rendiciones_anticipo where id_comprobante_anticipo = ADCOAN.id_comprobante_anticipo and aprobada='S' and anulada='N')
                            AND not exists(select 1 from ad_rendiciones_anticipo where id_comprobante_anticireintegro = ADCOAN.id_comprobante_anticipo and aprobada='S' and anulada='N' )
                            AND EXISTS (  SELECT 1  FROM ad_ordenes_pago WHERE id_comprobante_anticipo=ADCOAN.id_comprobante_anticipo AND aprobada='S' AND anulada='N' AND saldo_orden_pago(id_orden_pago)=0)";
            }else{
                $where .= " AND ADCOAN.cod_unidad_administracion = ".$filtro['cod_unidad_administracion']."
                            AND ADCOAN.aprobado = 'S' 
                            AND ADCOAN.anulado = 'N'
                            AND not exists(select 1 from ad_rendiciones_anticipo where id_comprobante_anticipo = ADCOAN.id_comprobante_anticipo and aprobada='S' and anulada='N')
                            AND not exists(select 1 from ad_rendiciones_anticipo where id_comprobante_anticireintegro = ADCOAN.id_comprobante_anticipo and aprobada='S' and anulada='N' )
                            AND EXISTS (  SELECT 1  FROM ad_ordenes_pago WHERE id_comprobante_anticipo=ADCOAN.id_comprobante_anticipo AND aprobada='S' AND anulada='N' AND saldo_orden_pago(id_orden_pago)=0)
                            AND adcoan.cod_unidad_ejecutora = ".$filtro['cod_unidad_ejecutora']."";
            }
            /*
             * Se borro de la lov original la sig condicion:
             * AND (ADCOAN.id_comprobante_anticipo <> ".$filtro['id_comprobante_anticireintegro']." or  ".$filtro['id_comprobante_anticireintegro']." IS NULL)
             */
            unset($filtro['rendicion_anticipo']);
            unset($filtro['cod_unidad_ejecutora']);
        }
        
        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'ADCOAN', '1=1');
        $sql = "SELECT ADCOAN.*, ADCOAN.id_comprobante_anticipo || ' - ' || ADCOAN.nro_comprobante as lov_descripcion
		FROM AD_COMPROBANTES_ANTICIPO ADCOAN
		WHERE $where
                ORDER BY lov_descripcion;";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }
    static public function get_preventivo_x_id_comprobante_anticipo ($id_comprobante_anticipo){
        if ($id_comprobante_anticipo != null){
            $sql = "SELECT prev.*
                    FROM AD_PREVENTIVOS prev LEFT JOIN AD_COMPROBANTES_ANTICIPO cant ON prev.id_preventivo = cant.id_preventivo
                    WHERE cant.id_comprobante_anticipo = ".$id_comprobante_anticipo.";";
            $datos = toba::db()->consultar($sql);
            if (isset($datos) && $datos != null)
                return $datos[0];
            else return null;
        }else return null;
    }
    
    static public function get_tipos_comprobantes_anticipo ($filtro = [])
    {
        $where = " 1=1 ";

        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ADTCAN', '1=1');

        $sql = "SELECT adtcan.*
                       ,kraux.COD_AUXILIAR ||' - '|| kraux.DESCRIPCION cod_auxiliar_format
                  FROM ad_tipos_comprob_anticipo adtcan, KR_AUXILIARES_EXT kraux
                 WHERE adtcan.COD_AUXILIAR = kraux.COD_AUXILIAR and " . $where . "
                ORDER BY ADTCAN.COD_TIPO_COMPROBANTE DESC";

        return toba::db()->consultar($sql);
        
    }
   
}

?>
