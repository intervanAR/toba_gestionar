<?php
class dao_recaudadores {
	
	static public function get_recaudadores ($filtro = array()){
		$where = "1=1";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'adr', '1=1');
        
		$sql = "SELECT adr.*, CASE
				          WHEN adr.es_banco = 'S'
				             THEN 'Si'
				          ELSE 'No'
				       END es_banco_format,
				       CASE
				          WHEN adr.activo = 'S'
				             THEN 'Si'
				          ELSE 'No'
				       END activo_format,
				       (select id_banco ||' - '|| descripcion 
          				  from kr_bancos where id_banco = adr.id_banco) banco_format
				  FROM ad_recaudadores adr
				 WHERE $where
			  ORDER BY ADR.ID_RECAUDADOR ASC";
		return toba::db()->consultar($sql);	
	}
	
}
?>