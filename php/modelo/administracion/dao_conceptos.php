<?php

class dao_conceptos {

	static public function get_conceptos($filtro = array()) {

    	$desde= null;
		$hasta= null;
		if(isset($filtro['desde'])){
			$desde= $filtro['desde'];
			$hasta= $filtro['hasta'];

			unset($filtro['desde']);
			unset($filtro['hasta']);
		}

		$where = "  1=1 ";
		if (isset($filtro) )
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'ADC', '1=1');

		$sql = "SELECT ADC.*
				FROM AD_CONCEPTOS ADC
				WHERE $where
				ORDER BY COD_CONCEPTO ASC;";
		$sql= dao_varios::paginador($sql, null, $desde, $hasta);
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

	static public function get_lov_cs_conceptso_x_codigo ($cod_concepto){
		if (isset($cod_concepto)){
			$sql = "SELECT CSCO.*, CSCO.COD_CONCEPTO ||' - '|| CSCO.DESCRIPCION LOV_DESCRIPCION
					FROM CS_CONCEPTOS CSCO
					WHERE CSCO.COD_CONCEPTO = $cod_concepto ";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}else return null;
	}

	static public function get_lov_cs_conceptos_x_nombre ($nombre, $filtro = array()){
		if (isset($nombre)) {
            $cod_comp = ctr_construir_sentencias::construir_translate_ilike('CSCO.COD_CONCEPTO', $nombre);
            $trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('CSCO.DESCRIPCION', $nombre);
            $where = "($cod_comp OR $trans_descricpcion)";
        } else {
            $where = "1=1";
        }

        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'CSCO', '1=1');

        $sql = "SELECT CSCO.*, CSCO.COD_CONCEPTO ||' - '|| CSCO.DESCRIPCION LOV_DESCRIPCION
				FROM CS_CONCEPTOS CSCO
				WHERE $where
                ORDER BY lov_descripcion;";
        $datos = toba::db()->consultar($sql);
        return $datos;
	}

	static public function get_lov_impuesto_x_codigo ($cod_impuesto){
		if (isset($cod_impuesto)){
			$sql = "SELECT ADIM.*, ADIM.COD_IMPUESTO ||' - '|| ADIM.DESCRIPCION LOV_DESCRIPCION
					FROM AD_IMPUESTOS ADIM
					WHERE ADIM.COD_IMPUESTO = '$cod_impuesto'";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}else return null;
	}

	static public function get_lov_impuesto_x_nombre ($nombre, $filtro = array()){
		if (isset($nombre)) {
            $cod_comp = ctr_construir_sentencias::construir_translate_ilike('ADIM.COD_IMPUESTO', $nombre);
            $trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('ADIM.DESCRIPCION', $nombre);
            $where = "($cod_comp OR $trans_descricpcion)";
        } else {
            $where = "1=1";
        }

        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'ADIM', '1=1');

        $sql = "SELECT ADIM.*, ADIM.COD_IMPUESTO ||' - '|| ADIM.DESCRIPCION LOV_DESCRIPCION
				FROM AD_IMPUESTOS ADIM
				WHERE $where
                ORDER BY lov_descripcion;";
        $datos = toba::db()->consultar($sql);
        return $datos;
	}

	static public function get_lov_ad_conceptso_x_codigo ($cod_concepto){
		if (isset($cod_concepto)){
			$sql = "SELECT adco.*, adco.COD_CONCEPTO ||' - '|| adco.DESCRIPCION LOV_DESCRIPCION
					FROM ad_conceptos adco
					WHERE adco.COD_CONCEPTO = $cod_concepto ";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}else return null;
	}

	static public function get_lov_ad_conceptos_x_nombre ($nombre, $filtro = array()){
		if (isset($nombre)) {
            $cod_comp = ctr_construir_sentencias::construir_translate_ilike('adco.COD_CONCEPTO', $nombre);
            $trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('adco.DESCRIPCION', $nombre);
            $where = "($cod_comp OR $trans_descricpcion)";
        } else {
            $where = "1=1";
        }

        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'adco', '1=1');

        $sql = "SELECT adco.*, adco.COD_CONCEPTO ||' - '|| adco.DESCRIPCION LOV_DESCRIPCION
				FROM ad_CONCEPTOS adco
				WHERE $where
                ORDER BY lov_descripcion;";
        $datos = toba::db()->consultar($sql);
        return $datos;
	}

	static public function crea_concepto ($cod_concepto, $descripcion, $cod_partida, $activo, $con_transaccion = true){
		try {
			if (is_null($descripcion)||empty($descripcion)){
				$descripcion = '-';
			}
			if (is_null($cod_partida)||empty($cod_partida)){
				$cod_partida = '';
			}
             $sql = "BEGIN :resultado := pkg_catalogo.crea_concepto(:cod_concepto, :descripcion, :cod_partida, :activo);END;";
             $parametros = array(array( 'nombre' => 'cod_concepto',
				                        'tipo_dato' => PDO::PARAM_STR,
				                        'longitud' => 32,
				                        'valor' => $cod_concepto),
             					 array( 'nombre' => 'descripcion',
				                        'tipo_dato' => PDO::PARAM_STR,
				                        'longitud' => 400,
				                        'valor' => $descripcion),
            					 array( 'nombre' => 'cod_partida',
				                        'tipo_dato' => PDO::PARAM_STR,
				                        'longitud' => 15,
				                        'valor' => $cod_partida),
            					 array( 'nombre' => 'activo',
				                        'tipo_dato' => PDO::PARAM_STR,
				                        'longitud' => 1,
				                        'valor' => $activo),
				                  array('nombre' => 'resultado',
				                        'tipo_dato' => PDO::PARAM_STR,
				                        'longitud' => 4000,
				                        'valor' => ''));
                if ($con_transaccion)
                	toba::db()->abrir_transaccion();

                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

                if ($con_transaccion){
                	if ($resultado[4]['valor'] <> 'OK'){
			 			toba::db()->abortar_transaccion();
			 			return $resultado[4]['valor'];
	                }else{
    	            	toba::db()->cerrar_transaccion();
			 			return $resultado[4]['valor'];
        	        }
                }
                return $resultado[4]['valor'];
			} catch (toba_error_db $e_db) {
				if ($con_transaccion) {
					toba::db()->abortar_transaccion();
				}
				toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
				toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
			} catch (toba_error $e) {
				if ($con_transaccion) {
					toba::db()->abortar_transaccion();
				}
				toba::notificacion()->error('Error '.$e->get_mensaje());
				toba::logger()->error('Error '.$e->get_mensaje());
			}
	}

	static public function existe_concepto ($cod_articulo){
		$sql = "SELECT pkg_catalogo.existe_concepto($cod_articulo) existe from dual;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['existe'];
	}

	static public function borrar_concepto ($cod_concepto){
		try {

			$sql = "DELETE FROM AD_CONCEPTOS WHERE COD_CONCEPTO = $cod_concepto";
			$resultado = toba::db()->ejecutar($sql);

		} catch (toba_error_db $e_db) {
			toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
			toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
		} catch (toba_error $e) {
			toba::notificacion()->error('Error '.$e->get_mensaje());
			toba::logger()->error('Error '.$e->get_mensaje());
		}
	}

	public static function get_lov_cs_agrupamientos_x_codigo ($cod_agrupamiento)
	{
		if (isset($cod_agrupamiento)){
			$sql = "
				SELECT CSAG.*, CSAG.cod_agrupamiento ||' - '|| CSAG.DESCRIPCION LOV_DESCRIPCION
				FROM CS_AGRUPAMIENTOS CSAG
				WHERE CSAG.cod_agrupamiento = $cod_agrupamiento ";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}else return null;
	}

	public static function get_lov_cs_agrupamientos_x_nombre ($nombre, $filtro = [])
	{
		if (isset($nombre)) {
            $cod_comp = ctr_construir_sentencias::construir_translate_ilike('CSAG.cod_agrupamiento', $nombre);
            $trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('CSAG.DESCRIPCION', $nombre);
            $where = "($cod_comp OR $trans_descricpcion)";
        } else {
            $where = "1=1";
        }

        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'CSAG', '1=1');

        $sql = "
        	SELECT CSAG.*, CSAG.cod_agrupamiento ||' - '|| CSAG.DESCRIPCION LOV_DESCRIPCION
			FROM CS_AGRUPAMIENTOS CSAG
			WHERE $where
            ORDER BY lov_descripcion
        ";
        $datos = toba::db()->consultar($sql);
        return $datos;
	}
}