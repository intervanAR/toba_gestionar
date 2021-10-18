<?php

class dao_cheques_propios {
    static public function get_cheques_propios($filtro= array()){        
        $where= "1=1";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'cp', '1=1');
        
        $sql = "SELECT cp.*
                FROM AD_CHEQUES_PROPIOS cp
                WHERE  $where";
        
        $datos = toba::db()->consultar($sql);
        
        return $datos;        
    }

    static public function get_cheque_propio_x_id($id_comprobante_pago) {
        if (isset($id_comprobante_pago)) {
            $sql = "SELECT cp.*,   cp.ID_COMPROBANTE_PAGO||' - '||ch.NRO_CHEQUE||' - '||to_char(cp.FECHA_EMISION, 'dd/mm/rr')||' - '||to_char(cp.FECHA_VENCIMIENTO,'dd/mm/rr')||' - '||cp.IMPORTE as lov_descripcion
                    FROM AD_CHEQUES_PROPIOS cp, AD_CHEQUES ch                    
                    WHERE cp.id_comprobante_pago = ".quote($id_comprobante_pago) ."
                    AND ch.id_cheque(+) = cp.id_cheque;";
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
	
	static public function get_lov_cheques_propios_x_nombre($nombre, $filtro = array()) {
		if (isset($nombre)) {
			//$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('id_comprobante_pago', $nombre);			
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_cheque', $nombre);
			$where = "($trans_nro)";
        } else {
            $where = '1=1';
        }
		
		if (isset($filtro['crea_comprobante'])) {
			if (!isset($filtro['id_pago']) || empty($filtro['id_pago'])) {
				$filtro['id_pago'] = 'null';
			}
			if (!isset($filtro['id_cobro']) || empty($filtro['id_cobro'])) {
				$filtro['id_cobro'] = 'null';
			}
			$where.= " AND pkg_ad_comprobantes_pagos.ua_comprobante_pago(ID_COMPROBANTE_PAGO) = ".$filtro['cod_unidad_administracion']."
			and (('".$filtro['crea_comprobante']."' = 'S' and pkg_ad_comprobantes_pagos.obtener_estado_inicial(ID_COMPROBANTE_PAGO) = 'S'
			AND PKG_AD_COMPROBANTES_PAGOS.ESTA_USADA(".$filtro['id_pago'].",".$filtro['id_cobro'].",ID_COMPROBANTE_PAGO) = 'N') 
			OR ('".$filtro['crea_comprobante']."' = 'N' and (pkg_ad_comprobantes_pagos.obtener_estado_en_caja(ID_COMPROBANTE_PAGO) = 'N' and
			  pkg_ad_comprobantes_pagos.obtener_estado_en_transito(ID_COMPROBANTE_PAGO) = 'S')))";
			
			unset($filtro['crea_comprobante']);
			unset($filtro['id_cobro']);
			unset($filtro['id_pago']);
			unset($filtro['cod_unidad_administracion']);
		}
		
		$where .= ' AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'cp', '1=1');

        $sql = "SELECT  cp.*, ch.NRO_CHEQUE||' - '||to_char(cp.FECHA_EMISION, 'dd/mm/rr')||' - '||to_char(cp.FECHA_VENCIMIENTO,'dd/mm/rr')||' - '||cp.IMPORTE as lov_descripcion
                FROM AD_CHEQUES_PROPIOS cp, AD_CHEQUES ch
                WHERE ch.id_cheque= cp.id_cheque
				AND 
				$where
                ORDER BY lov_descripcion ASC;";		
		
        $datos = toba::db()->consultar($sql);

        return $datos;
		
	}
	
	//Se obtiene la cantidad de cheques a imprimir.   
    //Si imprime desde pagos, ha seleccionado comprobante por lo tanto la cantidad será 1.
    //Si imprime desde recibo, la cantidad puede ser de 0 a n.
	static public function get_cantidad_cheques_asignar($filtro=array())
	{
		$where = " 1 = 0 "; // genero un false
		$join = '';
		if (isset($filtro['incluir_pagos']) && isset($filtro['id_recibo_pago']) && isset($filtro['pago_diferido']) && isset($filtro['id_cuenta_banco']) && isset($filtro['cod_moneda'])) {
			$join .= ', 
					 ad_pagos pag';
			$where = "  copa.id_comprobante_pago = pag.id_comprobante_pago 
						and pag.id_recibo_pago = " . quote($filtro['id_recibo_pago']) . "              
						and pag.anulado = 'N' 
						and ((" . quote($filtro['pago_diferido']) . " = 'N' and trunc(chpr.fecha_emision) = trunc(chpr.fecha_vencimiento)) 
							or (" . quote($filtro['pago_diferido']) . " = 'S' and trunc(chpr.fecha_emision) < trunc(chpr.fecha_vencimiento)))
						and chpr.id_cuenta_banco = " . quote($filtro['id_cuenta_banco']) . "        
					    and copa.cod_moneda = " . quote($filtro['cod_moneda']) . " ";
			if (isset($filtro['id_comprobante_pago'])) {
				$where .= " and pag.id_comprobante_pago = " . quote($filtro['id_comprobante_pago']) . " ";
			}
			unset($filtro['incluir_pagos']);
			unset($filtro['id_recibo_pago']);
			unset($filtro['pago_diferido']);
			unset($filtro['id_cuenta_banco']);
			unset($filtro['cod_moneda']);
			unset($filtro['id_comprobante_pago']);
		}
		$sql = "select count(1) as cantidad_cheques_asignar
				from ad_cheques_propios chpr, 
					 ad_comprobantes_pago copa
					 $join
				where $where 
				AND chpr.id_comprobante_pago = copa.id_comprobante_pago 
				and chpr.impreso = 'N' 
				and chpr.id_cheque is null
				and copa.tipo_comprobante_pago = 'CHP'
				and copa.anulado = 'N';";
		$datos = toba::db()->consultar_fila($sql);
		if (isset($datos) && !empty($datos) && isset($datos['cantidad_cheques_asignar'])) {
			return $datos['cantidad_cheques_asignar'];
		} else {
			return 0;
		}
	}
	
	static public function get_cheque_impreso_x_id($id_comprobante_pago) {
        if (isset($id_comprobante_pago)) {
            $sql = "SELECT cp.impreso
                    FROM AD_CHEQUES_PROPIOS cp, AD_CHEQUES ch                    
                    WHERE cp.id_comprobante_pago = ".quote($id_comprobante_pago) ."
                    AND ch.id_cheque= cp.id_cheque;";
            $datos = toba::db()->consultar_fila($sql);
            if (isset($datos) && !empty($datos) && isset($datos['impreso'])) {
                return $datos['impreso'];
            } else {
                return 'N';
            }
        } else {
            return 'N';
        }
    }
	
	static public function get_datos_cheque_propio_x_id($id_comprobante_pago) 
	{
        if (isset($id_comprobante_pago)) {
            $sql = "SELECT	cp.*,   
							cp.ID_COMPROBANTE_PAGO||' - '||ch.NRO_CHEQUE||' - '||to_char(cp.FECHA_EMISION, 'dd/mm/rr')||' - '||to_char(cp.FECHA_VENCIMIENTO,'dd/mm/rr')||' - '||cp.IMPORTE as lov_descripcion,
							ch.id_chequera_banco id_chequera,
							to_char(cp.FECHA_EMISION, 'dd/mm/rrrr') fecha_emision_format,
							to_char(cp.FECHA_VENCIMIENTO,'dd/mm/rrrr') fecha_vencimiento_format
                    FROM AD_CHEQUES_PROPIOS cp, AD_CHEQUES ch                    
                    WHERE cp.id_comprobante_pago = ".quote($id_comprobante_pago) ."
                    AND ch.id_cheque= cp.id_cheque;";
			$datos = toba::db()->consultar_fila($sql);
			if (isset($datos) && !empty($datos)) {
				return $datos;
			} else {
				return array();
			}
        } else {
            return array();
        }
    }
	
	static public function habilitar_cheque($id_comprobante_pago, $con_transaccion=true)
	{
		if (isset($id_comprobante_pago)) {
			$mensaje_error = 'Error habilitando el cheque propio.';
			try {
				if ($con_transaccion) {
					toba::db()->abrir_transaccion();
				}
				
				// Busco el cheque para habilitar
				$datos_cheque_propio = self::get_datos_cheque_propio_x_id($id_comprobante_pago);
				if (isset($datos_cheque_propio) && !empty($datos_cheque_propio) && isset($datos_cheque_propio['id_cheque']) && empty($datos_cheque_propio['id_cheque'])) {
					
					// Habilito el cheque
					$sql_upd = "UPDATE AD_CHEQUES
								SET UTILIZADO = 'N', 
									IMPRESO = 'N', 
									OBSERVACION =NULL, 
									FECHA_IMPRESO = NULL
								WHERE ID_CHEQUE = " . quote($datos_cheque_propio['id_cheque']) .";

								UPDATE AD_CHEQUES_PROPIOS 
								SET ID_CHEQUE = NULL
								WHERE ID_COMPROBANTE_PAGO = " . quote($id_comprobante_pago) .";";
					toba::db()->ejecutar($sql_upd);
				}
				
				if ($con_transaccion) {
					toba::db()->cerrar_transaccion();
				}
			} catch (toba_error_db $e_db) {
				toba::notificacion()->error($mensaje_error .' '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
				toba::logger()->error($mensaje_error .' '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
				if ($con_transaccion) {
					toba::db()->abortar_transaccion();
				}
			} catch (toba_error $e) {
				toba::notificacion()->error($mensaje_error .' '.$e->get_mensaje());
				toba::logger()->error($mensaje_error .' '.$e->get_mensaje());
				if ($con_transaccion) {
					toba::db()->abortar_transaccion();
				}
			}
		}
	}
	
	static public function marcar_cheque($id_comprobante_pago, $id_cheque, $con_transaccion=true){
        try {			
            	if ($con_transaccion) {
					toba::db()->abrir_transaccion();
				}
		//verrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrr		
		  ///  $sql = 'select * from ad_comprobantes_pago where id_comprobante_pago = '.$id_comprobante_pago;
		  //	$datos = toba::db()->consultar($sql);
			
            $sql = "BEGIN :resultado := pkg_ad_cheques.asignar_cheques_manual(:id_comprobante_pago, :id_cheque); END;";
            $parametros = array(array('nombre' => 'id_comprobante_pago',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $id_comprobante_pago),
                array('nombre' => 'id_cheque',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $id_cheque),
                array('nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''),
            );
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
			
		if ($resultado[2]['valor']== 'OK') {
           if ($con_transaccion) {
					toba::db()->cerrar_transaccion();
				}
		} else {
			if ($con_transaccion) {
               toba::db()->abortar_transaccion();
			}
		}	      
		 //  print_r($datos);	
         // return null;
            return $resultado[2]['valor'];
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
			if ($con_transaccion) {
               toba::db()->abortar_transaccion();
			}
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
            if ($con_transaccion) {
               toba::db()->abortar_transaccion();
			}
        }
    }


    static public function get_formas_emision_che ($filtro=[])
    {
        $where = "1= 1";
        $where.= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'adfec', '1=1');
        $sql = "SELECT adfec.*, decode(adfec.activa,'S','Si','No') activa_format
				  FROM AD_FORMAS_EMISION_CHEQUE adfec
				WHERE $where";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }
}

?>
