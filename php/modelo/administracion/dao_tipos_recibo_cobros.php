<?php
class dao_tipos_recibo_cobros
{
	/**
	 * @return {Array<string, string>} todos los elementos
	 */
	static public function get_tipos_recibo_cobros($filtro = array())
	{
		$where = ' 1=1 ';
		if (isset($filtro) )
		{
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'ATRC', '1=1', array('nombre'));
		}
		$sql = "
			SELECT *
			FROM
				AD_TIPOS_RECIBO_COBRO ATRC
			WHERE
				$where
			ORDER BY
				cod_tipo_recibo ASC
		;";
		$datos = toba::db()->consultar($sql);

		return $datos;
	}
	/**
	 * @return {string} descripcion
	 */
	static public function get_tipo_aplicacion_por_id($id_tipo_aplicacion)
	{
		$sql = "
			SELECT
				'$id_tipo_aplicacion - ' || CG.RV_MEANING lov_descripcion
			FROM
				CG_REF_CODES CG
			WHERE
				CG.RV_DOMAIN = 'AD_TIPO_APLICACION_COB'
				AND CG.RV_LOW_VALUE = '$id_tipo_aplicacion'
		";
		$datos = toba::db()->consultar_fila($sql);

		return $datos['lov_descripcion'];
	}
	/**
	 * @return {Array<string, string>} descripcion
	 */
	static public function get_lov_tipos_aplicacion_por_nombre($nombre, $filtro = array())
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
				CG.RV_LOW_VALUE id_tipo_aplicacion,
				CG.RV_LOW_VALUE || ' - ' || CG.RV_MEANING lov_descripcion
			FROM
				CG_REF_CODES CG
			WHERE
				CG.RV_DOMAIN = 'AD_TIPO_APLICACION_COB'
				AND $where
		";
		$datos = toba::db()->consultar($sql);

		return $datos;
	}
}
