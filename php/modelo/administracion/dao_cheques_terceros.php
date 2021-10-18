<?php

class dao_cheques_terceros {
	static public function get_cheques_terceros($filtro= array()){        
        $where= "1=1";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ct', '1=1');
        
        $sql = "SELECT ct.*
                FROM AD_CHEQUES_TERCEROS ct
                WHERE  $where";
        
        $datos = toba::db()->consultar($sql);
        
        return $datos;        
    }

    static public function get_cheque_tercero_x_id($id_comprobante_pago) {
        if (isset($id_comprobante_pago)) {
            $sql = "SELECT ct.*,   ct.ID_COMPROBANTE_PAGO||' - '||ct.NRO_CHEQUE||' - '||to_char(ct.FECHA_EMISION, 'dd/mm/rr')||' - '||to_char(ct.FECHA_VENCIMIENTO,'dd/mm/rr')||' - '||ct.IMPORTE as lov_descripcion
                    FROM AD_CHEQUES_TERCEROS ct
                    WHERE ct.id_comprobante_pago = ".quote($id_comprobante_pago) .";";
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
	
	static public function get_datos_cheque_tercero_x_id($id_comprobante_pago) {
        if (isset($id_comprobante_pago)) {
            $sql = "SELECT	ct.*, 
							ct.ID_COMPROBANTE_PAGO||' - '||ct.NRO_CHEQUE||' - '||to_char(ct.FECHA_EMISION, 'dd/mm/rr')||' - '||to_char(ct.FECHA_VENCIMIENTO,'dd/mm/rr')||' - '||ct.IMPORTE as lov_descripcion,
							to_char(ct.FECHA_EMISION, 'dd/mm/rr') fecha_emision,
							to_char(ct.FECHA_VENCIMIENTO,'dd/mm/rr') fecha_vencimiento
                    FROM AD_CHEQUES_TERCEROS ct
                    WHERE ct.id_comprobante_pago = ".quote($id_comprobante_pago) .";";
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
	
	static public function get_lov_cheques_terceros_x_nombre($nombre, $filtro = array()) {
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
			$where.= " AND pkg_ad_comprobantes_pagos.ua_comprobante_pago(ID_COMPROBANTE_PAGO) = ".$filtro['cod_unidad_administracion']." and
					(('".$filtro['crea_comprobante']."' = 'S' and pkg_ad_comprobantes_pagos.obtener_estado_inicial(ID_COMPROBANTE_PAGO) = 'S' 
					AND PKG_AD_COMPROBANTES_PAGOS.ESTA_USADA(".$filtro['id_pago'].",".$filtro['id_cobro'].",ID_COMPROBANTE_PAGO) = 'N') 
					OR ('".$filtro['crea_comprobante']."' = 'N' and pkg_ad_comprobantes_pagos.obtener_estado_en_caja(ID_COMPROBANTE_PAGO) = 'S'))";
			
			unset($filtro['crea_comprobante']);
			unset($filtro['id_cobro']);
			unset($filtro['id_pago']);
			unset($filtro['cod_unidad_administracion']);
		}
		
		$where .= ' AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'ct', '1=1');

        $sql = "SELECT  ct.*, ct.NRO_CHEQUE||' - '||to_char(ct.FECHA_EMISION, 'dd/mm/rr')||' - '||to_char(ct.FECHA_VENCIMIENTO,'dd/mm/rr')||' - '||ct.IMPORTE as lov_descripcion
                FROM AD_CHEQUES_TERCEROS ct
                WHERE $where
                ORDER BY lov_descripcion ASC;";		
		
        $datos = toba::db()->consultar($sql);

        return $datos;
		
	}
}

?>
