<?php

class dao_reportes_general
{

	public static function get_reportes($filtro = [])
	{
		$where = "1=1";
		
		if (isset($filtro['reporte']) && !empty($filtro['reporte'])){
			$where .= " and upper(r.reporte) like '%".strtoupper($filtro['reporte'])."%'";
			unset($filtro['valor']);
		}
		if (isset($filtro['descripcion']) && !empty($filtro['descripcion'])){
			$where .= " and upper(r.descripcion) like '%".strtoupper($filtro['descripcion'])."%'";
			unset($filtro['descripcion']);
		}
		if (isset($filtro['titulo']) && !empty($filtro['titulo'])){
			$where .= " and upper(r.titulo) like '%".strtoupper($filtro['titulo'])."%'";
			unset($filtro['titulo']);
		}
		if (isset($filtro['subtitulo']) && !empty($filtro['subtitulo'])){
			$where .= " and upper(r.subtitulo) like '%".strtoupper($filtro['subtitulo'])."%'";
			unset($filtro['subtitulo']);
		}
		
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'r', '1=1');

		$sql = "SELECT r.*
				  FROM KR_REPORTES r
				 WHERE $where 
			  ORDER BY r.reporte ";
			
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

	public static function get_reporte($reporte)
	{
		$reporte = quote($reporte);
		$sql = "
			SELECT
				reporte,
				descripcion,
				titulo,
				titulo AS p_titulo,
				subtitulo,
				subtitulo AS p_subtitulo,
				DECODE(
					habilitado, NULL, 'NNNNNNNNNN', RPAD (habilitado, 10, 'N')
				) habilitado,
				DECODE(
					obligatorio, NULL, 'NNNNNNNNNN', RPAD (obligatorio, 10, 'N')
				) obligatorio
			FROM KR_REPORTES
			WHERE REPORTE = $reporte
		";

		return toba::db()->consultar_fila($sql);
	}

    public static function obtener_titulo_subtitulo($nombre_reporte)
    {
        $sql =
            "BEGIN PKG_KR_GENERAL.obtener_titulo_subtitulo('$nombre_reporte', :p_titulo, :p_subtitulo); END;";
        $parametros = [[
            'nombre' => 'p_titulo',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 400,
            'valor' => '',
        ], [
            'nombre' => 'p_subtitulo',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 400,
            'valor' => '',
        ]];
        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

		return [
			'p_titulo' => $resultado[0]['valor'],
			'p_subtitulo' => $resultado[1]['valor'],
		];
	}

	public static function get_nombre_municipio()
	{
		$sql = 'SELECT PKG_KR_CONFIGURACION.nombre_municipio p_municipio FROM DUAL;';
		$datos = toba::db()->consultar_fila($sql);

		return $datos['p_municipio'];
	}
}
