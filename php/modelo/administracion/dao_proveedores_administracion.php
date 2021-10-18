<?php

class dao_proveedores_administracion
{
	public static function get_proveedores ($filtro){
		$where = " 1=1 ";
		if (isset($filtro) && $filtro != null)		
			$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'ADPR', '1=1', array('razon_social', 'direccion', 'telefono', 'localidad'));
		$sql = "SELECT ADPR.*     
				FROM AD_PROVEEDORES ADPR
				WHERE $where 
				ORDER BY ADPR.ID_PROVEEDOR ASC";
		$datos = toba::db()->consultar($sql);
		return $datos;
	} 

  static public function get_proveedores_a_exportar($provincia)
  {
    $sql ="SELECT pro.id_proveedor
             FROM ad_proveedores pro
            WHERE es_numerico (pro.nro_iibb) = 'S'
              AND pro.provincia IS NOT NULL
              AND pro.localidad IS NOT NULL";
    return toba::db()->consultar($sql);
  }
	
	public static function get_proveedores_administracion($nombre, $filtro = array())
	{
		$where = ' 1=1 ';
		if (isset($nombre)) {
            $trans_id_proveedor = ctr_construir_sentencias::construir_translate_ilike('ap.id_proveedor', $nombre);
			$trans_cuit = ctr_construir_sentencias::construir_translate_ilike('ap.cuit', $nombre);
			$trans_razon_social = ctr_construir_sentencias::construir_translate_ilike('ap.razon_social', $nombre);
            $where .= " AND ($trans_id_proveedor OR $trans_cuit OR $trans_razon_social)";
        }
		
		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'ap', '1=1');
		
		$sql_sel = "SELECT	AP.*,
							ap.id_proveedor || ' - ' || ap.razon_social || ' ('||pkg_varios.formatear_cuit(ap.CUIT) ||')' as id_razon_social_cuit_proveedor
					FROM	AD_PROVEEDORES AP
					WHERE $where;";
		return toba::db()->consultar($sql_sel);
	}
	
	static public function get_id_razon_social_cuit_x_id_proveedor($id_proveedor) 
	{
		if (isset($id_proveedor)) {
            $sql = "SELECT	ap.id_proveedor || ' - ' || ap.razon_social || ' ('||pkg_varios.formatear_cuit(ap.CUIT) ||')' as id_razon_social_cuit_proveedor
					FROM AD_PROVEEDORES AP
					WHERE ap.id_proveedor = " . quote($id_proveedor) . ";";
            $datos = toba::db()->consultar_fila($sql);
            if (isset($datos) && !empty($datos) && isset($datos['id_razon_social_cuit_proveedor'])) {
                return $datos['id_razon_social_cuit_proveedor'];
            } else {
                return '';
            }
        } else {
            return '';
        }
    }
	
	static public function get_es_proveedor_generico_x_id_proveedor($id_proveedor) 
	{
		if (isset($id_proveedor)) {
            $sql = "SELECT	1 as existe
					FROM AD_PROVEEDORES AP
					WHERE ap.id_proveedor = " . quote($id_proveedor) . "
					AND ap.cuit = PKG_KR_GENERAL.VALOR_PARAMETRO('CUIT_GENERICO');";
            $datos = toba::db()->consultar_fila($sql);
            if (isset($datos) && !empty($datos) && isset($datos['existe']) && $datos['existe'] == '1') {
                return 'S';
            } else {
                return 'N';
            }
        } else {
            return 'N';
        }
    }
	
	static public function get_razon_social_x_id_proveedor($id_proveedor) 
	{
		if (isset($id_proveedor)) {
            $sql = "SELECT	ap.razon_social
					FROM AD_PROVEEDORES AP
					WHERE ap.id_proveedor = " . quote($id_proveedor) . "
					AND ap.cuit <> PKG_KR_GENERAL.VALOR_PARAMETRO('CUIT_GENERICO');";
            $datos = toba::db()->consultar_fila($sql);
            if (isset($datos) && !empty($datos) && isset($datos['razon_social'])) {
                return $datos['razon_social'];
            } else {
                return '';
            }
        } else {
            return '';
        }
    }
	
	static public function get_datos_proveedor_administracion($id_proveedor) 
	{
		if (isset($id_proveedor)) {
            $sql = "SELECT	ap.*,
							CASE
								WHEN ap.cuit = PKG_KR_GENERAL.VALOR_PARAMETRO('CUIT_GENERICO') THEN 'S'
								ELSE 'N'
							END es_proveedor_generico
					FROM AD_PROVEEDORES AP
					WHERE ap.id_proveedor = " . quote($id_proveedor) . "
					AND ap.cuit <> PKG_KR_GENERAL.VALOR_PARAMETRO('CUIT_GENERICO');";
            $datos = toba::db()->consultar_fila($sql);
            return $datos;
        } else {
            return array();
        }
    }
	
    static public function get_lov_proveedor_x_id ($id_proveedor){
    	if (isset($id_proveedor)) {
	    	$sql = "SELECT ADPR.*, ADPR.ID_PROVEEDOR ||' - '|| ADPR.RAZON_SOCIAL ||' - '|| ADPR.CUIT AS LOV_DESCRIPCION
					FROM AD_PROVEEDORES ADPR
					WHERE ADPR.ID_PROVEEDOR = $id_proveedor  
					ORDER BY ID_PROVEEDOR";
	    	$datos = toba::db()->consultar_fila($sql);
	    	return $datos['lov_descripcion'];
    	}else return null;
    }
    
    static function get_lov_proveedores_x_nombre ($nombre, $filtro = array()){
    	$where = '1=1';
        if (isset($nombre)) {
            $trans_id = ctr_construir_sentencias::construir_translate_ilike('ADPR.ID_PROVEEDOR', $nombre);
            $trans_cuit = ctr_construir_sentencias::construir_translate_ilike('ADPR.CUIT', $nombre);
            $trans_razon = ctr_construir_sentencias::construir_translate_ilike('ADPR.RAZON_SOCIAL', $nombre);
            $where .= " AND ($trans_id OR $trans_cuit OR $trans_razon)";
        }
        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'ADPR', '1=1');
        $sql = "SELECT ADPR.*, ADPR.ID_PROVEEDOR ||' - '|| ADPR.RAZON_SOCIAL ||' - '|| ADPR.CUIT AS LOV_DESCRIPCION
				FROM AD_PROVEEDORES ADPR
				WHERE $where;";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }
   
    static function get_lov_condicion_iva_x_tipo ($tipo_iva){
    	$sql = "SELECT TIV.*, TIV.TIPO_IVA ||' - '|| TIV.DESCRIPCION AS LOV_DESCRIPCION
				FROM AD_TIPOS_IVA TIV
				WHERE tiv.activo = 'S'";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['lov_descripcion'];
    }
    static function get_lov_condicion_iva_x_nombre ($nombre, $filtro){
    	$where = '1=1';
    	if (isset($nombre)) {
            $trans_tipo = ctr_construir_sentencias::construir_translate_ilike('TIV.TIPO_IVA', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('TIV.DESCRIPCION', $nombre);
            $where .= " AND ($trans_tipo OR $trans_descripcion)";
        }
        if (isset($filtro) && $filtro != null)
    		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'TIV', '1=1');
    	$sql = "SELECT TIV.*, TIV.TIPO_IVA ||' - '|| TIV.DESCRIPCION AS LOV_DESCRIPCION
				FROM AD_TIPOS_IVA TIV
				WHERE $where ";
    	$datos = toba::db()->consultar($sql);
    	return $datos;
    }
    
    static function get_tipos_novedades (){
    	$sql = "SELECT TNO.*, TNO.DESCRIPCION ||' - '|| 'No bloquea pago' ||' - '|| 'No avisa pago' AS LOV_DESCRIPCION
				FROM AD_TIPOS_NOVEDAD TNO
				WHERE TNO.BLOQUEA_PAGO = 'N' AND TNO.AVISA_PAGO = 'N'
				UNION ALL
				SELECT TNO.*, TNO.DESCRIPCION ||' - '|| 'Bloquea pago' ||' - '|| 'Avisa pago' AS LOV_DESCRIPCION
				FROM AD_TIPOS_NOVEDAD TNO
				WHERE TNO.BLOQUEA_PAGO = 'S' AND TNO.AVISA_PAGO = 'S'
				UNION ALL
				SELECT TNO.*, TNO.DESCRIPCION ||' - '|| 'Bloquea pago' ||' - '|| 'No avisa pago' AS LOV_DESCRIPCION
				FROM AD_TIPOS_NOVEDAD TNO
				WHERE TNO.BLOQUEA_PAGO = 'S' AND TNO.AVISA_PAGO = 'N'
				UNION ALL
				SELECT TNO.*, TNO.DESCRIPCION ||' - '|| 'No bloquea pago' ||' - '|| 'Avisa pago' AS LOV_DESCRIPCION
				FROM AD_TIPOS_NOVEDAD TNO
				WHERE TNO.BLOQUEA_PAGO = 'N' AND TNO.AVISA_PAGO = 'S'
				ORDER BY LOV_DESCRIPCION";
    	$datos = toba::db()->consultar($sql);
    	return $datos;
    }
    
    static function get_lov_tipo_embargo_x_codigo ($cod_tipo){
    	$sql = "SELECT TIEM.*, TIEM.COD_TIPO ||' - '|| L_KRCTCT.NRO_CUENTA_CORRIENTE ||' - '|| TIEM.COD_MEDIO_PAGO ||' '|| L_ADMEPA.DESCRIPCION AS LOV_DESCRIPCION
				FROM AD_TIPOS_EMBARGOS TIEM,
				     AD_MEDIOS_PAGO L_ADMEPA,
				     KR_CUENTAS_CORRIENTE L_KRCTCT
				WHERE TIEM.COD_TIPO = $cod_tipo and
					  TIEM.COD_MEDIO_PAGO = L_ADMEPA.COD_MEDIO_PAGO AND
				      TIEM.ID_CUENTA_CORRIENTE = L_KRCTCT.ID_CUENTA_CORRIENTE;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];   	
    }
    
	static function get_lov_tipo_embargo_x_nombre ($nombre, $filtro){
		$where = '1=1';
    	if (isset($nombre)) {
            $trans_cod_tipo = ctr_construir_sentencias::construir_translate_ilike('TIEM.COD_TIPO', $nombre);
            $trans_cod_medio = ctr_construir_sentencias::construir_translate_ilike('TIEM.COD_MEDIO_PAGO', $nombre);
            $where .= " AND ($trans_cod_tipo OR $trans_cod_medio)";
        }
        if (isset($filtro) && $filtro != null)
    		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'TIEM', '1=1');
    	$sql = "SELECT TIEM.*, TIEM.COD_TIPO ||' - '|| L_KRCTCT.NRO_CUENTA_CORRIENTE ||' - '|| TIEM.COD_MEDIO_PAGO ||' '|| L_ADMEPA.DESCRIPCION AS LOV_DESCRIPCION
				FROM AD_TIPOS_EMBARGOS TIEM,
				     AD_MEDIOS_PAGO L_ADMEPA,
				     KR_CUENTAS_CORRIENTE L_KRCTCT
				WHERE $where AND 
					  TIEM.COD_MEDIO_PAGO = L_ADMEPA.COD_MEDIO_PAGO AND
				      TIEM.ID_CUENTA_CORRIENTE = L_KRCTCT.ID_CUENTA_CORRIENTE;";
		$datos = toba::db()->consultar($sql);
		return $datos;   	
    }
    
    
    static function get_descuentos_x_embargo ($id_embargo){
    	if (isset($id_embargo)){
            $sql = "BEGIN :resultado := pkg_proveedores.descuentos_aplicados_x_embargo(:id_embargo, SYSDATE);END;";		
            $parametros = array ( array(  'nombre' => 'id_embargo', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 32,
                                          'valor' => $id_embargo),
                                  array(  'nombre' => 'resultado', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => ''),
                            );
           $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
           return $resultado[1]['valor'];
    	}
    }
    
    static public function calcular_saldo_embargos ($id_proveedor){
    	if (isset($id_proveedor)){
            $sql = "BEGIN :resultado := pkg_proveedores.saldo_embargo_proveedor(:id_proveedor, SYSDATE);END;";		
            $parametros = array ( array(  'nombre' => 'id_proveedor', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 32,
                                          'valor' => $id_proveedor),
                                  array(  'nombre' => 'resultado', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => ''),
                            );
           $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
           return $resultado[1]['valor'];
    	}
    }
    
    static public function baja_embargo ($id_embargo, $fecha, $motivo){
    	if (isset($id_embargo) && isset($fecha) && isset($motivo)){
    		try{
            $sql = "BEGIN :resultado := pkg_proveedores.baja_embargo(:id_embargo, to_date(substr(:fecha,1,10),'yyyy-mm-dd'), :motivo);END;";		
            $parametros = array ( array(  'nombre' => 'id_embargo', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 32,
                                          'valor' => $id_embargo),
                                  array(  'nombre' => 'fecha', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => $fecha),
                                  array(  'nombre' => 'motivo', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => $motivo),
                                  array(  'nombre' => 'resultado', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => ''),
                            );
           toba::db()->abrir_transaccion();                
           $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
           if ($resultado[3]['valor'] != 'OK'){
           		toba::db()->abortar_transaccion();
           		toba::notificacion()->info($resultado[3]['valor']);	
           		return $resultado[3]['valor'];
           }else{
           		toba::db()->cerrar_transaccion();
           		toba::notificacion()->info('Embargo dado de baja.');
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
    }
    
 	static function ins_act_proveedor ($id_proveedor, $cuit, $razon_social, $direccion, $telefono, $localidad, $provincia, $iibb, $tipo_iva, $id_proveedor_ad, $tiene_cai_cae, $con_transaccion = true){
 		try{
 			if (!isset($id_proveedor))
 				$id_proveedor ='';
 			if (!isset($iibb))
 				$iibb = '';
 			if (!isset($tipo_iva))
 				$tipo_iva ='';
 			if (!isset($direccion))
 				$direccion ='';
 			if (!isset($telefono))
 				$telefono = '';
 			if (!isset($localidad))
 				$localidad = '';
 			if (!isset($provincia))
 				$provincia = '';
 			if (!isset($id_proveedor_ad))
 				$id_proveedor_ad = '';
 					
            $sql = "BEGIN :resultado := pkg_proveedores.ins_act_proveedor(:id_proveedor, :cuit, :razon_social, :direccion, :telefono, :localidad, :provincia, :iibb, :tipo_iva, :id_proveedor_ad, :tiene_cai_cae);END;";		
            $parametros = array ( array(  'nombre' => 'id_proveedor', 
                                          'tipo_dato' => PDO::PARAM_INT,
                                          'longitud' => 12,
                                          'valor' => $id_proveedor),
                                  array(  'nombre' => 'cuit', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 11,
                                          'valor' => $cuit),
                                  array(  'nombre' => 'razon_social', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 200,
                                          'valor' => $razon_social),
                                  array(  'nombre' => 'direccion', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 2000,
                                          'valor' => $direccion),
                                  array(  'nombre' => 'telefono', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 2000,
                                          'valor' => $telefono),
                                  array(  'nombre' => 'localidad', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 100,
                                          'valor' => $localidad),
                                  array(  'nombre' => 'provincia', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 3,
                                          'valor' => $provincia),
                                  array(  'nombre' => 'iibb', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 2000,
                                          'valor' => $iibb),
                                  array(  'nombre' => 'tipo_iva', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 2000,
                                          'valor' => $tipo_iva),
                                  array(  'nombre' => 'id_proveedor_ad', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => ''),
                                   array(  'nombre' => 'tiene_cai_cae', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 1,
                                          'valor' => $tiene_cai_cae),
                                    array( 'nombre' => 'resultado', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => ''));
		   if ($con_transaccion)	
		   		toba::db()->abrir_transaccion();
			
           $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
           
           $rta = array();
           $rta['resultado'] = $resultado[11]['valor'];
           $rta['id_proveedor_ad'] = $resultado[9]['valor'];
           
           if ($con_transaccion){
           		if ($resultado[11]['valor'] != 'OK'){
           			toba::db()->abortar_transaccion();
           			return $rta;
           		}else{
           			toba::db()->cerrar_transaccion();
           			return $rta;
           		}
           }
           return $rta;
           
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

  /**
  * Devuelve los campos configurados para la exportacion de proveedores
  * Para la provincia especificada.
  * @return array
  */
  public static function get_campos_a_exportar ($cod_provincia)
  {
    $sql = "SELECT *
          FROM ex_proveedores 
         WHERE cod_provincia = ".quote($cod_provincia)."
         ORDER BY ORDEN ";
      return toba::db()->consultar($sql);
  }
  static public function exportar_proveedor($id_proveedor, $funcion_formato_salida, $nro_renglon, &$cadena = '') 
  {
    if (isset($id_proveedor) && !empty($id_proveedor) && isset($funcion_formato_salida) && !empty($funcion_formato_salida)) {
      $sql = "BEGIN :resultado := pkg_proveedores.exportar_proveedor(:id_proveedor, :funcion_formato_salida, :nro_renglon, :l_cadena); END;";

      $parametros = array(
                array(  'nombre' => 'resultado', 
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''),
                array(  'nombre' => 'id_proveedor', 
                    'tipo_dato' => PDO::PARAM_INT,
                    'longitud' => 32,
                    'valor' => $id_proveedor),
                array(  'nombre' => 'funcion_formato_salida', 
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => $funcion_formato_salida),
                array(  'nombre' => 'nro_renglon', 
                    'tipo_dato' => PDO::PARAM_INT,
                    'longitud' => 11,
                    'valor' => $nro_renglon),
                array(  'nombre' => 'l_cadena', 
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''),
                
        );
        
       $resultado = ctr_procedimientos::ejecutar_procedimiento('Error al exportar proveedor.',$sql, $parametros);
      $cadena = $resultado[4]['valor'];
      return $resultado[0]['valor'];
    }
  }
  
}
?>
