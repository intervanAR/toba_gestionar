<?php 
class dao_st_transferencias {

	static public function get_transferencias ($filtro = [], $orden = [])
	{
		$desde= null; 
		$hasta= null;
	    if(isset($filtro['numrow_desde'])){
	      $desde = $filtro['numrow_desde']; $hasta= $filtro['numrow_hasta'];
	      unset($filtro['numrow_desde']); unset($filtro['numrow_hasta']);
	    }
	    
	    $where = self::get_where($filtro);

		$sql = "SELECT stt.*, 
					   to_char(stt.fecha, 'dd/mm/yyyy') fecha_format,
				       stdepo.cod_deposito || ' - ' || stdepo.descripcion deposito_origen,
				       stdepd.cod_deposito || ' - ' || stdepd.descripcion deposito_destino,
				       CASE
				          WHEN stt.estado = 'CAR'
				             THEN 'Carga'
				          WHEN stt.estado = 'CON'
				             THEN 'Confirmada'
				          WHEN stt.estado = 'ANU'
				             THEN 'Anulada'
				       END estado_format
				  FROM st_transferencias stt, st_depositos stdepo, st_depositos stdepd
				 WHERE stt.cod_deposito = stdepo.cod_deposito
				   AND stt.cod_deposito_destino = stdepd.cod_deposito and $where
				   ORDER BY STT.NRO_TRANSFERENCIA DESC";

		$sql = dao_varios::paginador($sql, null, $desde, $hasta, null, $orden);
        $datos = toba::db()->consultar($sql);
        return $datos;
	}

	static private function get_where ($filtro = [])
	{
	    $where = "1=1"; 
	    $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'stt', '1=1');
	    return $where;
	}

	static public function get_cantidad ($filtro = [])
	{
		$where = self::get_where($filtro);
	    $sql = "SELECT COUNT(*) cant 
		        FROM st_transferencias stt
		        WHERE $where";
	    $datos = toba::db()->consultar_fila($sql);
	    return $datos['cant'];
	}

	static public function get_lov_deposito_x_nombre ($nombre, $filtro  = [])
	{
		$where = " 1=1 ";

		if (isset($filtro['usuario_sector'])){
			$where .= " and stdep.cod_sector in ( SELECT US.COD_SECTOR FROM CO_USUARIOS_SECTORES us, ST_DEPOSITOS de WHERE us.cod_sector=de.cod_sector AND USUARIO=upper('".toba::usuario()->get_id()."'))";
			unset($filtro['usuario_sector']);
		}
		
		if (isset($filtro['no_cod_deposito'])){
			$where .=" and stdep.cod_deposito <> ".$filtro['no_cod_deposito'];
			unset($filtro['no_cod_deposito']);
		}

        if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('stdep.cod_deposito', $nombre);
            $trans_des = ctr_construir_sentencias::construir_translate_ilike('stdep.descripcion', $nombre);
            $where .= " AND ($trans_cod OR $trans_des)";
        }

		$sql = "SELECT STDEP.*, STDEP.cod_deposito ||' - '||stdep.descripcion lov_descripcion
				  FROM ST_DEPOSITOS STDEP
				 WHERE $where
				 order by lov_descripcion";
		return toba::db()->consultar($sql);
	}
	static public function get_lov_deposito_x_codigo ($cod_deposito)
	{
		$sql = "SELECT STDEP.cod_deposito ||' - '||stdep.descripcion lov_descripcion
				  FROM ST_DEPOSITOS STDEP
				 WHERE STDEP.cod_deposito = ".quote($cod_deposito);
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}

	

	static public function get_lov_stock_x_nombre ($nombre, $filtro  = [])
	{
		$where = " 1=1 ";
	
        if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('l_art.cod_articulo', $nombre);
            $trans_des = ctr_construir_sentencias::construir_translate_ilike('l_art.descripcion', $nombre);
            $where .= " AND ($trans_cod OR $trans_des)";
        }
        if (isset($filtro['cod_deposito']) && !empty($filtro['cod_deposito'])){
        	$where .= " and l_sto.cod_deposito = ".$filtro['cod_deposito'];
        	unset($filtro['cod_deposito']);
        }

        //$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'l_art', '1=1');

		$sql = "SELECT l_art.cod_articulo, 
		L_STO.COD_ARTICULO||'||'||STLO.COD_LOTE datos,
		stlo.cod_lote || '||' || l_art.cod_articulo claves,
            l_art.cod_articulo
         || ' - '
         || l_art.descripcion
         || DECODE (stlo.fecha_vencimiento,
                    NULL, '',
                       ' - Vencimiento '
                    || TO_CHAR (stlo.fecha_vencimiento, 'dd/mm/yyyy')
                   )
         || decode(nvl (stlo.seq_lote, 0),0, '', ' - Nro.Lote ' || stlo.seq_lote)
         || DECODE (stlo.cantidad_actual,
                    NULL, '',
                    ' - Cant.Actual ' || stlo.cantidad_actual
                   ) lov_descripcion
				  FROM st_stock_lotes stlo, st_stock l_sto, co_articulos l_art
				 WHERE stlo.cod_stock = l_sto.cod_stock
				   AND l_sto.cod_articulo = l_art.cod_articulo
				   AND stlo.cantidad_actual > 0 and $where
				 order by lov_descripcion";
		return toba::db()->consultar($sql);
	}

	static public function get_lov_stock_x_codigo ($codigo)
	{
		$claves = explode('||', $codigo);
		$cod_articulo = $claves[0];
		$cod_lote = $claves[1];

		$sql = "SELECT l_art.cod_articulo || ' - ' || l_art.descripcion lov_descripcion
				  FROM st_stock_lotes stlo, st_stock l_sto, co_articulos l_art
				 WHERE stlo.cod_stock = l_sto.cod_stock
				   AND l_sto.cod_articulo = l_art.cod_articulo
				   AND l_art.cod_articulo = ".quote($cod_articulo)."
				   AND stlo.cod_lote = ".quote($cod_lote);

		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}

	static public function get_datos_extra_articulo ($cod_articulo, $cod_lote)
	{

		$sql = "SELECT stlo.CANTIDAD_ACTUAL, stlo.COD_LOTE, stlo.PRECIO_VENTA,l_sto.COD_ARTICULO, l_sto.UNIDAD, l_art.DESCRIPCION
				  FROM st_stock_lotes stlo, st_stock l_sto, co_articulos l_art
				WHERE stlo.cod_stock = l_sto.cod_stock
				  AND l_sto.cod_articulo = l_art.cod_articulo
				  AND l_sto.cod_articulo = ".quote($cod_articulo)."
				  AND stlo.cod_lote = ".quote($cod_lote);
	    $datos = toba::db()->consultar_fila($sql);
	    return $datos;
	}

	static public function cantidad_lote ($cod_lote){
		$sql ="SELECT cantidad_actual cantidad
			     FROM st_stock_lotes
			    WHERE cod_lote = ".$cod_lote;
		$datos = toba::db()->consultar_fila($sql);
		return intval($datos['cantidad']);
	}

	static public function get_prox_nro_renglon($nro_transferencia){
		$sql = "SELECT NVL(MAX(NRO_RENGLON),0)+1 nro_renglon FROM ST_TRANSFERENCIAS_DETALLES WHERE nro_transferencia = ".$nro_transferencia;
		$datos = toba::db()->consultar_fila($sql);
		return $datos['nro_renglon'];
	}

	static public function modificar_item ($datos)
	{	
		toba::db()->abrir_transaccion();
		$sql = "update ST_TRANSFERENCIAS_DETALLES set ";
		foreach ($datos as $key => $value) {
			$sql .=" ".$key." = ".quote($value).",";
		}
		
		$sql = rtrim($sql,',');
		$sql .=" where nro_transferencia = ".$datos['nro_transferencia']." and nro_renglon = ".$datos['nro_renglon'];
		try {
			if (toba::db()->ejecutar($sql)) {
				$sql = "update st_transferencias set total = (select sum(total) from ST_TRANSFERENCIAS_DETALLES where nro_transferencia = ".$datos['nro_transferencia']." ) where nro_transferencia = ".$datos['nro_transferencia'].";";
				if (toba::db()->ejecutar($sql)){
					toba::db()->cerrar_transaccion();
					return true;
				}
			}
		}catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::db()->abortar_transaccion();
            return false;
		}
		toba::db()->abortar_transaccion();
		return false;
	}

	static public function confirmar_transferencia($nro_transferencia, $con_transaccion = true) 
	{
		$sql = "BEGIN :resultado := PKG_STOCK.confirmar_transferencia(:nro_transferencia);END;";
		$parametros = array(array(	'nombre' => 'nro_transferencia',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $nro_transferencia),
							array(	'nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''),
							);
		$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', '', $con_transaccion);
		return $resultado[1]['valor'];
	}
	
	static public function anular_transferencia($nro_transferencia, $con_transaccion = true) 
	{
		$sql = "BEGIN :resultado := PKG_STOCK.anular_transferencia(:nro_transferencia);END;";
		$parametros = array(array(	'nombre' => 'nro_transferencia',
									'tipo_dato' => PDO::PARAM_INT,
									'longitud' => 32,
									'valor' => $nro_transferencia),
							array(	'nombre' => 'resultado',
									'tipo_dato' => PDO::PARAM_STR,
									'longitud' => 4000,
									'valor' => ''),
							);
		$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', '', $con_transaccion);
		return $resultado[1]['valor'];
	}
}
?>
