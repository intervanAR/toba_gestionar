<?php
class dao_tipos_periodos
{
	/**
	 * @return {Array<string, string>} descripcion
	 */
	static public function get_lov_tipos_periodos()
	{
		$sql = "
			SELECT
				KTP.TIPO_PERIODO tipo_periodo,
				KTP.DESCRIPCION lov_descripcion
			FROM
				KR_TIPOS_PERIODOS KTP
			ORDER BY
				KTP.TIPO_PERIODO ASC
		";
		$datos = toba::db()->consultar($sql);

		return $datos;
	}
}
