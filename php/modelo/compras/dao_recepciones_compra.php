<?php
class dao_recepciones_compra 
{
	
	 public static function get_recepciones_compra($filtro = array(), $orden = array()) {
	 	$desde= null;
		$hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}
		$where = self::armar_where($filtro);
		
        $sql_sel = "SELECT  crc.*,
							to_char(crc.fecha, 'DD/MM/YYYY') as fecha_format,
							trim(to_char(crc.importe_recepcion, '$999,999,999,990.00')) as importe_recepcion_format,
							trim(to_char(crc.importe_impuesto, '$999,999,999,990.00')) as importe_impuesto_format,
							trim(to_char(crc.importe_retencion, '$999,999,999,990.00')) as importe_retencion_format,
							CASE
								WHEN crc.interna = 'S' THEN 'Si'
								ELSE 'No'
							END interna_format,
							CASE
								WHEN crc.presupuestario = 'S' THEN 'Si'
								ELSE 'No'
							END presupuestario_format,
							ke.nro_expediente as nro_expediente,
							keo.nro_expediente as nro_expediente_orden,
							cas.cod_ambito || '-' || cas.descripcion ambito,
							css.cod_sector || '-' || css.descripcion sector,
							cp.id_proveedor || '-' || cp.razon_social proveedor
					FROM co_recepciones crc
					JOIN co_ambitos_seq cas ON crc.cod_ambito = cas.cod_ambito AND crc.seq_ambito = cas.seq_ambito
					JOIN co_sectores_seq css ON crc.cod_sector = css.cod_sector AND crc.seq_sector = css.seq_sector
					LEFT JOIN kr_expedientes ke ON crc.id_expediente = ke.id_expediente
					LEFT JOIN kr_expedientes keo ON crc.id_expediente_orden = keo.id_expediente
					JOIN CO_PROVEEDORES cp ON crc.id_proveedor = cp.id_proveedor
					WHERE $where
					ORDER BY nro_recepcion DESC";
        $sql = dao_varios::paginador($sql_sel, null, $desde, $hasta, null, $orden);
        $datos = toba::db()->consultar($sql);
		
		foreach ($datos as $clave => $dato) {
			$datos[$clave]['observaciones_format'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($dato['observaciones'], 'observaciones_'.$clave, 50, 1, true);
		}
        return $datos;
    }
    
	static public function armar_where ($filtro = array())
	{
		$where = " 1=1 ";
		if (isset($filtro['ids_comprobantes'])) {
            $where .= "AND crc.nro_recepcion IN (" . $filtro['ids_comprobantes'] . ") ";
            unset($filtro['ids_comprobantes']);
        }
		if (isset($filtro['ambito_usuario']) && $filtro['ambito_usuario'] == '1') {
			$where .= " AND INSTR(Pkg_Usuarios.ambitos_usuario(".quote(strtoupper(toba::usuario()->get_id()))."), crc.cod_ambito) > 0";
			unset($filtro['ambito_usuario']);
		}
		if (isset($filtro['excluir_nro_recepcion'])) {
            $where .= " AND crc.nro_recepcion <> " . $filtro['excluir_nro_recepcion'] . " ";
            unset($filtro['excluir_nro_recepcion']);
        }
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'crc', '1=1');
		return $where;
	}
	
	static public function get_cantidad ($filtro = array())
	{
		$where = self::armar_where($filtro);
		$sql = "select count(*) cantidad
				  FROM co_recepciones crc
			   	  JOIN co_ambitos_seq cas ON crc.cod_ambito = cas.cod_ambito AND crc.seq_ambito = cas.seq_ambito
				  JOIN co_sectores_seq css ON crc.cod_sector = css.cod_sector AND crc.seq_sector = css.seq_sector
				  LEFT JOIN kr_expedientes ke ON crc.id_expediente = ke.id_expediente
				  LEFT JOIN kr_expedientes keo ON crc.id_expediente_orden = keo.id_expediente
				  JOIN CO_PROVEEDORES cp ON crc.id_proveedor = cp.id_proveedor
				where $where ";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cantidad'];
	}
	static public function get_lov_recepciones_compra_x_nro ($nro_recepcion){
		$sql = "SELECT	'#'
				        || re.nro_recepcion
				        || ' - '
				        || re.anio
				        || ' - '
				        || l_pro.razon_social lov_descripcion
					FROM	CO_RECEPCIONES RE, 
							CO_PROVEEDORES L_PRO
					WHERE RE.ID_PROVEEDOR = L_PRO.ID_PROVEEDOR 
					AND re.nro_recepcion = ".quote($nro_recepcion);
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];

	}
	public static function get_recepciones_compra_x_nombre($nombre, $filtro = array())
	{
		$where = ' 1=1 ';
		if (isset($nombre)) {
            $trans_nro_recepcion = ctr_construir_sentencias::construir_translate_ilike('re.nro_recepcion', $nombre);
            $where .= " AND ($trans_nro_recepcion)";
        }
		if (isset($filtro['id_proveedor_ad'])) {
			$where .= " AND RE.ID_PROVEEDOR = (	SELECT ID_PROVEEDOR 
												FROM CO_PROVEEDORES 
												WHERE ID_PROVEEDOR_AD = " . quote($filtro['id_proveedor_ad']) .")";
			unset($filtro['id_proveedor_ad']);
		}
		
		if (isset($filtro['no_estados']) && !empty($filtro['no_estados'])) {
			$lista_estados = array();
			foreach ($filtro['no_estados'] as $no_estado) {
				$lista_estados[] = 'PKG_GENERAL.VALOR_PARAMETRO('.quote($no_estado).')';
			}
			$lista_estados = (!empty($lista_estados))?$lista_estados:array('NULL');
			$where .= " AND RE.ESTADO NOT IN (".implode(', ', $lista_estados).") ";
			unset($filtro['no_estados']);
		}
		
		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 're', '1=1');
		
		$sql_sel = "SELECT	RE.NRO_RECEPCION NRO_RECEPCION,
							RE.COD_SECTOR COD_SECTOR,
							RE.ANIO ANIO,  RE.ESTADO ESTADO
							, '#'
					         || re.nro_recepcion
					         || ' - '
					         || re.anio
					         || ' - '
					         || l_pro.razon_social lov_descripcion
					FROM	CO_RECEPCIONES RE, 
							CO_PROVEEDORES L_PRO
					WHERE RE.ID_PROVEEDOR = L_PRO.ID_PROVEEDOR 
					AND $where;";
		return toba::db()->consultar($sql_sel);
	}
	
		public static function get_recepciones_x_nombre($nombre, $filtro = array())
	{
		$where = ' 1=1 ';
		if (isset($nombre)) {
            $trans_nro_recepcion = ctr_construir_sentencias::construir_translate_ilike('re.nro_recepcion', $nombre);
            $where .= " AND ($trans_nro_recepcion)";
        }
		if (isset($filtro['id_proveedor_ad'])) {
			$where .= " AND RE.ID_PROVEEDOR = (	SELECT ID_PROVEEDOR 
												FROM CO_PROVEEDORES 
												WHERE ID_PROVEEDOR_AD = " . quote($filtro['id_proveedor_ad']) .")";
			unset($filtro['id_proveedor_ad']);
		}
		
		if (isset($filtro['no_estados']) && !empty($filtro['no_estados'])) {
			$lista_estados = array();
			foreach ($filtro['no_estados'] as $no_estado) {
				$lista_estados[] = 'PKG_GENERAL.VALOR_PARAMETRO('.quote($no_estado).')';
			}
			$lista_estados = (!empty($lista_estados))?$lista_estados:array('NULL');
			$where .= " AND RE.ESTADO NOT IN (".implode(', ', $lista_estados).") ";
			unset($filtro['no_estados']);
		}
		
		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 're', '1=1');
		
		$sql_sel = "SELECT	RE.NRO_RECEPCION NRO_RECEPCION,
							RE.COD_SECTOR COD_SECTOR,
							RE.ANIO ANIO,  RE.ESTADO ESTADO
							, re.nro_recepcion lov_descripcion
					FROM	CO_RECEPCIONES RE, 
							CO_PROVEEDORES L_PRO
					WHERE RE.ID_PROVEEDOR = L_PRO.ID_PROVEEDOR 
					AND $where;";
		return toba::db()->consultar($sql_sel);
	}
	static public function get_nro_recepcion_x_nro_recepcion($nro_recepcion) {
        if (isset($nro_recepcion)) {
            return $nro_recepcion;
        } else {
            return '';
        }
    }
	
	public static function get_recepciones_compra_finalizada($nro_recepcion)
	{
		if (isset($nro_recepcion)) {
			$sql_sel = "SELECT	RE.FINALIZADA
						FROM	CO_RECEPCIONES RE
						WHERE RE.NRO_RECEPCION = " . quote($nro_recepcion) . ";";
			$datos = toba::db()->consultar($sql_sel);
			if (isset($datos) && !empty($datos) && isset($datos['finalizada'])) {
				return $datos['finalizada'];
			} else {
				return 'N';
			}
		} else {
			return 'N';
		}
	}
	
	public static function get_estados_recepcion() {
        $sql_sel = "SELECT  crc.rv_low_value estado,
							crc.rv_meaning lov_descripcion
					FROM cg_ref_codes crc
					WHERE crc.rv_domain = 'CO_ESTADO_RECEPCION'
					ORDER BY crc.rv_meaning ASC;";
        $datos = toba::db()->consultar($sql_sel);
		return $datos;
    }
	
	public static function get_nro_orden_x_nro_recepcion($nro_recepcion) {
        if (isset($nro_recepcion)) {
            $sql_sel = "SELECT  cr.nro_orden
						FROM	co_recepciones cr
						WHERE  cr.nro_recepcion =  " . quote($nro_recepcion) . ";";
            $datos = toba::db()->consultar_fila($sql_sel);
			if (isset($datos) && !empty($datos) && isset($datos['nro_orden'])) {
				return $datos['nro_orden'];
			} else {
				null;
			}
        } else {
            return null;
        }
    }
	
	public static function get_datos_extras_encabezado_recepcion_compra($nro_recepcion) {
        if (isset($nro_recepcion)) {
            $sql_sel = "SELECT  cr.importe_recepcion,
								cr.importe_retencion,
								cr.importe_impuesto,
								cr.estado
						FROM co_recepciones cr
						WHERE cr.nro_recepcion = " . quote($nro_recepcion) . ";";
            $datos = toba::db()->consultar_fila($sql_sel);
            return $datos;
        } else {
            return array();
        }
    }
	
	public static function eliminar_items_recepcion($nro_recepcion, $nro_orden)
	{
		if (isset($nro_recepcion) && isset($nro_orden)) {
			$sql = "Delete co_items_recepcion 
					where nro_recepcion = ".quote($nro_recepcion)."
					AND (nro_orden IS NULL OR nro_orden <> ".quote($nro_orden).");";
			toba::db()->ejecutar($sql);
		}
	}
	
	public static function get_seguimiento_estados_recepcion($nro_recepcion) {
        if (isset($nro_recepcion)) {
			$sql_sel = "SELECT  cer.*,
								to_char(cer.fecha, 'DD/MM/YYYY') as fecha_format,
								css.cod_sector || '-' || css.descripcion sector,
								crc.rv_meaning estado_format
						FROM co_estados_recepcion cer
						JOIN co_sectores_seq css ON cer.cod_sector = css.cod_sector AND cer.seq_sector = css.seq_sector
						JOIN cg_ref_codes crc ON crc.rv_domain = 'CO_ESTADO_RECEPCION' AND crc.rv_low_value =  cer.estado
						WHERE cer.nro_recepcion = " . quote($nro_recepcion) . "
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
	
	public static function get_datos_recepcion_compra($nro_recepcion) {
		if (isset($nro_recepcion)) {
			$sql_sel = "SELECT  crc.*,
								to_char(crc.fecha, 'DD/MM/YYYY') as fecha_format,
								trim(to_char(crc.importe_recepcion, '$999,999,999,990.00')) as importe_recepcion_format,
								trim(to_char(crc.importe_impuesto, '$999,999,999,990.00')) as importe_impuesto_format,
								trim(to_char(crc.importe_retencion, '$999,999,999,990.00')) as importe_retencion_format,
								CASE
									WHEN crc.interna = 'S' THEN 'Si'
									ELSE 'No'
								END interna_format,
								CASE
									WHEN crc.presupuestario = 'S' THEN 'Si'
									ELSE 'No'
								END presupuestario_format,
								ke.nro_expediente as nro_expediente,
								keo.nro_expediente as nro_expediente_orden,
								cas.cod_ambito || '-' || cas.descripcion ambito,
								css.cod_sector || '-' || css.descripcion sector,
								cp.id_proveedor || '-' || cp.razon_social proveedor
						FROM co_recepciones crc
						JOIN co_ambitos_seq cas ON crc.cod_ambito = cas.cod_ambito AND crc.seq_ambito = cas.seq_ambito
						JOIN co_sectores_seq css ON crc.cod_sector = css.cod_sector AND crc.seq_sector = css.seq_sector
						LEFT JOIN kr_expedientes ke ON crc.id_expediente = ke.id_expediente
						LEFT JOIN kr_expedientes keo ON crc.id_expediente_orden = keo.id_expediente
						JOIN CO_PROVEEDORES cp ON crc.id_proveedor = cp.id_proveedor
						WHERE nro_recepcion = " .quote($nro_recepcion).";";
			$datos = toba::db()->consultar_fila($sql_sel);

			$datos['observaciones_format'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($datos['observaciones'], 'observaciones_1', 50, 1, true);
			
			return $datos;
		} else {
			return array();
		}
    }
	
	static public function cambiar_estado($nro_recepcion, $estado, $observaciones) {
		if (isset($nro_recepcion) && isset($estado)) {
			if (!isset($observaciones)) {
				$observaciones = '';
			}
			$sql = "BEGIN :resultado := PKG_RECEPCIONES.cambiar_estado(:nro_recepcion, :estado, :observaciones); END;";
			
			$parametros = array(array(	'nombre' => 'nro_recepcion',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_recepcion),
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

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al cambiar el estado de la recepción.', false);
			if (isset($resultado[3]['valor']) && !empty($resultado[3]['valor'])) {
				return $resultado[3]['valor'];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}
	
	public static function get_items_orden($nombre = null, $filtro = array()) {
        $where = ' 1=1 ';
        if (isset($nombre)) {
            $trans_cod_articulo = ctr_construir_sentencias::construir_translate_ilike('coi.cod_articulo', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('coi.descripcion', $nombre);
            $where .= " AND ($trans_cod_articulo OR $trans_descripcion)";
        }
		
		if (isset($filtro['mostrar_recepcion']) && $filtro['mostrar_recepcion'] == 1 ) {
			if (isset($filtro['nro_orden'])) {
				$nro_orden = quote($filtro['nro_orden']);
			} else {
				$nro_orden = 'NULL';
			}
			$where .= "	AND (($nro_orden is not null and coi.nro_orden = $nro_orden) 
							OR ($nro_orden is null and coi.nro_orden is null))
						AND nvl(pkg_ordenes.saldo_recepcionar (coi.nro_orden, coi.nro_renglon),0) > 0";
			unset($filtro['mostrar_recepcion']);
			unset($filtro['nro_orden']);
		}
		
		if (isset($filtro['mostrar_no_incorporados_recepcion']) && $filtro['mostrar_no_incorporados_recepcion'] == 1 && isset($filtro['nro_recepcion']) ) {
			$where .= "	AND NOT EXISTS (SELECT 1 FROM CO_ITEMS_RECEPCION CIR WHERE CIR.NRO_RECEPCION = " . quote($filtro['nro_recepcion']) . " AND CIR.NRO_RENGLON_ORDEN = coi.NRO_RENGLON)";
			unset($filtro['mostrar_no_incorporados_recepcion']);
			unset($filtro['nro_recepcion']);
		}

		if (isset($filtro['con_saldo_devolucion']) && $filtro['con_saldo_devolucion'] == 1) {
			$where .= "	and pkg_ordenes.saldo_devolucion(cio.nro_orden, cio.nro_renglon) > 0 ";
			unset($filtro['con_saldo_devolucion']);
		}

		/* -- Usa la funcion del saldo_recepcion o saldo_devolucion. --*/
		$select = '';
		if (isset($filtro['devolucion']) && $filtro['devolucion'] == 'S'){
			$select = " nvl(pkg_ordenes.saldo_devolucion (coi.nro_orden, coi.nro_renglon),0) saldo_recepcionar,
						trim(to_char(nvl(pkg_ordenes.saldo_devolucion (coi.nro_orden, coi.nro_renglon),0), '$999,999,999,990.0000')) saldo_recepcionar_format,";
			unset($filtro['devolucion']);
		}else{
			$select = " nvl(pkg_ordenes.saldo_recepcionar (coi.nro_orden, coi.nro_renglon),0) saldo_recepcionar,
						trim(to_char(nvl(pkg_ordenes.saldo_recepcionar (coi.nro_orden, coi.nro_renglon),0), '$999,999,999,990.0000')) saldo_recepcionar_format,";
		}
		/* ---- */

        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'coi', '1=1');
        $sql = "SELECT	coi.nro_orden || '||' || coi.nro_renglon || '||' || coi.COD_ARTICULO COD_ARTICULO,'Renglón: '|| coi.nro_renglon || 
						' Item: ' || coi.nro_item || ' - ' || coi.COD_ARTICULO || ' - ' || replace(replace(coi.DESCRIPCION, '<','«'), '>', '»')  lov_descripcion,
						coi.*,
						coi.nro_orden || '||' || coi.nro_renglon nro_orden_renglon,
						coi.cod_articulo || ' - ' || coi.descripcion articulo,
						coi.cod_articulo || ' - ' || replace(replace(coi.descripcion, '<','«'), '>', '»') cod_des_articulo,
						$select
						trim(to_char(coi.precio, '$999,999,999,990.00000000')) as precio_format,
						trim(to_char(cio.total, '$999,999,999,990.0000')) as total_format,
						trim(to_char(cio.total_impuesto, '$999,999,999,990.0000')) as total_impuesto_format
				FROM V_CO_ORDEN_ITEMS coi
				LEFT JOIN co_items_orden cio ON coi.nro_orden = cio.nro_orden AND coi.nro_renglon = cio.nro_renglon
				WHERE $where
				ORDER BY coi.COD_ARTICULO;";
        $datos = toba::db()->consultar($sql);
		foreach ($datos as $clave => $dato) {
			$datos[$clave]['detalle_format'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($dato['detalle'], 'detalle_'.$clave, 30, 1, true);
			$datos[$clave]['articulo_format'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($dato['articulo'], 'articulo_'.$clave, 40, 1, true);
			$datos[$clave]['unidad'] = dao_compras_general::get_des_unidad_medida($dato['unidad']);
		}
        return $datos;
    }
	
	public static function get_descripcion_item_orden($item_articulo) {
        if (isset($item_articulo)) {
			$arr_item_articulo = explode('||', $item_articulo);
			$where = '1=1';
			if (!empty($arr_item_articulo[0])) {
				$where .= " AND coi.nro_orden = " . quote($arr_item_articulo[0]);
			}
			if (!empty($arr_item_articulo[1])) {
				$where .= " AND coi.nro_renglon = " . quote($arr_item_articulo[1]);
			}
			if (!empty($arr_item_articulo[2])) {
				$where .= " AND coi.cod_articulo = " . quote($arr_item_articulo[2]);
			}
			$sql = "SELECT	coi.nro_orden || '||' || coi.nro_renglon || '||' || coi.COD_ARTICULO COD_ARTICULO,
							coi.DESCRIPCION DESCRIPCION,
							'Item: ' || coi.nro_item || ' - ' || coi.COD_ARTICULO || ' - ' || replace(replace(coi.DESCRIPCION, '<','«'), '>', '»')  lov_descripcion
					FROM V_CO_ORDEN_ITEMS coi
					WHERE $where
					ORDER BY nro_orden, nro_renglon, COD_ARTICULO;";
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
	
	public static function get_datos_item_orden($fecha, $nro_orden=null, $nro_renglon = null, $cod_articulo = null) 
	{
        if (isset($fecha) && (isset($nro_orden) || isset($nro_renglon) || isset($cod_articulo))) {
			$where = '1=1';
			if (!empty($nro_orden)) {
				$where .= " AND coi.nro_orden = " . quote($nro_orden);
			}
			if (!empty($nro_renglon)) {
				$where .= " AND coi.nro_renglon = " . quote($nro_renglon);
			}
			if (!empty($cod_articulo)) {
				$where .= " AND coi.cod_articulo = " . quote($cod_articulo);
			}
			$sql = "SELECT	coi.nro_orden || '||' || coi.nro_renglon || '||' || coi.COD_ARTICULO COD_ARTICULO,
							coi.DESCRIPCION DESCRIPCION,
							'Item: ' || coi.nro_item || ' - ' || coi.COD_ARTICULO || ' - ' || replace(replace(coi.DESCRIPCION, '<','«'), '>', '»')  lov_descripcion,
							coi.*,
							nvl(pkg_ordenes.saldo_recepcionar (coi.nro_orden, coi.nro_renglon),coi.cantidad) cantidad_recepcionar,
							nvl(pkg_ordenes.saldo_recepcionar (coi.nro_orden, coi.nro_renglon),0) saldo_recepcionar,
							nvl(pkg_ordenes.saldo_devolucion (coi.nro_orden, coi.nro_renglon),0) saldo_devolucion,
							nvl(pkg_Ordenes.Precio_Ajustado_1(coi.NRO_ORDEN , " . quote($fecha) . ",coi.NRO_RENGLON), coi.precio) precio_recepcionar,
							ca.ing_patrimonio, 
							ca.ing_stock, 
							ca.cod_partida
					FROM V_CO_ORDEN_ITEMS coi
					JOIN CO_ARTICULOS ca ON (ca.cod_articulo = coi.cod_articulo)
					WHERE $where
					ORDER BY nro_orden, nro_renglon";
			$datos = toba::db()->consultar_fila($sql);
			return $datos;
		} else {
			return array();
		}
    }
	
	public static function get_cantidad_total_lug_ent_item($nro_recepcion, $nro_renglon) {
        if (isset($nro_recepcion) && isset($nro_renglon)) {
			$sql_sel = "select NVL(SUM(NVL(cisle.cantidad, 0)), 0) cantidad
						from   co_items_recepcion_lug_ent cisle
						where  cisle.nro_recepcion = " . quote($nro_recepcion) . "
						AND cisle.nro_renglon = " . quote($nro_renglon) . ";";
			$datos = toba::db()->consultar_fila($sql_sel);
			if (isset($datos['cantidad'])) {
				return $datos['cantidad'];
			} else {
				return 0;
			}
		} else {
			return 0;
		}
    }
	
	static public function importar_impuestos_orden_compra($nro_recepcion, $nro_renglon) {
		if (isset($nro_recepcion) && isset($nro_renglon)) {
			$sql = "BEGIN :resultado := pkg_ordenes.importar_impuestos_orden(:nro_recepcion, :nro_renglon); END;";
			
			$parametros = array(array(	'nombre' => 'nro_recepcion',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_recepcion),
								array(	'nombre' => 'nro_renglon',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 32,
										'valor' => $nro_renglon),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al importar los impuestos de la orden de compra.', false);
			if (isset($resultado[2]['valor']) && !empty($resultado[2]['valor'])) {
				return $resultado[2]['valor'];
			} else {
				return 'NOK';
			}
		} else {
			return 'NOK';
		}
	}
	
	static public function get_impuestos_item_recepcion($nro_recepcion, $nro_renglon)
	{
		if (isset($nro_recepcion) && isset($nro_renglon)) {
			$sql_sel = "SELECT  ciri.*,
								trim(to_char(ciri.impuesto, '$999,999,999,990.00')) as importe,
								ai.cod_impuesto || '-' || ai.descripcion cod_des_impuesto
						FROM CO_ITEMS_RECEPCION_ITO ciri
						JOIN ad_impuestos ai ON ciri.cod_impuesto = ai.cod_impuesto
						WHERE nro_recepcion = " .quote($nro_recepcion)."
						AND nro_renglon = " .quote($nro_renglon).";";
			return toba::db()->consultar($sql_sel);
		} else {
			return array();
		}
	}
	
	static public function incorporar_item_recepcion($nro_recepcion, $nro_orden, $nro_renglon) {
		if (isset($nro_recepcion) && isset($nro_orden) && isset($nro_renglon)) {
			
			$sql = "BEGIN :resultado := pkg_recepciones.agregar_item_orden(:nro_recepcion, :NRO_ORDEN, :NRO_RENGLON); END;";
		
			$parametros = array(array(	'nombre' => 'nro_recepcion',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_recepcion),
								array(	'nombre' => 'nro_orden',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_orden),
								array(	'nombre' => 'nro_renglon',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_renglon),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al incorporar los items de la orden en la recepción de compra.', false);
			if (isset($resultado[3]['valor']) && !empty($resultado[3]['valor'])) {
				return $resultado[3]['valor'];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}
	
	public static function get_facturas_recepcion($nro_recepcion) {
        if (isset($nro_recepcion)) {
			$sql_sel = "select	cf.*,
								CASE
									WHEN cf.origen_afi = 'S' THEN 'Si'
									ELSE 'No'
								END origen_afi_format,
								cg.rv_meaning tipo_factura_format,
								trim(to_char(cf.monto, '$999,999,999,990.00')) as monto_format
						from   co_facturas cf
						left join ad_facturas af ON (cf.id_factura = af.id_factura) 
						left join cg_ref_codes cg ON (cf.tipo_factura = cg.rv_low_value AND cg.rv_domain = 'CO_TIPO_FACTURA') 
						where  cf.nro_recepcion = " . quote($nro_recepcion) . ";";
			$datos = toba::db()->consultar($sql_sel);
			return $datos;
		} else {
			return array();
		}
    }
	
	static public function asociar_factura($nro_recepcion, $id_factura, $importe) {
		if (isset($nro_recepcion) && isset($id_factura) && isset($importe)) {
			$sql = "BEGIN :resultado := pkg_recepciones.asociar_factura(:nro_recepcion, :id_factura, :importe);    END;";
			
			$parametros = array(array(	'nombre' => 'nro_recepcion',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_recepcion),
								array(	'nombre' => 'id_factura',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $id_factura),
								array(	'nombre' => 'importe',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 32,
										'valor' => $importe),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al asociar la factura en la recepción.', false);
			if (isset($resultado[3]['valor']) && !empty($resultado[3]['valor'])) {
				return $resultado[3]['valor'];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}
	
	public static function desasociar_factura($nro_recepcion, $secuenta)
	{
		if (isset($nro_recepcion) && isset($secuenta)) {
			$sql = "Delete co_facturas 
					where nro_recepcion = ".quote($nro_recepcion)."
					AND secuencia = ".quote($secuenta).";";
			return toba::db()->ejecutar($sql);
		} else {
			return false;
		}
	}
	
	static public function generar_factura($nro_recepcion, $fecha_emision, $tipo_factura, $forma_pago, $nro_factura, $id_caja_chica, $fecha_base_vto, $fecha_emision_fac, $con_transaccion = true) {
		$usuario = strtoupper(toba::usuario()->get_id());
		$sql = "BEGIN :resultado := pkg_recepciones.generar_factura(:nro_recepcion, to_date(substr(:fecha_emision,1,10),'yyyy-mm-dd'), :cod_tipo_factura, :cod_forma_pago, :nro_factura, :usuario, :id_caja_chica, :fecha_base_vto, :fecha_emision_fac, :id_factura); END;";
		if (is_null($id_caja_chica))
			$id_caja_chica = '';
		$parametros = array(array(	'nombre' => 'nro_recepcion',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 12,
									'valor' => $nro_recepcion),

							array(	'nombre' => 'fecha_emision',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 32,
									'valor' => $fecha_emision),

							array(	'nombre' => 'cod_tipo_factura',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 32,
									'valor' => $tipo_factura),

							array(	'nombre' => 'cod_forma_pago',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $forma_pago),

							array(	'nombre' => 'nro_factura',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 20,
									'valor' => $nro_factura),

							array(	'nombre' => 'usuario',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 100,
									'valor' => $usuario),

							array(	'nombre' => 'id_factura',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 100,
									'valor' => ''),

							array(	'nombre' => 'id_caja_chica',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 20,
									'valor' => $id_caja_chica),

							array(	'nombre' => 'fecha_base_vto',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 20,
									'valor' => $fecha_base_vto),

							array(	'nombre' => 'fecha_emision_fac',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 20,
									'valor' => $fecha_emision_fac),

							array(	'nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''));
		

		$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', '', $con_transaccion);
		if ($resultado[count($resultado)-1]['valor'] == 'OK'){
			toba::notificacion()->info('Se Genero la Factura con ID: '.$resultado[6]['valor']);
			return $resultado[6]['valor'];
		}else{
			toba::notificacion()->error($resultado[count($resultado)-1]['valor']);
			return $resultado[count($resultado)-1]['valor'];	
		}

		
	}


	static public function get_no_estados (){
		$no_estados = [];
		$sql = "select PKG_GENERAL.VALOR_PARAMETRO('RECEPCION_ESTADO_FINAL') estado_1,
					   PKG_GENERAL.VALOR_PARAMETRO('RECEPCION_ESTADO_FINAL_NOK') estado_2,
					   PKG_GENERAL.VALOR_PARAMETRO('RECEPCION_ESTADO_INICIAL') estado_3
				from dual";
		$datos = toba::db()->consultar_fila($sql);
		$no_estados[0] = $datos['estado_1'];
		$no_estados[1] = $datos['estado_2'];
		$no_estados[2] = $datos['estado_3'];
		return $no_estados;
	}
	
	static public function get_lov_item_recepcion_x_nombre($nombre, $filtro = [])
	{
		$where = ' 1=1 ';
        if (isset($nombre)) {
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('itre.nro_renglon', $nombre);
            $trans_cod_articulo = ctr_construir_sentencias::construir_translate_ilike('itre.cod_articulo', $nombre);
            $where .= " AND ($trans_nro OR $trans_cod_articulo)";
        }
		
        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'itre', '1=1');
		$sql = "SELECT itre.*, itre.nro_recepcion ||'||'||itre.nro_renglon ids
					  ,itre.nro_renglon
				       || ' - '
				       || itre.cod_articulo
				       || ' - '
				       || l_art.descripcion lov_descripcion
				  FROM co_items_recepcion itre, co_articulos l_art
				 WHERE itre.cod_articulo = l_art.cod_articulo
				   and $where
				   order by lov_descripcion";
		return toba::db()->consultar($sql);
	}
	static public function get_lov_item_recepcion_x_id($ids)
	{
		$key_array = explode(apex_qs_separador, $ids);
		$nro_recepcion = null;
		$nro_renglon = null;
		if (count($key_array) > 1) {
			$nro_recepcion  = $key_array[0];
			$nro_renglon = $key_array[1];
		}	
		$sql = "SELECT itre.nro_renglon
				       || ' - '
				       || itre.cod_articulo
				       || ' - '
				       || l_art.descripcion lov_descripcion
				  FROM co_items_recepcion itre, co_articulos l_art
				 WHERE itre.cod_articulo = l_art.cod_articulo
				   and itre.nro_renglon = ".quote($nro_renglon)." 
				   and itre.nro_recepcion = ".quote($nro_recepcion)."
				   order by lov_descripcion";

	    $datos = toba::db()->consultar_fila($sql);
	    return $datos['lov_descripcion'];
	}

	

	

}
?>
