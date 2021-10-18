<?php
class dao_ejercicios
{
	/**
	 * @return {Array<string, string>} todos los elementos
	 */
	static public function get_ejercicios($filtro = array())
	{
		$where = '1=1';
		if (isset($filtro))
		{
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'KREJ', '1=1', array('nombre'));
		}
		$sql = "
			SELECT *
			FROM
				KR_EJERCICIOS KREJ
			WHERE
				$where
			ORDER BY
				id_ejercicio ASC
		;";
		$datos = toba::db()->consultar($sql);

		return $datos;
	}

	static public function get_ejercicio ($id_ejercicio)
	{
		$sql ="SELECT * 
				 FROM kr_ejercicios 
				WHERE id_ejercicio = $id_ejercicio";
		return toba::db()->consultar_fila($sql);
	}

	static public function get_valor_ejercicio($fecha, $format = 'yyyy-mm-dd') {
		try {
			$sql = "BEGIN :resultado := pkg_kr_ejercicios.retornar_ejercicio(to_date(substr(:fecha_comprobante,1,10),'".$format."')); END;";
			$parametros = array(array('nombre' => 'fecha_comprobante',
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 32,
					'valor' => $fecha),
				array('nombre' => 'resultado',
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 4000,
					'valor' => ''),
			);
			$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

			return array("ui_id_ejercicio" => $resultado[1]['valor']);
		} catch (toba_error_db $e_db) {
			toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
			toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
			return array("ui_id_ejercicio" => '');
		} catch (toba_error $e) {
			toba::notificacion()->error('Error ' . $e->get_mensaje());
			toba::logger()->error('Error ' . $e->get_mensaje());
			return array("ui_id_ejercicio" => '');
	   }
	}

	static public function calcular_valor_ejercicio ($fecha_egreso, $format = 'DD/MM/YYYY'){

		$sql = "SELECT id_ejercicio, nro_ejercicio
		          FROM kr_ejercicios
		         WHERE abierto = 'S' AND cerrado = 'N'
		           AND to_date('".$fecha_egreso."','".$format."') BETWEEN fecha_inicio AND fecha_fin;";
		$datos = toba::db()->consultar_fila($sql);
		if (isset($datos['id_ejercicio']) && !empty($datos['id_ejercicio'])){
			$datos['fecha_egreso'] = $fecha_egreso;
			return $datos;
		}else{
			$sql = "SELECT to_char(fecha_fin,'dd/mm/yyyy') fecha_fin, id_ejercicio, nro_ejercicio
                      FROM kr_ejercicios
                	 WHERE abierto = 'S' AND cerrado = 'N'
                	   AND fecha_fin = (SELECT max(fecha_fin) 
                	   					  FROM kr_ejercicios
                	   					  WHERE abierto = 'S'
                	   					  	AND cerrado = 'N');";
            $datos = toba::db()->consultar_fila($sql); 
            if (isset($datos['id_ejercicio']) && !empty($datos['id_ejercicio'])){
            	$datos['fecha_egreso'] = $datos['fecha_fin'];
				return $datos;
			}
		}
		return array('id_ejercicio'=>'','nro_ejercicio'=>'','fecha_egreso'=>'');
	}

	static public function get_nro_ejercicio($id_ejercicio){
		$sql = "SELECT NRO_EJERCICIO FROM KR_EJERCICIOS WHERE ID_EJERCICIO = $id_ejercicio;";
		$datos = toba::db()->consultar_fila($sql);	
		return $datos['nro_ejercicio'];
	}
	static public function get_ui_nro_ejercicio($id_ejercicio){
		$sql = "SELECT NRO_EJERCICIO ui_nro_ejercicio FROM KR_EJERCICIOS WHERE ID_EJERCICIO = $id_ejercicio;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos;
	}

	static public function get_lov_ejercicio_x_nombre ($nombre, $filtro = array()){
		$where ="";
		if (isset($nombre)) {
			$trans_id = ctr_construir_sentencias::construir_translate_ilike('id_ejercicio', $nombre);
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_ejercicio', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_id OR $trans_nro OR $trans_descripcion)";
		} else {
			$where = '1=1';
		}

		if (isset($filtro['con_asiento'])){
			$where .= " and pkg_proceso.al_menos_un_asiento(krej.id_ejercicio) = '".$filtro['con_asiento']."' ";
			unset($filtro['con_asiento']);
		}

		if (isset($filtro) && !empty($filtro))
			$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro, "KREJ", "1=1");
		$sql = "SELECT KREJ.*, KREJ.ID_EJERCICIO ||' - '|| KREJ.NRO_EJERCICIO ||' - '|| KREJ.DESCRIPCION AS LOV_DESCRIPCION
				FROM KR_EJERCICIOS KREJ
				WHERE $where
				ORDER BY KREJ.NRO_EJERCICIO;";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

	static public function get_lov_ejercicio_x_id ($id_ejercicio){
		$sql = "SELECT KREJ.ID_EJERCICIO ||' - '|| KREJ.NRO_EJERCICIO ||' - '|| KREJ.DESCRIPCION AS LOV_DESCRIPCION
				FROM KR_EJERCICIOS KREJ
				WHERE KREJ.ID_EJERCICIO = $id_ejercicio
				ORDER BY KREJ.NRO_EJERCICIO;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}


	static public function get_fecha_inicio ($id_ejercicio){
		$sql ="SELECT to_char(FECHA_INICIO,'dd/mm/yyyy') fecha
			   FROM KR_EJERCICIOS
			   WHERE ID_EJERCICIO = $id_ejercicio";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['fecha'];
	}

	static public function get_fecha_fin ($id_ejercicio){
		$sql ="SELECT to_char(FECHA_FIN, 'dd/mm/yyyy') fecha
			   FROM KR_EJERCICIOS
			   WHERE ID_EJERCICIO = $id_ejercicio";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['fecha'];
	}

	static public function get_fecha_inicio_actual (){
		try {
			$sql = "BEGIN :resultado := PKG_KR_EJERCICIOS.retornar_fecha_inicio(PKG_KR_EJERCICIOS.retornar_ejercicio(sysdate)); END;";
			$parametros = array(
							  array('nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''),
								);
			$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
			return $resultado[0]['valor'];
		} catch (toba_error_db $e_db) {
			toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
			toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
		} catch (toba_error $e) {
			toba::notificacion()->error('Error ' . $e->get_mensaje());
			toba::logger()->error('Error ' . $e->get_mensaje());
		}
	}

	static public function get_fecha_fin_actual (){
		try {
			$sql = "BEGIN :resultado := PKG_KR_EJERCICIOS.retornar_fecha_fin(PKG_KR_EJERCICIOS.retornar_ejercicio(sysdate)); END;";
			$parametros = array(
							  array('nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''),
								);
			$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
			return $resultado[0]['valor'];
		} catch (toba_error_db $e_db) {
			toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
			toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
		} catch (toba_error $e) {
			toba::notificacion()->error('Error ' . $e->get_mensaje());
			toba::logger()->error('Error ' . $e->get_mensaje());
		}
	}

	public static function abierto_no_cerrado_parcial($anio)
	{
		if (isset($anio)) {
			$sql = "SELECT pkg_kr_ejercicios.abierto_no_cerrado_parcial(".quote($anio).") resultado FROM DUAL;";
			$resultado = toba::db()->consultar_fila($sql);
			if (isset($resultado) && !empty($resultado) && isset($resultado['resultado'])) {
				return $resultado['resultado'];
			} else {
				return '';
			}
		} else {
			return '';
		}
	}

	static public function get_estado_fechas($id_ejercicio) {
		$sql = "
			SELECT
				abierto,
				fecha_abierto,
				cerrado,
				fecha_cerrado
			FROM
				KR_EJERCICIOS
			WHERE
				id_ejercicio = $id_ejercicio
		";
		$datos = toba::db()->consultar_fila($sql);

		return $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Datos tabla ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	static public function get_datos_periodos_por_ejercicio($id_ejercicio)
	{
		$sql = "
			SELECT *
			FROM
				KR_PERIODOS
			WHERE
				id_ejercicio = $id_ejercicio
		;";

		return toba::db()->consultar($sql);
	}
	static public function get_datos_periodos_cf_por_ejercicio($id_ejercicio)
	{
		$sql = "
			SELECT *
			FROM
				KR_PERIODOS_CF
			WHERE
				id_ejercicio = $id_ejercicio
		;";

		return toba::db()->consultar($sql);
	}
	static public function get_datos_ejercicio_cierres_por_ejercicio($id_ejercicio)
	{
		$sql = "
			SELECT *
			FROM
				KR_EJERCICIO_CIERRES
			WHERE
				id_ejercicio = $id_ejercicio
		;";

		return toba::db()->consultar($sql);
	}
	static public function remove_ejercicio_cierres_por_ejercicio($id_cierre, $id_ejercicio)
	{
		$sql = "
			DELETE FROM KR_EJERCICIO_CIERRES
			WHERE
				id_ejercicio = $id_ejercicio
				AND id_cierre = $id_cierre
		;";

		return toba::db()->ejecutar($sql);
	}
	static public function get_datos_cierre_cajas_por_ejercicio($id_ejercicio)
	{
		$sql = "
			SELECT *
			FROM
				KR_CIERRES_CAJAS
			WHERE
				id_ejercicio = $id_ejercicio
		;";

		return toba::db()->consultar($sql);
	}
	static public function remove_cierre_cajas_por_ejercicio($id_cierre, $id_ejercicio)
	{
		$sql = "
			DELETE FROM KR_CIERRES_CAJAS
			WHERE
				id_ejercicio = $id_ejercicio
				AND id_cierre = $id_cierre
		;";

		return toba::db()->ejecutar($sql);
	}
	static public function get_datos_cierre_impuesto_por_ejercicio($id_ejercicio)
	{
		$sql = "
			SELECT *
			FROM
				KR_CIERRES_IMPUESTOS
			WHERE
				id_ejercicio = $id_ejercicio
		;";

		return toba::db()->consultar($sql);
	}
	static public function remove_cierre_impuesto_por_ejercicio($id_cierre_impuestos, $id_ejercicio)
	{
		$sql = "SELECT PKG_KR_EJERCICIOS.permitir_eliminar_cierre($id_ejercicio) permite_eliminar from dual";
		$datos = toba::db()->consultar_fila($sql);
		
		if ($datos['permite_eliminar'] != 'OK')
		{
			throw new toba_error($datos['permite_eliminar']);
			return;
		}
		
		$sql = "
			DELETE FROM KR_CIERRES_IMPUESTOS
			WHERE
				id_ejercicio = $id_ejercicio
				AND id_cierre_impuestos = $id_cierre_impuestos
		;";

		return toba::db()->ejecutar($sql);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	static public function apertura_ejercicio($id_ejercicio)
	{
		$sql = "
			UPDATE
				KR_EJERCICIOS
			SET
				abierto = 'S',
				fecha_abierto = SYSDATE
			WHERE
				id_ejercicio = $id_ejercicio
		";

		toba::db()->ejecutar($sql);
	}
	static public function cierre_impuesto_ejercicio($id_ejercicio, $datos)
	{
		$fecha = $datos['fecha'];
		$observacion = $datos['observacion'] ? $datos['observacion'] : '';
		$con_transaccion = true;
		$sql = "BEGIN :resultado := Pkg_kr_ejercicios.CIERRE_IMPUESTOS(:id_ejercicio, :fecha, :observacion); END;";
		$parametros = [[
			'nombre' => 'id_ejercicio',
			'tipo_dato' => PDO::PARAM_INT,
			'longitud' => 32,
			'valor' => $id_ejercicio,
		], [
			'nombre' => 'fecha',
			'tipo_dato' => PDO::PARAM_STR,
			'longitud' => 32,
			'valor' => $fecha,
		], [
			'nombre' => 'observacion',
			'tipo_dato' => PDO::PARAM_STR,
			'longitud' => 4000,
			'valor' => $observacion,
		], [
			'nombre' => 'resultado',
			'tipo_dato' => PDO::PARAM_STR,
			'longitud' => 4000,
			'valor' => '',
		]];
		$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros, '', '', $con_transaccion);

		return end($resultado)['valor'];
	}
	static public function cierre_caja_ejercicio($id_ejercicio, $datos)
	{
		$fecha = $datos['fecha'];
		$observacion = $datos['observacion'] ? $datos['observacion'] : '';
		$con_transaccion = true;
		$sql = "BEGIN :resultado := Pkg_kr_ejercicios.CIERRE_CAJA(:id_ejercicio, :fecha, :observacion); END;";
		$parametros = [[
			'nombre' => 'id_ejercicio',
			'tipo_dato' => PDO::PARAM_INT,
			'longitud' => 32,
			'valor' => $id_ejercicio,
		], [
			'nombre' => 'fecha',
			'tipo_dato' => PDO::PARAM_STR,
			'longitud' => 32,
			'valor' => $fecha,
		], [
			'nombre' => 'observacion',
			'tipo_dato' => PDO::PARAM_STR,
			'longitud' => 4000,
			'valor' => $observacion,
		], [
			'nombre' => 'resultado',
			'tipo_dato' => PDO::PARAM_STR,
			'longitud' => 4000,
			'valor' => '',
		]];
		$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros, '', '', $con_transaccion);

		return end($resultado)['valor'];
	}
	static public function cierre_parcial_ejercicio($id_ejercicio, $datos)
	{
		$fecha = $datos['fecha'];
		$observacion = $datos['observacion'] ? $datos['observacion'] : '';
		$con_transaccion = true;
		$sql = "BEGIN :resultado := Pkg_kr_ejercicios.CIERRE_PARCIAL(:id_ejercicio, :fecha, :observacion); END;";
		$parametros = [[
			'nombre' => 'id_ejercicio',
			'tipo_dato' => PDO::PARAM_INT,
			'longitud' => 32,
			'valor' => $id_ejercicio,
		], [
			'nombre' => 'fecha',
			'tipo_dato' => PDO::PARAM_STR,
			'longitud' => 32,
			'valor' => $fecha,
		], [
			'nombre' => 'observacion',
			'tipo_dato' => PDO::PARAM_STR,
			'longitud' => 4000,
			'valor' => $observacion,
		], [
			'nombre' => 'resultado',
			'tipo_dato' => PDO::PARAM_STR,
			'longitud' => 4000,
			'valor' => '',
		]];
		$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros, '', '', $con_transaccion);

		return end($resultado)['valor'];
	}
	static public function cierre_final_ejercicio($id_ejercicio, $datos)
	{
		$fecha = $datos['fecha'];
		$con_transaccion = true;
		$sql = "BEGIN :resultado := Pkg_kr_ejercicios.CIERRE_FINAL(:id_ejercicio, :fecha); END;";
		$parametros = [[
			'nombre' => 'id_ejercicio',
			'tipo_dato' => PDO::PARAM_INT,
			'longitud' => 32,
			'valor' => $id_ejercicio,
		], [
			'nombre' => 'fecha',
			'tipo_dato' => PDO::PARAM_STR,
			'longitud' => 32,
			'valor' => $fecha,
		], [
			'nombre' => 'resultado',
			'tipo_dato' => PDO::PARAM_STR,
			'longitud' => 4000,
			'valor' => '',
		]];
		$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros, '', '', $con_transaccion);

		return end($resultado)['valor'];
	}

	static public function get_min_abierto (){
		$sql = "SELECT MIN (id_ejercicio) id_ejercicio
				  FROM kr_ejercicios
				 WHERE abierto = 'S'
				   AND cerrado = 'N'
				   AND pkg_proceso.al_menos_un_asiento (id_ejercicio) = 'S'";
		$datos = toba::db()->consultar_fila($sql);
		if (!empty($datos['id_ejercicio']))
			return $datos['id_ejercicio'];
		else return null;
	}
	

}
