<?php
class dao_cs_conceptos {

	public static function get_conceptos($filtro = [])
	{
		$where = "1=1";

		if (!empty($filtro)){

			if (isset($filtro['descripcion'])) {
	            $descripcion = ctr_construir_sentencias::construir_translate_ilike("csco.descripcion", $filtro['descripcion']);
	            $where.= " AND ($descripcion) ";
	            unset($filtro['descripcion']);
	        }

        	$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'csco', '1=1');
		}

		$sql = "
			SELECT csco.*
			FROM cs_conceptos csco
			WHERE $where
		";
		toba::logger()->debug('SQL CONCEPTOS '. $sql);
		return toba::db()->consultar($sql);
	}

	public static function get_lov_cs_conceptso_x_codigo ($cod_concepto)
	{
		if (isset($cod_concepto)){
			$sql = "
				SELECT CSCO.*, CSCO.COD_CONCEPTO ||' - '|| CSCO.DESCRIPCION LOV_DESCRIPCION
				FROM CS_CONCEPTOS CSCO
				WHERE CSCO.COD_CONCEPTO = $cod_concepto
			";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}
	}

	public static function get_lov_cs_conceptos_x_nombre ($nombre, $filtro = [])
	{
		if (isset($nombre)) {
            $cod_comp = ctr_construir_sentencias::construir_translate_ilike('CSCO.COD_CONCEPTO', $nombre);
            $trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('CSCO.DESCRIPCION', $nombre);
            $where = "($cod_comp OR $trans_descricpcion)";
        } else {
            $where = "1=1";
        }

        if (!empty($filtro)){

			if (isset($filtro['not_exist'])) {
				$cod_agrupamiento = $filtro['cod_agrupamiento'];
	            $where .= " AND not exists
        		( select 1 FROM CS_GRUPOS_CONCEPTOS
	                 WHERE cod_agrupamiento = '$cod_agrupamiento'
	                 and cod_concepto = CSCO.cod_concepto
           		) ";
           		unset($filtro['cod_agrupamiento']);
        		unset($filtro['not_exist']);
	        }

        	$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'CSCO', '1=1');
		}

        $sql = "
        	SELECT CSCO.*, CSCO.COD_CONCEPTO ||' - '|| CSCO.DESCRIPCION LOV_DESCRIPCION
			FROM CS_CONCEPTOS CSCO
			WHERE $where
            ORDER BY lov_descripcion
        ";
        $datos = toba::db()->consultar($sql);
        return $datos;
	}

	public static function get_grupos_conceptos($filtro = [])
	{
		$where = "1=1";

		if (!empty($filtro)){

			if (isset($filtro['descripcion'])) {
	            $descripcion = ctr_construir_sentencias::construir_translate_ilike("csco.descripcion", $filtro['descripcion']);
	            $where.= " AND ($descripcion) ";
	            unset($filtro['descripcion']);
	        }

        	$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'csgrco', '1=1');
		}

		$sql = "
			SELECT *
			FROM
			    cs_grupos_conceptos csgrco
			    JOIN cs_conceptos csco ON (csgrco.cod_concepto = csco.cod_concepto)
			WHERE $where
		";
		toba::logger()->debug('SQL GRUPOS CONCEPTOS '. $sql);
		return toba::db()->consultar($sql);
	}

}
?>