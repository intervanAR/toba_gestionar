<?php  

	class dao_formas_pago {

		static public function get_formas_pago ($filtro = []){
			$where = "1=1";
			
			if (isset($filtro['descripcion']) && !empty($filtro['descripcion']))
			{
				$where .= " and upper(adfp.descripcion) like '%".strtoupper($filtro['descripcion'])."%'";
				unset($filtro['descripcion']);
			}

			$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ADFP', '1=1');

			$sql = "SELECT ADFP.*, CASE
					          WHEN adfp.cuotas = 'S'
					             THEN 'Si'
					          ELSE 'No'
					       	  END cuotas_format
					  FROM AD_FORMAS_PAGO ADFP
					 WHERE $where 
				  ORDER BY ADFP.COD_FORMA_PAGO ";
				
			$datos = toba::db()->consultar($sql);
			return $datos;
		}
	}



?>