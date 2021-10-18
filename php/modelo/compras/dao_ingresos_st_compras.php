<?php
class dao_ingresos_st_compras {
	
	static public function get_ingresos ($filtro = array()){
		$where = " 1=1 ";
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'STING', ' 1=1 '); 
		$sql = "SELECT STING.*
					   ,(select rv_meaning
		                   from cg_ref_codes 
		                  where rv_low_value = sting.tipo_ingreso and rv_domain = 'ST_TIPO_INGRESO' ) tipo_ingreso
		               ,(select id_proveedor ||' - '|| razon_social as proveedor from co_proveedores where id_proveedor = sting.id_proveedor ) proveedor
					   ,stdep.cod_deposito ||' - '|| stdep.descripcion deposito
					   ,(select rv_meaning from cg_ref_codes where rv_domain = 'ST_ESTADO_INGRESO' and rv_low_value = sting.ESTADO) estado_format	 
				FROM ST_INGRESOS STING, st_depositos stdep
				WHERE STING.cod_deposito = stdep.cod_deposito and $where
				ORDER BY STING.NRO_INGRESO desc";
		return toba::db()->consultar($sql);
	}

	/*
	*Retorna el item con datos formateados para mostrar en grilla
	*/
	static public function get_item ($nro_ingreso, $nro_renglon)
	{
		$sql = "SELECT stid.*
					   ,coart.descripcion articulo_desc
					   ,to_char(stid.fecha_entrada,'dd/mm/yyyy') fecha_entrada_format
					   ,to_char(stid.fecha_vencimiento,'dd/mm/yyyy') fecha_vencimiento_format
				  FROM st_ingresos_detalles stid, co_articulos coart
				 WHERE stid.nro_ingreso = ".quote($nro_ingreso)."
				   AND stid.nro_renglon = ".quote($nro_renglon)."
				   AND stid.cod_articulo = coart.cod_articulo
				   ";
		return toba::db()->consultar_fila($sql);
	}
	
	public static function get_cantidad_item($nro_ingreso)
	{
		$sql = "SELECT count(*) cant
			FROM st_ingresos_detalles stid, co_articulos coart
		 WHERE stid.nro_ingreso = $nro_ingreso
		AND stid.cod_articulo = coart.cod_articulo
				   ";
		$datos = toba::db()->consultar_fila($sql);
		if (intval($datos['cant']) > 0){
			return true;
		}
		return false;
	}
	static public function get_campo ($nombre_campo, $id_comprobante){
		$sql = "SELECT $nombre_campo FROM ST_INGRESOS WHERE NRO_INGRESO = $id_comprobante ";
		$datos = toba::db()->consultar_fila($sql);
		return $datos[$nombre_campo];
	}
	
	static public function get_items_ingreso ($nro_ingreso){
		$sql = "SELECT STID.*
				FROM ST_INGRESOS_DETALLES STID
				WHERE STID.NRO_INGRESO = $nro_ingreso 
				ORDER BY STID.nro_renglon asc ";
		return toba::db()->consultar($sql);
	}
	
	static public function delete_ingreso_detalle ($nro_ingreso, $nro_renglon) 
	{	
		$sql = "DELETE FROM ST_INGRESOS_DETALLES WHERE NRO_INGRESO = $nro_ingreso and nro_renglon = $nro_renglon";
		ctr_procedimientos::ejecutar_transaccion_simple(null,$sql);
	}

	static public function contar_unidad_stock ($cod_stock, $unidad)
	{
		$sql = "SELECT count(*) cant
				from   st_stock_unidades
				where  cod_stock = $cod_stock
				and    unidad    = $unidad";
		$datos = toba::db()->consultar_fila($sql);
		return intval($datos['cant']);
	}



	/**
    *@return array
	*/
	static public function rescatar_datos ($cod_deposito, $cod_articulo, $unidad, $precio)
	{
		$resultado = [];


		$sql = "SELECT count(*) cant
				  from st_stock
				 Where cod_deposito = $cod_deposito
				   and cod_articulo = $cod_articulo";
	   	$datos = toba::db()->consultar_fila($sql);

	   	$resultado['cant'] = $datos['cant'];

	   	if (intval($resultado['cant']) > 0){
	   		$sql = "SELECT cod_stock, unidad
					--into   :ITEMS.cod_stock,:ITEMS.unidad_stock
					from   st_stock
					where  cod_deposito = $cod_deposito
				    and    cod_articulo = $cod_articulo";
			$datos = toba::db()->consultar_fila($sql);
			
			$resultado['cod_stock'] = $datos['cod_stock'];
			$resultado['unidad_stock'] = $datos['unidad'];

			if (is_null($unidad)){
				$resultado['unidad'] = $resultado['unidad_stock'];
				$resultado['coeficiente'] = 1;
			}else{
				$sql = "SELECT count(*) cant 
						  from st_stock_unidades
						 where cod_stock = ".quote($resultado['cod_stock'])."
						   and unidad = $unidad";
			    $datos = toba::db()->consultar_fila($sql);
			    if (intval($datos['cant']) > 0){
			    	$sql = "SELECT round(cantidad_stock / cantidad,4) coeficiente
				            from   st_stock_unidades
							where  cod_stock = ".quote($resultado['cod_stock'])."
							and    unidad    = $unidad";
				    $datos = toba::db()->consultar_fila($sql);
				    $resultado['coeficiente'] = $datos['coeficiente'];
			    }else{
			    	$resultado['coeficiente'] = 1;
			    }
			}
	   	}else{
	   		$resultado['cod_stock'] = null;
	   		$resultado['coeficiente'] = 1;
	   	}
	   	//Rescatar el ultimo precio testigo.
	   	if (is_null($precio) ){
		   	$sql = "SELECT precio
				      FROM co_precios_testigo
				     WHERE cod_articulo = $cod_articulo
				  ORDER BY fecha_vigencia ASC";
			$datos = toba::db()->consultar($sql);
			$resultado['precio'] = end($datos)['precio'];
		}
	   	return $resultado;
	}

	static public function incorporar_items_recepcion ($nro_ingreso, $nro_recepcion)
	{
		$sql = "BEGIN
					:resultado := PKG_STOCK.incorporar_items_recepcion(:nro_ingreso,:nro_recepcion); 
				END;";

		$parametros = [ 
            [   'nombre' => 'resultado', 
                'tipo_dato' => PDO::PARAM_STR,
                'longitud' => 4000,
                'valor' => ''],
		 	[   'nombre' => 'nro_ingreso', 
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $nro_ingreso],
            [   'nombre' => 'nro_recepcion', 
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $nro_recepcion],
        ];
        ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
	}

	static public function eliminar_items_recepcion ($nro_ingreso, $nro_recepcion)
	{
		$sql = "BEGIN
					:resultado := PKG_STOCK.eliminar_items_recepcion(:nro_ingreso,:nro_recepcion); 
				END;";

		$parametros = [ 
            [   'nombre' => 'resultado', 
                'tipo_dato' => PDO::PARAM_STR,
                'longitud' => 4000,
                'valor' => ''],
		 	[   'nombre' => 'nro_ingreso', 
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $nro_ingreso],
            [   'nombre' => 'nro_recepcion', 
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $nro_recepcion],
        ];
        ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
	}

	static public function existe_item_recepcion ($nro_ingreso, $cod_articulo, $nro_renglon)
	{
		$sql = "SELECT COUNT (*) cant
				  FROM co_items_recepcion
				 WHERE cod_articulo = ".quote($cod_articulo)." 
				   AND nro_ingreso = ".quote($nro_ingreso)."
				   AND nro_renglon_ingreso = ".quote($nro_renglon);
		$datos = toba::db()->consultar_fila($sql);	   
		if (intval($datos['cant']) > 0){
			return true;
		}
		return false;
	}

	static public function get_prox_nro_renglon($nro_ingreso)
	{
		$sql = "SELECT NVL (max(nro_renglon), 0) + 1 nro_renglon
				  FROM st_ingresos_detalles
				 WHERE nro_ingreso = $nro_ingreso";
		$result = toba::db()->consultar_fila($sql);
		return $result['nro_renglon'];
	}

	static public function modificar_item ($datos)
	{	
		$sql = "update ST_INGRESOS_DETALLES set ";
		foreach ($datos as $key => $value) {
			if ($key == 'fecha_entrada' || $key == 'fecha_vencimiento'){
				$sql .=" ".$key." = to_date(".quote($value).",'yyyy-mm-dd'),";	
			}else{
				$sql .=" ".$key." = ".quote($value).",";
			}
		}

		$sql = rtrim($sql,',');
		$sql .=" where nro_ingreso = ".$datos['nro_ingreso']." and nro_renglon = ".$datos['nro_renglon'];
		//toba::db()->ejecutar($sql);
		ctr_procedimientos::ejecutar_transaccion_simple(null,$sql);
		return true;
	}

	static public function tiene_items ($id_ingreso)
	{
		$sql = "SELECT count(*) cant
				  from ST_INGRESOS_DETALLES 
				 WHERE id_ingreso = $id_ingreso";
		$datos = toba::db()->consultar_fila($sql);
		
		if (intval($datos['cant']) > 0){
			return true;
		}
		return false;
	}

	static public function insertar_ingreso_detalle ($datos)
	{
		try{		

			toba::db()->abrir_transaccion();

			$datos['nro_renglon'] = self::get_prox_nro_renglon($datos['nro_ingreso']);
			
			foreach ($datos as $key => $value) {
				if (is_null($datos[$key])){
					$datos[$key] = 'null';
				}else{
					if ($key == 'fecha_vencimiento'){
						$datos[$key] = "to_date('".$datos[$key]."','yyyy-mm-dd')";
					}
					if ($key == 'fecha_entrada'){
						$datos[$key] = "to_date('".$datos[$key]."','yyyy-mm-dd')";
					}
				}

			}

			$sql = "INSERT INTO ST_INGRESOS_DETALLES
					   (nro_ingreso, nro_renglon, cod_articulo, unidad, precio, cantidad, total, unidad_stock, cantidad_stock, precio_stock, total_stock, fecha_entrada, fecha_vencimiento,marca, nro_lote, cod_lote)
					 VALUES
					   (".$datos['nro_ingreso'].", ".$datos['nro_renglon'].", ".$datos['cod_articulo'].", upper('".$datos['unidad']."'), ".$datos['precio'].", ".$datos['cantidad'].", ".$datos['total'].", upper('".$datos['unidad_stock']."'), ".$datos['cantidad_stock'].", ".$datos['precio_stock'].", ".$datos['total_stock'].", ".$datos['fecha_entrada'].", ".$datos['fecha_vencimiento'].",".$datos['marca'].",".$datos['nro_lote'].",".$datos['cod_lote'].")";

		    ctr_procedimientos::ejecutar_transaccion_simple('No fue posible insertar el item.',$sql,false);

		    toba::db()->cerrar_transaccion();

		}catch (toba_error $e) {
			$error = $e->get_mensaje();
			toba::logger()->error($e);
			toba::db()->abortar_transaccion();
			throw new toba_error(ctr_procedimientos::procesar_error($error));
		}
	}


	static public function anular_ingreso ($nro_ingreso)
	{
		$sql = "BEGIN
					:resultado := PKG_STOCK.anular_ingreso(:nro_ingreso); 
				END;";

		$parametros = [ 
            [   'nombre' => 'resultado', 
                'tipo_dato' => PDO::PARAM_STR,
                'longitud' => 4000,
                'valor' => ''],
		 	[   'nombre' => 'nro_ingreso', 
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $nro_ingreso],
        ];
        ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
	}

	static public function confirmar_ingreso ($nro_ingreso)
	{
		$sql = "BEGIN
					:resultado := PKG_STOCK.confirmar_ingreso(:nro_ingreso); 
				END;";

		$parametros = [ 
            [   'nombre' => 'resultado', 
                'tipo_dato' => PDO::PARAM_STR,
                'longitud' => 4000,
                'valor' => ''],
		 	[   'nombre' => 'nro_ingreso', 
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $nro_ingreso],
        ];
        ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
	}



	static public function get_datos_stock ($cod_articulo, $cod_deposito, $unidad = null)
	{
		$datos = [
			'cod_stock'=>null,
			'cant_stock'=>0,
			'unidad_stock'=>null,
			'unidad'=>$unidad,
			'coeficiente'=>1,
			'precio_testigo'=>null,
		];	
		//Cuenta el  stock del producto en el deposito
		$sql = "SELECT COUNT (*) cant_stock
				  FROM st_stock
				 WHERE cod_deposito = $cod_deposito 
				   AND cod_articulo = $cod_articulo ";
        $result = toba::db()->consultar_fila($sql);

        if (intval($result['cant_stock']) > 0)
        {
        	//Tengo en stcok, saco los datos del stock
        	$datos['cant_stock'] = $result['cant_stock'];	

        	$sql = "SELECT cod_stock, unidad 
					/*into   :ITEMS.cod_stock,:ITEMS.unidad_stock*/
					  FROM st_stock
					 WHERE cod_deposito = $cod_deposito
				       AND cod_articulo = $cod_articulo";
        	$result = toba::db()->consultar_fila($sql);
        	$datos['cod_stock'] = $result['cod_stock'];
	     	$datos['unidad_stock'] = $result['unidad']; //La unidad debe setearse en caso de que en el formulario sea nula.
	     	
	     	if (is_null($unidad))
	     	{
	     		$datos['unidad'] = $datos['unidad_stock'];
				$datos['coeficiente'] = 1;
	     	}else{

		     	$sql = "SELECT count(*) cant
						  FROM st_stock_unidades
						 WHERE cod_stock = ".$datos['cod_stock']."
						   AND unidad = ".$datos['unidad'];
	        	$result = toba::db()->consultar_fila($sql);

	        	if (intval($result['cant']) > 0)
	        	{
	        		$sql = "SELECT round(cantidad_stock / cantidad,4) coeficiente
	            			  FROM st_stock_unidades
							 WHERE cod_stock = ".$datos['cod_stock']."
							   AND unidad = ".$datos['unidad'];
				    $result = toba::db()->consultar_fila($sql);
				    $datos['coeficiente'] = $result['coeficiente'];
	        	}
        	}
        }
        //Rescatar el ultimo precio testigo.
	   	$sql = "SELECT precio
			      FROM co_precios_testigo
			     WHERE cod_articulo = $cod_articulo
			  ORDER BY fecha_vigencia ASC";
		$result = toba::db()->consultar($sql);
		$datos['precio_testigo'] = end($result)['precio'];

		return $datos;
	}

	public static function get_coeficiente_unidad_stock ($cod_stock, $unidad)
	{
		
		$sql = "SELECT count(*) cant
			      FROM st_stock_unidades
			     WHERE cod_stock = $cod_stock
			       AND unidad = ".quote($unidad);
		$result = toba::db()->consultar_fila($sql);

		if (intval($result['cant']) == 0)
			return 1;
		

		$sql = "SELECT (cantidad_stock / cantidad) coeficiente
			      FROM st_stock_unidades
			     WHERE cod_stock = $cod_stock
			       AND unidad = ".quote($unidad);
		$result = toba::db()->consultar_fila($sql);
		return intval($result['coeficiente']);
	}

	
static public function cambiar_precio_ingreso ($nro_ingreso, $nro_renglon,$precio)
	{
		$sql = "BEGIN
					:resultado := cambiar_precio_ingreso(:nro_ingreso,:nro_renglon,:precio); 
				END;";

		$parametros = [ 
            [   'nombre' => 'resultado', 
                'tipo_dato' => PDO::PARAM_STR,
                'longitud' => 4000,
                'valor' => ''],
		 	[   'nombre' => 'nro_ingreso', 
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $nro_ingreso],
            [   'nombre' => 'nro_renglon', 
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $nro_renglon],
            [   'nombre' => 'precio', 
                'tipo_dato' => PDO::PARAM_INT,
                'longitud' => 32,
                'valor' => $precio],
        ];
        ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
	}



	
}
?>