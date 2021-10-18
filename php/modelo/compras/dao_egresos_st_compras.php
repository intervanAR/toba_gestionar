<?php
class dao_egresos_st_compras {
	
	static public function get_egresos ($filtro = array()){
		
		$where = " 1=1 ";
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ste', ' 1=1 '); 
		$sql = "SELECT ste.*, stdep.cod_deposito ||' - '|| stdep.descripcion deposito,
						to_char(ste.FECHA,'DD/MM/YYYY') fecha_format
						,(select rv_meaning from cg_ref_codes where rv_domain = 'ST_TIPO_EGRESO' and rv_low_value = ste.TIPO_EGRESO ) tipo_egreso_format
						,(select rv_meaning from cg_ref_codes where rv_domain = 'ST_ESTADO_EGRESO' and rv_low_value = ste.ESTADO ) estado_format
						,(select sec.COD_SECTOR||' - '||sec.DESCRIPCION sector_format from co_sectores sec where ste.COD_SECTOR = sec.COD_SECTOR) sector
 				  FROM st_egresos ste, st_depositos stdep
 				 WHERE ste.cod_deposito = stdep.cod_deposito and $where
 				 ORDER BY STE.NRO_EGRESO desc";
		return toba::db()->consultar($sql);
	}

	static public function modificar_item ($datos)
	{	
		$sql = "update ST_EGRESOS_DETALLES set ";
		foreach ($datos as $key => $value) {
			$sql .=" ".$key." = ".quote($value).",";
		}

		$sql = rtrim($sql,',');
		$sql .=" where nro_egreso = ".$datos['nro_egreso']." and nro_renglon = ".$datos['nro_renglon'];
		ctr_procedimientos::ejecutar_transaccion_simple('No se pudo guardar.',$sql);
		return true;
	}
	
	static public function get_campo ($nombre_campo, $id_comprobante){
		$sql = "SELECT $nombre_campo FROM ST_EGRESOS WHERE NRO_EGRESO = $id_comprobante ";
		$datos = toba::db()->consultar_fila($sql);
		return $datos[$nombre_campo];
	}
	
	static public function get_items_egreso ($nro_egreso){
		$sql = "SELECT ste.*
				FROM ST_EGRESOS_DETALLES ste
				WHERE ste.nro_egreso = $nro_egreso 
				ORDER BY ste.COD_ARTICULO ";
		return toba::db()->consultar($sql);
	}
	static public function get_prox_nro_renglon($nro_egreso)
	{
		$sql = "SELECT NVL (max(nro_renglon), 0) + 1 nro_renglon
				  FROM st_egresos_detalles
				 WHERE nro_egreso = $nro_egreso";
		$result = toba::db()->consultar_fila($sql);
		return $result['nro_renglon'];
	}

	static public function delete_egreso_detalle ($nro_egreso, $nro_renglon) 
	{	
		$sql = "DELETE FROM ST_EGRESOS_DETALLES WHERE nro_egreso = $nro_egreso and nro_renglon = $nro_renglon";
		ctr_procedimientos::ejecutar_transaccion_simple(null,$sql);
	}

	/*
	*Retorna el item con datos formateados para mostrar en grilla
	*/
	static public function get_item ($nro_egreso, $nro_renglon)
	{
		$sql = "SELECT sted.*
					   ,coart.descripcion articulo_desc,
					   (select rv_meaning from cg_ref_codes where rv_domain = 'CO_UNIDAD_MEDIDA' and rv_low_value = sted.unidad ) unidad_desc
					   ,(select rv_meaning from cg_ref_codes where rv_domain = 'CO_UNIDAD_MEDIDA' and rv_low_value = sted.unidad_stock ) unidad_stock_desc
				  FROM st_egresos_detalles sted, co_articulos coart
				 WHERE sted.nro_egreso = ".quote($nro_egreso)."
				   AND sted.nro_renglon = ".quote($nro_renglon)."
				   AND sted.cod_articulo = coart.cod_articulo
				   ";
		return toba::db()->consultar_fila($sql);
	}
	

	static public function incorporar_items_pedido ($nro_egreso, $nro_pedido, $cod_deposito)
	{
		$sql = "BEGIN
					:resultado := PKG_STOCK.incorporar_items_pedido(:nro_egreso,:nro_pedido, :cod_deposito); 
				END;";

		$parametros = [ 
            [   'nombre' => 'resultado', 
                'tipo_dato' => PDO::PARAM_STR,
                'longitud' => 4000,
                'valor' => ''],
		 	[   'nombre' => 'nro_egreso', 
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $nro_egreso],
            [   'nombre' => 'cod_deposito', 
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $cod_deposito],
            [   'nombre' => 'nro_pedido', 
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $nro_pedido],
        ];
        ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
	}
	static public function eliminar_items_pedido ($nro_egreso, $nro_pedido)
	{
		$sql = "BEGIN
					:resultado := PKG_STOCK.eliminar_items_pedido(:nro_egreso,:nro_pedido); 
				END;";

		$parametros = [ 
            [   'nombre' => 'resultado', 
                'tipo_dato' => PDO::PARAM_STR,
                'longitud' => 4000,
                'valor' => ''],
		 	[   'nombre' => 'nro_egreso', 
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $nro_egreso],
            [   'nombre' => 'nro_pedido', 
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $nro_pedido],
        ];
        ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
	}

	public static function confirmar ($nro_egreso, $con_transaccion = true){
		$sql = "BEGIN :resultado := PKG_STOCK.confirmar_egreso(:nro_egreso);END;";
		$parametros = array(array(	'nombre' => 'nro_egreso',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $nro_egreso),
							array(	'nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''),);
		$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', '', $con_transaccion);
		return $resultado[1]['valor'];
    }

    public static function anular ($nro_egreso, $con_transaccion = true){
		$sql = "BEGIN :resultado := PKG_STOCK.anular_egreso(:nro_egreso);END;";
		$parametros = array(array(	'nombre' => 'nro_egreso',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $nro_egreso),
							array(	'nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''),);
		$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', '', $con_transaccion);
		return $resultado[1]['valor'];
    }

    static public function get_datos_stock_lote ($cod_lote)
    {
      $sql = "SELECT lot.cod_stock, precio_venta, unidad, venta_ultimo_precio, seq_lote_ultimo
                FROM   st_stock_lotes lot,
                       st_stock       sto
                WHERE  lot.cod_stock = sto.cod_stock
                AND    cod_lote = $cod_lote ";
      $datos = toba::db()->consultar_fila($sql);
      return $datos;
    }
}

?>