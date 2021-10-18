<?php
class dao_cs_movimientos {

	public static function get_movimientos($filtro = [], $orden = [])
	{
		$where = "1=1";
		$desde = null;
		$hasta = null;

		if (!empty($filtro)){

			if(isset($filtro['numrow_desde'])){
				$desde = $filtro['numrow_desde'];
				$hasta = $filtro['numrow_hasta'];
				unset($filtro['numrow_desde']);
				unset($filtro['numrow_hasta']);
			}

			if (isset($filtro['usuario'])) {
	            $usuario = ctr_construir_sentencias::construir_translate_ilike("m.usuario", $filtro['usuario']);
	            $where.= " AND ($usuario) ";
	            unset($filtro['usuario']);
	        }

	        if (isset($filtro['expediente'])) {
	            $expediente = ctr_construir_sentencias::construir_translate_ilike("m.expediente", $filtro['expediente']);
	            $where.= " AND ($expediente) ";
	            unset($filtro['expediente']);
	        }

	        if (isset($filtro['proveedor'])) {
	            $proveedor = ctr_construir_sentencias::construir_translate_ilike("m.proveedor", $filtro['proveedor']);
	            $where.= " AND ($proveedor) ";
	            unset($filtro['proveedor']);
	        }

        	$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'm', '1=1');
        }

		$sql = "
			SELECT m.*
			, d1.cod_dimension || ' - ' || d1.descripcion interno_desc
			, d2.cod_dimension || ' - ' || d2.descripcion destino_desc
			, csco.cod_concepto || ' - ' || csco.descripcion concepto_desc
			FROM cs_movimientos m
			JOIN cs_valores_dimensiones d1
			       ON (m.id_valor_dimension_d1 = d1.id_valor_dimension)
			LEFT JOIN cs_valores_dimensiones d2
			       ON (m.id_valor_dimension_d2 = d2.id_valor_dimension)
			JOIN cs_conceptos csco on (m.cod_concepto = csco.cod_concepto)
			WHERE $where
			ORDER BY id_movimiento
		";

		$sql = dao_varios::paginador($sql, null, $desde, $hasta, null, $orden);
		toba::logger()->debug('SQL MOVIMIEMTOS paginado '. $sql);
		return toba::db()->consultar($sql);
	}

}
?>