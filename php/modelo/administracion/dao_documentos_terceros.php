<?php

/**
 * Description of dao_documentos_terceros
 *
 * @author hmargiotta
 */
class dao_documentos_terceros {
	static public function get_documentos_terceros($filtro= array()){        
        $where= "1=1";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'dt', '1=1');
        
        $sql = "SELECT dt.*
                FROM AD_DOCUMENTOS_TERCEROS dt
                WHERE  $where";
        
        $datos = toba::db()->consultar($sql);
        
        return $datos;        
    }

    static public function get_documento_tercero_x_id($id_comprobante_pago) {
        if (isset($id_comprobante_pago)) {
            $sql = "SELECT dt.*,   dt.ID_COMPROBANTE_PAGO||' - '||dt.nro_documento||' - '||to_char(dt.fecha_emision, 'dd/mm/rr')||' - '||to_char(dt.fecha_vencimiento, 'dd/mm/rr')||' - '||dt.IMPORTE as lov_descripcion
                    FROM AD_DOCUMENTOS_TERCEROS dt
                    WHERE dt.id_comprobante_pago = ".quote($id_comprobante_pago) .";";
			
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
	
	static public function get_lov_documentos_terceros_x_nombre($nombre, $filtro = array()) {
		if (isset($nombre)) {
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('id_comprobante_pago', $nombre);			
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_documento', $nombre);
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
		
		$where .= ' AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'dt', '1=1');

        $sql = "SELECT  dt.*, 
                        dt.ID_COMPROBANTE_PAGO||' - '||dt.nro_documento||' - '||to_char(dt.fecha_emision, 'dd/mm/rr')||' - '||to_char(dt.fecha_vencimiento, 'dd/mm/rr')||' - '||dt.IMPORTE as lov_descripcion
                FROM AD_DOCUMENTOS_TERCEROS dt
                WHERE $where
                ORDER BY lov_descripcion ASC;";		
		
        $datos = toba::db()->consultar($sql);

        return $datos;
		
	}
}

?>
