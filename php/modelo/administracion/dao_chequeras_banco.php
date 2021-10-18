<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of dao_chequeras_banco
 *
 * @author lwolcan
 */
class dao_chequeras_banco {

	static public function get_chequeras($filtro = array()) {
		$where = "1=1";
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'cp', '1=1');
		$sql = "SELECT  cp.*, CASE
			            WHEN cp.activa = 'S'
			               THEN 'Si'
			            ELSE 'No'
			         END activa_format,
			         CASE
			            WHEN cp.pago_diferido = 'S'
			               THEN 'Si'
			            ELSE 'No'
			         END pago_diferido_format,
			         CASE
			            WHEN cp.generada = 'S'
			               THEN 'Si'
			            ELSE 'No'
			         END generada_format,
			         (SELECT descripcion
			            FROM kr_monedas
			           WHERE cod_moneda = cp.cod_moneda) moneda_format,
			         cp.id_cuenta_banco || ' - ' || krcb.descripcion cuenta_format,
			         krcb.id_banco || ' - ' || krb.descripcion banco_format
			    FROM ad_chequeras_banco cp, kr_cuentas_banco krcb LEFT JOIN kr_bancos krb
			         ON krcb.id_banco = krb.id_banco
			   WHERE cp.id_cuenta_banco = krcb.id_cuenta_banco AND  $where
		       ORDER BY 1 DESC ";

		$datos = toba::db()->consultar($sql);

		return $datos;
	}

	public static function crear_chequera($id_chequera_banco) {
		try {
			toba::db()->abrir_transaccion();
			$sql = "BEGIN :resultado := pkg_ad_cheques.crear_cheques (:id_chequera_banco); END;";
			$parametros = array(array('nombre' => 'id_chequera_banco',
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 32,
					'valor' => $id_chequera_banco),
				array('nombre' => 'resultado',
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 4000,
					'valor' => ''),
			);
			$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

			if ($resultado[1]['valor'] != 'OK') {
				toba::db()->abortar_transaccion();
			} else {
				toba::db()->cerrar_transaccion();
			}

			return $resultado[1]['valor'];
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

	public static function anular_cheque($id_cheque, $con_transaccion = true) {
		if (isset($id_cheque)) {
			try {
				if ($con_transaccion) {
					toba::db()->abrir_transaccion();
				}
				$sql_upd = "UPDATE AD_CHEQUES
                                SET anulado = 'S',
                                    fecha_anulado = TRUNC(SYSDATE)
                              WHERE id_cheque =" . quote($id_cheque) . ";";
				toba::db()->ejecutar($sql_upd);
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

	public static function sacar_impreso_cheque($id_cheque, $con_transaccion = true) {
		if (isset($id_cheque)) {
			try {
				if ($con_transaccion) {
					toba::db()->abrir_transaccion();
				}
				$sql_upd = "UPDATE AD_CHEQUES
									SET impreso = 'N',
										fecha_impreso = NULL
									WHERE id_cheque =" . quote($id_cheque) . ";";
				toba::db()->ejecutar($sql_upd);
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

	public static function reusar_cheque($id_cheque, $con_transaccion = true) {
		if (isset($id_cheque)) {
			// Determino si tiene asociado un recibo de pago aprobado.	
			$sql = "SELECT NVL(COUNT(1),0) valor
        FROM ad_cheques_propios acp,
             ad_pagos ap,
             ad_recibos_pago arp
        WHERE acp.id_comprobante_pago = ap.id_comprobante_pago
        AND ap.id_recibo_pago = arp.id_recibo_pago
        AND arp.aprobado = 'S'
        AND arp.anulado = 'N'
        AND acp.id_cheque = $id_cheque;";

			$l_existe_rp = toba::db()->consultar_fila($sql);

			//Determino si tiene asociado una transferencia aprobada.
			$sql = "SELECT NVL(COUNT(1),0) valor
        FROM ad_cheques_propios acp,
             ad_transferencias_fondos atf
        WHERE acp.id_comprobante_pago = atf.id_comprobante_pago
        --AND atf.confirmada = 'S'
        AND atf.anulada = 'N'
        AND acp.id_cheque = $id_cheque;";


			$l_existe_tf = toba::db()->consultar_fila($sql);
            			
			if ($l_existe_rp['valor'] != 0 || $l_existe_tf['valor'] != 0) {
				toba::notificacion()->info('El cheque esta asociado a un Recibo de Pago o una Transferencia de Fondos aprobada.');
				return;
			} else {
				try {
					if ($con_transaccion) {
						toba::db()->abrir_transaccion();
					}
					$sql_upd = "UPDATE AD_CHEQUES
								SET anulado = 'N',
									utilizado = 'N',
									observacion = NULL,
									fecha_anulado = NULL
								WHERE id_cheque =" . quote($id_cheque) . ";";
					toba::db()->ejecutar($sql_upd);
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
	}
	
	
	
	
	/*
	 * Lov's y funciones para la seleccion de rangos de cheques a imprimir 
	 */
	
	public static function get_lov_cheques_x_id ($id_cheque){
		if (isset($id_cheque) && !empty($id_cheque)){
			$sql = "SELECT adc.NRO_CHEQUE as lov_descripcion
					FROM AD_CHEQUES adc
					WHERE adc.nro_cheque = ".quote($id_cheque);
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}else
			return null;
	}
	
	public static function get_lov_cheques_x_nombre ($nombre, $filtro = array()){
		$where = "";
		if (isset($nombre)) {
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('adc.nro_cheque', $nombre);
            $where = "($trans_nro)";
        } else {
        	$where = " 1=1 ";
        }
            
		if (isset($filtro['nro_cheque_desde'])){
			$where .= " and adc.NRO_CHEQUE >= ".$filtro['nro_cheque_desde'];	
		}
		if (isset($filtro['id_chequera_banco'])){
			$where .= " and adc.ID_CHEQUERA_BANCO = ".$filtro['id_chequera_banco'];	
		}
		
		$sql = "SELECT adc.nro_cheque, adc.NRO_CHEQUE as lov_descripcion
				FROM AD_CHEQUES adc
				WHERE $where AND adc.impreso = 'N'
				ORDER BY NRO_CHEQUE ASC"; 
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	
	static public function get_id_cheque_x_nro ($nro_cheque, $id_chequera_banco){
		if (isset($nro_cheque) && !empty($nro_cheque) && isset($id_chequera_banco) && !empty($id_chequera_banco)){
			$sql = "SELECT ID_CHEQUE
					FROM AD_CHEQUES 
					WHERE nro_cheque = $nro_cheque 
						 and id_chequera_banco = $id_chequera_banco";
			$datos = toba::db()->consultar_fila($sql);
			if (count($datos) > 1){
				toba::notificacion()->info("Se encontro mas de un cheque con el mimso número.");
				return null;
			}
			return $datos['id_cheque'];  
		}else{
			return null;
		}
	}
	
	static public function get_min_cheque_no_impreso ($id_chequera_banco){
		$sql = "SELECT MIN(nro_cheque) nro_cheque
				FROM AD_CHEQUES
				WHERE id_chequera_banco = $id_chequera_banco
				AND impreso = 'N';";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['nro_cheque'];
	}
	
	
	static public function get_reporte_imprimir_cheque ($id_chequera_banco){
		if (isset($id_chequera_banco) && !empty($id_chequera_banco)){
			$sql = "SELECT nombre_reporte   
				    FROM AD_CHEQUERAS_BANCO ACB, AD_MODELOS_CHEQUE AMC
	      			WHERE ACB.ID_MODELO = AMC.ID_MODELO
	         			  AND ACB.ID_CHEQUERA_BANCO = $id_chequera_banco";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['nombre_reporte'];
		}else{
			return null;
		}
	}
	
	static public function cheques_sin_usar ($id_chequera_banco, $id_cheque_desde, $id_cheque_hasta){
		$sql = " SELECT COUNT(1) cantidad 
			     FROM AD_CHEQUES 
			     WHERE ID_CHEQUERA_BANCO = $id_chequera_banco 
			     	 	AND  ID_CHEQUE BETWEEN $id_cheque_desde AND $id_cheque_hasta AND UTILIZADO = 'N';";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cantidad'];
	}
	
	static public function cheques_impresos ($id_chequera_banco, $id_cheque_desde, $id_cheque_hasta){
		$sql = " SELECT COUNT(1) cantidad
			     FROM AD_CHEQUES 
			     WHERE ID_CHEQUERA_BANCO = $id_chequera_banco
					     AND  ID_CHEQUE BETWEEN $id_cheque_desde AND $id_cheque_hasta 
					     AND impreso = 'S';";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cantidad'];
	}
	
	static public function cheques_actualizar_estado_impreso ($id_chequera_banco, $id_cheque_desde, $id_cheque_hasta)
	{
		try {
			toba::db()->abrir_transaccion();
			$sql = "UPDATE ad_cheques SET impreso = 'S', fecha_impreso = trunc(sysdate)  
		       		WHERE ID_CHEQUERA_BANCO = $id_chequera_banco 
		        			AND id_cheque BETWEEN $id_cheque_desde AND $id_cheque_hasta AND impreso = 'N';";
			toba::logger()->debug("Actualizar estado de impreso en los cehques $sql");
			toba::db()->ejecutar($sql);
			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::notificacion()->error("No se pudo actualizar es estado de impreso en los cheques");
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}	
	
	
}

?>
