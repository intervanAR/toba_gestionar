<?php 
	
class dao_tipos_iva {

	static public function get_tipos_iva($filtro = [])
	{
		$where = ' 1=1 ';
		if (isset($filtro))
		{
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'ADTI', '1=1', array('nombre'));
		}
		$sql = "
			SELECT adti.*, DECODE (adti.activo, 'S', 'Si', 'No') activo_format,
			       DECODE (adti.discrimina_imp, 'S', 'Si', 'No') discrimina_imp_format
			  FROM ad_tipos_iva adti
			WHERE
				$where
			ORDER BY
				ADTI.TIPO_IVA ";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

	
}