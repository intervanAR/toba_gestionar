<?php

class dao_auditoria_tablas
{
	public static function get_registros_auditoria($filtro = array(), $orden = array()) {
		$fuente = null;
		if(isset($filtro['modulo'])){
			$fuente= $filtro['modulo'];
			unset($filtro['modulo']);
		} else {
			return array();
		}
		$desde= null;
		$hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];
			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}
		$where = self::get_where($filtro);

		$sql_sel = "SELECT  aud.*
				FROM auditoria_2 aud
				WHERE $where
				ORDER BY aud.fecha DESC";
		$sql= dao_varios::paginador($sql_sel, null, $desde, $hasta, null, $orden);
        $datos = toba::db($fuente)->consultar($sql);
		return $datos;
	}

	public static function get_cantidad_registros_auditados($filtro = array()) {
		$fuente = null;
		if(isset($filtro['modulo'])){
			$fuente= $filtro['modulo'];
			unset($filtro['modulo']);
		} else {
			return 0;
		}
		$where = self::get_where($filtro);
		$sql_sel = "SELECT  COUNT(1) cantidad
					FROM auditoria_2 aud
					WHERE $where;";
		$datos = toba::db($fuente)->consultar_fila($sql_sel);
		if (isset($datos['cantidad'])) {
			return $datos['cantidad'];
		} else {
			return 0;
		}
		return $datos;
	}

	public static function get_where($filtro = array()) {
		$where = " 1=1 ";
		if (isset($filtro['tabla'])) {
			$where .= "AND " . ctr_construir_sentencias::construir_translate_ilike('tabla', $filtro['tabla']);
			unset($filtro['tabla']);
		}
		if (isset($filtro['usuario'])) {
			$where .= "AND " . ctr_construir_sentencias::construir_translate_ilike('usuario', $filtro['usuario']);
			unset($filtro['usuario']);
		}
		if (isset($filtro['clave_des'])) {
			$where .= "AND " . ctr_construir_sentencias::construir_translate_ilike('clave_des', $filtro['clave_des']);
			unset($filtro['clave_des']);
		}
		if (isset($filtro['pk'])) {
			$where .= "AND " . ctr_construir_sentencias::construir_translate_ilike('pk', $filtro['pk']);
			unset($filtro['pk']);
		}
		if (isset($filtro['fecha_desde'])){
			$where .= " AND aud.fecha >= to_date('".$filtro['fecha_desde']."','YYYY/MM/DD')";
			unset($filtro['fecha_desde']);
		}
		if (isset($filtro['fecha_hasta'])){
			$where .= " AND aud.fecha <= to_date('".$filtro['fecha_hasta']."','YYYY/MM/DD')";
			unset($filtro['fecha_hasta']);
		}
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'aud', '1=1');
		return $where;
	}
	
	public static function get_modulos()
	{
		return array(
			array('clave' => 'afi', 'valor' => 'AFI'),
			array('clave' => 'rentas', 'valor' => 'Rentas'),
			array('clave' => 'rrhh', 'valor' => 'RRHH'),
			array('clave' => 'sociales', 'valor' => 'Sociales'),
			array('clave' => 'ventas_agua', 'valor' => 'Ventas Agua')
		);
	}
	
	public static function get_tablas($filtro) {
		$where = " 1=1";
		$fuente = null;
		$filtro_auditada = null;
		if(isset($filtro['modulo'])){
			$fuente= $filtro['modulo'];
			unset($filtro['modulo']);
		} else {
			return 0;
		}
		$desde= null;
		$hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}
		if(isset($filtro['auditada'])){
			$filtro_auditada = $filtro['auditada'] === 'S' ? true : false;
			unset($filtro['auditada']);
		}
		if (isset($filtro['tabla'])) {
			$where .= "AND " . ctr_construir_sentencias::construir_translate_ilike('tabla', $filtro['tabla']);
			unset($filtro['tabla']);
		}
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'tab', '1=1');
		$sql_sel = "SELECT  tab.*
				FROM v_tablas tab
				WHERE $where
				ORDER BY tab.tabla ASC";
		$sql = dao_varios::paginador($sql_sel, null, $desde, $hasta, null, array());
		$datos = toba::db($fuente)->consultar($sql);
		$datos_filtrados = array();
		foreach ($datos as $dato) {
			$datos_trigger = self::datos_trigger_x_tabla($fuente, $dato['tabla']);
			if (!isset($filtro_auditada) || $datos_trigger['auditada'] === $filtro_auditada) {
				$datos_filtrados[] = array_merge($dato, $datos_trigger);
			}
		}
		return $datos_filtrados;
	}
	
	public static function get_cantidad_tablas($filtro = array()) {
		$where = " 1=1";
		$fuente = null;
		$filtro_auditada = null;
		if(isset($filtro['modulo'])){
			$fuente= $filtro['modulo'];
			unset($filtro['modulo']);
		} else {
			return 0;
		}
		if(isset($filtro['auditada'])){
			$filtro_auditada = $filtro['auditada'] === 'S' ? true : false;
			unset($filtro['auditada']);
		}
		if (isset($filtro['tabla'])) {
			$where .= "AND " . ctr_construir_sentencias::construir_translate_ilike('tabla', $filtro['tabla']);
			unset($filtro['tabla']);
		}
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'tab', '1=1');

		$sql = "SELECT  tab.*
					FROM v_tablas tab
					WHERE $where;";
		$datos = toba::db($fuente)->consultar($sql);
		$datos_filtrados = array();
		foreach ($datos as $dato) {
			$datos_trigger = self::datos_trigger_x_tabla($fuente, $dato['tabla']);
			if (!isset($filtro_auditada) || $datos_trigger['auditada'] === $filtro_auditada) {
				$datos_filtrados[] = array_merge($dato, $datos_trigger);
			}
		}
		return count($datos_filtrados);
	}
	
	static public function datos_trigger_x_tabla($fuente, $tabla) {
		if (isset($tabla)) {
			$sql = "BEGIN :resultado := pkg_auditoria.datos_trigger(:tabla,:campos_auditados,:campos_clave,:insert,:update,:delete); END;";

			$parametros = array(	array(	'nombre' => 'tabla',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 4000,
											'valor' => $tabla),
									array(	'nombre' => 'campos_auditados',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 4000,
											'valor' => ''),
									array(	'nombre' => 'campos_clave',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 4000,
											'valor' => ''),
									array(	'nombre' => 'insert',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 4000,
											'valor' => ''),
									array(	'nombre' => 'update',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 4000,
											'valor' => ''),
									array(	'nombre' => 'delete',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 4000,
											'valor' => ''),
									array(	'nombre' => 'resultado',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 4000,
											'valor' => ''),
								);
			$resultado = toba::db($fuente)->ejecutar_store_procedure($sql, $parametros);
			$valor_resultado = $resultado[count($resultado) - 1]['valor'];
			if ($valor_resultado != 'OK') {
				return array(
					'auditada' => false,
					'operaciones' => '-',
					'campos' => '-'
				);
			} else {
				$operacion = array();
				$operaciones = array();
				$campos = str_replace(array(' ', '(', ')', '\''), array('', '', '', ''), trim($resultado[count($resultado) - 6]['valor']));
				$campo = explode(',', $campos);
				if ($resultado[count($resultado) - 4]['valor'] == 'S') {
					$operacion[] = 'insert';
					$operaciones[] = 'INS';
				}
				if ($resultado[count($resultado) - 3]['valor'] == 'S') {
					$operacion[] = 'update';
					$operaciones[] = 'UPD';
				}
				if ($resultado[count($resultado) - 2]['valor'] == 'S') {
					$operacion[] = 'delete';
					$operaciones[] = 'DEL';
				}
				if (empty($operaciones)) $operaciones[] = '-';
				if (empty($campos)) $campos = '-';
				return array(
					'operacion' => $operacion,
					'campo' => $campo,
					'operaciones' => implode(',', $operaciones),
					'campos' => str_replace(',', ', ', $campos),
					'auditada' => true
					);
			}
		}
	}
	
	static public function eliminar_auditoria($fuente, $tabla) {
		if (isset($tabla)) {
			$sql = "BEGIN pkg_auditoria.drop_trig_aud(:tabla); END;";

			$parametros = array(	array(	'nombre' => 'tabla',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 4000,
											'valor' => $tabla),
								);
			toba::db($fuente)->ejecutar_store_procedure($sql, $parametros);
		}
	}
	
	static public function crear_auditoria($fuente, $tabla, $operaciones, $campos) {
		if (isset($tabla) && isset($operaciones) && !empty($operaciones) && isset($campos) && !empty($campos)) {
			$arr_pks = self::get_pks_tabla($fuente, $tabla);
			if (empty($arr_pks)) return;
			$pks = '(' . implode(',', ctr_funciones_basicas::matriz_to_array($arr_pks, 'campo')) . ')';
			$cadena = '(' . implode(',', $campos) . ')';
			$insert = (in_array('insert', $operaciones)) ? 'S' : 'N';
			$update = (in_array('update', $operaciones)) ? 'S' : 'N';
			$delete = (in_array('delete', $operaciones)) ? 'S' : 'N';
			$sql = "BEGIN pkg_auditoria.Crear_Auditoria_SR(:tabla,'AUDITA$'||SUBSTR(:tabla,1,23),:cadena,:pk,:v_insert,:v_update,:v_delete); END;";
			$parametros = array(	array(	'nombre' => 'tabla',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 4000,
											'valor' => $tabla),
									array(	'nombre' => 'cadena',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 4000,
											'valor' => $cadena),
									array(	'nombre' => 'pk',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 4000,
											'valor' => $pks),
									array(	'nombre' => 'v_insert',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 4000,
											'valor' => $insert),
									array(	'nombre' => 'v_update',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 4000,
											'valor' => $update),
									array(	'nombre' => 'v_delete',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 4000,
											'valor' => $delete),
								);
			try {
				toba::db($fuente)->ejecutar_store_procedure($sql, $parametros);
			} catch(PDOException $e_pdo) {
				toba::logger()->error($e_pdo->getMessage());
				toba::notificacion()->error($e_pdo->getMessage());
			}
		}
	}
	
	public static function get_campos_tabla($fuente, $tabla) {
		$tabla = quote($tabla);
		$sql = "SELECT  tc.campo, 
						tc.campo || CASE
									WHEN cpk.POSICION >= 0 THEN ' (PK)'
									ELSE ''
									END descripcion
				FROM V_TAB_COLUMNS tc
				LEFT JOIN v_columnas_pk cpk ON cpk.tabla = tc.tabla AND cpk.campo = tc.campo  
				WHERE upper(tc.tabla) = upper(" . $tabla . ")";
		$datos = toba::db($fuente)->consultar($sql);
		return $datos;
	}
	
	public static function get_pks_tabla($fuente, $tabla) {
		$tabla = quote($tabla);
		$sql = "select campo
				from v_columnas_pk
				WHERE upper(tabla) = upper(" . $tabla . ")
				order by posicion";
		$datos = toba::db($fuente)->consultar($sql);
		return $datos;
	}
	
	public static function get_descripcion_tabla($tabla) {
		return $tabla;
	}
}
