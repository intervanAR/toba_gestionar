<?php

class dao_valor_dominios
{
	public static function get($dominio)
	{
		$sql = "
			SELECT cg.*
			FROM cg_ref_codes cg
			WHERE rv_domain = '$dominio'
		";

		return toba::db()->consultar($sql);
	}

	public static function significado_dominio($dominio, $valor)
	{
		$sql = "
			SELECT cg.*
			FROM cg_ref_codes cg
			WHERE
				rv_domain = '$dominio'
				AND rv_low_value = '$valor'
		";

		return toba::db()->consultar($sql);
	}

	public static function get_meaning($dominio, $valor)
	{
		$sql = "SELECT cg.rv_meaning
			FROM cg_ref_codes cg
			WHERE rv_domain='".$dominio."'
					AND rv_low_value ='".$valor."'";

		$datos = toba::db()->consultar_fila($sql);

		return $datos['rv_meaning'];
	}

	/**
	 * Universaliza la consulta get_[dominio]_x_nombre.
	 *
	 * @param string $dominio clave rv_domain
	 * @param string|null $nombre  texto de búsqueda
	 *
	 * @return Array<mixed>
	 */
	public static function get_dominio_x_nombre($dominio, $nombre = null)
	{
		$where = '1=1';

		if (isset($nombre)) {
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike(
				'rv_low_value',
				$nombre
			);
			$trans_nombre = ctr_construir_sentencias::construir_translate_ilike(
				'rv_meaning',
				$nombre
			);
			$where = "($trans_descripcion OR $trans_nombre)";
		}
		$dominio = quote($dominio);
		$sql = "
			SELECT
				rv_low_value clave,
				rv_meaning descripcion
			FROM
				CG_REF_CODES
			WHERE
				rv_domain = $dominio
				AND $where
		";

		toba::logger()->debug("[dao_valor_dominios] \n$sql");

		return toba::db()->consultar($sql);
	}

	public static function get_dominio($rv_domain, $filtro = null)
	{
		if (!isset($rv_domain) || empty($rv_domain)) {
			return [];
		}
		$where = '';
		if (isset($filtro)) {
			$where = 'AND '.ctr_construir_sentencias::get_where_filtro($filtro, 'crc');
		}
		$sql = "
			SELECT	crc.*,
					crc.rv_low_value clave,
					crc.rv_meaning descripcion
			FROM cg_ref_codes crc
			WHERE
				rv_domain = '$rv_domain'
				$where
			ORDER BY crc.rv_meaning ASC
		;";

		$datos = toba::db()->consultar($sql);

		return $datos;
	}

	public static function get_total_domain($dominio)
	{
		$sql = "
				SELECT
					count(1) total
				FROM
					cg_ref_codes cg
				WHERE
					rv_domain = '".$dominio."' ;";

		$datos = toba::db()->consultar_fila($sql);

		return $datos['total'];
	}
}
