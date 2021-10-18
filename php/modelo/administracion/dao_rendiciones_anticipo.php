<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of dao_rendiciones_anticipo
 *
 * @author ddiluca
 */
class dao_rendiciones_anticipo {
	
    public static function get_rendiciones_anticipos ($filtro=array(), $orden = array()){
    	$desde= null;
		$hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}
        $where = self::armar_where($filtro);
	    $sql = "SELECT  rant.*,
                            rant.importe_rendido ui_importe_rendido,
                            ua.descripcion unidad_administracion,
                            ue.descripcion unidad_ejecutora,
                            to_char(rant.fecha_rendicion, 'dd/mm/yyyy') fecha_comprobante_format,
                            to_char(rant.fecha_aprueba, 'dd/mm/yyyy') fecha_aprueba_format,
                            to_char(rant.fecha_anulacion, 'dd/mm/yyyy') fecha_anula_format,
                            to_char(rant.fecha_carga, 'dd/mm/yyyy') fecha_carga_format,
                            to_char(rant.fecha_imputacion, 'dd/mm/yyyy') fecha_imputacion_format,
                            krex.nro_expediente nro_expediente,
                            cant.nro_comprobante nro_comprobante_anticipo,
                            cant.id_beneficiario id_beneficiario,
                            ben.nombre nombre_beneficiario,
                            cant.id_preventivo preventivo,
                            trant.descripcion tipo_ren_des,
                            trant.dev_gastos tipo_ren_dev_gasto,
                            trant.factura tipo_ren_fact,
                            ADTCA.COD_AUXILIAR,
                            decode(rant.aprobada,'S','Si','No') aprobada_format,
        				    decode(rant.anulada,'S','Si','No') anulada_format
                    FROM   ad_rendiciones_anticipo rant
                    LEFT JOIN KR_UNIDADES_ADMINISTRACION ua ON rant.cod_unidad_administracion = ua.cod_unidad_administracion
                    LEFT JOIN KR_UNIDADES_EJECUTORAS ue ON rant.cod_unidad_ejecutora = ue.cod_unidad_ejecutora
                    LEFT JOIN KR_EXPEDIENTES krex ON rant.ID_EXPEDIENTE = krex.id_expediente
                    LEFT JOIN AD_COMPROBANTES_ANTICIPO cant ON rant.ID_COMPROBANTE_ANTICIPO = cant.id_comprobante_anticipo
                    LEFT JOIN AD_TIPOS_COMPROB_ANTICIPO ADTCA ON ADTCA.COD_TIPO_COMPROBANTE = cant.cod_tipo_comprobante
                    LEFT JOIN AD_TIPOS_RENDICION_ANTICIPO trant ON rant.COD_TIPO_RENDICION = trant.cod_tipo_rendicion
                    LEFT JOIN AD_BENEFICIARIOS ben ON ben.id_beneficiario = cant.id_beneficiario
                    WHERE ".$where."
                    ORDER BY rant.id_rendicion_anticipo DESC ";
	    
	   	    $sql= dao_varios::paginador($sql, null, $desde, $hasta, null, $orden);
            $datos = toba::db()->consultar($sql);
            return $datos;
    }
    
	static public function armar_where ($filtro = array())
	{
        $sql1 = "SELECT PKG_KR_USUARIOS.in_ua_tiene_acceso(upper('".toba::usuario()->get_id()."')) unidades_ad, 
                        PKG_KR_USUARIOS.USUARIO_TIENE_UES(upper('".toba::usuario()->get_id()."')) usuario_ues, 
                        PKG_KR_USUARIOS.in_ue_tiene_acceso(upper('".toba::usuario()->get_id()."')) unidades_eje_us 
                   FROM DUAL";
        $res = toba::db()->consultar_fila($sql1); 
        $where = "((rant.COD_UNIDAD_ADMINISTRACION IN ".$res['unidades_ad'].") AND (('".$res['usuario_ues']."' ='N') OR (rant.COD_UNIDAD_EJECUTORA IN ".$res['unidades_eje_us'].")))";
        if (isset($filtro['fecha_imputacion'])) {
            $where .= " AND rant.fecha_imputacion = to_date(".quote($filtro['fecha_imputacion']).", 'YYYY-MM-DD') ";
            unset($filtro['fecha_imputacion']);
        }
        if (isset($filtro['fecha_anulacion'])) {
            $where .= " AND rant.fecha_anulacion = to_date(".quote($filtro['fecha_anulacion']).", 'YYYY-MM-DD') ";
            unset($filtro['fecha_anulacion']);
        }
        if (isset($filtro['observaciones'])) {
            $where .= " AND " . ctr_construir_sentencias::construir_translate_ilike('rant.observaciones', $filtro['observaciones']);
            unset($filtro['observaciones']);
        }
        if (isset($filtro['nro_rendicion'])) {
            $where .= " AND " . ctr_construir_sentencias::construir_translate_ilike('rant.nro_rendicion', $filtro['nro_rendicion']);
            unset($filtro['nro_rendicion']);
        }
		if (isset($filtro['ids_comprobantes'])) {
			$where .= " AND rant.id_rendicion_anticipo IN (" . $filtro['ids_comprobantes'] . ") ";
			unset($filtro['ids_comprobantes']);
		}
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'rant', '1=1');
		return $where;
	}
	
	static public function get_cantidad ($filtro = array())
	{
		$where = self::armar_where($filtro);
		$sql = " select count(*) cantidad
				   FROM   ad_rendiciones_anticipo rant
                    LEFT JOIN KR_UNIDADES_ADMINISTRACION ua ON rant.cod_unidad_administracion = ua.cod_unidad_administracion
                    LEFT JOIN KR_UNIDADES_EJECUTORAS ue ON rant.cod_unidad_ejecutora = ue.cod_unidad_ejecutora
                    LEFT JOIN KR_EXPEDIENTES krex ON rant.ID_EXPEDIENTE = krex.id_expediente
                    LEFT JOIN AD_COMPROBANTES_ANTICIPO cant ON rant.ID_COMPROBANTE_ANTICIPO = cant.id_comprobante_anticipo
                    LEFT JOIN AD_TIPOS_COMPROB_ANTICIPO ADTCA ON ADTCA.COD_TIPO_COMPROBANTE = cant.cod_tipo_comprobante
                    LEFT JOIN AD_TIPOS_RENDICION_ANTICIPO trant ON rant.COD_TIPO_RENDICION = trant.cod_tipo_rendicion
                    LEFT JOIN AD_BENEFICIARIOS ben ON ben.id_beneficiario = cant.id_beneficiario
                    WHERE ".$where;
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cantidad'];
	}
	
  	static public function get_rendicion_anticipo_x_id ($id_rendicion){
        if ($id_rendicion != null){
            $sql = "SELECT  rant.*,
                            ua.descripcion unidad_administracion,
                            ue.descripcion unidad_ejecutora,
                            to_char(rant.fecha_rendicion, 'dd/mm/yyyy') fecha_comprobante_format,
                            to_char(rant.fecha_aprueba, 'dd/mm/yyyy') fecha_aprueba_format,
                            to_char(rant.fecha_anulacion, 'dd/mm/yyyy') fecha_anula_format,
                            to_char(rant.fecha_carga, 'dd/mm/yyyy') fecha_carga_format,
                            to_char(rant.fecha_imputacion, 'dd/mm/yyyy') fecha_imputacion_format,
                            krex.nro_expediente nro_expediente,
                            cant.nro_comprobante nro_comprobante_anticipo,
                            cant.id_beneficiario id_beneficiario,
                            ben.nombre nombre_beneficiario,
                            cant.id_preventivo preventivo,
                            trant.descripcion tipo_ren_des,
                            trant.dev_gastos tipo_ren_dev_gasto,
                            trant.factura tipo_ren_fact,
                            ADTCA.COD_AUXILIAR
                    FROM   ad_rendiciones_anticipo rant
                    LEFT JOIN KR_UNIDADES_ADMINISTRACION ua ON rant.cod_unidad_administracion = ua.cod_unidad_administracion
                    LEFT JOIN KR_UNIDADES_EJECUTORAS ue ON rant.cod_unidad_ejecutora = ue.cod_unidad_ejecutora
                    LEFT JOIN KR_EXPEDIENTES krex ON rant.ID_EXPEDIENTE = krex.id_expediente
                    LEFT JOIN AD_COMPROBANTES_ANTICIPO cant ON rant.ID_COMPROBANTE_ANTICIPO = cant.id_comprobante_anticipo
                    LEFT JOIN AD_TIPOS_COMPROB_ANTICIPO ADTCA ON ADTCA.COD_TIPO_COMPROBANTE = cant.cod_tipo_comprobante
                    LEFT JOIN AD_TIPOS_RENDICION_ANTICIPO trant ON rant.COD_TIPO_RENDICION = trant.cod_tipo_rendicion
                    LEFT JOIN AD_BENEFICIARIOS ben ON ben.id_beneficiario = cant.id_beneficiario
                    WHERE rant.id_rendicion_anticipo = ".$id_rendicion.";";
            $datos = toba::db()->consultar($sql);
            return $datos[0];
        }else return null;
    }
  
    
  
    
  static public function get_beneficiario_x_anticipo ($id_anticipo){
        if (isset($id_anticipo) && $id_anticipo != null){
            $sql = "SELECT BEN.*
                    FROM AD_BENEFICIARIOS BEN, AD_COMPROBANTES_ANTICIPO ANT
                    WHERE ANT.ID_COMPROBANTE_ANTICIPO = ".$id_anticipo." AND ANT.ID_BENEFICIARIO = BEN.ID_BENEFICIARIO;";
            $datos = toba::db()->consultar($sql);
            if (isset($datos) && $datos != null)
                return $datos[0];
            else return null;
        }else return null;
    }
    
   static public function get_lov_tipo_rendicion_x_codigo ($codigo){
        if (isset($codigo) && !empty($codigo)){
            $sql = "SELECT tren.*, tren.COD_TIPO_RENDICION || ' - ' || tren.DESCRIPCION as lov_descripcion
                    FROM AD_TIPOS_RENDICION_ANTICIPO tren
                    WHERE tren.cod_tipo_rendicion = ".$codigo.";";
            $datos = toba::db()->consultar($sql);
            return $datos[0]['lov_descripcion'];
        }else return null;
    }
    
   static public function get_lov_tipos_rendicion_x_nombre ($nombre, $filtro = array ()){
        if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('tren.COD_TIPO_RENDICION', $nombre);
            $trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('tren.descripcion', $nombre);
            $where = "($trans_cod OR $trans_descricpcion)";
        } else {
            $where = "1=1";
        }
        $sql = "SELECT tren.*, tren.COD_TIPO_RENDICION || ' - ' || tren.DESCRIPCION as lov_descripcion
                FROM AD_TIPOS_RENDICION_ANTICIPO tren
                WHERE ".$where."
                ORDER BY lov_descripcion;";
        $datos = toba::db()->consultar($sql);
        if (isset($datos) && !empty($datos))
            return $datos;
        else
            return null;
    }
    
    static public function get_lov_factura_x_id($id_factura){     
       if (isset($id_factura)) {
           $sql = "SELECT ADFA.*, ADFA.ID_FACTURA ||' - '|| ADFA.NRO_FACTURA ||' - '|| ADFA.FECHA_EMISION as lov_descripcion
                   FROM AD_FACTURAS ADFA
                   WHERE ADFA.ID_FACTURA = ".quote($id_factura) .";";  
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
   static public function get_lov_factura_x_nombre ($nombre, $filtro = array()){
        if (isset($nombre)) {
            $trans_id = ctr_construir_sentencias::construir_translate_ilike('adfa.id_factura', $nombre);
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('adfa.nro_factura', $nombre);
            $where = "($trans_id OR $trans_nro)";
        } else {
            $where = "1=1";
        }
        if (isset($filtro['cod_unidad_administracion'])){
                $where .= " AND adfa.confirmada = 'S'
                            AND adfa.anulada = 'N'
                            AND adfa.cod_unidad_administracion = ".$filtro['cod_unidad_administracion']."
                            AND NOT EXISTS (SELECT 1 FROM ad_fact_rend_anticipo fra, ad_rendiciones_anticipo ren, ad_tipos_rendicion_anticipo tra
                            WHERE fra.id_rendicion_anticipo = ren.id_rendicion_anticipo AND ren.anulada = 'N' AND ren.aprobada = 'S' AND ren.cod_tipo_rendicion = tra.cod_tipo_rendicion
                            AND tra.factura = 'S' AND fra.id_factura = adfa.id_factura)
                            AND NOT EXISTS (SELECT 1 FROM ad_comprobantes_gasto cg WHERE cg.id_factura = adfa.id_factura AND cg.aprobado = 'S'
                            AND cg.anulado = 'N')
                            AND pkg_ad_facturas.caja_chica(ADFA.COD_TIPO_FACTURA) = 'N'"; 
            if (isset($filtro['cod_unidad_ejecutora'])){
                $where .= " AND adfa.cod_unidad_ejecutora = ".$filtro['cod_unidad_ejecutora'].")";
            }
            unset($filtro['cod_unidad_ejecutora']);
            unset($filtro['cod_unidad_administracion']);
        }
        $sql = "SELECT adfa.*, adfa.id_factura || ' - ' || adfa.nro_factura || ' - ' || adfa.cuit || ' - ' || adfa.razon_social as lov_descripcion
                FROM AD_FACTURAS adfa
                WHERE ".$where."
                ORDER BY adfa.id_factura asc;";
        $datos = toba::db()->consultar($sql);
        if (isset($datos) && !empty($datos))
            return $datos;
        else
            return null;
    }
   static public function get_importe_factura ($id_factura){
        if ($id_factura != null){
            $sql = "SELECT importe
                    FROM AD_FACTURAS
                    WHERE id_factura = ".$id_factura.";";
            $resultado = toba::db()->consultar($sql);
            if (!empty($resultado))
                return $resultado[0]['importe'];
            else return null;
        }else return null;
    }
    
    static public function get_lov_recibo_de_cobro_x_nombre($nombre, $filtro) {
        if (isset($nombre)) {
            $trans_id = ctr_construir_sentencias::construir_translate_ilike('ADRECB.id_recibo_cobro', $nombre);
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('ADRECB.nro_recibo', $nombre);
            $where = "($trans_id OR $trans_nro)";
        } else {
            $where = "1=1";
        }
            $where .= " AND ADRECB.aprobado = 'S' 
                    AND ADRECB.anulado = 'N' 
                    AND ADRECB.cod_unidad_administracion = ".$filtro['cod_unidad_administracion']."
                    AND PKG_AD_ANTICIPOS.RETORNAR_AUX_X_CUENTA_CTE(".$filtro['cod_unidad_administracion'].", ADRECB.id_cuenta_corriente) = ".$filtro['cod_auxiliar']."
                    AND NOT EXISTS (SELECT 1 FROM ad_rendiciones_anticipo adrean, ad_rendiciones_ant_rec_cob adreanrc WHERE adrean.id_rendicion_anticipo = adreanrc.id_rendicion_anticipo AND adrean.aprobada = 'S' and adrean.anulada = 'N' AND adreanrc.id_recibo_cobro = ADRECB.id_recibo_cobro AND adrean.id_rendicion_anticipo <> ".$filtro['id_rendicion_anticipo'].")
                    AND pkg_kr_transacciones.saldo_transaccion(adrecb.id_transaccion, adrecb.id_cuenta_corriente, sysdate) > 0";
        if ($filtro['cod_unidad_ejecutora'] == '0')
            $where .= " AND (adrecb.cod_unidad_ejecutora = ".$filtro['cod_unidad_ejecutora'].")";
        
        unset($filtro['cod_unidad_ejecutora']);
        unset($filtro['cod_unidad_administracion']);
        unset($filtro['cod_auxiliar']);
        
        $sql = "SELECT ADRECB.*, ADRECB.id_recibo_cobro ||' - '||ADRECB.nro_recibo||' - '||to_char(ADRECB.fecha_comprobante,'dd/mm/yyyy')||' - '|| ADRECB.importe as lov_descripcion
                FROM AD_RECIBOS_COBRO ADRECB
                WHERE ".$where.";";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }
  static public function set_pendiente ($id_rendicion_anticipo){
       try{
            $sql = "BEGIN :resultado := pkg_ad_anticipos.establecer_pend_rendicion_ant(:id_rendicion_anticipo); END;";		
            $parametros = array ( array(  'nombre' => 'id_rendicion_anticipo', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 32,
                                          'valor' => $id_rendicion_anticipo),
                                  array(  'nombre' => 'resultado', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => ''),
                            );
            toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            if ($resultado[1]['valor'] == 'OK'){
                toba::db()->cerrar_transaccion();
            }else{
                toba::db()->abortar_transaccion();
                toba::notificacion()->error($resultado[1]['valor']);
            }
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

   }
  static public function unset_pendiente ($id_rendicion_anticipo){
        try{
            $sql = "BEGIN :resultado := pkg_ad_anticipos.suprimir_pend_rendicion_ant(:id_rendicion_anticipo); END;";		
            $parametros = array ( array(  'nombre' => 'id_rendicion_anticipo', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 32,
                                          'valor' => $id_rendicion_anticipo),
                                  array(  'nombre' => 'resultado', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => ''),
                            );
            toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            if ($resultado[1]['valor'] == 'OK'){
                toba::db()->cerrar_transaccion();
            }else{
                toba::db()->abortar_transaccion();
                toba::notificacion()->error($resultado[1]['valor']);
            }
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
   }
  static public function aprobar_rendicion_anticipo ($id_rendicion_anticipo, $fecha){
       try{
            $sql = "BEGIN :resultado := pkg_ad_anticipos.confirmar_rendicion_anticipo(:id_rendicion_anticipo, to_date(substr(:fecha,1,10),'yyyy-mm-dd'));END;";		
            $parametros = array ( array(  'nombre' => 'id_rendicion_anticipo', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 32,
                                          'valor' => $id_rendicion_anticipo),
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
               toba::notificacion()->info("La rendicion fue aprobado con exito.");
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
  static public function anular_rendicion_anticipo ($id_rendicion_anticipo, $fecha, $con_transaccion = true){
        try{
        	if ($con_transaccion) {
                toba::db()->abrir_transaccion();
            }
            $sql = "BEGIN :resultado := pkg_ad_anticipos.anular_rendicion_anticipo(:id_rendicion_anticipo, to_date(substr(:fecha,1,10),'yyyy-mm-dd'));END;";		
            $parametros = array ( array(  'nombre' => 'id_rendicion_anticipo', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 32,
                                          'valor' => $id_rendicion_anticipo),
                                  array(  'nombre' => 'fecha', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 32,
                                          'valor' => $fecha),
                                  array(  'nombre' => 'resultado', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => ''),
                            );
                            
           $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
           if ($con_transaccion) {	
	           if ($resultado[2]['valor'] == 'OK'){
	               toba::db()->cerrar_transaccion();
	               toba::notificacion()->info("La rendicion fue anulada.");
	           }else{
	               toba::db()->abortar_transaccion();
	               toba::notificacion()->error($resultado[2]['valor']);
	           }
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
   
  static public function consultar_saldo_transaccional ($id_transaccion, $id_cuenta_corriente){
        try{
            $sql = "BEGIN :resultado := pkg_kr_transacciones.saldo_transaccion(:id_transaccion, :id_cuenta_corriente, sysdate);END;";		
            $parametros = array ( array(  'nombre' => 'id_transaccion', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 32,
                                          'valor' => $id_transaccion),
                                  array(  'nombre' => 'id_cuenta_corriente', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 32,
                                          'valor' => $id_cuenta_corriente),
                                  array(  'nombre' => 'resultado', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => ''),
                            );
           $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
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
  static public function get_importe_recibo_cobro ($id_recibo_cobro){
        if ($id_recibo_cobro != null){
            $sql = "SELECT IMPORTE as ui_importe_recibo
                    FROM AD_RECIBOS_COBRO 
                    WHERE ID_RECIBO_COBRO = ".$id_recibo_cobro.";";
           $resultado = toba::db()->consultar($sql);
            if (!empty($resultado))
                return $resultado[0]['ui_importe_recibo'];
            else return null;
        }else return null;
    }

    static public function get_tipos_rendicion_anticipos ($filtro = [])
    {
        $where = " 1=1 ";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'adtra', '1=1');
        $sql = "SELECT adtra.*, DECODE (adtra.dev_gastos, 'S', 'Si', 'No') dev_gastos_format,
                       DECODE (adtra.factura, 'S', 'Si', 'No') factura_format,
                       krtt.cod_tipo_transaccion || ' - ' || krtt.descripcion tipo_transaccion
                  FROM ad_tipos_rendicion_anticipo adtra LEFT JOIN kr_tipos_transaccion krtt
                       ON adtra.cod_tipo_transaccion = krtt.cod_tipo_transaccion
                 WHERE $where
                 ORDER BY adtra.cod_tipo_rendicion";

        return toba::db()->consultar($sql);
    }

    ////////////////////////////////////////////////////
    /////   UI_ITEMS          //////////////////////////
    ////////////////////////////////////////////////////
    
    
   static public function get_ui_importe_facturas ($id_rendicion_anticipo){
        if ($id_rendicion_anticipo != null){
            $sql = " SELECT NVL(SUM(fa.importe),0) AS ui_importe_facura
                     FROM ad_fact_rend_anticipo fare, ad_facturas fa
                     WHERE id_rendicion_anticipo = ".$id_rendicion_anticipo."
                          AND fa.id_factura = fare.id_factura;";
            $datos = toba::db()->consultar($sql);
            return $datos[0]['ui_importe_facura'];
        }else return null;
    }
   static public function get_ui_importe_devengados ($id_rendicion_anticipo){
       if ($id_rendicion_anticipo != null){
           $sql = "SELECT NVL(SUM(de.importe),0) AS ui_importe_devengados
                   FROM ad_rendiciones_ant_det dere, ad_comprobantes_gasto de
                   WHERE id_rendicion_anticipo = ".$id_rendicion_anticipo."
                         AND   de.id_comprobante_gasto = dere.id_comprobante_gasto
                         AND   de.id_factura is null;   ";
           $datos = toba::db()->consultar($sql);
           return $datos[0]['ui_importe_devengados'];
       }else return null;
    }
   static public function get_ui_importe_devuelto ($id_rendicion_anticipo){
       if ($id_rendicion_anticipo != null){
           $sql = "SELECT NVL(SUM(rc.importe),0) AS ui_importe_devuelto           
                   FROM ad_rendiciones_ant_rec_cob rc
                   WHERE id_rendicion_anticipo = ".$id_rendicion_anticipo.";";
           $datos = toba::db()->consultar($sql);
           return $datos[0]['ui_importe_devuelto'];
       }else return null;
    }
   static public function get_ui_factura ($cod_tipo_rendicion){
        if ($cod_tipo_rendicion != null){
            $sql = "SELECT factura as ui_factura
                    FROM ad_tipos_rendicion_anticipo
                    WHERE cod_tipo_rendicion = ".$cod_tipo_rendicion.";";
            $datos = toba::db()->consultar($sql);
            return $datos; 
        }else return null;
    }
   static public function get_ui_dev_gasto ($cod_tipo_rendicion){
        if ($cod_tipo_rendicion != null){
            $sql = "SELECT dev_gastos as ui_dev_gasto
                    FROM ad_tipos_rendicion_anticipo
                    WHERE cod_tipo_rendicion = ".$cod_tipo_rendicion.";";
            $datos = toba::db()->consultar($sql);
            return $datos; 
        }else return null;
    }
   static public function get_tca_cod_auxiliar ($id_comprobante_anticipo){
        if ($id_comprobante_anticipo != null){
            $sql = "SELECT tca.cod_auxiliar as cod_auxiliar
                    FROM AD_TIPOS_COMPROB_ANTICIPO tca, AD_COMPROBANTES_ANTICIPO ca
                    WHERE tca.cod_tipo_comprobante = ca.cod_tipo_comprobante AND ca.id_comprobante_anticipo = ".$id_comprobante_anticipo.";";
            $datos = toba::db()->consultar($sql);
            return $datos;
        }else{return null;}
    }
    
   static public function get_id_expediente_x_id_anticipo ($id_comprobante_anticipo){
        if ($id_comprobante_anticipo != null){
           $sql = "SELECT exp.id_expediente as ui_id_expediente_anticipo
                    FROM  KR_EXPEDIENTES exp LEFT JOIN AD_COMPROBANTES_ANTICIPO cant ON exp.id_expediente = cant.id_expediente
                    WHERE cant.id_comprobante_anticipo = ".$id_comprobante_anticipo.";";
            $datos = toba::db()->consultar($sql);
            return $datos[0]['ui_id_expediente_anticipo'];
        }else return null; 
    } 
   
}

?>
