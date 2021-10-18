<?php

class dao_tipos_documento {

	static public function get_tipos_documento ($filtro = []){

		$where = '1=1';
		if (isset($filtro))
		{
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'TDOC', '1=1', array('nombre'));
		}

		$sql = "SELECT TDOC.*,
					   decode(tdoc.activo,'S','Si','No') activo_format
				  FROM AD_TIPOS_DOCUMENTO TDOC
				 WHERE $where
		      order by tdoc.cod_tipo_documento ";

		return toba::db()->consultar($sql);
	}
}

?>