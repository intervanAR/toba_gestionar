<?php
class dao_compras {
	
	static public function get_compras ($filtro = array ()){
		$where = " 1=1 ";
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'CC', ' 1=1 '); 
		$sql = "SELECT CC.*, KE.NRO_EXPEDIENTE,  ke.id_expediente
				FROM CO_COMPRAS CC 
					 LEFT JOIN  KR_EXPEDIENTES KE ON CC.ID_EXPEDIENTE = KE.ID_EXPEDIENTE
				WHERE $where
				ORDER BY NRO_COMPRA;";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	
}