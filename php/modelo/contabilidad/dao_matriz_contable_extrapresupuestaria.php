<?php
class dao_matriz_contable_extrapresupuestaria {
	
	static public function get_matrices_contables_extrapresupuestarias ($filtro = array ())
	{
		$where = " 1=1 ";
		
        if (isset($filtro) && !empty($filtro)) {
			$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'KRME', '1=1');
		}
		$sql = "SELECT KRME.*,
				       pkg_pr_auxiliares.mascara_aplicar(KRME.cod_auxiliar) ||' - '|| KRAUX.DESCRIPCION cod_auxiliar_format,
				       pkg_cp_cuentas.mascara_aplicar(KRME.nro_cuenta_contable) ||' - '|| CPC.DESCRIPCION nro_cuenta_contable_format,
				       TO_CHAR (KRME.fecha_vigencia, 'YYYY/MM/DD') fecha_vigencia_format
				FROM KR_MATRIZ_CONTAB_EXT KRME
				     LEFT JOIN KR_AUXILIARES_EXT KRAUX ON KRAUX.COD_AUXILIAR = KRME.COD_AUXILIAR
				     LEFT JOIN CP_CUENTAS CPC ON CPC.NRO_CUENTA_CONTABLE = KRME.NRO_CUENTA_CONTABLE
				 WHERE $where
				 ORDER BY KRME.COD_AUXILIAR, fecha_vigencia asc;";
						
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	
	
}


?>