<?php

/**
 * Description of dao_documentos_propio
 *
 * @author hmargiotta
 */
class dao_documentos_propio {
	static public function get_documentos_propio($filtro= array()){        
        $where= "1=1";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'dp', '1=1');
        
        $sql = "SELECT dp.*
                FROM AD_DOCUMENTOS_PROPIO dp
                WHERE  $where";
        
        $datos = toba::db()->consultar($sql);
        
        return $datos;        
    }

    static public function get_documento_propio_x_id($id_comprobante_pago) {
        if (isset($id_comprobante_pago)) {
            $sql = "SELECT dp.*,   dp.ID_COMPROBANTE_PAGO||' - '||dp.nro_documento||' - '||to_char(dp.fecha_emision, 'dd/mm/rr')||' - '||to_char(dp.fecha_vencimiento, 'dd/mm/rr')||' - '||dp.IMPORTE as lov_descripcion
                    FROM AD_DOCUMENTOS_PROPIO dp
                    WHERE dp.id_comprobante_pago = ".quote($id_comprobante_pago) .";";
			
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
	
	static public function get_lov_documentos_propio_x_nombre($nombre, $filtro = array()) {
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
			OR ('".$filtro['crea_comprobante']."' = 'N' and (pkg_ad_comprobantes_pagos.obtener_estado_en_caja(ID_COMPROBANTE_PAGO) = 'S' and
			pkg_ad_comprobantes_pagos.obtener_estado_en_transito(ID_COMPROBANTE_PAGO) = 'S')))";
			
			unset($filtro['crea_comprobante']);
			unset($filtro['id_cobro']);
			unset($filtro['id_pago']);
			unset($filtro['cod_unidad_administracion']);
		}
		
		$where .= ' AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'dp', '1=1');

        $sql = "SELECT  dp.*, 
                        dp.ID_COMPROBANTE_PAGO||' - '||dp.nro_documento||' - '||to_char(dp.fecha_emision, 'dd/mm/rr')||' - '||to_char(dp.fecha_vencimiento, 'dd/mm/rr')||' - '||dp.IMPORTE as lov_descripcion
                FROM AD_DOCUMENTOS_PROPIO dp
                WHERE $where
                ORDER BY lov_descripcion ASC;";		
		
        $datos = toba::db()->consultar($sql);

        return $datos;
		
	}
	
	static public function get_datos_documento_propio_x_id($id_comprobante_pago) 
	{
        if (isset($id_comprobante_pago)) {
            $sql = "SELECT	dp.*,
							dp.ID_COMPROBANTE_PAGO||' - '||dp.nro_documento||' - '||to_char(dp.fecha_emision, 'dd/mm/rr')||' - '||to_char(dp.fecha_vencimiento, 'dd/mm/rr')||' - '||dp.IMPORTE as lov_descripcion,
							dp.nro_documento nro,
							to_char(dp.FECHA_EMISION, 'dd/mm/rrrr') fecha_emision_format,
							to_char(dp.FECHA_VENCIMIENTO,'dd/mm/rrrr') fecha_vencimiento_format
                    FROM AD_DOCUMENTOS_PROPIO dp
                    WHERE dp.id_comprobante_pago = ".quote($id_comprobante_pago) .";";
			
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
	
}

?>
