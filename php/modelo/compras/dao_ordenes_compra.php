<?php

class dao_ordenes_compra {

	static public function get_ordenes_compra ($filtro = array(), $orden = array())
	{
		$desde= null;
		$hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}

	   $where = self::armar_where($filtro);

	   $sql_sel = "SELECT coc.nro_orden,
	       coc.cod_sector,
	       coc.seq_sector,
	       coc.numero,
	       coc.anio,
	       coc.cod_ambito,
	       coc.seq_ambito,
	       coc.fecha,
	       coc.id_proveedor,
	       coc.tipo_compra,
	       coc.destino_compra,
	       coc.nro_compra,
	       coc.valor_total,
	       coc.estado,
	       coc.condicion_pago,
	       coc.plazo_entrega,
	       coc.observaciones,
	       coc.lugar_entrega,
	       coc.interna,
	       coc.presupuestario,
	       coc.finalizada,
	       coc.id_expediente,
	       coc.tipo_orden,
	       coc.total_impuesto,
	       coc.cod_lugar,
	       coc.cod_lugar_despacho,
	       coc.cond_pago,
	       coc.medio_pago,
	       coc.porc_anticipo,
	       coc.cotizacion,
	       coc.condicion_entrega,
	       coc.modo_pago,
	       coc.cod_moneda,
	       coc.base_plazo_entrega,
	       coc.sellado,
	       to_char(COC.fecha, 'DD/MM/YYYY') as fecha_format,
	       to_char(COC.fecha_imputacion, 'DD/MM/YYYY') as fecha_imputacion_format,
	       ke.nro_expediente as nro_expediente,
	       trim(to_char(coc.valor_total, '$999,999,999,990.00')) as valor_total_format,
	       cas.cod_ambito || '-' || cas.descripcion ambito,
		   css.cod_sector || '-' || css.descripcion sector,
		   cpr.razon_social proveedor_desc
		FROM CO_ORDENES COC
			JOIN co_proveedores cpr ON coc.id_proveedor = cpr.id_proveedor
		    JOIN co_ambitos_seq cas ON COC.cod_ambito = cas.cod_ambito AND COC.seq_ambito = cas.seq_ambito
			JOIN co_sectores_seq css ON COC.cod_sector = css.cod_sector AND COC.seq_sector = css.seq_sector
		    LEFT JOIN kr_expedientes ke ON COC.id_expediente = ke.id_expediente
		WHERE $where
		ORDER BY coc.NRO_ORDEN DESC
		";

		$sql = dao_varios::paginador($sql_sel, null, $desde, $hasta, null, $orden);
        $datos = toba::db()->consultar($sql);
		return $datos;
	}

	static public function armar_where ($filtro = array())
	{
		$desde= null;
		$hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}

		$where = " 1=1 ";
		if (isset($filtro['ids_comprobantes'])) {
            $where .= "AND COC.nro_orden IN (" . $filtro['ids_comprobantes'] . ") ";
            unset($filtro['ids_comprobantes']);
        }
        if (isset($filtro['observaciones'])) {
            $where .= "AND upper(COC.observaciones) like upper('%".$filtro['observaciones']."%')";
            unset($filtro['observaciones']);
        }
		if (isset($filtro['ambito_usuario']) && $filtro['ambito_usuario'] == '1') {
			$where .= " AND INSTR(Pkg_Usuarios.ambitos_usuario(".quote(strtoupper(toba::usuario()->get_id()))."), COC.cod_ambito) > 0";
			unset($filtro['ambito_usuario']);
		}
		if (isset($filtro['proveedor_desc'])) {
            $where .= "AND upper(cpr.razon_social) like upper('%".$filtro['proveedor_desc']."%')";
            unset($filtro['proveedor_desc']);
        }
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'coc', '1=1');
		return $where;
	}

	static public function get_cantidad ($filtro = array())
	{
		$where = self::armar_where($filtro);
		$sql = "
			SELECT count(*) cantidad
			FROM CO_ORDENES COC
				JOIN co_proveedores cpr ON coc.id_proveedor = cpr.id_proveedor
				JOIN co_ambitos_seq cas ON COC.cod_ambito = cas.cod_ambito AND COC.seq_ambito = cas.seq_ambito
				JOIN co_sectores_seq css ON COC.cod_sector = css.cod_sector AND COC.seq_sector = css.seq_sector
				LEFT JOIN kr_expedientes ke ON COC.id_expediente = ke.id_expediente
			where $where
		";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cantidad'];
	}

	public static function get_datos_extras_encabezado_orden_compra($nro_orden) {
        if (isset($nro_orden)) {
            $sql_sel = "SELECT co.texto_contrato texto_contrato, co.estado
					    FROM co_ordenes co
					    WHERE co.nro_orden = " . quote($nro_orden) . ";";
            $datos = toba::db()->consultar_fila($sql_sel);
            return $datos;
        } else {
            return array();
        }
    }

	static public function get_origen_orden ($tipo_orden){
		if (isset($tipo_orden) && !empty($tipo_orden)){
			$sql = "SELECT pkg_ordenes.obtener_origen_orden(".quote($tipo_orden).") origen from dual;";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['origen'];
		}else
			return '';
	}

	public static function get_cantidad_total_lug_ent_item($nro_orden, $nro_renglon) {
        if (isset($nro_orden) && isset($nro_renglon)) {
			$sql_sel = "SELECT NVL (SUM (NVL (ciole.cantidad, 0)), 0) cantidad
						  FROM co_items_orden_lug_ent ciole
						 WHERE ciole.nro_orden = " . quote($nro_orden)."
						 AND ciole.nro_renglon = " . quote($nro_renglon) . ";";
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

	static public function get_lov_compras_adj_x_nro ($nro_compra){
        $sql = "SELECT  CO.*,
				        CO.nro_compra
				        || ' - Exp: '
				        || KREX.nro_expediente
				        || ' - Sector: '
				        || CO.cod_sector
				        || ' - Tipo: '
				        || CO.tipo_compra
				        || ' - Nro: '
				        || CO.numero
				        || ' - '
				        || CO.anio
				        || ' - Ambito:'
				        || CO.cod_ambito
				        || ' - '
				        || CO.cod_ambito_ejecuta
				        || ' - '
				        || CO.destino_compra as lov_descripcion
			        FROM CO_COMPRAS CO
			             LEFT JOIN KR_EXPEDIENTES KREX ON KREX.ID_EXPEDIENTE = CO.ID_EXPEDIENTE
			        WHERE CO.nro_compra = ".$nro_compra;
        $datos = toba::db()->consultar_fila($sql);
        return $datos['lov_descripcion'];
	}

	static public function get_lov_compras_adj_x_nombre ($nombre, $filtro = array()){
		$where = ' 1=1 ';
        if (isset($nombre)) {
            $trans_id = ctr_construir_sentencias::construir_translate_ilike('coad.nro_compra', $nombre);
            $where .= " AND ($trans_id)";
        }
        $sql = "SELECT DISTINCT coad.*,
                coad.nro_compra
                || ' - Exp: '
                || coad.nro_expediente
                || ' - Sector: '
                || coad.cod_sector
                || ' - Tipo: '
                || coad.tipo_compra
                || ' - Nro: '
                || coad.numero
                || ' - '
                || coad.anio
                || ' - Ambito:'
                || coad.cod_ambito
                || ' - '
                || coad.cod_ambito_ejecuta
                || ' - '
                || coad.destino_compra as lov_descripcion
	            FROM v_co_compras_adj coad
				WHERE $where and (COAD.COD_SECTOR = ".quote($filtro['cod_sector'])." AND ".$filtro['anio']." <= ".$filtro['anio'].")";
        $datos = toba::db()->consultar($sql);
        return $datos;
	}

	static public function get_lov_compra_pro_adj ($nombre, $filtro = array()){
		$where = ' 1=1 ';
        if (isset($nombre)) {
            $trans_id = ctr_construir_sentencias::construir_translate_ilike('coprad.id_proveedor', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('coprad.razon_social', $nombre);
            $where .= " AND ($trans_id OR $trans_descripcion)";
        }

        if (isset($filtro['vigente'])){
        	$where .=" and coprov.estado = 'VIG' ";
        	unset($filtro['vigente']);
        }
        if (!isset($filtro['nro_compra']) || is_null($filtro['nro_compra'])){
        	$filtro['nro_compra'] = 'null';
        }
		if (!isset($filtro['nro_orden_ext']) || is_null($filtro['nro_orden_ext'])){
        	$filtro['nro_orden_ext'] = 'null';
        }

		$sql = "SELECT DISTINCT coprad.nro_compra nro_compra,
                coprad.id_proveedor id_proveedor,
                coprad.razon_social razon_social,
                coprad.id_proveedor ||' - '|| coprad.razon_social as lov_descripcion
	            FROM v_co_compra_proveedores_adj coprad, co_proveedores coprov
                WHERE $where and coprad.ID_PROVEEDOR = coprov.ID_PROVEEDOR
                 and (  ( ".$filtro['nro_compra']." IS NOT NULL
	                     AND ".$filtro['nro_orden_ext']." IS NULL
	                     AND coprad.nro_compra = ".$filtro['nro_compra']."
	                    )/*
	                AND NOT EXISTS (
	                       SELECT 1
	                         FROM co_ordenes
	                        WHERE nro_compra = coprad.nro_compra
	                          AND id_proveedor = coprad.id_proveedor
	                          AND INSTR
	                                 (pkg_general.valor_parametro
	                                                 ('ORDEN_ESTADO_FINAL_NOK'),
	                                  estado
	                                 ) = 0)*/
	             OR (    ".$filtro['nro_compra']." IS NULL
	                 AND ".$filtro['nro_orden_ext']." IS NOT NULL
	                 AND coprad.nro_compra IS NULL
	                 AND coprad.id_proveedor =
	                                  (SELECT id_proveedor
	                                     FROM co_ordenes
	                                    WHERE nro_orden = ".$filtro['nro_orden_ext'].")
	                )
	             OR (    ".$filtro['nro_compra']." IS NULL
	                 AND ".$filtro['nro_orden_ext']." IS NULL
	                 AND coprad.nro_compra IS NULL
	                ))";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

	static public function get_lov_orden_ext_x_id ($nro_orden){

		$sql = "SELECT   ord.*,
				          ord.nro_orden
				       || ' - Exp: '
				       || l_krex.nro_expediente
				       || ' - '
				       || ord.anio AS lov_descripcion
			    FROM co_ordenes ord,
			         co_sectores_seq l_sesq,
			         co_sectores l_sec,
			         kr_expedientes l_krex,
			         co_proveedores l_pro
			   WHERE ord.seq_sector = l_sesq.seq_sector
				     AND ord.cod_sector = l_sesq.cod_sector
				     AND l_sesq.cod_sector = l_sec.cod_sector
				     AND ord.id_expediente = l_krex.id_expediente(+)
				     AND ord.id_proveedor = l_pro.id_proveedor
				     AND ord.nro_orden = ".quote($nro_orden);

		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}

	static public function get_lov_orden_ext_x_nombre ($nombre, $filtro){
		$where = ' 1=1 ';
        if (isset($nombre)) {
            $trans_id = ctr_construir_sentencias::construir_translate_ilike('ord.nro_orden', $nombre);
            //$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('coprad.razon_social', $nombre);
            $where .= " AND ($trans_id)";
        }
		$sql = "SELECT ord.*,
				          ord.nro_orden
				       || ' - Exp: '
				       || l_krex.nro_expediente
				       || ' - '
				       || ord.anio AS lov_descripcion
			    FROM co_ordenes ord,
			         co_sectores_seq l_sesq,
			         co_sectores l_sec,
			         kr_expedientes l_krex,
			         co_proveedores l_pro
			   WHERE $where and ord.seq_sector = l_sesq.seq_sector
			     AND ord.cod_sector = l_sesq.cod_sector
			     AND l_sesq.cod_sector = l_sec.cod_sector
			     AND ord.id_expediente = l_krex.id_expediente(+)
			     AND ord.id_proveedor = l_pro.id_proveedor
			     AND (    pkg_ordenes.generada_en (".$filtro['cod_sector'].", ord.nro_orden) = 'S'
			          AND ord.anio <= ".$filtro['anio']."
			          AND INSTR (pkg_general.valor_parametro ('ORDEN_ESTADO_INICIAL'),
			                     ord.estado
			                    ) = 0
			          AND ord.estado <> 'ANUL'
			         )
			ORDER BY nro_orden DESC";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}


	public static function get_datos_tipo_orden ($tipo_orden){
		$sql = "SELECT *
				FROM cg_ref_codes
				WHERE rv_domain = 'CO_TIPO_ORDEN' AND rv_low_value = '$tipo_orden'";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['rv_valor1'];
	}



	public static function obtener_nro_compra ($nro_compra_ext){
		if (!is_null($nro_compra_ext)){
			$sql = "SELECT pkg_ordenes.obtener_nro_compra($nro_compra_ext) nro_compra FROM DUAL;";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['nro_compra'];
		}else return null;
	}

	//------------------------------------------------------
	//-------------------    UI_ITEMS    -------------------
	//------------------------------------------------------
	public static function get_ui_valor_total ($nro_orden){
		if (!is_null($nro_orden)){
			$sql = "select nvl(valor_total, 0) ui_valor_total
				    from co_ordenes
				    where nro_orden=".quote($nro_orden);
			$datos = toba::db()->consultar_fila($sql);
			return $datos['ui_valor_total'];
		}
		return 0;
	}

	public static function get_ui_total_impuesto ($nro_orden){
		if (!is_null($nro_orden)){
			$sql = "select nvl(total_impuesto, 0) ui_total_impuesto
				    from co_ordenes
				    where nro_orden=".quote($nro_orden);
			$datos = toba::db()->consultar_fila($sql);
			return $datos['ui_total_impuesto'];
		}
		return 0;
	}

	public static function get_ui_estado_descripcion ($estado){
		if (!is_null($estado)){
			$sql = "SELECT RV_MEANING AS UI_ESTADO_DESCRIPCION
					FROM CG_REF_CODES
					WHERE RV_DOMAIN = 'CO_ESTADO_ORDEN' AND RV_LOW_VALUE = '$estado'";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['ui_estado_descripcion'];
		}
		return '';
	}

	public static function get_lov_lugares_entrega_x_nombre ($nombre, $filtro = array()){
		$where = ' 1=1 ';
        if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('cod_lugar', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
            $where .= " AND ($trans_cod OR $trans_descripcion)";
        }
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'lue', '1=1');
		$sql = "SELECT LUEN.*, LUEN.COD_LUGAR ||' - '|| LUEN.DESCRIPCION AS LOV_DESCRIPCION
				FROM CO_LUGARES_ENTREGA LUEN
				WHERE $where
				ORDER BY descripcion asc";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

	public static function get_lov_lugares_entrega_x_codigo ($cod_lugar){
		if (!is_null($cod_lugar)){
			$sql = "SELECT LUEN.*, LUEN.COD_LUGAR ||' - '|| LUEN.DESCRIPCION AS lov_descripcion
					FROM CO_LUGARES_ENTREGA LUEN
					WHERE LUEN.COD_LUGAR = ".quote($cod_lugar)."
					 	  ORDER BY cod_lugar DESC";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}else return '';
	}

	public static function actualizar_contrato($nro_orden, $texto) {
		if (isset($nro_orden) && isset($texto)) {
			$sql = "BEGIN :resultado := PKG_ORDENES.ACTUALIZAR_TEXTO_CONTRATO(:nro_orden, $texto); END;";
			$parametros = array(
								array(	'nombre' => 'nro_orden',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 40,
										'valor' => $nro_orden),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al actualizar el contrato en la compra.', false);
			return $resultado;
		}
	}

	public static function validar_numero ($numero, $nro_orden, $cod_sector, $tipo_orden, $anio){
		try {
			if (isset($numero) && isset($cod_sector) && isset($tipo_orden) && isset($anio)) {
				//Obtengo la Secuencia
				$sql = "SELECT PKG_SECUENCIAS.id_secuencia_sector($cod_sector, 'ORD', '".$tipo_orden."') SECUENCIA FROM DUAL";
				$datos = toba::db()->consultar_fila($sql);
				if (is_null($datos['secuencia']))
					$secuencia = 'null';
				else
					$secuencia = $datos['secuencia'];

				//Obtengo si es secuencia anual
				$sql = "SELECT PKG_SECUENCIAS.es_secuencia_anual($cod_sector, 'ORD', '".$tipo_orden."') SECUENCIA_ANUAL FROM DUAL";
				$datos = toba::db()->consultar_fila($sql);
				$secuencia_anual = $datos['secuencia_anual'];

				$where = " 1=1 ";
				if (isset($nro_orden) && !empty($nro_orden)){
					$where = " nro_orden != $nro_orden ";
				}
				$sql = "SELECT COUNT(1) cantidad
					    FROM   co_ordenes
					    WHERE  tipo_orden = '".$tipo_orden."'
							   AND    numero = $numero AND PKG_SECUENCIAS.id_secuencia_sector(cod_sector,'ORD',tipo_orden) = $secuencia
							   AND ('".$secuencia_anual."' = 'N' OR anio = $anio)
							   and $where;";
				$datos = toba::db()->consultar_fila($sql);

				if ($datos['cantidad'] > 0){
					return false;
				}else{
					return true;
				}
			}else{
				return false;
			}
		}catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            return false;
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
            return false;
        }
	}


	/*
	 * LOV'S DETALLE ITEMS
	 */

	public static function get_lov_articulos_adj_x_nombre ($nombre, $filtro = array()){
		$where = ' 1=1 ';
        if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('coitad.cod_articulo', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('coitad.descripcion', $nombre);
            $where .= " AND ($trans_cod OR $trans_descripcion)";
        }
        if (is_null($filtro['nro_compra'])){
        	$filtro['nro_compra'] = 'null';
        }

        $where .= "	and ((".$filtro['nro_compra']." IS NULL AND coitad.nro_compra IS NULL) OR (coitad.nro_compra = ".$filtro['nro_compra']." AND coitad.id_proveedor = ".$filtro['id_proveedor']."))";

		$sql = "SELECT DISTINCT coitad.cod_articulo cod_articulo, coitad.cod_articulo || ' - ' || coitad.descripcion lov_descripcion
           FROM v_co_compra_items_adj coitad
          WHERE $where";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

	public static function get_lov_articulos_x_nombre ($nombre, $filtro = array()){
		$where = ' 1=1 ';
        if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('ART.cod_articulo', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('ART.descripcion', $nombre);
            $where .= " AND ($trans_cod OR $trans_descripcion)";
        }
       	$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'ART', '1=1');
		$sql = " SELECT ART.cod_articulo, ART.cod_articulo || ' - '|| ART.descripcion AS lov_descripcion
		           FROM CO_ARTICULOS ART
		          WHERE $where";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

	public static function get_ordenes_compra_x_nombre($nombre = '', $filtro = array()) {
        if (isset($nombre)) {
			$campos = array(
					'COC.nro_orden',
			);
			$where = ctr_construir_sentencias::construir_sentencia_busqueda($nombre, $campos, true);
		} else {
			$where = '1=1';
		}

		if (isset($filtro['mostrar_recepcion']) && $filtro['mostrar_recepcion'] == '1' && isset($filtro['cod_sector']) && isset($filtro['anio'])) {
			$where .= " AND Pkg_Ordenes.Generada_En(".quote($filtro['cod_sector']).", coc.NRO_ORDEN) = 'S'
						AND coc.ANIO <= ".quote($filtro['anio'])."
						AND coc.finalizada = 'N'
						AND INSTR(Pkg_General.valor_parametro('ORDEN_ESTADO_SIG_COMP'), coc.estado) > 0";
			unset($filtro['mostrar_recepcion']);
			unset($filtro['cod_sector']);
			unset($filtro['anio']);
		}

		if (isset($filtro['mostrar_ajuste_precios']) && $filtro['mostrar_ajuste_precios'] == '1' && isset($filtro['cod_sector']) && isset($filtro['fecha_ajuste'])) {
			$where .= " AND coc.cod_sector = ".quote($filtro['cod_sector'])."
						AND coc.finalizada = 'N'
						AND (pkg_general.valor_parametro ('INTEGRADO_AFI') = 'N'
								OR pkg_kr_ejercicios.retornar_ejercicio(to_date(substr(".quote($filtro['fecha_ajuste']).",1,10),'dd/mm/yyyy')) = pkg_kr_ejercicios.retornar_ejercicio(coc.fecha))";
			unset($filtro['mostrar_ajuste_precios']);
			unset($filtro['cod_sector']);
			unset($filtro['fecha_ajuste']);
		}

		if (isset($filtro['ids_comprobantes'])) {
            $where .= "AND COC.nro_orden IN (" . $filtro['ids_comprobantes'] . ") ";
            unset($filtro['ids_comprobantes']);
        }

        if (isset($filtro['no_estado_carga'])){
        		$where.=" and coc.estado <> Pkg_General.valor_parametro('RECEPCION_ESTADO_INICIAL')";
        	unset($filtro['no_estado_carga']);
        }
        if (isset($filtro['no_estado_anulada'])){
        		$where.=" and coc.estado <> Pkg_General.valor_parametro('RECEPCION_ESTADO_FINAL_NOK')";
        	unset($filtro['no_estado_anulada']);
        }

        if (isset($filtro['devolucion'])){
        	if ($filtro['devolucion'] == 'S'){
        		$where.=" and nro_orden in (SELECT rep.nro_orden
                           FROM co_recepciones rep
                          WHERE rep.estado = 'RECI')";
        	}
        	unset($filtro['devolucion']);
        }

		$sql = "SELECT nro_orden, coc.cod_sector, coc.seq_sector, numero, anio, coc.cod_ambito,
				       coc.seq_ambito, coc.fecha, coc.id_proveedor, tipo_compra, coc.destino_compra,
				       nro_compra, valor_total, coc.estado, condicion_pago, plazo_entrega,
				       coc.observaciones, lugar_entrega, interna, presupuestario, finalizada,
				       coc.id_expediente, fecha_imputacion, tipo_orden, total_impuesto, cod_lugar,
				       cod_lugar_despacho, cond_pago, medio_pago, porc_anticipo, cotizacion,
				       condicion_entrega, modo_pago, base_plazo_entrega, sellado,
				       nro_orden_ext, TO_CHAR (coc.fecha, 'DD/MM/YYYY') AS fecha_format,
				       TO_CHAR (coc.fecha_imputacion,
				                'DD/MM/YYYY') AS fecha_imputacion_format, ke.id_expediente,
				       ke.nro_expediente AS nro_expediente,
				       TRIM (TO_CHAR (coc.valor_total, '$999,999,999,990.00')
				            ) AS valor_total_format,
				       cas.cod_ambito || '-' || cas.descripcion ambito,
				       css.cod_sector || '-' || css.descripcion sector,
				          coc.nro_orden
				       || ' (Exp.: '
				       || ke.nro_expediente
				       || ', Sec.: '
				       || css.cod_sector
				       || '-'
				       || css.descripcion
				       || ', Nro.: '
				       || coc.numero
				       || ', año: '
				       || coc.anio
				       || ', Proveedor: '
				       || cp.id_proveedor
				       || '-'
				       || cp.razon_social
				       || ')' lov_descripcion
				  FROM co_ordenes coc JOIN co_ambitos_seq cas
				       ON coc.cod_ambito = cas.cod_ambito AND coc.seq_ambito = cas.seq_ambito
				       JOIN co_sectores_seq css
				       ON coc.cod_sector = css.cod_sector AND coc.seq_sector = css.seq_sector
				       LEFT JOIN kr_expedientes ke ON coc.id_expediente = ke.id_expediente
				       JOIN co_proveedores cp ON coc.id_proveedor = cp.id_proveedor
				  WHERE $where
				  	   ORDER BY NRO_ORDEN DESC;";
		$datos = toba::db()->consultar($sql);
		return $datos;
    }

	static public function get_ordenes_compra_x_id($nro_orden) {

        if (isset($nro_orden)) {
            $sql = "SELECT coc.nro_orden || ' (Exp.: ' ||ke.nro_expediente || ', Sec.: ' || css.cod_sector || '-' || css.descripcion || ', Nro.: ' || coc.numero || ', año: ' || coc.anio || ', Proveedor: ' || cp.id_proveedor || '-' || cp.razon_social || ')' lov_descripcion
				FROM CO_ORDENES COC
				     JOIN co_ambitos_seq cas ON COC.cod_ambito = cas.cod_ambito AND COC.seq_ambito = cas.seq_ambito
					 JOIN co_sectores_seq css ON COC.cod_sector = css.cod_sector AND COC.seq_sector = css.seq_sector
				     LEFT JOIN kr_expedientes ke ON COC.id_expediente = ke.id_expediente
					 JOIN co_proveedores cp ON COC.id_proveedor = cp.id_proveedor
                    WHERE coc.nro_orden = " . quote($nro_orden) . ";";

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

	static public function get_datos_orden_compra_x_id($nro_orden) {

        if (isset($nro_orden)) {
            $sql = "SELECT co.*, TO_CHAR(co.fecha, 'DD/MM/YYYY') as fecha_format,
					       CASE
					          WHEN co.interna = 'S'
					             THEN 'Si'
					          ELSE 'No'
					       END interna_format, cas.cod_ambito || '-' || cas.descripcion ambito,
					       css.cod_sector || '-' || css.descripcion sector
					FROM CO_ORDENES co
					     JOIN co_ambitos_seq cas ON CO.cod_ambito = cas.cod_ambito AND CO.seq_ambito = cas.seq_ambito
					     JOIN co_sectores_seq css ON CO.cod_sector = css.cod_sector AND CO.seq_sector = css.seq_sector
					     LEFT JOIN kr_expedientes ke ON CO.id_expediente = ke.id_expediente
					     JOIN co_proveedores cp ON CO.id_proveedor = cp.id_proveedor
					    WHERE co.nro_orden =" . quote($nro_orden) . ";";
            return toba::db()->consultar_fila($sql);
        } else {
            return array();
        }
    }



    static public function get_lov_entidades_con_saldo_x_nombre ($nombre, $filtro = array() ){
     	if (isset($nombre)) {
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('PREN.COD_ENTIDAD', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('PREN.DESCRIPCION', $nombre);
            $where = "($trans_nro OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }

		$where .= " and ((Pkg_Pr_Entidades.Usuario_Pertenece_Ua (" . quote(toba::usuario()->get_id()) . ", PREN.ID_ENTIDAD) = 'S')
			      	Or ((Pkg_Pr_Entidades.Usuario_Pertenece_Ua (" . quote(toba::usuario()->get_id()) . ", PREN.ID_ENTIDAD) = 'N')
			            And Not Exists (Select 1 From Pr_Entidades Where (Pkg_Pr_Entidades.Usuario_Pertenece_Ua (" . quote(toba::usuario()->get_id()) . ", Id_Entidad) = 'S'))
			          ))
			        AND (Pkg_Kr_Ejercicios.Pertenece_Entidad_A_Estruc (Pren.COD_UNIDAD_ADMINISTRACION ,".$filtro['id_ejercicio'].", Pren.Id_Entidad ) = 'S'
			             And Pkg_Pr_Entidades.Imputable (Pren.Id_Entidad) = 'S'
			             )";

        if (isset($filtro['cod_sector'])){
        	$where.= " And pkg_solicitudes.es_entidad_del_sector(".$filtro['cod_sector'].", pren.id_entidad) = 'S'";
        	unset($filtro['cod_sector']);
        }

		$sql = "SELECT PREN.*,  pkg_pr_entidades.MASCARA_APLICAR(PREN.COD_ENTIDAD)
					   ||' - '|| PREN.DESCRIPCION
				       ||' - Saldo: '|| Pkg_Pr_Totales.Saldo_Acumulado_Egreso( PREN.COD_UNIDAD_ADMINISTRACION , ".$filtro['id_ejercicio'].", PREN.ID_ENTIDAD , Null , ".$filtro['cod_partida'].", Null , Null , 'PRES' , Sysdate )
				       ||' - Saldo 2:'|| Pkg_Pr_Totales.Saldo_Acumulado_Egreso( PREN.COD_UNIDAD_ADMINISTRACION , ".$filtro['id_ejercicio'].", PREN.ID_ENTIDAD , Null , Pkg_Pr_Partidas.Codigo_A_Nivel( ".$filtro['cod_partida']." , Pkg_Pr_Partidas.Control_Nivel(".$filtro['cod_partida'].")) , Null , Null , 'PRES' , Sysdate ) lov_descripcion_saldo
				FROM PR_ENTIDADES PREN
				WHERE $where
				ORDER BY lov_descripcion_saldo ASC;";
		$datos = toba::db()->consultar($sql);
		return $datos;

    }

    public static function get_cantidad_item_orden($nro_orden, $nro_renglon, $cod_articulo)
	{
        if (isset($nro_orden) && isset($nro_renglon) && isset($cod_articulo)) {
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
			$sql = "SELECT	cio.cantidad
					FROM co_items_orden cio
					WHERE cio.nro_orden = " . quote($nro_orden) . "
					AND cio.nro_renglon = " . quote($nro_renglon) ."
					AND cio.cod_articulo = " . quote($cod_articulo) . "	";
			$datos = toba::db()->consultar_fila($sql);
			if (isset($datos['cantidad'])) {
				return $datos['cantidad'];
			} else {
				return 0;
			}
		} else {
			return 0;
		}
    }



    public static function get_total_impuesto_x_ariculo ($nro_orden, $nro_renglon){
    	$sql = "SELECT NVL(SUM(total_impuesto),0) total
				FROM co_items_orden_ito
				WHERE nro_orden = $nro_orden AND nro_renglon = $nro_renglon;";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['total'];
    }

 	public static function get_total_lugares ($nro_orden, $nro_renglon){
    	$sql =" select nvl(sum(cantidad),0) total
				from co_items_orden_lug_ent
				where nro_orden = $nro_orden
					  and nro_renglon = $nro_renglon;";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['total'];
    }

    public static function importar_items_compra ($nro_orden, $nro_compra, $id_proveedor, $con_transaccion = true){
    	if (!is_null($nro_orden) && !is_null($nro_compra) && !is_null($id_proveedor)) {
			$sql = "BEGIN :resultado := Pkg_Ordenes.importar_items_compra(:nro_orden, :nro_compra, :id_proveedor); END;";
			$parametros = array(
								array(	'nombre' => 'nro_orden',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_orden),
								array(	'nombre' => 'nro_compra',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_compra),
								array(	'nombre' => 'id_proveedor',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $id_proveedor),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);
			if ($con_transaccion)
				toba::db()->abrir_transaccion();

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', '', false);
			if ($con_transaccion){
				if ($resultado[3]['valor'] != 'OK'){
					toba::db()->abortar_transaccion();
					toba::notificacion()->error($resultado[3]['valor']);
				}else{
					toba::db()->cerrar_transaccion();
				}
			}
			//print_r($resultado);
			return $resultado[3]['valor'];
		}
    }
 	public static function usuario_esta_en_bandeja ($cod_sector, $cod_ambito, $tipo_compra, $presupuestario, $interna, $estado){
    	if (!is_null($cod_sector) && !is_null($cod_ambito) && !is_null($tipo_compra) && !is_null($presupuestario) && !is_null($interna) && !is_null($estado)) {
			$usuario = toba::usuario()->get_id();
    		$sql = "select Pkg_usuarios.esta_en_bandeja('".$usuario."', $cod_sector, $cod_ambito, 'ORD', '".$tipo_compra."', '".$presupuestario."', '".$interna."', '".$estado."') as valor from dual";
    		$datos = toba::db()->consultar_fila($sql);
    		return $datos['valor'];
		}else
			return '';
    }

    public static function get_secuencia_orden_compra (){
    	$sql = "SELECT NVL(MAX(nro_orden),1) secuencia
    			FROM CO_ORDENES";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['secuencia'];
    }

    public static function verificar_secuencia_orden_compra ($numero, $anio, $cod_sector, $tipo_orden){
    	$secuencia = dao_secuencias::get_id_secuencia_sector($cod_sector, 'ORD', $tipo_orden);
		$anual = dao_secuencias::get_es_secuencia_anual($cod_sector, 'ORD', $tipo_orden);
    	$sql = "select count(1) cant
			    from   co_ordenes
			    where  tipo_orden = '".$tipo_orden."'
			    and    numero = $numero
			    and    PKG_SECUENCIAS.id_secuencia_sector(cod_sector,'ORD','".$tipo_orden."') = '".$secuencia."'
			    AND    ('".$anual."' = 'N' OR anio = ".$anio.");";
    	$datos = toba::db()->consultar_fila($sql);
    	if ($datos['cant'] > 0)
    		return false;
    	else
    		return true;
    }

    public static function get_compromisos ($nro_orden){
    	$sql = "SELECT * FROM CO_ORDENES_COMPROMISOS WHERE NRO_ORDEN = $nro_orden ORDER BY SECUENCIA ASC";
    	$datos = toba::db()->consultar($sql);
    	return $datos;
    }

    public static function get_datos_compromiso ($id_compromiso, $con_transaccion = false){
    	if (!is_null($id_compromiso)) {
			$sql = "BEGIN :resultado := pkg_ordenes.datos_compromiso(:id_compromiso, :fecha, :aprobado, :anulado, :fecha_anulado, :reimputado); END;";
			$parametros = array(array(	'nombre' => 'id_compromiso',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $id_compromiso),
								array(	'nombre' => 'fecha',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 32,
										'valor' => ''),
								array(	'nombre' => 'aprobado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 1,
										'valor' => ''),
								array(	'nombre' => 'anulado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 1,
										'valor' => ''),
								array(	'nombre' => 'fecha_anulado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 32,
										'valor' => ''),
								array(	'nombre' => 'reimputado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 1,
										'valor' => ''),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			if ($con_transaccion)
				toba::db()->abrir_transaccion();

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', '', false);
			if ($con_transaccion){
				if ($resultado[6]['valor'] != 'OK'){
					toba::db()->abortar_transaccion();
					toba::notificacion()->error($resultado[3]['valor']);
				}else{
					toba::db()->cerrar_transaccion();
				}
			}
			$datos = array();
			if ($resultado[6]['valor'] == 'OK' ){
				$datos['fecha'] = $resultado[1]['valor'];
				$datos['aprobado'] = $resultado[2]['valor'];
				$datos['anulado'] = $resultado[3]['valor'];
				$datos['fecha_anulado'] = $resultado[4]['valor'];
				$datos['reimputado'] = $resultado[5]['valor'];
			}else{
				toba::notificacion()->info($resultado[6]['valor']);
			}
			return $datos;
		}
    }

    public static function hay_compromisos_aprobados_no_anulados ($nro_orden){
    	$sql = "Select Count(1) cant
			    From   Co_ordenes_compromisos C1, Co_ordenes C2, Ad_compromisos A
			    Where  C1.Nro_orden = C2.Nro_orden
			    And    C1.Id_compromiso = A.Id_compromiso
			    And    A.Aprobado = 'S'
			    And    A.Anulado  = 'N'
			    And    C1.Nro_orden = $nro_orden;";
    	$datos = toba::db()->consultar_fila($sql);
    	if ($datos['cant'] > 0 )
    		return true;
    	else
    		return false;
    }

    public static function get_fecha_imputacion ($nro_orden){
    	$sql = "SELECT FECHA_IMPUTACION FROM CO_ORDENES WHERE NRO_ORDEN = $nro_orden";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['fecha_imputacion'];
    }

    public static function generar_compromiso_afi ($fecha, $nro_orden, $con_transaccion = true){
    	if (isset($fecha) && isset($nro_orden)){
    		if (self::hay_compromisos_aprobados_no_anulados($nro_orden)){
    			throw new toba_error("Se Encontraron Compromisos Aprobados y No Anulados.");
    		}
	    	if ($fecha <> self::get_fecha_imputacion($nro_orden)){
	    		$sql = "UPDATE CO_ordenes SET fecha_imputacion = to_date('".$fecha."','YYYY/MM/DD')
	        			WHERE nro_orden = $nro_orden;";
	    		$datos = toba::db()->ejecutar($sql);
	    	}
	    	$sql = "BEGIN :resultado := pkg_ordenes.generar_compromiso_afi(:nro_orden); END;";
			$parametros = array(array('nombre' => 'nro_orden',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $nro_orden),
							array(	'nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''));
			if ($con_transaccion)
				toba::db()->abrir_transaccion();

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', '', false);
			if ($con_transaccion){
				if ($resultado[1]['valor'] != 'OK'){
					toba::db()->abortar_transaccion();
					toba::notificacion()->error($resultado[1]['valor']);
				}else{
					toba::db()->cerrar_transaccion();
				}
			}
			return $resultado[1]['valor'];
    	}
    }

    public static function actualizar_fecha_imputacion ($nro_orden, $fecha){
    	$sql = " UPDATE CO_ORDENES
				 SET fecha_imputacion = to_date('".$fecha."','DD/MM/YYYY')
				 WHERE nro_orden = $nro_orden;";
    	echo $sql;
    	toba::db()->ejecutar($sql);
    }

    public static function asociar_compromiso_a_orden_compra ($nro_orden, $id_compromiso, $nro_compromiso, $anio, $con_transaccion = true){
    	$sql = "BEGIN :resultado := pkg_ordenes.asociar_compromiso(:nro_orden, :id_compromiso, :nro_compromiso, :anio); END;";
		$parametros = array(array(	'nombre' => 'nro_orden',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $nro_orden),
							array(	'nombre' => 'id_compromiso',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $id_compromiso),
							array(	'nombre' => 'nro_compromiso',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $nro_compromiso),
							array(	'nombre' => 'anio',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $anio),
							array(	'nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''),
							);
		if ($con_transaccion)
			toba::db()->abrir_transaccion();

		$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', '', false);
		if ($con_transaccion){
			if ($resultado[4]['valor'] != 'OK'){
				toba::db()->abortar_transaccion();
				toba::notificacion()->error($resultado[4]['valor']);
			}else{
				toba::db()->cerrar_transaccion();
			}
		}
		return $resultado[4]['valor'];
    }

    static public function get_lov_compromisos_x_nombre ($nombre, $filtro = array()){
    	$where = ' 1=1 ';
        if (isset($nombre)) {
            $trans_id = ctr_construir_sentencias::construir_translate_ilike('c.id_compromiso', $nombre);
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('c.nro_compromiso', $nombre);
            $trans_importe = ctr_construir_sentencias::construir_translate_ilike('c.importe', $nombre);
            $where .= " AND ($trans_id OR $trans_nro OR $trans_importe)";
        }

        $where .= " and (c.id_compromiso NOT IN (SELECT id_compromiso
                                         FROM co_ordenes_compromisos
                                        WHERE id_compromiso IS NOT NULL)
        AND c.anulado = 'N'
        AND c.aprobado = 'S'
        AND TO_CHAR(c.fecha_comprobante, 'YYYY') = '".$filtro['anio']."')";

		$sql = "SELECT c.*, c.nro_compromiso || ' (ID '||c.id_compromiso|| ') - ' ||to_char(c.fecha_comprobante, 'dd/mm/yyyy')|| ' (' || trim(to_char(c.importe, '$999,999,999,990.00')) || ')' lov_descripcion
        	    FROM ad_compromisos c
          		WHERE $where";

		$datos = toba::db()->consultar($sql);
		return $datos;

    }

    static public function anular_compromiso_afi ($nro_orden){
    	if (isset($nro_orden)){
        	$sql = "BEGIN :resultado := Pkg_Ordenes.anular_compromiso_afi(:nro_orden); END;";
			$parametros = array(array('nombre' => 'nro_orden',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $nro_orden),
							array(	'nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''));
			$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros, '', '', false);
    		if (isset($resultado[1]['valor']) && !empty($resultado[1]['valor'])) {
				return $resultado[1]['valor'];
			} else {
				return 'NOK';
			}
		} else {
			return 'NOK';
		}
    }

	static public function desasociar_compromiso ($nro_orden, $secuencia){
    	if (isset($nro_orden) && isset($secuencia)){
        	$sql = "BEGIN :resultado := Pkg_Ordenes.desasociar_compromiso(:nro_orden, :secuencia); END;";
			$parametros = array(array('nombre' => 'nro_orden',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $nro_orden),
							 array( 'nombre' => 'secuencia',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $secuencia),
							 array( 'nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''));
			$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros, '', '', false);
    		if (isset($resultado[2]['valor']) && !empty($resultado[2]['valor'])) {
				return $resultado[2]['valor'];
			} else {
				return 'NOK';
			}
		} else {
			return 'NOK';
		}
    }

	static public function cambiar_estado($nro_orden, $estado, $observaciones) {
		if (isset($nro_orden) && isset($estado)) {
			if (!isset($observaciones)) {
				$observaciones = '';
			}
			$sql = "BEGIN :resultado := PKG_ORDENES.cambiar_estado(:nro_orden, :estado, :observaciones); END;";

			$parametros = array(array(	'nombre' => 'nro_orden',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $nro_orden),
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

			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al cambiar el estado de la orden.', false);
			if (isset($resultado[3]['valor']) && !empty($resultado[3]['valor'])) {
				return $resultado[3]['valor'];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}
	public static function get_seguimiento_estados_orden($nro_orden) {
        if (isset($nro_orden)) {
			$sql_sel = "SELECT   ceo.*, TO_CHAR (ceo.fecha, 'DD/MM/YYYY') AS fecha_format,
					         css.cod_sector || '-' || css.descripcion sector,
					         crc.rv_meaning estado_format
					    FROM co_estados_orden ceo JOIN co_sectores_seq css
					         ON ceo.cod_sector = css.cod_sector
					            AND ceo.seq_sector = css.seq_sector
					         JOIN cg_ref_codes crc
					         ON crc.rv_domain = 'CO_ESTADO_ORDEN'
					            AND crc.rv_low_value = ceo.estado
					   WHERE ceo.nro_orden = " . quote($nro_orden) . "
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

    public static function get_partida_x_articulo ($cod_articulo){
    	$sql = "SELECT COD_PARTIDA
				  FROM CO_ARTICULOS
				 WHERE COD_ARTICULO = ".quote($cod_articulo);
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['cod_partida'];
    }

 	public static function get_datos_items_adjudicados ($cod_articulo, $id_proveedor, $nro_compra){
 		if (isset($cod_articulo) && isset($id_proveedor) && isset($nro_compra)){
	    	$sql = "SELECT DISTINCT coitad.*
					FROM v_co_compra_items_adj coitad
					WHERE coitad.cod_articulo = ".quote($cod_articulo)."
					      and coitad.id_proveedor = ".quote($id_proveedor)."
					      and coitad.NRO_COMPRA = ".quote($nro_compra)."";
	    	$datos = toba::db()->consultar_fila($sql);
	    	return $datos;
    	}else{
    		return null;
    	}
    }

    public static function get_tipo_y_destino_compra ($nro_orden){
    	if ( ! is_null($nro_orden)){
	    	$sql = "SELECT tipo_orden, destino_compra
				    FROM CO_ORDENES
				    WHERE NRO_ORDEN = $nro_orden";
	    	return toba::db()->consultar_fila($sql);
    	}else return array();
    }

    public static function generar_recepcion ($nro_orden, $con_transaccion = true){
    	$sql = "BEGIN :resultado := pkg_ordenes.generar_recepcion(:nro_orden, :id_rc);END;";
		$parametros=array(array('nombre' => 'nro_orden',
								'tipo_dato' => PDO::PARAM_INT,
								'longitud' => 12,
								'valor' => $nro_orden),
						  array('nombre' => 'id_rc',
								'tipo_dato' => PDO::PARAM_INT,
								'longitud' => 12,
								'valor' => ''),
						  array('nombre' => 'resultado',
								'tipo_dato' => PDO::PARAM_STR,
								'longitud' => 4000,
								'valor' => ''));

		if ($con_transaccion)
			toba::db()->abrir_transaccion();
		$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

		if ($con_transaccion){
			if ($resultado[2]['valor'] != 'OK'){
				toba::notificacion()->error($resultado[2]['valor']);
				toba::db()->abortar_transaccion();
				return;
			}else{
				toba::db()->cerrar_transaccion();
				$id = $resultado[1]['valor'];
				toba::notificacion()->info("Recepción de compra id $id generada con éxito!");
				return $id;
			}
		}
		return $resultado[1]['valor'];
    }

    public static function get_lov_ordenes_compras_x_nro($nro_orden){
    	$sql = "select ord.NRO_ORDEN lov_descripcion
				  from co_ordenes ord
				 where ord.nro_orden = ".quote($nro_orden);
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['lov_descripcion'];
    }

    public static function get_lov_ordenes_compras_x_nombre ($nombre, $filtro = array()){
    	$where = ' 1=1 ';
        if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('ord.nro_orden', $nombre);
            $where .= " AND ($trans_cod)";
        }

        if (isset($filtro['nro_orden_desde']) && !empty($filtro['nro_orden_desde'])){
        	$where .=" and ord.nro_orden >= ".$filtro['nro_orden_desde'];
        	unset($filtro['nro_orden_desde']);
        }

       	$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'ord', '1=1');

    	$sql = "select ord.*, ord.NRO_ORDEN lov_descripcion
				  from co_ordenes ord
				 where $where
				 order by ord.nro_orden asc ";

    	return toba::db()->consultar($sql);
    }

 	public static function get_proximo_nro_renglon($nro_orden){
    	$sql = "SELECT NVL (MAX (nro_renglon), 0)+1 numero
				  FROM co_items_orden
				 WHERE nro_orden = ".quote($nro_orden);
		$datos = toba::db()->consultar_fila($sql);
    	return $datos['numero'];
    }

	public static function get_item_orden ($nro_orden, $nro_renglon)
	{
		$sql = "SELECT it.*, art.cod_articulo || ' - ' || art.descripcion articulo,
				       decode(it.TIENE_SUBITEMS,'S','Si','No' ) tsi,
				       pa.cod_partida || ' - ' || pa.descripcion partida,
				       (SELECT rv_meaning
				          FROM cg_ref_codes
				         WHERE rv_domain = 'CO_UNIDAD_MEDIDA'
				           AND rv_low_value = it.unidad) unidad_format,
				       (SELECT rv_meaning
				          FROM cg_ref_codes
				         WHERE rv_domain = 'CO_EST_ITEM_ORDEN'
				           AND rv_low_value = it.estado) estado_format
				  FROM co_items_orden it LEFT JOIN pr_partidas pa
				       ON it.cod_partida = pa.cod_partida
				       , co_articulos art
				 WHERE it.cod_articulo = art.cod_articulo
				   AND it.nro_orden = ".quote($nro_orden)."
				   AND it.nro_renglon = ".$nro_renglon;
		return toba::db()->consultar_fila($sql);
	}

    public static function generar_imputacion ($nro_orden, $nro_renglon){
        $sql = "BEGIN :resultado := pkg_ordenes.generar_imputacion(:nro_orden, :nro_renglon); END;";

            $parametros = array(array(    'nombre' => 'nro_orden',
                                        'tipo_dato' => PDO::PARAM_INT,
                                        'longitud' => 12,
                                        'valor' => $nro_orden),
                                array(    'nombre' => 'nro_renglon',
                                        'tipo_dato' => PDO::PARAM_INT,
                                        'longitud' => 4,
                                        'valor' => $nro_renglon),
                                array(    'nombre' => 'resultado',
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

    public static function get_cod_moneda_x_nro($nro_orden){
		if (!is_null($nro_orden)){
			$sql = "SELECT cod_moneda
				from CO_ORDENES 
				where nro_orden = ".quote($nro_orden);
			$datos = toba::db()->consultar_fila($sql);
			return $datos['cod_moneda'];
		}
		return 0;
	}

 	public static function get_moneda_de_proveedor($id_proveedor){
		if (!is_null($id_proveedor)){
			$sql = "SELECT cod_moneda
				from CO_PROVEEDORES_PRESENTADOS 
				where id_proveedor = ".quote($id_proveedor);
			$datos = toba::db()->consultar_fila($sql);
			return $datos['cod_moneda'];
		}
		return "";
	}
}
?>
