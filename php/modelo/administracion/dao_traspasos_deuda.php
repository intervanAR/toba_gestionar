<?php

/**
 * Description of dao_traspasos_deuda
 *
 * @author hmargiotta
 */
class dao_traspasos_deuda {
	static public function get_traspasos_deuda($filtro= array()){        
        $where= "1=1";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'td', '1=1');
        
        $sql = "SELECT td.*
                FROM AD_TRASPASOS_DEUDA td
                WHERE  $where";
        
        $datos = toba::db()->consultar($sql);
        
        return $datos;        
    }

    static public function get_traspaso_deuda_x_id($id_comprobante_pago) {
        if (isset($id_comprobante_pago)) {
            $sql = "SELECT td.*,   td.ID_COMPROBANTE_PAGO||' - '||td.nro_traspaso||' - '||to_char(td.fecha_traspaso, 'dd/mm/rr')||' - '||td.IMPORTE as lov_descripcion
                    FROM AD_TRASPASOS_DEUDA td            
                    WHERE td.id_comprobante_pago = ".quote($id_comprobante_pago) .";";
			
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
	
	static public function get_lov_traspasos_deuda_x_nombre($nombre, $filtro = array()) {
		if (isset($nombre)) {
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('id_comprobante_pago', $nombre);			
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_traspaso', $nombre);
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
		
		$where .= ' AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'td', '1=1');

        $sql = "SELECT  td.*, 
                        td.ID_COMPROBANTE_PAGO||' - '||td.nro_traspaso||' - '||to_char(td.fecha_traspaso, 'dd/mm/rr')||' - '||td.IMPORTE as lov_descripcion
                FROM AD_TRASPASOS_DEUDA td
                WHERE $where
                ORDER BY lov_descripcion ASC;";		
		
        $datos = toba::db()->consultar($sql);

        return $datos;
		
	}
	
	static public function get_datos_traspaso_deuda_x_id($id_comprobante_pago) {
        if (isset($id_comprobante_pago)) {
            $sql = "SELECT td.*,   td.ID_COMPROBANTE_PAGO||' - '||td.nro_traspaso||' - '||to_char(td.fecha_traspaso, 'dd/mm/rr')||' - '||td.IMPORTE as lov_descripcion,
							td.nro_traspaso nro,
							to_char(td.FECHA_traspaso, 'dd/mm/rrrr') fecha_format,
							td.FECHA_traspaso fecha,
							td.id_cuenta_corriente id_cuenta_corriente_desde,
							kcc.tipo_cuenta_corriente
                    FROM AD_TRASPASOS_DEUDA td
					LEFT JOIN KR_CUENTAS_CORRIENTE kcc ON (td.id_cuenta_corriente_hasta = kcc.id_cuenta_corriente)
                    WHERE td.id_comprobante_pago = ".quote($id_comprobante_pago) .";";
			
            $datos = toba::db()->consultar_fila($sql);
            return $datos;
        } else {
            return '';
        }
    }
}

?>
