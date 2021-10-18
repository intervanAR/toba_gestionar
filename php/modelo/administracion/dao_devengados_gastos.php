<?php

class dao_devengados_gastos
{
  public static function get_cantidad($filtro = array()){
    $where = self::get_where($filtro);
    $sql = "SELECT COUNT(*) cant
          FROM AD_COMPROBANTES_GASTO ACG
          WHERE $where";
    $datos = toba::db()->consultar_fila($sql);
    return $datos['cant'];
  }

  public static function get_where ($filtro = array()){
    $where = '';
    $sql1 = "SELECT PKG_KR_USUARIOS.in_ua_tiene_acceso(upper('".toba::usuario()->get_id()."')) unidades_ad,
                        PKG_KR_USUARIOS.USUARIO_TIENE_UES(upper('".toba::usuario()->get_id()."')) usuario_ues,
                        PKG_KR_USUARIOS.in_ue_tiene_acceso(upper('".toba::usuario()->get_id()."')) unidades_eje_us
                 FROM DUAL";
        $res = toba::db()->consultar_fila($sql1);
        $where = "((acg.COD_UNIDAD_ADMINISTRACION IN ".$res['unidades_ad'].") AND (('".$res['usuario_ues']."' ='N') OR (acg.COD_UNIDAD_EJECUTORA IN ".$res['unidades_eje_us'].")))";

        if (isset($filtro['ui_fecha_desde']) && isset($filtro['ui_fecha_hasta']) && isset($filtro['ui_tipo_fecha'])){
          if ($filtro['ui_tipo_fecha'] == 'fecha_comp'){
            $where .= " AND acg.fecha_comprobante between to_date('".$filtro['ui_fecha_desde']."','YYYY/MM/DD') and to_date('".$filtro['ui_fecha_hasta']."','YYYY/MM/DD')";
          }elseif($filtro['ui_tipo_fecha'] == 'fecha_venc'){
            $where .= " AND acg.fecha_vencimiento between to_date('".$filtro['ui_fecha_desde']."','YYYY/MM/DD') and to_date('".$filtro['ui_fecha_hasta']."','YYYY/MM/DD')";
          }elseif($filtro['ui_tipo_fecha'] == 'fecha_carga'){
            $where .= " AND acg.fecha_carga between to_date('".$filtro['ui_fecha_desde']."','YYYY/MM/DD') and to_date('".$filtro['ui_fecha_hasta']."','YYYY/MM/DD')";
          }elseif($filtro['ui_tipo_fecha'] == 'fecha_aprueba'){
            $where .= " AND acg.fecha_aprueba between to_date('".$filtro['ui_fecha_desde']."','YYYY/MM/DD') and to_date('".$filtro['ui_fecha_hasta']."','YYYY/MM/DD')";
          }elseif($filtro['ui_tipo_fecha'] == 'fecha_anulacion'){
            $where .= " AND acg.fecha_anulacion between to_date('".$filtro['ui_fecha_desde']."','YYYY/MM/DD') and to_date('".$filtro['ui_fecha_hasta']."','YYYY/MM/DD')";
          }
          unset($filtro['ui_fecha_desde']);
          unset($filtro['ui_fecha_hasta']);
          unset($filtro['ui_tipo_fecha']);
        }else{
          if (isset($filtro['ui_fecha_desde']))
            unset($filtro['ui_fecha_desde']);
          if (isset($filtro['ui_fecha_hasta']))
            unset($filtro['ui_fecha_hasta']);
          if (isset($filtro['ui_tipo_fecha']))
            unset($filtro['ui_tipo_fecha']);
        }

    if (isset($filtro['ids_comprobantes'])) {
      $where .= "AND ACG.id_comprobante_gasto IN (" . $filtro['ids_comprobantes'] . ") ";
      unset($filtro['ids_comprobantes']);
    }

    $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'acg', '1=1');
        return $where;
  }
  public static function get_devengados_gastos($filtro=array(), $orden = array())
  {
	  $desde= null;
	  $hasta= null;
	  if(isset($filtro['numrow_desde'])){
	     $desde= $filtro['numrow_desde'];
	     $hasta= $filtro['numrow_hasta'];

	     unset($filtro['numrow_desde']);
	     unset($filtro['numrow_hasta']);
	  }
      $where = self::get_where($filtro);

      $sql = "SELECT acg.cod_unidad_administracion, acg.nro_comprobante, acg.importe, acg.id_comprobante_gasto, acg.fecha_comprobante,
          kua.descripcion as unidad_administracion,
          kue.descripcion as unidad_ejecutora,
          decode(acg.aprobado,'S','Si','No') aprobado_format,
       	  decode(acg.anulado,'S','Si','No') anulado_format,
          acg.aprobado, acg.anulado, acg.id_factura,
          atcg.descripcion as tipo_comprobante,
          kcc.nro_cuenta_corriente || ' - ' || kcc.descripcion as nro_des_cuenta_corriente,
          ke.nro_expediente as nro_expediente,
          kep.nro_expediente as nro_expediente_pago,
          trim(to_char(acg.importe, '$999,999,999,990.00')) as importe_format,
          to_char(acg.fecha_comprobante, 'DD/MM/YYYY') as fecha_comprobante_format,
          to_char(acg.fecha_vencimiento, 'DD/MM/YYYY') as fecha_vencimiento_format,
          to_char(acg.fecha_anulacion, 'DD/MM/YYYY') as fecha_anulacion_format,
                            cc.nro_cuenta_corriente,
                            cc.tipo_cuenta_corriente,
                cgrc.rv_meaning as clase_comprobante_format,
                CASE
          WHEN acg.id_factura IS NOT NULL THEN
            SUBSTR(LPAD (adf.nro_factura, 12, 0),1,4) || '-' || SUBSTR(LPAD (adf.nro_factura, 12, 0),5,12)
          ELSE ''
        END AS factura
          FROM ad_comprobantes_gasto acg
            LEFT JOIN kr_unidades_administracion kua ON acg.cod_unidad_administracion = kua.cod_unidad_administracion
            LEFT JOIN kr_cuentas_corriente kcc ON acg.id_cuenta_corriente = kcc.id_cuenta_corriente
            LEFT JOIN ad_tipos_comprobante_gasto atcg ON acg.cod_tipo_comprobante = atcg.cod_tipo_comprobante
                LEFT JOIN ad_compromisos com ON com.id_compromiso = acg.id_compromiso
                LEFT JOIN kr_unidades_ejecutoras kue ON acg.cod_unidad_ejecutora = kue.cod_unidad_ejecutora
            LEFT JOIN kr_expedientes ke ON acg.id_expediente = ke.id_expediente
            LEFT JOIN kr_expedientes kep ON acg.id_expediente_pago = kep.id_expediente
            LEFT JOIN kr_cuentas_corriente cc ON acg.id_cuenta_corriente = cc.id_cuenta_corriente
            LEFT JOIN cg_ref_codes cgrc On (cgrc.rv_domain = 'AD_CLASE_COMPROBANTE' and cgrc.rv_low_value = acg.clase_comprobante)
            LEFT JOIN ad_facturas adf on adf.id_factura = acg.id_factura
              WHERE $where
          ORDER BY id_comprobante_gasto DESC";
        $sql= dao_varios::paginador($sql, null, $desde, $hasta, null ,$orden);
        $datos = toba::db()->consultar($sql);

        foreach ($datos as $key => $value) {
          $sql = "SELECT pkg_kr_transacciones.saldo_transaccion(acg.id_transaccion, acg.id_cuenta_corriente, sysdate) saldo_transaccion
               FROM ad_comprobantes_gasto acg
               WHERE acg.id_comprobante_gasto = ".$datos[$key]['id_comprobante_gasto'];
          $result = toba::db()->consultar_fila($sql);
          $datos[$key]['saldo_transaccion'] = $result['saldo_transaccion'];
        }
        return $datos;
  	}

     static public function get_nro_cuenta_corrinte ($id_comprobante_gasto){
          if (isset($id_comprobante_gasto)){
              $sql = "SELECT KRCC.NRO_CUENTA_CORRIENTE
                      FROM AD_COMPROBANTES_GASTO ADCG, KR_CUENTAS_CORRIENTE KRCC
                      WHERE ADCG.ID_CUENTA_CORRIENTE = KRCC.ID_CUENTA_CORRIENTE AND ADCG.ID_COMPROBANTE_GASTO = ".quote($id_comprobante_gasto).";";
              $datos = toba::db()->consutar_fila($sql);
              return $datos['NRO_CUENTA_CORRIENTE'];
          }else return null;
      }

     static public function get_lov_comprobante_gasto_x_nombre ($nombre, $filtro){
           if (isset($nombre)) {
                $trans_id = ctr_construir_sentencias::construir_translate_ilike('ADCOGA.ID_COMPROBANTE_GASTO', $nombre);
                $trans_nro = ctr_construir_sentencias::construir_translate_ilike('ADCOGA.NRO_COMPROBANTE', $nombre);
                $trans_id_factura = ctr_construir_sentencias::construir_translate_ilike('ADCOGA.ID_FACTURA', $nombre);
                $trans_id_cc = ctr_construir_sentencias::construir_translate_ilike('ADCOGA.ID_CUENTA_CORRIENTE', $nombre);
                $where = "($trans_id OR $trans_nro OR $trans_id_factura OR $trans_id_cc)";
            } else {
                $where = "1=1";
            }
            if (isset($filtro['para_rendicion_caja_chica'])) {
                $where .= " AND ADCOGA.ID_CUENTA_CORRIENTE = L_KRCTCT.ID_CUENTA_CORRIENTE AND
                                ADCOGA.ID_VENCIMIENTO = L_ADFAVE.ID_VENCIMIENTO (+) AND
                                (ADCOGA.APROBADO = 'S' AND ADCOGA.ANULADO = 'N'
                                  AND ADCOGA.COD_UNIDAD_ADMINISTRACION = ".$filtro['cod_unidad_administracion']."
                                  AND ADCOGA.ID_CAJA_CHICA = ".$filtro['id_caja_chica']."
                                      AND Pkg_Kr_Transacciones.saldo_transaccion (ADCOGA.ID_TRANSACCION, ADCOGA.ID_CUENTA_CORRIENTE, NULL) > 0
                                      AND not exists(select 1
                                                     from ad_rendiciones_cach_det det, ad_rendiciones_caja_chica ren, AD_TIPOS_RENDICION_CACH trcc
                                                     where det.id_rendicion_caja_chica = ren.id_rendicion_caja_chica and ren.anulada = 'N'
                                                     AND trcc.COD_TIPO_RENDICION = ren.COD_TIPO_RENDICION AND trcc.DEV_GASTOS = 'S'
                                                     and det.id_comprobante_gasto = ADCOGA.id_comprobante_gasto))
                                ";
                unset($filtro['para_rendicion_caja_chica']);
                unset($filtro['id_caja_chica']);
                unset($filtro['cod_unidad_administracion']);
            }
            $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'adcocach', '1=1');
            $sql = "SELECT  ADCOGA.*,
                            ADCOGA.ID_COMPROBANTE_GASTO || ' - Nro.Comp: ' || ADCOGA.NRO_COMPROBANTE || ' - ID Factura: ' || ADCOGA.ID_FACTURA ||' - Nro.CC: '|| ADCOGA.ID_CUENTA_CORRIENTE || ' - ' || l_krctct.descripcion as lov_descripcion
                      FROM  AD_COMPROBANTES_GASTO ADCOGA,KR_CUENTAS_CORRIENTE L_KRCTCT, AD_FACTURAS_VENCIMIENTOS L_ADFAVE
                     WHERE  $where
                  ORDER BY lov_descripcion;";
            $datos = toba::db()->consultar($sql);
            return $datos;
       }

  static public function calcular_importe ($id_comprobante_gasto){
    $sql = "SELECT NVL(SUM(d.importe),0) as importe
        FROM AD_COMPROBANTES_GAS_DET d, AD_COMPROBANTES_GASTO c
        WHERE c.id_comprobante_gasto = d.id_comprobante_gasto and c.id_comprobante_gasto = ".$id_comprobante_gasto.";";
    $datos = toba::db()->consultar_fila($sql);
    return $datos['importe'];
    }

      static public function calcular_importe_detalle ($id_detalle, $id_comprobante_gasto){
           if ($id_comprobante_gasto != null && $id_detalle != null){
               $sql = "SELECT nvl(d.importe,0) as importe
                       FROM  ad_comprobantes_gas_det d
                       WHERE d.id_comprobante_gasto = ".$id_comprobante_gasto." and d.id_detalle = ".$id_detalle.";";
               $datos = toba::db()->consultar($sql);
               return $datos[0]['importe'];
           }else{
               return null;
           }
       }
      static public function get_importe ($id_comprobante_gasto){
           //Recupera el importe del devengado seteado en la tabla AD_COMPROBANTES_GASTO (no realiza el calculo)
           if ($id_comprobante_gasto != null){
               $sql = "SELECT importe
                       FROM AD_COMPROBANTES_GASTO
                       WHERE id_comprobante_gasto = ".$id_comprobante_gasto.";";
               $resultado = toba::db()->consultar($sql);
               if (!empty($resultado))
                   return $resultado[0]['importe'];
               else return null;
           }else return null;

       }

      static public function aprobar_devengado_gasto($id_comprobante_gasto)
      {
        $sql = "BEGIN
                  :resultado := pkg_kr_transacciones.confirmar_comprobante_gasto(:id_comprobante_gasto);
                END;";
        $parametros = [
           ['nombre' => 'resultado',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 4000,
            'valor' => ''],
           ['nombre' => 'id_comprobante_gasto',
            'tipo_dato' => PDO::PARAM_INT,
            'longitud' => 32,
            'valor' => $id_comprobante_gasto]];
        ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
      }

      static public function reimputar_devengado_gasto ($id_comprobante_gasto, $fecha, $id_entidad, $id_programa, $cod_fuenta_financiera, $cod_recurso){
           if (isset($id_comprobante_gasto)) {


                  if (empty($cod_recurso))
                    $cod_recurso = '';
                  if (empty($cod_fuenta_financiera))
                    $cod_fuenta_financiera = '';
                  if (empty($id_entidad))
                    $id_entidad = '';
                  if (empty($id_programa))
                    $id_programa = '';

                    $sql = "BEGIN :resultado := pkg_kr_transacciones.reimputar_comprobante_gasto(:id_comprobante_gasto, to_date(substr(:fecha,1,10),'yyyy-mm-dd'),:id_entidad,:id_programa,:cod_fuente_financiera,:cod_recurso,:rei_id_comprobante_gasto); END;";
                     $parametros = array(array( 'nombre' => 'id_comprobante_gasto',
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 32,
                                                'valor' => $id_comprobante_gasto),
                        array(                  'nombre' => 'fecha',
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 32,
                                                'valor' => $fecha),
                        array(                  'nombre' => 'id_entidad',
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 32,
                                                'valor' => $id_entidad),
                        array(                  'nombre' => 'id_programa',
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 32,
                                                'valor' => $id_programa),
                        array(                  'nombre' => 'cod_fuente_financiera',
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 32,
                                                'valor' => $cod_fuenta_financiera),
                        array(                  'nombre' => 'cod_recurso',
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 32,
                                                'valor' => $cod_recurso),
                        array(                  'nombre' => 'rei_id_comprobante_gasto',
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 32,
                                                'valor' => ''),
                        array(                  'nombre' => 'resultado',
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 4000,
                                                'valor' => '')
                            );

                    toba::db()->abrir_transaccion();
                    $resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', false);
                    if ($resultado[7]['valor'] == 'OK'){
                        toba::db()->cerrar_transaccion();
                    }else{
                        toba::db()->abortar_transaccion();
                    }

                    return $resultado[7]['valor'];

          }
       }
      static public function generar_detalle_imputacion ($id_comprobante_gasto, $id_factura, $origen_comprobante){
           try {
                if (!empty($id_comprobante_gasto) && !empty($id_factura)&& !empty($origen_comprobante)) {
                    $sql = "BEGIN :resultado := pkg_ad_comprobantes_gasto.generar_detalle_imputacion(:id_comprobante_gasto, :id_factura, null, :origen_comprobante);END;";
                    $parametros = array ( array(  'nombre' => 'id_comprobante_gasto',
                                                    'tipo_dato' => PDO::PARAM_STR,
                                                    'longitud' => 32,
                                                    'valor' => $id_comprobante_gasto),
                                          array(  'nombre' => 'id_factura',
                                                    'tipo_dato' => PDO::PARAM_STR,
                                                    'longitud' => 32,
                                                    'valor' => $id_factura),
                                          array(  'nombre' => 'origen_comprobante',
                                                    'tipo_dato' => PDO::PARAM_STR,
                                                    'longitud' => 32,
                                                    'valor' => $origen_comprobante),
                                          array(  'nombre' => 'resultado',
                                                    'tipo_dato' => PDO::PARAM_STR,
                                                    'longitud' => 4000,
                                                    'valor' => ''),
                                    );
                    toba::db()->abrir_transaccion();
                    $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                    if ($resultado[3]['valor'] == 'OK'){
                        toba::db()->cerrar_transaccion();
                    }else{
                        toba::db()->abortar_transaccion();
                    }
                    return $resultado[3]['valor'];
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

      static public function tiene_orden_pago ($id_comprobante_gasto){
          $sql = "select count(adop.id_orden_pago) ordenes
                  from AD_COMPROBANTES_GASTO adcg, AD_ORDENES_PAGO_cg adop
                  where adcg.id_comprobante_gasto = adop.id_comprobante_gasto
                        and adcg.id_comprobante_gasto = ".quote($id_comprobante_gasto).";";
          $resultado = toba::db()->consultar_fila($sql);
          if ($resultado['ordenes'] > 0)
              return true;
          else
              return false;
      }

      static public function generar_orden_pago ($id_comprobante_gasto, $fecha_comprobante, $con_transaccion = true){
           try{
                if (isset($id_comprobante_gasto)) {
                             $sql = "BEGIN :resultado := PKG_KR_TRANS_AUTO.trans_auto_generar_orden_pago(:id_comprobante_gasto,to_date(substr(:fecha_comprobante,1,10),'yyyy-mm-dd'),:genero_orden);END;";
                             $parametros = array(array(  'nombre' => 'id_comprobante_gasto',
                                                         'tipo_dato' => PDO::PARAM_INT,
                                                         'longitud' => 32,
                                                         'valor' => $id_comprobante_gasto),
                                                 array(  'nombre' => 'fecha_comprobante',
                                                         'tipo_dato' => PDO::PARAM_INT,
                                                         'longitud' => 32,
                                                         'valor' => $fecha_comprobante),
                                                 array(  'nombre' => 'genero_orden',
                                                         'tipo_dato' => PDO::PARAM_STR,
                                                         'longitud' => 32,
                                                         'valor' =>''),
                                                 array( 'nombre' => 'resultado',
                                                         'tipo_dato' => PDO::PARAM_STR,
                                                         'longitud' => 4000,
                                                         'valor' =>''),
                                     );
                            if ($con_transaccion)
                              toba::db()->abrir_transaccion();
                            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                            if ($resultado[3]['valor'] == 'OK'){
                              if ($con_transaccion)
                                  toba::db()->cerrar_transaccion();
                                toba::notificacion()->info("Se genero la orden de pago ".$resultado['2']['valor']);
                            }else{
                              if ($con_transaccion)
                                  toba::db()->abortar_transaccion();
                                toba::notificacion()->error("No se pudo generar la orden de pago.");
                            }
                            return $resultado;
                             //ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, 'Se genero la orden de pago.', 'Error al generar orden de pago.');
                     }
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

      static public function anular_devengado_gasto ($id_comprobante_gasto, $fecha, $con_transaccion = true){
            try{
            $sql = "BEGIN :resultado := pkg_kr_transacciones.anular_comprobante_gasto(:id_comprobante_gasto, to_date(substr(:fecha,1,10),'yyyy-mm-dd')); END;";
            $parametros = array ( array(  'nombre' => 'id_comprobante_gasto',
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 32,
                                            'valor' => $id_comprobante_gasto),
                                    array(  'nombre' => 'fecha',
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 32,
                                            'valor' => $fecha),
                                    array(  'nombre' => 'resultado',
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
                  toba::notificacion()->info("El devengo fue anulado con exito.");
              }else{
                  toba::db()->abortar_transaccion();
                  toba::notificacion()->error("No se pudo anular el devengado.");
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

       static public function get_comprobante_gasto_x_id_2($id_comprobante_gasto){
         $sql = "SELECT *
               FROM AD_COMPROBANTES_GASTO
               WHERE ID_COMPROBANTE_GASTO = $id_comprobante_gasto";
         $datos = toba::db()->consultar_fila($sql);
         return $datos;
       }


      static public function get_comprobante_gasto_x_id($id_comprobante_gasto){
       if (isset($id_comprobante_gasto) && !empty($id_comprobante_gasto)) {
           $sql = "SELECT ADCOGA.*, ADCOGA.ID_COMPROBANTE_GASTO ||' - '|| ADCOGA.FECHA_COMPROBANTE || '-' || ADCOGA.ID_CUENTA_CORRIENTE || '-' || ADCOGA.IMPORTE as lov_descripcion
                   FROM AD_COMPROBANTES_GASTO ADCOGA
                   WHERE ADCOGA.id_comprobante_gasto = $id_comprobante_gasto
                   ORDER BY lov_descripcion;";
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

static  public function get_tipo_devengados_gastos($filtro=array())
      {
      $where = ctr_construir_sentencias::get_where_filtro($filtro, 'atcg', '1=1');
      $sql = "SELECT atcg.*
              , decode(atcg.COMPROMISO_PREVIO,'S','Si','No') compromiso_previo_format
              , decode(atcg.negativo,'S','Si','No') negativo_format
              , decode(atcg.genera_COMPROMISO,'S','Si','No') genera_compromiso_format
              , decode(atcg.nota_credito,'S','Si','No') nota_credito_format
              , decode(atcg.automatico,'S','Si','No') automatico_format
              , (select rv_meaning from cg_ref_codes where rv_domain = 'KR_TIPO_CUENTA_CORRIENTE' AND RV_LOW_VALUE = atcg.TIPO_CUENTA_CORRIENTE ) tipo_cuenta_corriente_format
              , case
                   WHEN ATCG.ORIGEN_COMPROBANTE = 'SIN' THEN
                      'SIN - Sin Origen'
                   WHEN ATCG.ORIGEN_COMPROBANTE = 'FNO' THEN
                      'FNO - Factura Normal'
                   WHEN ATCG.ORIGEN_COMPROBANTE = 'FCV' THEN
                      'FCV - Factura Con Vencimiento'
                   WHEN ATCG.ORIGEN_COMPROBANTE = 'FCC' THEN
                      'FCC - Factura Con Cuotas'
                END ORIGEN_COMPROBANTE_FORMAT
              , case
                     WHEN ATCG.PROCESO_COMPROBANTE = 'NOR' THEN
                        'NOR - Pago Normal'
                     WHEN ATCG.PROCESO_COMPROBANTE = 'RAN' THEN
                        'RAN - Rendición Anticipo'
                     WHEN ATCG.PROCESO_COMPROBANTE = 'RCC' THEN
                        'RCC - Rendición Caja Chica'
                     WHEN ATCG.PROCESO_COMPROBANTE = 'PRE' THEN
                        'PRE - Prestamo'
                END PROCESO_COMPROBANTE_FORMAT
              , (select descripcion from kr_tipos_transaccion where cod_tipo_transaccion = atcg.cod_tipo_transaccion ) tipo_transaccion_format
              , (select descripcion from kr_tipos_transaccion where cod_tipo_transaccion = atcg.cod_tipo_transaccion_ajusta ) tipo_transaccion_aju_format
              , (select descripcion from kr_tipos_transaccion where cod_tipo_transaccion = atcg.cod_tipo_transaccion_reimputa ) tipo_transaccion_rei_format
        FROM ad_tipos_comprobante_gasto atcg
        WHERE $where
        ORDER BY descripcion;";
      $datos = toba::db()->consultar($sql);
      return $datos;
  }

static public function get_clase_devengados_gastos($filtro=array())
      {
      $datos = array( array('clase_comprobante' => 'NOR', 'descripcion' => 'Normal'),
          array('clase_comprobante' => 'AJU', 'descripcion' => 'Ajuste'),
          array('clase_comprobante' => 'REI', 'descripcion' => 'Reimputaciï¿½n')
          );
      return $datos;
  }

       static public function get_tipo_cuenta_corriente($filtro=array())
      {
      $datos = array( array('tipo_cuenta_corriente' => 'C', 'descripcion' => 'Cuenta a Cobrar '),
          array('tipo_cuenta_corriente' => 'P', 'descripcion' => 'Cuenta a Pagar'),
          array('tipo_cuenta_corriente' => 'A', 'descripcion' => 'Cuenta Auxiliar'),
                            array('tipo_cuenta_corriente' => 'J', 'descripcion' => 'Cuenta Caja Chica'),
                            array('tipo_cuenta_corriente' => 'G', 'descripcion' => 'Cuenta Fondo GarantÃ­a')
          );
      return $datos;
  }


  static public function get_descripcion_tipo_cuenta_corriente($tipo_cuenta_corriente)
      {
            $filtro = array ('tipo_cuenta_corriente'=>$tipo_cuenta_corriente);
      $datos = self::get_tipo_cuenta_corriente($filtro);
      foreach ($datos as $clase) {
    if ($clase['tipo_cuenta_corriente'] == $tipo_cuenta_corriente) {
        return $clase['descripcion'];
    }

      }
      return '';
  }
  static public function get_descripcion_clase_comprobante($clase_comprobante)
      {
      $datos = self::get_clase_devengados_gastos();
      foreach ($datos as $clase) {
    if ($clase['clase_comprobante'] == $clase_comprobante) {
        return $clase['descripcion'];
    }

      }
      return '';
  }

        static public function get_proximo_id_detalle($id_comprobante_gasto) {
      if (isset($id_comprobante_gasto)) {
    $sql = "SELECT NVL(MAX(NVL(id_detalle,0)),0) + 1 as id_detalle
      FROM ad_comprobantes_gas_det
      WHERE id_comprobante_gasto = ".quote($id_comprobante_gasto)."";
    $datos = toba::db()->consultar_fila($sql);
    if (isset($datos) && !empty($datos) && isset($datos['id_detalle'])) {
        return $datos['id_detalle'];
    } else {
        return null;
    }
      } else {
    return null;
      }
  }
  static public function get_nro_comprobante_x_id($id_comprobante_gasto){
           if (isset($id_comprobante_gasto)) {
    $sql = "SELECT nro_comprobante
      FROM ad_comprobantes_gasto
      WHERE id_comprobante_gasto = ".quote($id_comprobante_gasto)."";
    $datos = toba::db()->consultar_fila($sql);
                return $datos['nro_comprobante'];
      } else {
    return null;
      }
        }
        static public function get_id_transaccion_x_id_comprobante($id_comprobante_gasto){
           if (isset($id_comprobante_gasto)) {
    $sql = "SELECT id_transaccion
      FROM ad_comprobantes_gasto
      WHERE id_comprobante_gasto = ".quote($id_comprobante_gasto)."";
    $datos = toba::db()->consultar_fila($sql);
                return $datos['id_transaccion'];
      } else {
    return null;
      }
        }
        static public function get_fecha_comprobante_x_id($id_comprobante_gasto){
           if (isset($id_comprobante_gasto)) {
    $sql = "SELECT to_char(fecha_comprobante, 'DD/MM/YYYY') as fecha_comprobante
      FROM ad_comprobantes_gasto
      WHERE id_comprobante_gasto = ".quote($id_comprobante_gasto)."";
    $datos = toba::db()->consultar_fila($sql);
                return $datos['fecha_comprobante'];
      } else {
    return null;
      }
        }
        static public function get_nro_cuenta_corriente_x_id_comprobante($id_comprobante_gasto){
           if (isset($id_comprobante_gasto)) {
    $sql = "SELECT krcc.nro_cuenta_corriente nro_cuenta_corriente
      FROM ad_comprobantes_gasto adcg, KR_CUENTAS_CORRIENTE krcc
      WHERE adcg.id_cuenta_corriente = krcc.id_cuenta_corriente and id_comprobante_gasto = ".quote($id_comprobante_gasto)."";
    $datos = toba::db()->consultar_fila($sql);
                return $datos['nro_cuenta_corriente'];
      } else {
    return null;
      }
        }
        static public function get_descripcion_cuenta_corriente_x_id_comprobante($id_comprobante_gasto){
           if (isset($id_comprobante_gasto)) {
    $sql = "SELECT krcc.descripcion descripcion_cc
      FROM ad_comprobantes_gasto adcg, KR_CUENTAS_CORRIENTE krcc
      WHERE adcg.id_cuenta_corriente = krcc.id_cuenta_corriente and id_comprobante_gasto = ".quote($id_comprobante_gasto)."";
    $datos = toba::db()->consultar_fila($sql);
                return $datos['descripcion_cc'];
      } else {
    return null;
      }
        }
  static public function get_importe_devengado_gasto($id_comprobante_gasto) {
      if (isset($id_comprobante_gasto)) {
    $sql = "SELECT NVL(importe,0) as importe
      FROM ad_comprobantes_gasto
      WHERE id_comprobante_gasto = ".quote($id_comprobante_gasto)."";
    $datos = toba::db()->consultar_fila($sql);
    if (isset($datos) && !empty($datos) && isset($datos['importe'])) {
        return $datos['importe'];
    } else {
        return 0;
    }
      } else {
    return 0;
      }
  }

  static public function get_importe_detalle_devengado_gasto($id_comprobante_gasto, $id_detalle) {
      if (isset($id_comprobante_gasto) && isset($id_detalle)) {
    $sql = "SELECT NVL(importe,0) as importe
      FROM ad_comprobantes_gas_det
      WHERE id_comprobante_gasto = ".quote($id_comprobante_gasto)."
      AND id_detalle = ".quote($id_detalle)."";
    $datos = toba::db()->consultar_fila($sql);
    if (isset($datos) && !empty($datos) && isset($datos['importe'])) {
        return $datos['importe'];
    } else {
        return 0;
    }
      } else {
    return 0;
      }
  }

  static public function get_proximo_id_imputacion($id_comprobante_gasto, $id_detalle) {
      if (isset($id_comprobante_gasto) && isset($id_detalle)) {
    $sql = "SELECT NVL(MAX(NVL(id_imputacion,0)),0) + 1 as id_imputacion
      FROM ad_comprobantes_gas_imp
      WHERE id_comprobante_gasto = ".quote($id_comprobante_gasto)."
      AND id_detalle = ".quote($id_detalle)."";
    $datos = toba::db()->consultar_fila($sql);
    if (isset($datos) && !empty($datos) && isset($datos['id_imputacion'])) {
        return $datos['id_imputacion'];
    } else {
        return null;
    }
      } else {
    return null;
      }
  }
     static public function obtener_datos_comprobante($id_comprobante_pago){
    try{
      if (isset($id_comprobante_pago)&&(!empty($id_comprobante_pago))) {
          $sql = "BEGIN :resultado := pkg_ad_comprobantes_pagos.obtener_datos_comprobante (:id_comprobante_pago, :cod_moneda, :v_numero, :importe_nominal, :id_cuenta_banco, :id_sub_cuenta_banco, :v_beneficiario); END;";

          $parametros = array ( array(  'nombre' => 'id_comprobante_pago',
                          'tipo_dato' => PDO::PARAM_STR,
                          'longitud' => 32,
                          'valor' => $id_comprobante_pago),
                      array(  'nombre' => 'cod_moneda',
                          'tipo_dato' => PDO::PARAM_STR,
                          'longitud' => 32,
                          'valor' => ''),
                      array(  'nombre' => 'v_numero',
                          'tipo_dato' => PDO::PARAM_STR,
                          'longitud' => 32,
                          'valor' => ''),
                      array(  'nombre' => 'importe_nominal',
                          'tipo_dato' => PDO::PARAM_STR,
                          'longitud' => 32,
                          'valor' => ''),
                      array(  'nombre' => 'id_cuenta_banco',
                          'tipo_dato' => PDO::PARAM_STR,
                          'longitud' => 32,
                          'valor' => ''),
                      array(  'nombre' => 'id_sub_cuenta_banco',
                          'tipo_dato' => PDO::PARAM_STR,
                          'longitud' => 32,
                          'valor' => ''),
                      array(  'nombre' => 'v_beneficiario',
                          'tipo_dato' => PDO::PARAM_STR,
                          'longitud' => 32,
                          'valor' => ''),
                      array(  'nombre' => 'resultado',
                          'tipo_dato' => PDO::PARAM_STR,
                          'longitud' => 400,
                          'valor' => '')
                  );
          $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
          return array('cod_moneda' => $resultado[1]['valor'], 'v_numero' => $resultado[2]['valor'], 'importe_nominal' => $resultado[3]['valor'], 'id_cuenta_banco' => $resultado[4]['valor'], 'id_sub_cuenta_banco' => $resultado[5]['valor'], 'v_beneficiario' => $resultado[6]['valor']);
        }else{
          return '';
        }
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
         //   toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
        //    toba::db()->abortar_transaccion();
        }
  }
     static public function get_lov_comprobantes_gasto_x_nombre($nombre, $filtro = array())
  {
    if (isset($nombre)) {
      $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('id_comprobante_gasto', $nombre);
      $trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_comprobante', $nombre);
      $where = "($trans_codigo OR $trans_nro)";
        } else {
            $where = '1=1';
        }
    if (isset($filtro['para_rendicion_ant_detalle'])){
      $where .= " AND cg.APROBADO = 'S'
            AND cg.ANULADO = 'N'
            AND cg.COD_UNIDAD_ADMINISTRACION = ".$filtro['cod_unidad_administracion']."
            AND Pkg_Kr_Transacciones.saldo_transaccion (cg.ID_TRANSACCION, cg.ID_CUENTA_CORRIENTE, NULL) > 0
            AND not exists(select 1 from ad_rendiciones_ant_det det, ad_rendiciones_anticipo ren, AD_TIPOS_RENDICION_ANTICIPO tra
                 where det.id_rendicion_anticipo = ren.id_rendicion_anticipo and ren.anulada = 'N'
                   and ren.COD_TIPO_RENDICION = tra.COD_TIPO_RENDICION and tra.dev_gastos = 'S' and det.id_comprobante_gasto = cg.id_comprobante_gasto)
            AND cg.cod_auxiliar = ".$filtro['cod_auxiliar']."
            AND (".$filtro['cod_unidad_ejecutora']." IS NULL OR cg.cod_unidad_ejecutora = ".$filtro['cod_unidad_ejecutora'].")";
      unset($filtro['para_rendicion_ant_detalle']);
      unset($filtro['cod_unidad_ejecutora']);
      unset($filtro['cod_unidad_administracion']);
    }
    if (isset($filtro['presupuestario'])) {
      $where.= " AND PKG_AD_COMPROBANTES_PAGOS.muestro_gasto(cg.ANULADO, cg.APROBADO, '".$filtro['presupuestario']."',
          cg.IMPORTE, cg.CLASE_COMPROBANTE,
          Pkg_Kr_Transacciones.saldo_transaccion (cg.ID_TRANSACCION, cg.ID_CUENTA_CORRIENTE, NULL),
          '".$filtro['tipo_cuenta_corriente']."', '".$filtro['ejercicio_anterior']."',
          cg.FECHA_COMPROBANTE, to_date(substr('".$filtro['fecha_comprobante']."',1,10),'yyyy-mm-dd'),
          cg.ID_CUENTA_CORRIENTE , ".$filtro['id_cuenta_corriente'].") = 'S'
          AND PKG_KR_USUARIOS.TIENE_UA(upper('".$filtro['usuario']."'),cg.COD_UNIDAD_ADMINISTRACION) = 'S'
          AND ( p_unidad_ejecutora IS NULL OR cg.cod_unidad_ejecutora = p_unidad_ejecutora )";

      if (isset($filtro['cod_unidad_ejecutora'])) {
        if (empty($filtro['cod_unidad_ejecutora'])){
          $where = str_replace("p_unidad_ejecutora", "null", $where, $count);
        }else{
          $where = str_replace("p_unidad_ejecutora", $filtro['cod_unidad_ejecutora'], $where, $count);
        }
      }else{
        $where = str_replace("p_unidad_ejecutora", "null", $where, $count);
      }

      unset($filtro['presupuestario']);
      unset($filtro['tipo_cuenta_corriente']);
      unset($filtro['ejercicio_anterior']);
      unset($filtro['fecha_comprobante']);
      unset($filtro['usuario']);
      unset($filtro['id_cuenta_corriente']);
      unset($filtro['cod_unidad_ejecutora']);
    }

    if (isset($filtro['para_ordenes_pago'])) {

      if (empty($filtro['id_expediente'])){$filtro['id_expediente']='null';}
      if (empty($filtro['id_expediente_pago'])){$filtro['id_expediente_pago']='null';}
      if (empty($filtro['cod_uni_ejec'])){$filtro['cod_uni_ejec']='null';}

       $where .= " AND  (    cg.aprobado = 'S'
             AND cg.anulado = 'N'
                       AND cg.fecha_comprobante <= to_date('".substr($filtro['fecha_orden_pago'],0,10)."','YYYY/MM/DD')
             AND ".$filtro['cod_uni_admin']." = cg.cod_unidad_administracion
             AND (   (   '".$filtro['tipo_orden_pago_ejercicio_anteriori']."'  = 'N'
             AND pkg_kr_ejercicios.retornar_nro_ejercicio(to_date('".substr($filtro['fecha_orden_pago'],0,10)."','YYYY/MM/DD')) =
               pkg_kr_ejercicios.retornar_nro_ejercicio(cg.fecha_comprobante))
               OR (    '".$filtro['tipo_orden_pago_ejercicio_anteriori']."' = 'S'
             AND pkg_kr_ejercicios.retornar_nro_ejercicio(to_date('".substr($filtro['fecha_orden_pago'],0,10)."','YYYY/MM/DD')) >
               pkg_kr_ejercicios.retornar_nro_ejercicio(cg.fecha_comprobante))
               )
             AND cg.id_cuenta_corriente = ".$filtro['id_cta_cte']."
             AND cg.importe > 0
             AND pkg_kr_transacciones.saldo_transaccion
                            (cg.id_transaccion,
                             cg.id_cuenta_corriente,
                             SYSDATE
                            ) > 0
             AND pkg_kr_transacciones.saldo_ordenado (cg.id_comprobante_gasto) > 0
             AND (   cg.id_expediente = ".$filtro['id_expediente']."
               OR cg.id_expediente IS NULL
               OR ".$filtro['id_expediente']." IS NULL
               )
             AND (   cg.id_expediente_pago =  ".$filtro['id_expediente_pago']."
               OR cg.id_expediente_pago IS NULL
               OR ".$filtro['id_expediente_pago']." IS NULL
               )
        AND (   ".$filtro['cod_uni_ejec']." IS NULL
          OR cg.cod_unidad_ejecutora = ".$filtro['cod_uni_ejec']." )
        AND pkg_ad_comprobantes_gasto.esta_perimido(cg.id_comprobante_gasto, SYSDATE) = 'N'
        AND NOT EXISTS (
           SELECT 1
             FROM ad_comprobantes_gasto cg1
            WHERE cg1.id_comprobante_gasto_rei = cg.id_comprobante_gasto
            AND cg1.aprobado = 'S'
            AND cg1.anulado = 'N')
        AND NOT EXISTS (
           SELECT 1
             FROM ad_comprobantes_gasto cg1
            WHERE cg1.id_comprobante_gasto =
                           cg.id_comprobante_gasto_aju
            AND cg1.aprobado = 'S'
            AND cg1.anulado = 'N')
       )
       AND cg.id_caja_chica is null ";
       unset($filtro['para_ordenes_pago']);
       unset($filtro['cod_uni_admin']);
       unset($filtro['tipo_orden_pago_ejercicio_anteriori']);
       unset($filtro['fecha_orden_pago']);
       unset($filtro['id_cta_cte']);
       unset($filtro['id_expediente']);
       unset($filtro['id_expediente_pago']);
       unset($filtro['cod_uni_ejec']);
    }

    $where .= ' AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'cg', '1=1');

        $sql = "SELECT  cg.*,
            cg.nro_comprobante||' (' || cg.id_comprobante_gasto||') - '||to_char(cg.fecha_comprobante,'DD/MM/YYYY')  ||' ($'||(select saldo_devengado(CG.id_comprobante_gasto) importe from dual)||') '||L_ADFA.nro_factura as lov_descripcion
        FROM AD_COMPROBANTES_GASTO cg,
         AD_FACTURAS L_ADFA
                WHERE $where
                and cg.ID_FACTURA = L_ADFA.ID_FACTURA (+)
                ORDER BY lov_descripcion ASC;";
        $datos = toba::db()->consultar($sql);

        return $datos;

  }

   static public function get_lov_partidas_x_id($id_detalle) {
        if (isset($id_detalle)) {
            $sql = "SELECT det.*, det.id_detalle  || ' - ' || det.cod_partida || ' - ' || prp.descripcion as lov_descripcion
    FROM ad_comprobantes_gas_det det join pr_partidas prp on prp.cod_partida = det.cod_partida
    WHERE det.id_comprobante_gasto = ".quote($id_comprobante_gasto)." and det.id_detalle = " . quote($id_detalle) . ";";
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

    static public function get_lov_comprobante_gasto_x_id ($id_comprobante_gasto)
  {
        if (isset($id_comprobante_gasto) && !empty($id_comprobante_gasto)){
            $sql = "SELECT  dgas.*,
              dgas.nro_comprobante ||' (' || dgas.id_comprobante_gasto || ') - ' || to_char(dgas.fecha_comprobante,'DD/MM/YYYY') ||' ($'||dgas.importe||') '||L_ADFA.nro_factura  as lov_descripcion
                    FROM AD_COMPROBANTES_GASTO dgas,
                        AD_FACTURAS L_ADFA
                    WHERE dgas.id_comprobante_gasto = ".$id_comprobante_gasto."
                    and dgas.ID_FACTURA = L_ADFA.ID_FACTURA (+)
                    ;";
            $datos = toba::db()->consultar($sql);
            if (isset($datos) && !empty($datos))
                return $datos[0]['lov_descripcion'];
            else return '';
        }else return '';
    }

  static public function get_lov_partidas_x_nombre ($nombre, $filtro, $cod_unidad_administracion, $id_compromiso, $fecha_comp_gasto, $ui_id_egreso, $ui_sin_control_pres){
        if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('cod_partida', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
            $where = "($trans_cod OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
        $where .= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'pre', '1=1');
        $sql = "SELECT PRPA.*, PRPA.COD_PARTIDA COD_PARTIDA || '-' || PRPA.DESCRIPCION DESCRIPCION || '-' ||
                                        pkg_pr_totales.saldo_acumulado_egreso(".$cod_unidad_administracion.", pkg_kr_ejercicios.retornar_ejercicio(to_date('".$fecha_comp_gasto."'),'yyyy-mm-dd')), null, null, PRPA.COD_PARTIDA, null, null, 'PRES', sysdate) || '-' ||
                                        pkg_pr_totales.saldo_transaccion_egreso(".$ui_id_egreso.", null, null, PRPA.COD_PARTIDA, null, null) AS lov_descripcion
                FROM PR_PARTIDAS PRPA
                WHERE (pkg_pr_partidas.imputable(PRPA.cod_partida) = 'S' and (".$ui_sin_control_pres." = 'S' OR (".$ui_sin_control_pres." = 'N' and ((".$id_compromiso." is null or (".$id_compromiso." is not null and
                       exists(select 1
                              from ad_compromisos adco, ad_compromisos_det adcode
                              where (adco.id_compromiso = ".$id_compromiso." OR adco.id_compromiso_aju = ".$id_compromiso.") and adco.aprobado = 'S' and adco.anulado = 'N' and adco.id_compromiso = adcode.id_compromiso AND adcode.cod_partida = PRPA.cod_partida)))))))
                ORDER BY lov_dsecripcion;";
        $datos = toba::db()->consultar($sql);
        return $datos;

  }


    static public function get_lov_facturas_x_nombre($nombre, $filtro= array()) {
        if (isset($nombre)) {
            $trans_id = ctr_construir_sentencias::construir_translate_ilike('ADFA.ID_FACTURA', $nombre);
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('ADFA.NRO_FACTURA', $nombre);
            $trans_fecha_emision = ctr_construir_sentencias::construir_translate_ilike('ADFA.FECHA_EMISION', $nombre);
            $trans_importe = ctr_construir_sentencias::construir_translate_ilike('ADFA.IMPORTE', $nombre);
            $where = "($trans_id OR $trans_nro OR $trans_fecha_emision OR $trans_importe)";
        } else {
            $where = '1=1';
        }
        $join= '';

        if (isset($filtro['cod_unidad_administracion'])) {
            $where.= " AND ADFA.confirmada = 'S'
                       AND ADFA.anulada = 'N'
                       AND ADFA.cod_unidad_administracion = ".$filtro['cod_unidad_administracion']."
                       AND pkg_ad_facturas.mostrar_factura('".$filtro['ui_origen_comprobante']."', ADFA.id_factura) = 'S'
                       AND pkg_ad_facturas.factura_tiene_saldo(ADFA.id_factura) = 'S'
                       AND exists(select 1
                                  from  ad_proveedores pro, kr_cuentas_corriente cuco
                                  where pro.id_proveedor = cuco.id_proveedor and cuco.id_cuenta_corriente = ".$filtro['id_cuenta_corriente']."
                                        and pro.id_proveedor = adfa.id_proveedor) ";
            if (isset($filtro['cod_unidad_ejecutora'])){
                       $where.= " AND ".$filtro['cod_unidad_ejecutora']." IS NULL OR adfa.cod_unidad_ejecutora = ".$filtro['cod_unidad_ejecutora'];
            }

        }
        unset($filtro['id_cuenta_corriente']);
        unset($filtro['cod_unidad_administracion']);
        unset($filtro['cod_unidad_ejecutora']);
        unset($filtro['cod_tipo_comprobante']);
        unset($filtro['ui_origen_comprobante']);
        $where .= ' AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'ADFA', '1=1');
        $sql = "SELECT  ADFA.*,
                adfa.id_factura ||' - '||
                CASE
                    WHEN ADFA.nro_factura IS NOT NULL THEN
                        SUBSTR(LPAD (ADFA.nro_factura, 12, 0),1,4) || '-' || SUBSTR(LPAD (ADFA.nro_factura, 12, 0),5,12)
                    ELSE ''
                END ||'  '||
                to_char(adfa.fecha_emision,'DD/MM/YYYY') || '  ' || l_krex.nro_expediente || ' - ' ||  L_ADPR.RAZON_SOCIAL as lov_descripcion
                FROM AD_FACTURAS ADFA, AD_PROVEEDORES L_ADPR, KR_EXPEDIENTES L_KREX_PAGO, KR_EXPEDIENTES L_KREX
                WHERE ADFA.ID_PROVEEDOR = L_ADPR.ID_PROVEEDOR AND
                      ADFA.ID_EXPEDIENTE_PAGO = L_KREX_PAGO.ID_EXPEDIENTE (+) AND
                      ADFA.ID_EXPEDIENTE = L_KREX.ID_EXPEDIENTE (+) and $where
                ORDER BY lov_descripcion ASC;";

        $datos = toba::db()->consultar($sql);
        return $datos;
    }

   static public function get_lov_factura_vencimiento_x_id($id_vencimiento) {
        if (isset($id_vencimiento)) {
            $sql = "SELECT fv.*, fv.nro_vencimiento||' ('|| TO_CHAR(fv.fecha_vencimiento, 'DD/MM/YYYY') || ')' as lov_descripcion
        FROM ad_facturas_vencimientos fv
        WHERE fv.id_vencimiento = " . quote($id_vencimiento) . ";";
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

    static public function get_lov_factura_vencimiento_x_nombre($nombre, $filtro = array()){
        if (isset($nombre)) {
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_vencimiento', $nombre);
            $trans_id = ctr_construir_sentencias::construir_translate_ilike('id_vencimiento', $nombre);
            $where = "($trans_nro OR $trans_id)";
        } else {
            $where = '1=1';
        }
    if (isset($filtro['excluir_vencimientos_devengados'])) {
      $where.= " AND NOT EXISTS (SELECT 1
                    FROM ad_comprobantes_gasto
                    WHERE id_factura = adfave.id_factura
                      AND id_vencimiento = adfave.id_vencimiento
                      AND aprobado = 'S'
                      AND anulado = 'N') ";
      unset($filtro['excluir_vencimientos_devengados']);
    }
        $where.= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'adfave', '1=1');

        $sql=   "SELECT adfave.*, adfave.nro_vencimiento||' ('|| TO_CHAR(adfave.fecha_vencimiento, 'DD/MM/YYYY') || ')' as lov_descripcion
                 FROM ad_facturas_vencimientos adfave
                 WHERE $where
                 ORDER BY lov_descripcion";

        $datos = toba::db()->consultar($sql);
        return $datos;
    }

    static public function get_lov_compromiso($id_compromiso) {
        if (isset($id_compromiso)) {
            $sql = "SELECT com.*, com.id_compromiso || ' - ' || com.fecha_comprobante || ' - ' || com.id_beneficiario || ' - ' || ben.nombre || ' - ' || cc.nro_cuenta_corriente || ' - ' || cc.descripcion || ' - ' || com.importe as lov_descripcion
    FROM AD_COMPROMISOS com
                     left join AD_BENEFICIARIOS ben on com.id_beneficiario = ben.id_beneficiario
                     left join KR_CUENTAS_CORRIENTE cc on com.id_cuenta_corriente = cc.id_cuenta_corriente
                     left join AD_COMPROBANTES_GASTO acg on acg.ID_COMPROMISO = com.id_compromiso
    WHERE com.id_compromiso = " . quote($id_compromiso) . ";";
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

    static public function get_lov_auxiliares($nombre, $filtro = array()){
      if (isset($nombre)) {
          $trans_cod = ctr_construir_sentencias::construir_translate_ilike('cod_auxiliar', $nombre);
          $trans_des = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
          $where = "($trans_cod OR $trans_des)";
      } else {
          $where = '1=1';
      }
      if (isset($filtro['devengado'])){ //Condicion para el formulario Devengado Gasto
        $where.= " AND pkg_pr_auxiliares.imputable(KRAUEX.cod_auxiliar) = 'S' and pkg_pr_auxiliares.activo(KRAUEX.cod_auxiliar) = 'S' ";
        unset($filtro['devengado']);
      }
      $where.= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'KRAUEX', '1=1');

      $sql=   "SELECT KRAUEX.*, KRAUEX.COD_AUXILIAR ||' - '|| KRAUEX.DESCRIPCION as lov_descripcion
               FROM KR_AUXILIARES_EXT KRAUEX
               WHERE $where
               ORDER BY lov_descripcion";
      $datos = toba::db()->consultar($sql);
      return $datos;
  }

   static public function get_lov_comprobante_ajuste ($nombre, $filtro = array()){
    $join = '';
    if (isset($nombre)) {
        $trans_id = ctr_construir_sentencias::construir_translate_ilike('id_comprobante_gasto', $nombre);
        $trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_comprobante', $nombre);
        $where = "($trans_id OR $trans_nro)";
    } else {
        $where = '1=1';
    }

    if (isset($filtro['devengado']) and isset($filtro['cod_unidad_administracion'])){ //condicion para formulario devengado
        $where.= " AND ADCOGA.ID_CUENTA_CORRIENTE = L_KRCTCT.ID_CUENTA_CORRIENTE AND
                   ADCOGA.ID_VENCIMIENTO = L_ADFAVE.ID_VENCIMIENTO (+) AND (ADCOGA.aprobado = 'S' AND ADCOGA.anulado = 'N' AND ADCOGA.cod_unidad_administracion = ".$filtro['cod_unidad_administracion'];

        if (isset($filtro['fecha_comprobante'])){
          $where.= " AND pkg_kr_ejercicios.retornar_ejercicio(ADCOGA.fecha_comprobante) = pkg_kr_ejercicios.retornar_ejercicio(to_date(substr('".$filtro['fecha_comprobante']."',1,10),'dd/mm/yyyy'))";
        }
        if (isset($filtro['id_cuenta_corriente'])){
            $where.=" AND ADCOGA.clase_comprobante = 'NOR' AND ADCOGA.id_cuenta_corriente = ".$filtro['id_cuenta_corriente'];
        }
        if (isset($filtro['cod_tipo_comprobante'])){
          $where.=" AND ADCOGA.cod_tipo_comprobante = '".$filtro['cod_tipo_comprobante']."'";
        }
        if (isset($filtro['cod_unidad_ejecutora'])){
          $where.= " AND (".$filtro['cod_unidad_ejecutora']." IS NULL OR adcoga.cod_unidad_ejecutora = ".$filtro['cod_unidad_ejecutora'].")";
        }
        $where.= ")";
        $join.= ", KR_CUENTAS_CORRIENTE L_KRCTCT, AD_FACTURAS_VENCIMIENTOS L_ADFAVE";

        unset($filtro['cod_unidad_administracion']);
        unset($filtro['fecha_comprobante']);
        unset($filtro['id_cuenta_corriente']);
        unset($filtro['cod_tipo_comprobante']);
        unset($filtro['cod_unidad_ejecutora']);
        unset($filtro['devengado']);
    }
      $where.= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'KRAUEX', '1=1');
      $sql=   "SELECT ADCOGA.*, ADCOGA.ID_COMPROBANTE_GASTO ||' - '|| ADCOGA.FECHA_COMPROBANTE || '-' || ADCOGA.ID_CUENTA_CORRIENTE || '-' || ADCOGA.IMPORTE as lov_descripcion
               FROM AD_COMPROBANTES_GASTO ADCOGA $join
               WHERE $where
               ORDER BY lov_descripcion";
      $datos = toba::db()->consultar($sql);
      return $datos;
  }


    //////////////////////////////////////////////
    //UNBOUNDS
    //////////////////////////////////////////////

    static public function get_origen_comprobante($cod_tipo_comprobante){
        if (!empty($cod_tipo_comprobante) && ($cod_tipo_comprobante != 'nopar') && ($cod_tipo_comprobante != '')) {
            try{
                $sql= "select origen_comprobante from AD_TIPOS_COMPROBANTE_GASTO where cod_tipo_comprobante = '".$cod_tipo_comprobante."';";
                $res= toba::db()->consultar_fila($sql);
                //$datos["ui_id_egreso"]= $res[0]["id_egreso"];
                //return $res[0]["id_egreso"];
                return $res['origen_comprobante'];

            }catch (toba_error_db $e_db) {
                toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            } catch (toba_error $e) {
                toba::notificacion()->error('Error '.$e->get_mensaje());
            }
        }
        else{

            return "";
        }
    }

    //UI_ORIGEN_COMPROBANTE_GASTO
   static public function get_ui_origen_comprobante($cod_tipo_comprobante){
        if (!empty($cod_tipo_comprobante) && ($cod_tipo_comprobante != 'nopar') && ($cod_tipo_comprobante != '')) {
            try{
                $sql= "select origen_comprobante ui_origen_comprobante from AD_TIPOS_COMPROBANTE_GASTO where cod_tipo_comprobante = '".$cod_tipo_comprobante."';";
                $res= toba::db()->consultar_fila($sql);
                //$datos["ui_id_egreso"]= $res[0]["id_egreso"];
                //return $res[0]["id_egreso"];
                return $res;

            }catch (toba_error_db $e_db) {
                toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            } catch (toba_error $e) {
                toba::notificacion()->error('Error '.$e->get_mensaje());
            }
        }
        else{

            return "";
        }
    }

    //CC_TIENE_BENEFICIARIOS
    static public function get_cc_tiene_beneficiarios($id_cuenta_corriente){
        if (!empty($id_cuenta_corriente)) {
            try{
                $sql= "SELECT count(1) cant
                       FROM ad_beneficiarios_pago
                       WHERE id_cuenta_corriente = ".$id_cuenta_corriente.";";
                $res= toba::db()->consultar($sql);
                return array("cc_tiene_beneficiarios"=>$res[0]["cant"]);
            }catch (toba_error_db $e_db) {
                toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            } catch (toba_error $e) {
                toba::notificacion()->error('Error '.$e->get_mensaje());
            }
        }
    }

    //ui_negativo
    static public function get_negativo ($cod_tipo_comprobante)
    {
      $sql = "SELECT negativo
                FROM ad_tipos_comprobante_gasto
               WHERE cod_tipo_comprobante = '".$cod_tipo_comprobante."'";
      $datos = toba::db()->consultar_fila($sql);
      return ['ui_negativo'=>$datos['negativo']];
    }

     // UI_IMPORTE_SALDO
     static public function get_importe_saldo($id_comprobante_gasto){
      $sql = "select saldo_devengado($id_comprobante_gasto) importe from dual;";
      $datos = toba::db()->consultar_fila($sql);
      return $datos['importe'];
     }


    // UI_IMPORTE_OP


    static public function get_importe_ordenado($id_comprobante_gasto){
               $sql = " SELECT nvl(sum(opcg.importe),0) as importe_ordenado
                               FROM ad_ordenes_pago_cg opcg, ad_ordenes_pago op
                               WHERE opcg.id_orden_pago = op.id_orden_pago
                                     and op.aprobada = 'S'
                                     and op.anulada = 'N'
                                     and opcg.id_comprobante_gasto = $id_comprobante_gasto;";
                $resultado = toba::db()->consultar_fila($sql);
                return $resultado['importe_ordenado'];
    }

   //UI_ID_EGRESO

   static public function get_egreso ($id_compromiso){
       if (!empty($id_compromiso)){
             try {
                $sql = " SELECT id_egreso
                         FROM ad_compromisos com, kr_transaccion tra, pr_egresos egr
                         WHERE tra.id_transaccion = com.id_transaccion
                               and tra.id_transaccion = egr.id_transaccion
                               and com.id_compromiso = ".quote($id_compromiso).";";
                $resultado = toba::db()->consultar($sql);
                return array ("ui_id_egreso"=>$resultado[0]["id_egreso"]);
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
         else return null;
    }

    //Para cargar del reimputar
   static public function get_reimputacion_inicializacion_param (){
         $sql = "select pkg_kr_general.valor_parametro('REI_ID_ENTIDAD') ui_id_entidad,
                pkg_kr_general.valor_parametro('REI_ID_PROGRAMA') ui_id_programa,
                pkg_kr_general.valor_parametro('REI_COD_FUENTE_FINANCIAMIENTO') ui_cod_fuente_financiera,
                pkg_kr_general.valor_parametro('REI_COD_RECURSO') cod_recurso
                from dual;";
        $datos = toba::db()->consultar_fila($sql);
        return $datos;
    }

    //UI_SIN_COTROL_PRES

     static public function get_sin_control_pres ($cod_tipo_comprobante){
       if (!empty($cod_tipo_comprobante)){
          try {
                $sql = " BEGIN :resultado := pkg_ad_comprobantes_gasto.devengado_sin_control (:cod_tipo_comprobante); END;";
                $parametros = array ( array(  'nombre' => 'cod_tipo_comprobante',
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 32,
                                                'valor' => $cod_tipo_comprobante),

                                      array  (  'nombre' => 'resultado',
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 4000,
                                                'valor' => ''),
                                );
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                return array ("ui_sin_control_pres"=>$resultado[1]['valor']);

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
         else return null;
     }
    //UI_ID_EJERCICIO
   static public function get_ui_id_ejercicio ($fecha_comprobante){
          if (!empty($fecha_comprobante)){
          //  $sql = "SELECT pkg_kr_ejercicios.retornar_ejercicio (to_date(substr(".$fecha_comprobante.",1,10),'YYYY-MM-DD')) AS EJERCICIO FROM DUAL;";

          try {
                $sql = "BEGIN :resultado := pkg_kr_ejercicios.retornar_ejercicio(to_date(substr(:fecha_comprobante, 1,10), 'yyyy-mm-dd')); END;";
                $parametros = array ( array(  'nombre' => 'fecha_comprobante',
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 32,
                                                'valor' => $fecha_comprobante),

                                      array  (  'nombre' => 'resultado',
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 4000,
                                                'valor' => ''),
                                );
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                return array("ui_id_ejercicio"=>$resultado[1]['valor']);

            } catch (toba_error_db $e_db) {
               toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
               toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
               toba::db()->abortar_transaccion();
            } catch (toba_error $e) {
               toba::notificacion()->error('Error '.$e->get_mensaje());
               toba::logger()->error('Error '.$e->get_mensaje());
               toba::db()->abortar_transaccion();
            }
            $datos= toba::db()->consultar_fila($sql);
            return $datos['ejercicio'];
        } else return null;
    }
    //UI_IMPORTE_TOTAL
   static public function calcular_importe_total ($id_comprobante_gasto){
                $sql= "select nvl(sum(importe),0) as importe
                       from ad_comprobantes_gas_imp
                       where id_comprobante_gasto = ".$id_comprobante_gasto.";";
                $res= toba::db()->consultar_fila($sql);
                return $res['importe'];
    }

  static public function get_tipo_comprobante_gasto ($codigo)
  {
        if (isset($codigo)){
            $sql = "SELECT ADTICOGA.*
        FROM AD_TIPOS_COMPROBANTE_GASTO ADTICOGA
        WHERE ADTICOGA.COD_TIPO_COMPROBANTE = " . quote($codigo) . ";";
            $datos = toba::db()->consultar_fila($sql);
            return $datos;
        }
        else {
            return null;
        }
    }

  static public function get_lov_tipo_comprobante_gasto($codigo)
  {
        if (isset($codigo)) {
            $sql = "SELECT ADTICOGA.*, ADTICOGA.COD_TIPO_COMPROBANTE || ' - ' || ADTICOGA.DESCRIPCION as lov_descripcion
        FROM AD_TIPOS_COMPROBANTE_GASTO ADTICOGA
        WHERE ADTICOGA.COD_TIPO_COMPROBANTE = " . quote($codigo) . ";";
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

  static public function get_lov_tipos_comprobante_gasto_x_nombre($nombre, $filtro)
  {
        if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('ADTICOGA.COD_TIPO_COMPROBANTE', $nombre);
            $trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('ADTICOGA.DESCRIPCION', $nombre);
            $where = "($trans_cod OR $trans_descricpcion)";
        } else {
            $where = "1=1";
        }

    if (isset($filtro['origenes_comprobante']) && !empty($filtro['origenes_comprobante'])) {
      $where .= " AND ADTICOGA.ORIGEN_COMPROBANTE IN (" . implode(', ', $filtro['origenes_comprobante']) . ") ";
      unset($filtro['origenes_comprobante']);
    }

    $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'ADTICOGA', '1=1');
        $sql = "SELECT ADTICOGA.*, ADTICOGA.COD_TIPO_COMPROBANTE || ' - ' || ADTICOGA.DESCRIPCION as lov_descripcion
                FROM AD_TIPOS_COMPROBANTE_GASTO ADTICOGA
                WHERE  $where
                ORDER BY lov_descripcion;";
        toba::logger()->debug('dao_devengados_gasto::get_lov_tipos_comprobante_gasto_x_nombre: '. $sql);
        $datos = toba::db()->consultar($sql);
        return $datos;
    }

    static public function get_id_transaccion($id_comprobante_gasto){
      if (!empty($id_comprobante_gasto)){
        $sql = "select id_transaccion
          from ad_comprobantes_gasto
          where id_comprobante_gasto = $id_comprobante_gasto";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['id_transaccion'];
      }
    }

    static public function get_orden_pago ($id_comprobante_gasto){
      if (!is_null($id_comprobante_gasto)){
        $sql = "SELECT cg.ID_ORDEN_PAGO
            FROM ad_ordenes_pago_cg cg, ad_ordenes_pago op
           WHERE ID_COMPROBANTE_GASTO = $id_comprobante_gasto
                 and cg.id_orden_pago = op.id_orden_pago
                 and op.ANULADA = 'N'";
        $datos = toba::db()->consultar_fila($sql);
        if (isset($datos['id_orden_pago']) && !empty($datos['id_orden_pago'])){
          return $datos['id_orden_pago'];
        }
      }else{
        return null;
      }
    }


    static public function get_ordenes_cg ($id_comprobante_gasto){
    	if (!is_null($id_comprobante_gasto)){
	    	$sql = "SELECT adpcg.*, to_char(adop.fecha_orden_pago,'DD/MM/YYYY') fecha_orden_pago, adop.nro_orden_pago
					  FROM ad_ordenes_pago_cg adpcg, ad_ordenes_pago adop
					 WHERE adpcg.id_orden_pago = adop.id_orden_pago
					   AND adpcg.id_comprobante_gasto = ".quote($id_comprobante_gasto);
	    	return toba::db()->consultar($sql);
    	}else {
	    	return array();
	    }
    }

    static public function get_saldos_imputacion ($id_comprobante_gasto)
    {
      $sql = "select cgd.importe, cgd.cod_partida, p.descripcion det_partida,
             e.cod_entidad cod_entidad, e.descripcion det_entidad,
             pg.cod_programa cod_programa, pg.descripcion det_programa,
             cgi.cod_fuente_financiera, cgi.cod_recurso,
             trim(to_char(cgi.importe, '$999,999,999,990.00')) as importe_format,
              pkg_pr_totales.SALDO_ACUMULADO_EGRESO_NIVEL(cg.COD_UNIDAD_ADMINISTRACION, pkg_kr_ejercicios.RETORNAR_EJERCICIO(cg.fecha_comprobante) ,
                                                   cgi.id_entidad, cgi.id_programa, cgd.cod_partida,
                                                   cgi.cod_fuente_financiera, cgi.cod_recurso, 'PRES', cg.FECHA_COMPROBANTE ,
                                                   pkg_pr_partidas.CONTROL_NIVEL(cgd.cod_partida)) saldo,
             trim(to_char(pkg_pr_totales.SALDO_ACUMULADO_EGRESO_NIVEL(cg.COD_UNIDAD_ADMINISTRACION, pkg_kr_ejercicios.RETORNAR_EJERCICIO(cg.fecha_comprobante) ,
                                                         cgi.id_entidad, cgi.id_programa, cgd.cod_partida,
                                                         cgi.cod_fuente_financiera, cgi.cod_recurso, 'PRES', cg.FECHA_COMPROBANTE ,
                                                         pkg_pr_partidas.CONTROL_NIVEL(cgd.cod_partida)), '$999,999,999,990.00')) as saldo_format,
             pkg_pr_partidas.CONTROL_NIVEL(cgd.cod_partida) nivel
      from ad_comprobantes_gasto cg, ad_comprobantes_gas_det cgd, ad_comprobantes_gas_imp cgi, pr_partidas p, pr_entidades e, pr_programas pg
      where cgd.id_comprobante_gasto = cg.id_comprobante_gasto
      and   cgi.id_comprobante_gasto = cgd.id_comprobante_gasto and cgi.id_detalle = cgd.id_detalle
      and   p.cod_partida = cgd.cod_partida
      and   e.id_entidad = cgi.id_entidad
      and   pg.id_programa = cgi.id_programa
      and   aprobado = 'N' and anulado = 'N'
      and   cg.id_comprobante_gasto = ".$id_comprobante_gasto;
      $datos = toba::db()->consultar($sql);
      return $datos;
    }


    public static function importar_detalle ($id_comprobante_gasto)
  {
    $sql = "BEGIN :resultado := pkg_ad_comprobantes_gasto.importar_detalle_devengado(:id_comprobante_gasto); END;";
    $parametros =
      [
        [ 'nombre' => 'resultado',
          'tipo_dato' => PDO::PARAM_STR,
          'longitud' => 4000,
          'valor' => ''
        ],
        ['nombre' => 'id_comprobante_gasto',
         'tipo_dato' => PDO::PARAM_STR,
         'longitud' => 32,
         'valor' => $id_comprobante_gasto],
      ];
    return ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
  }
}
?>
