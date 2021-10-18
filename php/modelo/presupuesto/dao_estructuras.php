<?php
class dao_estructuras
{
	/**
	 * @return {string} descripcion
	 */
	static public function get_estructuras_por_id($id_estructura)
	{
		$sql = "
			SELECT
				'$id_estructura - ' || PE.DESCRIPCION lov_descripcion
			FROM
				PR_ESTRUCTURAS PE
			WHERE
				PE.ID_ESTRUCTURA = $id_estructura
		";
		$datos = toba::db()->consultar_fila($sql);

		return $datos['lov_descripcion'];
	}
	/**
	 * @return {Array<string, string>} descripcion
	 */
	static public function get_lov_estructuras_por_nombre($nombre, $filtro = array())
	{
		$where = '1=1';

		if (isset($nombre))
		{
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('PE.ID_ESTRUCTURA', $nombre);
			$trans_nombre = ctr_construir_sentencias::construir_translate_ilike('PE.DESCRIPCION', $nombre);
			$where = "($trans_codigo OR $trans_nombre)";
		}
		$sql = "
			SELECT
				PE.ID_ESTRUCTURA id_estructura,
				PE.ID_ESTRUCTURA || ' - ' || PE.DESCRIPCION lov_descripcion
			FROM
				PR_ESTRUCTURAS PE
			WHERE
				$where
			ORDER BY
				PE.ID_ESTRUCTURA ASC
		";
		$datos = toba::db()->consultar($sql);

		return $datos;
	}
}
