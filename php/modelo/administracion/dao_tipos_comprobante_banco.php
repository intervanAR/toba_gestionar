<?php
class dao_tipos_comprobante_banco
{
	/**
	 * @return {Array<string, string>} todos los elementos
	 */
	static public function get_tipos_comprobante_banco($filtro = array())
	{
		$where = ' 1=1 ';
		if (isset($filtro) )
		{
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'ATCB', '1=1', array('nombre'));
		}
		$sql = "
			SELECT *
			FROM
				AD_TIPOS_COMPROBANTE_BANCO ATCB
			WHERE
				$where
			ORDER BY
				cod_tipo_comprobante ASC
		;";
		$datos = toba::db()->consultar($sql);

		return $datos;
	}
	/**
	 * @return {string} descripcion
	 */
	static public function get_tipo_comprobante_pago_por_id($id_tipo_comprobante_pago)
	{
		$sql = "
			SELECT
				'$id_tipo_comprobante_pago - ' || CG.RV_MEANING lov_descripcion
			FROM
				AD_TIPOS_COMPROBANTE_BANCO ATCB,
				CG_REF_CODES CG
			WHERE
				CG.RV_DOMAIN = 'AD_TIPO_COMPROB_PAGO'
				AND CG.RV_LOW_VALUE = ATCB.TIPO_COMPROBANTE_PAGO
				AND ATCB.TIPO_COMPROBANTE_PAGO = '$id_tipo_comprobante_pago'
		";
		$datos = toba::db()->consultar_fila($sql);

		return $datos['lov_descripcion'];
	}
	/**
	 * @return {Array<string, string>} arreglo de tipos de comprobante de pago
	 */
	static public function get_lov_tipos_comprobante_pago_por_nombre($nombre, $filtro = array())
	{
		if (isset($nombre))
		{
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('CG.RV_LOW_VALUE', $nombre);
			$trans_nombre = ctr_construir_sentencias::construir_translate_ilike('CG.RV_MEANING', $nombre);
			$where = "($trans_codigo OR $trans_nombre)";
		} else {
			$where = '1=1';
		}
		$sql = "
			SELECT
				CG.RV_LOW_VALUE id_tipo_comprobante_pago,
				CG.RV_LOW_VALUE || ' - ' ||CG.RV_MEANING lov_descripcion
			FROM
				CG_REF_CODES CG
			WHERE
				$where
				AND CG.RV_DOMAIN = 'AD_TIPO_COMPROB_PAGO'
			ORDER BY CG.RV_LOW_VALUE ASC
		";
		$datos = toba::db()->consultar($sql);

		return $datos;
	}
	/**
	 * @return {string} descripcion
	 */
	static public function get_tipo_matriz_comp_pago_por_id($id_tipo_matriz_comp_pago)
	{
		$sql = "
			SELECT
				'$id_tipo_matriz_comp_pago - ' || ECP1.DESCRIPCION || ' - ' || ECP2.DESCRIPCION lov_descripcion
			FROM
				AD_MATRIZ_COMP_PAGO MCP,
				AD_ESTADOS_COMP_PAGO ECP1,
				AD_ESTADOS_COMP_PAGO ECP2
			WHERE
				ECP1.ESTADO = MCP.ESTADO
				AND ECP2.ESTADO = MCP.ESTADO_HASTA
				AND MCP.ID_MATRIZ_COMP_PAGO = $id_tipo_matriz_comp_pago
		";
		$datos = toba::db()->consultar_fila($sql);

		return $datos['lov_descripcion'];
	}
	/**
	 * @return {Array<string, string>} arreglo de tipos de matriz de comprobante de pago
	 */
	static public function get_lov_tipos_matriz_comprobante_pago_por_nombre($nombre, $filtro = array())
	{
		if (isset($nombre))
		{
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('MCP.ID_MATRIZ_COMP_PAGO ', $nombre);
			$trans_descripcion1 = ctr_construir_sentencias::construir_translate_ilike('ECP1.DESCRIPCION', $nombre);
			$trans_descripcion2 = ctr_construir_sentencias::construir_translate_ilike('ECP2.DESCRIPCION', $nombre);
			$where = "($trans_codigo OR $trans_descripcion1 OR $trans_descripcion2)";
		} else {
			$where = '1=1';
		}
		$sql = "
			SELECT
				MCP.ID_MATRIZ_COMP_PAGO id_matriz_comp_pago,
				MCP.ID_MATRIZ_COMP_PAGO || ' - ' || ECP1.DESCRIPCION || ' - ' || ECP2.DESCRIPCION lov_descripcion
			FROM
				AD_MATRIZ_COMP_PAGO MCP,
				AD_ESTADOS_COMP_PAGO ECP1,
				AD_ESTADOS_COMP_PAGO ECP2
			WHERE
				$where
				AND ECP1.ESTADO = MCP.ESTADO
				AND ECP2.ESTADO = MCP.ESTADO_HASTA
			ORDER BY MCP.ID_MATRIZ_COMP_PAGO ASC
		";
		$datos = toba::db()->consultar($sql);

		return $datos;
	}
}
