<?php

class dao_pedidos_compra {
	
	public static function get_pedidos_internos($filtro = array()) {
        $where = " 1=1 ";
        
		if (isset($filtro['ids_comprobantes'])) {
            $where .= "AND cp.nro_pedido IN (" . $filtro['ids_comprobantes'] . ") ";
            unset($filtro['ids_comprobantes']);
        }
		
		if (isset($filtro['ambito_usuario']) && $filtro['ambito_usuario'] == '1') {
			$where .= " AND INSTR(Pkg_Usuarios.ambitos_usuario(".quote(strtoupper(toba::usuario()->get_id()))."), cp.cod_ambito) > 0";
			unset($filtro['ambito_usuario']);
		}
		
		
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'cp', '1=1');
        $sql_sel = "SELECT  cp.*,
							to_char(cp.fecha, 'DD/MM/YYYY') as fecha_format,
							CASE
								WHEN cp.interna = 'S' THEN 'Si'
								ELSE 'No'
							END interna_format,
							cas.cod_ambito || '-' || cas.descripcion ambito,
							css.cod_sector || '-' || css.descripcion sector
					FROM co_pedidos cp
					JOIN co_ambitos_seq cas ON cp.cod_ambito = cas.cod_ambito AND cp.seq_ambito = cas.seq_ambito
					JOIN co_sectores_seq css ON cp.cod_sector = css.cod_sector AND cp.seq_sector = css.seq_sector
					WHERE $where
					ORDER BY nro_pedido DESC;";
        $datos = toba::db()->consultar($sql_sel);
		
		foreach ($datos as $clave => $dato) {
			$datos[$clave]['observaciones_format'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($dato['observaciones'], 'observaciones_'.$clave, 50, 1, true);
		}
        return $datos;
    }
	
	public static function get_datos_pedido_interno($nro_pedido) {
		if (isset($nro_pedido)) {
			$sql_sel = "SELECT  cp.*,
								to_char(cp.fecha, 'DD/MM/YYYY') as fecha_format,
								CASE
									WHEN cp.interna = 'S' THEN 'Si'
									ELSE 'No'
								END interna_format,
								cas.cod_ambito || '-' || cas.descripcion ambito,
								css.cod_sector || '-' || css.descripcion sector
						FROM co_pedidos cp
						JOIN co_ambitos_seq cas ON cp.cod_ambito = cas.cod_ambito AND cp.seq_ambito = cas.seq_ambito
						JOIN co_sectores_seq css ON cp.cod_sector = css.cod_sector AND cp.seq_sector = css.seq_sector
						WHERE nro_pedido = " .quote($nro_pedido).";";
			$datos = toba::db()->consultar_fila($sql_sel);
			
			$datos['observaciones_format'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($datos['observaciones'], 'observaciones_1', 50, 1, true);
			
			return $datos;
		} else {
			return array();
		}
    }

    public static function get_items_pedido_compra($filtro) {
		$where = " 1=1 ";
        
		if (isset($filtro['importar_items_solicitud']) && $filtro['importar_items_solicitud'] == '1') {
			$where .= " AND cip.ENTREGADO = 'N' 
						AND cip.SISTEMA_COMPRA is null 
						AND (   cip.nro_solicitud IS NULL
							OR EXISTS (	SELECT 1
										FROM co_items_compra
										WHERE nro_solicitud = cip.nro_solicitud
										AND nro_renglon_solicitud = cip.nro_renglon_solicitud
										AND estado = 'DES'))
						AND ('PEND', 'S') IN (	SELECT	estado,
														pkg_usuarios.esta_en_bandeja (" . quote(toba::usuario()->get_id()) . ", cod_sector, cod_ambito, 'PED', NULL, NULL, interna, estado)
												FROM co_pedidos
												WHERE nro_pedido = cip.nro_pedido) ";
			unset($filtro['importar_items_solicitud']);
		}
		
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'cip', '1=1');
        $sql_sel = "SELECT  cip.*,
							to_char(cp.fecha, 'DD/MM/YYYY') as pedido_fecha_format,
							ca.cod_articulo || ' - ' || ca.descripcion articulo,
							css.cod_sector || '-' || css.descripcion sector,
							cp.anio pedido_anio,
							cp.numero pedido_nro
					FROM co_items_pedido cip
					JOIN co_pedidos cp ON cip.nro_pedido = cp.nro_pedido
					JOIN co_articulos ca ON cip.cod_articulo = ca.cod_articulo
					JOIN co_sectores_seq css ON cp.cod_sector = css.cod_sector AND cp.seq_sector = css.seq_sector
					WHERE $where
					ORDER BY cip.nro_pedido, cip.nro_renglon;";
        $datos = toba::db()->consultar($sql_sel);
		
		foreach ($datos as $clave => $dato) {
			$datos[$clave]['detalle_format'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($dato['detalle'], 'detalle_'.$clave, 30, 1, true);
			$datos[$clave]['articulo_format'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($dato['articulo'], 'articulo_'.$clave, 40, 1, true);
		}
        return $datos;
    }
	
	public static function get_datos_extras_encabezado_pedido_interno($nro_pedido) {
        if (isset($nro_pedido)) {
            $sql_sel = "SELECT  cp.estado
						FROM co_pedidos cp
						WHERE cp.nro_pedido = " . quote($nro_pedido) . ";";
            $datos = toba::db()->consultar_fila($sql_sel);
            return $datos;
        } else {
            return array();
        }
    }
	
	public static function get_estados_pedido_interno() {
        $sql_sel = "SELECT  crc.rv_low_value estado,
							crc.rv_meaning lov_descripcion
					FROM cg_ref_codes crc
					WHERE crc.rv_domain = 'CO_ESTADO_PEDIDO'
					ORDER BY crc.rv_meaning ASC;";
        $datos = toba::db()->consultar($sql_sel);
		return $datos;
    }
	
	public static function get_seguimiento_estados_pedido($nro_pedido) {
        if (isset($nro_pedido)) {
			$sql_sel = "SELECT  cep.*,
								to_char(cep.fecha, 'DD/MM/YYYY') as fecha_format,
								css.cod_sector || '-' || css.descripcion sector,
								crc.rv_meaning estado_format
						FROM co_estados_pedido cep
						JOIN co_sectores_seq css ON cep.cod_sector = css.cod_sector AND cep.seq_sector = css.seq_sector
						JOIN cg_ref_codes crc ON crc.rv_domain = 'CO_ESTADO_PEDIDO' AND crc.rv_low_value =  cep.estado
						WHERE cep.nro_pedido = " . quote($nro_pedido) . "
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
	
	static public function cambiar_estado($nro_pedido, $estado, $observaciones) {
		if (isset($nro_pedido) && isset($estado)) {
			if (!isset($observaciones)) {
				$observaciones = '';
			}
			$sql = "BEGIN :resultado := PKG_PEDIDOS.cambiar_estado(:nro_pedido,:estado,:observaciones); END;";
			
			$parametros = array(array(	'nombre' => 'nro_pedido',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_pedido),
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

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al cambiar el estado del pedido interno.', false);
			if (isset($resultado[3]['valor']) && !empty($resultado[3]['valor'])) {
				return $resultado[3]['valor'];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}
	
	public static function get_datos_articulo_pedido($cod_ambito, $interna, $cod_articulo) 
	{
        if (isset($cod_ambito) && isset($interna) && isset($cod_articulo)) {
			$sql = "select min(de.cod_deposito) cod_deposito
					from   st_depositos de,
						   co_sectores  se,
						   st_stock     st 
					where  de.cod_sector   = se.cod_sector
					and    de.cod_deposito = st.cod_deposito
					and    se.cod_ambito   = " . quote($cod_ambito) ."
					and    de.interno      = " . quote($interna) ."
					and    st.cod_articulo = " . quote($cod_articulo) .";";
			$datos = toba::db()->consultar_fila($sql);
			if (isset($datos) && isset($datos['cod_deposito'])) {
				$sql = "select unidad
						from   st_stock
						where  cod_deposito = " . quote($datos['cod_deposito']) ."
						and    st.cod_articulo = " . quote($cod_articulo) .";";
				$datos1 = toba::db()->consultar_fila($sql);
				if (isset($datos1) && isset($datos1['unidad'])) {
					return array('cod_deposito' => $datos['cod_deposito'],
								'unidad' => $datos1['unidad'],
						);
				} else {
					return array('cod_deposito' => $datos['cod_deposito'],
								'unidad' => null,
						);
				}
			} else {
				return array();
			}
		} else {
			return array();
		}
    }
	
	
	public static function get_cantidad_stock_x_unidad($cod_deposito, $cod_articulo, $unidad) 
	{
        if (isset($cod_deposito) && isset($unidad) && isset($cod_articulo)) {
			$sql = "select count(*) cantidad
					from   st_stock st,
						   st_stock_unidades un
					where  un.cod_stock = st.cod_stock
					and    cod_deposito = " . quote($cod_deposito) ."
					and    cod_articulo = " . quote($cod_articulo) ."
					and    un.unidad    = " . quote($unidad) .";";
			$datos = toba::db()->consultar_fila($sql);
			if (isset($datos) && isset($datos['cantidad'])) {
				return $datos['cantidad'];
			} else {
				return 0;
			}
		} else {
			return 0;
		}
    }
	
	public static function get_cantidad_egreso_detalle($nro_pedido, $nro_renglon) 
	{
        if (isset($nro_pedido) && isset($nro_renglon)) {
			$sql = "SELECT COUNT(1) cantidad
					FROM ST_EGRESOS_DETALLES
					WHERE NRO_PEDIDO = " . quote($nro_pedido) ."
					AND NRO_RENGLON_PEDIDO = " . quote($nro_renglon) .";";
			$datos = toba::db()->consultar_fila($sql);
			if (isset($datos) && isset($datos['cantidad'])) {
				return $datos['cantidad'];
			} else {
				return 0;
			}
		} else {
			return 0;
		}
    }
	
	static public function cambiar_entregado_item($nro_pedido, $nro_renglon, $entregado, $nro_egreso) {
		if (isset($nro_pedido) && isset($nro_renglon) && isset($entregado)) {
			if (!isset($nro_egreso)) {
				$nro_egreso = '';
			}
			$sql = "BEGIN :resultado := PKG_PEDIDOS.cambiar_entregado_item(:nro_pedido,:nro_renglon,:entregado,:nro_egreso); END;";
			
			$parametros = array(array(	'nombre' => 'nro_pedido',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_pedido),
								array(	'nombre' => 'nro_renglon',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 32,
										'valor' => $nro_renglon),
								array(	'nombre' => 'entregado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4,
										'valor' => $entregado),
								array(	'nombre' => 'nro_egreso',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 40,
										'valor' => $nro_egreso),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al cambiar el estado de entregado del pedido interno.', false);
			if (isset($resultado[4]['valor']) && !empty($resultado[4]['valor'])) {
				return $resultado[4]['valor'];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}
	
	public static function get_egresos_items_pedido_interno($nro_pedido, $nro_renglon)
	{
		if (isset($nro_pedido) && isset($nro_renglon)) {
			$sql_sel = "SELECT  sed.*,
								se.numero,
								to_char(se.fecha, 'DD/MM/YYYY') as fecha_format
						FROM ST_EGRESOS_DETALLES sed
						JOIN ST_EGRESOS se ON sed.nro_egreso = se.nro_egreso
						WHERE sed.nro_pedido = " . quote($nro_pedido) . "
						AND sed.nro_renglon_pedido = " . quote($nro_renglon) . "
						ORDER BY sed.nro_renglon ASC;";
			$datos = toba::db()->consultar($sql_sel);
			return $datos;
		} else {
			return array();
		}
	}

	public static function get_lov_pedidos_x_nombre ($nombre, $filtro = [])
	{
		if (isset($nombre)) { 
   			$trans_cod = ctr_construir_sentencias::construir_translate_ilike('cope.nro_pedido', $nombre);
   			$trans_des = ctr_construir_sentencias::construir_translate_ilike('cope.numero', $nombre);
            $where = "($trans_cod OR $trans_des)";
        } else {
            $where = '1=1';
        }

        $where .= ' and '.ctr_construir_sentencias::get_where_filtro($filtro, 'cope');

		$sql = "SELECT cope.*,  '#'
			         || cope.nro_pedido
			         || ' ( '
			         || cope.numero
			         || ' ) - '
			         || cose.cod_sector
			         || ' '
			         || cose.descripcion
			         || ' - '
			         || TO_CHAR (cope.fecha, 'dd/mm/yyyy') lov_descripcion
			    FROM co_pedidos cope, CO_SECTORES_SEQ cose
			   WHERE cope.cod_sector = cose.cod_sector and cope.seq_sector 
			   = cose.seq_sector AND $where
			ORDER BY lov_descripcion ";
		return toba::db()->consultar($sql);
	}

	public static function get_lov_pedidos_x_nro_pedido ($nro_pedido)
	{
		$sql = "SELECT '#'
			         || cope.nro_pedido
			         || ' ( '
			         || cope.numero
			         || ' ) - '
			         || cose.cod_sector
			         || ' '
			         || cose.descripcion
			         || ' - '
			         || TO_CHAR (cope.fecha, 'dd/mm/yyyy') lov_descripcion
			    FROM co_pedidos cope, co_sectores cose
			   WHERE cope.cod_sector = cose.cod_sector and cope.nro_pedido = ".$nro_pedido;
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}

}

?>
