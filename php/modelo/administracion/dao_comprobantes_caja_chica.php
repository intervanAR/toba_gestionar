<?php

/**
 * Description of dao_comprobantes_caja_chica
 *
 * @author lwolcan
 */
class dao_comprobantes_caja_chica {

    static public function get_comprobante_cajas_chicas($filtro=array(), $orden = array())
    {
    	$desde= null;
		$hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}

		$where = self::armar_where($filtro);

	    $sql = "SELECT adcocach.*,
                           to_char(adcocach.fecha_comprobante, 'DD/MM/YYYY') as fecha_comprobante_format,
                           to_char(adcocach.fecha_anulacion, 'DD/MM/YYYY') as fecha_anulacion_format,
                           to_char(adcocach.fecha_aprueba, 'DD/MM/YYYY') as fecha_aprueba_format,
                           to_char(adcocach.fecha_carga, 'DD/MM/YYYY') as fecha_carga_format,
                           adtcach.descripcion tipo_comprobante,
                           krua.DESCRIPCION unidad_administracion,
                           adcachi.descripcion caja_chica,
                           trim(to_char(adcocach.importe, '$999,999,999,990.00')) importe_format,
                           krex.NRO_EXPEDIENTE,
                           decode(adcocach.aprobado,'S','Si','No') aprobado_format,
        				   decode(adcocach.anulado,'S','Si','No') anulado_format
                    FROM AD_COMPROBANTES_CAJA_CHICA adcocach
                         LEFT JOIN AD_TIPOS_COMPROB_CACH adtcach ON adcocach.cod_tipo_comprobante = adtcach.cod_tipo_comprobante
                         LEFT JOIN KR_UNIDADES_ADMINISTRACION krua ON adcocach.COD_UNIDAD_ADMINISTRACION = krua.cod_unidad_administracion
                         LEFT JOIN AD_CAJAS_CHICAS adcachi ON adcocach.id_caja_chica = adcachi.id_caja_chica
                         LEFT JOIN KR_EXPEDIENTES krex ON adcocach.id_expediente = krex.id_expediente
                    WHERE $where
                    ORDER BY id_comprobante_caja_chica DESC";

	    $sql= dao_varios::paginador($sql, null, $desde, $hasta, null, $orden);
	    $datos = toba::db()->consultar($sql);
	    return $datos;
	}

	static public function armar_where ($filtro = array())
	{
        $sql1 = "SELECT PKG_KR_USUARIOS.in_ua_tiene_acceso(upper('".toba::usuario()->get_id()."')) unidades_ad
                     FROM DUAL";
        $res = toba::db()->consultar_fila($sql1);
		$where = " 1 = 1 ";
		if (isset($filtro['ids_comprobantes'])) {
			$where .= " AND adcocach.id_comprobante_caja_chica IN (" . $filtro['ids_comprobantes'] . ") ";
			unset($filtro['ids_comprobantes']);
		}
        $where .= " AND " .ctr_construir_sentencias::get_where_filtro($filtro, 'adcocach', '1=1');
        $where .= " AND (PKG_KR_USUARIOS.tiene_acceso_cach_usuario(" . quote(toba::usuario()->get_id()) . ", adcocach.id_caja_chica) = 'S') AND (adcocach.COD_UNIDAD_ADMINISTRACION IN ".$res['unidades_ad'].")";
		return $where;
	}

	static public function get_cantidad ($filtro = array())
	{
		$where = self::armar_where($filtro);
		$sql = " select count(*) cantidad
				  FROM AD_COMPROBANTES_CAJA_CHICA adcocach
                         LEFT JOIN AD_TIPOS_COMPROB_CACH adtcach ON adcocach.cod_tipo_comprobante = adtcach.cod_tipo_comprobante
                         LEFT JOIN KR_UNIDADES_ADMINISTRACION krua ON adcocach.COD_UNIDAD_ADMINISTRACION = krua.cod_unidad_administracion
                         LEFT JOIN AD_CAJAS_CHICAS adcachi ON adcocach.id_caja_chica = adcachi.id_caja_chica
                         LEFT JOIN KR_EXPEDIENTES krex ON adcocach.id_expediente = krex.id_expediente
                    WHERE $where";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cantidad'];
	}
	static public function get_auditoria ($id_comprobante_caja_chica){
    $sql = "SELECT TO_CHAR (cch.fecha_carga, 'DD/MM/YYYY HH24:MI:SS') fecha_carga,
                   TO_CHAR (cch.fecha_aprueba, 'DD/MM/YYYY HH24:MI:SS') fecha_aprueba,
                   TO_CHAR (cch.fecha_anulacion, 'DD/MM/YYYY HH24:MI:SS') fecha_anulacion,
                   TO_CHAR (cch.fecha_anula, 'DD/MM/YYYY HH24:MI:SS') fecha_anula,
                   cch.usuario_anula, cch.usuario_aprueba, cch.usuario_carga
              FROM ad_comprobantes_caja_chica cch
             WHERE CCH.ID_COMPROBANTE_CAJA_CHICA = $id_comprobante_caja_chica ";

    return toba::db()->consultar_fila($sql);
  }
	static public function get_comprobantes_cajas_chica_id_caja ($id_caja_chica){
		$sql = "SELECT cc.*, tcc.descripcion ui_tipo_comprobante
				  FROM ad_comprobantes_caja_chica cc, ad_tipos_comprob_cach tcc
				 WHERE cc.cod_tipo_comprobante = tcc.cod_tipo_comprobante
				       AND id_caja_chica = $id_caja_chica";
		return toba::db()->consultar($sql);
	}

        static public function get_comprobante_cajas_chicas_x_id ($id_comp_caja_chica){
            if (isset($id_comp_caja_chica)) {
            $sql = "SELECT     adcocach.*,
                               adcocach.ID_COMPROBANTE_CAJA_CHICA ||' - '|| adcocach.NRO_COMPROBANTE as lov_descripcion
                        FROM   AD_COMPROBANTES_CAJA_CHICA adcocach
                       WHERE   adcocach.ID_COMPROBANTE_CAJA_CHICA = ".quote($id_comp_caja_chica) ."
                    ORDER BY   lov_descripcion ASC;";
            $datos = toba::db()->consultar_fila($sql);
            if (isset($datos) && !empty($datos) && isset($datos['lov_descripcion'])) {
                return $datos['lov_descripcion'];
            } else {
                return '';
                }
             }
         }

    static public function aprobar_comprobante_caja_chica($id_comprobante_caja_chica, $con_transaccion=true) {
        if (isset($id_comprobante_caja_chica)) {
            try {
                $sql = "BEGIN :resultado := pkg_ad_cajas_chica.confirmar_comp_cach(:id_comprobante_caja_chica);END;";
                $parametros = array(array('nombre' => 'id_comprobante_caja_chica',
                        'tipo_dato' => PDO::PARAM_INT,
                        'longitud' => 32,
                        'valor' => $id_comprobante_caja_chica),
                    array('nombre' => 'resultado',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 4000,
                        'valor' => ''),
                );
                if ($con_transaccion)
                	toba::db()->abrir_transaccion();

                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                if ($resultado[1]['valor'] <> 'OK'){
					throw new toba_error('Error aprobando comprobante de caja chica. '. $resultado[1]['valor']);
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
            return 'Id comprobante no definido';
        }
    }

    static public function anular_comprobante_caja_chica($id_comprobante_caja_chica, $fecha, $con_transaccion = true) {
        $sql = "BEGIN :resultado := pkg_ad_cajas_chica.anular_comp_cach(:id_comprobante_caja_chica, trunc(to_date(substr(:fecha,1,10),'yyyy-mm-dd')));END;";
        $parametros = array(array('nombre' => 'id_comprobante_caja_chica',
                                'tipo_dato' => PDO::PARAM_STR,
                                'longitud' => 32,
                                'valor' => $id_comprobante_caja_chica),
                            array('nombre' => 'fecha',
                                'tipo_dato' => PDO::PARAM_STR,
                                'longitud' => 32,
                                'valor' => $fecha),
                            array('nombre' => 'resultado',
                                'tipo_dato' => PDO::PARAM_STR,
                                'longitud' => 4000,
                                'valor' => ''),
                             );
	    if ($con_transaccion) {
	        toba::db()->abrir_transaccion();
	    }
        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
        if ($con_transaccion) {
	        if ($resultado[2]['valor'] == 'OK'){
	            toba::db()->cerrar_transaccion();
	        }else{
	            toba::db()->abortar_transaccion();
	        }
        }
        return $resultado[2]['valor'];
    }

        static public function get_lov_comprobante_caja_chica_x_nombre ($nombre, $filtro=array()){
            if (isset($nombre)) {
                $trans_id = ctr_construir_sentencias::construir_translate_ilike('adcocach.ID_COMPROBANTE_CAJA_CHICA', $nombre);
                $trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('adcocach.NRO_COMPROBANTE', $nombre);
                $where = "($trans_id OR $trans_descricpcion)";
            } else {
                $where = "1=1";
            }

			if (isset($filtro['para_ordenes_pago'])) {
                            $where .= " AND (    adcocach.aprobado = 'S'
                                        AND adcocach.anulado = 'N'
                                        AND pkg_ad_cajas_chica.retornar_cach_x_cuenta_cte
                                                                    (".$filtro['cod_uni_admin'].",
                                                                     ".$filtro['id_cta_cte']."
                                                                    ) = adcocach.id_caja_chica
                                        AND NOT EXISTS (
                                               SELECT 1
                                                 FROM ad_ordenes_pago
                                                WHERE id_comprobante_caja_chica =
                                                                            adcocach.id_comprobante_caja_chica
                                                  AND aprobada = 'S'
                                                  AND anulada = 'N')
                                       )";
                            unset($filtro['cod_uni_admin']);
                            unset($filtro['id_cta_cte']);
                            unset($filtro['para_ordenes_pago']);
                        }

			$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'adcocach', '1=1');

			$sql = "SELECT	adcocach.*,
					adcocach.ID_COMPROBANTE_CAJA_CHICA || '-' || adcocach.NRO_COMPROBANTE as lov_descripcion
                                  FROM	AD_COMPROBANTES_CAJA_CHICA adcocach
                                 WHERE  $where
                              ORDER BY lov_descripcion;";
            $datos = toba::db()->consultar($sql);
            return $datos;
       }

      static public function get_tipos_comprobante_caja_chica($filtro = [])
      {
      $where = "1=1";
      $where .=" and ".ctr_construir_sentencias::get_where_filtro($filtro, 'adtcc', $default = '1=1');
      $sql ="SELECT adtcc.*, DECODE (adtcc.apertura, 'S', 'Si', 'No') apertura_format,
                    DECODE (adtcc.automatico, 'S', 'Si', 'No') automatico_format
               FROM ad_tipos_comprob_cach adtcc
              WHERE $where
              order by adtcc.cod_tipo_comprobante ";
      return toba::db()->consultar($sql);
      }
      static public function get_nro_comprobante_caja_chica ($id_comprobante_caja_chica){
           if ($id_comprobante_caja_chica != null){
               $sql = "SELECT nro_comprobante
                       FROM AD_COMPROBANTES_CAJA_CHICA
                       WHERE id_comprobante_caja_chica = ".quote($id_comprobante_caja_chica).";";
               $datos = toba::db()->consultar_fila($sql);
               return $datos['nro_comprobante'];
           }
           else return null;
       }
      static public function get_tipo_comprobante_caja_chica_x_codigo ($codigo){
           if ($codigo != null){
               $sql = "SELECT ADTICOCC.*
                       FROM AD_TIPOS_COMPROB_CACH ADTICOCC
                       WHERE ADTICOCC.cod_tipo_comprobante = '".strtoupper($codigo)."';";
               $datos = toba::db()->consultar_fila($sql);
               return $datos;
           }else return null;
       }
      static public function get_lov_tipo_comprobante_caja_chica_x_codigo ($codigo){
           if ($codigo != null){
               $sql = "SELECT ADTICOCC.*, ADTICOCC.cod_tipo_comprobante ||' - '|| ADTICOCC.descripcion as lov_descripcion
                       FROM AD_TIPOS_COMPROB_CACH ADTICOCC
                       WHERE ADTICOCC.cod_tipo_comprobante = '".strtoupper($codigo)."'
                       ORDER BY lov_descripcion ";
               $datos = toba::db()->consultar_fila($sql);
               return $datos['lov_descripcion'];
           }else return null;
       }

      static public function get_lov_tipo_comprobante_caja_chica_x_nombre ($nombre, $filtro){
           if (isset($nombre)) {
               $trans_cod = ctr_construir_sentencias::construir_translate_ilike('ADTICOCC.COD_TIPO_COMPROBANTE', $nombre);
               $trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('ADTICOCC.DESCRIPCION', $nombre);
               $where = "($trans_cod OR $trans_descricpcion)";
           } else {
               $where = "1=1";
           }
           if (isset($filtro['automatico'])){
               $where .= " AND ADTICOCC.automatico = 'N'";
               unset($filtro['automatico']);
           }
           $sql = "SELECT ADTICOCC.*, ADTICOCC.cod_tipo_comprobante ||' - '|| ADTICOCC.descripcion as lov_descripcion
                   FROM AD_TIPOS_COMPROB_CACH ADTICOCC
                   WHERE $where
                   ORDER BY lov_descripcion";
           $datos = toba::db()->consultar($sql);
           return $datos;
       }
    static public function tiene_orden_pago ($id_comprobante_caja_chica){
    	$sql = "select count(*) cant
    			from ad_ordenes_pago
    			where id_comprobante_caja_chica = ".quote($id_comprobante_caja_chica)." and anulada = 'N'";
    	$datos = toba::db()->consultar_fila($sql);
    	if (intval($datos['cant']) > 0){
    		return 'S';
    	}else{
    		return 'N';
    	}
    }

    static public function get_id_orden_pago ($id_comprobante_caja_chica){
    	if (isset($id_comprobante_caja_chica)){
	    	//Retorna el id de una orden de pago (no anulada) asociada al comprobante.
	    	$sql = "SELECT ID_ORDEN_PAGO
	    			  FROM AD_ORDENES_PAGO
	    			 WHERE ID_COMPROBANTE_CAJA_CHICA = ".quote($id_comprobante_caja_chica)."
	    				   AND ANULADA = 'N';";
	    	$datos = toba::db()->consultar_fila($sql);
	    	return $datos['id_orden_pago'];
    	}else return null;
    }
}

?>
