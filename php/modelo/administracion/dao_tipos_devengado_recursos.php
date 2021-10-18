<?php
class dao_tipos_devengado_recursos
{
	/**
	 * @return {Array<string, string>} todos los elementos
	 */
	static public function get_tipos_devengado_recursos($filtro = array())
	{
		$where = '1=1';
		if (isset($filtro))
		{
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'ATCR', '1=1', array('nombre'));
		}
		$sql = "
			SELECT *
			FROM
				AD_TIPOS_COMPROBANTE_RECURSO ATCR
			WHERE
				$where
			ORDER BY
				cod_tipo_comprobante ASC
		;";
		$datos = toba::db()->consultar($sql);

		return $datos;
	}
}
