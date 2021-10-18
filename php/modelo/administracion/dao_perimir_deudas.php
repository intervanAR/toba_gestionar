<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of dao_perimir_deudas
 *
 * @author ddiluca
 */
class dao_perimir_deudas {
    static public function get_perimir_deudas ($filtro=array()){
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
            $where = ctr_construir_sentencias::get_where_filtro($filtro, 'ADPD', '1=1');
            $sql = "SELECT  ADPD.*,
                            to_char(ADPD.fecha, 'dd/mm/yyyy') fecha_format,
                            to_char(ADPD.fecha_aprueba, 'dd/mm/yyyy') fecha_aprueba_format,
                            to_char(ADPD.fecha_anulacion, 'dd/mm/yyyy') fecha_anulacion_format,
                            to_char(ADPD.fecha_carga, 'dd/mm/yyyy') fecha_carga_format,
                            to_char(ADPD.fecha_anula, 'dd/mm/yyyy') fecha_anula_format,
                            KRUA.DESCRIPCION UNIDAD_ADMINISTRACION      
                    FROM AD_PERIMIR_DEUDA ADPD 
                         LEFT JOIN KR_UNIDADES_ADMINISTRACION KRUA ON ADPD.COD_UNIDAD_ADMINISTRACION = KRUA.COD_UNIDAD_ADMINISTRACION
                    WHERE $where
                    ORDER BY ADPD.ID_COMPROBANTE DESC;";
            $sql= dao_varios::paginador($sql, null, $desde, $hasta);
            $datos = toba::db()->consultar($sql);
            return $datos;
    }
    
   static public function get_perimir_deuda_x_id ($id_comprobante){
        if ($id_comprobante != null){
            $sql = "SELECT  ADPD.*,
                            to_char(ADPD.fecha, 'dd/mm/yyyy') fecha_format,
                            to_char(ADPD.fecha_aprueba, 'dd/mm/yyyy') fecha_aprueba_format,
                            to_char(ADPD.fecha_anulacion, 'dd/mm/yyyy') fecha_anulacion_format,
                            to_char(ADPD.fecha_carga, 'dd/mm/yyyy') fecha_carga_format,
                            to_char(ADPD.fecha_anula, 'dd/mm/yyyy') fecha_anula_format,
                            KRUA.DESCRIPCION UNIDAD_ADMINISTRACION      
                    FROM AD_PERIMIR_DEUDA ADPD 
                         LEFT JOIN KR_UNIDADES_ADMINISTRACION KRUA ON ADPD.COD_UNIDAD_ADMINISTRACION = KRUA.COD_UNIDAD_ADMINISTRACION
                    WHERE ADPD.ID_COMPROBANTE = ".quote($id_comprobante).";";
            $datos = toba::db()->consultar($sql);
            return $datos[0];
        }else return null;
    }
    
   static public function get_perimir_deudas_detalle ($id_comprobante){
       // $where = ctr_construir_sentencias::get_where_filtro($filtro, 'ADPDD', '1=1');
        if (isset($id_comprobante)){
            $sql = "SELECT ADPDD.*,
                            ADCG.ID_COMPROBANTE_GASTO, ADCG.NRO_COMPROBANTE,
                            to_char(ADCG.fecha_comprobante, 'dd/mm/yyyy') fecha_comprobante_format,
                            KRCC.NRO_CUENTA_CORRIENTE,
                            KRCC.DESCRIPCION,
                            ADCG.ID_TRANSACCION,
                            ADCG.IMPORTE
                    FROM  AD_PERIMIR_DEUDA_DET ADPDD
                           LEFT JOIN AD_COMPROBANTES_GASTO ADCG ON ADPDD.ID_COMPROBANTE_GASTO = ADCG.ID_COMPROBANTE_GASTO
                           LEFT JOIN KR_CUENTAS_CORRIENTE KRCC ON ADCG.ID_CUENTA_CORRIENTE = KRCC.ID_CUENTA_CORRIENTE
                    WHERE ADPDD.ID_COMPROBANTE = ".quote($id_comprobante)."  
                    ORDER BY ADPDD.ID_COMPROBANTE_GASTO DESC;";
             $datos = toba::db()->consultar($sql);
             return $datos;
        }else return null;
    }
    
   static public function get_ultimo_ejercicio (){
        $sql = "SELECT  KREJ.NRO_EJERCICIO NRO_EJERCICIO,
                        KREJ.DESCRIPCION DESCRIPCION,
                        KREJ.ID_EJERCICIO ID_EJERCICIO,
                        KREJ.ABIERTO ABIERTO,
                        KREJ.CERRADO CERRADO,
                        KREJ.FECHA_INICIO FECHA_INICIO,
                        KREJ.FECHA_FIN FECHA_FIN,
                        KREJ.ID_ESTRUCTURA ID_ESTRUCTURA, KREJ.NRO_EJERCICIO ||' - '|| KREJ.DESCRIPCION AS LOV_DESCRIPCION
                        FROM KR_EJERCICIOS KREJ
                        WHERE krej.nro_ejercicio = (SELECT MAX(nro_ejercicio)
                                                    FROM kr_ejercicios);";
        $datos = toba::db()->consultar_fila($sql);
        return $datos;
    }
    
   static public function get_lov_ejercicio_x_id ($id_ejercicio){
        $sql = "SELECT  KREJ.NRO_EJERCICIO NRO_EJERCICIO,
                        KREJ.DESCRIPCION DESCRIPCION,
                        KREJ.ID_EJERCICIO ID_EJERCICIO,
                        KREJ.ABIERTO ABIERTO,
                        KREJ.CERRADO CERRADO,
                        KREJ.FECHA_INICIO FECHA_INICIO,
                        KREJ.FECHA_FIN FECHA_FIN,
                        KREJ.ID_ESTRUCTURA ID_ESTRUCTURA, KREJ.NRO_EJERCICIO ||' - '|| KREJ.DESCRIPCION AS LOV_DESCRIPCION
                        FROM KR_EJERCICIOS KREJ
                        WHERE krej.id_ejercicio = ".quote($id_ejercicio).";";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['lov_descripcion'];
    }
    
   static public function get_lov_ejercicios ($nombre){
        if (isset($nombre)) {
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_ejercicio', $nombre);
            $trans_desc = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
            $where = "($trans_nro OR $trans_desc)";
        } else {
            $where = '1=1';
        }
        $sql = " SELECT KREJ.NRO_EJERCICIO NRO_EJERCICIO,
                        KREJ.DESCRIPCION DESCRIPCION,
                        KREJ.ID_EJERCICIO ID_EJERCICIO,
                        KREJ.ABIERTO ABIERTO,
                        KREJ.CERRADO CERRADO,
                        KREJ.FECHA_INICIO FECHA_INICIO,
                        KREJ.FECHA_FIN FECHA_FIN,
                        KREJ.ID_ESTRUCTURA ID_ESTRUCTURA, KREJ.NRO_EJERCICIO ||' - '|| KREJ.DESCRIPCION AS LOV_DESCRIPCION
                FROM KR_EJERCICIOS KREJ
                WHERE $where
                ORDER BY NRO_EJERCICIO DESC";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }
   static public function get_fecha_inicio_ejercicio ($id_ejercicio){
         $sql = "SELECT to_char(trunc(KREJ.fecha_inicio),'dd/mm/yyyy') fecha_desde
                 FROM KR_EJERCICIOS KREJ
                 WHERE krej.id_ejercicio = ".quote($id_ejercicio).";";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['fecha_desde'];
    }
   static public function get_fecha_fin_ejercicio ($id_ejercicio){
        $sql = "SELECT to_char(trunc(KREJ.fecha_fin),'dd/mm/yyyy') fecha_hasta
                 FROM KR_EJERCICIOS KREJ
                 WHERE krej.id_ejercicio = ".quote($id_ejercicio).";";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['fecha_hasta']; 
    }
   static public function anular_perimir_deuda ($id_comprobante, $fecha, $con_transaccion = true){
       try{
       	
       		if ($con_transaccion) {
                toba::db()->abrir_transaccion();
            }
				
            $sql = "BEGIN :resultado := pkg_ad_perimir_deuda.anular_comp_perimir_deuda(:id_comprobante, to_date(substr(:fecha,1,10),'yyyy-mm-dd'));END;";		
            $parametros = array ( array(  'nombre' => 'id_comprobante', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 32,
                                          'valor' => $id_comprobante),
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
	           }else{
	               toba::db()->abortar_transaccion();
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
  static public function aprobar_perimir_deuda ($id_comprobante){
       try{
            $sql = "BEGIN :resultado := pkg_ad_perimir_deuda.confirmar_comp_perimir_deuda(:id_comprobante);END;";		
            $parametros = array ( array(  'nombre' => 'id_comprobante', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 32,
                                          'valor' => $id_comprobante),
                                  array(  'nombre' => 'resultado', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => ''),
                            );
           toba::db()->abrir_transaccion();
           $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
           if ($resultado[1]['valor'] == 'OK'){
               toba::db()->cerrar_transaccion();
               toba::notificacion()->info('Comprobante Aprobados con exito.');
           }else{
               toba::db()->abortar_transaccion();
               toba::notificacion()->info('Error al Aprobar: '.$resultado[1]['valor']);
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
   static public function get_comprobantes_gastos ($fecha_inicio, $fecha_hasta){
        $where = "(ADCG.APROBADO = 'S') AND (ADCG.ANULADO = 'N') AND (pkg_kr_transacciones.saldo_transaccion(ADCG.id_transaccion,ADCG.id_cuenta_corriente,null)>0)";
        if (isset($fecha_inicio)){
            $where .= " AND to_date('".$fecha_inicio."','YYYY-MM-DD') <= ADCG.fecha_comprobante";
        }
        if (isset($fecha_hasta)){
            $where .= " AND to_date('".$fecha_hasta."','YYYY-MM-DD') >= ADCG.fecha_comprobante";
        }
        $sql = "SELECT ADCG.ID_COMPROBANTE_GASTO id_comprobante_gasto,
                       ADCG.NRO_COMPROBANTE nro_comprobante,
                       ADCG.FECHA_COMPROBANTE fecha_comprobante,
                       KRCC.NRO_CUENTA_CORRIENTE nro_cuenta_corriente,
                       KRCC.DESCRIPCION descripcion_cc,
                       ADCG.IMPORTE importe
                FROM AD_COMPROBANTES_GASTO ADCG, KR_CUENTAS_CORRIENTE KRCC
                WHERE $where and krcc.id_cuenta_corriente = adcg.id_cuenta_corriente
                ORDER BY id_comprobante_gasto DESC;";
        $datos = toba::db()->consultar($sql);
        for ($i = 0; $i < count($datos); $i++) {
                $datos[$i]['seleccion']='N';
        } 
        return $datos;
    }
   static public function cargar_comprobantes($id_comprobante, $id_comprobantes){
        if (!empty($id_comprobante) && !empty($id_comprobantes)){
            try {
                $sql = " BEGIN :resultado := pkg_ad_perimir_deuda.importar_comprobantes_gasto(:id_comprobante, :id_comprobantes); END;";		
                $parametros = array ( array(  'nombre' => 'id_comprobante', 
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 32,
                                                'valor' => $id_comprobante),
                                      array(  'nombre' => 'id_comprobantes', 
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 32000,
                                                'valor' => $id_comprobantes),

                                      array  (  'nombre' => 'resultado', 
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 4000,
                                                'valor' => ''),
                                );
                              
                toba::db()->abrir_transaccion();
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                if ($resultado[2]['valor'] == 'OK'){
                    toba::db()->cerrar_transaccion();
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
    }
  
}

?>
