<?php
class dao_depositos_st_compras {
	
	static public function get_depositos ($filtro = array()){
		$where = " 1=1 ";
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'STDEP', ' 1=1 '); 
		$sql = "SELECT STDEP.*
				FROM ST_DEPOSITOS STDEP
				WHERE $where
				ORDER BY STDEP.COD_DEPOSITO ";
		return toba::db()->consultar($sql);
	}

	static public function get_deposito ($cod_deposito){
		$sql = "SELECT STDEP.*, cos.cod_ambito
				FROM ST_DEPOSITOS STDEP, CO_SECTORES COS
				WHERE STDEP.cod_sector = cos.cod_sector AND STDEP.cod_deposito = ".quote($cod_deposito);
		return toba::db()->consultar_fila($sql);
	}
	
	
	static public function get_lov_deposito_x_nombre ($nombre, $filtro = array()){
		$where = " 1=1 ";
		if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('STDEP.cod_deposito', $nombre);
            $trans_des = ctr_construir_sentencias::construir_translate_ilike('STDEP.descripcion', $nombre);
            $where .= " AND ($trans_cod OR $trans_des)";
        }
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'STDEP', ' 1=1 '); 
		$sql = "SELECT STDEP.*, 
					   STDEP.COD_DEPOSITO ||' - '|| STDEP.DESCRIPCION AS LOV_DESCRIPCION
				FROM ST_DEPOSITOS STDEP
				WHERE $where 
				ORDER BY LOV_DESCRIPCION";
		return toba::db()->consultar($sql);
	}
	static public function get_lov_depositos_x_codigo ($cod_deposito){
		$sql = "SELECT STDEP.COD_DEPOSITO ||' - '|| STDEP.DESCRIPCION AS LOV_DESCRIPCION
				FROM ST_DEPOSITOS STDEP
				WHERE STDEP.COD_DEPOSITO = $cod_deposito ";
		
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}
	
}

?>