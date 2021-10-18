<?php
class dao_tipos_transaccion
{
	/**
	 * @return {string} descripcion
	 */
	static public function get_tipo_transaccion_por_id($id_tipo_transaccion)
	{
		$sql = "
			SELECT
				'$id_tipo_transaccion - ' || KTT.DESCRIPCION lov_descripcion
			FROM
				KR_TIPOS_TRANSACCION KTT
			WHERE
				KTT.COD_TIPO_TRANSACCION = $id_tipo_transaccion
		";
		$datos = toba::db()->consultar_fila($sql);

		return $datos['lov_descripcion'];
	}
	/**
	 * @return {Array<string, string>} arreglo de tipos de matriz de comprobante de pago
	 */
	static public function get_lov_tipos_transaccion_por_nombre($nombre, $filtro = array())
	{
		if (isset($nombre))
		{
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('KTT.COD_TIPO_TRANSACCION', $nombre);
			$trans_nombre = ctr_construir_sentencias::construir_translate_ilike('KTT.DESCRIPCION', $nombre);
			$where = "($trans_codigo OR $trans_nombre)";
		} else {
			$where = '1=1';
		}
		$sql = "
			SELECT
				KTT.COD_TIPO_TRANSACCION cod_tipo_transaccion,
				KTT.COD_TIPO_TRANSACCION || ' - ' || KTT.DESCRIPCION lov_descripcion
			FROM
				KR_TIPOS_TRANSACCION KTT
			WHERE
				$where
			ORDER BY KTT.COD_TIPO_TRANSACCION ASC
		";
		$datos = toba::db()->consultar($sql);

		return $datos;
	}
}
