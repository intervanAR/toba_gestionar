<?php

class dao_matriz_contable_impuestos {
	
	public static function get_matriz_contable_impuestos ($filtro = array()){
		$where = " 1=1 ";
		
        if (isset($filtro) && !empty($filtro)) {
			$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'krmci', '1=1');
		}
		
		$sql = "SELECT krmci.*,
				       TO_CHAR (krmci.fecha_vigencia, 'YYYY/MM/DD') fecha_vigencia_format,
				       adi.DESCRIPCION impuesto_descripcion,
				       pkg_cp_cuentas.mascara_aplicar (adcg.nro_cuenta_contable) || ' - '|| adcg.descripcion cc_gasto_format,
				       pkg_cp_cuentas.mascara_aplicar (adcr.nro_cuenta_contable) || ' - '|| adcr.descripcion cc_recurso_format   
				  FROM KR_MATRIZ_CONTAB_ITO krmci JOIN cp_cuentas adcg
				       ON krmci.nro_cuenta_contable_gasto = adcg.nro_cuenta_contable
				       LEFT JOIN cp_cuentas adcr ON krmci.nro_cuenta_contable_recurso = adcr.NRO_CUENTA_CONTABLE
				       LEFT JOIN ad_impuestos adi on adi.cod_impuesto = krmci.cod_impuesto
				 WHERE $where;";
						
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	
}

?>