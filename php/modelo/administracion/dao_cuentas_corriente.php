<?php
/**
 * Description of dao_cuentas_corriente
 *
 * @author ddiluca
 * @author lgraziani
 */
class dao_cuentas_corriente
{
	static public function get_cuentas_corrientes($filtro = array()) {
		$desde= null;
		$hasta= null;
		if(isset($filtro['desde'])){
			$desde= $filtro['desde'];
			$hasta= $filtro['hasta'];

			unset($filtro['desde']);
			unset($filtro['hasta']);
		}
		$sql1 = "SELECT PKG_KR_USUARIOS.in_ua_tiene_acceso(upper('".toba::usuario()->get_id()."')) unidades_ad FROM DUAL;";
		$res = toba::db()->consultar_fila($sql1);
		$where = " (CC.COD_UNIDAD_ADMINISTRACION IN ".$res['unidades_ad'].") ";
		if (isset($filtro))
			$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'cc', '1=1', array('descripcion'));

		$sql = "SELECT cc.*,
					   KRUA.DESCRIPCION UNIDAD_ADMINISTRACION,
					   ADRE.DESCRIPCION RECAUDADOR,
					   ADPR.CUIT CUIT_PROVEEDOR,
					   ADPR.RAZON_SOCIAL RAZON_SOCIAL_PROVEEDOR,
					   KRORFI.DESCRIPCION ORGANISMO_FINANCIERO,
					   ADCACH.DESCRIPCION CAJA_CHICA,
					   KRAUEX.DESCRIPCION AUXILIAR,
					   CGRC.RV_MEANING TIPO,
					   case when cc.activo = 'S' then
						  'Si'
					   else
						  'No'
					   end activo_format
				FROM KR_CUENTAS_CORRIENTE cc
					 LEFT JOIN KR_UNIDADES_ADMINISTRACION KRUA ON KRUA.COD_UNIDAD_ADMINISTRACION = CC.COD_UNIDAD_ADMINISTRACION
					 LEFT JOIN AD_RECAUDADORES ADRE ON CC.ID_RECAUDADOR = ADRE.ID_RECAUDADOR
					 LEFT JOIN AD_PROVEEDORES ADPR ON CC.ID_PROVEEDOR = ADPR.ID_PROVEEDOR
					 LEFT JOIN KR_ORGANISMOS_FINANCIEROS KRORFI ON CC.COD_ORGANISMO_FINANCIERO = KRORFI.COD_ORGANISMO_FINANCIERO
					 LEFT JOIN AD_CAJAS_CHICAS ADCACH ON CC.ID_CAJA_CHICA = ADCACH.ID_CAJA_CHICA
					 LEFT JOIN KR_AUXILIARES_EXT KRAUEX ON CC.COD_AUXILIAR = KRAUEX.COD_AUXILIAR
					 LEFT JOIN CG_REF_CODES CGRC ON CGRC.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE'
								AND CGRC.RV_LOW_VALUE = CC.TIPO_CUENTA_CORRIENTE
				WHERE  $where
				ORDER BY ID_CUENTA_CORRIENTE DESC;";
 		$sql= dao_varios::paginador($sql, null, $desde, $hasta);
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	static public function get_cuentas_corrientes_2 ($filtro = array()){

		$where = ctr_construir_sentencias::get_where_filtro($filtro, 'cc', '1=1');

		$sql = "SELECT cc.*,
					   KRUA.DESCRIPCION UNIDAD_ADMINISTRACION,
					   ADRE.DESCRIPCION RECAUDADOR,
					   ADPR.CUIT CUIT_PROVEEDOR,
					   ADPR.RAZON_SOCIAL RAZON_SOCIAL_PROVEEDOR,
					   KRORFI.DESCRIPCION ORGANISMO_FINANCIERO,
					   ADCACH.DESCRIPCION CAJA_CHICA,
					   KRAUEX.DESCRIPCION AUXILIAR,
					   CGRC.RV_MEANING TIPO,
					   case when cc.activo = 'S' then
						  'Si'
					   else
						  'No'
					   end activo_format
				FROM KR_CUENTAS_CORRIENTE cc
					 LEFT JOIN KR_UNIDADES_ADMINISTRACION KRUA ON KRUA.COD_UNIDAD_ADMINISTRACION = CC.COD_UNIDAD_ADMINISTRACION
					 LEFT JOIN AD_RECAUDADORES ADRE ON CC.ID_RECAUDADOR = ADRE.ID_RECAUDADOR
					 LEFT JOIN AD_PROVEEDORES ADPR ON CC.ID_PROVEEDOR = ADPR.ID_PROVEEDOR
					 LEFT JOIN KR_ORGANISMOS_FINANCIEROS KRORFI ON CC.COD_ORGANISMO_FINANCIERO = KRORFI.COD_ORGANISMO_FINANCIERO
					 LEFT JOIN AD_CAJAS_CHICAS ADCACH ON CC.ID_CAJA_CHICA = ADCACH.ID_CAJA_CHICA
					 LEFT JOIN KR_AUXILIARES_EXT KRAUEX ON CC.COD_AUXILIAR = KRAUEX.COD_AUXILIAR
					 LEFT JOIN CG_REF_CODES CGRC ON CGRC.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE'
								AND CGRC.RV_LOW_VALUE = CC.TIPO_CUENTA_CORRIENTE
				WHERE $where
				ORDER BY ID_CUENTA_CORRIENTE DESC;";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	static public function get_cuentas_corrientes_x_id($id_cuenta) {
		if (isset($id_cuenta)) {
			$sql = "SELECT cc.*, cc.id_cuenta_corriente|| ' - ' ||cc.descripcion as lov_descripcion
					FROM KR_CUENTAS_CORRIENTE cc
					WHERE id_cuenta_corriente = " . quote($id_cuenta) . ";";
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

	static public function get_cuentas_corrientes_x_recaudador ($id_recaudador){
   		if ($id_recaudador){
   			$sql = "SELECT cc.*,
					   krua.COD_UNIDAD_ADMINISTRACION ||' - '|| krua.descripcion unidad_administracion,
					   ADRE.DESCRIPCION RECAUDADOR,
					   ADPR.CUIT CUIT_PROVEEDOR,
					   ADPR.RAZON_SOCIAL RAZON_SOCIAL_PROVEEDOR,
					   KRORFI.DESCRIPCION ORGANISMO_FINANCIERO,
					   ADCACH.DESCRIPCION CAJA_CHICA,
					   KRAUEX.DESCRIPCION AUXILIAR,
					   CGRC.RV_MEANING TIPO_CUENTA_FORMAT,
					   case when cc.activo = 'S' then
						  'Si'
					   else
						  'No'
					   end activo_format
				FROM KR_CUENTAS_CORRIENTE cc
					 LEFT JOIN KR_UNIDADES_ADMINISTRACION KRUA ON KRUA.COD_UNIDAD_ADMINISTRACION = CC.COD_UNIDAD_ADMINISTRACION
					 LEFT JOIN AD_RECAUDADORES ADRE ON CC.ID_RECAUDADOR = ADRE.ID_RECAUDADOR
					 LEFT JOIN AD_PROVEEDORES ADPR ON CC.ID_PROVEEDOR = ADPR.ID_PROVEEDOR
					 LEFT JOIN KR_ORGANISMOS_FINANCIEROS KRORFI ON CC.COD_ORGANISMO_FINANCIERO = KRORFI.COD_ORGANISMO_FINANCIERO
					 LEFT JOIN AD_CAJAS_CHICAS ADCACH ON CC.ID_CAJA_CHICA = ADCACH.ID_CAJA_CHICA
					 LEFT JOIN KR_AUXILIARES_EXT KRAUEX ON CC.COD_AUXILIAR = KRAUEX.COD_AUXILIAR
					 LEFT JOIN CG_REF_CODES CGRC ON CGRC.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE'
								AND CGRC.RV_LOW_VALUE = CC.TIPO_CUENTA_CORRIENTE
				WHERE cc.id_recaudador = ".quote($id_recaudador)."
				ORDER BY CC.ID_RECAUDADOR ASC ;";
   			return toba::db()->consultar($sql);
   		}else{
   			return null;
   		}
	}

	static public function get_datos_cuentas_corrientes_x_id($id_cuenta) {
		if (isset($id_cuenta)) {
			$sql = "SELECT	cc.*,
							cc.nro_cuenta_corriente|| ' - ' ||cc.descripcion as lov_descripcion,
							CASE
								WHEN cc.tipo_cuenta_corriente = 'J' THEN acc.cod_auxiliar
								ELSE cc.cod_auxiliar
							END cod_auxiliar
					FROM KR_CUENTAS_CORRIENTE cc
					LEFT JOIN AD_CAJAS_CHICAS acc ON (cc.id_caja_chica = acc.id_caja_chica)
					WHERE id_cuenta_corriente = " . quote($id_cuenta) . ";";
			$datos = toba::db()->consultar_fila($sql);
			return $datos;
		} else {
			return null;
		}
	}
	static public function get_nro_cuenta_x_id($id_cuenta) {
		if (isset($id_cuenta)) {
			$sql = "SELECT nro_cuenta_corriente
					FROM KR_CUENTAS_CORRIENTE
					WHERE id_cuenta_corriente = " . quote($id_cuenta) . ";";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['nro_cuenta_corriente'];
		} else {
			return null;
		}
	}
	static public function get_lov_cuentas_corrientes_x_id ($id_cuenta){
		$sql =" SELECT  cc.nro_cuenta_corriente|| ' - ' ||cc.descripcion || ' (' || cg.rv_meaning ||')' as lov_descripcion
				FROM KR_CUENTAS_CORRIENTE cc, CG_REF_CODES CG
				WHERE CG.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE'
				and cg.rv_low_value = cc.tipo_cuenta_corriente";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];

	}
	static public function get_lov_cuentas_corrientes_x_nro($nro_cc)
	{
		$sql ="
			SELECT
				cc.nro_cuenta_corriente|| ' - ' ||cc.descripcion || ' (' || cg.rv_meaning ||')' as lov_descripcion
			FROM
				KR_CUENTAS_CORRIENTE cc,
				CG_REF_CODES CG
			WHERE
				CG.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE'
				AND cc.nro_cuenta_corriente = $nro_cc";

		return toba::db()->consultar_fila($sql)['lov_descripcion'];

	}
	static public function get_lov_cuentas_corrientes_x_nombre($nombre, $filtro = array())
	{
		if (isset($nombre)) {
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('cc.nro_cuenta_corriente', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('cc.descripcion', $nombre);
			$where = "($trans_nro OR $trans_descripcion)";
		} else {
			$where = '1=1';
		}
		$join = '';
		if (isset($filtro['cod_tipo_compromiso'])) {
			$join.= ", AD_TIPOS_COMPROMISO tp";
			$where.= " AND tp.tipo_cuenta_corriente= cc.tipo_cuenta_corriente
					   AND tp.cod_tipo_compromiso= '" . $filtro['cod_tipo_compromiso'] . "'";
			unset($filtro['cod_tipo_compromiso']);
		}

		if (isset($filtro['cod_tipo_recibo'])) {
			$join.= ", AD_TIPOS_RECIBO_COBRO trc";
			$where.= " AND trc.tipo_cuenta_corriente= cc.tipo_cuenta_corriente
					   AND trc.cod_tipo_recibo= '" . $filtro['cod_tipo_recibo'] . "'";

			unset($filtro['cod_tipo_recibo']);
		}

		if (isset($filtro['cod_tipo_recibo_pago'])) {
			$join.= ", AD_TIPOS_RECIBO_PAGO trp";
			$where.= " AND trp.tipo_cuenta_corriente = cc.tipo_cuenta_corriente
					   AND trp.cod_tipo_recibo= '" . $filtro['cod_tipo_recibo_pago'] . "'";

			unset($filtro['cod_tipo_recibo_pago']);
		}

		if (isset($filtro['cod_tipo_comprobante_recurso'])) {
			$join.= ", AD_TIPOS_COMPROBANTE_RECURSO atcr";
			$where.= " AND atcr.tipo_cuenta_corriente = cc.tipo_cuenta_corriente
					   AND atcr.cod_tipo_comprobante = '" . $filtro['cod_tipo_comprobante_recurso'] . "'";

			unset($filtro['cod_tipo_comprobante_recurso']);
		}

		if (isset($filtro['id_comprobante_gasto'])) {
			$join.= ", AD_TIPOS_COMPROBANTE_GASTO atcg, ad_comprobantes_gasto acg";
			$where.=" AND CG.RV_LOW_VALUE = CC.TIPO_CUENTA_CORRIENTE
					  AND acg.cod_tipo_comprobante = atcg.cod_tipo_comprobante
					  AND atcg.TIPO_CUENTA_CORRIENTE = CC.TIPO_CUENTA_CORRIENTE
					  AND CC.activo = 'S'
					  AND CC.COD_UNIDAD_ADMINISTRACION = '" . $filtro['cod_unidad_administracion'] . "'
					  AND acg.id_comprobante_gasto = '" . $filtro['id_comprobante_gasto'] . "'
					  AND acg.COD_TIPO_COMPROBANTE = atcg.cod_tipo_comprobante";
			unset($filtro['id_comprobante_gasto']);
		}

		if (isset($filtro['para_ordenes_pago']) && isset($filtro['cod_uni_admin'])) {
			if (isset($filtro['tipo_cuenta_corriente'])) {
				$where .= " AND cc.activo = 'S'
							AND cc.tipo_cuenta_corriente = '" . $filtro['tipo_cuenta_corriente'] . "'
							AND cc.cod_unidad_administracion = '" . $filtro['cod_uni_admin'] . "'
							AND exists (SELECT 1
										  FROM cg_ref_codes cg
										 WHERE cg.rv_domain = 'KR_TIPO_CUENTA_CORRIENTE'
										   AND cg.rv_low_value = cc.tipo_cuenta_corriente)";

				unset($filtro['tipo_cuenta_corriente']);
			}
			if (isset($filtro['cod_tipo_cta_cte']) && isset($filtro['id_cta_cte'])){
				$where .=" AND (	kcc.activo = 'S'
						   AND kcc.tipo_cuenta_corriente =
										  '".$filtro['cod_tipo_cta_cte']."'
						   AND kcc.cod_unidad_administracion =
													".$filtro['cod_uni_admin']."
						   AND kcc.id_proveedor in (select id_proveedor from kr_cuentas_corriente where id_cuenta_corriente = ".$filtro['id_cta_cte'].")
							   )";
				unset($filtro['cod_tipo_cta_cte']);
				unset($filtro['id_cta_cte']);
			}
			unset($filtro['para_ordenes_pago']);
			unset($filtro['cod_uni_admin']);
		}
		if (isset($filtro['p_inicio']))
		{
			$where.= " AND cc.nro_cuenta_corriente >= {$filtro['p_inicio']}";

			unset($filtro['p_inicio']);
		}

		if(isset($filtro['distinta'])) {
			$where.= " AND ".$filtro['id_cuenta_corriente']." <> cc.id_cuenta_corriente";
			unset($filtro['distinta']);
			unset($filtro['id_cuenta_corriente']);
		}

		if (isset($filtro['tipo_comprobante_gasto'])){
			$where .= " AND cc.tipo_cuenta_corriente = (select tipo_cuenta_corriente
														  from ad_tipos_comprobante_gasto
														 where cod_tipo_comprobante = '".$filtro['tipo_comprobante_gasto']."')";
			unset($filtro['tipo_comprobante_gasto']);
		}

		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'cc', '1=1');
		$sql = "
			SELECT
				cc.*,
				cc.id_cuenta_corriente|| ' - ' ||cc.descripcion || ' (' || cg.rv_meaning ||')' as lov_descripcion
			FROM
				KR_CUENTAS_CORRIENTE cc,
				CG_REF_CODES CG $join
			WHERE
				CG.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE'
				AND cg.rv_low_value = cc.tipo_cuenta_corriente
				AND $where
			ORDER BY cc.nro_cuenta_corriente ASC
		";
		$datos = toba::db()->consultar($sql);

		return $datos;
	}

	static public function get_tipos_cuenta($filtro) {
		$where = "1=1";
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'cc', '1=1');
		$sql = "SELECT cc.*
				FROM KR_CUENTAS_CORRIENTE cc
				WHERE  $where";
		$datos = toba::db()->consultar($sql);

		return $datos;
	}

	static public function get_cuenta_transferir_x_id($id_cuenta_transferir) {
		if (isset($id_cuenta_transferir)) {
			$sql = "SELECT	CUTR.ID_CUENTA_TRANSFERIR || ' (Nro.: ' || L_KRCTCT.NRO_CUENTA_CORRIENTE || ') - ' || L_KRCTCT.DESCRIPCION || ' (' || cg.rv_meaning ||')' lov_descripcion
					FROM	AD_CUENTAS_TRANSFERIR CUTR,
							KR_CUENTAS_CORRIENTE L_KRCTCT,
							CG_REF_CODES CG
					WHERE CUTR.ID_CUENTA_CORRIENTE = L_KRCTCT.ID_CUENTA_CORRIENTE
					AND CG.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE'
					AND cg.rv_low_value = L_KRCTCT.tipo_cuenta_corriente
					AND CUTR.ID_CUENTA_TRANSFERIR = " . quote($id_cuenta_transferir) . ";";
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

	static public function get_lov_cuentas_transferir_x_nombre($nombre, $filtro = array())
	{
		$where = '1=1';
		if (isset($nombre)) {
			$trans_id = ctr_construir_sentencias::construir_translate_ilike('CUTR.id_cuenta_transferir', $nombre);
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('L_KRCTCT.nro_cuenta_corriente', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('L_KRCTCT.descripcion', $nombre);
			$where .= " AND ($trans_id OR $trans_nro OR $trans_descripcion)";
		}

		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'CUTR', '1=1');
		$sql = "SELECT	CUTR.ID_CUENTA_TRANSFERIR ID_CUENTA_TRANSFERIR,
						CUTR.ID_CUENTA_CORRIENTE ID_CUENTA_CORRIENTE,
						L_KRCTCT.DESCRIPCION L_KRCTCT_DESCRIPCION,
						CUTR.CBU CBU,
						L_KRCTCT.TIPO_CUENTA_CORRIENTE,
						L_KRCTCT.NRO_CUENTA_CORRIENTE,
						L_KRCTCT.ORIGEN_CUENTA_CORRIENTE,
						CUTR.ID_CUENTA_TRANSFERIR || ' (Nro.: ' || L_KRCTCT.NRO_CUENTA_CORRIENTE || ') - ' || L_KRCTCT.DESCRIPCION || ' (' || cg.rv_meaning ||')' lov_descripcion
				FROM	AD_CUENTAS_TRANSFERIR CUTR,
						KR_CUENTAS_CORRIENTE L_KRCTCT,
						CG_REF_CODES CG
				WHERE CUTR.ID_CUENTA_CORRIENTE = L_KRCTCT.ID_CUENTA_CORRIENTE
				AND CG.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE'
				AND cg.rv_low_value = L_KRCTCT.tipo_cuenta_corriente
				AND $where;";

		$datos = toba::db()->consultar($sql);

		return $datos;
	}

	static public function get_nro_movimiento_cuenta_corriente_x_id($id_movimiento_cuenta) {
		if (isset($id_movimiento_cuenta)) {
			$sql = "SELECT	krmoct.nro_movimiento ||' - '|| substr(krmoct.DETALLE,1,65) ||' - Debe: $'|| krmoct.DEBE ||' Haber: $'|| krmoct.HABER lov_descripcion
					FROM KR_MOVIMIENTOS_CUENTA KRMOCT
					WHERE KRMOCT.ID_MOVIMIENTO_CUENTA = " . quote($id_movimiento_cuenta) . ";";
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

	static public function get_lov_nro_movimiento_cuentas_corriente_x_nombre($nombre, $filtro = array())
	{
		$where = '1=1';
		if (isset($nombre)) {
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('KRMOCT.nro_movimiento', $nombre);
			$trans_det = ctr_construir_sentencias::construir_translate_ilike('KRMOCT.detalle', $nombre);
			$where .= " AND ($trans_nro OR $trans_det)";
		}

		if (isset($filtro['con_saldo'])) {
			$where .= " AND ABS(PKG_KR_TRANSACCIONES.SALDO_TRANSACCION (KRMOCT.ID_TRANSACCION, KRMOCT.ID_CUENTA_CORRIENTE, NULL)) > 0 ";
			unset($filtro['con_saldo']);
		}

		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'KRMOCT', '1=1');
		$sql = "SELECT	KRMOCT.*,
						krmoct.nro_movimiento ||' - '|| substr(krmoct.DETALLE,1,65) ||' - Debe: $'|| krmoct.DEBE ||' Haber: $'|| krmoct.HABER lov_descripcion
				FROM KR_MOVIMIENTOS_CUENTA KRMOCT
				WHERE $where;";

		$datos = toba::db()->consultar($sql);

		return $datos;
	}

	static public function get_movimientos_cuentas_corrientes($filtro = array())
	{
		$where = '1=1';
		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'KRMOCT', '1=1');
		$sql = "SELECT	KRMOCT.*,
						to_char(KRMOCT.FECHA_MOVIMIENTO, 'dd/mm/yyyy') fecha_movimiento_format,
						kcc.nro_cuenta_corriente || ' - ' || kcc.descripcion as cuenta_corriente
				FROM KR_MOVIMIENTOS_CUENTA KRMOCT
				JOIN KR_CUENTAS_CORRIENTE kcc ON KRMOCT.id_cuenta_corriente = kcc.id_cuenta_corriente
				WHERE $where
				ORDER BY KRMOCT.id_movimiento_cuenta ASC;";

		$datos = toba::db()->consultar($sql);

		return $datos;
	}

	static public function get_apl_movimientos_cuentas_corrientes($filtro = array())
	{
		$where = '1=1';
		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'KRAPCT', '1=1');
		$sql = "SELECT	KRAPCT.*,
						to_char(KRAPCT.FECHA_APLICADO, 'dd/mm/yyyy') fecha_aplicado_format,
						kcc.nro_cuenta_corriente || ' - ' || kcc.descripcion as cuenta_corriente,
						KRMOCT.debe,
						KRMOCT.haber,
						KRMOCT.detalle,
						'N' as confirmado,
						KRMOCT.nro_movimiento,
						CGRC.RV_MEANING tipo_cuenta_corriente
				FROM KR_APLICACIONES_CUENTA KRAPCT
				JOIN KR_MOVIMIENTOS_CUENTA KRMOCT ON (KRMOCT.id_movimiento_cuenta = KRAPCT.id_movimiento_cuenta)
				JOIN KR_CUENTAS_CORRIENTE kcc ON KRMOCT.id_cuenta_corriente = kcc.id_cuenta_corriente
				LEFT JOIN CG_REF_CODES CGRC ON (CGRC.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE' AND CGRC.RV_LOW_VALUE = KCC.TIPO_CUENTA_CORRIENTE)
				WHERE $where
				ORDER BY KRMOCT.id_movimiento_cuenta ASC;";

		$datos = toba::db()->consultar($sql);

		return $datos;
	}

	static public function get_descripcion_cta_cte_x_id_cta_cte($id_cuenta_corriente)
	{
		if (isset($id_cuenta_corriente)) {
			$sql = "SELECT kcc.nro_cuenta_corriente || ' - ' || kcc.descripcion as nro_cta_cte_descripcion
					FROM kr_cuentas_corriente kcc
					WHERE id_cuenta_corriente = ".quote($id_cuenta_corriente) .";";
			$datos = toba::db()->consultar_fila($sql);
			if (isset($datos) && !empty($datos) && isset($datos['nro_cta_cte_descripcion'])) {
				return $datos['nro_cta_cte_descripcion'];
			} else {
				return '';
			}
		} else {
			return '';
		}
	}


	static public function get_lov_tipo_cuenta_corriente_x_codigo($codigo)
	{
		$codigo = quote($codigo);
		$sql = "
			SELECT
				rv_meaning lov_descripcion
			FROM CG_REF_CODES
			WHERE
				rv_domain = 'KR_TIPO_CUENTA_CORRIENTE'
				AND rv_low_value = $codigo
		";

		return toba::db()->consultar_fila($sql)['lov_descripcion'];
	}

	static public function get_lov_tipo_cuenta_corriente_x_nombre($nombre)
	{
		$trans_cod = ctr_construir_sentencias::construir_translate_ilike(
			'CGRC.rv_low_value',
			$nombre
		);
		$trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike(
			'CGRC.rv_meaning',
			$nombre
		);
		$where = "($trans_cod OR $trans_descricpcion)";
		$sql = "
			SELECT
				rv_low_value id_tipo_cc,
				rv_meaning lov_descripcion
			FROM CG_REF_CODES CGRC
			WHERE
				rv_domain = 'KR_TIPO_CUENTA_CORRIENTE'
				AND $where
		";

		return toba::db()->consultar($sql);
	}

	static public function get_lov_tipos_cuenta_corriente (){
		$sql = "SELECT RV_MEANING AS descripcion
				FROM CG_REF_CODES
				WHERE RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE';";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

	static function get_lov_organismo_financiero_x_codigo ($codigo_organismo_financiero){
		if (isset($codigo_organismo_financiero)){
			$sql = "SELECT KRORFI.*, KRORFI.COD_ORGANISMO_FINANCIERO ||' - '|| KRORFI.DESCRIPCION AS LOV_DESCRIPCION
					FROM KR_ORGANISMOS_FINANCIEROS KRORFI
					WHERE KRORFI.ACTIVO = 'S'";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}else{
			return null;
		}

	}

	static function get_lov_organismo_financiero_x_nombre ($nombre, $filtro){
		if (isset($nombre)) {
			$trans_cod = ctr_construir_sentencias::construir_translate_ilike('KRORFI.COD_ORGANISMO_FINANCIERO', $nombre);
			$trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('KRORFI.descripcion', $nombre);
			$where = "($trans_cod OR $trans_descricpcion)";
		} else {
			$where = " 1=1 ";
		}
		if (isset($filtro)){
			$where .=" and ".ctr_construir_sentencias::get_where_filtro($filtro, "KRORFI", '1=1');
		}
		$sql = "SELECT KRORFI.*, KRORFI.COD_ORGANISMO_FINANCIERO ||' - '|| KRORFI.DESCRIPCION AS LOV_DESCRIPCION
					FROM KR_ORGANISMOS_FINANCIEROS KRORFI
					WHERE $where and KRORFI.ACTIVO = 'S'";
		$datos = toba::db()->consultar($sql);
		if (isset($datos) && !empty($datos))
			return $datos;
		else
			return null;
	}

	static function get_lov_recaudadores_x_id ($id_recaudador){
		if (isset($id_recaudador)){
				$sql = "SELECT ADRE.*, ADRE.ID_RECAUDADOR ||' - '|| ADRE.DESCRIPCION AS LOV_DESCRIPCION
						FROM AD_RECAUDADORES ADRE
						WHERE ID_RECAUDADOR = ".quote($id_recaudador).";";
				$datos = toba::db()->consultar_fila($sql);
				return $datos['lov_descripcion'];
			}else{
				return null;
		}
	}

	static function get_lov_recaudadores_x_nombre ($nombre, $filtro){
		if (isset($nombre)) {
			$trans_id = ctr_construir_sentencias::construir_translate_ilike('ADRE.ID_RECAUDADOR', $nombre);
			$trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('ADRE.descripcion', $nombre);
			$where = "($trans_id OR $trans_descricpcion)";
		} else {
			$where = " 1=1 ";
		}
		if (isset($filtro)){
			$where .=" and ".ctr_construir_sentencias::get_where_filtro($filtro, "ADRE", '1=1');
		}
		$sql = "SELECT ADRE.*, ADRE.ID_RECAUDADOR ||' - '|| ADRE.DESCRIPCION AS LOV_DESCRIPCION
				FROM AD_RECAUDADORES ADRE
				WHERE $where ";
		$datos = toba::db()->consultar($sql);
		if (isset($datos) && !empty($datos))
			return $datos;
		else
			return null;
	}

	static function validar_cuenta_corriente ($nro_cuenta, $cod_unidad_administracion, $tipo_cuenta, $id_cuenta_corriente){
		//Busca si existe cuenta con el mismo num, ua y tipo.
		if (isset($nro_cuenta) && $nro_cuenta != null && isset($cod_unidad_administracion)&& $cod_unidad_administracion != null
			 && isset($tipo_cuenta) && $tipo_cuenta != null){
			$where = " krcuco.TIPO_CUENTA_CORRIENTE = '".$tipo_cuenta."'
						AND krcuco.NRO_CUENTA_CORRIENTE = ".$nro_cuenta."
						AND krcuco.COD_UNIDAD_ADMINISTRACION = ".$cod_unidad_administracion."
   					 ";
			if (isset($id_cuenta_corriente)){
				$where .= " AND KRCUCO.ID_CUENTA_CORRIENTE <> ".$id_cuenta_corriente."";
			}
			$sql = "SELECT COUNT(KRCUCO.ID_CUENTA_CORRIENTE) AS SUMA
					FROM kr_cuentas_corriente krcuco
					WHERE $where";
			$resultado = toba::db()->consultar_fila($sql);
			if ($resultado['suma'] > 0){
				return true;
			}else return false;
		}else{
			toba::notificacion()->info("Hay parametros nulos");
			return null;
		}
	}

	static public function get_aplicaciones_bebe ($id_movimiento_cuenta){
		if (isset($id_movimiento_cuenta)){
			$sql = "SELECT DISTINCT A.*, B.NRO_MOVIMIENTO, B.DETALLE
					FROM KR_APLICACIONES_CUENTA A,KR_MOVIMIENTOS_CUENTA B
					WHERE A.ID_MOVIMIENTO_CUENTA = $id_movimiento_cuenta
							AND A.ID_MOVIMIENTO_CUENTA_APL = B.ID_MOVIMIENTO_CUENTA";
			$datos = toba::db()->consultar($sql);
			return $datos;
		}else return null;
	}
	static public function get_aplicaciones_haber ($id_movimiento_cuenta){
		if (isset($id_movimiento_cuenta)){
			$sql = "SELECT DISTINCT A.*, B.NRO_MOVIMIENTO, B.DETALLE
					FROM KR_APLICACIONES_CUENTA A, KR_MOVIMIENTOS_CUENTA B
					WHERE A.ID_MOVIMIENTO_CUENTA_APL = $id_movimiento_cuenta
	  					AND A.ID_MOVIMIENTO_CUENTA=B.ID_MOVIMIENTO_CUENTA";
			$datos = toba::db()->consultar($sql);
			return $datos;
		}else return null;
	}

	static public function get_lov_interfase_x_codigo ($cod_interfase){
		if (isset($cod_interfase)){

			$sql = "SELECT APEI.*, APEI.COD_INTERFASE ||' - '||APEI.DESCRIPCION AS LOV_DESCRIPCION
					FROM AD_PAGO_ELEC_INTF APEI
					WHERE APEI.COD_INTERFASE = $cod_interfase
					ORDER BY LOV_DESCRIPCION ASC;";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}else return null;
	}

	static public function get_lov_interfase_x_nombre ($nombre, $filtro){
		if (isset($nombre)) {
			$trans_cod = ctr_construir_sentencias::construir_translate_ilike('APEI.COD_INTERFASE', $nombre);
			$trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('APEI.DESCRIPCION', $nombre);
			$where = "($trans_cod OR $trans_descricpcion)";
		} else {
			$where = " 1=1 ";
		}
		$sql = "SELECT APEI.*, APEI.COD_INTERFASE ||' - '||APEI.DESCRIPCION AS LOV_DESCRIPCION
				FROM AD_PAGO_ELEC_INTF APEI
				WHERE $where
				ORDER BY LOV_DESCRIPCION ASC;";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

	static public function crear_cuentas_para_auxiliar ($cod_auxiliar, $descripcion, $con_transaccion = true){
		try{
			$sql = "BEGIN :resultado := Pkg_Kr_Cuentas_Corriente.crear_cuentas_para_auxiliar(:cod_auxiliar, :descripcion);END;";
			$parametros = array (   array(  'nombre' => 'cod_auxiliar',
											'tipo_dato' => PDO::PARAM_INT,
											'longitud' => 20,
											'valor' => $cod_auxiliar),

									array(  'nombre' => 'descripcion',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 400,
											'valor' => $descripcion),

									array(  'nombre' => 'resultado',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 1000,
											'valor' => '')
							);
			if ($con_transaccion)
				toba::db()->abrir_transaccion();

			$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

			if ($con_transaccion){
				if ($resultado[2]['valor'] == 'OK'){
					toba::db()->cerrar_transaccion();
				}else{
					toba::db()->abortar_transaccion();
					toba::notificacion()->info($resultado[2]['valor']);
				}
			}
			return $resultado[2]['valor'];

		} catch (toba_error_db $e_db) {
			toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
			toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
		} catch (toba_error $e) {
			toba::notificacion()->error('Error '.$e->get_mensaje());
			toba::logger()->error('Error '.$e->get_mensaje());
		}
	}

	static public function eliminar_cuenta_corriente($p_origen, $p_id)
	{
        $sql = "BEGIN
        			:resultado := Pkg_Kr_Cuentas_Corriente.eliminar_cta_cte(:p_origen, :p_id);
        		END;";
        $parametros =  [
        		['nombre' => 'resultado',
				 'tipo_dato' => PDO::PARAM_STR,
				 'longitud' => 1000,
				 'valor' => ''],
        		['nombre' => 'p_origen',
				 'tipo_dato' => PDO::PARAM_STR,
				 'longitud' => 20,
				 'valor' => $p_origen],
				['nombre' => 'p_id',
				 'tipo_dato' => PDO::PARAM_STR,
				 'longitud' => 400,
				 'valor' => $p_id],
		];
		$verificador = function($resultado) {
				if ($resultado[0]['valor'] !== 'S') {
					throw new toba_error(ctr_procedimientos::procesar_error($resultado[0]['valor']));
				}
			};
        ctr_procedimientos::ejecutar_procedimiento('No se puede eliminar la Cuenta Corriente',$sql,$parametros, $verificador);
	}

	static public function eliminar_todas_cuenta_corriente ($p_origen, $p_id, $con_transaccion = true){
		try{
			$sql = "BEGIN :resultado := Pkg_Kr_Cuentas_Corriente.eliminar_todas_cta_cte(:p_origen, :p_id);END;";
			$parametros = array (   array(  'nombre' => 'p_origen',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 20,
											'valor' => $p_origen),

									array(  'nombre' => 'p_id',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 400,
											'valor' => $p_id),

									array(  'nombre' => 'resultado',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 1000,
											'valor' => ''));
			if ($con_transaccion)
				toba::db()->abrir_transaccion();

			$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
			if ($con_transaccion){
				if ($resultado[2]['valor'] != 'OK'){
					toba::db()->abortar_transaccion();
					return $resultado[2]['valor'];
				}else{
					toba::db()->cerrar_transaccion();
					return $resultado[2]['valor'];
				}
			}else{
				return $resultado[2]['valor'];
			}

		} catch (toba_error_db $e_db) {
			toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
			toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
		} catch (toba_error $e) {
			toba::notificacion()->error('Error '.$e->get_mensaje());
			toba::logger()->error('Error '.$e->get_mensaje());
		}
	}


	static public function cambiar_estado_cta_cte ($p_origen, $p_id, $p_estado, $con_transaccion = true){
		try{
			$sql = "BEGIN :resultado := PKG_KR_CUENTAS_CORRIENTE.CAMBIAR_ESTADO_CTA_CTE(:p_origen, :p_id, :p_estado);END;";
			$parametros = array (   array(  'nombre' => 'p_origen',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 20,
											'valor' => $p_origen),

									array(  'nombre' => 'p_id',
											'tipo_dato' => PDO::PARAM_INT,
											'longitud' => 60,
											'valor' => $p_id),

									array(  'nombre' => 'p_estado',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 400,
											'valor' => $p_estado),

									array(  'nombre' => 'resultado',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 1000,
											'valor' => '')
							);
			if ($con_transaccion)
				toba::db()->abrir_transaccion();

			$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

			if ($con_transaccion){
				if ($resultado[2]['valor'] == 'OK'){
					toba::db()->cerrar_transaccion();
				}else{
					toba::db()->abortar_transaccion();
					toba::notificacion()->info($resultado[2]['valor']);
				}
			}
			return $resultado[2]['valor'];

		} catch (toba_error_db $e_db) {
			toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
			toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
		} catch (toba_error $e) {
			toba::notificacion()->error('Error '.$e->get_mensaje());
			toba::logger()->error('Error '.$e->get_mensaje());
		}
	}

	//--------------------------------------------------------------------------------
	//-------------------- UI ITEMS --------------------------------------------------
	//--------------------------------------------------------------------------------

 	static public function get_tipo_cuenta_x_id_cuenta ($id_cuenta_corriente){
		$sql = "SELECT TIPO_CUENTA_CORRIENTE as ui_tipo_cuenta_corriente
				FROM KR_CUENTAS_CORRIENTE
				WHERE ID_CUENTA_CORRIENTE = ".quote($id_cuenta_corriente).";";
		$datos = toba::db()->consultar_fila($sql);
		return $datos;
	}

}

?>
