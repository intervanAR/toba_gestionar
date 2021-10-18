<?php

class dao_pedidos_cotizacion {
	
	public static function get_pedidos_cotizacion($filtro = array(), $orden = array()) {
		$desde= null;
		$hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}
        $where = self::armar_where($filtro);
        
        $sql_sel = "SELECT  cc.NRO_COMPRA, cc.COD_SECTOR, cc.SEQ_SECTOR, cc.TIPO_COMPRA, cc.NUMERO, cc.ANIO, cc.COD_AMBITO, cc.SEQ_AMBITO, cc.COD_AMBITO_EJECUTA, cc.SEQ_AMBITO_EJECUTA, cc.FECHA_PUBLICACION, cc.NRO_SOLICITUD, cc.DESTINO_COMPRA, cc.INTERNA, cc.PRESUPUESTARIO, cc.OBJETO, cc.RESPONSABLE, cc.LUGAR_APERTURA, cc.FECHA_PRESENTACION, to_char(cc.FECHA_APERTURA, 'yyyy-mm-dd hh24:mi:ss') FECHA_APERTURA, cc.CONSULTAS, cc.PLIEGO, cc.VALOR_PLIEGO, cc.VALOR_COMPRA, cc.NRO_RESOLUCION, cc.FECHA_RESOLUCION, cc.PLAZO_ENTREGA, cc.CONDICIONES_PAGO, cc.MANTENIMIENTO_OFERTA, cc.ESTADO, cc.ORIGEN_SISTEMA, cc.LLAMADO, cc.FINALIZADA, cc.SELLADO, cc.LUGAR_ENTREGA, cc.OBSERVACIONES, cc.FOJAS, cc.TEXTO_CONTRATO, cc.TEXTO_PLIEGO, cc.TITULO_CONTRATO, cc.TITULO_PLIEGO, cc.SOBRES, cc.ID_EXPEDIENTE, cc.FECHA_IMPUTACION, cc.FECHA, cc.COD_MONEDA, cc.COTIZACION,
							to_char(cc.fecha, 'DD/MM/YYYY') as fecha_format,
							to_char(cc.fecha_imputacion, 'DD/MM/YYYY') as fecha_imputacion_format,
							to_char(cc.fecha_resolucion, 'DD/MM/YYYY') as fecha_resolucion_format,
							to_char(cc.fecha_apertura, 'DD/MM/YYYY') as fecha_apertura_format,
							to_char(cc.fecha_publicacion, 'DD/MM/YYYY') as fecha_publicacion_format,
							to_char(cc.fecha_presentacion, 'DD/MM/YYYY') as fecha_presentacion_format,
							trim(to_char(cc.valor_compra, '$999,999,999,990.00')) as valor_compra_format,
							CASE
								WHEN cc.interna = 'S' THEN 'Si'
								ELSE 'No'
							END interna_format,
							CASE
								WHEN cc.presupuestario = 'S' THEN 'Si'
								ELSE 'No'
							END presupuestario_format,
							ke.nro_expediente as nro_expediente,
							cas.cod_ambito || '-' || cas.descripcion ambito,
							css.cod_sector || '-' || css.descripcion sector
					FROM co_compras cc
					JOIN co_ambitos_seq cas ON cc.cod_ambito = cas.cod_ambito AND cc.seq_ambito = cas.seq_ambito
					JOIN co_sectores_seq css ON cc.cod_sector = css.cod_sector AND cc.seq_sector = css.seq_sector
					LEFT JOIN kr_expedientes ke ON cc.id_expediente = ke.id_expediente
					WHERE $where
					ORDER BY nro_compra DESC";

        $sql = dao_varios::paginador($sql_sel, null, $desde, $hasta, null, $orden);
        $datos = toba::db()->consultar($sql);
        //print_r($datos);
		
		foreach ($datos as $clave => $dato) {
			$datos[$clave]['observaciones_format'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($dato['observaciones'], 'observaciones_'.$clave, 50, 1, true);
				$datos[$clave]['objeto_format'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($dato['objeto'], 'objeto_'.$clave, 30, 1, true);
		}
        return $datos;
    }
    
	public static function armar_where ($filtro = array())
	{
		$where = " 1=1 ";
		if (isset($filtro['ids_comprobantes'])) {
            $where .= "AND cc.nro_compra IN (" . $filtro['ids_comprobantes'] . ") ";
            unset($filtro['ids_comprobantes']);
        }
		if (isset($filtro['ambito_usuario']) && $filtro['ambito_usuario'] == '1') {
			$where .= " AND INSTR(Pkg_Usuarios.ambitos_usuario(".quote(strtoupper(toba::usuario()->get_id()))."), cc.cod_ambito) > 0";
			unset($filtro['ambito_usuario']);
		}
		if (isset($filtro['excluir_nro_compra'])) {
            $where .= " AND cc.nro_compra <> " . $filtro['excluir_nro_compra'] . " ";
            unset($filtro['excluir_nro_compra']);
        }
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'cc', '1=1');
		return $where;
	}
	
	public static function get_cantidad ($filtro = array())
	{
		$where = self::armar_where($filtro);
		
		$sql =" SELECT COUNT(*) cantidad
		  		  FROM co_compras cc
				  JOIN co_ambitos_seq cas ON cc.cod_ambito = cas.cod_ambito AND cc.seq_ambito = cas.seq_ambito
				  JOIN co_sectores_seq css ON cc.cod_sector = css.cod_sector AND cc.seq_sector = css.seq_sector
		  	 LEFT JOIN kr_expedientes ke ON cc.id_expediente = ke.id_expediente 
		   	WHERE $where ";
		
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cantidad'];
	}
	
    public static function get_monto_limite_compra($destino_compra, $tipo_compra) {
		if (isset($destino_compra) && isset($tipo_compra)) {
			$sql_sel = "SELECT monto_maximo
						FROM CO_LIMITES_COMPRA
						WHERE destino_compra = " . quote($destino_compra) . "
						AND   tipo_compra    = " . quote($tipo_compra) . ";";
			$datos = toba::db()->consultar_fila($sql_sel);
			if (isset($datos) && !empty($datos) && isset($datos['monto_maximo'])) {
				return $datos['monto_maximo'];
			} else {
				return 0;
			}
		} else {
			return 0;
		}
    }
	
	public static function get_valor_sellado_oferta_compra($destino_compra, $tipo_compra) {
		if (isset($destino_compra) && isset($tipo_compra)) {
			$sql_sel = "SELECT valor_sellado_oferta
						FROM CO_LIMITES_COMPRA
						WHERE destino_compra = " . quote($destino_compra) . "
						AND   tipo_compra    = " . quote($tipo_compra) . ";";
			$datos = toba::db()->consultar_fila($sql_sel);
			if (isset($datos) && !empty($datos) && isset($datos['valor_sellado_oferta'])) {
				return $datos['valor_sellado_oferta'];
			} else {
				return 0;
			}
		} else {
			return 0;
		}
    }
	
	public static function get_estados_compra() {
        $sql_sel = "SELECT  crc.rv_low_value estado,
							crc.rv_meaning lov_descripcion
					FROM cg_ref_codes crc
					WHERE crc.rv_domain = 'CO_ESTADO_COMPRA'
					ORDER BY crc.rv_meaning ASC;";
        $datos = toba::db()->consultar($sql_sel);
		return $datos;
    }
	
	public static function get_seguimiento_estados_compra($nro_compra) {
        if (isset($nro_compra)) {
			$sql_sel = "SELECT  cec.*,
								to_char(cec.fecha, 'DD/MM/YYYY') as fecha_format,
								css.cod_sector || '-' || css.descripcion sector,
								crc.rv_meaning estado_format
						FROM co_estados_compra cec
						JOIN co_sectores_seq css ON cec.cod_sector = css.cod_sector AND cec.seq_sector = css.seq_sector
						JOIN cg_ref_codes crc ON crc.rv_domain = 'CO_ESTADO_COMPRA' AND crc.rv_low_value =  cec.estado
						WHERE cec.nro_compra = " . quote($nro_compra) . "
						ORDER BY secuencia ASC;";
			$datos = toba::db()->consultar($sql_sel);
			foreach ($datos as $clave => $dato) {
				$datos[$clave]['observaciones_format'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($dato['observaciones'], 'observaciones_'.$clave, 50, 1, true);
		}
			return $datos;
		} else {
			return array();
		}
    }
	
	public static function get_datos_pedido_cotizacion($nro_compra) {
        if (isset($nro_compra)) {
			$sql_sel = "SELECT	cc.*, 
								to_char(cc.fecha, 'DD/MM/YYYY') as fecha_format,
								to_char(cc.fecha_imputacion, 'DD/MM/YYYY') as fecha_imputacion_format,
								to_char(cc.fecha_resolucion, 'DD/MM/YYYY') as fecha_resolucion_format,
								to_char(cc.fecha_apertura, 'DD/MM/YYYY') as fecha_apertura_format,
								to_char(cc.fecha_publicacion, 'DD/MM/YYYY') as fecha_publicacion_format,
								to_char(cc.fecha_presentacion, 'DD/MM/YYYY') as fecha_presentacion_format,
								trim(to_char(cc.valor_compra, '$999,999,999,990.00')) as valor_compra_format,
								CASE
									WHEN cc.interna = 'S' THEN 'Si'
									ELSE 'No'
								END interna_format,
								CASE
									WHEN cc.presupuestario = 'S' THEN 'Si'
									ELSE 'No'
								END presupuestario_format,
								ke.nro_expediente as nro_expediente,
								cas.cod_ambito || '-' || cas.descripcion ambito,
								css.cod_sector || '-' || css.descripcion sector
						FROM co_compras cc
						JOIN co_ambitos_seq cas ON cc.cod_ambito = cas.cod_ambito AND cc.seq_ambito = cas.seq_ambito
						JOIN co_sectores_seq css ON cc.cod_sector = css.cod_sector AND cc.seq_sector = css.seq_sector
						LEFT JOIN kr_expedientes ke ON cc.id_expediente = ke.id_expediente
						WHERE cc.nro_compra = " . quote($nro_compra) . "
						ORDER BY nro_compra DESC;";
			$datos = toba::db()->consultar_fila($sql_sel);
			return $datos;
		} else {
			return array();
		}
	}
	
	public static function get_modelos_pliegos() {
		$sql_sel = "SELECT cmp.id_modelo,
							cmp.nro_modelo,
							cmp.descripcion
					FROM CO_MODELOS_PLIEGOS cmp
					ORDER BY nro_modelo ASC;";
		$datos = toba::db()->consultar($sql_sel);
		foreach ($datos as $clave => $dato) {
			$datos[$clave]['texto'] = self::get_texto_modelo_pliego($dato['id_modelo']);
			$datos[$clave]['texto_format'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($datos[$clave]['texto'], 'texto_'.$clave, 50, 1000, true);
		}
		return $datos;
    }
	
	public static function actualizar_contrato($nro_compra, $texto) {
		if (isset($nro_compra) && isset($texto)) {
			$sql = "BEGIN :resultado := PKG_COMPRAS.ACTUALIZAR_TEXTO_CONTRATO(:nro_compra, $texto); END;";
			$parametros = array(
								array(	'nombre' => 'nro_compra',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 40,
										'valor' => $nro_compra),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al actualizar el contrato en la compra.', false);
			return $resultado;
		}
	}
	
	public static function actualizar_pliego($nro_compra, $texto) {
		if (isset($nro_compra) && isset($texto)) {
			$sql = "BEGIN :resultado := PKG_COMPRAS.ACTUALIZAR_TEXTO_PLIEGO(:nro_compra, $texto); END;";
			$parametros = array(
								array(	'nombre' => 'nro_compra',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 40,
										'valor' => $nro_compra),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al actualizar el pliego en la compra.', false);
			return $resultado;
		}
	}
	
	public static function get_texto_modelo_pliego($id_modelo) {
		if (isset($id_modelo)) {
			$sql = "BEGIN SELECT cmp.texto INTO :resultado FROM CO_MODELOS_PLIEGOS cmp WHERE cmp.id_modelo = :id_modelo ; END;";
			$parametros = array(
								array(	'nombre' => 'id_modelo',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 40,
										'valor' => $id_modelo),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros, '', 'Error al obtener el texto del modelo.', false);
			if (isset($resultado[1]['valor'])) {
				return $resultado[1]['valor'];
			} else {
				return '';
			}
		}
	}
	
	public static function get_datos_extras_encabezado_pedido_cotizacion($nro_compra) {
        if (isset($nro_compra)) {
            $sql_sel = "SELECT  cc.texto_pliego,
								cc.texto_contrato,
								cc.estado
						FROM co_compras cc
						WHERE cc.nro_compra = " . quote($nro_compra) . ";";
            $datos = toba::db()->consultar_fila($sql_sel);
            return $datos;
        } else {
            return array();
        }
    }
	
	static public function cambiar_estado($nro_compra, $estado, $observaciones) {
		if (isset($nro_compra) && isset($estado)) {
			if (!isset($observaciones)) {
				$observaciones = '';
			}
			$sql = "BEGIN :resultado := PKG_COMPRAS.cambiar_estado(:nro_compra, :estado, :observaciones); END;";
			
			$parametros = array(array(	'nombre' => 'nro_compra',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_compra),
								array(	'nombre' => 'estado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 32,
										'valor' => $estado),
								array(	'nombre' => 'observaciones',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => $observaciones),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al cambiar el estado del pedido de cotización.', false);
			if (isset($resultado[3]['valor']) && !empty($resultado[3]['valor'])) {
				return $resultado[3]['valor'];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}
	
	public static function get_preventivos_compra($nro_compra) {
        if (isset($nro_compra)) {
			$sql_sel = "select	ccp.*,
								CASE
									WHEN ap.anulado = 'S' THEN 'Si'
									ELSE 'No'
								END anulado_preventivo,
								CASE
									WHEN ccp.origen_afi = 'S' THEN 'Si'
									ELSE 'No'
								END origen_afi_format,
								CASE
									WHEN ap.ID_PREVENTIVO_REI IS NOT NULL THEN 'Si'
									ELSE 'No'
								END reimputacion_preventivo,
								to_char(ap.FECHA_ANULACION, 'DD/MM/YYYY') as fecha_anulacion_preventivo,
								to_char(ap.FECHA_COMPROBANTE, 'DD/MM/YYYY') as fecha_preventivo
						from   co_compras_preventivos ccp
						left join ad_preventivos ap ON (ccp.id_preventivo = ap.id_preventivo) 
						where  nro_compra = " . quote($nro_compra) . ";";
			$datos = toba::db()->consultar($sql_sel);
			return $datos;
		} else {
			return array();
		}
    }
	
	public static function get_cantidad_preventivos_aprobados($nro_compra) {
        if (isset($nro_compra)) {
			$sql_sel = "select count(*) cantidad
						from   co_compras_preventivos ccp
						join ad_preventivos ap ON ccp.id_preventivo = ap.id_preventivo
						where  nro_compra = " . quote($nro_compra) . "
						AND ap.aprobado = 'S'
						AND ap.anulado = 'N';";
			$datos = toba::db()->consultar_fila($sql_sel);
			if (isset($datos) && !empty($datos) && isset($datos['cantidad'])) {
				return $datos['cantidad'];
			} else {
				return 0;
			}
		} else {
			return 0;
		}
    }
	
	public static function actualizar_fecha_imputacion($nro_compra, $fecha_inputacion) {
		if (isset($nro_compra) && isset($fecha_inputacion)) {
			$sql = "UPDATE CO_COMPRAS 
					SET fecha_imputacion = " . quote($fecha_inputacion) . "
					WHERE nro_compra = " . quote($nro_compra) . ";";
			toba::db()->ejecutar($sql);
		}
	}
	
	static public function ajustar_reserva_interna_afi($nro_compra) {
		if (isset($nro_compra)) {
			
			$sql = "BEGIN :resultado := pkg_compras.ajustar_reserva_interna_afi(:nro_compra); END;";
			
			$parametros = array(array(	'nombre' => 'nro_compra',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_compra),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros, '', 'Error al ajustar la reserva interna de AFI en el pedido de cotización.', false);
			
			if (isset($resultado[1]['valor']) && !empty($resultado[1]['valor'])) {
				return $resultado[1]['valor'];
			} else {
				return 'NOK';
			}
		} else {
			return 'NOK';
		}
	}
	
	static public function anular_reserva_interna_afi($nro_compra) {
		if (isset($nro_compra)) {
			
			$sql = "BEGIN :resultado := Pkg_Compras.anular_reserva_interna_afi(:nro_compra); END;";
			
			$parametros = array(array(	'nombre' => 'nro_compra',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_compra),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros, '', 'Error al anular la reserva interna de AFI en el pedido de cotización.', false);
			
			if (isset($resultado[1]['valor']) && !empty($resultado[1]['valor'])) {
				return $resultado[1]['valor'];
			} else {
				return 'NOK';
			}
		} else {
			return 'NOK';
		}
	}
	
	static public function asociar_preventivo($nro_compra, $id_preventivo, $nro_preventivo, $anio) {
		if (isset($nro_compra) && isset($id_preventivo) && isset($nro_preventivo) && isset($anio)) {
			$sql = "BEGIN :resultado := pkg_compras.asociar_preventivo(:nro_compra, :id_preventivo, :nro_preventivo, :anio);  END;";
			
			$parametros = array(array(	'nombre' => 'nro_compra',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_compra),
								array(	'nombre' => 'id_preventivo',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $id_preventivo),
								array(	'nombre' => 'nro_preventivo',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_preventivo),
								array(	'nombre' => 'anio',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $anio),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al asociar el preventivo en el pedido de cotización.', false);
			if (isset($resultado[4]['valor']) && !empty($resultado[4]['valor'])) {
				return $resultado[4]['valor'];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}
	
	static public function desasociar_preventivo($nro_compra, $secuencia) {
		if (isset($nro_compra) && isset($secuencia)) {
			$sql = "BEGIN :resultado := pkg_compras.desasociar_preventivo(:NRO_COMPRA, :SECUENCIA);     END;";
			
			$parametros = array(array(	'nombre' => 'nro_compra',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_compra),
								array(	'nombre' => 'secuencia',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $secuencia),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al desasociar el preventivo en el pedido de cotización.', false);
			if (isset($resultado[2]['valor']) && !empty($resultado[2]['valor'])) {
				return $resultado[2]['valor'];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}
	
	public static function get_items_compra_x_nombre($nombre, $filtro = array()) {
        $where = ' 1=1 ';
        if (isset($nombre)) {
            $trans_nro_item = ctr_construir_sentencias::construir_translate_ilike('cic.nro_item', $nombre);
			$trans_cod_concepto = ctr_construir_sentencias::construir_translate_ilike('cic.cod_articulo', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('ca.descripcion', $nombre);
            $where .= " AND ($trans_cod_concepto OR $trans_descripcion OR $trans_nro_item)";
        }

        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'cic', '1=1');
        $sql = "SELECT	cic.*,
						cic.nro_compra || '".apex_qs_separador."' || nro_renglon nro_compra_renglon,
						cic.nro_item || ' - Art.: ' ||ca.COD_ARTICULO || ' - ' || replace(replace(ca.DESCRIPCION, '<','«'), '>', '»') || ' (Detalle: ' || cic.detalle || ')' lov_descripcion
				FROM CO_ITEMS_COMPRA cic
				JOIN CO_ARTICULOS ca ON (cic.cod_articulo = ca.cod_articulo)
				WHERE $where
				ORDER BY cic.nro_item;";
        return toba::db()->consultar($sql);
    }
	
	public static function get_item_compra_x_compra_renglon($nro_compra_renglon) {
        if (isset($nro_compra_renglon)) {
			$nro_compra_renglon = explode(apex_qs_separador, $nro_compra_renglon);
			$nro_compra = $nro_compra_renglon[0];
			$nro_renglon = $nro_compra_renglon[1];
			if (isset($nro_compra) && isset($nro_renglon)) {
				$sql = "SELECT	cic.*,
							cic.nro_item || ' - Art.: ' ||ca.COD_ARTICULO || ' - ' || replace(replace(ca.DESCRIPCION, '<','«'), '>', '»') || ' (Detalle: ' || cic.detalle || ')' lov_descripcion
						FROM CO_ITEMS_COMPRA cic
						JOIN CO_ARTICULOS ca ON (cic.cod_articulo = ca.cod_articulo)
						WHERE nro_compra = " . quote($nro_compra) . "
						AND nro_renglon = " . quote($nro_renglon) . ";";
				$resultado = toba::db()->consultar_fila($sql);
				if (isset($resultado) && !empty($resultado) && isset($resultado['lov_descripcion'])) {
					return $resultado['lov_descripcion'];
				} else {
					return '';
				} 
			} else {
				return '';
			}
	    } else {
            return '';
        }
    }
	
	public static function get_items_compra($filtro = array()) {
        $where = ' 1=1 ';
		$select = "";
		if (isset($filtro['tiene_subitems'])) {
			$where .= " AND PKG_COMPRAS.tiene_subitems(cic.nro_compra, cic.nro_renglon) = " . quote($filtro['tiene_subitems']) . " ";
			unset($filtro['tiene_subitems']);
		}
		if (isset($filtro['id_proveedor'])) {
			$select .= ", PKG_COMPRAS.retornar_valor_cotizado(cic.nro_compra, " . quote($filtro['id_proveedor']) . ", cic.nro_renglon) precio_cotizado";
			$select .= ", PKG_COMPRAS.retornar_marca_cotizado(cic.nro_compra, " . quote($filtro['id_proveedor']) . ", cic.nro_renglon) marca_cotizado";
			$select .= ", PKG_COMPRAS.retornar_observacion_cotizado(cic.nro_compra, " . quote($filtro['id_proveedor']) . ", cic.nro_renglon) observacion_cotizado";
			unset($filtro['id_proveedor']);
		}
        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'cic', '1=1');
        $sql = "SELECT	cic.*,
						ca.COD_ARTICULO || ' - ' || replace(replace(ca.DESCRIPCION, '<','«'), '>', '»') articulo,
						cic.nro_compra || '".apex_qs_separador."' || nro_renglon nro_compra_renglon,
						cic.nro_item || ' - Art.: ' ||ca.COD_ARTICULO || ' - ' || replace(replace(ca.DESCRIPCION, '<','«'), '>', '»') || ' (Detalle: ' || cic.detalle || ')' lov_descripcion,
						cic.nro_item || ' (Detalle: ' || SUBSTR(cic.detalle, 1, 50) || ')' lov_descripcion_detalle
						$select
				FROM CO_ITEMS_COMPRA cic
				JOIN CO_ARTICULOS ca ON (cic.cod_articulo = ca.cod_articulo)
				WHERE $where
				ORDER BY cic.nro_item;";
        $datos = toba::db()->consultar($sql);
		
		foreach ($datos as $clave => $dato) {
			$datos[$clave]['detalle_format'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($dato['detalle'], 'detalle_'.$clave, 50, 1, true);
		}
        return $datos;
    }
	
	public static function get_items_compra_precios($filtro = array()) {
        $where = ' 1=1 ';
		
        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'cicp', '1=1');
        $sql = "SELECT	cicp.*,
						cic.nro_item,
						cic.detalle,
						trim(to_char(cicp.precio, '$999,999,999,990.00000000')) as precio_format,
						trim(to_char(cicp.monto_impuesto, '$999,999,999,990.00000000')) as monto_impuesto_format,
						(SELECT COUNT(1)
						FROM co_items_compra_alternativas cica
						WHERE cica.nro_compra = cic.nro_compra
						AND cica.nro_renglon = cic.nro_renglon
						AND cica.id_proveedor = cicp.id_proveedor) as cantidad_alternativas
				FROM CO_ITEMS_COMPRA_PRECIOS cicp
				JOIN CO_ITEMS_COMPRA cic ON (cicp.nro_compra = cic.nro_compra AND cicp.nro_renglon = cic.nro_renglon)
				WHERE $where
				ORDER BY cic.nro_item;";
        $datos = toba::db()->consultar($sql);
		
		foreach ($datos as $clave => $dato) {
			$datos[$clave]['detalle_format'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($dato['detalle'], 'detalle_'.$clave, 50, 1, true);
			if ($dato['cantidad_alternativas'] > 0) {
				$datos[$clave]['precios_alternativos'] = 'Si';
			} else {
				$datos[$clave]['precios_alternativos'] = 'No';
			}
		}
        return $datos;
    }
	
	static public function actualizar_valor_cotizado($nro_compra, $id_proveedor, $nro_renglon, $precio, $marca, $observacion) {
		if (isset($nro_compra) && isset($nro_renglon) && isset($id_proveedor) && isset($precio)) {

			if (is_null($marca))
				$marca = '';

			if (is_null($observacion))
				$observacion = '';
			
			$sql = "BEGIN :resultado := PKG_COMPRAS.actualizar_valor_cotizado(:nro_compra,:id_proveedor, :nro_renglon,:precio, :marca, :observacion); END;";
			
			$parametros = array(array(	'nombre' => 'nro_compra',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_compra),
								array(	'nombre' => 'id_proveedor',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $id_proveedor),
								array(	'nombre' => 'nro_renglon',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_renglon),
								array(	'nombre' => 'precio',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 32,
										'valor' => $precio),
								array(	'nombre' => 'marca',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 32,
										'valor' => $marca),
								array(	'nombre' => 'observacion',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 200,
										'valor' => $observacion),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al actualizar el valor corizado en el pedido de cotización.', false);
			if (isset($resultado[6]['valor']) && !empty($resultado[6]['valor'])) {
				return $resultado[6]['valor'];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}
	
	static public function borrar_valor_cotizado($nro_compra, $id_proveedor, $nro_renglon) {
		if (isset($nro_compra) && isset($nro_renglon) && isset($id_proveedor)) {
			$sql = "BEGIN :resultado := PKG_COMPRAS.borrar_valor_cotizado(:nro_compra,:id_proveedor, :nro_renglon); END;";
			
			$parametros = array(array(	'nombre' => 'nro_compra',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_compra),
								array(	'nombre' => 'id_proveedor',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $id_proveedor),
								array(	'nombre' => 'nro_renglon',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_renglon),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al borrar el valor corizado en el pedido de cotización.', false);
			if (isset($resultado[3]['valor']) && !empty($resultado[3]['valor'])) {
				return $resultado[3]['valor'];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}
	
	static public function get_impuesto_item_compra_precio($nro_compra, $nro_renglon, $id_proveedor, $cod_impuesto) 
	{
		if (isset($nro_compra) && isset($nro_renglon) && isset($id_proveedor) && isset($cod_impuesto)) {
			$sql_sel = "SELECT icp.precio * NVL(ci.porcentaje,0) / 100 impuesto
						FROM CO_ITEMS_COMPRA_PRECIOS icp,
							 CO_ITEMS_COMPRA ic,
							 CO_ARTICULOS a,
							 AD_CONCEPTOS c,
							 AD_CONCEPTOS_IMPUESTOS ci
						WHERE icp.nro_compra = ic.nro_compra
						AND icp.nro_renglon = ic.nro_renglon
						AND ic.cod_articulo = a.cod_articulo
						AND a.cod_concepto = c.cod_concepto
						AND c.cod_concepto = ci.cod_concepto
						AND icp.nro_compra = " . quote($nro_compra) . "
						AND icp.nro_renglon = " . quote($nro_renglon) . "
						AND icp.id_proveedor = " . quote($id_proveedor) . "
						AND ci.cod_impuesto = " . quote($cod_impuesto) . ";";
			$datos = toba::db()->consultar_fila($sql_sel);
			if (isset($datos) && !empty($datos) && isset($datos['impuesto'])) {
				return $datos['impuesto'];
			} else {
				return 0;
			}
		} else {
			return 0;
		}
	}
	
	public static function get_documentacion_compra($nro_compra) {
        if (isset($nro_compra)) {
			$sql_sel = "select	ccd.*,
								ccd.id_documentacion || ' - ' || cd.descripcion documentacion,
								to_char(ccd.fecha, 'DD/MM/YYYY') as fecha_format
						from   co_compras_documentaciones ccd
						JOIN co_documentaciones cd on (cd.id_documentacion = ccd.id_documentacion)
						where  ccd.nro_compra = " . quote($nro_compra) . ";";
			$datos = toba::db()->consultar($sql_sel);
			return $datos;
		} else {
			return array();
		}
    }
	
	public static function get_documentos_compra($filtro = array()) {
        $where = ' 1=1 ';
		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'cd', '1=1');
        $sql = "select	cd.*,
						cd.id_documentacion || ' - ' || cd.descripcion lov_descripcion
				from co_documentaciones cd
				WHERE $where
				ORDER BY cd.id_documentacion;";
        $datos = toba::db()->consultar($sql);
		
		return $datos;
    }
	
	public static function get_existe_documentacion_compra($nro_compra, $id_documentacion) {
        if (isset($nro_compra) && isset($id_documentacion)) {
			$sql_sel = "select	count(1) resultado
						from   co_compras_documentaciones ccd
						JOIN co_documentaciones cd on (cd.id_documentacion = ccd.id_documentacion)
						where  ccd.nro_compra = " . quote($nro_compra) . "
						AND ccd.id_documentacion = " . quote($id_documentacion) . ";";
			$datos = toba::db()->consultar_fila($sql_sel);
			if (isset($datos['resultado']) && $datos['resultado'] > 0) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
    }
	
	public static function eliminar_documentacion_compra($nro_compra, $id_documentacion) {
        if (isset($nro_compra) && isset($id_documentacion)) {
			$sql = "DELETE from co_compras_documentaciones ccd
					where  ccd.nro_compra = " . quote($nro_compra) . "
					AND ccd.id_documentacion = " . quote($id_documentacion) . ";";
			return toba::db()->ejecutar($sql);
		} else {
			return false;
		}
    }
	
	public static function insertar_documentacion_compra($nro_compra, $id_documentacion) {
        if (isset($nro_compra) && isset($id_documentacion)) {
			$sql = "INSERT INTO co_compras_documentaciones(nro_compra, id_documentacion, fecha, usuario)
					VALUES (" . quote($nro_compra) . ", " . quote($id_documentacion) . ", TRUNC(SYSDATE), UPPER(" . quote(toba::usuario()->get_id()) . "));";
			return toba::db()->ejecutar($sql);
		} else {
			return false;
		}
    }
	
	public static function get_archivos_compra($nro_compra) {
        if (isset($nro_compra)) {
			$sql_sel = "select	ca.*,
								ca.nro_archivo || ' - ' || ca.descripcion archivo,
								to_char(ca.fecha, 'DD/MM/YYYY') as fecha_format,
								crc.rv_meaning mime_type_format,
								NVL(round(NVL(dbms_lob.getlength(dato), 0) /1024,2), 0) kb
						from   co_archivos ca
						LEFT OUTER JOIN CG_REF_CODES crc ON (crc.rv_domain = 'CO_MIME_TYPES' AND crc.rv_low_value = ca.mime_type)
						where  ca.nro_compra = " . quote($nro_compra) . ";";
			$datos = toba::db()->consultar($sql_sel);
			return $datos;
		} else {
			return array();
		}
    }
	
	public static function get_archivo_compra($nro_compra, $nro_archivo) {
        if (isset($nro_compra) && isset($nro_archivo)) {
			$sql_sel = "select	ca.*,
								ca.nro_archivo || ' - ' || ca.descripcion archivo,
								to_char(ca.fecha, 'DD/MM/YYYY') as fecha_format,
								crc.rv_meaning mime_type_format,
								NVL(round(NVL(dbms_lob.getlength(dato), 0) /1024,2), 0) kb
						from   co_archivos ca
						LEFT OUTER JOIN CG_REF_CODES crc ON (crc.rv_domain = 'CO_MIME_TYPES' AND crc.rv_low_value = ca.mime_type)
						where  ca.nro_compra = " . quote($nro_compra) . "
						AND ca.nro_archivo = " . quote($nro_archivo) . ";";
			$datos = toba::db()->consultar_fila($sql_sel);
			return $datos;
		} else {
			return array();
		}
    }
	
	public static function get_concatall_extensiones_archivos() {
		$sql = "select concat_all('SELECT rv_abbreviation	FROM CG_REF_CODES WHERE rv_domain = ''CO_MIME_TYPES''', ' ') extensiones FROM DUAL";
		$resultado = toba::db()->consultar_fila($sql);
		if (isset($resultado['extensiones'])) {
			return $resultado['extensiones'];
		} else {
			return '';
		}
	}
	
	public static function get_extensiones_archivos() {
		$sql = "SELECT lower(rv_abbreviation) lista_extensiones,
					rv_low_value mime_type
				FROM CG_REF_CODES 
				WHERE rv_domain = 'CO_MIME_TYPES';";
		$resultado = toba::db()->consultar($sql);
		return $resultado;
	}
	
	public static function actualizar_dato_archivo_compras($nro_compra, $nro_archivo, $dato) {
		if (isset($nro_compra) && isset($nro_archivo) && isset($dato)) {
			$sql = "UPDATE co_archivos SET dato = EMPTY_BLOB() WHERE nro_compra = :nro_compra AND nro_archivo = :nro_archivo RETURNING dato INTO :p_dato";
			$parametros = array(
								array(	'nombre' => 'nro_compra',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 40,
										'valor' => $nro_compra),
								array(	'nombre' => 'nro_archivo',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 40,
										'valor' => $nro_archivo),
								array(	'nombre' => 'p_dato',
										'tipo_dato' => PDO::PARAM_LOB,
										'longitud' => 400000000,
										'valor' => $dato)
								);
			return toba::db()->ejecutar_store_procedure($sql, $parametros);
		}
	}
	
	public static function get_max_nro_archivo_compra($nro_compra) {
        if (isset($nro_compra)) {
			$sql_sel = "SELECT NVL(MAX(NRO_ARCHIVO),0) nro_archivo
						FROM  CO_ARCHIVOS ca
						WHERE ca.nro_compra = " . quote($nro_compra) . ";";
			$datos = toba::db()->consultar_fila($sql_sel);
			if (isset($datos['nro_archivo'])) {
				return $datos['nro_archivo'];
			} else {
				return 1;
			}
		} else {
			return 1;
		}
    }


    public static function generar_texto_carta($nro_compra, $id_proveedor, $id_modelo, $con_transaccion = true) {

		if (isset($nro_compra) && isset($id_proveedor) && isset($id_modelo)) {
			$sql = "BEGIN :resultado:= PKG_COMPRAS.generar_texto_carta(:nro_compra,:id_proveedor, :id_modelo); END;";
			$parametros = array(array(	'nombre' => 'nro_compra',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $nro_compra),
							array(	'nombre' => 'id_proveedor',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $id_proveedor),
							array(	'nombre' => 'id_modelo',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $id_modelo),
							array(	'nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''),
							);

			if ($con_transaccion)
				toba::db()->abrir_transaccion();

			$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

			if ($resultado[3]['valor'] == 'OK'){
				if ($con_transaccion)
					toba::db()->cerrar_transaccion();
			}else{
				if ($con_transaccion)
					toba::db()->abortar_transaccion();
				return false;
			}
			return true;
		}
	}
	
	public static function get_precios_alternativos ($nro_compra, $id_proveedor, $nro_item){
		//if (empty($nro_compra) || empty($nro_compra) || empty($nro_item) )
			//return array();
		$sql = "SELECT v_co_precios_alternativas.nro_alternativa nro_alternativa,
				       v_co_precios_alternativas.precio precio,
				       v_co_precios_alternativas.marca marca,
				       v_co_precios_alternativas.descripcion descripcion,
				       '$ '|| trim(to_char(v_co_precios_alternativas.precio, '999,999,999,990.00000000')) ||' - '|| v_co_precios_alternativas.marca ||' - '|| v_co_precios_alternativas.descripcion lov_descripcion
				  FROM v_co_precios_alternativas v_co_precios_alternativas
				 WHERE (    v_co_precios_alternativas.nro_compra = ".quote($nro_compra)."
				        AND v_co_precios_alternativas.nro_item = ".quote($nro_item)."
				        AND v_co_precios_alternativas.id_proveedor =
				                                                    ".quote($id_proveedor).")";
		return toba::db()->consultar($sql);
	}

	public static function get_archivos_adjuntos ($nro_compra)
	{
		$sql = "SELECT *
				  FROM co_archivos
				 WHERE nro_compra = ".quote($nro_compra)."
				 ORDER BY FECHA DESC";
		return toba::db()->consultar($sql);
	}

}

?>
