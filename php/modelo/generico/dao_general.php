<?php

/**
 * @author fbohn
 * @author hmargiotta
 * @author lgraziani
 */
class dao_general
{
	public static function vincula_expediente_madre()
	{
		$valor_parametro = self::consultar_parametro_kr('VINCULA_EXPEDIENTE');

		return isset($valor_parametro) && $valor_parametro === 'S';
	}

	public static function abrevia_dominio($tabla, $parametro)
	{
		$sql = "
			SELECT pkg_general.ABBREV_DOMINIO('$tabla', '$parametro') abrevia
			FROM DUAL
		";
		$datos = toba::db()->consultar_fila($sql);

		return $datos['abrevia'];
	}

	public static function minima_fecha_factura()
	{
		return self::consultar_parametro_kr('MIN_FECHA_FACTURA');
	}

	public static function get_url_afip()
	{
		return self::consultar_parametro('URL_AFIP');
	}

	public static function get_lugares_entrega_x_item()
	{
		return self::consultar_parametro_kr('LUGARES_ENTREGA_X_ITEM');
	}

	public static function get_estado_inicial_solicitud()
	{
		return self::consultar_parametro('SOLICITUD_ESTADO_INICIAL');
	}

	public static function get_estado_final_solicitud()
	{
		return self::consultar_parametro('SOLICITUD_ESTADO_FINAL');
	}

	public static function get_estado_final_nok_solicitud()
	{
		return self::consultar_parametro('SOLICITUD_ESTADO_FINAL_NOK');
	}

	public static function get_informa_sin_presup_solicitud()
	{
		return self::consultar_parametro('INFORMA_SIN_PRESUP_SOLICITUD');
	}

	public static function get_integrado_afi()
	{
		return self::consultar_parametro('INTEGRADO_AFI');
	}

	public static function get_copiar_descripcion_en_detalle()
	{
		return self::consultar_parametro('COPIAR_DESCRIPCION_EN_DETALLE');
	}

	public static function get_marcar_cheque()
	{
		return self::consultar_parametro_kr('CHEQUE_PROPIO_NRO_CHEQUE');
	}

	public static function get_sellado_compra($tipo_compra)
	{
		return self::consultar_orden_parametro('CO_TIPO_COMPRA', $tipo_compra);
	}

	public static function get_permite_compra_sin_solicitud()
	{
		return self::consultar_parametro('PERM_COMPRA_SIN_SOLICITUD');
	}

	public static function get_estado_inicial_compra()
	{
		return self::consultar_parametro('COMPRA_ESTADO_INICIAL');
	}

	public static function get_estado_final_compra()
	{
		return self::consultar_parametro('COMPRA_ESTADO_FINAL');
	}

	public static function get_estado_final_nok_compra()
	{
		return self::consultar_parametro('COMPRA_ESTADO_FINAL_NOK');
	}

	public static function get_estado_inicial_recepcion()
	{
		return self::consultar_parametro('RECEPCION_ESTADO_INICIAL');
	}

	public static function get_estado_final_recepcion()
	{
		return self::consultar_parametro('RECEPCION_ESTADO_FINAL');
	}

	public static function get_estado_final_nok_recepcion()
	{
		return self::consultar_parametro('RECEPCION_ESTADO_FINAL_NOK');
	}

	public static function get_estado_inicial_orden()
	{
		return self::consultar_parametro('ORDEN_ESTADO_INICIAL');
	}

	public static function get_estado_final_orden()
	{
		return self::consultar_parametro('ORDEN_ESTADO_FINAL');
	}

	public static function get_estado_final_nok_orden()
	{
		return self::consultar_parametro('ORDEN_ESTADO_FINAL_NOK');
	}

	public static function get_estado_inicial_pedido()
	{
		return self::consultar_parametro('PEDIDO_ESTADO_INICIAL');
	}

	public static function get_estado_final_pedido()
	{
		return self::consultar_parametro('PEDIDO_ESTADO_FINAL');
	}

	public static function get_estado_final_nok_pedido()
	{
		return self::consultar_parametro('PEDIDO_ESTADO_FINAL_NOK');
	}

	public static function get_copias_orden_compra_carga()
	{
		return self::consultar_parametro('COPIAS_ORDEN_COMPRA_CARGA');
	}

	public static function get_copias_orden_compra()
	{
		return self::consultar_parametro('COPIAS_ORDEN_COMPRA');
	}

	public static function get_integrado_ren()
	{
		return self::consultar_parametro('INTEGRADO_REN');
	}

	public static function get_copias_solicitud_compra()
	{
		return self::consultar_parametro('COPIAS_SOLICITUD');
	}

	public static function meses_vencimiento_proveedor()
	{
		return self::consultar_parametro('MESES_VENC_PROVEEDOR');
	}

	/**
	 * Transforma fechas yyyy-mm-dd a dd/mm/yyyy
	 *
	 * @param string $fecha Fecha a transformar.
	 * @return string Fecha formateada.
	 */
	public static function transformar_fecha($fecha)
	{
		if (!preg_match('/^\d{4}\-\d{1,2}\-\d{1,2}/', $fecha)) {
			return $fecha;
		}

		$fecha = substr($fecha, 0, 10);
		list($anio, $mes, $dia) = explode('-', $fecha);

		if (str_word_count($dia) === 1) {
			$dia = "0$dia";
		}

		if (str_word_count($mes) === 1) {
			$mes = "0$mes";
		}

		return "$dia/$mes/$anio";
	}

	public static function borrar_registro(
		$tabla, $campo, $id_comprobante, $con_transaccion = true
	) {
		if (!isset($tabla) || !isset($id_comprobante)) {
			return;
		}
		try {
			if ($con_transaccion) {
				toba::db()->abrir_transaccion();
			}
			$sql = "DELETE FROM $tabla WHERE $campo= $id_comprobante";

			toba::db()->ejecutar($sql);

			if ($con_transaccion) {
				toba::db()->cerrar_transaccion();
			}

			return 'OK';
		} catch (toba_error_db $e_db) {
			toba::notificacion()->error($e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
			toba::logger()->error($e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
			if ($con_transaccion) {
				toba::db()->abortar_transaccion();
			}
		} catch (toba_error $e) {
			toba::notificacion()->error($e->get_mensaje());
			toba::logger()->error($e->get_mensaje());
			if ($con_transaccion) {
				toba::db()->abortar_transaccion();
			}
		}
	}

	/**
	 * Formatea un CBU de entrada según la siguiente mascara:
	 * 00000000"-"00000000000000.
	 *
	 * Ejemplo:
	 * CBU = 511584321323
	 * CBU_FORMAT = 00000000-00511584321323
	 */
	public static function format_cbu($cbu)
	{
		if (!isset($cbu)) {
			return;
		}
		$mask = '0000000000000000000000';
		$long_mask = strlen($mask);
		$pos_guion = 8;
		$long_cbu = strlen($cbu);
		$sub_mask = substr($mask, 0, $long_mask - $long_cbu);

		$new_str_cbu = $sub_mask.$cbu;
		$substr1 = substr($new_str_cbu, 0, $pos_guion);
		$substr2 = substr($new_str_cbu, $pos_guion);
		$cbu_format = $substr1.'-'.$substr2;

		return $cbu_format;
	}

	public static function validar_cbu($cbu)
	{
		if (!isset($cbu)) {
			return;
		}
		try {
			$sql = 'BEGIN :resultado := pkg_general.cbu_valido(:cbu); END;';
			$parametros = [[
				'nombre' => 'cbu',
				'tipo_dato' => PDO::PARAM_STR,
				'longitud' => 23,
				'valor' => $cbu,
			], [
				'nombre' => 'resultado',
				'tipo_dato' => PDO::PARAM_STR,
				'longitud' => 4000,
				'valor' => '',
			]];
			$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

			if (isset($resultado[1]['valor'])) {
				return $resultado[1]['valor'];
			}
		} catch (toba_error_db $e_db) {
			toba::notificacion()->error($e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
			toba::logger()->error($e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
		} catch (toba_error $e) {
			toba::notificacion()->error($e->get_mensaje());
			toba::logger()->error($e->get_mensaje());
		}
	}

	public static function get_id_session()
	{
		try {
			$sql = 'BEGIN :resultado := pkg_general.obtener_id_session(); END;';
			$parametros = [[
				'nombre' => 'resultado',
				'tipo_dato' => PDO::PARAM_INT,
				'longitud' => 10,
				'valor' => '',
			]];
			$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

			if (isset($resultado[0]['valor'])) {
				return $resultado[0]['valor'];
			}
		} catch (toba_error_db $e_db) {
			toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
			toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());

			return 'Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate();
		} catch (toba_error $e) {
			toba::notificacion()->error('Error '.$e->get_mensaje());
			toba::logger()->error('Error '.$e->get_mensaje());

			return 'Error '.$e->get_mensaje();
		}
	}

	public static function actualizar_registro(
		$tabla, $columnas = [], $filtro = [], $con_transaccion = false
	) {
		if (!isset($columnas) || !isset($tabla)) {
			return;
		}
		try {
			if (isset($columnas)) {
				$seters = ctr_construir_sentencias::construir_sentencia_set($columnas, 'tbl');
			}
			if (isset($filtro)) {
				$where = ctr_construir_sentencias::get_where_filtro($filtro, 'tbl', '1=1');
			}
			if ($con_transaccion) {
				toba::db()->abrir_transaccion();
			}

			$sql = "
				UPDATE
					$tabla tbl
				SET
					$seters
				WHERE
					$where
			";
			toba::logger()->debug('----- ACTUALIZAR REGISTRO $sql: '.$sql);
			toba::db()->ejecutar($sql);

			if ($con_transaccion) {
				toba::db()->cerrar_transaccion();
			}

			return 'OK';
		} catch (toba_error_db $e_db) {
			toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
			toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
			if ($con_transaccion) {
				toba::db()->abortar_transaccion();
			}

			return 'Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate();
		} catch (toba_error $e) {
			toba::notificacion()->error('Error '.$e->get_mensaje());
			toba::logger()->error('Error '.$e->get_mensaje());
			if ($con_transaccion) {
				toba::db()->abortar_transaccion();
			}

			return 'Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate();
		}
	}

	/*
	 * Elimina registro/s de la tabla indicada.
	 * Por seguridad el filtro no debe ser null o vacio.
	 * De esta manera se evita hacer un:
	 * DELETE FROM tabla
	 * , que eliminarìa todo (suponiendo que se superan las posible constrains de la tabla)
	 *
	 * para eliminar completamente los datos setear el filtro: all = 1
	 *
	 */
	public static function eliminar_registro(
		$tabla, $filtro = [], $con_transaccion = false
	) {
		if (
			!isset($filtro)
			|| empty($filtro)
			|| !isset($tabla)
		) {
			return;
		}
		try {
			if (isset($filtro) && !isset($filtro['all'])) {
				$where = ctr_construir_sentencias::get_where_filtro($filtro, 'tbl', '1=1');
			} elseif (isset($filtro['all'])) {
				$where = ' 1 = 1 ';
			}

			if ($con_transaccion) {
				toba::db()->abrir_transaccion();
			}

			$sql = "
					DELETE FROM
						$tabla tbl
					WHERE
						$where
					;";
			toba::logger()->debug('----- ELIMINAR REGISTRO $sql: '.$sql);
			toba::db()->ejecutar($sql);

			if ($con_transaccion) {
				toba::db()->cerrar_transaccion();
			}

			return 'OK';
		} catch (toba_error_db $e_db) {
			$sql_codigo_error = $e_db->get_codigo_motor();
			if ($sql_codigo_error == '2292') {
				toba::notificacion()->error('Error! Los datos están en uso. No es posible eliminar la información.');

				return 'Error! Los datos están en uso. No es posible eliminar la información.';
			}

			toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
			toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
			if ($con_transaccion) {
				toba::db()->abortar_transaccion();
			}

			return 'Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate();
		} catch (toba_error $e) {
			toba::notificacion()->error('Error '.$e->get_mensaje());
			toba::logger()->error('Error '.$e->get_mensaje());
			if ($con_transaccion) {
				toba::db()->abortar_transaccion();
			}

			return 'Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate();
		}
	}

	/**
	 * SELECT * FROM tabla.
	 */
	public static function seleccionar_registros(
		$tabla, $filtro = [], $con_transaccion = false
	) {
		if (
			!isset($filtro)
			|| empty($filtro)
			|| !isset($tabla)
		) {
			return '';
		}
		if (isset($filtro)) {
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'tbl');
		}
		$sql = "
			SELECT *
			FROM $tabla tbl
			WHERE
				$where
		";

		toba::logger()->debug("----- SELECCIONAR REGISTROS \$sql: $sql");

		return toba::db()->consultar($sql);
	}

	public static function calcular_billetes(
		$p_id_entidad,
		$p_pedido,
		$p_sector,
		$p_organizacion,
		$tipo_moneda,
		$con_transaccion = false
	) {
		try {
			if (
				!isset($p_id_entidad)
				|| !isset($p_pedido)
				|| !isset($tipo_moneda)
			) {
				toba::logger()->debug('----- DAO_GENERAL - CALCULAR BILLETES: No todos los parametros están seteados.');

				return 'Upps! No es posible realizar el calculo de billets.';
			}
			$p_sector = !empty($p_sector) ? $p_sector : 'null';
			$p_organizacion = !empty($p_organizacion) ? $p_organizacion : 'null';

			if ($con_transaccion) {
				toba::db()->abrir_transaccion();
			}

			$sql =
				"BEGIN :resultado := pkg_general.calcular_billetes($p_id_entidad, $p_pedido, $p_sector, $p_organizacion, '$tipo_moneda'); END;";

			$parametros = [[
				'nombre' => 'resultado',
				'tipo_dato' => PDO::PARAM_STR,
				'longitud' => 4000,
				'valor' => '',
			]];
			$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

			if ($con_transaccion) {
				toba::db()->cerrar_transaccion();
			}

			return $resultado[0]['valor'];
		} catch (toba_error_db $e_db) {
			toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
			toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
			if ($con_transaccion) {
				toba::db()->abortar_transaccion();
			}

			return 'Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate();
		} catch (toba_error $e) {
			toba::notificacion()->error('Error '.$e->get_mensaje());
			toba::logger()->error('Error '.$e->get_mensaje());
			if ($con_transaccion) {
				toba::db()->abortar_transaccion();
			}

			return 'Error '.$e->get_mensaje();
		}
	}

	public static function execute_immediate($sentencia = null)
	{
		if (is_null($sentencia)) {
			return false;
		}
		$sql = "BEGIN PKG_GENERAL.execute_immediate('$sentencia'); :resultado := 'OK'; EXCEPTION WHEN OTHERS THEN :resultado := 'NOTOK'; END;";
		$parametros = [[
			'nombre' => 'resultado',
			'tipo_dato' => PDO::PARAM_STR,
			'longitud' => 500,
			'valor' => '',
		]];
		$datos = toba::db()->ejecutar_store_procedure($sql, $parametros);

		return $datos[0]['valor'] === 'OK';
	}

	public static function obtener_basico(
		$p_id_entidad,
		$p_id_persona,
		$p_id_empleado,
		$p_id_ocupacion_puesto,
		$p_fecha,
		$con_transaccion = false
	) {
		try {
			if (
				!isset($p_id_entidad)
				|| !isset($p_id_persona)
				|| !isset($p_id_empleado)
				|| !isset($p_id_ocupacion_puesto)
				|| !isset($p_fecha)
			) {
				toba::logger()->debug('----- DAO_GENERAL - OBTENER BASICO No todos los parametros están seteados.');

				return 'Upps! No es posible calcular el básico.';
			}
			if ($con_transaccion) {
				toba::db()->abrir_transaccion();
			}

			$sql = "BEGIN :resultado := PKG_GENERAL.obtener_basico($p_id_entidad, $p_id_persona, $p_id_empleado, $p_id_ocupacion_puesto, TO_DATE('$p_fecha', 'yyyy-mm-dd')); END;";
			$parametros = [[
				'nombre' => 'resultado',
				'tipo_dato' => PDO::PARAM_STR,
				'longitud' => 4000,
				'valor' => '',
			]];
			$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

			if ($con_transaccion) {
				toba::db()->cerrar_transaccion();
			}

			return $resultado[0]['valor'];
		} catch (toba_error_db $e_db) {
			toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
			toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
			if ($con_transaccion) {
				toba::db()->abortar_transaccion();
			}

			return 'Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate();
		} catch (toba_error $e) {
			toba::notificacion()->error('Error '.$e->get_mensaje());
			toba::logger()->error('Error '.$e->get_mensaje());
			if ($con_transaccion) {
				toba::db()->abortar_transaccion();
			}

			return 'Error '.$e->get_mensaje();
		}
	}

	public static function convierte_numero($valor)
	{
		try {
			$sql = "BEGIN :resultado := pkg_general.convierte_numero('$valor'); END;";
			$parametros = [[
				'nombre' => 'resultado',
				'tipo_dato' => PDO::PARAM_INT,
				'longitud' => 10,
				'valor' => '',
			]];
			$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

			return !isset($resultado[0]['valor']) ? '' : $resultado[0]['valor'];
		} catch (toba_error_db $e_db) {
			toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
			toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());

			return 'Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate();
		} catch (toba_error $e) {
			toba::notificacion()->error('Error '.$e->get_mensaje());
			toba::logger()->error('Error '.$e->get_mensaje());

			return 'Error '.$e->get_mensaje();
		}
	}

	/**
	 * Devuelve el total de filas existentes.
	 *
	 * @param string		 $tabla	  Nombre de la tabla principal
	 * @param (int|string)[] $filtro	 Optional Filtros aplicados a la tabla principal
	 * @param string		 $tab		Optional Abreviatura de la tabla principal
	 * @param string		 $where	  Optional Filtros especiales
	 * @param string		 $join	   Optional Listado separado por coma de tablas a hacer JOIN
	 * @param string		 $where_join Optional Filtros del JOIN
	 *
	 * @return int
	 */
	public static function get_cuadro_total_paginas(
		$tabla, $filtro = [], $tab = 'tab', $where = '', $join = '', $where_join = ''
	) {
		$tablas_cuenta_comprobante = ['re_facturas', 're_notas_credito'];

		if (empty($join)) {
			$join = '';
			$where_join = '';

			if ($tabla === 're_personas' && isset($filtro['nombre'])) {
				$trans_nombre = ctr_construir_sentencias::construir_translate_ilike('nombre', $filtro['nombre']);
				$where_join = "AND ($trans_nombre)";

				unset($filtro['nombre']);
			}

			if ($tabla === 're_partidas' && isset($filtro['id_persona'])) {
				$join = ', RE_CUENTAS c';
				$where_join = "
					AND c.id_cuenta = $tab.id_cuenta
					AND c.id_persona = {$filtro['id_persona']}
				";

				unset($filtro['id_persona']);
			}

			if (
				in_array($tabla, $tablas_cuenta_comprobante)
				&& isset($filtro['id_cuenta'])
			) {
				$join = ', RE_COMPROBANTES_CUENTA cc';
				$where_join = "
					AND cc.id_comprobante = $tab.id_comprobante
					AND cc.id_cuenta = {$filtro['id_cuenta']}
				";

				unset($filtro['id_cuenta']);
			}
		}

		$where = ctr_construir_sentencias::get_where_filtro($filtro, $tab).$where;
		$sql = "
			SELECT
				count(*) cantidad
			FROM
				$tabla $tab
				$join
			WHERE
				$where
				$where_join
		";

		return toba::db()->consultar_fila($sql)['cantidad'];
	}

	/**
	 * Esta función se encarga de realizar la consulta teniendo
	 * en cuenta la página del cuadro y la cantidad de filas máximo
	 * que muestra.
	 *
	 * Se encarga de aplicar el filtro a la consulta que recibe.
	 *
	 * @example
	 * ```php
	 *
	 * public static function get_convenios_mejoras($filtro = [], $order= [])
	 * {
	 *   $tab = 'MVPC';
	 *   $where = '1=1';
	 *
	 *   $sql = "
	 *	 SELECT *
	 *	 FROM RE_MVP_CONVENIOS $tab
	 *	 WHERE $where
	 *   ";
	 *
	 *   return dao_general::paginador_con_where_generico($sql, $tab, $filtro, $order);
	 * }
	 *
	 * ```
	 *
	 * @param string		 $sql	 consulta básica sin la inclusión de los
	 *								filtros genéricos
	 * @param string $tab nombre de la tabla principal
	 * @param (int|string)[] $filtro  contiene los filtros de paginación como
	 *								también aquellos que son generados
	 *								mediante el llamado a `get_where_filtro`
	 * @param (int|string)[] $order   Optional contiene los campos y en qué
	 *								orden deben ser ordenados
	 *
	 * @return mixed[] resultado de la consulta
	 */
	public static function paginador_con_where_generico(
		$sql, $tab, $filtro, $order = []
	) {
		$desde = null;
		$hasta = null;

		if (isset($filtro['numrow_desde'])) {
			$desde = $filtro['numrow_desde'] == 1
				? 1
				: intval($filtro['numrow_desde']) + 1;
			$hasta = $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}

		$where = ctr_construir_sentencias::get_where_filtro($filtro, $tab);
		$sql .= " AND $where";

		if (!isset($desde)) {
			$sql = ctr_construir_sentencias::armar_order_by($sql, $order);

			return toba::db()->consultar($sql);
		}
		$sql = ctr_construir_sentencias::armar_order_by($sql, $order);
		$sql = "
			SELECT *
			FROM (
				SELECT ROWNUM rnum, tabla.*
				FROM ($sql) tabla
			)
			WHERE rnum between $desde AND $hasta
		";
toba::logger()->debug("*********$sql---". count(toba::db()->consultar($sql)));
		return toba::db()->consultar($sql);
	}

	private static function consultar_parametro_kr($parametro)
	{
		$sql = "
			SELECT PKG_GENERAL.VALOR_PARAMETRO_KR('$parametro') valor_parametro
			FROM DUAL
		";
		$resultado = toba::db()->consultar_fila($sql);

		if (
			isset($resultado)
			&& !empty($resultado)
			&& isset($resultado['valor_parametro'])
		) {
			return $resultado['valor_parametro'];
		}
	}

	private static function consultar_parametro($parametro)
	{
		$sql = "
			SELECT PKG_GENERAL.VALOR_PARAMETRO('$parametro') valor_parametro
			FROM DUAL
		";
		$resultado = toba::db()->consultar_fila($sql);

		if (
			isset($resultado)
			&& !empty($resultado)
			&& isset($resultado['valor_parametro'])
		) {
			return $resultado['valor_parametro'];
		}
	}

	private static function consultar_orden_parametro($dominio, $valor)
	{
		$sql = "
			SELECT PKG_GENERAL.orden_dominio('$dominio', '$valor') orden_parametro
			FROM DUAL
		";
		$resultado = toba::db()->consultar_fila($sql);

		if (
			isset($resultado)
			&& !empty($resultado)
			&& isset($resultado['orden_parametro'])
		) {
			return $resultado['orden_parametro'];
		}
	}

	//////////////////////////////////////////////////////////////////////////////////
	///////////////FUNCIONES PARA ESCANEAR PARAMETROS/////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////


	public static function strpos_r($haystack, $needle) {
		if(strlen($needle) > strlen($haystack))
			trigger_error(sprintf("%s: length of argument 2 must be <= argument 1", __FUNCTION__), E_USER_WARNING);

		$seeks = array();
		while($seek = strrpos($haystack, $needle))
		{
			array_push($seeks, $seek);
			$haystack = substr($haystack, 0, $seek);
		}
		return $seeks;
	}

	public static function escanear_parametros($query){
		$ini= self::strpos_r($query, "[");
		$fin= self::strpos_r($query, "]");
		sort($ini);
		sort($fin);
		$parametros= array();
		for ($i=0; $i < count($ini); $i++) {
			$param= substr($query, $ini[$i], $fin[$i] - $ini[$i] + 1);

			if (!in_array($param, $parametros))
				array_push($parametros, $param);
		}

		return $parametros;
	}

	public static function reemplazar_parametros($query, $parametros){

		for ($i=0; $i < count($parametros); $i++) {
			$query= str_replace($parametros[$i]['parametro'], $parametros[$i]['valor'], $query);
		}

		return $query;
	}

	public static function escanear_prompts($query){
		$ini= self::strpos_r($query, "{");
		$fin= self::strpos_r($query, "}");
		$prompts= array();
		for ($i=0; $i < count($ini); $i++) {
			$param= substr($query, $ini[$i], $fin[$i] - $ini[$i] + 1);

			if (!isset($prompts[$param]))
				$prompts[$param]= 1;
			else
				$prompts[$param]= $prompts[$param] + 1;
		}

		return $prompts;
	}

	public static function get_url_homepage() {
        $archivo = toba::nucleo()->toba_instalacion_dir().'/instalacion.ini';
        $ini = parse_ini_file($archivo, true);
        $proyecto = toba::proyecto()->get_id();
		switch ($proyecto) {
            case 'principal':
                return isset($ini['url_homepage_principal']) ? $ini['url_homepage_principal'] : null;
                break;
            case 'administracion':
                return isset($ini['url_homepage_administracion']) ? $ini['url_homepage_administracion'] : null;
                break;
            case 'compras':
                return isset($ini['url_homepage_compras']) ? $ini['url_homepage_compras'] : null;
                break;
            case 'contabilidad':
                return isset($ini['url_homepage_contabilidad']) ? $ini['url_homepage_contabilidad'] : null;
                break;
            case 'costos':
                return isset($ini['url_homepage_costos']) ? $ini['url_homepage_costos'] : null;
                break;
            case 'presupuesto':
                return isset($ini['url_homepage_presupuesto']) ? $ini['url_homepage_presupuesto'] : null;
                break;
            case 'rrhh':
                return isset($ini['url_homepage_rrhh']) ? $ini['url_homepage_rrhh'] : null;
                break;
            case 'sociales':
                return isset($ini['url_homepage_sociales']) ? $ini['url_homepage_sociales'] : null;
                break;
            case 'rentas':
                return isset($ini['url_homepage_rentas']) ? $ini['url_homepage_rentas'] : null;
                break;
            case 'ventas_agua':
                return isset($ini['url_homepage_ventas_agua']) ? $ini['url_homepage_ventas_agua'] : null;
                break;
            default:
                return null;
                break;
        }
    }
}
