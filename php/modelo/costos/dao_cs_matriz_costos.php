<?php
class dao_cs_matriz_costos {

	public static function get_matrices ($filtro = [])
	{
		$where = "1=1";

		if (!empty($filtro)){

			/*if (isset($filtro['descripcion'])) {
	            $descripcion = ctr_construir_sentencias::construir_translate_ilike("csmaco.descripcion", $filtro['descripcion']);
	            $where.= " AND ($descripcion) ";
	            unset($filtro['descripcion']);
	        }*/

        	$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'csmaco', '1=1');
		}

		$sql = "
			SELECT  csmaco.*
			    , (select csvadi.cod_dimension || ' - ' || csvadi.descripcion
			        from cs_valores_dimensiones csvadi  where csmaco.id_valor_dimension = csvadi.id_valor_dimension) csvadi_desc
			    , (select csvadi2.cod_dimension || ' - ' || csvadi2.descripcion
			        from cs_valores_dimensiones csvadi2  where csmaco.id_valor_dimension_d2 = csvadi2.id_valor_dimension) csvadi2_desc
			    , (select csvadi3.cod_dimension || ' - ' || csvadi3.descripcion
			        from cs_valores_dimensiones csvadi3  where csmaco.id_valor_dimension_d3 = csvadi3.id_valor_dimension) csvadi3_desc
			    , (select csvadi4.cod_dimension || ' - ' || csvadi4.descripcion
			        from cs_valores_dimensiones csvadi4  where csmaco.id_valor_dimension_d4 = csvadi4.id_valor_dimension) csvadi4_desc
			    , (select pkg_pr_programas.mascara_aplicar(prpr.cod_programa)|| ' - ' ||prpr.descripcion
			        from pr_programas prpr where csmaco.id_programa = prpr.id_programa) programa_desc
			    , (select pkg_pr_entidades.mascara_aplicar(pren.cod_entidad)|| ' - ' ||pren.descripcion
			        from pr_entidades pren where csmaco.id_entidad = pren.id_entidad) entidad_desc
			    , (select pkg_pr_fuentes.mascara_aplicar(prfufi.cod_fuente_financiera)|| ' - ' ||prfufi.descripcion
			        from pr_fuentes_financieras prfufi where csmaco.cod_fuente_financiera = prfufi.cod_fuente_financiera) fuente_desc
			    , (select pkg_pr_recursos.mascara_aplicar(prre.cod_recurso) ||' - '|| prre.descripcion
			        from pr_recursos prre where csmaco.cod_recurso = prre.cod_recurso) recurso_desc
			    , (select krunad.cod_unidad_administracion|| ' - ' ||krunad.descripcion
			        from KR_UNIDADES_ADMINISTRACION krunad where csmaco.cod_unidad_administracion = krunad.cod_unidad_administracion) ua_desc
			FROM cs_matriz_costos csmaco
			WHERE $where
		";
		toba::logger()->debug('SQL MATRIZ COSTOS '. $sql);
		return toba::db()->consultar($sql);
	}

	public static function get_nuevo_id_matriz()
    {
        $sql = "
            SELECT nvl(max(nro_entrada)+1,1) nro_entrada
            FROM CS_MATRIZ_COSTOS
        ";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['nro_entrada'];

    }

}
?>