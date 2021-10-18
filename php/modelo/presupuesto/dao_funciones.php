<?php
class dao_funciones {
	
    static public function get_datos($filtro = array()){
        $where= "1=1";
        
        if (isset($filtro['id_padre'])){
          $where .= " and PRF.cod_funcion_padre = ".$filtro['id_padre'];
          unset($filtro['id_padre']);
        }
        
        if(isset($filtro))
          $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'prf', '1=1');
        
        $sql = "SELECT prf.*, 
                      pkg_pr_funciones.activa (prf.cod_funcion) ui_activa,
                      pkg_pr_funciones.mascara_aplicar (prf.cod_funcion) cod_funcion_masc,
                          '['
                       || pkg_pr_funciones.mascara_aplicar (prf.cod_funcion)
                       || '] '
                       || prf.descripcion AS descripcion_2
                FROM PR_FUNCIONES prf
                WHERE $where";
        
        $datos = toba::db()->consultar($sql);
        return $datos;
    }

 	static public function get_funciones($filtro = array()){
        $where= "1=1";
        
        if (isset($filtro['sin_padre'])){
        	$where .= " and PRF.cod_funcion_padre is null ";
        	unset($filtro['sin_padre']);
        }
        
        if(isset($filtro))
        	$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ff', '1=1');
        
        $sql = "SELECT prf.*, '['|| pkg_PR_FUNCIONES.mascara_aplicar (prf.COD_FUNCION)|| '] '|| prf.descripcion AS descripcion_2
                FROM PR_FUNCIONES prf
                WHERE $where";
        
        $datos = toba::db()->consultar($sql);
        return $datos;
    }
    
    
    
    
	static public function cant_niveles (){
		$sql ="SELECT pkg_PR_FUNCIONES.cant_niveles AS cant_niveles FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cant_niveles']; 
	}
	static public function es_hoja ($cod_funcion){
		if (!is_null($cod_funcion))
			$sql = "SELECT pkg_PR_FUNCIONES.ES_HOJA($cod_funcion) AS ES_HOJA FROM DUAL;";
		else
			$sql = "SELECT pkg_PR_FUNCIONES.ES_HOJA(null) AS ES_HOJA FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['es_hoja'];
	}
	static public function activo ($cod_funcion){
		if (!is_null($cod_funcion))
			$sql = "SELECT pkg_PR_FUNCIONES.ACTIVA($cod_funcion) AS activo FROM DUAL;";
		else
			$sql = "SELECT pkg_PR_FUNCIONES.ACTIVA(null) AS activo FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['activo'];
	}
	static public function imputable ($cod_funcion){
		if (!is_null($cod_funcion))
			$sql = "SELECT pkg_PR_FUNCIONES.IMPUTABLE($cod_funcion) AS imputable FROM DUAL;";
		else
			$sql = "SELECT pkg_PR_FUNCIONES.IMPUTABLE(null) AS imputable FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['imputable'];
	}
	static public function get_hijos ($cod_funcion){
		$sql = "select PRF.*, '[' || pkg_PR_FUNCIONES.MASCARA_APLICAR(PRF.COD_FUNCION) ||'] ' || PRF.descripcion as descripcion_2
				from PR_FUNCIONES PRF
				where PRF.COD_FUNCION_PADRE = $cod_funcion
				order by cod_FUNCION asc;";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
 	static public function cargar_descripcion($cod_funcion){
	   	$sql ="SELECT pkg_PR_FUNCIONES.CARGAR_DESCRIPCION($cod_funcion) AS descripcion FROM DUAL;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['descripcion'];
    }
	static public function tiene_hijos($cod_funcion){
	   	$sql ="SELECT pkg_PR_FUNCIONES.tiene_hijos($cod_funcion) AS tiene_hijo FROM DUAL;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['tiene_hijo'];
    }
    static public function get_nivel ($cod_funcion){
	     $sql = "SELECT NIVEL 
	             FROM PR_FUNCIONES
	             WHERE COD_FUNCION = $cod_funcion;";
	     $datos = toba::db()->consultar_fila($sql);
	     return $datos['nivel'];
    }
	static public function mascara_aplicar ($cod_funcion){
		$sql = "SELECT pkg_PR_FUNCIONES.mascara_aplicar($cod_funcion) COD_FUNCION FROM DUAL";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cod_funcion'];
	}
	
	static public function valor_del_nivel ($cod_funcion, $nivel){
		$sql ="SELECT pkg_PR_FUNCIONES.VALOR_DEL_NIVEL($cod_funcion, $nivel) AS valor FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['valor']; 
	}
	
	static public function ultimo_del_nivel ($nivel){
		if (is_null($nivel))
			$sql ="SELECT pkg_PR_FUNCIONES.ULTIMO_DEL_NIVEL(NULL) AS valor FROM DUAL;";
		else 
			$sql ="SELECT pkg_PR_FUNCIONES.ULTIMO_DEL_NIVEL($nivel) AS valor FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['valor']; 
	}
	
	static public function existe ($cod_funcion){
		$sql = "SELECT NVL (MIN (1), 0) cant
        		FROM PR_FUNCIONES
       			WHERE COD_FUNCION = $cod_funcion";
		$datos = toba::db()->consultar_fila($sql);
		if ($datos['cant'] > 0)
			return true;
		else 
			return false;
	}
	static public function armar_codigo ($nivel, $cod_funcion, $cod_funcion_padre){
		if ($cod_funcion_padre == null)
			$cod_funcion_padre = 'NULL';
		$sql ="SELECT  pkg_PR_FUNCIONES.armar_codigo($nivel, $cod_funcion, $cod_funcion_padre) AS valor FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['valor']; 
	}
	
	static public function rrhh ($cod_funcion){
		if ($cod_funcion == null)
			$cod_funcion = 'NULL';
		$sql ="SELECT  pkg_PR_FUNCIONES.RRHH($cod_funcion) AS valor FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['valor']; 
	}
	static public function eliminar_funcion ($cod_funcion, $con_transaccion = true){
      $sql = "BEGIN 
                :resultado := pkg_PR_FUNCIONES.eliminar_funcion(:codigo); 
              END;";        	
      $parametros = [ 
          [ 'nombre' => 'resultado', 
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 4000,
            'valor' => ''],
          [ 'nombre' => 'codigo', 
            'tipo_dato' => PDO::PARAM_STR,
            'longitud' => 20,
            'valor' => $cod_funcion],
      ];
      ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);    
	}
	
	
	static public function cargar_funcion($cod_funcion){
       $sql = "BEGIN pkg_PR_FUNCIONES.CARGAR_FUNCION(:codigo, :descripcion, :nivel, :cod_padre, :activa);END;";
       $parametros = array ( array(  'nombre' => 'codigo', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => $cod_funcion), 
       						 array(  'nombre' => 'descripcion', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 100,
                                     'valor' => ''),
      						 array(  'nombre' => 'nivel', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 3,
                                     'valor' => ''),
      						 array(  'nombre' => 'cod_padre', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => ''),
      						 array(  'nombre' => 'activa', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => ''));	  
      						     						 
        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
        $datos = array (	"cod_funcion"=>$cod_funcion, 
	        				"descripcion"=>$resultado[1]['valor'], 
	        				"nivel"=>$resultado[2]['valor'], 
         					"cod_funcion_padre"=>$resultado[3]['valor'],
        					"activa"=>$resultado[4]['valor']);
        return $datos;
   }
	
	static public function actualizar_funcion ($cod_funcion, $descripcion, $activa, $con_transaccion = true){
		try{
       		$sql = "BEGIN :resultado := pkg_PR_FUNCIONES.ACTUALIZAR_FUNCION(:codigo, :descripcion,:activa); END;";
       
	        $parametros = array (array(  'nombre' => 'codigo', 
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 9,
	                                     'valor' => $cod_funcion), 
	       						 array(  'nombre' => 'descripcion', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 100,
	                                     'valor' => $descripcion),
	      						 array(  'nombre' => 'activa', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 4,
	                                     'valor' => $activa),
	      						 array(  'nombre' => 'resultado', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 4000,
	                                     'valor' => ''));
	      	if ($con_transaccion)					 
            	toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            if ($resultado[3]['valor'] == 'OK'){
            	if ($con_transaccion){
                	toba::db()->cerrar_transaccion();
            	}
                 toba::notificacion()->info($resultado[3]['valor']);
	        }else{
	        	if ($con_transaccion){
	    	    	toba::db()->abortar_transaccion();
	        	}
                toba::notificacion()->error($resultado[3]['valor']);
            }
            
            return $resultado[3]['valor'];
            
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            if ($con_transaccion)
            	toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
            if ($con_transaccion)
            	toba::db()->abortar_transaccion();
        }   
	}
	
	static public function cambiar_estado_activa_hijos ($cod_funcion, $con_transaccion = true){
		try{
       		$sql = "BEGIN pkg_PR_FUNCIONES.CAMBIAR_ESTADO_ACTIVA_HIJOS(:codigo); END;";
       
	        $parametros = array (array(  'nombre' => 'codigo', 
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 9,
	                                     'valor' => $cod_funcion));
	      	if ($con_transaccion)				 
            	toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
           	if ($con_transaccion)
               	toba::db()->cerrar_transaccion();
               	
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            if ($con_transaccion)
            	toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
            if ($con_transaccion)
            	toba::db()->abortar_transaccion();
        }   
	}
	
	
   static public function crear_funcion($cod_funcion, $descripcion, $nivel, $cod_funcion_padre, $activa, $con_transaccion = true){
   	try{
       $sql = "BEGIN :resultado := pkg_PR_FUNCIONES.CREAR_FUNCION(:codigo, :descripcion, :nivel, :cod_padre, :activa); END;";
       
       $parametros = array ( array(  'nombre' => 'codigo', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => $cod_funcion), 
       						 array(  'nombre' => 'descripcion', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 100,
                                     'valor' => $descripcion),
      						 array(  'nombre' => 'nivel', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 3,
                                     'valor' => $nivel),
      						 array(  'nombre' => 'cod_padre', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => $cod_funcion_padre),
      						 array(  'nombre' => 'activa', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4,
                                     'valor' => $activa),
      						 array(  'nombre' => 'resultado', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4000,
                                     'valor' => ''));
      		if ($con_transaccion)				 
            	toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            if ($resultado[5]['valor'] == 'OK'){
            	if ($con_transaccion)
                	toba::db()->cerrar_transaccion();
	        }else{
	        	if ($con_transaccion)
	    	    	toba::db()->abortar_transaccion();
                toba::notificacion()->error($resultado[5]['valor']);
            }
            return $resultado[5]['valor'];
            
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            if ($con_transaccion)
            	toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
            if ($con_transaccion)
            	toba::db()->abortar_transaccion();
        }   
   }
}
?>