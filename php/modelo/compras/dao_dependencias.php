<?php

class dao_dependencias {
    
	static public function get_dependencia_x_codigo ($cod_dependencia){
		$sql ="SELECT * FROM PA_DEPENDENCIAS WHERE COD_DEPENDENCIA = $cod_dependencia";
		$datos = toba::db()->consultar_fila($sql);
		return $datos;
	}

	static public function get_dependencia_x_id ($id_dependencia){
		$sql ="SELECT * FROM PA_DEPENDENCIAS WHERE id_dependencia = $id_dependencia";
		$datos = toba::db()->consultar_fila($sql);
		return $datos;
	}
	
    static public function get_dependencias($filtro= array()){
        
        $where = ' 1=1 ';
    	if (isset($filtro['sin_padre'])){
    		$where .= " and id_dependencia_padre is null ";
    		unset($filtro['sin_padre']);
    	}	
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'pad', '1=1');
        
        $sql = "SELECT	pad.*, 
        '['||pkg_PA_DEPENDENCIAS.mascara_aplicar(pad.cod_DEPENDENCIA)||'] '||substr(pad.DESCRIPCION,1,90) as descripcion_2,
        decode(pad.activa,'S','Si','No') activa_format
                FROM PA_DEPENDENCIAS pad
                WHERE $where
                order by cod_dependencia asc;";
		
        $datos = toba::db()->consultar($sql);
        return $datos;      
    }
    
    static public function get_lov_dependencias_x_cod_dependencia($cod_dependencia) {            
        if (isset($cod_dependencia)) {
            $sql = "SELECT pkg_pa_dependencias.mascara_aplicar(pad.cod_dependencia) ||' - '|| pkg_pa_dependencias.cargar_descripcion(pad.cod_dependencia) lov_descripcion,
            			   '[' || pkg_pa_dependencias.MASCARA_APLICAR(pad.cod_dependencia) ||'] ' || pad.descripcion as descripcion_2
                FROM PA_DEPENDENCIAS pad
                WHERE pad.cod_dependencia = ".quote($cod_dependencia) .";";

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
        
    static public function get_lov_dependencias_x_nombre($nombre, $filtro = array()) {
        
        if (isset($nombre)) {
			$campos = array(
						'pad.cod_dependencia',
						'pkg_pa_dependencias.mascara_aplicar(pad.cod_dependencia)',
						'pkg_pa_dependencias.cargar_descripcion(pad.cod_dependencia)',
						"pad.cod_dependencia ||' - '|| pkg_pa_dependencias.cargar_descripcion(pad.cod_dependencia)",
						"pkg_pa_dependencias.mascara_aplicar(pad.cod_dependencia) ||' - '|| pkg_pa_dependencias.cargar_descripcion(pad.cod_dependencia)",
				);
			$where = ctr_construir_sentencias::construir_sentencia_busqueda($nombre, $campos, false);
        } else {
            $where = '1=1';
        }
	
		if (isset($filtro['en_actividad'])) {
			$where .= " AND pkg_pa_dependencias.ACTIVO(pad.cod_dependencia) = 'S' ";
			unset($filtro['en_actividad']);
		}
		
        $sql = "SELECT  pad.*, pkg_pa_dependencias.mascara_aplicar(pad.cod_dependencia) ||' - '|| pkg_pa_dependencias.cargar_descripcion(pad.cod_dependencia) as lov_descripcion
                FROM PA_DEPENDENCIAS pad
                WHERE $where
                ORDER BY lov_descripcion ASC;";  
        $datos = toba::db()->consultar($sql);
        return $datos;
    }
	
    
	static public function cant_niveles (){
		$sql ="SELECT pkg_pa_dependencias.cant_niveles AS cant_niveles FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cant_niveles']; 
	}
	
	static public function es_hoja ($cod_dependencia){
		$sql = "SELECT pkg_pa_dependencias.ES_HOJA($cod_dependencia) AS ES_HOJA FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['es_hoja'];
	}
	
	static public function activa ($cod_dependencia){
		$sql = "SELECT pkg_pa_dependencias.ACTIVo($cod_dependencia) AS activo FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['activo'];
	}
			
	static public function get_hijos ($id_dependencia){
		$sql = "select PAD.*, '[' || pkg_pa_dependencias.MASCARA_APLICAR(PAD.cod_dependencia) ||'] ' || PAD.descripcion as descripcion_2
				from PA_DEPENDENCIAS PAD
				where PAD.id_dependencia_padre = $id_dependencia
				order by cod_dependencia asc;";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	
 	static public function cargar_descripcion($cod_dependencia){
	   	$sql ="SELECT pkg_pa_dependencias.CARGAR_DESCRIPCION($cod_dependencia) AS descripcion FROM DUAL;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['descripcion'];
   }
	
	static public function tiene_hijos($cod_dependencia){
	   	$sql ="SELECT pkg_pa_dependencias.tiene_hijos($cod_dependencia) AS tiene_hijo FROM DUAL;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['tiene_hijo'];
   }
	static public function crear_dependencia($codigo, 
										 $descripcion, 
										 $nivel, 
										 $id_dependencia_padre, 
										 $activo, $con_transaccion = true){
        if (is_null($id_dependencia_padre)){
            $id_dependencia_padre = '';
        }

       $sql = "BEGIN :resultado := pkg_pa_dependencias.crear_dependencia(:codigo, :descripcion, :nivel, :id_dependencia_padre, :activo);END;";

       $parametros = array ( array(  'nombre' => 'codigo', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => $codigo), 
       						 array(  'nombre' => 'descripcion', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 100,
                                     'valor' => $descripcion),
      						 array(  'nombre' => 'nivel', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 3,
                                     'valor' => $nivel),
      						 array(  'nombre' => 'id_dependencia_padre', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 9,
                                     'valor' => $id_dependencia_padre),
      						 array(  'nombre' => 'activo', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => $activo),
      						 array(  'nombre' => 'resultado', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 400,
                                     'valor' => ''));
	      						 
      	if ($con_transaccion)
      		toba::db()->abrir_transaccion();
      		
        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
        if ($con_transaccion){
        	if ($resultado[5]['valor'] != 'OK'){
        		toba::db()->abortar_transaccion();
        		toba::notificacion()->error($resultado[5]['valor']);
        	}else{
        		toba::db()->cerrar_transaccion();
        	}
        }
        return $resultado[5]['valor'];
   }
   
	static public function actualizar_dependencia($codigo, $descripcion, $activo, $con_transaccion = true){
	    $sql = "BEGIN :resultado := pkg_pa_dependencias.actualizar_dependencia(:codigo, :descripcion, :activo);END;";

    	$parametros = array (array(  'nombre' => 'codigo', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => $codigo), 
       						 array(  'nombre' => 'descripcion', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 100,
                                     'valor' => $descripcion),
      						 array(  'nombre' => 'activo', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => $activo),
      						 array(  'nombre' => 'resultado', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 400,
                                     'valor' => ''));
	      						 
      	if ($con_transaccion)
      		toba::db()->abrir_transaccion();
      		
        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
        if ($con_transaccion){
        	if ($resultado[3]['valor'] != 'OK'){
        		toba::db()->abortar_transaccion();
        		toba::notificacion()->error($resultado[3]['valor']);
        	}else{
        		toba::db()->cerrar_transaccion();
        	}
        }
        return $resultado[3]['valor'];
   }
   
	static public function cargar_dependencia($cod_dependencia){
       $sql = "BEGIN pkg_pa_dependencias.cargar_dependencia(:codigo, :descripcion, :nivel, :id_dependencia_padre, :activa);END;";

       $parametros = array ( array(  'nombre' => 'codigo', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => $cod_dependencia), 
       						 array(  'nombre' => 'descripcion', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 100,
                                     'valor' => ''),
      						 array(  'nombre' => 'nivel', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 3,
                                     'valor' => ''),
      						 array(  'nombre' => 'id_dependencia_padre', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => ''),
      						 array(  'nombre' => 'activa', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => ''),);
	      						 
        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
	    $datos = array ("cod_dependencia"=>$cod_dependencia, 
	        			"descripcion"=>$resultado[1]['valor'], 
	        			"nivel"=>$resultado[2]['valor'], 
	        			"id_dependencia_padre"=>$resultado[3]['valor'], 
	        			"activa"=>$resultado[4]['valor']);
        return $datos;
   }
   
	static public function get_nivel_dependencia ($id_dependencia){
	     $sql = "SELECT nivel_dependencia nivel
	             FROM pa_dependencias
	             WHERE id_dependencia = $id_dependencia;";
	     $datos = toba::db()->consultar_fila($sql);
	     return $datos['nivel'];
    }
	
	static public function mascara_aplicar ($cod_dependencia){
		$sql = "SELECT pkg_pa_dependencias.mascara_aplicar($cod_dependencia) cod_dependencia FROM DUAL";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cod_dependencia'];
	}

	static public function mascara_buscar ($id_dependencia, $nivel_elegido){
        if (is_null($id_dependencia) || empty($id_dependencia))
    		$sql = "SELECT pkg_pa_dependencias.mascara_buscar(null, $nivel_elegido) max_cod FROM DUAL";
        else
            $sql = "SELECT pkg_pa_dependencias.mascara_buscar($id_dependencia, $nivel_elegido) max_cod FROM DUAL";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['max_cod'];
	}
	
	static public function existe_dependencia ($cod_dependencia){
		$sql = "SELECT NVL (MIN (1), 0) cant
        		FROM pa_dependencias 
       			WHERE cod_dependencia = $cod_dependencia";
		$datos = toba::db()->consultar_fila($sql);
		if ($datos['cant'] > 0)
			return true;
		else 
			return false;
	}
	
	static public function armar_codigo_dependencia($nivel, $cod_dependencia, $cod_dependencia_padre){
		if ($cod_dependencia_padre == null)
			$cod_dependencia_padre = 'NULL';
		$sql ="SELECT  pkg_pa_dependencias.armar_codigo($nivel, $cod_dependencia, $cod_dependencia_padre) AS valor FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['valor']; 
	}
	
	static public function eliminar_dependencia ($id_dependencia, $con_transaccion = true){
		 try{
            $sql = "BEGIN :resultado := pkg_pa_dependencias.eliminar_dependencia(:id_dependencia); END;";        	
            $parametros = array ( array(  'nombre' => 'id_dependencia', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 20,
                                          'valor' => $id_dependencia),
                                  array(  'nombre' => 'resultado', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => ''),
                            );
            if ($con_transaccion)
            	toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            if ($con_transaccion){
	            if ($resultado[1]['valor'] == 'OK'){
	                toba::db()->cerrar_transaccion();
	            }else{
	                toba::db()->abortar_transaccion();
	                toba::notificacion()->error($resultado[1]['valor']);
	            }
            }
            return $resultado[1]['valor'];
            
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
            toba::db()->abortar_transaccion();
        }   
	}
	static public function cambiar_estado_activo_hijos ($id_dependencia){
		try {
			$sql = "BEGIN pkg_pa_dependencias.cambiar_estado_activo_hijos(:id_dependencia);END;";
	        $parametros = array ( array( 'nombre' => 'id_dependencia', 
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 9,
	                                     'valor' => $id_dependencia));
	        
	        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
	       	return 'OK';
		}catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
        }catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
        }
		
	}
	
	static public function cambiar_estado_figur_hijos ($id_dependencia, $figurativo){
		try {
			$sql = "BEGIN pkg_pa_dependencias.cambiar_estado_figur_hijos(:id_dependencia, :figurativo);END;";
	        $parametros = array ( array( 'nombre' => 'id_dependencia', 
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 9,
	                                     'valor' => $id_dependencia),
	        					  array( 'nombre' => 'figurativo', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 1,
	                                     'valor' => $figurativo));
	        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
			return 'OK';   
		}catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
        }catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
        }
	}
	
	static public function cambiar_estado_fuente_hijos ($id_dependencia, $reimputacion){
		try {
			$sql = "BEGIN pkg_pa_dependencias.cambiar_fuente_hijos(:id_dependencia);END;";
	        $parametros = array ( array( 'nombre' => 'id_dependencia', 
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 9,
	                                     'valor' => $id_dependencia),);
	        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
			return 'OK'; 
		}catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
        }catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
        }
	}
	
	static public function ultimo_del_nivel ($id_dependencia_padre){
    	if ($cod_dependencia == null)	
    		$cod_dependencia = 'null';
    	$sql = "SELECT pkg_pa_dependencias.ultimo_del_nivel ($id_dependencia_padre) valor from dual;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['valor'];
    	
    }
	
  	static public function valor_del_nivel ($codigo, $nivel){
    	if ($codigo == null)	
    		$codigo = 'null';
    	if ($nivel == null)
    		$nivel = 'null';
    	if (isset($nivel)  && !empty($nivel)){
	    	$sql ="SELECT pkg_pa_dependencias.valor_del_nivel($codigo, $nivel) valor from dual;";
	   		$datos = toba::db()->consultar_fila($sql);
	   		return $datos['valor'];
    	}else return null;
    }



    static public function get_nivles_dependencias($filtro =[], $orden = []){
        $where = ' 1=1 ';
          
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'pand', '1=1');

        $sql = "SELECT pand.*
                  FROM PA_NIVEL_DEPENDENCIAS pand
                where $where
                ORDER BY PAND.nivel_dependencia ASC";
        return toba::db()->consultar($sql);

    }
}

?>
