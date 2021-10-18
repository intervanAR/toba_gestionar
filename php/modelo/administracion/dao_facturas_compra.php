<?php
/**
 *
 * @author fbohn
 * @author ddiluca
 * @author lgraziani
 */
class dao_facturas_compra
{
	public static function get_facturas_compra($filtro = array(), $orden = array()) {
		$desde= null;
		$hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}
		$where = self::get_where_facturas_compra($filtro);

		$sql_sel = "SELECT  af.*,
						trim(to_char(pkg_ad_facturas.importe_pagado (af.id_factura), '$999,999,999,990.00')) importe_pagado,
       					TO_CHAR(pkg_ad_facturas.fecha_ultimo_pago (af.id_factura),'DD/MM/YYYY') fecha_ultimo_pago,
						decode(af.confirmada,'S','Si','No') confirmada_format,
        				decode(af.anulada,'S','Si','No') anulada_format,
						kua.descripcion as unidad_administracion,
						kue.descripcion as unidad_ejecutora,
						atf.descripcion as tipo_factura,
						ap.id_proveedor || ' - ' || af.razon_social || ' ('||pkg_varios.formatear_cuit(af.CUIT) ||')' as id_razon_social_cuit_proveedor,
						ke.nro_expediente as nro_expediente,
						kep.nro_expediente as nro_expediente_pago,
						to_char(af.fecha_emision, 'DD/MM/YYYY') as fecha_emision_format,
						to_char(af.fecha_emision_fac, 'DD/MM/YYYY') as fecha_emision_fac_format,
						afp.DESCRIPCION as forma_pago,
						trim(to_char(af.importe_neto, '$999,999,999,990.00')) as importe_neto_format,
						trim(to_char(af.importe_impuestos, '$999,999,999,990.00')) as importe_impuestos_format,
						trim(to_char(af.importe, '$999,999,999,990.00')) as importe_format,
						trim(to_char((SELECT NVL(SUM(acg.IMPORTE),0)
								FROM AD_COMPROBANTES_GASTO acg
								WHERE acg.ID_FACTURA = af.id_factura AND acg.APROBADO = 'S' AND acg.ANULADO = 'N'), '$999,999,999,990.00')) as importe_devengado_format,
						CASE
							WHEN af.nro_factura IS NOT NULL THEN
								SUBSTR(LPAD (af.nro_factura, 12, 0),1,4) || '-' || SUBSTR(LPAD (af.nro_factura, 12, 0),5,12)
							ELSE ''
						END AS nro_factura_format
				FROM ad_facturas af
				JOIN kr_unidades_administracion kua ON af.cod_unidad_administracion = kua.cod_unidad_administracion
				JOIN ad_tipos_factura atf ON af.cod_tipo_factura = atf.cod_tipo_factura
				JOIN ad_proveedores ap ON af.id_proveedor = ap.id_proveedor
				LEFT JOIN ad_formas_pago afp ON af.COD_FORMA_PAGO = afp.COD_FORMA_PAGO
				LEFT JOIN kr_unidades_ejecutoras kue ON af.cod_unidad_ejecutora = kue.cod_unidad_ejecutora
				LEFT JOIN kr_expedientes ke ON af.id_expediente = ke.id_expediente
				LEFT JOIN kr_expedientes kep ON af.id_expediente_pago = kep.id_expediente
				WHERE $where
				ORDER BY id_factura DESC";
		//$datos = ctr_construir_sentencias::consultar_x_paginado($sql_sel, $filtro);
		$sql= dao_varios::paginador($sql_sel, null, $desde, $hasta, null, $orden);
        $datos = toba::db()->consultar($sql);
		return $datos;
	}

	public static function get_cantidad_facturas_compra($filtro = array()) {

		$where = self::get_where_facturas_compra($filtro);

		$sql_sel = "SELECT  COUNT(1) cantidad
					FROM ad_facturas af
					WHERE $where;";
		$datos = toba::db()->consultar_fila($sql_sel);
		if (isset($datos['cantidad'])) {
			return $datos['cantidad'];
		} else {
			return 0;
		}
		return $datos;
	}

	public static function get_where_facturas_compra($filtro = array()) {
		$where = " 1=1 ";

		if (isset($filtro['ui_fecha_desde']) && isset($filtro['ui_fecha_hasta']) && isset($filtro['ui_tipo_fecha'])){
			if ($filtro['ui_tipo_fecha'] == 'fecha_emision'){
				$where .= " AND af.fecha_emision between to_date('".$filtro['ui_fecha_desde']."','YYYY/MM/DD') and to_date('".$filtro['ui_fecha_hasta']."','YYYY/MM/DD')";
			}elseif($filtro['ui_tipo_fecha'] == 'fecha_emision_fac'){
				$where .= " AND af.fecha_emision_fac between to_date('".$filtro['ui_fecha_desde']."','YYYY/MM/DD') and to_date('".$filtro['ui_fecha_hasta']."','YYYY/MM/DD')";
			}elseif($filtro['ui_tipo_fecha'] == 'fecha_confirma'){
				$where .= " AND af.fecha_confirma between to_date('".$filtro['ui_fecha_desde']."','YYYY/MM/DD') and to_date('".$filtro['ui_fecha_hasta']."','YYYY/MM/DD')";
			}elseif($filtro['ui_tipo_fecha'] == 'fecha_carga'){
				$where .= " AND af.fecha_carga between to_date('".$filtro['ui_fecha_desde']."','YYYY/MM/DD') and to_date('".$filtro['ui_fecha_hasta']."','YYYY/MM/DD')";
			}elseif($filtro['ui_tipo_fecha'] == 'fecha_anula'){
				$where .= " AND af.fecha_anula between to_date('".$filtro['ui_fecha_desde']."','YYYY/MM/DD') and to_date('".$filtro['ui_fecha_hasta']."','YYYY/MM/DD')";
			}
			unset($filtro['ui_fecha_desde']);
			unset($filtro['ui_fecha_hasta']);
			unset($filtro['ui_tipo_fecha']);
		}else{
			if (isset($filtro['ui_fecha_desde']))
				unset($filtro['ui_fecha_desde']);
			if (isset($filtro['ui_fecha_hasta']))
				unset($filtro['ui_fecha_hasta']);
			if (isset($filtro['ui_tipo_fecha']))
				unset($filtro['ui_tipo_fecha']);
		}

		if (isset($filtro['razon_social'])) {
			$where .= "AND " . ctr_construir_sentencias::construir_translate_ilike('razon_social', $filtro['razon_social']);
			unset($filtro['razon_social']);
		}

		if (isset($filtro['cod_forma_pago']) && $filtro['cod_forma_pago'] == '-1') {
			$where .= " AND af.cod_forma_pago IS NULL ";
			unset($filtro['cod_forma_pago']);
		}

		if (isset($filtro['ids_comprobantes'])) {
			$where .= "AND af.id_factura IN (" . $filtro['ids_comprobantes'] . ") ";
			unset($filtro['ids_comprobantes']);
		}

		if (isset($filtro['para_devengar'])) {
			if ($filtro['para_devengar'] == '1') {
				$where .= " AND AF.CONFIRMADA = 'S'
							AND AF.ANULADA = 'N'
							AND AF.id_caja_chica IS NULL
							AND (	AF.cod_forma_pago IS NULL OR
									EXISTS (SELECT 1
											FROM AD_FACTURAS_VENCIMIENTOS FV
											WHERE FV.ID_FACTURA = AF.ID_FACTURA
											AND	NOT EXISTS (SELECT 1
															FROM AD_COMPROBANTES_GASTO CG
															WHERE FV.ID_FACTURA = CG.ID_FACTURA AND
															FV.ID_VENCIMIENTO = CG.ID_VENCIMIENTO
															AND CG.APROBADO ='S'
															AND CG.ANULADO='N')
											)
								)
							AND (SELECT NVL(SUM(acg.IMPORTE),0)
								FROM AD_COMPROBANTES_GASTO acg
								WHERE acg.ID_FACTURA = af.id_factura AND acg.APROBADO = 'S' AND acg.ANULADO = 'N') < AF.importe";
			}
			unset($filtro['para_devengar']);
		}

		if (isset($filtro['observaciones'])){
			$where .= " and upper(af.observaciones) like upper('%".$filtro['observaciones']."%')";
			unset($filtro['observaciones']);
		}

		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'af', '1=1');

		return $where;
	}

	static function get_tipos_facturas($filtro = array()) {
		$where = " 1=1 ";

		if (isset($filtro['positivo'])){
			$where .= " and l_atc.POSITIVO = '".$filtro['positivo']."'";
			unset($filtro['positivo']);
		}

		if (isset($filtro['tipo_comprobante'])){
			$where .= " and l_atc.tipo_comprobante = '".$filtro['tipo_comprobante']."'";
			unset($filtro['tipo_comprobante']);
		}

		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ADTIFA', '1=1');
		$sql_sel = "SELECT	ADTIFA.COD_TIPO_FACTURA COD_TIPO_FACTURA,
							ADTIFA.DESCRIPCION DESCRIPCION,
							ADTIFA.MASCARA_NUMERO MASCARA_NUMERO,
							ADTIFA.LETRA_FACTURA LETRA_FACTURA,
							ADTIFA.ACTIVO ACTIVO,
							ADTIFA.CAJA_CHICA CAJA_CHICA,
							ADTIFA.ID_TIPO_COMPROBANTE ID_TIPO_COMPROBANTE,
							L_ATC.POSITIVO L_ATC_POSITIVO
					FROM AD_TIPOS_FACTURA ADTIFA, AD_TIPOS_COMPROBANTE L_ATC
					WHERE ADTIFA.ID_TIPO_COMPROBANTE = L_ATC.ID_TIPO_COMPROBANTE
					AND $where
					ORDER BY COD_TIPO_FACTURA";
		$datos = toba::db()->consultar($sql_sel);
		return $datos;
	}

	static function get_formas_pago($filtro = array()) {
		$where = " 1=1 ";
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ADFOPA', '1=1');
		$sql_sel = "SELECT	ADFOPA.*
					FROM AD_FORMAS_PAGO ADFOPA
					WHERE $where
					ORDER BY COD_FORMA_PAGO";
		$datos = toba::db()->consultar($sql_sel);
		return $datos;
	}

	static function get_tipos_iva($filtro = array()) {
		$where = " 1=1 ";
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ADTIIV', '1=1');
		$sql_sel = "SELECT	ADTIIV.*
					FROM AD_TIPOS_IVA ADTIIV
					WHERE $where
					ORDER BY TIPO_IVA";
		$datos = toba::db()->consultar($sql_sel);
		return $datos;
	}

	static function get_usa_caja_chica_x_tipo_factura($cod_tipo_factura) {
		if (isset($cod_tipo_factura)) {
			$sql_sel = "SELECT	ADTIFA.CAJA_CHICA CAJA_CHICA
						FROM AD_TIPOS_FACTURA ADTIFA
						WHERE ADTIFA.cod_tipo_factura = " . quote($cod_tipo_factura) . ";";
			$datos = toba::db()->consultar_fila($sql_sel);
			if (isset($datos) && !empty($datos) && isset($datos['caja_chica'])) {
				return $datos['caja_chica'];
			} else {
				return 'N';
			}
		} else {
			return 'N';
		}
	}


	public static function get_factura_compra_x_id_factura($id_factura) {
		if (isset($id_factura)) {
			$sql_sel = "SELECT  af.*,
							af.fecha_confirma as fecha_confirmado,
							kua.descripcion as unidad_administracion,
							kue.descripcion as unidad_ejecutora,
							atf.descripcion as tipo_factura,
							ap.id_proveedor || ' - ' || af.razon_social || ' ('||pkg_varios.formatear_cuit(af.CUIT) ||')' as id_razon_social_cuit_proveedor,
							ke.nro_expediente as nro_expediente,
							kep.nro_expediente as nro_expediente_pago,
							to_char(af.fecha_emision, 'DD/MM/YYYY') as fecha_emision_format,
							to_char(af.fecha_emision, 'YYYY-MM-DD') as fecha_emision_format_iso,
							to_char(af.fecha_emision_fac, 'DD/MM/YYYY') as fecha_emision_fac_format,
							afp.DESCRIPCION as forma_pago,
							NVL(afp.cuotas, 'N') cuotas_format
					FROM ad_facturas af
					JOIN kr_unidades_administracion kua ON af.cod_unidad_administracion = kua.cod_unidad_administracion
					JOIN ad_tipos_factura atf ON af.cod_tipo_factura = atf.cod_tipo_factura
					JOIN ad_proveedores ap ON af.id_proveedor = ap.id_proveedor
					LEFT JOIN ad_formas_pago afp ON af.COD_FORMA_PAGO = afp.COD_FORMA_PAGO
					LEFT JOIN kr_unidades_ejecutoras kue ON af.cod_unidad_ejecutora = kue.cod_unidad_ejecutora
					LEFT JOIN kr_expedientes ke ON af.id_expediente = ke.id_expediente
					LEFT JOIN kr_expedientes kep ON af.id_expediente_pago = kep.id_expediente
					WHERE af.id_factura = " . quote($id_factura) . ";";
			$datos = toba::db()->consultar_fila($sql_sel);
			return $datos;
		} else {
			return array();
		}
	}

	static public function forma_pago_en_cuotas($cod_forma_pago) {
		if (isset($cod_forma_pago)) {
			$sql_sel = "SELECT	ADFOPA.*
						FROM AD_FORMAS_PAGO ADFOPA
						WHERE ADFOPA.cod_forma_pago = " . quote($cod_forma_pago) . ";";
			$datos = toba::db()->consultar_fila($sql_sel);
			if (isset($datos) && !empty($datos) && isset($datos['cuotas'])) {
				return (strcasecmp($datos['cuotas'], 'S') == 0) ? true : false;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	static public function valorizar_vencimiento_factura($id_factura, $id_vencimiento) {
		if (isset($id_factura) && isset($id_vencimiento)) {
			$sql = "BEGIN :resultado := PKG_AD_FACTURAS.VALORIZAR(:id_factura, :id_vencimiento); END;";

			$parametros = [
				['nombre' => 'resultado',
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 4000,
					'valor' => ''],
				['nombre' => 'id_factura',
					'tipo_dato' => PDO::PARAM_INT,
					'longitud' => 32,
					'valor' => $id_factura],
				['nombre' => 'id_vencimiento',
					'tipo_dato' => PDO::PARAM_INT,
					'longitud' => 32,
					'valor' => $id_vencimiento],
			];
			ctr_procedimientos::ejecutar_procedimiento('Error en la valoraci�n del vencimiento.',$sql,$parametros);
			toba::notificacion()->info('La valoraci�n del vencimiento finaliz� exitosamente.');
		}
	}

	public static function get_conceptos_administracion($nombre, $filtro = array()) {
		$where = ' 1=1 ';
		if (isset($nombre)) {
			$trans_cod_concepto = ctr_construir_sentencias::construir_translate_ilike('adcn.cod_concepto', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('adcn.descripcion', $nombre);
			$where .= " AND ($trans_cod_concepto OR $trans_descripcion)";
		}

		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'adcn', '1=1');
		$sql = "SELECT	ADCN.COD_CONCEPTO COD_CONCEPTO,
						ADCN.DESCRIPCION DESCRIPCION,
						ADCN.COD_PARTIDA COD_PARTIDA,
						ADCN.COD_CONCEPTO || ' - ' || ADCN.DESCRIPCION cod_concepto_descripcion
				FROM AD_CONCEPTOS ADCN
				WHERE $where
				ORDER BY COD_CONCEPTO;";
		return toba::db()->consultar($sql);
	}

	public static function get_concepto_descripcion_x_cod_concepto($cod_concepto) {
		if (isset($cod_concepto)) {
			$sql = "SELECT	ADCN.COD_CONCEPTO || ' - ' || ADCN.DESCRIPCION cod_concepto_descripcion
					FROM AD_CONCEPTOS ADCN
					WHERE cod_concepto = " . quote($cod_concepto) . ";";
			$resultado = toba::db()->consultar_fila($sql);
			if (isset($resultado) && !empty($resultado) && isset($resultado['cod_concepto_descripcion'])) {
				return $resultado['cod_concepto_descripcion'];
			} else {
				return '';
			}
		} else {
			return '';
		}
	}
	public static function get_descripcion_concepto($cod_concepto){
		$sql = "SELECT ADCN.DESCRIPCION
				 FROM AD_CONCEPTOS ADCN
				 WHERE ADCN.COD_CONCEPTO = ".quote($cod_concepto);
		$datos = toba::db()->consultar_fila($sql);
		return $datos['descripcion'];
	}

	public static function get_cod_partida_presupuestaria_x_cod_concepto($cod_concepto) {
		if (isset($cod_concepto)) {
			$sql = "SELECT	ADCN.COD_PARTIDA
					FROM AD_CONCEPTOS ADCN
					WHERE cod_concepto = " . quote($cod_concepto) . ";";
			$resultado = toba::db()->consultar_fila($sql);
			if (isset($resultado) && !empty($resultado) && isset($resultado['cod_partida'])) {
				return $resultado['cod_partida'];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	public static function get_vencimientos_factura_x_id_factura($id_factura) {
		if (isset($id_factura)) {
			$sql = "SELECT	afv.*
					FROM AD_FACTURAS_VENCIMIENTOS afv
					WHERE afv.id_factura = " . quote($id_factura) . ";";
			return toba::db()->consultar($sql);
		} else {
			return array();
		}
	}

	public static function get_calcula_impuesto_automaticamente($cod_impuesto, $cod_tipo_factura, $cod_concepto) {
		if (isset($cod_impuesto) && isset($cod_tipo_factura) && isset($cod_concepto)) {
			$sql = "SELECT	TIFAIM.automatico
					FROM	AD_TIPOS_FACTURA_IMPUESTO TIFAIM,
							AD_IMPUESTOS IMP,
							AD_CONCEPTOS_IMPUESTOS COIM
					WHERE TIFAIM.COD_IMPUESTO = IMP.COD_IMPUESTO
					AND IMP.COD_IMPUESTO = COIM.COD_IMPUESTO
					AND COIM.COD_CONCEPTO = " . quote($cod_concepto) . "
					AND imp.cod_impuesto = " . quote($cod_impuesto) . "
					AND TIFAIM.cod_tipo_factura = " . quote($cod_tipo_factura) . ";";
			$datos = toba::db()->consultar_fila($sql);
			if (isset($datos) && !empty($datos) && isset($datos['automatico'])) {
				return $datos['automatico'];
			} else {
				return 'N';
			}
		} else {
			return 'N';
		}
	}

	static public function cargar_detalle_recepcion_factura($id_factura, $nro_recepcion) {
		if (isset($id_factura) && isset($nro_recepcion)) {
			$id_vencimiento = self::get_primer_vencimiento_factura($id_factura);
			$sql = "BEGIN :resultado := PKG_AD_FACTURAS.IMPORTAR_RECEPCION(:nro_recepcion, :id_factura, :id_vencimiento); END;";

			$parametros = [
				['nombre' => 'resultado',
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 4000,
					'valor' => ''],
				['nombre' => 'nro_recepcion',
					'tipo_dato' => PDO::PARAM_INT,
					'longitud' => 32,
					'valor' => $nro_recepcion],
				['nombre' => 'id_factura',
					'tipo_dato' => PDO::PARAM_INT,
					'longitud' => 32,
					'valor' => $id_factura],
				['nombre' => 'id_vencimiento',
					'tipo_dato' => PDO::PARAM_INT,
					'longitud' => 32,
					'valor' => $id_vencimiento],
			];
			ctr_procedimientos::ejecutar_procedimiento('Error en la importaci�n de los detalles de la recepci�n.',$sql,$parametros);
			toba::notificacion()->info('La importaci�n de los detalles de la recepci�n finaliz� exitosamente.');
		}
	}

	public static function get_primer_vencimiento_factura($id_factura) {
		if (isset($id_factura)) {
			$datos_fac = self::get_factura_compra_x_id_factura($id_factura);
			if (isset($datos_fac) && !empty($datos_fac)) {
				if ((isset($datos_fac['cod_forma_pago']) && !empty($datos_fac['cod_forma_pago']) && isset($datos_fac['cuotas_format']) && $datos_fac['cuotas_format'] == 'S') || (!isset($datos_fac['cod_forma_pago']) || empty($datos_fac['cod_forma_pago']))) {
					$sql = "SELECT	MIN(afv.id_vencimiento) id_vencimiento
							FROM AD_FACTURAS_VENCIMIENTOS afv
							WHERE afv.id_factura = " . quote($id_factura) . ";";
					$datos = toba::db()->consultar_fila($sql);
					if (isset($datos) && !empty($datos) && isset($datos['id_vencimiento'])) {
						return $datos['id_vencimiento'];
					}
				}
			}
		}
		return '';
	}

	public static function get_importes_encabezado_factura_compra($id_factura) {
		if (isset($id_factura)) {
			$sql_sel = "SELECT  af.importe_neto,
								af.importe_impuestos,
								af.importe,
								(SELECT NVL(SUM(IMPORTE),0)
								FROM AD_COMPROBANTES_GASTO acg
								WHERE acg.ID_FACTURA = af.id_factura AND acg.APROBADO = 'S' AND acg.ANULADO = 'N') importe_devengado
					FROM ad_facturas af
					WHERE af.id_factura = " . quote($id_factura) . ";";
			$datos = toba::db()->consultar_fila($sql_sel);
			return $datos;
		} else {
			return array();
		}
	}

	static public function confirmar_factura_compra($id_factura) {
		if (isset($id_factura)) {
			$sql = "BEGIN :resultado := PKG_AD_FACTURAS.confirmar_factura(:id_factura); END;";
			$parametros = [
				   ['nombre' => 'resultado',
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 4000,
					'valor' => ''],
				   ['nombre' => 'id_factura',
					'tipo_dato' => PDO::PARAM_INT,
					'longitud' => 32,
					'valor' => $id_factura]];

			ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
			toba::notificacion()->info('La factura de compra se confirm� exitosamente.');
		}
	}

	static public function desconfirmar_factura_compra($id_factura) {
		if (isset($id_factura)) {
			$sql = "BEGIN :resultado := PKG_AD_FACTURAS.desaprobar_factura(:id_factura); END;";
			$parametros = [
				   ['nombre' => 'resultado',
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 4000,
					'valor' => ''],
				   ['nombre' => 'id_factura',
					'tipo_dato' => PDO::PARAM_INT,
					'longitud' => 32,
					'valor' => $id_factura]];

			ctr_procedimientos::ejecutar_procedimiento('Error en la desconfirmaci�n de la factura de compra.',$sql,$parametros);
			toba::notificacion()->info('La factura de compra se desconfirm� exitosamente.');
		}
	}

	static public function anular_factura_compra($id_factura) {
		if (isset($id_factura)) {
			$sql = "BEGIN :resultado := PKG_AD_FACTURAS.anular_factura(:id_factura); END;";

			$parametros = [
				   ['nombre' => 'resultado',
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 4000,
					'valor' => ''],
				   ['nombre' => 'id_factura',
					'tipo_dato' => PDO::PARAM_INT,
					'longitud' => 32,
					'valor' => $id_factura]];

			ctr_procedimientos::ejecutar_procedimiento('Error en la anulaci�n de la factura de compra.',$sql,$parametros);
			toba::notificacion()->info('La factura de compra se anul� exitosamente.');
		}
	}

	static public function devengar_factura_compra($id_factura, $datos) {
		if (isset($id_factura) && isset($datos) && !empty($datos) && isset($datos['fecha_comprobante']) && isset($datos['cod_tipo_comprobante'])) {

			$sql = "BEGIN :resultado := PKG_AD_FACTURAS.devengado_automatico(:cod_tipo_comprobante, :id_factura, :id_compromiso, :id_vencimiento, :fecha_comprobante, :id_expediente, :id_expediente_pago, :id_comprobante_gasto); END;";

			$id_comprobante_gasto = "";
			if (!isset($datos['id_compromiso']) || empty($datos['id_compromiso'])) {
				$datos['id_compromiso'] = "";
			}
			if (!isset($datos['id_expediente']) || empty($datos['id_expediente'])) {
				$datos['id_expediente'] = "";
			}
			if (!isset($datos['id_expediente_pago']) || empty($datos['id_expediente_pago'])) {
				$datos['id_expediente_pago'] = "";
			}
			if (!isset($datos['id_vencimiento']) || empty($datos['id_vencimiento'])) {
				$datos['id_vencimiento'] = "";
			}

			$parametros = [
				['nombre' => 'resultado',
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 4000,
					'valor' => ''],
				['nombre' => 'cod_tipo_comprobante',
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 6,
					'valor' => $datos['cod_tipo_comprobante']],
				['nombre' => 'id_factura',
					'tipo_dato' => PDO::PARAM_INT,
					'longitud' => 32,
					'valor' => $id_factura],
				['nombre' => 'id_compromiso',
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 32,
					'valor' => $datos['id_compromiso']],
				['nombre' => 'id_vencimiento',
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 32,
					'valor' => $datos['id_vencimiento']],
				['nombre' => 'fecha_comprobante',
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 32,
					'valor' => $datos['fecha_comprobante']],
				['nombre' => 'id_expediente',
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 32,
					'valor' => $datos['id_expediente']],
				['nombre' => 'id_expediente_pago',
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 32,
					'valor' => $datos['id_expediente_pago']],
				['nombre' => 'id_comprobante_gasto',
					'tipo_dato' => PDO::PARAM_STR,
					'longitud' => 32,
					'valor' => $id_comprobante_gasto],
			];
			$resultado = ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);

			return $resultado[8]['valor'];

		}
	}


	static public function generar_orden_pago($id_factura, $id_comprobante_gasto, $fecha_comprobante) {
		if (isset($id_factura) && isset($id_comprobante_gasto) && isset($fecha_comprobante)) {

			$sql = "BEGIN :resultado := PKG_KR_TRANS_AUTO.trans_auto_generar_orden_pago(:id_comprobante_gasto,:fecha_comprobante, :id_orden_pago); END;";

			$parametros = [
				['nombre' =>'resultado',
							'tipo_dato' => PDO::PARAM_STR,
							'longitud' => 4000,
							'valor' => ''],
				['nombre' =>'id_comprobante_gasto',
							'tipo_dato' => PDO::PARAM_INT,
							'longitud' => 32,
							'valor' => $id_comprobante_gasto],
				['nombre' =>'fecha_comprobante',
							'tipo_dato' => PDO::PARAM_INT,
							'longitud' => 32,
							'valor' => $fecha_comprobante],
				['nombre' =>'id_orden_pago',
							'tipo_dato' => PDO::PARAM_INT,
							'longitud' => 32,
							'valor' => ''],
			];

			$resultado = ctr_procedimientos::ejecutar_procedimiento('Error en la generaci�n de la orden de pago desde la factura de compra.',$sql, $parametros);

			if (isset($resultado[3]['valor'])){
				return $resultado[3]['valor'];
			}
		}
	}
	public static function get_tipo_comprobante_x_cod_tipo_factura($cod_tipo_factura) {
		if (isset($cod_tipo_factura)) {
			$sql_sel = "SELECT  atc.*
					FROM ad_tipos_factura atf
					JOIN ad_tipos_comprobante atc ON atf.id_tipo_comprobante = atc.id_tipo_comprobante
					WHERE atf.cod_tipo_factura = " . quote($cod_tipo_factura) . ";";
			$datos = toba::db()->consultar_fila($sql_sel);
			return $datos;
		} else {
			return array();
		}
	}

	static function get_datos_tipo_factura_x_tipo_factura($cod_tipo_factura) {
		if (isset($cod_tipo_factura)) {
			$sql_sel = "SELECT	ADTIFA.*,
								atc.positivo,
								atc.numeracion_aut,
								atc.tipo_comprobante
						FROM AD_TIPOS_FACTURA ADTIFA
						JOIN ad_tipos_comprobante atc ON adtifa.id_tipo_comprobante = atc.id_tipo_comprobante
						WHERE ADTIFA.cod_tipo_factura = " . quote($cod_tipo_factura) . ";";
			$datos = toba::db()->consultar_fila($sql_sel);
			return $datos;
		} else {
			return array();
		}
	}

	public static function get_existe_numero_factura($nro_factura, $cod_tipo_factura, $id_proveedor, $cuit, $id_tipo_comprobante, $tiene_cai_cae, $id_factura = null) {
		if (isset($nro_factura) && isset($cod_tipo_factura) && isset($id_proveedor) && isset($cuit)) {
			$id_factura = intval($id_factura);
			$sql = "SELECT COUNT (1) as cantidad
					FROM AD_FACTURAS F,
						AD_TIPOS_FACTURA TF
					WHERE F.COD_TIPO_FACTURA = TF.COD_TIPO_FACTURA
					AND F.ID_PROVEEDOR = " . quote($id_proveedor) . "
					/*AND TF.COD_TIPO_FACTURA = " . quote($cod_tipo_factura) . "*/
					AND F.ID_FACTURA <> " . quote($id_factura) . "
					AND TF.ID_TIPO_COMPROBANTE = ". quote($id_tipo_comprobante) ."
					AND TF.LETRA_FACTURA = (SELECT TF1.LETRA_FACTURA
											FROM AD_TIPOS_FACTURA TF1
											WHERE TF1.COD_TIPO_FACTURA = " . quote($cod_tipo_factura) . ")
					AND f.tiene_cai_cae = " . quote($tiene_cai_cae) . "
					AND F.NRO_FACTURA = " . quote($nro_factura) . ";";
                       
			$datos = toba::db()->consultar_fila($sql);
			$cuit_generico = dao_kr_general::get_cuit_generico();
			if (isset($datos) && !empty($datos) && isset($datos['cantidad']) && $datos['cantidad'] > 0 && isset($cuit_generico) && strcasecmp($cuit, $cuit_generico) <> 0) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public static function get_impuesto_grabado_detalle($cod_tipo_factura) {
		if (isset($cod_tipo_factura)) {
			$sql = "SELECT COUNT (1) as cantidad
					FROM AD_TIPOS_FACTURA_IMPUESTO
					WHERE COD_TIPO_FACTURA = " . quote($cod_tipo_factura) . "
					AND AUTOMATICO = 'S';";
			$datos = toba::db()->consultar_fila($sql);
			if (isset($datos) && !empty($datos) && isset($datos['cantidad']) && $datos['cantidad'] == 0) {
				return 'N';
			} else {
				return 'S';
			}
		} else {
			return 'N';
		}
	}

	public static function get_proximo_vencimiento_factura($id_factura) {
		if (isset($id_factura)) {
			$sql = "select id_vencimiento
					from ad_facturas_vencimientos fave
					where id_factura = " . quote($id_factura) . "
					and not exists(	select 1
									from ad_facturas_det fade
									where fade.id_factura = fave.id_factura and fade.id_vencimiento = fave.id_vencimiento)
					ORDER BY id_vencimiento ASC;";
			$datos = toba::db()->consultar_fila($sql);
			if (isset($datos) && !empty($datos) && isset($datos['id_vencimiento'])) {
				return $datos['id_vencimiento'];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	public static function get_min_fecha_vencimiento_factura($id_factura) {
		if (isset($id_factura)) {
			$sql = "SELECT	TO_CHAR(MIN(afv.fecha_vencimiento), 'YYYY-MM-DD') fecha_vencimiento
					FROM AD_FACTURAS_VENCIMIENTOS afv
					WHERE afv.id_factura = " . quote($id_factura) . ";";
			$datos = toba::db()->consultar_fila($sql);
			if (isset($datos) && !empty($datos) && isset($datos['fecha_vencimiento'])) {
				return $datos['fecha_vencimiento'];
			}
		}
		return '';
	}

	static public function get_id_nro_fecha_factura_x_id($id_factura) {
		if (isset($id_factura)) {
			$sql = "SELECT ADFA.*,
						  ADFA.ID_FACTURA ||' - '||
						  CASE
							  WHEN ADFA.nro_factura IS NOT NULL THEN
								  SUBSTR(LPAD (ADFA.nro_factura, 12, 0),1,4) || '-' || SUBSTR(LPAD (ADFA.nro_factura, 12, 0),5,12)
							  ELSE ''
						  END ||'  '|| to_char(ADFA.FECHA_EMISION,'DD/MM/YYYY') ||' - $'|| ADFA.IMPORTE as lov_descripcion
				  FROM AD_FACTURAS ADFA
				  WHERE ADFA.ID_FACTURA = " . quote($id_factura) . ";";
			$datos = toba::db()->consultar_fila($sql);
			if (isset($datos) && !empty($datos) && isset($datos['lov_descripcion'])) {
				return $datos['lov_descripcion'];
			} else {
				return '';
			}
		} else {
			return '';
		}
	}

	static public function get_items_factura ($id_factura){
		$sql = "SELECT adfd.id_detalle, adfd.id_detalle ||' - '|| adcon.descripcion lov_descripcion
		          FROM AD_FACTURAS_DET adfd, AD_CONCEPTOS adcon
		          WHERE adfd.cod_concepto = adcon.cod_concepto and adfd.id_factura = ".quote($id_factura);
		return toba::db()->consultar($sql);
	}

	static public function get_item_factura ($id_factura, $id_detalle){
		$sql = "SELECT adfd.*, adco.descripcion
				  FROM ad_facturas_det adfd, ad_conceptos adco
				 WHERE adfd.cod_concepto = adco.cod_concepto and adfd.id_factura = ".quote($id_factura)." and adfd.id_detalle = ".quote($id_detalle);
		return toba::db()->consultar_fila($sql);
	}


	public static function get_lov_factura_x_id ($id_factura)
	{
		$sql = "SELECT  adfa.*, adfa.id_factura ||' - '|| adpro.razon_social ||' '||adfa.fecha_emision ||' - ('|| adfa.importe || ')' as lov_descripcion
				FROM AD_FACTURAS ADFA, ad_proveedores adpro
				WHERE adfa.id_proveedor = adpro.id_proveedor and adfa.id_factura = ".quote($id_factura);
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}

	static public function get_lov_factura_x_nombre($nombre, $filtro = array()) {
		if (isset($nombre)) {
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('ADFA.id_factura', $nombre);
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('ADFA.nro_factura', $nombre);
			$where = "($trans_codigo OR $trans_nro)";
		} else {
			$where = '1=1';
		}

		if (isset($filtro['para_ordenes_pago'])) {
			if (empty($filtro['id_exp'])) {
				$filtro['id_exp'] = 'null';
			}
			if (empty($filtro['id_exp_pago'])) {
				$filtro['id_exp_pago'] = 'null';
			}
			if (empty($filtro['cod_uni_ejecutora'])) {
				$filtro['cod_uni_ejecutora'] = 'null';
			}
			$where .= " AND (	adfa.confirmada = 'S'
						AND adfa.anulada = 'N'
						AND adfa.cod_unidad_administracion = " . $filtro['cod_uni_admin'] . "
						AND pkg_ad_facturas.factura_tiene_saldo (adfa.id_factura) = 'S'
						AND (   adfa.id_expediente = " . $filtro['id_exp'] . "
							OR adfa.id_expediente IS NULL
							OR " . $filtro['id_exp'] . " IS NULL
							)
						AND (   adfa.id_expediente_pago = " . $filtro['id_exp_pago'] . "
							OR adfa.id_expediente_pago IS NULL
							OR " . $filtro['id_exp_pago'] . " IS NULL
							)
						AND EXISTS (
							  SELECT 1
								FROM ad_proveedores pro, kr_cuentas_corriente cuco
								WHERE pro.id_proveedor = cuco.id_proveedor
								 AND cuco.id_cuenta_corriente =" . $filtro['id_cta_cte'] . "
								 AND pro.id_proveedor = adfa.id_proveedor)
						AND pkg_ad_facturas.comprobante_gasto (adfa.id_factura) = 'N'
						AND pkg_ad_facturas.saldo_a_ordenar (adfa.id_factura) > 0
						AND ( " . $filtro['cod_uni_ejecutora'] . " IS NULL
							OR adfa.cod_unidad_ejecutora =" . $filtro['cod_uni_ejecutora'] . ")
						AND adfa.fecha_emision <= to_date(substr('" . $filtro['fecha_orden_pago'] . "',1,10),'yyyy-mm-dd'))";
			unset($filtro['para_ordenes_pago']);
			unset($filtro['cod_uni_admin']);
			unset($filtro['id_exp']);
			unset($filtro['id_exp_pago']);
			unset($filtro['id_cta_cte']);
			unset($filtro['cod_uni_ejecutora']);
			unset($filtro['fecha_orden_pago']);
		}

		if (isset($filtro['asociar_recepcion_compra'])) {
			$where .= " AND (pkg_general.valor_parametro ('CO_FILTRA_DEVENGADAS') = 'S' OR
						((SELECT NVL (SUM (coga.importe), 0)
							 FROM ad_comprobantes_gasto coga
							WHERE coga.id_factura = adfa.id_factura
							  AND coga.aprobado = 'S'
							  AND coga.anulado = 'N') = 0
							AND pkg_general.valor_parametro ('CO_FILTRA_DEVENGADAS') = 'N'))";
			unset($filtro['asociar_recepcion_compra']);
		}

		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'ADFA', '1=1');

		$sql = "SELECT  adfa.*, adfa.id_factura ||' - '|| adpro.razon_social ||' '||adfa.fecha_emision ||' - ('|| adfa.importe || ')' as lov_descripcion
				FROM AD_FACTURAS ADFA, ad_proveedores adpro
				WHERE adfa.id_proveedor = adpro.id_proveedor and $where
				ORDER BY lov_descripcion ASC;";

		$datos = toba::db()->consultar($sql);

		return $datos;
	}


	public static function get_facturas_segun_consulta($filtro = [])
	{
		if (!isset($filtro['p_tipo_cc']))
		{
			return [];
		}
		$tipo_cc = $filtro['p_tipo_cc'];

		unset($filtro['p_tipo_cc']);

		$where2 = '';
		if (isset($filtro['p_inicio']) && isset($filtro['p_fin']))
		{
			$where2 .= " AND factura.nro_cuenta_corriente BETWEEN {$filtro['p_inicio']} AND {$filtro['p_fin']}";
		}
		unset($filtro['p_inicio']);
		unset($filtro['p_fin']);

		if (isset($filtro['p_saldo_impago']) && $filtro['p_saldo_impago'] === 'S')
		{
			$where2 .= " AND saldo_impago != 0";

		}
		unset($filtro['p_saldo_impago']);
		if (isset($filtro['p_fregistro_d']) && isset($filtro['p_fregistro_h']))
		{
			$where2 .= " AND TRUNC(factura.fecha_registro) BETWEEN
							TO_DATE ('{$filtro['p_fregistro_d']}', 'RRRR/MM/DD')
							AND TO_DATE('{$filtro['p_fregistro_h']}', 'RRRR/MM/DD')";

			unset($filtro['p_fregistro_d']);
			unset($filtro['p_fregistro_h']);
		}
		if (isset($filtro['p_femision_d']) && isset($filtro['p_femision_h']))
		{
			$where2 .= " AND TRUNC(factura.fecha_emision) BETWEEN
								TO_DATE('{$filtro['p_femision_d']}', 'RRRR/MM/DD')
								AND TO_DATE('{$filtro['p_femision_h']}', 'RRRR/MM/DD')";

			unset($filtro['p_femision_d']);
			unset($filtro['p_femision_h']);
		}
		if (isset($filtro['p_programa']))
		{
			$where2 .= " AND INSTR(factura.programas, {$filtro['p_programa']}) > 0";

			unset($filtro['p_programa']);
		}
		$where1 = ctr_construir_sentencias::get_where_filtro($filtro, 'factura');
		$sql = "
			SELECT
				factura.*,
				(factura.devengado - factura.saldo) pagado,
				(factura.bruto - (factura.devengado - factura.saldo)) adeudado
			FROM (
				SELECT
					fa.cod_unidad_administracion p_ua,
					fa.id_proveedor || ' - ' || pr.razon_social proveedor,
					(	SELECT rv_meaning
						FROM CG_REF_CODES
						WHERE
							rv_domain = 'KR_TIPO_CUENTA_CORRIENTE'
							AND rv_low_value = cuco.tipo_cuenta_corriente
					) tipo_cuenta_corriente,
					cuco.nro_cuenta_corriente,
					tifa.letra_factura letra_factura,
					pkg_mascaras.aplicar_mascara(
						fa.nro_factura,
						tifa.mascara_numero
					) nro_factura,
					TRUNC(fa.fecha_emision) fecha_registro,
					TRUNC(fa.fecha_emision_fac) fecha_emision,
					expc.nro_expediente nro_expediente_compra,
					expp.nro_expediente nro_expediente_pago,
					fa.importe_neto neto,
					fa.importe_impuestos impuestos,
					fa.importe bruto,

					(	SELECT NVL(SUM(coga.importe), 0)
						FROM ad_comprobantes_gasto coga
						WHERE
							coga.id_factura = fa.id_factura
							AND coga.aprobado = 'S'
							AND coga.anulado = 'N'
					) devengado,

					(	SELECT
							NVL(SUM(pkg_kr_transacciones.saldo_transaccion(
								coga.id_transaccion,
								coga.id_cuenta_corriente,
								TRUNC(SYSDATE)
							)), 0)
						FROM ad_comprobantes_gasto coga
						WHERE
							coga.id_factura = fa.id_factura
							AND coga.aprobado = 'S'
							AND coga.anulado = 'N'
					) saldo,
					(fa.importe_neto
						- ((
							SELECT NVL(SUM(coga.importe), 0)
							FROM ad_comprobantes_gasto coga
							WHERE
								coga.id_factura = fa.id_factura
								AND coga.aprobado = 'S'
								AND coga.anulado = 'N'
						)
						- (	SELECT
								NVL(SUM(pkg_kr_transacciones.saldo_transaccion(
									coga.id_transaccion,
									coga.id_cuenta_corriente,
									TRUNC(SYSDATE)
								)), 0)
							FROM ad_comprobantes_gasto coga
							WHERE
								coga.id_factura = fa.id_factura
								AND coga.aprobado = 'S'
								AND coga.anulado = 'N'
						))
					) saldo_impago,

					concat_all(
						'SELECT DISTINCT pr.cod_programa
						FROM
							ad_facturas_det de,
							ad_facturas_imp im,
							pr_programas pr
						WHERE
							de.id_factura = im.id_factura
							AND de.id_detalle = im.id_detalle
							AND im.id_programa = pr.id_programa
							AND de.id_factura = ' || fa.id_factura,
						' '
					) programas

				FROM
					kr_cuentas_corriente cuco,
					ad_proveedores pr,
					ad_facturas fa,
					ad_tipos_factura tifa,
					kr_expedientes expc,
					kr_expedientes expp
				WHERE
					cuco.id_proveedor = fa.id_proveedor
					AND cuco.tipo_cuenta_corriente = '$tipo_cc'
					AND fa.cod_unidad_administracion = cuco.cod_unidad_administracion
					AND fa.id_proveedor = pr.id_proveedor
					AND fa.cod_tipo_factura = tifa.cod_tipo_factura
					and fa.id_expediente = expc.id_expediente (+)
					and fa.id_expediente_pago = expp.id_expediente (+)
					AND fa.confirmada = 'S'
					AND fa.anulada = 'N'
			) factura
			WHERE
				$where1
				$where2
		";

		return toba::db()->consultar($sql);
	}

	public static function get_comprobantes_gastos ($id_factura){
		if (!is_null($id_factura)){
			$sql = "SELECT adcomg.id_comprobante_gasto, adcomg.nro_comprobante,
					       TO_CHAR (adcomg.fecha_comprobante, 'DD/MM/YYYY') fecha_comprobante,
					       adcomg.importe
					  FROM ad_comprobantes_gasto adcomg
					 WHERE adcomg.id_factura =".quote($id_factura);
			return toba::db()->consultar($sql);
		}else{
			return array();
		}
	}

	static public function generar_imputacion_costos($id_factura)
	{
		$sql = "BEGIN :resultado := pkg_ad_facturas.generar_imputacion_costos(:id_factura); END;";
		$parametros = [
			['nombre' =>'resultado',
						'tipo_dato' => PDO::PARAM_STR,
						'longitud' => 4000,
						'valor' => ''],
			['nombre' =>'id_factura',
						'tipo_dato' => PDO::PARAM_INT,
						'longitud' => 32,
						'valor' => $id_factura],
		];
		$resultado = ctr_procedimientos::ejecutar_procedimiento('Error Generando Imputacion de Costos',$sql, $parametros);
		return $resultado[0]['valor'];
	}
	static public function generar_movimientos_costos($id_factura)
	{
		$sql = "BEGIN :resultado := pkg_ad_facturas.generar_movimientos_costos(:id_factura); END;";
		$parametros = [
			['nombre' =>'resultado',
						'tipo_dato' => PDO::PARAM_STR,
						'longitud' => 4000,
						'valor' => ''],
			['nombre' =>'id_factura',
						'tipo_dato' => PDO::PARAM_INT,
						'longitud' => 32,
						'valor' => $id_factura],
		];
		$resultado = ctr_procedimientos::ejecutar_procedimiento(null,$sql, $parametros);
		return $resultado[0]['valor'];
	}

	static public function cargar_centros_costos_defecto($id_factura, $id_detalle)
	{
		$sql = "BEGIN :resultado := pkg_ad_facturas.cargar_centros_costos_defecto(:id_factura); END;";
		$parametros = [
			['nombre' =>'resultado',
						'tipo_dato' => PDO::PARAM_STR,
						'longitud' => 4000,
						'valor' => ''],
			['nombre' =>'id_factura',
						'tipo_dato' => PDO::PARAM_INT,
						'longitud' => 32,
						'valor' => $id_factura],
			['nombre' =>'id_detalle',
						'tipo_dato' => PDO::PARAM_INT,
						'longitud' => 32,
						'valor' => $id_detalle],
		];
		$resultado = ctr_procedimientos::ejecutar_procedimiento(null,$sql, $parametros);
		return $resultado[0]['valor'];
	}

	static public function comprobante_gasto($id_factura)
	{
		$sql = "SELECT pkg_ad_facturas.comprobante_gasto($id_factura) comprobante_gasto FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['comprobante_gasto'];
	}

	static public function get_compromiso_desde_recepcion ($id_factura)
	{
		$sql = "SELECT adco.id_compromiso, adf.id_factura, coo.nro_orden, coo.anio,
				       adco.fecha_comprobante, adco.importe,
				       pkg_kr_ejercicios.retornar_ejercicio (adco.fecha_comprobante)
				  FROM ad_facturas adf,
				       co_recepciones cor,
				       co_ordenes coo,
				       co_ordenes_compromisos cooc,
				       ad_compromisos adco
				 WHERE adf.nro_recepcion = cor.nro_recepcion
				   AND cor.nro_orden = coo.nro_orden
				   AND coo.nro_orden = cooc.nro_orden
				   AND cooc.id_compromiso = adco.id_compromiso
				   AND adco.aprobado = 'S'
				   AND adco.anulado = 'N'
				   AND pkg_kr_ejercicios.retornar_ejercicio (adco.fecha_comprobante) =
                                pkg_kr_ejercicios.retornar_ejercicio (sysdate)
   				   AND saldo_compromiso(adco.id_compromiso) > 0
				   AND adf.id_factura = ".quote($id_factura);
		$datos = toba::db()->consultar($sql);
		if (!empty($datos[0]['id_compromiso']) && !is_null($datos[0]['id_compromiso']))
			return $datos[0]['id_compromiso'];
		return null;
	}

	static public function copiar_factura ($id_factura, $nro_factura)
	{
		$sql = "BEGIN
					:resultado := pkg_ad_facturas.copiar_factura(:id_factura, :nro_factura, :usuario,:nuevo_id);
				END;";

		$usuario = strtoupper(toba::usuario()->get_id());

		$parametros = [
			['nombre' =>'resultado',
						'tipo_dato' => PDO::PARAM_STR,
						'longitud' => 4000,
						'valor' => ''],
			['nombre' =>'id_factura',
						'tipo_dato' => PDO::PARAM_INT,
						'longitud' => 32,
						'valor' => $id_factura],
			['nombre' =>'nro_factura',
						'tipo_dato' => PDO::PARAM_INT,
						'longitud' => 32,
						'valor' => $nro_factura],
			['nombre' =>'usuario',
						'tipo_dato' => PDO::PARAM_STR,
						'longitud' => 200,
						'valor' => $usuario],
			['nombre' =>'nuevo_id',
						'tipo_dato' => PDO::PARAM_INT,
						'longitud' => 32,
						'valor' => ''],
		];
		$resultado = ctr_procedimientos::ejecutar_procedimiento(null,$sql, $parametros);
		return $resultado[4]['valor'];
	}

	public static function get_rendicion_factura($filtro = [])
    {
        $datos = [];
        $where = " 1=1 ";

        if (isset($filtro)) {
            $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'rf', '1=1');
        }

        $sql = "
            SELECT  rf.*, fa.*, rf.importe importe_rend
            FROM
                AD_REND_FACT rf
                JOIN AD_FACTURAS fa on (rf.id_factura = fa.id_factura)
            WHERE
                $where
        ";
        toba::logger()->debug('SQL RENDICIONES FACTURAS ************* ' . $sql);
        $datos = toba::db()->consultar($sql);
        return $datos;
    }
}
