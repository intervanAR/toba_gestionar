<?php
class dao_asientos_contables {
	
	static public function get_asientos_contables ($filtro = array (),$orden = array())
    { 
        $desde= null;$hasta= null;
        if(isset($filtro['numrow_desde'])){
            $desde= $filtro['numrow_desde'];
            $hasta= $filtro['numrow_hasta'];
            unset($filtro['numrow_desde']);
            unset($filtro['numrow_hasta']);
        }

		$where = self::get_where($filtro);

		$sql = "SELECT cpac.id_asiento, cpac.nro_asiento, cpac.cod_tipo_asiento,cpac.detalle,
                       cpac.id_asiento_contrasienta, cpac.id_ejercicio, cpac.id_transaccion,
                       cpac.usuario_anula, cpac.usuario_carga, cpac.usuario_confirma,
                       cpac.anulado, cpac.confirmado,
                       TO_CHAR (cpac.fecha_asiento, 'dd/mm/yyyy') fecha_asiento_format,
                       TO_CHAR (cpac.fecha_anula, 'dd/mm/yyyy') fecha_anula_format,
                       TO_CHAR (cpac.fecha_confirma, 'dd/mm/yyyy') fecha_confirma_format,
                       TO_CHAR (cpac.fecha_asiento_anulacion,
                                'dd/mm/yyyy'
                               ) fecha_asiento_anu_format,
                       TO_CHAR (cpac.fecha_carga, 'dd/mm/yyyy') fecha_carga_format,
                       krua.descripcion unidad_administracion, krej.descripcion ejercicio,
                       cpta.descripcion tipo_asiento,
                       CASE
                          WHEN cpac.automatico = 'S'
                             THEN 'Si'
                          ELSE 'No'
                       END automatico_format,
                       CASE
                          WHEN cpasc.nro_asiento IS NOT NULL
                             THEN    cpasc.nro_asiento
                                  || ' (ID: '
                                  || cpasc.id_asiento
                                  || ')'
                          ELSE ''
                       END contrasiento,
                       (SELECT SUM (cpasde.debe)
                          FROM cp_detalles_asiento cpasde
                         WHERE cpasde.id_asiento = cpac.id_asiento) debe,
                       (SELECT SUM (cpasde.haber)
                          FROM cp_detalles_asiento cpasde
                         WHERE cpasde.id_asiento = cpac.id_asiento) haber
				FROM CP_ASIENTOS CPAC LEFT JOIN KR_UNIDADES_ADMINISTRACION KRUA ON CPAC.COD_UNIDAD_ADMINISTRACION = KRUA.COD_UNIDAD_ADMINISTRACION
				LEFT JOIN KR_EJERCICIOS KREJ ON KREJ.ID_EJERCICIO = CPAC.ID_EJERCICIO
				LEFT OUTER JOIN CP_ASIENTOS CPASC ON CPAC.ID_ASIENTO_CONTRASIENTA = CPASC.ID_ASIENTO
				LEFT JOIN CP_TIPOS_ASIENTO CPTA ON CPTA.COD_TIPO_ASIENTO = CPASC.COD_TIPO_ASIENTO
				WHERE $where
				ORDER BY CPAC.ID_ASIENTO DESC";
		
        $sql= dao_varios::paginador($sql, null, $desde, $hasta, null ,$orden);
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

    static private function get_where ($filtro = []){
        $where = "";
        if (isset($filtro['usuario_ua']) && $filtro['usuario_ua'] ==1) {
            $sql1 = "SELECT PKG_KR_USUARIOS.in_ua_tiene_acceso(upper('".toba::usuario()->get_id()."')) unidades_ad FROM DUAL";
            $res = toba::db()->consultar_fila($sql1); 
            $where = "(CPAC.COD_UNIDAD_ADMINISTRACION IN ".$res['unidades_ad'].")";
            unset($filtro['usuario_ua']);
        } else {
            $where = " 1=1 ";
        }
        
        if (isset($filtro) && !empty($filtro)) {
            $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'CPAC', '1=1');
        }
        return $where;
    }
	
    public static function get_cantidad($filtro = array()){
        $where = self::get_where($filtro);
        $sql = "SELECT COUNT(cpac.id_asiento) cant 
              FROM CP_ASIENTOS CPAC
              WHERE $where";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['cant'];
    }


	static public function get_asiento_contable_x_id ($id_asiento){
		$sql ="SELECT * FROM CP_ASIENTOS WHERE ID_ASIENTO = $id_asiento;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos;
	}
	
	
	static public function get_lov_tipo_asiento_x_codigo ($cod_tipo_asiento){
		$sql = "SELECT CPTA.*, CPTA.COD_TIPO_ASIENTO ||' - '|| CPTA.DESCRIPCION ||' - Requiere UA: '|| CPTA.REQUIERE_UA AS LOV_DESCRIPCION
				FROM CP_TIPOS_ASIENTO CPTA
				WHERE CPTA.COD_TIPO_ASIENTO = $cod_tipo_asiento
				ORDER BY LOV_DESCRIPCION ASC;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}
	
	static public function get_lov_tipo_asiento_x_nombre ($nombre, $filtro){
		$where = "";
		if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('CPTA.COD_TIPO_ASIENTO', $nombre);
            $trans_des = ctr_construir_sentencias::construir_translate_ilike('CPTA.DESCRIPCION', $nombre);
            $trans_ua  = ctr_construir_sentencias::construir_translate_ilike('CPTA.REQUIERE_UA', $nombre);
            $where = "($trans_cod OR $trans_des OR $trans_ua)";
        } else {
            $where = "1=1";
        }
        if (isset($filtro) && !empty($filtro))
			$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'CPTA', '1=1');
		$sql = "SELECT CPTA.*, CPTA.COD_TIPO_ASIENTO ||' - '|| CPTA.DESCRIPCION ||' - Requiere UA: '|| CPTA.REQUIERE_UA AS LOV_DESCRIPCION
				FROM CP_TIPOS_ASIENTO CPTA
				WHERE $where
				ORDER BY LOV_DESCRIPCION ASC;";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	

	
	static public function get_nro_ejercicio_ui ($id_ejercicio){
		$sql =" SELECT NRO_EJERCICIO 
				FROM KR_EJERCICIOS
				WHERE ID_EJERCICIO = $id_ejercicio";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['nro_ejercicio'];
	}
	
	static public function get_valor_ejercicio($fecha) {
        try {
            $sql = "BEGIN :resultado := pkg_kr_ejercicios.retornar_ejercicio(to_date(substr(:fecha_comprobante,1,10),'dd-mm-yyyy')); END;";
            $parametros = array(array('nombre' => 'fecha_comprobante',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $fecha),
                array('nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''),
            );
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

            return array("ui_id_ejercicio" => $resultado[1]['valor']);
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
       }
    }
    
    static public function validar_fecha ($fecha){
    	//retorna S si la fecha es <= que sysdate, sino retorna N
    	$sql =" SELECT 1 as valido
				FROM DUAL
				WHERE to_date('$fecha', 'dd-mm-yyyy')  <= trunc(SYSDATE) ;";
    	$datos = toba::db()->consultar_fila($sql);
    	if ($datos['valido'] == '1')
    		return 'S';
    	else 
    		return 'N'; 
    	
    }
	
    
    static public function get_lov_cuentas_contables_x_numero ($nro_cuenta_contable){
    	$sql = "SELECT CPC.*, pkg_cp_cuentas.mascara_aplicar(CPC.NRO_CUENTA_CONTABLE) ||' - '|| CPC.DESCRIPCION ||'- CtaCte: '|| pkg_cp_cuentas.CTA_CTE(cpc.nro_cuenta_contable) 
							||' - CtaBco: '|| pkg_cp_cuentas.CTA_BCO(cpc.nro_cuenta_contable) as lov_descripcion
				FROM CP_CUENTAS CPC
				WHERE CPC.NRO_CUENTA_CONTABLE = $nro_cuenta_contable;";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['lov_descripcion'];
    }
    
    static public function get_lov_cuentas_contables_x_nombre ($nombre, $filtro = array()){
    	$where = "";
		if (isset($nombre)) {
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('CPC.NRO_CUENTA_CONTABLE', $nombre);
            $trans_des = ctr_construir_sentencias::construir_translate_ilike('CPC.DESCRIPCION', $nombre);
            $where = "($trans_nro OR $trans_des)";
        } else {
            $where = "1=1";
        }
        if (isset($filtro) && !empty($filtro))
			$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'CPC', '1=1');
		$sql = "SELECT CPC.*, pkg_cp_cuentas.mascara_aplicar(CPC.NRO_CUENTA_CONTABLE) ||' - '|| CPC.DESCRIPCION ||'- CtaCte: '|| pkg_cp_cuentas.CTA_CTE(cpc.nro_cuenta_contable) 
								||' - CtaBco: '|| pkg_cp_cuentas.CTA_BCO(cpc.nro_cuenta_contable) as lov_descripcion
				FROM CP_CUENTAS CPC
				WHERE $where and PKG_CP_CUENTAS.ACTIVA(NRO_CUENTA_CONTABLE) = 'S'
      				  AND PKG_CP_CUENTAS.IMPUTABLE(NRO_CUENTA_CONTABLE) = 'S'
      		    ORDER BY LOV_DESCRIPCION;";    
		$datos = toba::db()->consultar($sql);
		return $datos;	
    }
	
    static public function get_lov_cuentas_corrientes_x_id ($id_cuenta_corriente){
    	$sql = "SELECT KRCTCT.*, KRCTCT.ID_CUENTA_CORRIENTE ||' - '|| KRCTCT.NRO_CUENTA_CORRIENTE ||' - '|| KRCTCT.DESCRIPCION ||' - '|| CGRC.RV_MEANING ||' - '|| KRUA.DESCRIPCION AS LOV_DESCRIPCION
				FROM KR_CUENTAS_CORRIENTE KRCTCT 
				     LEFT JOIN KR_UNIDADES_ADMINISTRACION KRUA ON KRUA.COD_UNIDAD_ADMINISTRACION = KRCTCT.COD_UNIDAD_ADMINISTRACION
				     LEFT JOIN CG_REF_CODES CGRC ON (CGRC.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE' AND CGRC.RV_LOW_VALUE = KRCTCT.TIPO_CUENTA_CORRIENTE)
				WHERE KRCTCT.ID_CUENTA_CORRIENTE = $id_cuenta_corriente;";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['lov_descripcion'];
    }
    
    static public function get_lov_cuenta_corriente_x_nombre ($nombre, $filtro = array()){
    	$where = "";
		if (isset($nombre)) {
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('KRCTCT.NRO_CUENTA_CORRIENTE', $nombre);
            $trans_des = ctr_construir_sentencias::construir_translate_ilike('KRCTCT.DESCRIPCION', $nombre);
            $where = "($trans_nro OR $trans_des)";
        } else {
            $where = "1=1";
        }
        if (isset($filtro['nro_cuenta_contable'])){
        	$where .= " AND pkg_cp_cuentas.CTA_CTE(".$filtro['nro_cuenta_contable'].") = 'S'";
        	unset($filtro['nro_cuenta_contable']);
        }
        
        if (isset($filtro) && !empty($filtro))
			$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'KRCTCT', '1=1');
	    
    	$sql = "SELECT KRCTCT.*, KRCTCT.ID_CUENTA_CORRIENTE ||' - '|| KRCTCT.NRO_CUENTA_CORRIENTE ||' - '|| KRCTCT.DESCRIPCION ||' - '|| CGRC.RV_MEANING ||' - '|| KRUA.DESCRIPCION AS LOV_DESCRIPCION
				FROM KR_CUENTAS_CORRIENTE KRCTCT 
				     LEFT JOIN KR_UNIDADES_ADMINISTRACION KRUA ON KRUA.COD_UNIDAD_ADMINISTRACION = KRCTCT.COD_UNIDAD_ADMINISTRACION
				     LEFT JOIN CG_REF_CODES CGRC ON (CGRC.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE' AND CGRC.RV_LOW_VALUE = KRCTCT.TIPO_CUENTA_CORRIENTE)
				WHERE $where  
				ORDER BY LOV_DESCRIPCION;";
    	$datos = toba::db()->consultar($sql);
    	return $datos;
    }
    
    static public function get_lov_cuentas_bancos_x_id ($id_cuenta_banco){
    	
    	
    	$sql = "SELECT KRCUBA.*, KRCUBA.ID_CUENTA_BANCO ||' - '|| KRCUBA.NRO_CUENTA ||' - '|| KRCUBA.DESCRIPCION ||' - '|| KRCUBA.TIPO_CUENTA_BANCO AS LOV_DESCRIPCION
				FROM KR_CUENTAS_BANCO KRCUBA  
				WHERE  KRCUBA.ID_CUENTA_BANCO = $id_cuenta_banco
				ORDER BY LOV_DESCRIPCION";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['lov_descripcion'];
    }
    
    static public function get_lov_cuentas_banco_x_nombre ($nombre, $filtro = array()){
    	$where = "";
		if (isset($nombre)) {
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('KRCUBA.NRO_CUENTA', $nombre);
            $trans_des = ctr_construir_sentencias::construir_translate_ilike('KRCUBA.DESCRIPCION', $nombre);
            $where = "($trans_nro OR $trans_des)";
        } else {
            $where = "1=1";
        }
        if (isset($filtro['nro_cuenta_contable'])){
        	$where .= " AND pkg_cp_cuentas.CTA_BCO(".$filtro['nro_cuenta_contable'].") = 'S'";
        	unset($filtro['nro_cuenta_contable']);
        }
        
        if (isset($filtro) && !empty($filtro))
			$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'KRCUBA', '1=1');
    	
    	$sql = "SELECT KRCUBA.*, KRCUBA.ID_CUENTA_BANCO ||' - '|| KRCUBA.NRO_CUENTA ||' - '|| KRCUBA.DESCRIPCION ||' - '|| KRCUBA.TIPO_CUENTA_BANCO AS LOV_DESCRIPCION
				FROM KR_CUENTAS_BANCO KRCUBA  
				WHERE $where
				ORDER BY LOV_DESCRIPCION";
    	$datos = toba::db()->consultar($sql);
    	return $datos;
    }
    
    static public function confirmar_asiento_contable ($id_asiento){
	    try {
            $sql = "BEGIN :resultado := PKG_CP_ASIENTOS.CONFIRMAR(:id_asiento); END;";
            $parametros = array(array('nombre' => 'id_asiento',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $id_asiento),
                array('nombre' => 'resultado',
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
            	toba::notificacion()->info('No se pudo Confirmar: '.$resultado[1]['valor']);
            }
            return $resultado[1]['valor'];
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
       }
    }
    
    static public function anular_asiento_contable ($id_asiento){
      try {
            $sql = "BEGIN :resultado :=  PKG_CP_ASIENTOS.ANULAR(:id_asiento); END;";
            $parametros = array(array('nombre' => 'id_asiento',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $id_asiento),
                array('nombre' => 'resultado',
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
            	toba::notificacion()->info('No se pudo Anular: '.$resultado[1]['valor']);
            }
            return $resultado[1]['valor'];
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
       }	
    }
    
    static public function contrasentar_asiento_contable ($id_asiento, $fecha){
     try {
     		
            $sql = "BEGIN :resultado :=  PKG_CP_ASIENTOS.CONTRA_ASENTAR(:id_asiento, :fecha, :id_contrasiento);END;";
            $parametros = array(
            
              array('nombre' => 'id_asiento',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $id_asiento),
            
              array('nombre' => 'fecha',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $fecha),
            
              array('nombre' => 'id_contrasiento',
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
            }else{
            	toba::db()->abortar_transaccion();
            }
            return $resultado[3]['valor'];
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
       }
    }
    
    static public function get_ui_requiere_ua ($cod_tipo_asiento){
    	if (isset($cod_tipo_asiento)){
	    	$sql = "SELECT REQUIERE_UA AS ui_requiere_ua 
					FROM CP_TIPOS_ASIENTO
					WHERE COD_TIPO_ASIENTO = $cod_tipo_asiento";
	    	$datos = toba::db()->consultar_fila($sql);
	    	return $datos['ui_requiere_ua'];
    	}else return null;
    }
}

?>