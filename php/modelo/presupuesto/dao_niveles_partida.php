<?php
class dao_niveles_partida
{
	/**
	 * @return {Array<string, string>} descripcion
	 */
	static public function get_lov_niveles_partida()
	{
		$sql = "
			SELECT
				PNP.NIVEL nivel,
				PNP.NIVEL || ' - ' || PNP.DESCRIPCION lov_descripcion
			FROM
				PR_NIVELES_PARTIDA PNP
			ORDER BY
				PNP.NIVEL ASC
		";
		$datos = toba::db()->consultar($sql);

		return $datos;
	}
}
