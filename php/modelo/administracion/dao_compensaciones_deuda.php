<?php

class dao_compensaciones_deuda {
	static public function get_compensaciones_deuda($filtro= array()){        
        $where= "1=1";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'cd', '1=1');
        
        $sql = "SELECT cd.*
                FROM AD_COMPENSACINES_DEUDA cd
                WHERE  $where";
        
        $datos = toba::db()->consultar($sql);
        
        return $datos;        
    }

    static public function get_compensacion_deuda_x_id($id_comprobante_pago) {
        if (isset($id_comprobante_pago)) {
            $sql = "SELECT cd.*,   cd.ID_COMPROBANTE_PAGO||' - '||cd.nro_compensacion||' - '||to_char(cd.fecha_compensacion, 'dd/mm/rr')||' - '||cd.IMPORTE as lov_descripcion
                    FROM AD_COMPENSACINES_DEUDA cd       
                    WHERE cd.id_comprobante_pago = ".quote($id_comprobante_pago) .";";
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
	
	static public function get_lov_compensaciones_deuda_x_nombre($nombre, $filtro = array()) {
		if (isset($nombre)) {
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('id_comprobante_pago', $nombre);			
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_compensacion', $nombre);
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
		
		$where .= ' AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'cd', '1=1');

        $sql = "SELECT  cd.*, 
                        cd.ID_COMPROBANTE_PAGO||' - '||cd.nro_compensacion||' - '||to_char(cd.fecha_lote, 'dd/mm/rr')||' - '||cd.IMPORTE as lov_descripcion
                FROM AD_COMPENSACINES_DEUDA cd
                WHERE $where
                ORDER BY lov_descripcion ASC;";		
		
        $datos = toba::db()->consultar($sql);

        return $datos;
		
	}
	
	static public function get_datos_compensacion_deuda_x_id($id_comprobante_pago) {
        if (isset($id_comprobante_pago)) {
            $sql = "SELECT	cd.*, 
							cd.ID_COMPROBANTE_PAGO||' - '||cd.nro_compensacion||' - '||to_char(cd.fecha_compensacion, 'dd/mm/rr')||' - '||cd.IMPORTE as lov_descripcion,
							cd.nro_compensacion nro,
							to_char(cd.FECHA_compensacion, 'dd/mm/rrrr') fecha_format,
							cd.FECHA_compensacion fecha,
							cd.id_cuenta_corriente id_cuenta_corriente_desde,
							kcc.tipo_cuenta_corriente
                    FROM AD_COMPENSACINES_DEUDA cd   
					LEFT JOIN KR_CUENTAS_CORRIENTE kcc ON (cd.id_cuenta_corriente_cobrar = kcc.id_cuenta_corriente)
                    WHERE cd.id_comprobante_pago = ".quote($id_comprobante_pago) .";";
            $datos = toba::db()->consultar_fila($sql);
            return $datos;
        } else {
            return '';
        }
    }
}

?>
