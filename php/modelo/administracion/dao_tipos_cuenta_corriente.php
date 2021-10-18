<?php
class dao_tipos_cuenta_corriente
{
	/**
	 * @return {string} descripcion
	 */
	static public function get_tipo_cuenta_corriente_por_id($id_tipo_cuenta_corriente)
	{
		$sql = "
			SELECT
				'$id_tipo_cuenta_corriente - ' || CG.RV_MEANING lov_descripcion
			FROM
				CG_REF_CODES CG
			WHERE
				CG.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE'
				AND CG.RV_LOW_VALUE = '$id_tipo_cuenta_corriente'
		";
		$datos = toba::db()->consultar_fila($sql);

		return $datos['lov_descripcion'];
	}
	/**
	 * @return {Array<string, string>} descripcion
	 */
	static public function get_lov_tipos_cuenta_corriente_por_nombre($nombre, $filtro = array())
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
				CG.RV_LOW_VALUE id_tipo_cuenta_corriente,
				CG.RV_LOW_VALUE || ' - ' || CG.RV_MEANING lov_descripcion
			FROM
				CG_REF_CODES CG
			WHERE
				CG.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE'
				AND $where
		";
		$datos = toba::db()->consultar($sql);

		return $datos;
	}
}
