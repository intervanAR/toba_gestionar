<?php
class dao_compromisos_gastos {
    public static function get_compromisos_gastos($filtro=array(),$orden = array()){
    	$desde= null;
		$hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}
		
        $where = self::armar_where($filtro);
        
        $sql = "SELECT c.*,
				decode(c.aprobado,'S','Si','No') aprobado_format,
		        decode(c.anulado,'S','Si','No') anulado_format,
		        to_char(c.fecha_comprobante, 'dd/mm/yyyy') fecha_comprobante_format, trim(to_char(c.importe, '$999,999,999,990.00')) importe_format, 
                tp.descripcion tipo_compromiso_desc, tp.tipo_cuenta_corriente,
                cc.nro_cuenta_corriente nro_cuenta_corriente, cc.descripcion desc_cuenta_corriente, ua.descripcion unidad_administracion                
                FROM AD_COMPROMISOS c, AD_TIPOS_COMPROMISO tp, KR_CUENTAS_CORRIENTE cc, KR_UNIDADES_ADMINISTRACION ua
                WHERE tp.cod_tipo_compromiso= c.cod_tipo_compromiso 
                AND cc.id_cuenta_corriente (+)= c.id_cuenta_corriente
                AND ua.cod_unidad_administracion= c.cod_unidad_administracion                
                AND $where
                ORDER BY c.id_compromiso DESC";
        
        $sql= dao_varios::paginador($sql, null, $desde, $hasta, null, $orden);
        $datos = toba::db()->consultar($sql);
	   
        foreach ($datos as $clave => $dato) {
			$datos[$clave]['des_clase_comprobante'] = self::get_descripcion_clase_comprobante($datos[$clave]['clase_comprobante']);
	    }
	    return $datos;
    }
	static public function armar_where ($filtro = array())
	{
		$where = " 1=1 ";
        $sql1 = "SELECT PKG_KR_USUARIOS.in_ua_tiene_acceso(upper('".toba::usuario()->get_id()."')) unidades
                   FROM DUAL";
        $res = toba::db()->consultar_fila($sql1);
        $where .= " AND c.COD_UNIDAD_ADMINISTRACION in ".$res['unidades'];
        
		if (isset($filtro['ids_comprobantes'])) {
			$where .= "AND C.id_compromiso IN (" . $filtro['ids_comprobantes'] . ") ";
			unset($filtro['ids_comprobantes']);
		}
        $where.= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'c', '1=1');
		return $where;
	}
	
	static public function get_cantidad ($filtro = array())
	{
		$where = self::armar_where($filtro);
		$sql = "select count(*) cantidad
				 FROM AD_COMPROMISOS c, AD_TIPOS_COMPROMISO tp, KR_CUENTAS_CORRIENTE cc, KR_UNIDADES_ADMINISTRACION ua
                WHERE tp.cod_tipo_compromiso= c.cod_tipo_compromiso 
                AND cc.id_cuenta_corriente (+)= c.id_cuenta_corriente
                AND ua.cod_unidad_administracion= c.cod_unidad_administracion                 
                AND $where ";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cantidad'];
	}
	
    static public function get_compromiso_gasto_x_id($id_compromiso){
        if (isset($id_compromiso)) {
            $sql = "SELECT c.*
                        FROM AD_COMPROMISOS c
                        WHERE c.id_compromiso= ".$id_compromiso;

            $datos = toba::db()->consultar_fila($sql);
            
            return $datos;
        }else{
            return array();
        }            
    }
    
    static public function get_clase_comprobante($filtro=array())
    	{
	    $where = ctr_construir_sentencias::get_where_filtro($filtro, 'cg', '1=1');
	    $sql = "SELECT cg.*
		    FROM cg_ref_codes cg
		    WHERE $where";
            
	    $datos = toba::db()->consultar($sql);            
	    /*$datos = array( array('clase_comprobante' => 'NOR', 'descripcion' => 'Normal'),
			    array('clase_comprobante' => 'AJU', 'descripcion' => 'Ajuste'),
			    array('clase_comprobante' => 'REI', 'descripcion' => 'Reimputacion')
			    );*/
	    return $datos;
	}
		    
	static public function get_descripcion_clase_comprobante($clase_comprobante)
    	{
            $filtro= array('rv_domain'=> 'TIPO_COMPROMISO', 'rv_low_value'=> $clase_comprobante);
	    $datos = self::get_clase_comprobante($filtro);
	    foreach ($datos as $clase) {
		if ($clase['rv_low_value'] == $clase_comprobante) {
                    return $clase['rv_meaning'];
                }
                
                /*if ($clase['clase_comprobante'] == $clase_comprobante) {
		    return $clase['descripcion'];
		}*/
		
	    }
	    return '';
	}
	
	static public function get_tipos_compromiso($filtro= array()){
        
        $where= "1=1";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'tc', '1=1');
        
        $sql = "SELECT tc.*, 
                   DECODE (tc.negativo, 'S', 'Si', 'No') negativo_format,
                   DECODE (tc.automatico, 'S', 'Si', 'No') automatico_format,
                   DECODE (tc.preventivo_previo,
                           'S', 'Si',
                           'No'
                          ) preventivo_previo_format,
                   DECODE (tc.genera_preventivo,
                           'S', 'Si',
                           'No'
                          ) genera_preventivo_format,
                   (SELECT rv_meaning
                      FROM cg_ref_codes
                     WHERE rv_domain =
                                  'KR_TIPO_CUENTA_CORRIENTE'
                       AND rv_low_value = tc.tipo_cuenta_corriente)
                                                             tipo_cuenta_corriente_format,
                   (SELECT    cod_tipo_preventivo
                           || ' - '
                           || descripcion
                      FROM ad_tipos_preventivo
                     WHERE cod_tipo_preventivo = tc.cod_tipo_preventivo)
                                                                   tipo_preventivo_format,
                   (SELECT    cod_tipo_transaccion
                           || ' - '
                           || descripcion
                      FROM kr_tipos_transaccion
                     WHERE cod_tipo_transaccion = tc.cod_tipo_transaccion)
                                                                  tipo_transaccion_format,
                   (SELECT    cod_tipo_transaccion
                           || ' - '
                           || descripcion
                      FROM kr_tipos_transaccion
                     WHERE cod_tipo_transaccion = tc.cod_tipo_transaccion_ajusta)
                                                              tipo_transaccion_aju_format,
                   (SELECT    cod_tipo_transaccion
                           || ' - '
                           || descripcion
                      FROM kr_tipos_transaccion
                     WHERE cod_tipo_transaccion = tc.cod_tipo_transaccion_reimputa)
                                                  tipo_transaccion_rei_format
                FROM AD_TIPOS_COMPROMISO tc
                WHERE  $where";
        
        $datos = toba::db()->consultar($sql);
        
        return $datos;
    }
    
    static public function get_lov_tipos_compromiso_x_cod($cod_tipo_compromiso) {
        
        if (isset($cod_tipo_compromiso)) {
            $sql = "SELECT tc.cod_tipo_compromiso||' - '||tc.descripcion lov_descripcion
                    FROM AD_TIPOS_COMPROMISO tc
                    WHERE cod_tipo_compromiso = ".quote($cod_tipo_compromiso) .";";
            
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
        
        
    static public function get_lov_tipos_compromiso_x_nombre($nombre, $filtro= array()) {
        if (isset($nombre)) {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('cod_tipo_compromiso', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
            $where = "($trans_codigo OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }

        $where .= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'tc', '1=1');

        $sql = "SELECT  tc.*, 
                        tc.cod_tipo_compromiso || ' - ' || tc.descripcion as lov_descripcion
                FROM AD_TIPOS_COMPROMISO tc
                WHERE $where
                ORDER BY lov_descripcion ASC;";

        $datos = toba::db()->consultar($sql);

        return $datos;
    } 
        
    /////////////////////////////////////////////
    //LOV's ci_compromiso_gastos
    /////////////////////////////////////////////
    
    static public function get_lov_compromiso_x_id($id_compromiso){        
        
        if (isset($id_compromiso)) {
            $sql = "SELECT c.*, c.nro_compromiso || ' (ID '||c.id_compromiso|| ') - ' ||to_char(c.fecha_comprobante, 'dd/mm/yyyy')|| ' (' || trim(to_char(c.importe, '$999,999,999,990.00')) || ')' lov_descripcion
                    FROM AD_COMPROMISOS c
                    WHERE id_compromiso = ".quote($id_compromiso) .";";
            
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
        
	static public function get_lov_compromisos_x_nombre($nombre, $filtro)
	{
		if (isset($nombre)) {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('id_compromiso', $nombre);
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_compromiso', $nombre);
            $where = "($trans_codigo OR $trans_nro)";
        } else {
            $where = '1=1';
        }
        if (isset($filtro['fecha_comprobante'])) {
            $where .= " AND pkg_kr_ejercicios.retornar_ejercicio(c.fecha_comprobante) = 
                        pkg_kr_ejercicios.retornar_ejercicio( to_date('".$filtro['fecha_comprobante']."','dd/mm/yyyy'))";
			unset($filtro['fecha_comprobante']);
        }
		
		if (isset($filtro['id_proveedor']) && isset($filtro['cod_unidad_administracion'])) {
			$where .= " AND EXISTS (SELECT 1 
									FROM KR_CUENTAS_CORRIENTE KCC 
									WHERE KCC.ID_PROVEEDOR = ".$filtro['id_proveedor']."
									AND KCC.ORIGEN_CUENTA_CORRIENTE = 'PRO' 
									AND KCC.COD_UNIDAD_ADMINISTRACION = ".$filtro['cod_unidad_administracion']."
									AND c.ID_CUENTA_CORRIENTE = KCC.ID_CUENTA_CORRIENTE) ";
			unset($filtro['id_proveedor']);
		}
        //Flag para formulario Devengados Gastos
        if (isset($filtro['devengado'])){
            $where.= " and saldo_compromiso(c.id_compromiso) > 0 ";
            unset($filtro['devengado']);
        }
        
        $where .= ' AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'c', '1=1');
        
        $sql = "SELECT  c.*, 
						c.nro_compromiso || ' (ID '||c.id_compromiso|| ') - ' ||to_char(c.fecha_comprobante, 'dd/mm/yyyy')|| ' (' || trim(to_char(c.importe, '$999,999,999,990.00')) || ')' as lov_descripcion
                FROM AD_COMPROMISOS c, 
					 KR_EXPEDIENTES L_KREX, 
					 KR_CUENTAS_CORRIENTE L_KRCTCT, 
					 AD_BENEFICIARIOS L_ADBE
                WHERE c.ID_EXPEDIENTE = L_KREX.ID_EXPEDIENTE (+) 
				AND c.ID_CUENTA_CORRIENTE = L_KRCTCT.ID_CUENTA_CORRIENTE 
				AND c.ID_BENEFICIARIO = L_ADBE.ID_BENEFICIARIO (+) 
				AND $where
                ORDER BY lov_descripcion ASC;";
        
        $datos = toba::db()->consultar($sql);
        
        return $datos;
    }
    
    
    //////////////////////////////////////////////
    //UNBOUNDS
    //////////////////////////////////////////////
        
    //UD_ID_EJERCICIO

    public static function get_ui_ejercicio($fecha_comprobante){

        try {
            if (isset($fecha_comprobante)&&(!empty($fecha_comprobante))) {
               // toba::db()->abrir_transaccion();
                $sql = "BEGIN :resultado := pkg_kr_ejercicios.retornar_ejercicio(to_date(substr(:fecha_comprobante,1,10),'yyyy-mm-dd')); END;";		
                $parametros = array ( array(  'nombre' => 'fecha_comprobante', 
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 32,
                                                'valor' => $fecha_comprobante),
                                        array(  'nombre' => 'resultado', 
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 4000,
                                                'valor' => ''),
                                );
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);                

                //$datos["ui_id_ejercicio"]= $resultado[1]['valor'];

           //     toba::db()->cerrar_transaccion();

                return array("ui_id_ejercicio"=>$resultado[1]['valor']);
                /*
                return array("ui_id_ejercicio"=>$resultado[1]['valor'],
                             "ui_id_egreso"=>"",
                             "ui_sin_control_pres"=>"",
                             "cc_tiene_beneficiarios"=>"");    */        

                /*
                if ($resultado[2]['valor'] != 'OK') {
                        toba::notificacion()->error($resultado[2]['valor']);
                        toba::logger()->error($resultado[2]['valor']);
                        toba::db()->abortar_transaccion();
                } else {
                        toba::notificacion()->info('se recupero exitosamente.');
                        toba::db()->cerrar_transaccion();
                        toba::notificacion()->info();
                }*/
            }else{
                return array("ui_id_ejercicio"=>'');
            }                
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
         //   toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
          //  toba::db()->abortar_transaccion();
        }
    }
    
    
    public static function get_ui_ejercicio_ajax($fecha_comprobante){

        try {
            if (isset($fecha_comprobante)&&(!empty($fecha_comprobante))) {
                if (substr($fecha_comprobante,4,1) == '-'){
                    $sql = "BEGIN :resultado := pkg_kr_ejercicios.retornar_ejercicio(to_date(substr(:fecha_comprobante,1,10),'yyyy-mm-dd')); END;";		
                }else{    
                    $sql = "BEGIN :resultado := pkg_kr_ejercicios.retornar_ejercicio(to_date(substr(:fecha_comprobante,1,10),'dd/mm/yyyy')); END;";		
                }
                
                $parametros = array ( array(  'nombre' => 'fecha_comprobante', 
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 32,
                                                'valor' => $fecha_comprobante),
                                        array(  'nombre' => 'resultado', 
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 4000,
                                                'valor' => ''),
                                );
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);                

                //$datos["ui_id_ejercicio"]= $resultado[1]['valor'];

        //        toba::db()->cerrar_transaccion();

                return array("ui_id_ejercicio"=>$resultado[1]['valor']);
                /*
                return array("ui_id_ejercicio"=>$resultado[1]['valor'],
                             "ui_id_egreso"=>"",
                             "ui_sin_control_pres"=>"",
                             "cc_tiene_beneficiarios"=>"");    */        

                /*
                if ($resultado[2]['valor'] != 'OK') {
                        toba::notificacion()->error($resultado[2]['valor']);
                        toba::logger()->error($resultado[2]['valor']);
                        toba::db()->abortar_transaccion();
                } else {
                        toba::notificacion()->info('se recupero exitosamente.');
                        toba::db()->cerrar_transaccion();
                        toba::notificacion()->info();
                }*/
            }else{
                return array("ui_id_ejercicio"=>'');
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
        
        
    //UI_ID_EGRESO

    public static function get_ui_egreso($id_preventivo){
        //$id_prev= $datos['id_preventivo'];

        if (!empty($id_preventivo)) {
            try{
                $sql= "select id_egreso
                   from ad_preventivos prv, kr_transaccion tra, pr_egresos egr
                   where tra.id_transaccion = prv.id_transaccion
                   and tra.id_transaccion = egr.id_transaccion
                   and prv.id_preventivo = ".$id_preventivo.";";


                $res= toba::db()->consultar_fila($sql);

                //$datos["ui_id_egreso"]= $res[0]["id_egreso"];
                
                //return $res[0]["id_egreso"];
                return array("ui_id_egreso"=>$res["id_egreso"]);

            }catch (toba_error_db $e_db) {
                toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());                    
            } catch (toba_error $e) {
                toba::notificacion()->error('Error '.$e->get_mensaje());
            }
        }
        else{
            return array("ui_id_egreso"=>"");
        }
    }
    
    //UI_SIN_CONTROL_PRES
    
    public static function get_ui_sin_control_pres($cod_tipo_compromiso){
        try{                  
            if (isset($cod_tipo_compromiso)&&(!empty($cod_tipo_compromiso))) {
        //        toba::db()->abrir_transaccion();
                $sql = "BEGIN :resultado := pkg_ad_comprobantes_gasto.compromiso_sin_control(:cod_tipo_compromiso); END;";		
                $parametros = array ( array(  'nombre' => 'cod_tipo_compromiso', 
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 32,
                                                'valor' => $cod_tipo_compromiso),
                                        array(  'nombre' => 'resultado', 
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 4000,
                                                'valor' => ''),
                                );
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

                //$datos["ui_sin_control_pres"]= $resultado[1]['valor'];

           //     toba::db()->cerrar_transaccion();

                //return $resultado[1]['valor'];

                return array("ui_sin_control_pres"=>$resultado[1]['valor']);
            }else{
                return array("ui_sin_control_pres"=>'');
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
    
    //CC_TIENE_BENEFICIARIOS
    
    public static function get_cc_tiene_beneficiarios($id_cuenta_corriente){

        if ((isset($id_cuenta_corriente))&&(!empty($id_cuenta_corriente))) {
            try{
                $sql= "select count(1) cant
                       from ad_beneficiarios_pago 
                       where id_cuenta_corriente = ".$id_cuenta_corriente.";";

                $res= toba::db()->consultar($sql);

                //$datos["cc_tiene_beneficiarios"]= $res[0]["cant"];
                
                //return $res[0]["cant"];
                
                return array("cc_tiene_beneficiarios"=>$res[0]["cant"]);
            }catch (toba_error_db $e_db) {
                toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());                    
            } catch (toba_error $e) {
                toba::notificacion()->error('Error '.$e->get_mensaje());
            }
        }else{
            return array("cc_tiene_beneficiarios"=>'');
        }
    }

    //////////////////////////////////////////////
    //////////////////////////////////////////////
    
    /////////////////////////////////////////////
    /////AUXILIARES
    /////////////////////////////////////////////
    
    static public function get_preventivo_previo($cod_tipo_compromiso){
        $sql = "SELECT tp.preventivo_previo
                    FROM AD_TIPOS_COMPROMISO tp
                    WHERE tp.cod_tipo_compromiso = ".quote($cod_tipo_compromiso ) .";";
            
        $datos = toba::db()->consultar_fila($sql);
        
        return $datos["preventivo_previo"];
    }
    
    static public function get_afectacion_especifica($cod_fuente_financiera){
        try{        
            if (isset($cod_fuente_financiera)&&(!empty($cod_fuente_financiera))) {
          //      toba::db()->abrir_transaccion();
                $sql = "BEGIN :resultado := pkg_pr_fuentes.afectacion_especifica(:cod_fuente_financiera); END;";		
                $parametros = array ( array(  'nombre' => 'cod_fuente_financiera', 
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 32,
                                                'valor' => $cod_fuente_financiera),
                                        array(  'nombre' => 'resultado', 
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 4000,
                                                'valor' => ''),
                                );
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

       //         toba::db()->cerrar_transaccion();

                return $resultado[1]['valor'];
            }else{
                return '';
            }

        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
      //      toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
      //      toba::db()->abortar_transaccion();
        }       
    }
	
	static public function get_importe( $id_compromiso){
        if (isset($id_compromiso)){
            $sql = "SELECT SUM(ci.importe) importe
                    FROM AD_COMPROMISOS_DET cd, AD_COMPROMISOS_IMP ci
                    WHERE cd.id_compromiso = ".$id_compromiso."
					AND ci.id_compromiso= cd.id_compromiso
					AND ci.id_detalle= cd.id_detalle;";
            
            $datos = toba::db()->consultar_fila($sql);
          
			return $datos['importe'];
        }else{
            return 0;
        }
    }
    
    
    static public function get_importe_saldo($id_compromiso, $aprobado, $anulado){
        if (($aprobado == 'S')&&($anulado == 'N')&&(isset($id_compromiso))){
            try{                  
         //       toba::db()->abrir_transaccion();
                $sql = "BEGIN :resultado := saldo_compromiso(:id_compromiso); END;";		
                $parametros = array ( array(  'nombre' => 'id_compromiso', 
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 32,
                                                'valor' => $id_compromiso),
                                        array(  'nombre' => 'resultado', 
                                                'tipo_dato' => PDO::PARAM_STR,
                                                'longitud' => 4000,
                                                'valor' => ''),
                                );
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

                //$datos["ui_sin_control_pres"]= $resultado[1]['valor'];

      //          toba::db()->cerrar_transaccion();

                return $resultado[1]['valor'];

                //return array("importe_saldo" => $resultado[1]['valor']);

            } catch (toba_error_db $e_db) {
                toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
                toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
      //          toba::db()->abortar_transaccion();
            } catch (toba_error $e) {
                toba::notificacion()->error('Error '.$e->get_mensaje());
                toba::logger()->error('Error '.$e->get_mensaje());
       //         toba::db()->abortar_transaccion();
            }
        }else{
            return 0; //array("importe_saldo" => '');
        }
    }
    
    static public function get_importe_ajustado($id_transaccion, $aprobado, $anulado){
        if (($aprobado == 'S')&&($anulado == 'N')){
            $sql = "select sum(pkg_pr_totales.total_transaccion_egreso(id_egreso, null, null, null, null, null)) importe
                    from pr_egresos 
                    where id_transaccion = ".$id_transaccion.";";

             if (empty($id_egreso)){
                $sql = str_replace("p_id_egreso", "null", $sql, $count);
            }else{
                $sql = str_replace("p_id_egreso",$id_egreso, $sql, $count);
            }
            
            $datos = toba::db()->consultar_fila($sql);

            return $datos["importe"];
        }else{
            return 0;
        }
    }
    
     public static function get_observaciones_preventivo($id_preventivo){
         if ((isset($id_preventivo))&&(!empty($id_preventivo))){
            $sql = "select observaciones
                    from ad_preventivos
                    where id_preventivo = ".$id_preventivo.";";

            $datos = toba::db()->consultar_fila($sql);

            return $datos["observaciones"];
        }else{
            return '';
        }
     }

    static public function aprobar_compromiso_gasto($id_compromiso){

        $sql = "SELECT count(1) cant
                FROM AD_COMPROMISOS_IMP
                WHERE id_compromiso = ".$id_compromiso.";";

        $datos = toba::db()->consultar_fila($sql);

        if ($datos["cant"] == '0') {
            return 'Se debe cargar una imputación para el compromiso.';
        }

        $sql = "SELECT count(1) cant
                FROM AD_COMPROMISOS_DET
                WHERE id_compromiso = ".$id_compromiso.";";

        $datos = toba::db()->consultar_fila($sql);

        if ($datos["cant"] == '0') {
            return 'Se debe cargar un detalle para el compromiso.';
        }
        
        try{
            toba::db()->abrir_transaccion();
            $sql = "BEGIN :resultado := pkg_kr_transacciones.confirmar_compromiso(:id_compromiso); END;";		
            $parametros = array ( array(  'nombre' => 'id_compromiso', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 32,
                                            'valor' => $id_compromiso),
                                    array(  'nombre' => 'resultado', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 4000,
                                            'valor' => ''),
                            );
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

            toba::db()->cerrar_transaccion();

            //return $resultado[1]['valor'];

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
    
    
    static public function anular_compromiso_gasto($id_compromiso, $fecha, $con_transaccion = true){

        try{
        	if ($con_transaccion)
            	toba::db()->abrir_transaccion();
            	
            $sql = "BEGIN :resultado := pkg_kr_transacciones.anular_compromiso(:id_compromiso, to_date(substr(:fecha,1,10),'yyyy-mm-dd')); END;";		
            $parametros = array ( array(  'nombre' => 'id_compromiso', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 32,
                                            'valor' => $id_compromiso),
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

            if ($con_transaccion){
            	if ($resultado[2]['valor'] == 'OK'){
            		toba::db()->cerrar_transaccion();	
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
    
    public static function reimputar_compromiso_gasto($id_compromiso, $fecha){
        
        $sql = "select pkg_general.valor_parametro('REI_ID_ENTIDAD') id_entidad, 
                pkg_general.valor_parametro('REI_ID_PROGRAMA') id_programa, 
                pkg_general.valor_parametro('REI_COD_FUENTE_FINANCIAMIENTO') cod_fuente, 
                pkg_general.valor_parametro('REI_COD_RECURSO') cod_recurso
                from dual;";

        $datos = toba::db()->consultar_fila($sql);
        
        if ((!isset($datos['id_entidad']))&&(empty($datos['id_entidad']))){
            return 'No estan seteados los parametros';
        }
        
        try{
            toba::db()->abrir_transaccion();
            $sql = "BEGIN :resultado := pkg_kr_transacciones.reimputar_compro_compromiso(:id_compromiso,
                                                                to_date(substr(:fecha,1,10),'yyyy-mm-dd'),
                                                                :id_entidad,
                                                                :id_programa,
                                                                :cod_fuente,
                                                                :cod_recurso,
                                                                :id_compromiso_rei
                                                                );  END;";		
            $parametros = array ( array(  'nombre' => 'id_compromiso', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 32,
                                            'valor' => $id_compromiso),
                                  array(  'nombre' => 'fecha', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 32,
                                            'valor' => $fecha),
                                  array(  'nombre' => 'id_entidad', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 32,
                                            'valor' => $datos['id_entidad']),
                                  array(  'nombre' => 'id_programa', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 32,
                                            'valor' => $datos['id_programa']),
                                  array(  'nombre' => 'cod_fuente', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 32,
                                            'valor' => $datos['cod_fuente']),
                                  array(  'nombre' => 'cod_recurso', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 32,
                                            'valor' => $datos['cod_recurso']),
                                  array(  'nombre' => 'id_compromiso_rei', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 32,
                                            'valor' => ''),
                                  array(  'nombre' => 'resultado', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 4000,
                                            'valor' => ''),
                            );
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

            toba::db()->cerrar_transaccion();

            //return $resultado[1]['valor'];

            return $resultado[7]['valor'];

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
    
    public static function get_unidad_administracion_default(){

        $sql = "select usu.cod_unidad_administracion cod_unidad_administracion
                from kr_usuarios usu, kr_unidades_administracion adm
                where usu.cod_unidad_administracion = adm.cod_unidad_administracion and usu.usuario = upper('".toba::usuario()->get_id()."');";

        $datos = toba::db()->consultar_fila($sql);
        
        return $datos['cod_unidad_administracion'];
    }
	
	static public function get_datos_extras_compromiso_gasto_x_id($id_compromiso){
        if (isset($id_compromiso)) {
            $sql = "SELECT	c.anulado,
							c.aprobado
                        FROM AD_COMPROMISOS c
                        WHERE c.id_compromiso= ".$id_compromiso;

            $datos = toba::db()->consultar_fila($sql);
            
            return $datos;
        }else{
            return array();
        }            
    }
    
    public static function get_negativo_tipo_compromiso($cod_tipo_compromiso){
        if (isset($cod_tipo_compromiso)) {
            $sql = "SELECT tc.negativo
                        FROM AD_TIPOS_COMPROMISO tc
                        WHERE tc.cod_tipo_compromiso= '".$cod_tipo_compromiso."'";

            $datos = toba::db()->consultar_fila($sql);
            
            return $datos['negativo'];
        }else{
            return '';
        }   
    }
    
    public static function get_sysdate(){
        $sql = "SELECT to_char(sysdate, 'dd/mm/yyyy') fecha
                        FROM dual;";

        $datos = toba::db()->consultar_fila($sql);

        return $datos['fecha'];
    }
	
	public static function get_importe_detalle($id_compromiso, $id_detalle){
		if (isset($id_compromiso) && isset($id_detalle)) {
			$sql= "select sum(importe) importe
					from AD_COMPROMISOS_IMP
					where id_compromiso= $id_compromiso
					and id_detalle= $id_detalle";

			$datos = toba::db()->consultar_fila($sql);

			return $datos['importe'];
		}else{
			return 0;
		}
	}

  public static function importar_detalle ($id_compromiso)
  {
    $sql = "BEGIN :resultado := pkg_ad_comprobantes_gasto.importar_detalle_compromiso(:id_compromiso); END;";    
    $parametros = 
      [  
        [ 'nombre' => 'resultado', 
          'tipo_dato' => PDO::PARAM_STR,
          'longitud' => 4000,
          'valor' => ''
        ],
        ['nombre' => 'id_compromiso', 
         'tipo_dato' => PDO::PARAM_STR,
         'longitud' => 32,
         'valor' => $id_compromiso],
      ];
    $resultado = ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
  }
}
?>
