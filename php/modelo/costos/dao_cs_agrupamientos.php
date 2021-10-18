<?php
class dao_cs_agrupamientos {

	public static function get_agrupamientos ($filtro = [])
	{
		$where = "1=1";

		if (!empty($filtro)){

			if (isset($filtro['descripcion'])) {
	            $descripcion = ctr_construir_sentencias::construir_translate_ilike("csag.descripcion", $filtro['descripcion']);
	            $where.= " AND ($descripcion) ";
	            unset($filtro['descripcion']);
	        }

        	$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'csag', '1=1');
		}

		$sql = "
			SELECT csag.*
			FROM cs_agrupamientos csag
			WHERE $where
			ORDER BY cod_agrupamiento
		";
		toba::logger()->debug('SQL AGRUPAMIENTOS '. $sql);
		return toba::db()->consultar($sql);
	}

	public static function get_nuevo_id_grupo($cod_agrupamiento)
    {
        $sql = "
            SELECT nvl(max(cod_grupo)+1,1) cod_grupo
            FROM CS_GRUPOS
            WHERE
            	nvl(cod_agrupamiento,1) = nvl('$cod_agrupamiento',1)
        ";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['cod_grupo'];

    }

}
?>