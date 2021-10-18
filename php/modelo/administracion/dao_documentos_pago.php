<?php
	
class dao_documentos_pago {

	static public function get_clase_documentos_pago ($filtro = []){
		$where= "1=1";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'adcd', '1=1');

		$sql = "SELECT adcd.*, krmo.descripcion moneda,
				       DECODE (adcd.activo, 'S', 'Si', 'No') activo_format
				  FROM ad_clases_documento adcd, kr_monedas krmo
				 WHERE adcd.cod_moneda = krmo.cod_moneda and $where 
				 order by adcd.cod_clase_documento desc";
		return toba::db()->consultar($sql);
	}
}

?>