<?php

class dao_tipos_novedad {

	static public function get_tipos_novedad ($filtro = []){

		$where = '1=1';
		if (isset($filtro))
		{
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'tnov', '1=1', array('nombre'));
		}

		$sql = "SELECT tnov.*,
					   decode(tnov.bloquea_pago,'S','Si','No') bloquea_pago_format,
					   decode(tnov.avisa_pago,'S','Si','No') avisa_pago_format
				  FROM AD_TIPOS_NOVEDAD tnov
				 WHERE $where
		      order by tnov.tipo_novedad ";

		return toba::db()->consultar($sql);
	}
}

?>