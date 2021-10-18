<?php

class dao_matriz_contable_gasto {
	
	static public function get_matrices_contables ($filtro = array ())
	{
		$where = " 1=1 ";
		
        if (isset($filtro) && !empty($filtro)) {
			$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'krmcp', '1=1');
		}
		$sql = "SELECT krmcp.*,
				       case 
					      when krmcp.nro_cuenta_contable_d is not null then
					            pkg_cp_cuentas.mascara_aplicar(krmcp.nro_cuenta_contable_d)
					      else ''
					   end as  cuenta_contable_d_format,
				       pkg_cp_cuentas.mascara_aplicar(krmcp.nro_cuenta_contable_h) cuenta_contable_h_format,
				       pkg_cp_cuentas.mascara_aplicar(krmcp.nro_cuenta_contable_perime) cuenta_contable_perime_format,
				       TO_CHAR (krmcp.fecha_vigencia, 'YYYY/MM/DD') fecha_vigencia_format,
				          prtp.cod_tipo_proyecto
				       || ' - '
				       || prtp.descripcion AS tipo_proyecto,
				       prp.cod_partida || ' - ' || prp.descripcion AS partida,
				       (SELECT    cod_partida
				               || ' - '
				               ||  descripcion
				          FROM pr_partidas
				         WHERE cod_partida = krmcp.cod_partida_reimputa) AS partida_reimputa
				  FROM kr_matriz_contab_par krmcp LEFT JOIN pr_tipos_proyectos prtp
				       ON prtp.cod_tipo_proyecto = krmcp.cod_tipo_proyecto
				       LEFT JOIN pr_partidas prp ON prp.cod_partida = krmcp.cod_partida
				  WHERE $where
				  ORDER BY krmcp.cod_partida,krmcp.cod_tipo_proyecto, fecha_vigencia ASC";
						
		$datos = toba::db()->consultar($sql);
		foreach ($datos as $clave => $dato) {
			$datos[$clave]['tipo_proyecto'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($dato['tipo_proyecto'], 'tipo_proyecto_'.$clave, 30, 1, true);
			$datos[$clave]['partida'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($dato['partida'], 'partida_'.$clave, 30, 1, true);
			$datos[$clave]['partida_reimputa'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($dato['partida_reimputa'], 'partida_reimputa_'.$clave, 30, 1, true);
		}
		return $datos;
	}
	
	static public function get_tipos_proyecto (){
    	$sql  = "	SELECT PRTIPR.cod_tipo_proyecto, PRTIPR.cod_tipo_proyecto ||' - '|| descripcion as descripcion
					FROM PR_TIPOS_PROYECTOS PRTIPR
					where (PKG_PR_TIPO_PROYECTOS.ACTIVO(PRTIPR.COD_TIPO_PROYECTO) = 'S')";
    	$datos = toba::db()->consultar($sql);
        return $datos;
    }
	
	
}


?>

