<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of dao_recibos_cobros
 *
 * @author hmargiotta
 */
class dao_recibos_cobros {
    public static function get_recibos_cobros($filtro=array(), $orden = array()){
        $desde= null; $hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];
			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}
		$where = self::armar_where($filtro);
        $sql = "SELECT rc.*, 
        		decode(rc.aprobado,'S','Si','No') aprobado_format,
        		decode(rc.anulado,'S','Si','No') anulado_format,
        		to_char(rc.fecha_comprobante, 'dd/mm/yyyy') fecha_comprobante_format, trim(to_char(rc.importe, '$999,999,999,990.00')) importe_format,
                ua.descripcion unidad_administracion, trc.descripcion tipo_recibo, ae.descripcion auxiliar, cc.descripcion desc_cuenta_corriente
                FROM AD_RECIBOS_COBRO rc, KR_UNIDADES_ADMINISTRACION ua, 
                AD_TIPOS_RECIBO_COBRO trc, KR_AUXILIARES_EXT ae, KR_CUENTAS_CORRIENTE cc
                WHERE ua.cod_unidad_administracion= rc.cod_unidad_administracion
                AND trc.cod_tipo_recibo= rc.cod_tipo_recibo
                AND ae.cod_auxiliar (+)= rc.cod_auxiliar
                AND cc.id_cuenta_corriente= rc.id_cuenta_corriente
                $where
				order by rc.id_recibo_cobro desc";
                
	    $sql= dao_varios::paginador($sql, null, $desde, $hasta, null, $orden);
	    $datos = toba::db()->consultar($sql);
	    return $datos;
    }
	static public function armar_where ($filtro = array())
	{
       if (empty($filtro)){        
            $sql1 = "SELECT PKG_KR_USUARIOS.in_ua_tiene_acceso(upper('".toba::usuario()->get_id()."')) unidades,
							PKG_KR_USUARIOS.in_ue_tiene_acceso(upper('".toba::usuario()->get_id()."')) unidades_ejecutoras
                        FROM DUAL";
            $res = toba::db()->consultar_fila($sql1);
            $where= "AND rc.COD_UNIDAD_ADMINISTRACION in ".$res['unidades']."
					 AND ( pkg_kr_usuarios.usuario_tiene_ues(upper('".toba::usuario()->get_id()."'))='N'
							    OR rc.COD_UNIDAD_EJECUTORA in ".$res['unidades_ejecutoras']."
							   )";;
        }else{
            $where= " AND 1=1";
        }
        
		if (isset($filtro['id_apertura'])) {
            $where .= "AND rc.id_recibo_cobro IN (SELECT id_recibo_cobro
					                                FROM ad_recibos_cobro_ape_liq
					                               WHERE id_apertura = ".$filtro['id_apertura'].")";
            unset($filtro['id_apertura']);
        }
        if (isset($filtro['id_liquidacion_recaudador'])) {
            $where .= "AND rc.id_recibo_cobro IN (SELECT id_recibo_cobro
					                                FROM ad_recibos_cobro_ape_liq
					                               WHERE id_liquidacion_recaudador = ".$filtro['id_liquidacion_recaudador'].")";
            unset($filtro['id_liquidacion_recaudador']);
        }


		if (isset($filtro['presupuestario'])){
			if ($filtro['presupuestario']) {
				$where.=" AND trc.presupuestario = '".$filtro['presupuestario']."'";

				unset($filtro['presupuestario']);
			}
		}
		if (isset($filtro['ids_comprobantes'])) {
            $where .= "AND rc.id_recibo_cobro IN (" . $filtro['ids_comprobantes'] . ") ";
            unset($filtro['ids_comprobantes']);
        }
        $where.= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'rc', '1=1');
        return $where;
	}
	
	static public function get_cantidad ($filtro = array())
	{
		$where = self::armar_where($filtro);
		$sql = " SELECT count(*) cantidad
				   FROM AD_RECIBOS_COBRO rc, KR_UNIDADES_ADMINISTRACION ua, 
	               	    AD_TIPOS_RECIBO_COBRO trc, KR_AUXILIARES_EXT ae, KR_CUENTAS_CORRIENTE cc
	              WHERE ua.cod_unidad_administracion= rc.cod_unidad_administracion
	                AND trc.cod_tipo_recibo= rc.cod_tipo_recibo
	                AND ae.cod_auxiliar (+)= rc.cod_auxiliar
	                AND cc.id_cuenta_corriente= rc.id_cuenta_corriente
	                $where";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cantidad'];
	}
	
	public static function get_recibo_cobro_x_id($id_recibo_cobro){
       
        if (isset($id_recibo_cobro)) {
			$sql = "SELECT  rc.*, 
							rc.nro_recibo as nro_recibo_cobro,
							to_char(rc.fecha_comprobante, 'dd/mm/yyyy') fecha_comprobante_format,
							to_char(rc.fecha_anulacion_recibo, 'yyyy-mm-dd') fecha_anulacion_recibo_format,
							to_char(rc.fecha_anulacion_recibo, 'yyyy-mm-dd') fecha_anulacion_recibo_2,
							kcc.tipo_cuenta_corriente
					FROM AD_RECIBOS_COBRO rc
					JOIN KR_CUENTAS_CORRIENTE kcc ON (rc.id_cuenta_corriente = kcc.id_cuenta_corriente)
					WHERE rc.id_recibo_cobro = " . quote($id_recibo_cobro) .";";

			$datos = toba::db()->consultar_fila($sql);
			return $datos;
		} else {
			return array();
		}
    }
	
	
	static public function get_lov_recibo_cobro_x_id($id_recibo_cobro) {
        if (isset($id_recibo_cobro)) {
            $sql = "SELECT rc.*, 
            rc.id_recibo_cobro||' - '||rc.nro_recibo||' - '||to_char(rc.fecha_comprobante,'dd/mm/yyyy')
            				  ||' (' || trim(to_char(rc.IMPORTE, '$999,999,999,990.00')) ||')' as lov_descripcion
                    FROM AD_RECIBOS_COBRO rc
                    WHERE rc.id_recibo_cobro = ".quote($id_recibo_cobro) .";";
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
	
	static public function get_lov_recibo_x_nombre($nombre, $filtro = array()) {

        if (isset($nombre)) {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('id_recibo_cobro', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('nro_recibo', $nombre);
            $where = "($trans_codigo OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
        if (isset($filtro['para_rendicion_caja_chica'])){
            $where .= " and (ADRECB.aprobado = 'S' 
                        AND ADRECB.anulado = 'N' 
                        AND ADRECB.cod_unidad_administracion = ".$filtro['cod_unidad_administracion']." 
                        AND PKG_AD_CAJAS_CHICA.RETORNAR_CACH_X_CUENTA_CTE(".$filtro['cod_unidad_administracion'].", ADRECB.id_cuenta_corriente) = ".$filtro['id_caja_chica'].")";
            unset($filtro['para_rendicion_caja_chica']);
            unset($filtro['id_caja_chica']);
            unset($filtro['cod_unidad_administracion']);
        }
        if (isset($filtro['para_ordenes_pago'])) {
            if ($filtro['cod_uni_ejecutora'] == 0) {
                $filtro['cod_uni_ejecutora'] = 'NULL';
            }
            $where .= " AND (    adrecb.aprobado = 'S'
                     AND adrecb.anulado = 'N'
                     AND " . $filtro['cod_uni_admin'] . " =
                                                        adrecb.cod_unidad_administracion
                     AND pkg_kr_transacciones.saldo_transaccion
                                                            (adrecb.id_transaccion,
                                                             adrecb.id_cuenta_corriente,
                                                             SYSDATE
                                                            ) > 0
                     AND adrecb.id_cuenta_corriente = " . $filtro['id_cta_cte'] . "
                     AND (   " . $filtro['cod_uni_ejecutora'] . " IS NULL
                         OR adrecb.cod_unidad_ejecutora =
                                                     " . $filtro['cod_uni_ejecutora'] . "
                        )
                      )
                      
                      and adrecb.id_recibo_cobro not in (select id_recibo_cobro 
                      									   from ad_ordenes_pago_rc
                      									   where id_orden_pago = ".quote($filtro['id_orden_pago']).")";
            
			unset($filtro['id_orden_pago']);
            unset($filtro['para_ordenes_pago']);
            unset($filtro['cod_uni_admin']);
            unset($filtro['id_cta_cte']);
            unset($filtro['cod_uni_ejecutora']);
        }

		if (isset($filtro['fecha_rpa'])){
			$where .=" AND ADRECB.FECHA_COMPROBANTE <= TO_DATE('".$filtro['fecha_rpa']."', 'yyyy/mm/dd')";
			unset($filtro['fecha_rpa']);
		}

		if (isset($filtro['ids_comprobantes'])) {
            $where .= "AND adrecb.id_recibo_cobro IN (" . $filtro['ids_comprobantes'] . ") ";
            unset($filtro['ids_comprobantes']);
        }
		
        $where .= 'AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'adrecb', '1=1');

        $sql = "SELECT  adrecb.*,   adrecb.id_recibo_cobro
       || ' - '
       || adrecb.nro_recibo
       || ' - '
       || TO_CHAR (adrecb.fecha_comprobante, 'DD/MM/YYYY') 
       ||' (' || trim(to_char(adrecb.IMPORTE, '$999,999,999,990.00')) ||')' AS lov_descripcion
                FROM AD_RECIBOs_COBRO adrecb
                WHERE $where
                ORDER BY lov_descripcion ASC;";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }
	
	
	static public function get_tipos_recibos($filtro= array()){
        
        $where= "1=1";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'tr', '1=1');
        
        $sql = "SELECT	tr.*
                FROM AD_TIPOS_RECIBO_COBRO tr
                WHERE $where";
        
        $datos = toba::db()->consultar($sql);
        return $datos;      
    }
    
    static public function get_lov_tipo_recibo_x_cod_tipo($cod_tipo) {        
		if (isset($cod_tipo)) {
			$sql = "SELECT tr.*, descripcion lov_descripcion
				FROM AD_TIPOS_RECIBO_COBRO tr
				WHERE tr.cod_tipo_recibo = ".quote($cod_tipo) .";";

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
	
	
	static public function get_lov_tipos_recibo_x_nombre($nombre, $filtro = array()) {

        if (isset($nombre)) {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('cod_tipo_recibo', $nombre);            
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_codigo OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
		
        $where .= 'AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'tr', '1=1');

        $sql = "SELECT  tr.*, tr.cod_tipo_recibo||' - '||tr.descripcion as lov_descripcion
                FROM AD_TIPOS_RECIBO_COBRO tr
                WHERE $where
                ORDER BY lov_descripcion ASC;";
        
        $datos = toba::db()->consultar($sql);
        return $datos;
    }
	
	/*public static function get_crea_comprobante($cod_medio_pago){	
		try{
			if (isset($cod_medio_pago)&&(!empty($cod_medio_pago))) {
					$sql = "BEGIN pkg_ad_comprobantes_pagos.condiciones_comprobante(:cod_medio_pago, 'COB', :crea, :automatica, :cta_cte); END;";		

					$parametros = array ( array(  'nombre' => 'cod_medio_pago', 
													'tipo_dato' => PDO::PARAM_STR,
													'longitud' => 32,
													'valor' => $cod_medio_pago),
											array(  'nombre' => 'crea', 
													'tipo_dato' => PDO::PARAM_STR,
													'longitud' => 20,
													'valor' => ''),
											array(  'nombre' => 'automatica', 
													'tipo_dato' => PDO::PARAM_STR,
													'longitud' => 20,
													'valor' => ''),
											array(  'nombre' => 'cta_cte', 
													'tipo_dato' => PDO::PARAM_STR,
													'longitud' => 400,
													'valor' => '')
									);
					$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);                


					return $resultado[1]['valor'];

				}else{
					return '';
				}                
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error($e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error($e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
         //   toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error($e->get_mensaje());
            toba::logger()->error($e->get_mensaje());
        //    toba::db()->abortar_transaccion();
        }
		
	}*/
	
	public static function get_cotizacion_referencia($cod_moneda, $fecha_comprobante){
		
		try{
			if (isset($cod_moneda)&&(!empty($cod_moneda))) {
					$sql = "BEGIN :resultado:= PKG_AD_MONEDAS.COTIZACION_REFERENCIA(:cod_moneda,to_date(substr(:fecha_comprobante,1,10),'yyyy-mm-dd')); END;";		

					$parametros = array ( array(  'nombre' => 'fecha_comprobante', 
													'tipo_dato' => PDO::PARAM_STR,
													'longitud' => 32,
													'valor' => $fecha_comprobante),
											array(  'nombre' => 'cod_moneda', 
													'tipo_dato' => PDO::PARAM_STR,
													'longitud' => 20,
													'valor' => $cod_moneda),
											array(  'nombre' => 'resultado', 
													'tipo_dato' => PDO::PARAM_STR,
													'longitud' => 400,
													'valor' => '')
									);
					$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);                


					return $resultado[2]['valor'];

				}else{
					return '';
				}                
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error($e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error($e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
         //   toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error($e->get_mensaje());
            toba::logger()->error($e->get_mensaje());
        //    toba::db()->abortar_transaccion();
        }
	}
	
	public static function get_unidad_administracion_default(){

        $sql = "select  PKG_KR_USUARIOS.ua_por_defecto(upper('".toba::usuario()->get_id()."')) cod_unidad_administracion
                from dual";

        $datos = toba::db()->consultar_fila($sql);
        
        return $datos['cod_unidad_administracion'];
    }
	
	public static function get_unidad_ejecutora_default(){

        $sql = "select  PKG_KR_USUARIOS.ue_por_defecto(upper('".toba::usuario()->get_id()."')) cod_unidad_ejecutora
                from dual";

        $datos = toba::db()->consultar_fila($sql);
        
        return $datos['cod_unidad_ejecutora'];
    }
	
	public static function get_vincula_expediente(){
		$sql = "select  PKG_GENERAL.VALOR_PARAMETRO_KR('VINCULA_EXPEDIENTE_COB') vincula
                from dual";

        $datos = toba::db()->consultar_fila($sql);
        
        return $datos['vincula'];
	}
	
	public static function get_tipo_aplicacion_descripcion($tipo_aplicacion){
		/*if ($tipo_aplicacion == 'CRE') {
			$descripcion= dao_comprobantes_recurso::get_comprobante_recurso_x_id($id_comprobante_recurso);
		}elseif($tipo_aplicacion == 'CGA'){
			$descripcion= dao_devengados_gastos::get_comprobante_gasto_x_id( $id_comprobante_gasto);
		}else{
			$descripcion= dao_recibos_pago::get_recibo_pago_x_id($id_recibo_pago);
		}
		
		return array('tipo_aplicacion_desc' => " - ".$tipo_aplicacion." - ".$id_comprobante_recurso." - ".$id_comprobante_gasto." - ".$id_recibo_pago);
		*/
		
	   $dominio= dao_valor_dominios::significado_dominio('AD_TIPO_APLICACION_COB',$tipo_aplicacion);
		
		return  array('tipo_aplicacion_desc' => $dominio[0]['rv_meaning']);
	}
	
	/*
	public static function calcular_diferencia($id_comprobante_gasto, $id_comprobante_recurso, $id_recibo_pago){
		
		$l_saldo_cr= 0;
		$l_saldo_cg= 0;
		$l_saldo_rp= 0;
		
		if (isset($id_comprobante_recurso)){
			$sql= "SELECT SUM(pkg_kr_transacciones.saldo_transaccion(id_transaccion,id_cuenta_corriente,sysdate)) saldo
					FROM ad_comprobantes_recurso 
					WHERE id_comprobante_recurso = ".$id_comprobante_recurso;
			
			$datos = toba::db()->consultar_fila($sql);
			
			$l_saldo_cr= $datos['saldo'];
		}
		
		if (isset($id_comprobante_gasto)){
			$sql= "SELECT NVL(pkg_kr_transacciones.saldo_transaccion(id_transaccion,id_cuenta_corriente,sysdate),0) saldo
					FROM ad_comprobantes_gasto 
					WHERE id_comprobante_gasto = ".$id_comprobante_gasto;
			
			$datos = toba::db()->consultar_fila($sql);
			
			$l_saldo_cg= $datos['saldo'];
		}
		
		if (isset($id_recibo_pago)){
			$sql= "SELECT NVL(pkg_kr_transacciones.saldo_transaccion(id_transaccion,id_cuenta_corriente,sysdate),0) saldo
				FROM ad_recibos_pago rpa
				WHERE rpa.id_recibo_pago = ".$id_recibo_pago;
			
			$datos = toba::db()->consultar_fila($sql);
			
			$l_saldo_rp= $datos['saldo'];
		}
	}*/
	
	public static function aplicar_aplicacion_cobro($id_recibo_cobro, $id_aplicacion_cobro, $fecha){
		
		try{
			toba::db()->abrir_transaccion();
			if (isset($id_recibo_cobro)&&(!empty($id_recibo_cobro))) {
					$sql = "BEGIN :resultado:= pkg_kr_trans_tesoreria.confirmar_aplicacion_rec_cobro(:id_recibo_cobro, :id_aplicacion_cobro,to_date(substr(:fecha,1,10),'yyyy-mm-dd')); END;";		

					$parametros = array ( array(  'nombre' => 'fecha', 
													'tipo_dato' => PDO::PARAM_STR,
													'longitud' => 32,
													'valor' => $fecha),
											array(  'nombre' => 'id_aplicacion_cobro', 
													'tipo_dato' => PDO::PARAM_INT,
													'longitud' => 20,
													'valor' => $id_aplicacion_cobro),
											array(  'nombre' => 'id_recibo_cobro', 
													'tipo_dato' => PDO::PARAM_INT,
													'longitud' => 20,
													'valor' => $id_recibo_cobro),
											array(  'nombre' => 'resultado', 
													'tipo_dato' => PDO::PARAM_STR,
													'longitud' => 4000,
													'valor' => ''));
					$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);   
					if ($resultado[3]['valor'] == 'OK'){
						toba::db()->cerrar_transaccion();
					}else{
						toba::db()->abortar_transaccion();
					}             
					return $resultado[3]['valor'];
				}else{
					return '';
				}                
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error($e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error($e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error($e->get_mensaje());
            toba::logger()->error($e->get_mensaje());
            toba::db()->abortar_transaccion();
        }
	}
	
	public static function anular_aplicacion_cobro($id_aplicacion_cobro, $fecha){
		try{
			toba::db()->abrir_transaccion();
			if (isset($id_aplicacion_cobro)&&(!empty($id_aplicacion_cobro))) {
					$sql = "BEGIN :resultado:= pkg_kr_trans_tesoreria.anular_aplicacion_rec_cobro(:id_aplicacion_cobro,to_date(substr(:fecha,1,10),'yyyy-mm-dd')); END;";		

					$parametros = array ( array(  'nombre' => 'fecha', 
													'tipo_dato' => PDO::PARAM_STR,
													'longitud' => 32,
													'valor' => $fecha),
											array(  'nombre' => 'id_aplicacion_cobro', 
													'tipo_dato' => PDO::PARAM_INT,
													'longitud' => 20,
													'valor' => $id_aplicacion_cobro),
											array(  'nombre' => 'resultado', 
													'tipo_dato' => PDO::PARAM_STR,
													'longitud' => 4000,
													'valor' => ''));
					$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);                

					if ($resultado[2]['valor'] == 'OK'){
						toba::db()->cerrar_transaccion();
						
					}else{
						toba::db()->abortar_transaccion();
					}
					return $resultado[2]['valor'];
				}else{
					return '';
				}                
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error($e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error($e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error($e->get_mensaje());
            toba::logger()->error($e->get_mensaje());
            toba::db()->abortar_transaccion();
        }
	}
	
	public static function preseleccionar($id_recibo_cobro, $tipo_aplicacion, $fecha)
	{
		$sql = "BEGIN :resultado:= pkg_recibos_cobro.cargar_comprobantes(:id_recibo_cobro, :tipo_aplicacion,to_date(substr(:fecha,1,10),'yyyy-mm-dd')); END;";		

		$parametros = [ 
			[   'nombre' => 'resultado', 
				'tipo_dato' => PDO::PARAM_STR,
				'longitud' => 400,
				'valor' => ''],
			[   'nombre' => 'fecha', 
				'tipo_dato' => PDO::PARAM_STR,
				'longitud' => 32,
				'valor' => $fecha],
			[   'nombre' => 'tipo_aplicacion', 
				'tipo_dato' => PDO::PARAM_STR,
				'longitud' => 20,
				'valor' => $tipo_aplicacion],
			[   'nombre' => 'id_recibo_cobro', 
				'tipo_dato' => PDO::PARAM_STR,
				'longitud' => 20,
				'valor' => $id_recibo_cobro],
		];
		$resultado = ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
		return $resultado[0]['valor'];
	}
	
	static public function get_datos_extras_aplicacion_cobro_x_id($id_recibo_cobro, $id_aplicacion_cobro){
        if (isset($id_aplicacion_cobro)) {
            $sql = "SELECT	ap.anulado,
							ap.aplicado,
							ap.fecha_aplicado,
							ap.fecha_anulacion_aplicacion,
							ap.usuario_anula
                        FROM AD_APLICACIONES_COBRO ap
                        WHERE ap.id_recibo_cobro= ".$id_recibo_cobro."
						AND ap.id_aplicacion_cobro= ".$id_aplicacion_cobro;

            $datos = toba::db()->consultar_fila($sql);
            
            return $datos;
        }else{
            return array();
        }            
    }
	
	public static function get_cobros($id_recibo_cobro){
		if (isset($id_recibo_cobro)) {
            $sql = "SELECT	c.*,
            		CASE
                    WHEN kcb.id_cuenta_banco IS NOT NULL THEN cp.id_cuenta_banco || ' - ' || kcb.descripcion
                    ELSE ''
                    END as id_cuenta_banco
                    FROM AD_COBROS c,ad_comprobantes_pago cp, kr_cuentas_banco kcb 
                    WHERE c.id_recibo_cobro= ".$id_recibo_cobro."
                        AND c.id_comprobante_pago = cp.id_comprobante_pago (+)
                        AND kcb.id_cuenta_banco (+)= cp.id_cuenta_banco
					ORDER BY c.id_cobro desc";

            $datos = toba::db()->consultar($sql);
            
            return $datos;
        }else{
            return array();
        }            
		
	}
	
	public static function moneda_del_comprobante($id_comprobante_pago)
	{
		$sql= "SELECT pkg_ad_comprobantes_pagos.moneda_del_comprobante(".quote($id_comprobante_pago).") moneda FROM DUAL";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['moneda'];
	}
	
	public static function get_datos_cobro_x_id_cobro($id_cobro){
		if (isset($id_cobro)) {
            $sql = "SELECT	c.*,
							amp.*
                        FROM AD_COBROS c
						JOIN AD_MEDIOS_PAGO amp ON (c.cod_medio_pago = amp.cod_medio_pago)
                        WHERE c.id_cobro= ".$id_cobro;

            $datos = toba::db()->consultar_fila($sql);
            
            return $datos;
        }else{
            return array();
        }            
		
	}
	
	public static function calcular_importe_recibo_cobro($id_recibo_cobro){
		if (isset($id_recibo_cobro)) {
            $sql = "SELECT	rc.importe
                        FROM AD_RECIBOS_COBRO rc						
                        WHERE rc.id_recibo_cobro= ".$id_recibo_cobro;

            $datos = toba::db()->consultar_fila($sql);
            
            return $datos['importe'];
        }else{
            return 0;
        }   
	}
	
	public static function get_importe_retenciones_recibo_cobro($id_recibo_cobro){
		if (isset($id_recibo_cobro)) {
            $sql = "SELECT	rc.importe_retenciones
                        FROM AD_RECIBOS_COBRO rc						
                        WHERE rc.id_recibo_cobro= ".$id_recibo_cobro;

            $datos = toba::db()->consultar_fila($sql);
            
            return $datos['importe_retenciones'];
        }else{
            return 0;
        }   
	}
	
	public static function get_saldo_recibo_cobro($id_recibo_cobro, $id_transaccion, $id_cuenta_corriente, $aproado, $anulado){
		if (isset($id_recibo_cobro)&&($aproado == 'S')&&($anulado == 'N')) {
			$sql= "SELECT pkg_kr_transacciones.saldo_transaccion (".quote($id_transaccion).", ".quote($id_cuenta_corriente).", NULL) saldo_transaccion FROM DUAL;";
			$resultado = toba::db()->consultar_fila($sql);   
			return $resultado['saldo_transaccion'];
        }else{
            return 0;
        }   
	}

	public static function get_nada(){		
		return '1';
	}
	
	public static function confirmar_recibo_cobro($id_recibo_cobro, $con_transaccion=true){
		$sql= "BEGIN :resultado:= pkg_kr_trans_tesoreria.confirmar_recibo_cobro(:id_recibo_cobro); END;";
		$parametros = array (array(  'nombre' => 'resultado',
									 'tipo_dato' => PDO::PARAM_STR,
									 'longitud' => 4000,
							  		 'valor' => ''),
							 array(  'nombre' => 'id_recibo_cobro', 
									 'tipo_dato' => PDO::PARAM_STR,
									 'longitud' => 20,
									 'valor' => $id_recibo_cobro),										
									);
		ctr_procedimientos::ejecutar_procedimiento(null, $sql, $parametros, $con_transaccion);
	}
	
	public static function anular_recibo_cobro($id_recibo_cobro, $fecha, $con_transaccion=true)
	{
		$sql= "BEGIN :resultado:= pkg_kr_trans_tesoreria.anular_recibo_cobro(:id_recibo_cobro, to_date(substr(:fecha,1,10),'yyyy-mm-dd')); END;";
		$parametros = array ( 
			array(  'nombre' => 'resultado',
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 400,
					'valor' => ''),
			array(  'nombre' => 'id_recibo_cobro', 
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 20,
					'valor' => $id_recibo_cobro),
			array(  'nombre' => 'fecha', 
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 20,
					'valor' => $fecha),);
		ctr_procedimientos::ejecutar_procedimiento(null, $sql, $parametros, $con_transaccion);
        return 'OK';
	}
	
	
	static public function eliminar_forma_cobro($id_cobro, $con_transaccion = true) {
        if (isset($id_cobro) && !empty($id_cobro)) {
            $mensaje_error = 'Error eliminando el cobro.';



            try {
                if ($con_transaccion) {
                    toba::db()->abrir_transaccion();
                }
                $datos_cobro = dao_recibos_cobros::get_datos_cobro_x_id_cobro($id_cobro);
                if (isset($datos_cobro) && !empty($datos_cobro)) {
                    if (isset($datos_cobro['id_comprobante_pago'])) {
                        dao_cheques_propios::habilitar_cheque($datos_cobro['id_comprobante_pago'], !$con_transaccion);
                    }

                    // Elimino el pago
                    $sql_del = "DELETE FROM ad_cobros
								WHERE id_cobro = " . quote($id_cobro) . ";";
                    toba::db()->ejecutar($sql_del);
					
					// obtengo las condiciones del comprobante
					$condiciones_comprobante = dao_medios_pago::get_condiciones_comprobante_x_medio_pago($datos_cobro['cod_medio_pago'], 'COB');
					// Si esta seteado el comprobante de cobro entonces lo elimino (elimina comprobante de cobro y comprobante)
					if ($condiciones_comprobante['crea_comprobante'] == 'S' && isset($datos_cobro['id_comprobante_pago']) && !empty($datos_cobro['id_comprobante_pago'])) { // si se creo el comprobante
						 // eliminacion del comprobante de pago
						$resultado = dao_comprobantes_pago::eliminar_comprobante_pago($datos_cobro['id_comprobante_pago'], false);
						// Si retorna con error arroja una axcepcion y corta el proceso
						if (strcasecmp($resultado, 'S') <> 0){
							throw new toba_error('Error en la eliminacion del comprobante de cobro. '. $resultado);
						}
					}
                }
                if ($con_transaccion) {
                    toba::db()->cerrar_transaccion();
                }
            
            } catch (toba_error $e) {
                toba::notificacion()->error(ctr_procedimientos::procesar_error($e_db->get_mensaje()));
                toba::logger()->error($mensaje_error . ' ' . $e->get_mensaje());
                if ($con_transaccion) {
                    toba::db()->abortar_transaccion();
                }
            }
        }
    }
	
	public static function get_fecha_anulacion($id_recibo_cobro){				
		if (isset($id_recibo_cobro)) {
			$sql = "SELECT decode(PKG_GENERAL.VALOR_PARAMETRO_KR('AFI_FECHA_ANULACION_DEF'), 'FECHA_COMPROBANTE', to_char(fecha_comprobante,'yyyy-mm-dd'), to_char(sysdate,'yyyy-mm-dd')) fecha
				   FROM AD_RECIBOS_COBRO
				   WHERE id_recibo_cobro= ".quote($id_recibo_cobro);

		   $datos = toba::db()->consultar_fila($sql);

		   return $datos['fecha'];
		}
	}
	
	
	public static function recuperar_aperturas_caja($fecha_comprobante){
		
		if (isset($fecha_comprobante)) {			
			$sql= "SELECT apca.id_apertura, apca.id_caja, to_char(apca.fecha_apertura,'dd/mm/yyyy') fecha_apertura, to_char(apca.fecha_cierre, 'dd/mm/yyyy') fecha_cierre, apca.con_sello,
							PKG_KR_INTERFACE_RENTAS.importe_apertura_caja(apca.id_apertura) importe
				   FROM RE_APERTURAS_CAJA apca
				   WHERE trunc(apca.FECHA_CIERRE) = to_date(".quote($fecha_comprobante).",'dd/mm/yyyy')
					AND NOT EXISTS (SELECT 1 
									FROM ad_recibos_cobro_ape_liq rcal,
										 ad_recibos_cobro rc   
									WHERE rcal.id_recibo_cobro = rc.id_recibo_cobro 
									and rcal.id_apertura = apca.id_apertura
									AND rc.ANULADO = 'N'
									AND rc.APROBADO = 'S')";

		   $datos = toba::db()->consultar($sql);
		   return $datos;
		}
	}	
	
	public static function recuperar_liqudiaciones_recaudador($fecha_comprobante){
		
		if (isset($fecha_comprobante)) {			
			$sql= "SELECT lire.*, to_char(lire.fecha_aplicacion,'dd/mm/yyyy') fecha_aplicacion_fmt, to_char(lire.fecha_liquidacion,'dd/mm/yyyy') fecha_liquidacion_fmt
				   FROM RE_LIQUIDACIONES_RECAUDADOR lire
				   WHERE lire.FECHA_APLICACION = to_date(".quote($fecha_comprobante).",'dd/mm/yyyy')
					AND NOT EXISTS (SELECT 1 
									FROM ad_recibos_cobro rc,
										 ad_recibos_cobro_ape_liq rcal    
									WHERE rc.id_recibo_cobro = rcal.id_recibo_cobro 
									AND rcal.id_liquidacion_recaudador = lire.id_liquidacion_recaudador
									AND rc.ANULADO = 'N'
									AND rc.APROBADO = 'S')";

		   $datos = toba::db()->consultar($sql);

		   return $datos;
		}
	}
	
	public static function importar_aperturas_caja($id_recibo_cobro, $aperturas, $con_transaccion = true){
		$sql= "BEGIN :resultado:= pkg_kr_interface_rentas.importar_apertura_caja(:id_recibo_cobro, :aperturas); END;";

		$parametros = [   
		    [ 'nombre' => 'resultado',
			  'tipo_dato' => PDO::PARAM_STR,
			  'longitud' => 400,
			  'valor' => ''],
			[ 'nombre' => 'id_recibo_cobro', 
			  'tipo_dato' => PDO::PARAM_INT,
			  'longitud' => 20,
			  'valor' => $id_recibo_cobro],
		    [ 'nombre' => 'aperturas', 
			  'tipo_dato' => PDO::PARAM_STR,
			  'longitud' => 400,
			  'valor' => $aperturas],
		];
		ctr_procedimientos::ejecutar_procedimiento(null, $sql, $parametros, $con_transaccion);
        return 'OK';
	}
	
	public static function importar_liquidaciones_recaudador($id_recibo_cobro, $id_liquidaciones, $con_transaccion = true){

		$sql= "BEGIN :resultado:= pkg_kr_interface_rentas.importar_liquid_recaudador(:id_recibo_cobro, :id_liquidaciones); END;";
		$parametros = [ 
			[  'nombre' => 'resultado',
				'tipo_dato' => PDO::PARAM_STR,
				'longitud' => 400,
				'valor' => ''],
			[  'nombre' => 'id_recibo_cobro', 
				'tipo_dato' => PDO::PARAM_STR,
				'longitud' => 20,
				'valor' => $id_recibo_cobro],
			[  'nombre' => 'id_liquidaciones', 
				'tipo_dato' => PDO::PARAM_STR,
				'longitud' => 400,
				'valor' => $id_liquidaciones],
		];
		ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
		toba::notificacion()->info('Importado con exito.');
	}
	
	
	
	public static function modificar_fecha_anulacion($id_recibo_cobro, $fecha_anulacion, $con_transaccion = true)
	{
		$sql= "BEGIN :resultado:= pkg_recibos_cobro.modificar_fecha_anulacion (:id_recibo_cobro,:fecha_anulacion); END;";

		$parametros = [ 
			[   'nombre' => 'resultado',
				'tipo_dato' => PDO::PARAM_STR,
				'longitud' => 400,
				'valor' => ''],
			[   'nombre' => 'id_recibo_cobro', 
				'tipo_dato' => PDO::PARAM_STR,
				'longitud' => 20,
				'valor' => $id_recibo_cobro],
			[   'nombre' => 'fecha_anulacion', 
				'tipo_dato' => PDO::PARAM_STR,
				'longitud' => 20,
				'valor' => $fecha_anulacion],
		];
		ctr_procedimientos::ejecutar_procedimiento(null, $sql, $parametros);
		return 'OK';
	}
	
	static public function get_aplicacion_cobro_x_id($id_aplicacion){
		$sql= "SELECT apc.*
				FROM AD_APLICACIONES_COBRO apc
				WHERE apc.id_aplicacion_cobro= $id_aplicacion";
		
		$datos= toba::db()->consultar_fila($sql);
		
		return $datos;
	}

	

	static public function eliminar_aplicacion($id_aplicacion_cobro, $con_transaccion = true) {
        if (isset($id_aplicacion_cobro) && !empty($id_aplicacion_cobro)) {
            $mensaje_error = 'Error eliminando aplicaión.';
            try {
                if ($con_transaccion) {
                    toba::db()->abrir_transaccion();
                }
                $sql_del = "DELETE FROM ad_aplicaciones_cobro
							WHERE id_aplicacion_cobro = " . quote($id_aplicacion_cobro) . ";";
                toba::db()->ejecutar($sql_del);
				
                if ($con_transaccion) {
                    toba::db()->cerrar_transaccion();
                }
            } catch (toba_error_db $e_db) {
                toba::notificacion()->error($mensaje_error . ' ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
                toba::logger()->error($mensaje_error . ' ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
                if ($con_transaccion) {
                    toba::db()->abortar_transaccion();
                }
            } catch (toba_error $e) {
                toba::notificacion()->error($mensaje_error . ' ' . $e->get_mensaje());
                toba::logger()->error($mensaje_error . ' ' . $e->get_mensaje());
                if ($con_transaccion) {
                    toba::db()->abortar_transaccion();
                }
            }
        }
    }
	
	public static function detalles_de_aplicacion($id_comprobante, $tipo){
		if (isset($id_comprobante) && !empty($id_comprobante)) {
            $mensaje_error = 'Error modificando fecha.';
            try {               
				$sql= "BEGIN pkg_ad_comprobantes_pagos.detalles_de_aplicaciones(:tipo, :id_comprobante, :nro_comprobante, :fecha_comprobante, :imp_comp, :saldo_comp); END;";

				$parametros = array ( array(  'nombre' => 'tipo', 
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => $tipo),
										array(  'nombre' => 'id_comprobante', 
												'tipo_dato' => PDO::PARAM_INT,
												'longitud' => 20,
												'valor' => $id_comprobante),
										array(  'nombre' => 'nro_comprobante',
												'tipo_dato' => PDO::PARAM_INT,
												'longitud' => 20,
												'valor' => ''),
										array(  'nombre' => 'fecha_comprobante',
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 20,
												'valor' => ''),
										array(  'nombre' => 'imp_comp',
												'tipo_dato' => PDO::PARAM_INT,
												'longitud' => 20,
												'valor' => ''),
										array(  'nombre' => 'saldo_comp',
												'tipo_dato' => PDO::PARAM_INT,
												'longitud' => 20,
												'valor' => '')
								);
				$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);  
				
				return array('nro_comprobante' => $resultado[2]['valor'], 'fecha_comprobante' => $resultado[3]['valor'], 'imp_comp' => $resultado[4]['valor'], 'saldo_comp' => $resultado[5]['valor']);
            } catch (toba_error_db $e_db) {
                toba::notificacion()->error($mensaje_error . ' ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
                toba::logger()->error($mensaje_error . ' ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());                
            } catch (toba_error $e) {
                toba::notificacion()->error($mensaje_error . ' ' . $e->get_mensaje());
                toba::logger()->error($mensaje_error . ' ' . $e->get_mensaje());                
            }
        }
	}


	public static function get_aperturas_liquidaciones ($id_recibo_cobro)
	{
		$sql = "SELECT adtcal.*
  				  FROM ad_recibos_cobro_ape_liq adtcal
  				 where adtcal.id_recibo_cobro = ".$id_recibo_cobro;
		return toba::db()->consultar($sql);
	}
}

?>
