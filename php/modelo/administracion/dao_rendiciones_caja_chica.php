<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of rendiciones_caja_chica
 *
 * @author ddiluca
 */
class dao_rendiciones_caja_chica {

    public static function get_rendiciones_caja_chica ($filtro=array(), $orden = array()){
    	
   			$desde= null;
			$hasta= null;
			if(isset($filtro['desde'])){
				$desde= $filtro['desde'];
				$hasta= $filtro['hasta'];
	
				unset($filtro['desde']);
				unset($filtro['hasta']);
			}
            //$sql1 = "";   ADITIONAL WHERE CONDITION
            //$res = toba::db()->consultar_fila($sql1); 
            //$where = "((rant.COD_UNIDAD_ADMINISTRACION IN ".$res['unidades_ad'].") AND (('".$res['usuario_ues']."' ='N') OR (rant.COD_UNIDAD_EJECUTORA IN ".$res['unidades_eje_us'].")))";
		$where = " 1 = 1 ";
		if (isset($filtro['ids_comprobantes'])) {
			$where .= " AND rcch.id_rendicion_caja_chica IN (" . $filtro['ids_comprobantes'] . ") ";
			unset($filtro['ids_comprobantes']);
		}
            $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'rcch', '1=1');
	    $sql = "SELECT rcch.*,
                           trim(to_char(rcch.importe_rendido, '$999,999,999,990.00')) importe_rendido_format, 
                           to_char(rcch.fecha_rendicion, 'dd/mm/yyyy') fecha_rendicion_format,
                           to_char(rcch.fecha_aprueba, 'dd/mm/yyyy') fecha_aprueba_format,
                           to_char(rcch.fecha_anulacion, 'dd/mm/yyyy') fecha_anula_format,
                           to_char(rcch.fecha_carga, 'dd/mm/yyyy') fecha_carga_format,
                           to_char(rcch.fecha_imputacion, 'dd/mm/yyyy') fecha_imputacion_format,
                           to_char(rcch.fecha_pendiente, 'dd/mm/yyyy') fecha_pendiente_format,
                           krua.cod_unidad_administracion ||'-'|| krua.DESCRIPCION unidad_administracion,
                           trcch.DESCRIPCION tipo_rendicion,
                           adrc.NRO_RECIBO nro_recibo,
                           trcch.dev_gastos ui_dev_gastos,
                           trcch.factura ui_factura,
                           krex.nro_expediente nro_expediente,
                           decode(rcch.aprobada,'S','Si','No') aprobada_format,
        				   decode(rcch.anulada,'S','Si','No') anulada_format         
                     FROM  AD_RENDICIONES_CAJA_CHICA rcch 
                           LEFT JOIN KR_UNIDADES_ADMINISTRACION krua ON rcch.cod_unidad_administracion = krua.cod_unidad_administracion
                           LEFT JOIN AD_TIPOS_RENDICION_CACH trcch ON rcch.COD_TIPO_RENDICION = trcch.COD_TIPO_RENDICION
                           LEFT JOIN KR_EXPEDIENTES krex ON rcch.ID_EXPEDIENTE = krex.ID_EXPEDIENTE
                           LEFT JOIN KR_TRANSACCION KRTRA ON rcch.id_transaccion = krtra.id_transaccion
                           LEFT JOIN AD_RECIBOS_COBRO adrc ON rcch.ID_RECIBO_COBRO = adrc.ID_RECIBO_COBRO
                    WHERE ".$where."
                    ORDER BY rcch.id_rendicion_caja_chica DESC";
	     	$sql= dao_varios::paginador($sql, null, $desde, $hasta,null, $orden);
            $datos = toba::db()->consultar($sql);
            return $datos;
    }
    
    static public function get_rendiciones_x_id_caja_chica ($id_caja_chica){
    	$sql = "SELECT rcch.*
				  FROM AD_RENDICIONES_CAJA_CHICA RCCH
				 WHERE ID_CAJA_CHICA = $id_caja_chica";
    	return toba::db()->consultar($sql);
    }
    
   static public function get_rendicion_caja_chica_x_id ($id_rendicion){
        if ($id_rendicion != null){
            $sql = "SELECT rcch.*,
                           trim(to_char(rcch.importe_rendido, '$999,999,999,990.00')) importe_rendido_format, 
                           to_char(rcch.fecha_rendicion, 'dd/mm/yyyy') fecha_rendicion_format,
                           to_char(rcch.fecha_aprueba, 'dd/mm/yyyy') fecha_aprueba_format,
                           to_char(rcch.fecha_anulacion, 'dd/mm/yyyy') fecha_anula_format,
                           to_char(rcch.fecha_carga, 'dd/mm/yyyy') fecha_carga_format,
                           to_char(rcch.fecha_imputacion, 'dd/mm/yyyy') fecha_imputacion_format,
                           to_char(rcch.fecha_pendiente, 'dd/mm/yyyy') fecha_pendiente_format,
                           krua.DESCRIPCION unidad_administracion,
                           trcch.DESCRIPCION tipo_rendicion,
                           adrc.NRO_RECIBO nro_recibo,
                           krex.nro_expediente nro_expediente         
                     FROM  AD_RENDICIONES_CAJA_CHICA rcch 
                           LEFT JOIN KR_UNIDADES_ADMINISTRACION krua ON rcch.cod_unidad_administracion = krua.cod_unidad_administracion
                           LEFT JOIN AD_TIPOS_RENDICION_CACH trcch ON rcch.COD_TIPO_RENDICION = trcch.COD_TIPO_RENDICION
                           LEFT JOIN KR_EXPEDIENTES krex ON rcch.ID_EXPEDIENTE = krex.ID_EXPEDIENTE
                           LEFT JOIN KR_TRANSACCION KRTRA ON rcch.id_transaccion = krtra.id_transaccion
                           LEFT JOIN AD_RECIBOS_COBRO adrc ON rcch.ID_RECIBO_COBRO = adrc.ID_RECIBO_COBRO
                    WHERE  rcch.id_rendicion_caja_chica = ".$id_rendicion.";";
            $datos = toba::db()->consultar($sql);
            return $datos[0];
        }else return null;
    }
    
   static public function get_lov_tipo_rendicion_cch_x_codigo ($codigo){
        if (isset($codigo)){
            $sql = "SELECT ADTIRECC.*, ADTIRECC.COD_TIPO_RENDICION || '-' || ADTIRECC.DESCRIPCION as lov_descripcion
                    FROM AD_TIPOS_RENDICION_CACH ADTIRECC
                    WHERE ADTIRECC.cod_tipo_rendicion = '".strtoupper($codigo)."';";
            $datos = toba::db()->consultar($sql);
            return $datos[0]['lov_descripcion'];
        }else return null;
    }
    
   static public function get_lov_tipos_rendicion_cch_x_nombre ($nombre, $filtro = array ()){
        if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('ADTIRECC.COD_TIPO_RENDICION', $nombre);
            $trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('ADTIRECC.descripcion', $nombre);
            $where = "($trans_cod OR $trans_descricpcion)";
        } else {
            $where = "1=1";
        }
        $sql = "SELECT ADTIRECC.*, ADTIRECC.COD_TIPO_RENDICION || '-' || ADTIRECC.DESCRIPCION as lov_descripcion
                FROM AD_TIPOS_RENDICION_CACH ADTIRECC
                WHERE ".$where."
                ORDER BY lov_descripcion;";
        $datos = toba::db()->consultar($sql);
        if (isset($datos) && !empty($datos))
            return $datos;
        else
            return null;
    }
    
   static public function get_lov_facturas_x_nombre ($nombre, $filtro = array ()){
        if (isset($nombre)) {
            $trans_id = ctr_construir_sentencias::construir_translate_ilike('ADFA.ID_FACTURA', $nombre);
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('ADFA.NRO_FACTURA', $nombre);
            //$trans_fecha = ctr_construir_sentencias::construir_translate_ilike('ADFA.fecha_emision', $nombre);
            $where = "($trans_id OR $trans_nro)";
        } else {
            $where = "1=1";
        }
        $where .= " AND ADFA.ID_PROVEEDOR = L_ADPR.ID_PROVEEDOR AND
                    ADFA.ID_EXPEDIENTE_PAGO = L_KREX_PAGO.ID_EXPEDIENTE (+) AND
                    ADFA.ID_EXPEDIENTE = L_KREX.ID_EXPEDIENTE (+) AND
                   (ADFA.CONFIRMADA = 'S' AND ADFA.ANULADA = 'N' AND ADFA.COD_UNIDAD_ADMINISTRACION = ".$filtro['cod_unidad_administracion']."
                       AND ADFA.ID_CAJA_CHICA = ".$filtro['id_caja_chica']." 
                       AND not exists(select 1 
                                      from ad_fact_rend_caja_chica frcc, ad_rendiciones_caja_chica ren, AD_TIPOS_RENDICION_CACH trcc 
                                      where frcc.id_rendicion_caja_chica = ren.id_rendicion_caja_chica 
                                            and ren.anulada = 'N' and ren.COD_TIPO_RENDICION = trcc.COD_TIPO_RENDICION 
                                            and trcc.FACTURA = 'S' and frcc.id_factura = ADFA.id_factura)
                       AND not exists(select 1 
                                      from ad_comprobantes_gasto cg 
                                      where cg.id_factura = ADFA.id_factura and cg.APROBADO = 'S' and cg.ANULADO = 'N') 
                       AND pkg_ad_facturas.caja_chica(ADFA.COD_TIPO_FACTURA) = 'S')";
        unset($filtro['cod_unidad_administracion']);
        unset($filtro['id_caja_chica']);
        $sql = "SELECT ADFA.*, ADFA.ID_FACTURA ||'-'|| ADFA.NRO_FACTURA ||'-'||ADFA.cuit ||'-'||ADFA.RAZON_SOCIAL ||'-'|| ADFA.FECHA_EMISION as lov_descripcion
                FROM AD_FACTURAS ADFA,AD_PROVEEDORES L_ADPR, KR_EXPEDIENTES L_KREX_PAGO, KR_EXPEDIENTES L_KREX
                WHERE $where
                ORDER BY lov_descripcion;";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }
    
   static public function anular_rendicion_caja_chica ($id_rendicion_caja_chia, $fecha, $con_transaccion = true){
        try{
            $sql = "BEGIN :resultado := pkg_ad_cajas_chica.anular_rendicion_cach(:id_rendicion_caja_chia, to_date(substr(:fecha,1,10),'yyyy-mm-dd'));END;";		
            $parametros = array ( array(  'nombre' => 'id_rendicion_caja_chia', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 32,
                                          'valor' => $id_rendicion_caja_chia),
                                  array(  'nombre' => 'fecha', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 32,
                                          'valor' => $fecha),
                                  array(  'nombre' => 'resultado', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => ''),
                            );
           if ($con_transaccion)
              toba::db()->abrir_transaccion();
           $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
           if ($resultado[2]['valor'] == 'OK'){
               if ($con_transaccion)
                   toba::db()->cerrar_transaccion();
               toba::notificacion()->info("La rendicion fue anulada.");
           }elseif($con_transaccion){
               toba::db()->abortar_transaccion();
           }
           return $resultado[2]['valor'];
           
          } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            if ($con_transaccion)
              toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
            if ($con_transaccion)
              toba::db()->abortar_transaccion();
        }   
   }
  static public function aprobar_rendicion_caja_chica ($id_rendicion_caja_chia, $fecha){
        try{
            $sql = "BEGIN :resultado := pkg_ad_cajas_chica.confirmar_rendicion_cach(:id_rendicion_caja_chia, to_date(substr(:fecha,1,10),'yyyy-mm-dd'));END;";		
            $parametros = array ( array(  'nombre' => 'id_rendicion_caja_chia', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 32,
                                          'valor' => $id_rendicion_caja_chia),
                                  array(  'nombre' => 'fecha', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 32,
                                          'valor' => $fecha),
                                  array(  'nombre' => 'resultado', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => ''),
                            );
           toba::db()->abrir_transaccion();
           $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
           if ($resultado[2]['valor'] == 'OK'){
               toba::db()->cerrar_transaccion();
               toba::notificacion()->info("La rendicion fue anulada.");
           }else{
               toba::db()->abortar_transaccion();
           }
           return $resultado[2]['valor'];
           
          } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
            toba::db()->abortar_transaccion();
        }   
   }
   
  static public function generar_orden_pago ($id_rendicion_caja_chica, $fecha){
           try{
                if (isset($id_rendicion_caja_chica)) {
                             $sql = "BEGIN :resultado := PKG_KR_TRANS_AUTO.trans_auto_generar_ord_pag_rcc(:id_rendicion_caja_chica,:fecha,:genero_orden);END;";
                             $parametros = array(array(  'nombre' => 'id_rendicion_caja_chica', 
                                                         'tipo_dato' => PDO::PARAM_INT,
                                                         'longitud' => 32,
                                                         'valor' => $id_rendicion_caja_chica),
                                                 array(  'nombre' => 'fecha', 
                                                         'tipo_dato' => PDO::PARAM_INT,
                                                         'longitud' => 32,
                                                         'valor' => $fecha),
                                                 array(  'nombre' => 'genero_orden', 
                                                         'tipo_dato' => PDO::PARAM_STR,
                                                         'longitud' => 32,
                                                         'valor' =>''),
                                                 array(	'nombre' => 'resultado', 
                                                         'tipo_dato' => PDO::PARAM_STR,
                                                         'longitud' => 4000,
                                                         'valor' =>''),
                                     );
                            toba::db()->abrir_transaccion();
                            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);    
                            if ($resultado[3]['valor'] == 'OK'){
                                toba::db()->cerrar_transaccion();
                                toba::notificacion()->info("Se genero la orden de pago ".$resultado['2']['valor']);
                            }else{
                                toba::db()->abortar_transaccion();
                                toba::notificacion()->error($resultado['3']['valor']);
                            }
                            return $resultado;
                             //ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, 'Se genero la orden de pago.', 'Error al generar orden de pago.');
                     }
                 } catch (toba_error_db $e_db) {
                 toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
                 toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
                 toba::db()->abortar_transaccion();
                 } catch (toba_error $e) {
                 toba::notificacion()->error('Error '.$e->get_mensaje());
                 toba::logger()->error('Error '.$e->get_mensaje());
                 toba::db()->abortar_transaccion();
            }     
       }
   static public function set_pendiente_rendicion_cch ($id_rendicion_caja_chica){
           try{
                if (isset($id_rendicion_caja_chica)) {
                             $sql = "BEGIN :resultado := pkg_ad_cajas_chica.establecer_pend_rendicion_cach(:id_rendicion_caja_chica);END;";
                             $parametros = array(array(  'nombre' => 'id_rendicion_caja_chica', 
                                                         'tipo_dato' => PDO::PARAM_INT,
                                                         'longitud' => 32,
                                                         'valor' => $id_rendicion_caja_chica),
                                                 array(	'nombre' => 'resultado', 
                                                         'tipo_dato' => PDO::PARAM_STR,
                                                         'longitud' => 4000,
                                                         'valor' =>''),
                                     );
                            toba::db()->abrir_transaccion();
                            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);    
                            if ($resultado[1]['valor'] == 'OK'){
                                toba::db()->cerrar_transaccion();
                            }else{
                                toba::db()->abortar_transaccion();
                                toba::notificacion()->error($resultado[1]['valor']);
                            }
                            return $resultado;
                     }
                 } catch (toba_error_db $e_db) {
                 toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
                 toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
                 toba::db()->abortar_transaccion();
                 } catch (toba_error $e) {
                 toba::notificacion()->error('Error '.$e->get_mensaje());
                 toba::logger()->error('Error '.$e->get_mensaje());
                 toba::db()->abortar_transaccion();
            }     
       }
   static public function unset_pendiente_rendicion_cch ($id_rendicion_caja_chica){
              try{
                   if (isset($id_rendicion_caja_chica)) {
                                $sql = "BEGIN :resultado := pkg_ad_cajas_chica.suprimir_pend_rendicion_cach(:id_rendicion_caja_chica);END;";
                                $parametros = array(array(  'nombre' => 'id_rendicion_caja_chica', 
                                                            'tipo_dato' => PDO::PARAM_INT,
                                                            'longitud' => 32,
                                                            'valor' => $id_rendicion_caja_chica),
                                                    array(	'nombre' => 'resultado', 
                                                            'tipo_dato' => PDO::PARAM_STR,
                                                            'longitud' => 4000,
                                                            'valor' =>''),
                                        );
                               toba::db()->abrir_transaccion();
                               $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);    
                               if ($resultado[1]['valor'] == 'OK'){
                                   toba::db()->cerrar_transaccion();
                               }else{
                                   toba::db()->abortar_transaccion();
                                   toba::notificacion()->error($resultado[1]['valor']);
                               }
                               return $resultado;
                        }
                    } catch (toba_error_db $e_db) {
                    toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
                    toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
                    toba::db()->abortar_transaccion();
                    } catch (toba_error $e) {
                    toba::notificacion()->error('Error '.$e->get_mensaje());
                    toba::logger()->error('Error '.$e->get_mensaje());
                    toba::db()->abortar_transaccion();
               }     
     }
   static public function get_fecha_rendicion ($id_rendicion_caja_chica){
        if ($id_rendicion_caja_chica != null){
            $sql = "select fecha_rendicion
                    from ad_rendiciones_caja_chica
                    where id_rendicion_caja_chica = ".quote($id_rendicion_caja_chica).";";
            $datos = toba::db()->consultar_fila($sql);
            return $datos['fecha_rendicion'];
        }else return null;
    }
    ///////////////////////////////////////////////////
    ////////// UI ITEMS  //////////////////////////////
    ///////////////////////////////////////////////////
    
   static public function get_ui_importe_rendido ($id_rendicion_caja_chica){
        if ($id_rendicion_caja_chica != null){
            $sql = "select importe_rendido as ui_importe_rendido
                    from ad_rendiciones_caja_chica
                    where id_rendicion_caja_chica = ".quote($id_rendicion_caja_chica).";";
            $datos = toba::db()->consultar_fila($sql);
            return $datos['ui_importe_rendido'];
        }else return null;
    }
   static public function get_ui_importe_facturas ($id_rendicion_caja_chica){
        if ($id_rendicion_caja_chica != null){
            $sql = "select nvl(sum(fa.importe),0) as ui_importe_facturas
                   from ad_fact_rend_caja_chica fare, ad_facturas fa
                   where id_rendicion_caja_chica = ".quote($id_rendicion_caja_chica)."
                   and fa.id_factura = fare.id_factura;";
            $datos = toba::db()->consultar_fila($sql);
            return $datos['ui_importe_facturas'];
        }else return null;
    }
   static public function get_ui_importe_devengados ($id_rendicion_caja_chica){
        if ($id_rendicion_caja_chica != null){
            $sql = "select nvl(sum(de.importe),0) as ui_importe_devengados
                    from ad_rendiciones_cach_det dere, ad_comprobantes_gasto de
                    where id_rendicion_caja_chica = ".quote($id_rendicion_caja_chica)."
                    and de.id_comprobante_gasto = dere.id_comprobante_gasto
                    and de.id_factura is null";
            $datos = toba::db()->consultar_fila($sql);
            return $datos['ui_importe_devengados'];
        }else return null;
    }
   static public function get_ui_tipo_rend_cierre ($cod_tipo_rendicion){
        if ($cod_tipo_rendicion != null){
             $sql = "select cierre as ui_tipo_rend_cierre
                     from AD_TIPOS_RENDICION_CACH
                     where cod_tipo_rendicion = '".strtoupper($cod_tipo_rendicion)."'";
            $datos = toba::db()->consultar_fila($sql);
            return $datos['ui_tipo_rend_cierre'];
        }else return null;
    }
    
    static public function get_uis_tipos_rendicion_cch($cod_tipo_rendicion){
        if ($cod_tipo_rendicion != null){
             $sql = "select dev_gastos as ui_dev_gastos, factura as ui_factura
                     from AD_TIPOS_RENDICION_CACH
                     where cod_tipo_rendicion = '".strtoupper($cod_tipo_rendicion)."'";
            $datos = toba::db()->consultar_fila($sql);
            return $datos;
        }else return null;
    }
    static public function get_ui_importe_cobro ($id_recibo_cobro){
        if (isset($id_recibo_cobro)) {
            $sql = "SELECT  IMPORTE AS ui_importe_cobro
                    FROM    AD_RECIBOS_COBRO 
                    WHERE   id_recibo_cobro = " . quote($id_recibo_cobro) .";";
            $datos = toba::db()->consultar_fila($sql);
            return $datos['ui_importe_cobro'];
        }else return null;
    }
    static public function get_ui_importe_factura ($id_factura){
        if (isset($id_factura)) {
            $sql = "SELECT  IMPORTE AS ui_importe
                    FROM    AD_FACTURAS
                    WHERE   ID_FACTURA = " . quote($id_factura) .";";
            $datos = toba::db()->consultar_fila($sql);
            return $datos['ui_importe'];
        }else return null;
     }
    static public function get_ui_importe_rendicion ($id_rendicion_caja_chica){
       if (isset($id_rendicion_caja_chica)) {
           $sql = "SELECT  IMPORTE_RENDIDO AS ui_importe_rendido
                   FROM    AD_RENDICIONES_CAJA_CHICA 
                   WHERE   ID_RENDICION_CAJA_CHICA = " . quote($id_rendicion_caja_chica) .";";
           $datos = toba::db()->consultar_fila($sql);
           return $datos;//$datos['ui_importe_rendido'];
       }else return null;
    }


    static public function get_tipos_rendicion_cch ($filtro = []){
      $where =" 1=1 ";
      $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ADTRC', '1=1');
      $sql = "SELECT adtrc.*, DECODE (adtrc.cierre, 'S', 'Si', 'No') cierre_format,
                     DECODE (adtrc.dev_gastos, 'S', 'Si', 'No') dev_gastos_format,
                     DECODE (adtrc.factura, 'S', 'Si', 'No') factura_format
                FROM ad_tipos_rendicion_cach adtrc LEFT JOIN kr_tipos_transaccion krtt
                     ON adtrc.cod_tipo_transaccion = krtt.cod_tipo_transaccion
                     WHERE $where
                     ORDER BY adtrc.cod_tipo_rendicion";
       return toba::db()->consultar($sql);
    }

}




?>
