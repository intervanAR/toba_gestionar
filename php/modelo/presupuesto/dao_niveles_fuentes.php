<?php	
	/**
	* 
	*/
	class dao_niveles_fuentes
	{
		static public function get_niveles_fuente($filtro = array()) 
		{
			$where = "  1=1 ";
			if (isset($filtro) )
				$where = ctr_construir_sentencias::get_where_filtro($filtro, 'ab', '1=1');
			$sql = "SELECT   prnf.*
			FROM pr_niveles_fuente prnf
			WHERE $where
			ORDER BY prnf.nivel DESC;";
			$datos = toba::db()->consultar($sql);
			return $datos;
		}

	}
	

	?>

