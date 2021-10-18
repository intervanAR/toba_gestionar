<?php

class dao_transacciones
{
	static public function get_transacciones($filtro= array()){

        $where= " 1=1 ";
		if (isset($filtro['ids_comprobantes'])) {
			$where .= " AND kt.id_transaccion IN (" . $filtro['ids_comprobantes'] . ") ";
			unset($filtro['ids_comprobantes']);
		}

		if (isset($filtro['id_comprobante_origen'])) {
			$where .= " AND PKG_KR_TRANSACCIONES.ID_COMPROBANTE_TRANSACCION(kt.ID_TRANSACCION) = " . quote($filtro['id_comprobante_origen']);
			unset($filtro['id_comprobante_origen']);
		}
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'kt', '1=1');

        $sql = "SELECT	kt.*,
						to_char(kt.fecha_transaccion, 'dd/mm/yyyy') fecha_transaccion_format,
						to_char(kt.fecha_transaccion_anulada, 'dd/mm/yyyy') fecha_transaccion_anul_format,
						'S' confirmada,
						ktt.cod_tipo_transaccion || ' - ' || ktt.descripcion tipo_transaccion,
						PKG_KR_TRANSACCIONES.ID_COMPROBANTE_TRANSACCION(kt.ID_TRANSACCION) id_comprobante_origen
                FROM KR_TRANSACCION kt
				JOIN KR_TIPOS_TRANSACCION ktt ON (kt.cod_tipo_transaccion = ktt.cod_tipo_transaccion)
				WHERE  $where
				ORDER BY id_transaccion DESC;";

        $datos = toba::db()->consultar($sql);
		foreach ($datos as $clave => $dato) {
			$datos[$clave]['des_origen_transaccion'] = self::get_descripcion_origen_transaccion($dato['origen_transaccion']);
		}
        return $datos;
    }


	public static function get_origenes_transaccion()
	{
		$origenes = array();
		$origenes[] = array('origen_transaccion' => 'RCO', 'descripcion' => 'Recibo cobro');
		$origenes[] = array('origen_transaccion' => 'COB', 'descripcion' => 'Cobro');
		$origenes[] = array('origen_transaccion' => 'APC', 'descripcion' => 'Aplicacion Cobro');
		$origenes[] = array('origen_transaccion' => 'CRE', 'descripcion' => 'Comprobante Recurso');
		$origenes[] = array('origen_transaccion' => 'CIR', 'descripcion' => 'Cambio Imputacion Recurso');
		$origenes[] = array('origen_transaccion' => 'CGA', 'descripcion' => 'Comprobante Gasto');
		$origenes[] = array('origen_transaccion' => 'CIG', 'descripcion' => 'Cambio Imputacion Gasto');
		$origenes[] = array('origen_transaccion' => 'RPA', 'descripcion' => 'Recibo Pago');
		$origenes[] = array('origen_transaccion' => 'PAG', 'descripcion' => 'Pago');
		$origenes[] = array('origen_transaccion' => 'CBA', 'descripcion' => 'Comprobante Bancos');
		$origenes[] = array('origen_transaccion' => 'COM', 'descripcion' => 'Comprobante Compromiso');
		$origenes[] = array('origen_transaccion' => 'PRE', 'descripcion' => 'Comprobante Preventivo');
		$origenes[] = array('origen_transaccion' => 'RBA', 'descripcion' => 'Recibo de Banco');
		$origenes[] = array('origen_transaccion' => 'APP', 'descripcion' => 'Aplicacion Pago');
		$origenes[] = array('origen_transaccion' => 'RAN', 'descripcion' => 'Rendicion Anticipo');
		$origenes[] = array('origen_transaccion' => 'RCC', 'descripcion' => 'Rendicion Caja Chica');
		$origenes[] = array('origen_transaccion' => 'PDE', 'descripcion' => 'Perimir Deuda');
		return $origenes;
	}

	static public function get_descripcion_origen_transaccion($origen_transaccion)
	{
	    $datos = self::get_origenes_transaccion();
	    foreach ($datos as $origen) {
			if ($origen['origen_transaccion'] == $origen_transaccion) {
				return $origen['descripcion'];
			}
		}
	    return '';
	}

	

	static public function get_tipos_transacciones($filtro = array())
	{
        $where = " 1=1 ";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ktt', '1=1');
        $sql_sel = "SELECT ktt.*,
					       decode(ktt.GENERA_COMPROB_PRESUP_INGRESO, 'S','Si','No') gen_comprob_presup_ingreso_f,
					       decode(ktt.GENERA_COMPROB_PRESUP_EGRESO, 'S','Si','No') gen_comprob_presup_egreso_f,
					       decode(ktt.GENERA_COMPROB_EXTRAP_INGRESO, 'S','Si','No') gen_comprob_extrap_ingreso_f,
					       decode(ktt.GENERA_COMPROB_EXTRAP_EGRESO, 'S','Si','No') gen_comprob_extrap_egreso_f,
					       decode(ktt.ORIGEN_TRANSACCION , 'S','Si','No') origen_transaccion_f,
					       decode(ktt.GENERA_MOVIMIENTO_CUENTA , 'S','Si','No') gen_movimiento_cuenta_f,
					       decode(ktt.GENERA_APLICACION_CUENTA , 'S','Si','No') gen_APLICACION_cuenta_f,
					       decode(ktt.GENERA_ASIENTO_CONTABILIDAD, 'S','Si','No') gen_asiento_contabilidad_f,
					       decode(ktt.CONTRASIENTO_CUANDO_ANULA , 'S','Si','No') contrasiento_cuando_anula_f,
					       ktt.cod_tipo_transaccion || ' - ' || ktt.descripcion tipo_transaccion
					FROM KR_TIPOS_TRANSACCION ktt
					WHERE $where
					ORDER BY tipo_transaccion";
        $datos = toba::db()->consultar($sql_sel);
        return $datos;
    }

	static public function get_datos_extras_transaccion_x_id($id_transaccion)
	{
        if (isset($id_transaccion)) {
            $sql = "SELECT	PKG_KR_TRANSACCIONES.ID_COMPROBANTE_TRANSACCION(kt.ID_TRANSACCION) id_comprobante_origen
					FROM KR_TRANSACCION kt
					WHERE kt.id_transaccion = " . quote($id_transaccion) . ";";

            $datos = toba::db()->consultar_fila($sql);

            return $datos;
        } else {
            return array();
        }
    }

}
?>
