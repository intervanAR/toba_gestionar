<?php

class dao_articulos_compra {

	public static function get_listado_articulos($filtro = array()){
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}
        $where = self::get_where_articulos_compra($filtro);
        $sql = "SELECT ca.ID_RUBRO,
                         ca.COD_CONCEPTO,
                         ca.cod_item,
                         ca.unidad,
                         ca.cod_partida,
                         ccl.descripcion cod_clase,
        			   ca.cod_articulo cod_articulo, ca.descripcion descripcion,
				       ca.cod_articulo
				       || ' - '
				       || REPLACE (REPLACE (ca.descripcion, '<', '«'), '>', '»')
				                                                     cod_articulo_descripcion,
				       CASE
				          WHEN ca.activo = 'S'
				             THEN 'Si'
				          ELSE 'No'
				       END activo_format,
				       CASE
				          WHEN ca.ing_patrimonio = 'S'
				             THEN 'Si'
				          ELSE 'No'
				       END ing_patrimonio_format,
				       CASE
				          WHEN ca.ing_stock = 'S'
				             THEN 'Si'
				          ELSE 'No'
				       END ing_stock_format
				FROM CO_ARTICULOS ca left join co_clases ccl on ccl.cod_clase = ca.cod_clase
				WHERE $where 
				ORDER BY COD_ARTICULO";
       	$sql= dao_varios::paginador($sql, null, $desde, $hasta);
       	$datos = toba::db()->consultar($sql);
		foreach ($datos as $clave => $dato) {
			$datos[$clave]['descripcion'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($dato['descripcion'], 'descripcion_'.$clave, 50, 1, true);
		}
	    return $datos;
	}
	
	
	public static function get_articulos_compra($nombre, $filtro = array()) {
		$desde= null;
		$hasta= null;
		if(isset($filtro['desde'])){
			$desde= $filtro['desde'];
			$hasta= $filtro['hasta'];

			unset($filtro['desde']);
			unset($filtro['hasta']);
		}
		$where = " 1=1 ";
        if (isset($nombre)) {
            $trans_cod_concepto = ctr_construir_sentencias::construir_translate_ilike('ca.cod_articulo', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('ca.descripcion', $nombre);
            $where .= " AND ($trans_cod_concepto OR $trans_descripcion)";
        }
		
		if (isset($filtro['cod_articulo_recepcion'])) {
			$where .= " AND  ca.COD_PARTIDA = (SELECT ca1.cod_partida FROM co_articulos ca1 WHERE ca1.cod_articulo = " . quote($filtro['cod_articulo_recepcion']) . ") AND ca.cod_articulo <> " . quote($filtro['cod_articulo_recepcion']) . "";
			unset($filtro['cod_articulo_recepcion']);
		}
		
        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'ca', '1=1');
        $sql = "SELECT ca.ID_RUBRO,
                         ca.COD_CONCEPTO,
                         ca.cod_item,
                         ca.unidad,
                         ca.cod_partida,
                         ccl.descripcion cod_clase,
        			   ca.cod_articulo cod_articulo, ca.descripcion descripcion,
				       ca.cod_articulo 
				       || ' - '
				       || REPLACE (REPLACE (ca.descripcion, '<', '«'), '>', '»')
				                                                     cod_articulo_descripcion,
				       CASE
				          WHEN ca.activo = 'S'
				             THEN 'Si'
				          ELSE 'No'
				       END activo_format,
				       CASE
				          WHEN ca.ing_patrimonio = 'S'
				             THEN 'Si'
				          ELSE 'No'
				       END ing_patrimonio_format,
				       CASE
				          WHEN ca.ing_stock = 'S'
				             THEN 'Si'
				          ELSE 'No'
				       END ing_stock_format
				FROM CO_ARTICULOS ca left join co_clases ccl on ccl.cod_clase = ca.cod_clase
				WHERE $where 
				ORDER BY COD_ARTICULO";
        /* 
           Se saco esta condicion 08/11/2016 
            and pkg_catalogo.existe_concepto(ca.cod_articulo) = 'S'
         */
       	$sql= dao_varios::paginador($sql, null, $desde, $hasta);
       	return toba::db()->consultar($sql);
    }
    
    public static function get_cantidad_articulo_compra ($filtro = array()){
    	$where = self::get_where_articulos_compra($filtro);
		
        $sql_sel = "SELECT  COUNT(1) cantidad
					FROM co_articulos ca
					WHERE $where;";
        $datos = toba::db()->consultar_fila($sql_sel);
		if (isset($datos['cantidad'])) {
			return $datos['cantidad'];
		} else {
			return 0;
		}
        return $datos;
    }
    
	public static function get_where_articulos_compra($filtro = array()) {
		$where = " 1=1 ";
        if (isset($filtro['cod_articulo'])) {
            $where .= "AND " . ctr_construir_sentencias::construir_translate_ilike('ca.cod_articulo', $filtro['cod_articulo']);
            unset($filtro['cod_articulo']);
        }

        if (isset($filtro['descripcion'])) {
            $where .= " AND " .ctr_construir_sentencias::construir_translate_ilike('ca.descripcion', $filtro['descripcion']) ;
            unset($filtro['descripcion']);
        }
		
		if (isset($filtro['ids_comprobantes'])) {
            $where .= "AND co.cod_articulo IN (" . $filtro['ids_comprobantes'] . ") ";
            unset($filtro['ids_comprobantes']);
        }
		if (isset($filtro['cod_articulo_recepcion'])) {
			$where .= " AND  ca.COD_PARTIDA = (SELECT ca1.cod_partida FROM co_articulos ca1 WHERE ca1.cod_articulo = " . quote($filtro['cod_articulo_recepcion']) . ") AND ca.cod_articulo <> " . quote($filtro['cod_articulo_recepcion']) . "";
			unset($filtro['cod_articulo_recepcion']);
		}
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ca', '1=1');
		return $where;
	}
	
 	static public function get_lov_articulo_x_codigo ($cod_articulo){
    	$sql = "select art.cod_articulo ||' - '|| art.descripcion lov_descripcion
    			from co_articulos art
    			where art.cod_articulo = $cod_articulo";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['lov_descripcion'];
    }
    
    static public function get_lov_articulo_x_nombre ($nombre, $filtro = array()){
    	
    	if (isset($nombre)) { 
   			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('art.cod_articulo', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('art.descripcion', $nombre);
            $where = "($trans_codigo OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
        
    	if (isset($filtro['articulo_desde'])){
    		$where .= " and art.cod_articulo >= ".$filtro['articulo_desde'];
    		unset($filtro['articulo_desde']);
    	}
    	
    	if (isset($filtro['primero'])){
    		$where .= " and art.cod_articulo = (select min(cod_articulo) 
    											  from co_articulos 
    											 where activo = 'S')";
    		unset($filtro['primero']);
    	}
    	
    	if (isset($filtro['ultimo'])){
    		$where .= " and art.cod_articulo = (select max(cod_articulo) 
    											  from co_articulos 
    											 where activo = 'S')";
    		unset($filtro['ultimo']);
    	}

    	if (isset($filtro['en_deposito']))
    	{
    		$where .=" AND art.cod_articulo IN (SELECT ststo.cod_articulo
                                          FROM st_stock ststo
                                         WHERE ststo.cod_deposito = ".$filtro['en_deposito'].")";
    		unset($filtro['en_deposito']);
    	}
    	
    	$where .= ' and '.ctr_construir_sentencias::get_where_filtro($filtro, 'art');
    	
    	$sql = "select art.*, art.cod_articulo ||' - '|| art.descripcion lov_descripcion
    			from co_articulos art
    			where $where
    			order by art.cod_articulo";
    	return toba::db()->consultar($sql);
    }
	
	static public function get_lov_articulos_de_recepcion ($nombre, $filtro = array(),$nro_recepcion){
    	
    	if (isset($nombre)) { 
   			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('ca.cod_articulo', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('ca.descripcion', $nombre);
            $where = "($trans_codigo OR $trans_descripcion)";
        } 
        if (isset($nro_recepcion)){
			$join = "co_items_recepcion co, co_articulos ca"; 
    		$where .= " and co.cod_articulo = ca.cod_articulo and co.nro_recepcion = $nro_recepcion";
    	}else{
    		$join = " co_articulos ca "; 
			$where .= " and activo = 'S'";
    	}
    	
    	$where .= ' and '.ctr_construir_sentencias::get_where_filtro($filtro, 'ca');
    	
	    	$sql = "select ca.cod_articulo,ca.cod_articulo ||' - '|| ca.descripcion         lov_descripcion
			from $join 
		    where $where";

    	return toba::db()->consultar($sql);
    }
	public static function get_articulo_descripcion_x_cod_articulo($cod_articulo) {
        if (isset($cod_articulo)) {
            $sql = "SELECT	ca.COD_articulo || ' - ' || replace(replace(ca.DESCRIPCION, '<','«'), '>', '»') cod_articulo_descripcion
					FROM CO_ARTICULOS ca
					WHERE cod_articulo = " . quote($cod_articulo) . ";";
            $resultado = toba::db()->consultar_fila($sql);
            if (isset($resultado) && !empty($resultado) && isset($resultado['cod_articulo_descripcion'])) {
                return $resultado['cod_articulo_descripcion'];
            } else {
                return '';
            }
        } else {
            return '';
        }
    }
	
    public static function get_datos_articulo_compra($cod_articulo) {
        if (isset($cod_articulo)) {
			$sql_sel = "SELECT  ca.*,
								ca.cod_articulo || ' - ' || ca.descripcion lov_descripcion
						FROM co_articulos ca
						WHERE ca.cod_articulo = " . quote($cod_articulo) . ";";
			$datos = toba::db()->consultar_fila($sql_sel);
			return $datos;
		} else {
			return array();
		}
    }
	
	public static function get_precios_testigos($cod_articulo=null) {
		if (isset($cod_articulo)) {
			$sql_sel = "SELECT *
						FROM CO_PRECIOS_TESTIGO cpt
						WHERE cpt.cod_articulo = " . quote($cod_articulo) . "
						ORDER BY fecha_vigencia DESC;";
			$datos = toba::db()->consultar($sql_sel);
			return $datos;
		} else {
			return array();
		}
		
    }
    
    public static function get_lov_clase_articulo_x_codigo ($codigo){
    	if (isset($codigo)){
    		$sql = "SELECT coc.*, coc.cod_clase ||' - '|| coc.descripcion lov_descripcion
					FROM co_clases coc
					WHERE coc.cod_clase = ".quote($codigo);
    		$datos = toba::db()->consultar_fila($sql);
    		return $datos['lov_descripcion'];
    	}else return null;
    }
    
    static public function get_lov_clase_articulo_x_nombre ($nombre, $filtro = array()){
   		if (isset($nombre)) { 
   			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('cod_clase', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
            $where = "($trans_codigo OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
        $where .= ' and '.ctr_construir_sentencias::get_where_filtro($filtro, 'coc');
        
        $sql = "SELECT coc.*, coc.cod_clase ||' - '|| coc.descripcion lov_descripcion
				FROM co_clases coc
				WHERE $where
				ORDER BY DESCRIPCION";
        return toba::db()->consultar($sql);
	}
	
	  public static function get_lov_rubro_articulo_x_id ($id_rubro){
    	if (isset($id_rubro)){
    		$sql = "SELECT cor.*, cor.id_rubro ||' - '|| cor.COD_RUBRO || ' - ' || cor.descripcion lov_descripcion
  					FROM co_rubros cor
 					WHERE cor.id_rubro = ".quote($id_rubro);
    		$datos = toba::db()->consultar_fila($sql);
    		return $datos['lov_descripcion'];
    	}else return null;
    }
    
    static public function get_lov_rubro_articulo_x_nombre ($nombre, $filtro = array()){
   		if (isset($nombre)) { 
   			$trans_id = ctr_construir_sentencias::construir_translate_ilike('id_rubro', $nombre);
   			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('cod_rubro', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
            $where = "($trans_id OR $trans_codigo OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
        $where .= ' and '.ctr_construir_sentencias::get_where_filtro($filtro, 'cor');
        
        $sql = "SELECT cor.*, cor.id_rubro ||' - '|| cor.COD_RUBRO || ' - ' || cor.descripcion lov_descripcion
  				FROM co_rubros cor
 				WHERE $where
				ORDER BY id_rubro asc";
        return toba::db()->consultar($sql);
	}
	
	static public function copiar_articulo ($cod_articulo){
		try {
             $sql = "BEGIN :resultado := PKG_CATALOGO.copiar_articulo(:cod_articulo, :cod_articulo_nuevo);END;";
             $parametros = array(array( 'nombre' => 'cod_articulo',
				                        'tipo_dato' => PDO::PARAM_INT,
				                        'longitud' => 32,
				                        'valor' => $cod_articulo),
             					 array( 'nombre' => 'cod_articulo_nuevo',
				                        'tipo_dato' => PDO::PARAM_INT,
				                        'longitud' => 32,
				                        'valor' => ''),
				                  array('nombre' => 'resultado',
				                        'tipo_dato' => PDO::PARAM_STR,
				                        'longitud' => 4000,
				                        'valor' => ''));
				                  
              toba::db()->abrir_transaccion();
                
              $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
              $respuesta = null;
              
              if ($resultado[2]['valor'] <> 'OK'){
		 		toba::db()->abortar_transaccion();
		 		$respuesta = array("resultado"=>$resultado[2]['valor']);
	      	  }else{
    	   		toba::db()->cerrar_transaccion();
				$respuesta = array("resultado"=>$resultado[2]['valor'],"cod_articulo_nuevo"=>$resultado[1]['valor']);
	          }
	          return $respuesta;
        	 
			} catch (toba_error_db $e_db) {
				if ($con_transaccion) {
					toba::db()->abortar_transaccion();
				}
				toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
				toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());            
			} catch (toba_error $e) {
				if ($con_transaccion) {
					toba::db()->abortar_transaccion();
				}
				toba::notificacion()->error('Error '.$e->get_mensaje());
				toba::logger()->error('Error '.$e->get_mensaje());            
			}
	}
		
    static public function get_ui_propiedad_descripcion ($cod_articulo, $cod_propiedad){
    	$sql = "SELECT cl.orden as ui_orden, upper(cop.descripcion) ui_descripcion
				  FROM co_clases_propiedades cl, co_articulos ca, co_propiedades cop
				 WHERE cl.cod_clase = ca.cod_clase
				   AND cl.cod_propiedad = cop.cod_propiedad
				   and cop.cod_propiedad = $cod_propiedad
				   and ca.cod_articulo = $cod_articulo";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos;
    } 
    
    static public function calcular_descripcion ($cod_articulo, $con_transaccion = true){
    	try {
             $sql = "BEGIN :resultado := PKG_CATALOGO.calcular_descripcion(:cod_articulo);END;";
             $parametros = array(array( 'nombre' => 'cod_articulo',
				                        'tipo_dato' => PDO::PARAM_INT,
				                        'longitud' => 32,
				                        'valor' => $cod_articulo),
				                  array('nombre' => 'resultado',
				                        'tipo_dato' => PDO::PARAM_STR,
				                        'longitud' => 4000,
				                        'valor' => ''));
			 if ($con_transaccion)
			 	toba::db()->abrir_transaccion();
			 	                  
              $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
              
              if ($resultado[1]['valor'] <> 'OK'){
              	if ($con_transaccion)
		 			toba::db()->abortar_transaccion();
	      	  }else{
	      	  	if ($con_transaccion)
    	   			toba::db()->cerrar_transaccion();
	          }
			} catch (toba_error_db $e_db) {
				if ($con_transaccion) {
					toba::db()->abortar_transaccion();
				}
				toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
				toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());            
			} catch (toba_error $e) {
				if ($con_transaccion) {
					toba::db()->abortar_transaccion();
				}
				toba::notificacion()->error('Error '.$e->get_mensaje());
				toba::logger()->error('Error '.$e->get_mensaje());            
			}
    }
    
    static public function get_propiedades ($filtro  = array()){
    	$where = " 1=1 ";
    	if (isset($filtro['descripcion']) && !empty($filtro['descripcion'])){
    		$where .= " and pro.descripcion like '%".$filtro['descripcion']."%'";
    		unset($filtro['descripcion']);
    	}
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'pro', '1=1');
    	$sql = "SELECT PRO.* 
    			  FROM CO_PROPIEDADES PRO
    			 WHERE $where
    			 ORDER BY PRO.COD_PROPIEDAD ASC";
    	$datos = toba::db()->consultar($sql);
    	return $datos;
    }
    
    static public function get_clases ($filtro = array()){
    	$where = " 1=1 ";
    	if (isset($filtro['descripcion']) && !empty($filtro['descripcion'])){
    		$where .= " and cla.descripcion like '%".$filtro['descripcion']."%'";
    		unset($filtro['descripcion']);
    	}
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'cla', '1=1');
    	$sql = "SELECT CLA.*
  				  FROM co_clases cla
    			 WHERE $where
    			 ORDER BY CLA.COD_CLASE ASC";
    	$datos = toba::db()->consultar($sql);
    	return $datos;
    }
   
    static public function get_lov_propiedades_x_nombre ($nombre, $filtro = array()){
    	if (isset($nombre)) { 
   			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('cod_propiedad', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
            $where = "($trans_codigo OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
        $where .= ' and '.ctr_construir_sentencias::get_where_filtro($filtro, 'pro');
        
        $sql = "select pro.*, pro.cod_propiedad ||' - '|| pro.descripcion lov_descripcion
				from co_propiedades pro
    			where $where
				ORDER BY lov_descripcion";
        return toba::db()->consultar($sql);
    }
    
    static public function get_lov_propiedades_x_codigo ($cod_propiedad){
    	$sql = "select pro.cod_propiedad ||' - '|| pro.descripcion lov_descripcion
    			from co_propiedades pro
    			where pro.cod_propiedad = $cod_propiedad";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['lov_descripcion'];
    }

    static public function get_descripcion ($cod_articulo){
    	$sql = "select descripcion from co_articulos where cod_articulo = ".quote($cod_articulo);
    	$datos = toba::db()->consultar($sql);
    	return $datos;
    }

    static public function get_articulo_descripcion($cod_articulo){
    	$sql = "select descripcion from co_articulos where cod_articulo = ".quote($cod_articulo);
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['descripcion'];
    }
}

?>
