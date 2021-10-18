<?php

class dao_ajustes_precios {

    public static function get_ajustes_precios($filtro = array()) {
        $where = " 1=1 ";
        
		if (isset($filtro['ids_comprobantes'])) {
            $where .= "AND cap.nro_ajuste IN (" . $filtro['ids_comprobantes'] . ") ";
            unset($filtro['ids_comprobantes']);
        }
		
		if (isset($filtro['ambito_usuario']) && $filtro['ambito_usuario'] == '1') {
			$where .= " AND INSTR(Pkg_Usuarios.ambitos_usuario(".quote(strtoupper(toba::usuario()->get_id()))."), cap.cod_ambito) > 0";
			unset($filtro['ambito_usuario']);
		}
		
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'cap', '1=1');
        $sql_sel = "SELECT  cap.*,
							to_char(cap.fecha, 'DD/MM/YYYY') as fecha_format,
							cas.cod_ambito || '-' || cas.descripcion ambito,
							css.cod_sector || '-' || css.descripcion sector,
							co.numero,
							co.anio
					FROM co_ajustes_precio cap
					JOIN co_ambitos_seq cas ON cap.cod_ambito = cas.cod_ambito AND cap.seq_ambito = cas.seq_ambito
					JOIN co_sectores_seq css ON cap.cod_sector = css.cod_sector AND cap.seq_sector = css.seq_sector
					JOIN co_ordenes co ON cap.nro_orden = co.nro_orden
					WHERE $where
					ORDER BY nro_ajuste DESC;";
        $datos = toba::db()->consultar($sql_sel);
		
		foreach ($datos as $clave => $dato) {
			$datos[$clave]['observaciones_format'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($dato['observacion'], 'observaciones_'.$clave, 50, 1, true);
		}
        return $datos;
    }
	
	public static function get_estados_ajuste_precios() {
        $sql_sel = "SELECT  crc.rv_low_value estado,
							crc.rv_meaning lov_descripcion
					FROM cg_ref_codes crc
					WHERE crc.rv_domain = 'CO_ESTADO_AJUSTE'
					ORDER BY crc.rv_meaning ASC;";
        $datos = toba::db()->consultar($sql_sel);
		return $datos;
    }
	
	public static function get_datos_extras_encabezado_ajuste_precios($nro_ajuste) {
        if (isset($nro_ajuste)) {
            $sql_sel = "SELECT  cap.estado
						FROM co_ajustes_precio cap
						WHERE cap.nro_ajuste = " . quote($nro_ajuste) . ";";
            $datos = toba::db()->consultar_fila($sql_sel);
            return $datos;
        } else {
            return array();
        }
    }
	
	static public function cambiar_estado_ajustes($nro_ajuste, $estado) {
		if (isset($nro_ajuste) && isset($estado)) {
			$sql = "BEGIN :resultado := PKG_ORDENES.cambiar_estado_ajustes(:nro_ajuste, :estado); END;";
			
			$parametros = array(array(	'nombre' => 'estado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 32,
										'valor' => $estado),
								array(	'nombre' => 'nro_ajuste',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_ajuste),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al cambiar el estado del ajuste de precios.', false);
			if (isset($resultado[2]['valor']) && !empty($resultado[2]['valor'])) {
				return $resultado[2]['valor'];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}
	
	public static function get_datos_ajuste_item($fecha, $nro_orden, $nro_renglon) 
	{
        if (isset($fecha) && isset($nro_orden) && isset($nro_renglon)) {
			$where = '1=1';
			$where .= " AND cio.nro_orden = " . quote($nro_orden);
			$where .= " AND cio.nro_renglon = " . quote($nro_renglon);
			$sql = "SELECT	nvl(pkg_ordenes.saldo_recepcionar (cio.nro_orden, cio.nro_renglon),0) cantidad,
							nvl(pkg_Ordenes.Precio_Ajustado_1(cio.NRO_ORDEN , " . quote($fecha) . ",cio.NRO_RENGLON), 0) precio
					FROM CO_ITEMS_orden cio
					WHERE $where";
			$datos = toba::db()->consultar_fila($sql);
			return $datos;
		} else {
			return array();
		}
    }
	
	public static function get_descripcion_ajuste_item($item_articulo) {
        if (isset($item_articulo)) {
			$arr_item_articulo = explode('||', $item_articulo);
			$where = '1=1';
			if (!empty($arr_item_articulo[0])) {
				$where .= " AND coi.nro_orden = " . quote($arr_item_articulo[0]);
			}
			if (!empty($arr_item_articulo[1])) {
				$where .= " AND coi.nro_renglon = " . quote($arr_item_articulo[1]);
			}
			$sql = "SELECT	'Renglón: '|| coi.nro_renglon || ' Item: ' || coi.cod_articulo || ' - ' || replace(replace(coi.descripcion, '<','«'), '>', '»')  lov_descripcion
					FROM V_CO_ORDEN_ITEMS coi
					WHERE $where
					ORDER BY nro_orden, nro_renglon;";
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
	
	public static function get_preventivos_ajuste($nro_ajuste) {
        if (isset($nro_ajuste)) {
			$sql_sel = "select	capp.*,
								CASE
									WHEN ap.anulado = 'S' THEN 'Si'
									ELSE 'No'
								END anulado_preventivo,
								CASE
									WHEN ap.ID_PREVENTIVO_REI IS NOT NULL THEN 'Si'
									ELSE 'No'
								END reimputacion_preventivo,
								to_char(ap.FECHA_ANULACION, 'DD/MM/YYYY') as fecha_anulacion_preventivo,
								to_char(ap.FECHA_COMPROBANTE, 'DD/MM/YYYY') as fecha_preventivo,
								ap.nro_preventivo,
								trim(to_char(ap.importe, '$999,999,999,990.00')) as importe_preventivo
						from   co_ajustes_precio_preventivos capp
						join ad_preventivos ap ON (capp.id_preventivo = ap.id_preventivo) 
						where  nro_ajuste = " . quote($nro_ajuste) . ";";
			$datos = toba::db()->consultar($sql_sel);
			return $datos;
		} else {
			return array();
		}
    }
	
	public static function get_compromisos_ajuste($nro_ajuste) {
        if (isset($nro_ajuste)) {
			$sql_sel = "select	capc.*,
								CASE
									WHEN ac.anulado = 'S' THEN 'Si'
									ELSE 'No'
								END anulado_compromiso,
								to_char(ac.FECHA_ANULACION, 'DD/MM/YYYY') as fecha_anulacion_compromiso,
								to_char(ac.FECHA_COMPROBANTE, 'DD/MM/YYYY') as fecha_compromiso,
								ac.nro_compromiso,
								trim(to_char(ac.importe, '$999,999,999,990.00')) as importe_compromiso,
								kcc.nro_cuenta_corriente || ' - ' || kcc.descripcion cta_cte_compromiso
						from   co_ajustes_precio_compromisos capc
						join ad_compromisos ac ON (capc.id_compromiso = ac.id_compromiso) 
						join kr_cuentas_corriente kcc ON (ac.id_cuenta_corriente = kcc.id_cuenta_corriente) 
						where  nro_ajuste = " . quote($nro_ajuste) . ";";
			$datos = toba::db()->consultar($sql_sel);
			return $datos;
		} else {
			return array();
		}
    }
	
}

?>
