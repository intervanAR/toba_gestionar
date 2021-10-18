<?php
class dao_matriz_contable_recurso {
	static public function get_matrices_contables_recurso ($filtro = array ())
	{
		$where = " 1=1 ";
		
        if (isset($filtro) && !empty($filtro)) {
			$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'KRMCR', '1=1');
		}
		$sql = "SELECT KRMCR.*,
				       pkg_pr_recursos.MASCARA_APLICAR(KRMCR.cod_recurso)||' - '|| PRR.DESCRIPCION  recurso_format,
				       CASE
				           WHEN KRMCR.cod_recurso_reimputa IS NOT NULL
				              THEN pkg_pr_recursos.MASCARA_APLICAR(cod_recurso_reimputa) ||' - '|| (SELECT DESCRIPCION FROM PR_RECURSOS WHERE COD_RECURSO = KRMCR.COD_RECURSO_REIMPUTA)
				           ELSE ''
				       END AS recurso_reimputa_format,
				       TO_CHAR (krmcr.fecha_vigencia, 'YYYY/MM/DD') fecha_vigencia_format,
				       pkg_cp_cuentas.mascara_aplicar(KRMCR.nro_cuenta_contable_d) cuenta_contable_d_format,
				       CASE
				           WHEN KRMCR.nro_cuenta_contable_H IS NOT NULL
				              THEN pkg_cp_cuentas.mascara_aplicar(KRMCR.nro_cuenta_contable_h)
				           ELSE ''
				       END AS cuenta_contable_h_format
				FROM KR_MATRIZ_CONTAB_REC KRMCR
				     LEFT JOIN PR_RECURSOS PRR ON PRR.COD_RECURSO = KRMCR.COD_RECURSO
				WHERE $where
				ORDER BY KRMCR.cod_recurso, fecha_vigencia asc;";
						
		$datos = toba::db()->consultar($sql);
		
		foreach ($datos as $clave => $dato) {
			$datos[$clave]['recurso_format'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($dato['recurso_format'], 'recurso_format_'.$clave, 50, 1, true);
			$datos[$clave]['recurso_reimputa_format'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($dato['recurso_reimputa_format'], 'recurso_reimputa_format_'.$clave, 50, 1, true);
		}
		return $datos;
	}
	
	static function para_reimptuar ($cod_recurso){
		if (isset($cod_recurso) && !empty($cod_recurso)){
			$sql = "SELECT PKG_PR_RECURSOS.PARA_REIMPUTAR(".quote($cod_recurso).") AS para_reimputar FROM DUAL;";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['para_reimputar'];	
		}else{
			return '';
		}
	}
}
?>