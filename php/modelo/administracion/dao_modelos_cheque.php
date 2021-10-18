<?php
class dao_modelos_cheque {
	
	static public function get_modelo_cheques ($filtro = array()){
		$where = " 1=1 ";
		
		if (isset($filtro['descripcion'])){
			$where .= " AND ADC.DESCRIPCION LIKE '%".$filtro['descripcion']."%'";
			unset($filtro['descripcion']);
		}
		
		if (!empty($filtro))
			$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro, 'adc', '1=1');
		
		$sql = "SELECT ADC.*
				  FROM AD_MODELOS_CHEQUE ADC
				 WHERE $where 
				 ORDER BY ADC.ID_MODELO DESC";
		return toba::db()->consultar($sql);
	}
	
	static public function get_nombre_reporte ($id_modelo){
		if (!is_null($id_modelo)){
			$sql = "SELECT NOMBRE_REPORTE FROM AD_MODELOS_CHEQUE WHERE ID_MODELO = $id_modelo ;";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['nombre_reporte'];
		}
		return null;
	}
	
}
?>