<?php

class dao_tipos_factura
{
	/**
	 * @return {Array<string, string>} todos los elementos
	 */
	static public function get_tipos_factura($filtro = [])
	{
		$where = ' 1=1 ';
		if (isset($filtro))
		{
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'ATF', '1=1', array('nombre'));
		}
		$sql = "
			SELECT *
			FROM
				AD_TIPOS_FACTURA ATF
			WHERE
				$where
			ORDER BY
				id_tipo_comprobante ASC
		;";
		$datos = toba::db()->consultar($sql);

		return $datos;
	}

	static public function get_lov_tipo_factura_x_nombre ($nombre, $filtro = []){
		if (isset($nombre))
		{
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('ADTF.COD_TIPO_FACTURA', $nombre);
			$trans_nombre = ctr_construir_sentencias::construir_translate_ilike('ADTF.DESCRIPCION', $nombre);
			$where = "($trans_codigo OR $trans_nombre)";
		} else {
			$where = ' 1=1 ';
		}
		
		$where .= " AND ".ctr_construir_sentencias::get_where_filtro($filtro, 'ADTF', '1=1');

		$sql = "
			SELECT ADTF.*, ADTF.COD_TIPO_FACTURA ||' - '||ADTF.DESCRIPCION ||' - '|| ADTF.LETRA_FACTURA lov_descripcion
			  FROM AD_TIPOS_FACTURA ADTF 
		     WHERE $where ";
		$datos = toba::db()->consultar($sql);

		return $datos;
	}
	static public function get_lov_tipo_factura_x_codigo ($cod_tipo_factura){
		$sql = "
			SELECT ADTF.COD_TIPO_FACTURA ||' - '||ADTF.DESCRIPCION ||' - '|| ADTF.LETRA_FACTURA as lov_descripcion
			  FROM AD_TIPOS_FACTURA ADTF 
		     WHERE adtf.cod_tipo_factura = $cod_tipo_factura ";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}

	static public function get_tipo_factura ($cod_tipo_factura){
		$sql = "SELECT *
				  FROM AD_TIPOS_FACTURA
				 WHERE cod_tipo_factura = ".quote($cod_tipo_factura);
		return toba::db()->consultar_fila($sql);
	}
	
	/**
	 * @return {string} descripcion
	 */
	static public function get_tipo_comprobante_por_id($id_tipo_comprobante)
	{

		$sql = "
			SELECT
				ATC.ID_TIPO_COMPROBANTE || ' - ' || CG.RV_MEANING lov_descripcion
			FROM
				AD_TIPOS_COMPROBANTE ATC,
				CG_REF_CODES CG
			WHERE
				CG.RV_DOMAIN = 'AD_TIPOS_COMPROBANTE'
				AND CG.RV_LOW_VALUE = ATC.TIPO_COMPROBANTE
				AND ATC.ID_TIPO_COMPROBANTE = '$id_tipo_comprobante'
		";
		$datos = toba::db()->consultar_fila($sql);

		return $datos['lov_descripcion'];
	}
	/**
	 * @return {Array<string, string>} descripcion
	 */
	static public function get_lov_tipos_comprobante_por_nombre($nombre, $filtro = array())
	{
		if (isset($nombre))
		{
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('ATC.ID_TIPO_COMPROBANTE', $nombre);
			$trans_nombre = ctr_construir_sentencias::construir_translate_ilike('CG.RV_MEANING', $nombre);
			$where = "($trans_codigo OR $trans_nombre)";
		} else {
			$where = '1=1';
		}
		$sql = "
			SELECT
				ATC.ID_TIPO_COMPROBANTE ID_TIPO_COMPROBANTE,
				ATC.ID_TIPO_COMPROBANTE || ' - ' || CG.RV_MEANING lov_descripcion
			FROM
				AD_TIPOS_COMPROBANTE ATC,
				CG_REF_CODES CG
			WHERE
				CG.RV_DOMAIN = 'AD_TIPOS_COMPROBANTE'
				AND CG.RV_LOW_VALUE = ATC.TIPO_COMPROBANTE
				AND $where
		";
		$datos = toba::db()->consultar($sql);

		return $datos;
	}
	/**
	 * @return {string} descripcion
	 */
	static public function get_tipo_impuesto_por_id($id_tipo_impuesto)
	{
		$sql = "
			SELECT
				AI.COD_IMPUESTO || ' - ' || AI.DESCRIPCION lov_descripcion
			FROM
				AD_IMPUESTOS AI
			WHERE
				AI.COD_IMPUESTO = '$id_tipo_impuesto'
		";
		$datos = toba::db()->consultar_fila($sql);

		return $datos['lov_descripcion'];
	}

	/**
	 * @return {Array<string, string>} tipos de impuesto
	 */
	static public function get_lov_tipos_impuesto_por_nombre($nombre, $filtro = array())
	{
		if (isset($nombre))
		{
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('AI.COD_IMPUESTO', $nombre);
			$trans_nombre = ctr_construir_sentencias::construir_translate_ilike('AI.DESCRIPCION', $nombre);
			$where = "($trans_codigo OR $trans_nombre)";
		} else {
			$where = '1=1';
		}
		$sql = "
			SELECT
				AI.COD_IMPUESTO COD_IMPUESTO,
				AI.COD_IMPUESTO || ' - ' || AI.DESCRIPCION lov_descripcion
			FROM
				AD_IMPUESTOS AI
			WHERE
				$where
		";
		$datos = toba::db()->consultar($sql);

		return $datos;
	}
}
