<?php

/**
 * Description of dao_transferencias_recibidas
 *
 * @author hmargiotta
 */
class dao_transferencias_recibidas {
	static public function get_transferencias_recibidas($filtro= array()){        
        $where= "1=1";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'tr', '1=1');
        
        $sql = "SELECT tr.*
                FROM AD_TRANSFERENCIAS_RECIBIDAS tr
                WHERE  $where";
        
        $datos = toba::db()->consultar($sql);
        
        return $datos;        
    }

    static public function get_transferencia_recibida_x_id($id_comprobante_pago) {
        if (isset($id_comprobante_pago)) {
            $sql = "SELECT tr.*,   tr.ID_COMPROBANTE_PAGO||' - '||tr.nro_transferencia||' - '||to_char(tr.fecha_transferencia, 'dd/mm/rr')||' - '||tr.IMPORTE as lov_descripcion
                    FROM AD_TRANSFERENCIAS_RECIBIDAS tr
                    WHERE tr.id_comprobante_pago = ".quote($id_comprobante_pago) .";";
			
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
	
	static public function get_lov_transferencias_recibidas_x_nombre($nombre, $filtro = array()) {
		if (isset($nombre)) {
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('id_comprobante_pago', $nombre);			
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_transferencia', $nombre);
			$where = "($trans_codigo OR $trans_nro)";
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
			$where.= " AND PKG_AD_COMPROBANTES_PAGOS.UA_COMPROBANTE_PAGO(ID_COMPROBANTE_PAGO) = ".$filtro['cod_unidad_administracion']."
			AND (('".$filtro['crea_comprobante']."' = 'S' AND PKG_AD_COMPROBANTES_PAGOS.OBTENER_ESTADO_INICIAL(ID_COMPROBANTE_PAGO) = 'S' 
			AND PKG_AD_COMPROBANTES_PAGOS.ESTA_USADA(".$filtro['id_pago'].",".$filtro['id_cobro'].",ID_COMPROBANTE_PAGO) = 'N') 
			OR ('".$filtro['crea_comprobante']."' = 'N' AND PKG_AD_COMPROBANTES_PAGOS.OBTENER_ESTADO_EN_CAJA(ID_COMPROBANTE_PAGO) = 'S'))";
			
			unset($filtro['crea_comprobante']);
			unset($filtro['id_cobro']);
			unset($filtro['id_pago']);
			unset($filtro['cod_unidad_administracion']);
		}
		
		$where .= ' AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'tr', '1=1');

        $sql = "SELECT  tr.*, 
                        tr.ID_COMPROBANTE_PAGO||' - '||tr.nro_transferencia||' - '||to_char(tr.fecha_transferencia, 'dd/mm/rr')||' - '||tr.IMPORTE as lov_descripcion
                FROM AD_TRANSFERENCIAS_RECIBIDAS tr
                WHERE $where
                ORDER BY lov_descripcion ASC;";		
		
        $datos = toba::db()->consultar($sql);

        return $datos;
		
	}
}

?>
