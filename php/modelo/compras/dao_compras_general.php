<?php

class dao_compras_general {

	public static function consultar_domain($domain, $low_value)
	{
		$sql = "SELECT rv_meaning 
				  FROM cg_ref_codes
				 WHERE rv_low_value = '".$low_value."' AND rv_domain = '".$domain."'";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['rv_meaning'];
	}


    public static function get_destinos_compra() {
        $sql_sel = "SELECT  crc.rv_low_value destino_compra,
							crc.rv_meaning lov_descripcion
					FROM cg_ref_codes crc
					WHERE crc.rv_domain = 'CO_DESTINO_COMPRA'
					ORDER BY crc.rv_meaning ASC;";
        $datos = toba::db()->consultar($sql_sel);
		return $datos;
    }
	
	public static function get_tipos_compras() {
        $sql_sel = "SELECT  crc.rv_low_value tipo_compra,
							crc.rv_meaning lov_descripcion
					FROM cg_ref_codes crc
					WHERE crc.rv_domain = 'CO_TIPO_COMPRA'
					ORDER BY crc.rv_meaning ASC;";
        $datos = toba::db()->consultar($sql_sel);
		return $datos;
    }
	
	public static function get_tipos_facturas_compras() {
        $sql_sel = "SELECT  crc.rv_low_value tipo_factura,
							crc.rv_meaning lov_descripcion
					FROM cg_ref_codes crc
					WHERE crc.rv_domain = 'CO_TIPO_FACTURA'
					ORDER BY crc.rv_meaning ASC;";
        $datos = toba::db()->consultar($sql_sel);
		return $datos;
    }
	
	static public function borrar_arreglo_temp_co()
	{
		$sql = "BEGIN PKG_TMP_ARREGLO.borrar; END;";

		ctr_procedimientos::ejecutar_functions_mensajes($sql, array(), '', 'Error al borrar el arreglo temporal de compras.');
		return true;
	}
	
	public static function get_estados_sectores($estado_hasta=null) {
		$sql_sel = "SELECT *
					FROM CO_TMP_ESTADOS_SECTORES;";
		$datos = toba::db()->consultar($sql_sel);
		$cantidad = count($datos);
		$i=0;
		$enc = false;
		$resultado = array();
		while ($i < $cantidad && !$enc) {
			$resultado[] = $datos[$i];
			if (isset($estado_hasta) && $datos[$i]['estado_desde'] == $estado_hasta) {
				$enc = true;
			}
			$i++;
		}
		return $resultado;
    }
	
	public static function get_unidades_medida() {
        $sql_sel = "SELECT  crc.rv_low_value unidad,
							crc.rv_meaning lov_descripcion
					FROM cg_ref_codes crc
					WHERE crc.rv_domain = 'CO_UNIDAD_MEDIDA'
					ORDER BY crc.rv_meaning ASC;";
        $datos = toba::db()->consultar($sql_sel);
		return $datos;
    }
	
	public static function get_marca_modelo() {
        $sql_sel = "SELECT  crc.rv_low_value marca_modelo,
							crc.rv_meaning lov_descripcion
					FROM cg_ref_codes crc
					WHERE crc.rv_domain = 'CO_MARCA_MODELO'
					ORDER BY crc.rv_meaning ASC;";
        $datos = toba::db()->consultar($sql_sel);
		return $datos;
    }
	
	public static function get_estado_item_compra() {
        $sql_sel = "SELECT  crc.rv_low_value estado_item_compra,
							crc.rv_meaning lov_descripcion
					FROM cg_ref_codes crc
					WHERE crc.rv_domain = 'CO_EST_ITEM_COMPRA'
					ORDER BY crc.rv_meaning ASC;";
        $datos = toba::db()->consultar($sql_sel);
		return $datos;
    }
	
	public static function get_des_unidad_medida($unidad) {
		if (isset($unidad)) {
			$sql_sel = "SELECT  crc.rv_meaning descripcion
						FROM cg_ref_codes crc
						WHERE crc.rv_domain = 'CO_UNIDAD_MEDIDA'
						AND crc.rv_low_value = " . quote($unidad) . ";";
			$datos = toba::db()->consultar_fila($sql_sel);
			if (isset($datos['descripcion'])) {
				return $datos['descripcion'];
			} else {
				return '';
			}
		} else {
			return '';
		}
    }
	
	public static function get_sistemas_compras($filtro = array()) {
		$where = ' 1 = 1 ';
		if (isset($filtro['no_pco'])) {
			$where .= " AND crc.rv_low_value <> 'PCO' ";
		}
        $sql_sel = "SELECT  crc.rv_low_value sistema_compra,
							crc.rv_meaning lov_descripcion
					FROM cg_ref_codes crc
					WHERE crc.rv_domain = 'CO_SISTEMA_COMPRA'
					AND $where
					ORDER BY crc.rv_meaning ASC;";
        $datos = toba::db()->consultar($sql_sel);
		return $datos;
    }
	public static function get_estado_solicitud_compra() {
        $sql_sel = "SELECT  crc.rv_low_value estado,
							crc.rv_meaning descripcion
					FROM cg_ref_codes crc
					WHERE crc.rv_domain = 'CO_ESTADO_SOLICITUD'
					ORDER BY crc.rv_meaning ASC;";
        $datos = toba::db()->consultar($sql_sel);
		return $datos;
    }

    public static function get_estado_anul_solicitud(){
    	$sql = "select rv_meaning
    	  	     from cg_ref_codes
    	  	     where rv_low_value = 'ANUL' and rv_domain ='CO_ESTADO_SOLICITUD'";
  	     $datos = toba::db()->consultar_fila($sql);
  	     return $datos['rv_meaning'];
    }
    public static function get_estado_anul_pedido(){
    	$sql = "select rv_meaning
    	  	     from cg_ref_codes
    	  	     where rv_low_value = 'ANUL' and rv_domain ='CO_ESTADO_PEDIDO'";
  	     $datos = toba::db()->consultar_fila($sql);
  	     return $datos['rv_meaning'];
    }
    public static function get_estado_anul_orden(){
    	$sql = "select rv_meaning
    	  	     from cg_ref_codes
    	  	     where rv_low_value = 'ANUL' and rv_domain ='CO_ESTADO_COMPRA'";
  	     $datos = toba::db()->consultar_fila($sql);
  	     return $datos['rv_meaning'];
    }
    public static function get_estado_anul_recepcion(){
    	$sql = "select rv_meaning
    	  	     from cg_ref_codes
    	  	     where rv_low_value = 'ANUL' and rv_domain ='CO_ESTADO_RECEPCION'";
  	     $datos = toba::db()->consultar_fila($sql);
  	     return $datos['rv_meaning'];
    }

    public static function update_fecha_compra($nro_compra, $fecha){
    	$sql= "UPDATE CO_COMPRAS
    		SET fecha_apertura= to_date('$fecha', 'yyyy-mm-dd hh24:mi:ss')
    		WHERE nro_compra= $nro_compra";

    	toba::db()->ejecutar($sql);

    	return "OK";
    }
}

?>
