<?php

/**
 * Description of dao_retenciones_efectuadas
 *
 * @author hmargiotta
 */
class dao_retenciones_efectuadas {
	static public function get_retenciones_efectuadas($filtro= array()){        
        $where= "1=1";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 're', '1=1');
        
        $sql = "SELECT re.*
                FROM AD_RETENCIONES_EFECTUADAS re
                WHERE  $where";
        
        $datos = toba::db()->consultar($sql);
        
        return $datos;        
    }

    static public function get_retencion_efectuada_x_id($id_comprobante_pago) {
        if (isset($id_comprobante_pago)) {
            $sql = "SELECT re.*,   re.ID_COMPROBANTE_PAGO||' - '||re.nro_comprobante||' - '||to_char(re.fecha_retencion, 'dd/mm/rr')||' - '||re.IMPORTE as lov_descripcion
                    FROM AD_RETENCIONES_EFECTUADAS re
                    WHERE re.id_comprobante_pago = ".quote($id_comprobante_pago) .";";
			
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
	
	static public function get_lov_retenciones_efectuadas_x_nombre($nombre, $filtro = array()) {
		if (isset($nombre)) {
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('id_comprobante_pago', $nombre);			
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_comprobante', $nombre);
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
			$where.= " AND pkg_ad_comprobantes_pagos.ua_comprobante_pago(ID_COMPROBANTE_PAGO) = ".$filtro['cod_unidad_administracion']."
			and (('".$filtro['crea_comprobante']."' = 'S' and pkg_ad_comprobantes_pagos.obtener_estado_inicial(ID_COMPROBANTE_PAGO) = 'S' 
			AND PKG_AD_COMPROBANTES_PAGOS.ESTA_USADA(".$filtro['id_pago'].",".$filtro['id_cobro'].",ID_COMPROBANTE_PAGO) = 'N') 
			OR ('".$filtro['crea_comprobante']."' = 'N' and pkg_ad_comprobantes_pagos.obtener_estado_en_caja(ID_COMPROBANTE_PAGO) = 'S'))";
			
			unset($filtro['crea_comprobante']);
			unset($filtro['id_cobro']);
			unset($filtro['id_pago']);
			unset($filtro['cod_unidad_administracion']);
		}
		
		$where .= ' AND '. ctr_construir_sentencias::get_where_filtro($filtro, 're', '1=1');

        $sql = "SELECT  re.*, 
                        re.ID_COMPROBANTE_PAGO||' - '||re.nro_comprobante||' - '||to_char(re.fecha_retencion, 'dd/mm/rr')||' - '||re.IMPORTE as lov_descripcion
                FROM AD_RETENCIONES_EFECTUADAS re
                WHERE $where
                ORDER BY lov_descripcion ASC;";		
		
        $datos = toba::db()->consultar($sql);

        return $datos;
		
	}
	
	static public function get_datos_retencion_efectuada_x_id($id_comprobante_pago) {
        if (isset($id_comprobante_pago)) {
            $sql = "SELECT	re.*,   
							re.ID_COMPROBANTE_PAGO||' - '||re.nro_comprobante||' - '||to_char(re.fecha_retencion, 'dd/mm/rr')||' - '||re.IMPORTE as lov_descripcion,
							re.nro_comprobante nro,
							to_char(re.FECHA_retencion, 'dd/mm/rrrr') fecha_format,
							re.FECHA_retencion fecha,
							arp.importe importe_retencion_pago,
							arp.base_imponible,
							arp.importe_fijo,
							arp.importe_alicuota,
							arp.importe_minimo,
							arp.alicuota,
							arp.excedente_imponible
                    FROM AD_RETENCIONES_EFECTUADAS re
					LEFT JOIN AD_RETENCIONES_PAGO arp ON (re.id_retencion_pago = arp.id_retencion_pago)
                    WHERE re.id_comprobante_pago = ".quote($id_comprobante_pago) .";";
			
            $datos = toba::db()->consultar_fila($sql);
            return $datos;
        } else {
            return '';
        }
    }
}

?>
