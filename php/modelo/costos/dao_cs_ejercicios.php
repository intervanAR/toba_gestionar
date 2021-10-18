<?php
class dao_cs_ejercicios {

	public static function get_ejercicios($filtro = [])
	{
		$where = "1=1";

		if (!empty($filtro)){

			if (isset($filtro['descripcion'])) {
	            $descripcion = ctr_construir_sentencias::construir_translate_ilike("CSEJ.descripcion", $filtro['descripcion']);
	            $where.= " AND ($descripcion) ";
	            unset($filtro['descripcion']);
	        }

        	$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'CSEJ', '1=1');
		}

		$sql = "
			SELECT CSEJ.*
			FROM CS_EJERCICIOS CSEJ
			WHERE $where
		";
		toba::logger()->debug('SQL EJERCICIOS '. $sql);
		return toba::db()->consultar($sql);
	}

	public static function get_nuevo_id_ejercicio()
    {
        $sql = "
            SELECT nvl(max(id_ejercicio)+1,1) id_ejercicio
            FROM CS_EJERCICIOS
        ";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['id_ejercicio'];

    }

	public static function get_lov_cs_ejercicios_x_nombre($nombre, $filtro = [])
	{
		if (isset($nombre)) {
   			$trans_cod = ctr_construir_sentencias::construir_translate_ilike('CSEJ.NRO_EJERCICIO', $nombre);
   			$trans_des = ctr_construir_sentencias::construir_translate_ilike('CSEJ.descripcion', $nombre);
            $where = "($trans_cod OR $trans_des)";
        } else {
            $where = '1=1';
        }

        if (isset($filtro) && !empty($filtro))
        	$where .= ' and '.ctr_construir_sentencias::get_where_filtro($filtro, 'CSEJ');

		$sql = "
			SELECT CSEJ.*
				, CSEJ.NRO_EJERCICIO ||' - '|| CSEJ.DESCRIPCION lov_descripcion
			FROM CS_EJERCICIOS CSEJ
			WHERE $where
		";
		toba::logger()->debug('sql valor dimension '. $sql);
		return toba::db()->consultar($sql);
	}

	public static function get_lov_cs_ejercicios_x_id($id_ejercicio)
	{
		if (isset($id_ejercicio)){
			$sql = "
				SELECT CSEJ.*
					, CSEJ.NRO_EJERCICIO ||' - '|| CSEJ.DESCRIPCION  lov_descripcion
				FROM CS_EJERCICIOS CSEJ
				WHERE CSEJ.id_ejercicio = '$id_ejercicio'
			";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}
	}

	public static function get_cierre_ejercicios($filtro = [])
	{
		$where = "1=1";

		if (!empty($filtro))
        	$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'CSCIEJ', '1=1');

		$sql = "
			SELECT CSCIEJ.*
			FROM CS_CIERRES_EJERCICIOS CSCIEJ
			WHERE $where
		";
		toba::logger()->debug('SQL CIERRE EJERCICIOS '. $sql);
		return toba::db()->consultar($sql);
	}

}
?>