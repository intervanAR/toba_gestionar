<?php
class dao_dimension {

	static public function get_descripcion ($id_dimension){
		$sql = "select PKG_DIMENSION.DESCRIPCION($id_dimension) descripcion from dual";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['descripcion'];
	}

	static public function get_descripcion_x_parametro ($parametro){
		$sql_dimension = " select PKG_KR_GENERAL.VALOR_PARAMETRO('$parametro') id_dimension from dual";
		$id_dimension = toba::db()->consultar_fila($sql_dimension)['id_dimension'];
		$sql = "select PKG_DIMENSION.DESCRIPCION($id_dimension) descripcion from dual";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['descripcion'];
	}

	static public function get_lov_dimension_x_nombre($nombre, $filtro = array()){
		if (isset($nombre)) {
   			$trans_cod = ctr_construir_sentencias::construir_translate_ilike('csvadi.cod_dimension', $nombre);
   			$trans_des = ctr_construir_sentencias::construir_translate_ilike('csvadi.descripcion', $nombre);
            $where = "($trans_cod OR $trans_des)";
        } else {
            $where = '1=1';
        }

        if (isset($filtro['por_nivel']) && isset($filtro['id_dimension'])){
        	$where .= " AND csvadi.ID_NIVEL IN (SELECT MAX (ID_NIVEL)
				                                           FROM CS_VALORES_DIMENSIONES
				                                          WHERE ID_DIMENSION = ".$filtro['id_dimension'].")";
        	unset($filtro['por_nivel']);
        }

        if (isset($filtro['dimension_interno'])){
        	$where .= " and csvadi.id_dimension = PKG_KR_GENERAL.VALOR_PARAMETRO('DIMENSION_INTERNO') ";
        	unset($filtro['dimension_interno']);
        }

        if (isset($filtro['valor_parametro'])){
        	$where .= " and csvadi.id_dimension = PKG_KR_GENERAL.VALOR_PARAMETRO('".$filtro['valor_parametro']."') ";
        	unset($filtro['valor_parametro']);
        }

        if (isset($filtro['no_exist'])){
        	$where .= " AND not exists ( select 1 FROM CS_VALORES_DIMENSIONES
                                          WHERE ID_DIMENSION =  csvadi.id_dimension  and
                           id_valor_dimension_padre=csvadi.id_valor_dimension) ";
        	unset($filtro['no_exist']);
        }

        if (isset($filtro['nro_nivel'])
        	&& isset($filtro['id_dimension']))
        {
        	$where .= " AND csvadi.ID_NIVEL in (
        		SELECT ID_NIVEL
	           	FROM cs_niveles_dimension
	          	WHERE
	          		csdi_id_dimension = ".$filtro['id_dimension']."
                 	AND nro_nivel = ".$filtro['nro_nivel']."
	        )";
        	unset($filtro['nro_nivel']);
        }

        if (isset($filtro['id_valor_dimension_padre'])) {
        	$where.= "
        		AND nvl(id_valor_dimension_padre, -1) = nvl('".$filtro['id_valor_dimension_padre']."',-1)
        	";
        	unset($filtro['id_valor_dimension_padre']);
        }

        $where .= ' and '.ctr_construir_sentencias::get_where_filtro($filtro, 'csvadi');


		$sql = "SELECT csvadi.*, csvadi.cod_dimension ||' - '|| csvadi.descripcion lov_descripcion
				  FROM CS_VALORES_DIMENSIONES csvadi
				 WHERE $where
			  ORDER BY csvadi.cod_dimension asc ";
		toba::logger()->debug('sql valor dimension '. $sql);
		return toba::db()->consultar($sql);
	}

    static public function get_lov_dimension_x_nombre2($nombre, $filtro = array()){
        if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('csvadi.cod_dimension', $nombre);
            $trans_des = ctr_construir_sentencias::construir_translate_ilike('csvadi.descripcion', $nombre);
            $where = "($trans_cod OR $trans_des)";
        } else {
            $where = '1=1';
        }

        if (isset($filtro['por_nivel']) && isset($filtro['id_dimension'])){
            $where .= " AND csvadi.ID_NIVEL IN (SELECT MAX (ID_NIVEL)
                                                           FROM CS_VALORES_DIMENSIONES
                                                          WHERE ID_DIMENSION = ".$filtro['id_dimension'].")";
            unset($filtro['por_nivel']);
        }

        if (isset($filtro['dimension_interno'])){
            $where .= " and csvadi.id_dimension = PKG_KR_GENERAL.VALOR_PARAMETRO('DIMENSION_INTERNO') ";
            unset($filtro['dimension_interno']);
        }

        if (isset($filtro['valor_parametro'])){
            $where .= " and csvadi.id_dimension = PKG_KR_GENERAL.VALOR_PARAMETRO('".$filtro['valor_parametro']."') ";
            unset($filtro['valor_parametro']);
        }

        if (isset($filtro['no_exist'])){
            $where .= " AND not exists ( select 1 FROM CS_VALORES_DIMENSIONES
                                          WHERE ID_DIMENSION =  csvadi.id_dimension  and
                           id_valor_dimension_padre=csvadi.id_valor_dimension) ";
            unset($filtro['no_exist']);
        }

        if (isset($filtro['nro_nivel'])
            && isset($filtro['id_dimension']))
        {
            
            unset($filtro['nro_nivel']);
        }

        if (isset($filtro['id_valor_dimension_padre'])) {
            $where.= "
                AND nvl(id_valor_dimension_padre, -1) = nvl('".$filtro['id_valor_dimension_padre']."',-1)
            ";
            unset($filtro['id_valor_dimension_padre']);
        }

        $where .= ' and '.ctr_construir_sentencias::get_where_filtro($filtro, 'csvadi');


        $sql = "SELECT csvadi.*, csvadi.cod_dimension ||' - '|| csvadi.descripcion lov_descripcion
                  FROM CS_VALORES_DIMENSIONES csvadi
                 WHERE $where
              ORDER BY csvadi.cod_dimension asc ";
        toba::logger()->debug('sql valor dimension '. $sql);
        return toba::db()->consultar($sql);
    }

	static public function get_lov_dimension_x_codigo($cod_dimension){
		$sql = "SELECT csvadi.*, csvadi.descripcion lov_descripcion
				  FROM CS_VALORES_DIMENSIONES csvadi
				 WHERE csvadi.cod_dimension = ".quote($cod_dimension);
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}

	static public function get_lov_dimension_x_id_valor_dimension($id_valor_dimension){
		$sql = "SELECT csvadi.*, csvadi.descripcion lov_descripcion
				  FROM CS_VALORES_DIMENSIONES csvadi
				 WHERE csvadi.id_valor_dimension = ".quote($id_valor_dimension);
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}

	static public function get_dimension_abbrev ($dominio, $abbrev){
		$sql = "select initcap(Descripcion) descripcion
			      from cs_dimensiones
			     where id_dimension = pkg_general.abbrev_dominio('$dominio','$abbrev');";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['descripcion'];
	}

	public static function get_nuevo_id_dimension()
    {
        $sql = "
            SELECT nvl(max(id_dimension)+1,1) id_dimension
            FROM CS_DIMENSIONES
        ";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['id_dimension'];
    }

	static public function get_niveles_dimension($filtro = [])
	{
		$where = " 1=1 ";

        if (isset($filtro['no_exist'])){
        	$where .= " AND not exists
        		( select 1 FROM cs_niveles_dimension
	                 WHERE CSDI_ID_DIMENSION = a.CSDI_ID_DIMENSION
	                 and nro_nivel > a.nro_nivel
           		) ";
        	unset($filtro['no_exist']);
        }

        if (!empty($filtro))
        	$where .= ' and '.ctr_construir_sentencias::get_where_filtro($filtro, 'a');

		$sql = "
			SELECT a.*
			FROM cs_niveles_dimension a
			WHERE $where
		";
		toba::logger()->Debug('SQL NIVELES DIMENSION '. $sql);
		$datos = principal_ei_tabulator_consultar::todos_los_datos($sql);
		return $datos;
	}

	public static function get_nuevo_id_nivel_dimension()
    {
        $sql = "
            SELECT nvl(max(id_nivel)+1,1) id_nivel
            FROM CS_NIVELES_DIMENSION
        ";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['id_nivel'];

    }

	public static function get_lov_cs_dimension_x_nombre($nombre, $filtro = [])
	{
		if (isset($nombre)) {
   			$trans_cod = ctr_construir_sentencias::construir_translate_ilike('CSDI.id_dimension', $nombre);
   			$trans_des = ctr_construir_sentencias::construir_translate_ilike('CSDI.descripcion', $nombre);
            $where = "($trans_cod OR $trans_des)";
        } else {
            $where = '1=1';
        }

        if (isset($filtro) && !empty($filtro))
        	$where .= ' and '.ctr_construir_sentencias::get_where_filtro($filtro, 'CSDI');

		$sql = "
			SELECT CSDI.*,
				CSDI.id_dimension ||' - '|| CSDI.descripcion lov_descripcion
			FROM CS_DIMENSIONES CSDI
			WHERE $where
			ORDER BY CSDI.id_dimension
		";
		toba::logger()->debug('sql valor dimension '. $sql);
		return toba::db()->consultar($sql);
	}

	public static function get_lov_cs_dimension_x_id($id_dimension)
	{
		if (isset($id_dimension)){
			$sql = "
				SELECT CSDI.*
				, CSDI.id_dimension ||' - '|| CSDI.descripcion lov_descripcion
				FROM CS_DIMENSIONES CSDI
				WHERE CSDI.id_dimension = '$id_dimension'
			";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}
	}

	public static function get_cs_dimensiones($filtro = [])
	{
		$where = "1=1";

		if (!empty($filtro)){

			if (isset($filtro['descripcion'])) {
	            $descripcion = ctr_construir_sentencias::construir_translate_ilike("csdi.descripcion", $filtro['descripcion']);
	            $where.= " AND ($descripcion) ";
	            unset($filtro['descripcion']);
	        }

        	$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'csdi', '1=1');
		}

		$sql = "
			SELECT csdi.*
			FROM cs_dimensiones csdi
			WHERE $where
		";
		toba::logger()->debug('SQL DIMENSIONES '. $sql);
		return toba::db()->consultar($sql);
	}

	public static function get_rrhh_hereda($id_valor_dimension){
		$sql = "select PKG_DIMENSION.RRHH('$id_valor_dimension') valor from dual";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['valor'];
	}

    public static function hereda_codigo($id_dimension)
    {
        $sql = "
            SELECT HEREDA_CODIGO_NIVEL_ANT l_hereda
            FROM CS_DIMENSIONES
            WHERE ID_DIMENSION = $id_dimension;
        ";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['l_hereda'];
    }

	/*public static function crear_valor_dimension($codigo_dim, $descripcion, $fecha_act, $fecha_desct, $id_valor_dimension_padre, $id_nivel, $id_dimension, $valor_dimension, $figurativo, $rrhh)
	{

		$codigo_dim = quote($codigo_dim);
        $descripcion = quote(strtoupper($descripcion));
        $fecha_act = quote($fecha_act);
        $fecha_desct = !empty($fecha_desct) ? quote($fecha_desct) : '';

        $id_valor_dimension_padre = !empty($id_valor_dimension_padre) ? quote($id_valor_dimension_padre) : '';
        $id_nivel = quote($id_nivel);
        $id_dimension = quote($id_dimension);
        $figurativo = quote($figurativo);
        $rrhh = quote($rrhh);

        $mensaje_error = 'Error al Crear el Nodo.';

        $sql = "
            BEGIN
                :resultado := pkg_dimension.crear_valor_dimension(
                    $codigo_dim,
                    $descripcion,
                    to_date($fecha_act, 'YYYY-MM-DD'),
                    null,
                    '$id_valor_dimension_padre',
                    $id_nivel,
                    $id_dimension,
                    $figurativo,
                    $rrhh
                );
            END;
        ";

        $parametros = [[
            'nombre' => 'resultado',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 400,
            'valor' => '',
        ]];

        $res = ctr_procedimientos::ejecutar_procedimiento(
            $mensaje_error,
            $sql,
            $parametros
        );

        return pos($res)['valor'];
   }*/

    public static function crear_valor_dimension ($codigo_dim, $descripcion, $fecha_act, $fecha_desct, $id_valor_dimension_padre, $id_nivel, $id_dimension, $valor_dimension, $figurativo, $rrhh, $con_transaccion = false) {

        try {

            if ( !empty($codigo_dim) && !empty($descripcion) && !empty($fecha_act) ) {

                if ($con_transaccion) {
                    toba::db()->abrir_transaccion();
                }

		        $id_valor_dimension_padre = !empty($id_valor_dimension_padre) ? $id_valor_dimension_padre : '';

                 $sql = " BEGIN :resultado := pkg_dimension.crear_valor_dimension(
                 	:codigo_dim
                 	,:descripcion
                 	,to_date(:fecha_act,'YYYY-MM-DD')
                 	,:fecha_desct
                 	,:id_valor_dimension_padre
                 	,:id_nivel
                 	,:id_dimension
                 	,:valor_dimension
                 	,:figurativo
                 	,:rrhh
                 ); END; ";

                $sql = ctr_procedimientos::sanitizar_consulta($sql);
                $parametros = array(
                    array('nombre' => 'codigo_dim',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 50,
                        'valor' => $codigo_dim),
                    array('nombre' => 'descripcion',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 200,
                        'valor' => $descripcion),
                    array('nombre' => 'fecha_act',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $fecha_act),
                    array('nombre' => 'fecha_desct',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => ''),
                    array('nombre' => 'id_valor_dimension_padre',
                        'tipo_dato' => PDO::PARAM_INT,
                        'longitud' => 8,
                        'valor' => $id_valor_dimension_padre),
                    array('nombre' => 'id_nivel',
                        'tipo_dato' => PDO::PARAM_INT,
                        'longitud' => 5,
                        'valor' => $id_nivel),
                    array('nombre' => 'id_dimension',
                        'tipo_dato' => PDO::PARAM_INT,
                        'longitud' => 5,
                        'valor' => $id_dimension),
                    array('nombre' => 'valor_dimension',  //out
                        'tipo_dato' => PDO::PARAM_INT,
                        'longitud' => 5,
                        'valor' => ''),
                    array('nombre' => 'figurativo',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 5,
                        'valor' => $figurativo),

                    array('nombre' => 'rrhh',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 5,
                        'valor' => $rrhh),

                    array('nombre' => 'resultado',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 4000,
                        'valor' => ''),
                );

                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                toba::logger()->debug('valor resultado '. json_encode($resultado));
                if ($resultado[10]['valor'] == 'OK') {
                    if ($con_transaccion) {
                        toba::db()->cerrar_transaccion();
                    }
                    return 'OK';
                } else {
                    toba::db()->abortar_transaccion();
                    return $resultado[10]['valor'];
                }

            } else {
                toba::logger()->debug('----- DAO_DIMENSION - AGREGAR NODO No todos los parametros están seteados.');
                return 'No es posible crear el nodo.';
            }
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            if ($con_transaccion) {
                toba::db()->abortar_transaccion();
            }
            return 'Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
            if ($con_transaccion) {
                toba::db()->abortar_transaccion();
            }
            return 'Error ' . $e->get_mensaje();
        }
    }

    public static function obtener_nivel($id_valor_dimension)
    {
        $sql = "
            SELECT nro_nivel nro_nivel
            FROM CS_VALORES_DIMENSIONES a,
            	 cs_niveles_dimension b
		    WHERE
		    	b.csdi_id_dimension = a.id_dimension
				AND b.id_nivel = a.id_nivel
				AND id_valor_dimension = $id_valor_dimension
        ";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['nro_nivel'];
    }

    public static function existe_nivel($id_dimension, $nivel)
    {
        $sql = "
            SELECT count(1) cantidad
            FROM cs_niveles_dimension
            WHERE csdi_id_dimension = $id_dimension
                AND nro_nivel = $nivel
        ";
        $datos = toba::db()->consultar_fila($sql);
        if (intval($datos['cantidad']) > 0){
            return true;
        }else{
            return false;
        }
    }

    public static function cant_valores_nivel($id_dimension, $nivel)
    {
        $sql = "
            SELECT max(cant_valores) cantidad
            FROM cs_niveles_dimension
            WHERE
                csdi_id_dimension = $id_dimension
                AND nro_nivel = $nivel
            GROUP BY cant_valores;
        ";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['cantidad'];
    }

    public static function modificar_valor_dimension ($codigo_dim, $descripcion, $fecha_act, $fecha_desct, $activo, $id_valor_dimension, $figurativo, $rrhh, $con_transaccion = false) {

        try {

            if ( !empty($codigo_dim) && !empty($descripcion) && !empty($fecha_act) ) {

                if ($con_transaccion) {
                    toba::db()->abrir_transaccion();
                }

		        $id_valor_dimension_padre = !empty($id_valor_dimension_padre) ? quote($id_valor_dimension_padre) : '';

                 $sql = " BEGIN :resultado := pkg_dimension.modificar_valor_dimension(
                 	:codigo_dim
                 	,:descripcion
                 	,to_date(:fecha_act,'YYYY-MM-DD')
                 	,to_date(:fecha_desct,'YYYY-MM-DD')
                 	,:activo
                 	,:id_valor_dimension
                 	,:figurativo
                 	,:rrhh
                 ); END; ";

                $sql = ctr_procedimientos::sanitizar_consulta($sql);
                $parametros = array(
                    array('nombre' => 'codigo_dim',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 50,
                        'valor' => $codigo_dim),
                    array('nombre' => 'descripcion',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 200,
                        'valor' => $descripcion),
                    array('nombre' => 'fecha_act',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $fecha_act),
                    array('nombre' => 'fecha_desct',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 20,
                        'valor' => $fecha_desct),
                    array('nombre' => 'activo',
                        'tipo_dato' => PDO::PARAM_INT,
                        'longitud' => 5,
                        'valor' => $activo),
                    array('nombre' => 'id_valor_dimension',
                        'tipo_dato' => PDO::PARAM_INT,
                        'longitud' => 5,
                        'valor' => $id_valor_dimension),
                    array('nombre' => 'figurativo',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 5,
                        'valor' => $figurativo),
                    array('nombre' => 'rrhh',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 5,
                        'valor' => $rrhh),
                    array('nombre' => 'resultado',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 4000,
                        'valor' => ''),
                );

                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                toba::logger()->debug('valor resultado '. json_encode($resultado));
                if ($resultado[8]['valor'] == 'OK') {
                    if ($con_transaccion) {
                        toba::db()->cerrar_transaccion();
                    }
                    return 'OK';
                } else {
                    toba::db()->abortar_transaccion();
                    return $resultado[8]['valor'];
                }

            } else {
                toba::logger()->debug('----- DAO_DIMENSION - MODIFICAR NODO No todos los parametros están seteados.');
                return 'No es posible modificar el nodo.';
            }
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            if ($con_transaccion) {
                toba::db()->abortar_transaccion();
            }
            return 'Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
            if ($con_transaccion) {
                toba::db()->abortar_transaccion();
            }
            return 'Error ' . $e->get_mensaje();
        }
    }

    public static function eliminar_valor_dimension($id_valor_dimension)
    {
        $id_valor_dimension = quote($id_valor_dimension);
        $mensaje_error = null;

        $sql = "
            BEGIN
                :resultado := pkg_dimension.eliminar_valor_dimension(
                    $id_valor_dimension
                );
            END;
        ";

        $parametros = [[
            'nombre' => 'resultado',
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 400,
            'valor' => '',
        ]];

        $rta = ctr_procedimientos::ejecutar_procedimiento(
            $mensaje_error,
            $sql,
            $parametros
        );

        return pos($rta)['valor'];
    }

}
?>