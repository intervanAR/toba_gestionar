<?php

class dao_limites_compra {

	static public function get_limites_compra ($filtro = [])
	{
		$where = " 1=1 ";
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'colc', ' 1=1 '); 
		$sql = "SELECT colc.*,
					   TO_CHAR (colc.monto_maximo,
				                '99G999G999G999G990D00'
				               ) monto_maximo_format,
				       TO_CHAR (colc.valor_sellado_oferta,
				                '99G999G999G999G990D00'
				               ) valor_sellado_oferta_format,
				       TO_CHAR (colc.porc_garantia_contrato,
				                '9G999G990D9999'
				               ) porc_garantia_contrato_format,
				       TO_CHAR (colc.porc_garantia_oferta,
				                '9G999G990D9999'
				               ) porc_garantia_oferta_format,
				       TO_CHAR (colc.porc_sellado_orden,
				                '9G999G990D9999'
				               ) porc_sellado_orden_format,
				       (SELECT rv_meaning
				          FROM cg_ref_codes
				         WHERE rv_domain = 'CO_TIPO_COMPRA'
				           AND rv_low_value = colc.tipo_compra) tipo_compra_format,
				       (SELECT rv_meaning
				          FROM cg_ref_codes
				         WHERE rv_domain = 'CO_DESTINO_COMPRA'
				           AND rv_low_value = colc.destino_compra) destino_compra_format
  				  FROM co_limites_compra colc 
  				 WHERE $where ";
		return toba::db()->consultar($sql);
	}
}

?>