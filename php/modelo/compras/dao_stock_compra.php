<?php
class dao_stock_compra {
	
	static public function get_depositos ($filtro = array()){
		$where = "1=1 ";
		$where .= " AND ". ctr_construir_sentencias::get_where_filtro($filtro, 'stdep', '1=1');
		
		$sql = "SELECT stdep.*, cosec.cod_sector || ' - ' || cosec.descripcion sector,
				       DECODE (stdep.stock_por_lote, 'S', 'Si', 'No') stock_por_lote_format,
				       DECODE (stdep.interno, 'S', 'Si', 'No') interno_format
				  FROM st_depositos stdep, co_sectores cosec
				 WHERE stdep.cod_sector = cosec.cod_sector and $where
  			  ORDER BY STDEP.COD_DEPOSITO DESC";
		return toba::db()->consultar($sql);
		
	}
	
	static public function get_stock ($filtro = array()){
		
		$where = "1=1 ";
		$where .= " AND ". ctr_construir_sentencias::get_where_filtro($filtro, 'ststo', '1=1');
		
		$sql = "SELECT ststo.*, (select rv_meaning from cg_ref_codes where rv_domain = 'CO_UNIDAD_MEDIDA' and rv_low_value = ststo.unidad) unidad_format
  				  FROM ST_STOCK ststo
  				 WHERE $where
  			  ORDER BY ststo.cod_stock DESC";
		return toba::db()->consultar($sql);
		
	}
	static public function actualizar_stock ($datos)
	{
		$cod_stock = $datos['cod_stock'];
		$cod_deposito = $datos['cod_deposito'];
		unset($datos['cod_stock']);
		unset($datos['cod_deposito']);

		$sql = "update ST_STOCK set ";
		foreach ($datos as $key => $value) {
			$sql .=" ".$key." = ".quote($value).",";
		}

		$sql = rtrim($sql,',');
		$sql .=" where cod_stock = ".quote($cod_stock)." and cod_deposito = ".quote($cod_deposito);
		ctr_procedimientos::ejecutar_transaccion_simple(null,$sql); 
		return true;
	}
	static public function get_stock_contadores ($filtro = array()){
		
		$where = "1=1 ";
		$where .= " AND ". ctr_construir_sentencias::get_where_filtro($filtro, 'stcon', '1=1');
		
		$sql = "SELECT stcon.*
  				  FROM ST_STOCK_CONTADORES stcon
  				 WHERE $where
  			  ORDER BY stcon.fecha DESC";
		return toba::db()->consultar($sql);
		
	}
	
	static public function get_stock_lotes ($filtro = array()){
		
		$where = "1=1 ";
		$where .= " AND ". ctr_construir_sentencias::get_where_filtro($filtro, 'stlot', '1=1');
		
		$sql = "SELECT stlot.*, coart.descripcion articulo_desc
				  FROM st_stock_lotes stlot, st_stock stst, co_articulos coart
				 WHERE stlot.cod_stock = stst.cod_stock
				   AND stst.cod_articulo = coart.cod_articulo and $where
  			  ORDER BY stlot.cod_lote DESC";
		return toba::db()->consultar($sql);
		
	}
	
	static public function get_stock_movimientos ($filtro = array()){
		
		$where = "1=1 ";
		$where .= " AND ". ctr_construir_sentencias::get_where_filtro($filtro, 'stmov', '1=1');
		
		$sql = "SELECT stmov.*, to_char(stmov.fecha,'dd/mm/yyyy') fecha_format
  				  FROM ST_STOCK_MOVIMIENTOS stmov
  				 WHERE $where
  			  ORDER BY stmov.cod_movimiento DESC";
		return toba::db()->consultar($sql);
		
	}

	static public function get_lov_stock_x_claves ($claves)
	{
		$claves = explode('||', $claves);
		$cod_lote = $claves[0];
		$cod_articulo = $claves[1];
	

		$sql = "SELECT stlo.COD_LOTE ||'||'||l_art.COD_ARTICULO claves,
					  l_art.cod_articulo
       || ' - '
       || l_art.descripcion
       || DECODE (stlo.fecha_vencimiento,
                  NULL, '',
                     ' - Vencimiento '
                  || TO_CHAR (stlo.fecha_vencimiento, 'dd/mm/yyyy')
                 )
       || decode(nvl (stlo.seq_lote, 0),0, '', ' - Nro.Lote ' || stlo.seq_lote)
       || DECODE (stlo.cantidad_actual, NULL, '',' - Cant.Actual ' || stlo.cantidad_actual
                 ) lov_descripcion
    			  FROM st_stock_lotes stlo, st_stock l_sto, co_articulos l_art
				 WHERE stlo.cod_stock = l_sto.cod_stock
				   AND l_sto.cod_articulo = l_art.cod_articulo and stlo.cod_lote = ".quote($cod_lote)."
				   and l_sto.cod_articulo = ".quote($cod_articulo);

		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}
	
	static public function get_articulo_stock ($cod_lote, $cod_articulo)
	{
		$sql = "SELECT stlo.*
				  FROM st_stock_lotes stlo, st_stock l_sto, co_articulos l_art
				 WHERE stlo.cod_stock = l_sto.cod_stock
				   AND l_sto.cod_articulo = l_art.cod_articulo
				   and stlo.cod_lote = $cod_lote and l_sto.cod_articulo = $cod_articulo";

		return toba::db()->consultar_fila($sql);
	}

	static public function get_lov_stock_x_nombre ($nombre, $filtro = [])
	{
		$where = " 1=1 ";
		 if (isset($nombre)) {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('l_art.cod_articulo', $nombre);
            $trans_nombre = ctr_construir_sentencias::construir_translate_ilike('l_art.descripcion', $nombre);
            $where = "($trans_codigo OR $trans_nombre)";
        } else {
            $where = '1=1';
        }

        if (isset($filtro['cod_deposito'])){
        	$where .=" and  l_sto.cod_deposito = ".quote($filtro['cod_deposito']);
        	unset($filtro['cod_deposito']);
        }
        if (isset($filtro['con_saldo'])){
        	$where .=" and  stlo.cantidad_actual > 0 ";
        	unset($filtro['con_saldo']);
        }

        if (isset($filtro['con_pedido'])){
        	if ($filtro['con_pedido'] == 'S'){
        		$where .=" and l_sto.cod_articulo IN (
                                          SELECT cod_articulo
                                            FROM co_items_pedido
                                           WHERE nro_pedido =
                                                            ".quote($filtro['nro_pedido']).")";
        	}
        	unset($filtro['con_pedido']);
        	unset($filtro['nro_pedido']);
        }


		$where .= " AND ". ctr_construir_sentencias::get_where_filtro($filtro, 'stlo', '1=1');
		$sql = "SELECT stlo.*, 
					   stlo.COD_LOTE ||'||'||l_art.COD_ARTICULO claves,
					  l_art.cod_articulo
       || ' - '
       || l_art.descripcion
       || DECODE (stlo.fecha_vencimiento,
                  NULL, '',
                     ' - Vencimiento '
                  || TO_CHAR (stlo.fecha_vencimiento, 'dd/mm/yyyy')
                 )
       || decode(nvl (stlo.seq_lote, 0),0, '', ' - Nro.Lote ' || stlo.seq_lote)
       || DECODE (stlo.cantidad_actual, NULL, '',' - Cant.Actual ' || stlo.cantidad_actual
                 ) lov_descripcion
    			  FROM st_stock_lotes stlo, st_stock l_sto, co_articulos l_art
				 WHERE stlo.cod_stock = l_sto.cod_stock
				   AND l_sto.cod_articulo = l_art.cod_articulo and $where
			  ORDER BY lov_descripcion";

		return toba::db()->consultar($sql);			 
	}

	static public function get_lov_dimension_x_nombre ($nombre, $filtro = []){
		$where = " 1=1 ";
		
		if (isset($nombre)) {
            $trans_nombre = ctr_construir_sentencias::construir_translate_ilike('csvadi.descripcion', $nombre);
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('csvadi.cod_dimension', $nombre);
            $where = "($trans_codigo OR $trans_nombre)";
        } else {
            $where = '1=1';
        }

		if (isset($filtro['id_dimension']) && !empty($filtro['id_dimension'])){
			$where .=" and csvadi.id_dimension = ".$filtro['id_dimension'];
			unset($filtro['id_dimension']);
		}

		if (isset($filtro['dominio']) && isset($filtro['valor'])){
			$where .=" and csvadi.id_dimension =
                         pkg_general.abbrev_dominio ('".$filtro['dominio']."', '".$filtro['valor']."')
                         AND NOT EXISTS (SELECT 1
							               FROM cs_valores_dimensiones
							              WHERE id_dimension = csvadi.id_dimension
							                AND id_valor_dimension_padre = csvadi.id_valor_dimension
							                AND activo = 'S')";
			unset($filtro['dominio']);
			unset($filtro['valor']);
		}

		$where .= " AND ". ctr_construir_sentencias::get_where_filtro($filtro, 'csvadi', '1=1');
		$sql = "SELECT csvadi.*, csvadi.cod_dimension || ' - ' || csvadi.descripcion lov_descripcion
					   ,csvadi.descripcion lov_descripcion2
    			  FROM cs_valores_dimensiones csvadi
    			 WHERE $where
			  ORDER BY lov_descripcion";
		return toba::db()->consultar($sql);			  
	}

	static public function get_lov_dimension_x_codigo($codigo){
		$sql = "SELECT csvadi.cod_dimension || ' - ' || csvadi.descripcion lov_descripcion
    			  FROM cs_valores_dimensiones csvadi
    			 WHERE csvadi.cod_dimension = $codigo";

		$datos = toba::db()->consultar_fila($sql);			  
		return $datos['lov_descripcion'];
	}

	/**
	* @return array(cant, coeficiente)
	*/
	static public function get_cantidad_stock_unidades ($cod_stock, $unidad)
	{

		$sql = "SELECT (SELECT COUNT (*)
		          FROM st_stock_unidades
		         WHERE cod_stock = $cod_stock AND unidad = upper('".$unidad."')) cant,
		       (SELECT (cantidad_stock / cantidad) coeficiente
		          FROM st_stock_unidades
		         WHERE cod_stock = $cod_stock AND unidad = upper('".$unidad."')) coeficiente
		 		  FROM DUAL";
	    return toba::db()->consultar_fila($sql);
	}

}

?>