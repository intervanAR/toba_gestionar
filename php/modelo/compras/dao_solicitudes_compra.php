<?php

class dao_solicitudes_compra {

    public static function get_solicitudes_compra($filtro = array(), $orden = array()) {
    	$desde= null;
		$hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}

        $where = self::armar_where($filtro);

        $sql_sel = "SELECT  csc.*,
					        concat_all('SELECT cps.id_preventivo
					  FROM co_solicitudes_preventivos cps LEFT JOIN ad_preventivos ap
					       ON (cps.id_preventivo = ap.id_preventivo)
					 WHERE nro_solicitud ='||csc.nro_solicitud, ', ') as preventivos,
							to_char(csc.fecha, 'DD/MM/YYYY') as fecha_format,
							to_char(csc.fecha_imputacion, 'DD/MM/YYYY') as fecha_imputacion_format,
							to_char(csc.fecha_resolucion, 'DD/MM/YYYY') as fecha_resolucion_format,
							trim(to_char(csc.valor_estimado, '$999,999,999,990.00')) as valor_estimado_format,
							CASE
								WHEN csc.interna = 'S' THEN 'Si'
								ELSE 'No'
							END interna_format,
							CASE
								WHEN csc.gasto_propio = 'S' THEN 'Si'
								ELSE 'No'
							END gasto_propio_format,
							CASE
								WHEN csc.presupuestario = 'S' THEN 'Si'
								ELSE 'No'
							END presupuestario_format,
							ke.nro_expediente as nro_expediente,
							cas.cod_ambito || '-' || cas.descripcion ambito,
							css.cod_sector || '-' || css.descripcion sector,
							(select descripcion from kr_monedas where cod_moneda = csc.cod_moneda) moneda_format,
							(select rv_meaning from cg_ref_codes where rv_low_value = csc.estado and rv_domain = 'CO_ESTADO_SOLICITUD' ) estado_format,
         (select rv_meaning from cg_ref_codes where rv_low_value = csc.destino_compra and rv_domain = 'CO_DESTINO_COMPRA' ) destino_compra_format,
         (select rv_meaning from cg_ref_codes where rv_low_value = csc.tipo_compra and rv_domain = 'CO_TIPO_COMPRA' ) tipo_compra_format
					FROM co_solicitudes csc
					JOIN co_ambitos_seq cas ON csc.cod_ambito = cas.cod_ambito AND csc.seq_ambito = cas.seq_ambito
					JOIN co_sectores_seq css ON csc.cod_sector = css.cod_sector AND csc.seq_sector = css.seq_sector
					LEFT JOIN kr_expedientes ke ON csc.id_expediente = ke.id_expediente
					WHERE $where
					ORDER BY csc.nro_solicitud DESC";

        $sql = dao_varios::paginador($sql_sel, null, $desde, $hasta, null, $orden);
        $datos = toba::db()->consultar($sql);

		foreach ($datos as $clave => $dato) {
			$datos[$clave]['observaciones_format'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($dato['observaciones'], 'observaciones_'.$clave, 50, 1, true);
			$datos[$clave]['destino_format'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($dato['destino'], 'destino_'.$clave, 30, 1, true);
		}
        return $datos;
    }

    public static function armar_where($filtro = array()){
    	if(isset($filtro['numrow_desde'])){
			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}

    	$where = " 1=1 ";

    	if (isset($filtro['cod_articulo'])){
    		$where .=" AND csc.nro_solicitud IN (SELECT DISTINCT i.nro_solicitud
                                                   FROM co_items_solicitud i
                                                  WHERE i.cod_articulo =
                                                                  ".$filtro['cod_articulo'].")";
    		unset($filtro['cod_articulo']);
    	}

		if (isset($filtro['ids_comprobantes'])) {
            $where .= "AND csc.nro_solicitud IN (" . $filtro['ids_comprobantes'] . ") ";
            unset($filtro['ids_comprobantes']);
        }

		if (isset($filtro['ambito_usuario']) && $filtro['ambito_usuario'] == '1') {
			$where .= " AND INSTR(Pkg_Usuarios.ambitos_usuario(".quote(strtoupper(toba::usuario()->get_id()))."), csc.cod_ambito) > 0";
			unset($filtro['ambito_usuario']);
		}

		if (isset($filtro['excluir_nro_solicitud'])) {
            $where .= " AND csc.nro_solicitud <> " . $filtro['excluir_nro_solicitud'] . " ";
            unset($filtro['excluir_nro_solicitud']);
        }

		if (isset($filtro['unificar_solicitudes']) && $filtro['unificar_solicitudes'] == '1') {
			$where .= " AND csc.estado = Pkg_General.valor_parametro('SOLICITUD_ESTADO_UNIFICA')
						AND PKG_USUARIOS.esta_en_bandeja(" . quote(toba::usuario()->get_id()) . ", csc.cod_sector, csc.cod_ambito, 'SOL', csc.tipo_compra, csc.presupuestario, csc.interna, csc.estado) = 'S'
						AND PKG_USUARIOS.usuario_pertenece(csc.cod_ambito, " . quote(toba::usuario()->get_id()) . ") = 'S'
						AND csc.unificacion = 'N' ";
			unset($filtro['unificar_solicitudes']);
		}
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'csc', '1=1');
    	return $where;

    }

    public static function get_cantidad ($filtro = array()){
    	$where = self::armar_where($filtro);
    	$sql = "select count(nro_solicitud) cantidad
    			  FROM co_solicitudes csc
					JOIN co_ambitos_seq cas ON csc.cod_ambito = cas.cod_ambito AND csc.seq_ambito = cas.seq_ambito
					JOIN co_sectores_seq css ON csc.cod_sector = css.cod_sector AND csc.seq_sector = css.seq_sector
					LEFT JOIN kr_expedientes ke ON csc.id_expediente = ke.id_expediente
					WHERE $where ";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['cantidad'];
    }

    public static function get_proximo_nro_item($nro_solicitud){
    	$sql = "SELECT NVL (MAX (nro_item), 0)+1 numero
				  FROM co_items_solicitud
				 WHERE nro_solicitud = $nro_solicitud ";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['numero'];
    }

	public static function get_proximo_nro_renglon($nro_solicitud){
    	$sql = "SELECT NVL (MAX (nro_renglon), 0)+1 numero
				  FROM co_items_solicitud
				 WHERE nro_solicitud = $nro_solicitud ";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['numero'];
    }

	public static function get_item_solicitud ($nro_solicitud, $nro_renglon){
		$sql = "SELECT it.*,
					   art.cod_articulo || ' - ' || art.descripcion articulo,
				       pa.cod_partida || ' - ' || pa.descripcion partida,
				       (SELECT rv_meaning
				          FROM cg_ref_codes
				         WHERE rv_domain = 'CO_UNIDAD_MEDIDA'
				           AND rv_low_value = it.unidad) unidad_format
				  FROM co_articulos art, co_items_solicitud it LEFT JOIN pr_partidas pa
				       ON it.cod_partida = pa.cod_partida
				 WHERE it.cod_articulo = art.cod_articulo
				 	   and it.nro_solicitud = ".quote($nro_solicitud)." and it.nro_renglon = ".quote($nro_renglon);
		return toba::db()->consultar_fila($sql);
	}

	public static function get_solicitudes_compra_x_nombre($nombre = '', $filtro = array()) {
        if (isset($nombre)) {
			$campos = array(
					'csc.nro_solicitud',
			);
			$where = ctr_construir_sentencias::construir_sentencia_busqueda($nombre, $campos, true);
		} else {
			$where = '1=1';
		}

		if (isset($filtro['ids_comprobantes'])) {
            $where .= "AND csc.nro_solicitud IN (" . $filtro['ids_comprobantes'] . ") ";
            unset($filtro['ids_comprobantes']);
        }

		if (isset($filtro['ambito_usuario']) && $filtro['ambito_usuario'] == '1') {
			$where .= " AND INSTR(Pkg_Usuarios.ambitos_usuario(".quote(strtoupper(toba::usuario()->get_id()))."), csc.cod_ambito) > 0";
			unset($filtro['ambito_usuario']);
		}
		if (isset($filtro['mostrar_compras']) && $filtro['mostrar_compras'] == '1') {
			$where .= " AND PKG_USUARIOS.esta_en_bandeja(".quote(strtoupper(toba::usuario()->get_id())).", csc.cod_sector,csc.cod_ambito,'SOL',nvl(csc.tipo_compra,null),csc.presupuestario,csc.interna,csc.estado) = 'S'";
			$where .= " AND PKG_ESTADOS.estado_valido('SOL',csc.tipo_compra,csc.presupuestario,csc.interna,csc.estado,Pkg_General.valor_parametro ('SOLICITUD_ESTADO_FINAL'),'N') = 'S'";
			$where .= " AND csc.estado = Pkg_General.valor_parametro ('SOLICITUD_ESTADO_SIG_COMP')";
			unset($filtro['mostrar_compras']);
		}

		if (isset($filtro['excluir_nro_solicitud'])) {
            $where .= " AND csc.nro_solicitud <> " . $filtro['excluir_nro_solicitud'] . " ";
            unset($filtro['excluir_nro_solicitud']);
        }

		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'csc', '1=1');
        $sql_sel = "SELECT  csc.*,
							csc.nro_solicitud || ' (Exp.: ' ||ke.nro_expediente || ', Sec.: ' || css.cod_sector || '-' || css.descripcion || ', Nro.: ' || csc.numero || ', año: ' || csc.anio || ', Tipo compra: ' || csc.tipo_compra || ')' lov_descripcion
					FROM co_solicitudes csc
					JOIN co_ambitos_seq cas ON csc.cod_ambito = cas.cod_ambito AND csc.seq_ambito = cas.seq_ambito
					JOIN co_sectores_seq css ON csc.cod_sector = css.cod_sector AND csc.seq_sector = css.seq_sector
					LEFT JOIN kr_expedientes ke ON csc.id_expediente = ke.id_expediente
					WHERE $where
					ORDER BY nro_solicitud DESC;";
        $datos = toba::db()->consultar($sql_sel);
        return $datos;
    }

	static public function get_solicitudes_compra_x_id($nro_solicitud) {

        if (isset($nro_solicitud)) {
            $sql = "SELECT  csc.nro_solicitud || ' (Exp.: ' ||ke.nro_expediente || ', Sec.: ' || css.cod_sector || '-' || css.descripcion || ', Nro.: ' || csc.numero || ', año: ' || csc.anio || ', Tipo compra: ' || csc.tipo_compra || ')' lov_descripcion
					FROM co_solicitudes csc
					JOIN co_ambitos_seq cas ON csc.cod_ambito = cas.cod_ambito AND csc.seq_ambito = cas.seq_ambito
					JOIN co_sectores_seq css ON csc.cod_sector = css.cod_sector AND csc.seq_sector = css.seq_sector
					LEFT JOIN kr_expedientes ke ON csc.id_expediente = ke.id_expediente
                    WHERE csc.nro_solicitud = " . quote($nro_solicitud) . ";";

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

	public static function get_estados_solicitud() {
        $sql_sel = "SELECT  crc.rv_low_value estado,
							crc.rv_meaning lov_descripcion
					FROM cg_ref_codes crc
					WHERE crc.rv_domain = 'CO_ESTADO_SOLICITUD'
					ORDER BY crc.rv_meaning ASC;";
        $datos = toba::db()->consultar($sql_sel);
		return $datos;
    }

	public static function get_items_imputacion($nro_solicitud) {
        if (isset($nro_solicitud)) {
			$sql_sel = "select *
						from   co_items_solicitud_imputacion
						where  nro_solicitud = " . quote($nro_solicitud) . ";";
			$datos = toba::db()->consultar($sql_sel);
			return $datos;
		} else {
			return array();
		}
    }

	public static function get_existe_expediente_solicitud($nro_solicitud, $id_expediente) {
		$where = " id_expediente = ".quote($id_expediente);
		if (!is_null($nro_solicitud) && !empty($nro_solicitud)){
			$where .= " and nro_solicitud <> ".quote($nro_solicitud);
		}
		$sql_sel = "select count(1) cant
					from co_solicitudes
					where $where";
		$datos = toba::db()->consultar_fila($sql_sel);
		if (isset($datos) && !empty($datos) && isset($datos['cant']) && $datos['cant'] > 0) {
			return true;
		} else {
			return false;
		}

    }

	public static function get_datos_extras_encabezado_solicitud_compra($nro_solicitud) {
        if (isset($nro_solicitud)) {
            $sql_sel = "SELECT  cs.valor_estimado,
								cs.estado,
								cs.unificacion,
								cs.fecha_imputacion
						FROM co_solicitudes cs
						WHERE cs.nro_solicitud = " . quote($nro_solicitud) . ";";
            $datos = toba::db()->consultar_fila($sql_sel);
            return $datos;
        } else {
            return array();
        }
    }

	static public function get_preventivo_valido($nro_solicitud) {
		if (isset($nro_solicitud)) {
			$sql_sel = "SELECT	pkg_solicitudes.Preventivo_Valido(" . quote($nro_solicitud) . ") resultado
						FROM DUAL;";
			$datos = toba::db()->consultar_fila($sql_sel);
			if (isset($datos) && !empty($datos) && isset($datos['resultado'])) {
				return $datos['resultado'];
			} else {
				return 'NOK';
			}
		} else {
			return 'NOK';
		}
	}

	static public function get_pedido_cotizacion_tramite($nro_solicitud) {
		if (isset($nro_solicitud)) {
			$sql_sel = "SELECT	pkg_solicitudes.Pedido_Cotizacion_En_Tramite(" . quote($nro_solicitud) . ") resultado
						FROM DUAL;";
			$datos = toba::db()->consultar_fila($sql_sel);
			if (isset($datos) && !empty($datos) && isset($datos['resultado'])) {
				return $datos['resultado'];
			} else {
				return 'N';
			}
		} else {
			return 'N';
		}
	}

	public static function get_datos_solicitud_compra($nro_solicitud) {
        if (isset($nro_solicitud)) {
			$sql_sel = "SELECT  csc.*,
								to_char(csc.fecha, 'DD/MM/YYYY') as fecha_format,
								to_char(csc.fecha_imputacion, 'DD/MM/YYYY') as fecha_imputacion_format,
								to_char(csc.fecha_resolucion, 'DD/MM/YYYY') as fecha_resolucion_format,
								trim(to_char(csc.valor_estimado, '$999,999,999,990.00')) as valor_estimado_format,
								CASE
									WHEN csc.interna = 'S' THEN 'Si'
									ELSE 'No'
								END interna_format,
								CASE
									WHEN csc.gasto_propio = 'S' THEN 'Si'
									ELSE 'No'
								END gasto_propio_format,
								CASE
									WHEN csc.presupuestario = 'S' THEN 'Si'
									ELSE 'No'
								END presupuestario_format,
								ke.nro_expediente as nro_expediente,
								cas.cod_ambito || '-' || cas.descripcion ambito,
								css.cod_sector || '-' || css.descripcion sector
						FROM co_solicitudes csc
						JOIN co_ambitos_seq cas ON csc.cod_ambito = cas.cod_ambito AND csc.seq_ambito = cas.seq_ambito
						JOIN co_sectores_seq css ON csc.cod_sector = css.cod_sector AND csc.seq_sector = css.seq_sector
						LEFT JOIN kr_expedientes ke ON csc.id_expediente = ke.id_expediente
						WHERE csc.nro_solicitud = " . quote($nro_solicitud) . "
						ORDER BY nro_solicitud DESC;";
			$datos = toba::db()->consultar_fila($sql_sel);
			return $datos;
		} else {
			return array();
		}
    }

	public static function get_nro_compra_x_nro_solicitud($nro_solicitud) {
        if (isset($nro_solicitud)) {
            $sql_sel = "SELECT nro_compra
					      FROM co_compras co, cg_ref_codes cg
					     WHERE cg.rv_domain = 'CO_ESTADO_COMPRA' AND cg.rv_low_value = co.estado
					       and co.nro_solicitud =  " . quote($nro_solicitud) . "
  			          ORDER BY cg.rv_abbreviation DESC ";
            $datos = toba::db()->consultar_fila($sql_sel);
			if (isset($datos) && !empty($datos) && isset($datos['nro_compra'])) {
				return $datos['nro_compra'];
			} else {
				null;
			}
        } else {
            return null;
        }
    }

	public static function get_seguimiento_estados_solicitud($nro_solicitud) {
        if (isset($nro_solicitud)) {
			$sql_sel = "SELECT  ces.*,
								to_char(ces.fecha, 'DD/MM/YYYY') as fecha_format,
								css.cod_sector || '-' || css.descripcion sector,
								crc.rv_meaning estado_format
						FROM co_estados_solicitud ces
						JOIN co_sectores_seq css ON ces.cod_sector = css.cod_sector AND ces.seq_sector = css.seq_sector
						JOIN cg_ref_codes crc ON crc.rv_domain = 'CO_ESTADO_SOLICITUD' AND crc.rv_low_value =  ces.estado
						WHERE ces.nro_solicitud = " . quote($nro_solicitud) . "
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

	static public function copiar_solicitud($nro_solicitud) {
		if (isset($nro_solicitud)) {
			$sql = "BEGIN :resultado := PKG_SOLICITUDES.copiar_solicitud(:nro_solicitud,:nro_solicitud_des); END;";

			$parametros = array(array(	'nombre' => 'nro_solicitud',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_solicitud),
								array(	'nombre' => 'nro_solicitud_des',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => ''),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al copiar la solicitud.', true);
			if (isset($resultado[1]['valor']) && !empty($resultado[1]['valor'])) {
				return $resultado[1]['valor'];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	static public function cambiar_estado($nro_solicitud, $estado, $observaciones) {
		if (isset($nro_solicitud) && isset($estado)) {
			if (!isset($observaciones)) {
				$observaciones = '';
			}
			$sql = "BEGIN :resultado := PKG_SOLICITUDES.cambiar_estado(:estado, :nro_solicitud, :observaciones); END;";

			$parametros = array(array(	'nombre' => 'estado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 32,
										'valor' => $estado),
								array(	'nombre' => 'nro_solicitud',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_solicitud),
								array(	'nombre' => 'observaciones',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => $observaciones),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros,null,null,false);
			if (isset($resultado[3]['valor']) && !empty($resultado[3]['valor'])) {
				return $resultado[3]['valor'];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	static public function asociar_preventivo($nro_solicitud, $id_preventivo, $nro_preventivo, $anio) {
		if (isset($nro_solicitud) && isset($id_preventivo) && isset($nro_preventivo) && isset($anio)) {
			$sql = "BEGIN :resultado := pkg_solicitudes.asociar_preventivo(:nro_solicitud, :id_preventivo, :nro_preventivo, :anio);  END;";

			$parametros = array(array(	'nombre' => 'nro_solicitud',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_solicitud),
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

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al asociar el preventivo en la solicitud.', false);
			if (isset($resultado[4]['valor']) && !empty($resultado[4]['valor'])) {
				return $resultado[4]['valor'];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	static public function desasociar_preventivo($nro_solicitud, $secuencia) {
		if (isset($nro_solicitud) && isset($secuencia)) {
			$sql = "BEGIN :resultado := pkg_solicitudes.desasociar_preventivo(:NRO_SOLICITUD, :SECUENCIA);     END;";

			$parametros = array(array(	'nombre' => 'nro_solicitud',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_solicitud),
								array(	'nombre' => 'secuencia',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $secuencia),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al desasociar el preventivo en la solicitud.', false);
			if (isset($resultado[2]['valor']) && !empty($resultado[2]['valor'])) {
				return $resultado[2]['valor'];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	public static function get_cantidad_preventivos($nro_solicitud) {
        if (isset($nro_solicitud)) {
			$sql_sel = "select count(*) cantidad
						from   co_solicitudes_preventivos
						where  nro_solicitud = " . quote($nro_solicitud) . ";";
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

	public static function get_cantidad_preventivos_aprobados($nro_solicitud) {
        if (isset($nro_solicitud)) {
			$sql_sel = "select count(*) cantidad
						from   co_solicitudes_preventivos csp
						join ad_preventivos ap ON csp.id_preventivo = ap.id_preventivo
						where  nro_solicitud = " . quote($nro_solicitud) . "
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

	public static function get_cantidad_imputacion($nro_solicitud, $nro_renglon) {
        if (isset($nro_solicitud) && isset($nro_renglon)) {
			$sql_sel = "select count(*) cantidad
						from   co_items_solicitud_imputacion
						where  nro_solicitud = " . quote($nro_solicitud) . "
						AND nro_renglon = " . quote($nro_renglon) . ";";
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

	public static function get_tiene_imputacion_item($nro_solicitud, $nro_renglon) {
		$cantidad = self::get_cantidad_imputacion($nro_solicitud, $nro_renglon);
		if ($cantidad > 0) {
			return 'S';
		} else {
			return 'N';
		}
	}

	static public function get_reserva_interna_afi($nro_solicitud, $con_transaccion = true) {
		if (isset($nro_solicitud)) {
			if ($con_transaccion)
				toba::db()->abrir_transaccion();

			$sql = "BEGIN :resultado := pkg_solicitudes.generar_reserva_interna_afi(:nro_solicitud); END;";

			$parametros = array(array(	'nombre' => 'nro_solicitud',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_solicitud),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros, '', 'Error al generar la reserva interna de AFI en la solicitud.', false);
			if ($con_transaccion)
				toba::db()->abortar_transaccion();

			if (isset($resultado[1]['valor']) && !empty($resultado[1]['valor'])) {
				return $resultado[1]['valor'];
			} else {
				return 'NOK';
			}
		} else {
			return 'NOK';
		}
	}

	static public function unificar_solicitudes($nro_solicitud, $solicitudes_unificar=array()) {
		if (isset($nro_solicitud) && isset($solicitudes_unificar) && !empty($solicitudes_unificar)) {

			$sql_sol_uni = '';
			$i=1;
			foreach ($solicitudes_unificar as $nro_solicitud_unificar) {
				$sql_sol_uni .= " solicitudes_unificar($i):= $nro_solicitud_unificar; ";
				$i++;
			}
			$sql = "DECLARE solicitudes_unificar PKG_SOLICITUDES.ARRAY_SOLICITUDES; BEGIN $sql_sol_uni :resultado := PKG_SOLICITUDES.unificar_items(:nro_solicitud, solicitudes_unificar); END;";

			$parametros = array(array(	'nombre' => 'nro_solicitud',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_solicitud),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al unificar las solicitudes de compra.', false);
			if (isset($resultado[1]['valor']) && !empty($resultado[1]['valor'])) {
				return $resultado[1]['valor'];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	static public function incorporar_item_solicitud($nro_solicitud, $nro_pedido, $nro_renglon) {
		if (isset($nro_solicitud) && isset($nro_pedido) && isset($nro_renglon)) {

			$sql = "BEGIN :resultado := PKG_SOLICITUDES.incorporar_item_pedido(:nro_solicitud, :nro_pedido, :nro_renglon); END;";

			$parametros = array(array(	'nombre' => 'nro_solicitud',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_solicitud),
								array(	'nombre' => 'nro_pedido',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_pedido),
								array(	'nombre' => 'nro_renglon',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_renglon),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al incorporar los items del pedido en la solicitud de compra.', false);
			if (isset($resultado[3]['valor']) && !empty($resultado[3]['valor'])) {
				return $resultado[3]['valor'];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	public static function get_cantidad_total_lug_ent_item($nro_solicitud, $nro_renglon) {
        if (isset($nro_solicitud) && isset($nro_renglon)) {
			$sql_sel = "select NVL(SUM(NVL(cisle.cantidad, 0)), 0) cantidad
						from   co_items_solicitud_lug_ent cisle
						where  cisle.nro_solicitud = " . quote($nro_solicitud) . "
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

	public static function get_preventivos_solicitud($nro_solicitud, $filtro = array()) {
        if (isset($nro_solicitud)) {
        	$where = ' 1=1 ';

        	if (isset($filtro['aprobado'])){
        		$where .=" and ap.aprobado = '".$filtro['aprobado']."'";
        		unset($filtro['aprobado']);
        	}
        	if (isset($filtro['anulado'])){
        		$where .=" and ap.anulado = '".$filtro['anulado']."'";
        		unset($filtro['anulado']);
        	}

        	$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'cps', '1=1');

			$sql_sel = "select	cps.*,
								CASE
									WHEN ap.anulado = 'S' THEN 'Si'
									ELSE 'No'
								END anulado_preventivo,
								CASE
									WHEN cps.origen_afi = 'S' THEN 'Si'
									ELSE 'No'
								END origen_afi_format,
								CASE
									WHEN ap.ID_PREVENTIVO_REI IS NOT NULL THEN 'Si'
									ELSE 'No'
								END reimputacion_preventivo,
								to_char(ap.FECHA_ANULACION, 'DD/MM/YYYY') as fecha_anulacion_preventivo,
								to_char(ap.FECHA_COMPROBANTE, 'DD/MM/YYYY') as fecha_preventivo
						from   co_solicitudes_preventivos cps
						left join ad_preventivos ap ON (cps.id_preventivo = ap.id_preventivo)
						where  nro_solicitud = " . quote($nro_solicitud) . "
							and $where ";
			$datos = toba::db()->consultar($sql_sel);
			return $datos;
		} else {
			return array();
		}
    }

	public static function actualizar_fecha_imputacion($nro_solicitud, $fecha_inputacion) {
		if (isset($nro_solicitud) && isset($fecha_inputacion)) {
			$sql = "UPDATE CO_SOLICITUDES
					SET fecha_imputacion = " . quote($fecha_inputacion) . "
					WHERE nro_solicitud = " . quote($nro_solicitud) . ";";
			toba::db()->ejecutar($sql);
		}
	}

	static public function generar_reserva_interna_afi($nro_solicitud) {
		if (isset($nro_solicitud)) {

			$sql = "BEGIN :resultado := pkg_solicitudes.generar_reserva_interna_afi(:nro_solicitud); END;";

			$parametros = array(array(	'nombre' => 'nro_solicitud',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_solicitud),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros, '', 'Error al generar la reserva interna de AFI en la solicitud.', false);

			if (isset($resultado[1]['valor']) && !empty($resultado[1]['valor'])) {
				return $resultado[1]['valor'];
			} else {
				return 'NOK';
			}
		} else {
			return 'NOK';
		}
	}

	static public function anular_reserva_interna_afi($nro_solicitud) {
		if (isset($nro_solicitud)) {

			$sql = "BEGIN :resultado := pkg_solicitudes.anular_reserva_interna_afi(:nro_solicitud); END;";

			$parametros = array(array(	'nombre' => 'nro_solicitud',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_solicitud),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros, '', 'Error al anular la reserva interna de AFI en la solicitud.', false);

			if (isset($resultado[1]['valor']) && !empty($resultado[1]['valor'])) {
				return $resultado[1]['valor'];
			} else {
				return 'NOK';
			}
		} else {
			return 'NOK';
		}
	}

	public static function actualizar_valor_estimado($nro_solicitud) {
		if (isset($nro_solicitud)) {
			$sql = "UPDATE CO_SOLICITUDES cs
					SET valor_estimado = (	SELECT NVL(SUM(NVL(cis.total_estimado, 0)),0)
											FROM co_items_solicitud cis
											WHERE cis.nro_solicitud = cs.nro_solicitud)
					WHERE cs.nro_solicitud = " . quote($nro_solicitud) . ";";
			toba::db()->ejecutar($sql);
		}
	}

	public static function borrar_item ($nro_solicitud, $nro_renglon){
		$sql = "BEGIN :resultado := pkg_solicitudes.borrar_item_solicitud(:nro_solicitud, :nro_renglon); END;";

			$parametros = array(array(	'nombre' => 'nro_solicitud',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 12,
										'valor' => $nro_solicitud),
								array(	'nombre' => 'nro_renglon',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 4,
										'valor' => $nro_renglon),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''));

			$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros, '', '', false);

			if (isset($resultado[2]['valor']) && !empty($resultado[2]['valor'])) {
				return $resultado[2]['valor'];
			} else {
				return 'NOK';
			}
	}

	public static function get_tiene_subitems($nro_solicitud, $nro_item){
		$sql = "SELECT pkg_solicitudes.get_tiene_subitems($nro_solicitud, $nro_item) tiene_subitems FROM DUAL;";

		$datos = toba::db()->consultar_fila($sql);
		return $datos['tiene_subitems'];
	}

	public static function generar_imputacion ($nro_solicitud, $nro_renglon){
		$sql = "BEGIN :resultado := pkg_solicitudes.generar_imputacion(:nro_solicitud, :nro_renglon); END;";

			$parametros = array(array(	'nombre' => 'nro_solicitud',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 12,
										'valor' => $nro_solicitud),
								array(	'nombre' => 'nro_renglon',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 4,
										'valor' => $nro_renglon),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''));

			$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros, '', '', false);

			if (isset($resultado[2]['valor']) && !empty($resultado[2]['valor'])) {
				return $resultado[2]['valor'];
			} else {
				return 'NOK';
			}
	}

	public static function get_archivos_adjuntos($nro_solicitud) {
        if (isset($nro_solicitud)) {
			$sql_sel = "select	ca.*,
								ca.nro_archivo || ' - ' || ca.descripcion archivo,
								to_char(ca.fecha, 'DD/MM/YYYY') as fecha_format,
								crc.rv_meaning mime_type_format,
								NVL(round(NVL(dbms_lob.getlength(dato), 0) /1024,2), 0) kb
						from   co_archivos_sol ca
						LEFT OUTER JOIN CG_REF_CODES crc ON (crc.rv_domain = 'CO_MIME_TYPES' AND crc.rv_low_value = ca.mime_type)
						where  ca.nro_solicitud = " . quote($nro_solicitud) . ";";
			$datos = toba::db()->consultar($sql_sel);
			return $datos;
		} else {
			return array();
		}
    }

	public static function get_archivo_compra($nro_solicitud, $nro_archivo) {
        if (isset($nro_solicitud) && isset($nro_archivo)) {
			$sql_sel = "SELECT	ca.*,
								ca.nro_archivo || ' - ' || ca.descripcion archivo,
								to_char(ca.fecha, 'DD/MM/YYYY') as fecha_format,
								crc.rv_meaning mime_type_format,
								NVL(round(NVL(dbms_lob.getlength(dato), 0) /1024,2), 0) kb
						  FROM  co_archivos_sol ca
			        LEFT OUTER JOIN CG_REF_CODES crc ON (crc.rv_domain = 'CO_MIME_TYPES' AND crc.rv_low_value = ca.mime_type)
						WHERE  ca.nro_solicitud = " . quote($nro_solicitud) . "
						AND ca.nro_archivo = " . quote($nro_archivo) . ";";
			$datos = toba::db()->consultar_fila($sql_sel);
			return $datos;
		} else {
			return array();
		}
    }

    public static function get_max_nro_archivo($nro_solicitud) {
        if (isset($nro_solicitud)) {
			$sql_sel = "SELECT NVL(MAX(NRO_ARCHIVO),0) nro_archivo
						FROM  CO_ARCHIVOS_SOL ca
						WHERE ca.nro_solicitud = " . quote($nro_solicitud) . ";";
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

    public static function actualizar_dato_archivo($nro_solicitud, $nro_archivo, $dato) {
		if (isset($nro_solicitud) && isset($nro_archivo) && isset($dato)) {
			$sql = "UPDATE co_archivos_sol SET dato = EMPTY_BLOB() WHERE nro_solicitud = :nro_solicitud AND nro_archivo = :nro_archivo RETURNING dato INTO :p_dato";
			$parametros = array(
								array(	'nombre' => 'nro_solicitud',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 40,
										'valor' => $nro_solicitud),
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

	public static function get_nuevo_nro_costo($nro_renglon,$nro_solicitud) {
        $sql = "
            SELECT
                NVL(max(nro_costo),0)+1 nro_costo
            FROM
                co_items_solicitud_costos
            WHERE
                nro_renglon = $nro_renglon AND
                nro_solicitud = $nro_solicitud
        ";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['nro_costo'];
    }

    public static function get_nuevo_nro_costo_orden($nro_renglon, $nro_orden) {
        $sql = "
            SELECT
                NVL(max(nro_costo),0)+1 nro_costo
            FROM
                co_items_orden_costos
            WHERE
                nro_renglon = $nro_renglon AND
                nro_orden = $nro_orden
        ";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['nro_costo'];
    }
}

?>
