<?php
class dao_clasificadores_economicos_gasto {
	
	static public function get_datos ($filtro = array()){
		
		$where = ' 1=1 ';
    	if (isset($filtro['id_padre'])){
    		$where .= " and PREG.cod_economico_padre = ".$filtro['id_padre'];
    		unset($filtro['id_padre']);
    	}	
    	
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'PREG', '1=1');
		
		$sql ="SELECT PREG.* ,
					pkg_pr_economico_gastos.activo (preg.cod_economico) ui_activo,
			      	pkg_pr_economico_gastos.imputable (preg.cod_economico) ui_imputable,
			      	pkg_pr_economico_gastos.mascara_aplicar
			                                       (preg.cod_economico)
			                                                           cod_economico_masc,
			          '['|| pkg_pr_economico_gastos.mascara_aplicar (preg.cod_economico)|| '] '|| preg.descripcion AS descripcion_2
			     FROM PR_ECONOMICO_GASTOS PREG 
			    WHERE $where 
			 ORDER BY PREG.COD_ECONOMICO ASC ";
		return toba::db()->consultar($sql);
	}

	static public function get_clasificadores_economicos_gastos ($filtro = array()){
		
		$where = ' 1=1 ';
    	if (isset($filtro['sin_padre'])){
    		$where .= " and PREG.cod_economico_padre is null ";
    		unset($filtro['sin_padre']);
    	}	
    	
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'PRER', '1=1');
		
		$sql ="SELECT PREG.* , '['|| PKG_PR_ECONOMICO_GASTOS.MASCARA_APLICAR (PREG.COD_ECONOMICO) || '] '|| PREG.DESCRIPCION AS DESCRIPCION_2
			     FROM PR_ECONOMICO_GASTOS PREG 
			    WHERE $where 
			 ORDER BY PREG.COD_ECONOMICO ASC ";
		return toba::db()->consultar($sql);
	}
	
	static public function get_clasificador_economico_gasto_x_codigo ($cod_economico){
		$sql ="SELECT PREG.* 
			     FROM PR_ECONOMICO_GASTOS PREG 
			    WHERE RPEG.COD_ECONOMICO = $cod_economico";
		$datos = toba::db()->consultar_fila($sql);
		return $datos;
	}
	
 	static public function get_lov_clasificador_economico_gasto_x_codigo($cod_economico) {            
        if (isset($cod_economico)) {
            $sql = "SELECT    PKG_PR_ECONOMICO_GASTOS.MASCARA_APLICAR (PREG.COD_ECONOMICO) 
            	   || ' - '|| PKG_PR_ECONOMICO_GASTOS.CARGAR_DESCRIPCION (PREG.COD_ECONOMICO)LOV_DESCRIPCION,
					  	'['|| PKG_PR_ECONOMICO_GASTOS.MASCARA_APLICAR (PREG.COD_ECONOMICO)
					|| '] '|| PREG.DESCRIPCION AS DESCRIPCION_2
					FROM PR_ECONOMICO_GASTOS PREG
					WHERE PREG.COD_ECONOMICO = ".quote($cod_economico) .";";

            $datos = toba::db()->consultar_fila($sql);
            return $datos['lov_descripcion'];
        }else return null;
    }
    
  	static public function get_lov_clasificador_economico_gasto_x_nombre($nombre, $filtro = array()) {
        if (isset($nombre)) {
			$campos = array(
						'PREG.COD_ECONOMICO',
						'PKG_PR_ECONOMICO_GASTOS.mascara_aplicar(PREG.COD_ECONOMICO)',
						'PKG_PR_ECONOMICO_GASTOS.cargar_descripcion(PREG.COD_ECONOMICO)',
						"PREG.COD_ECONOMICO ||' - '|| PKG_PR_ECONOMICO_GASTOS.cargar_descripcion(PREG.COD_ECONOMICO)",
						"PKG_PR_ECONOMICO_GASTOS.mascara_aplicar(PREG.COD_ECONOMICO) ||' - '|| PKG_PR_ECONOMICO_GASTOS.cargar_descripcion(PREG.COD_ECONOMICO)",
				);
			$where = ctr_construir_sentencias::construir_sentencia_busqueda($nombre, $campos, false);
        } else {
            $where = '1=1';
        }
        
		if (isset($filtro['imputable'])) {
			$where .= " AND PKG_PR_ECONOMICO_GASTOS.IMPUTABLE(PREG.COD_ECONOMICO) = 'S' ";
			unset($filtro['imputable']);
		}

		if (isset($filtro['activo'])) {
			$where.= " AND PKG_PR_ECONOMICO_GASTOS.activo(PREG.COD_ECONOMICO) = 'S' "; 
			unset($filtro['activo']);
		}
		
        $sql = "SELECT  PREG.*, PKG_PR_ECONOMICO_GASTOS.MASCARA_APLICAR(PREG.COD_ECONOMICO)
				   || ' - '|| PKG_PR_ECONOMICO_GASTOS.CARGAR_DESCRIPCION (PREG.COD_ECONOMICO) LOV_DESCRIPCION,
				      '['|| PKG_PR_ECONOMICO_GASTOS.MASCARA_APLICAR (PREG.COD_ECONOMICO)|| '] '|| PREG.DESCRIPCION AS DESCRIPCION_2
				FROM PR_ECONOMICO_GASTOS PREG
                WHERE $where
                ORDER BY LOV_DESCRIPCION ASC;";  
        $datos = toba::db()->consultar($sql);
        return $datos;
    }
	static public function cant_niveles (){
		$sql ="SELECT PKG_PR_ECONOMICO_GASTOS.cant_niveles AS cant_niveles FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cant_niveles']; 
	}
	static public function es_hoja ($cod_economico){
		if (!is_null($cod_economico))
			$sql = "SELECT PKG_PR_ECONOMICO_GASTOS.ES_HOJA($cod_economico) AS ES_HOJA FROM DUAL;";
		else
			$sql = "SELECT PKG_PR_ECONOMICO_GASTOS.ES_HOJA(null) AS ES_HOJA FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['es_hoja'];
	}
	static public function activo ($cod_economico){
		if (!is_null($cod_economico))
			$sql = "SELECT PKG_PR_ECONOMICO_GASTOS.ACTIVO($cod_economico) AS activo FROM DUAL;";
		else
			$sql = "SELECT PKG_PR_ECONOMICO_GASTOS.ACTIVO(null) AS activo FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['activo'];
	}
	static public function imputable ($cod_economico){
		if (!is_null($cod_economico))
			$sql = "SELECT PKG_PR_ECONOMICO_GASTOS.IMPUTABLE($cod_economico) AS imputable FROM DUAL;";
		else
			$sql = "SELECT PKG_PR_ECONOMICO_GASTOS.IMPUTABLE(null) AS imputable FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['imputable'];
	}
	static public function get_hijos ($cod_economico){
		$sql = "select preg.*, '[' || PKG_PR_ECONOMICO_GASTOS.MASCARA_APLICAR(preg.cod_economico) ||'] ' || preg.descripcion as descripcion_2
				from PR_ECONOMICO_GASTOS PREG
				where preg.cod_economico_padre = $cod_economico
				order by cod_economico asc;";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
 	static public function cargar_descripcion($cod_economico){
	   	$sql ="SELECT PKG_PR_ECONOMICO_GASTOS.CARGAR_DESCRIPCION($cod_economico) AS descripcion FROM DUAL;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['descripcion'];
    }
	static public function tiene_hijos($cod_economico){
	   	$sql ="SELECT PKG_PR_ECONOMICO_GASTOS.tiene_hijos($cod_economico) AS tiene_hijo FROM DUAL;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['tiene_hijo'];
    }
    static public function get_nivel ($cod_economico){
	     $sql = "SELECT NIVEL 
	             FROM PR_ECONOMICO_GASTOS 
	             WHERE cod_economico = $cod_economico;";
	     $datos = toba::db()->consultar_fila($sql);
	     return $datos['nivel'];
    }
	static public function mascara_aplicar ($cod_economico){
		$sql = "SELECT PKG_PR_ECONOMICO_GASTOS.mascara_aplicar($cod_economico) cod_economico FROM DUAL";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cod_economico'];
	}
	
	static public function valor_del_nivel ($id_economico, $nivel){
		$sql ="SELECT PKG_PR_ECONOMICO_GASTOS.VALOR_DEL_NIVEL($id_economico, $nivel) AS valor FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['valor']; 
	}
	
	static public function ultimo_del_nivel ($nivel){
		if (is_null($nivel))
			$sql ="SELECT PKG_PR_ECONOMICO_GASTOS.ULTIMO_DEL_NIVEL(NULL) AS valor FROM DUAL;";
		else 
			$sql ="SELECT PKG_PR_ECONOMICO_GASTOS.ULTIMO_DEL_NIVEL($nivel) AS valor FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['valor']; 
	}
	
	static public function existe ($codigo){
		$sql = "SELECT NVL (MIN (1), 0) cant
        		FROM PR_ECONOMICO_GASTOS
       			WHERE cod_economico = $codigo";
		$datos = toba::db()->consultar_fila($sql);
		if ($datos['cant'] > 0)
			return true;
		else 
			return false;
	}
	static public function armar_codigo ($nivel, $cod_economico, $cod_economico_padre){
		if ($cod_economico_padre == null)
			$cod_economico_padre = 'NULL';
		$sql ="SELECT  PKG_PR_ECONOMICO_GASTOS.armar_codigo($nivel, $cod_economico, $cod_economico_padre) AS valor FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['valor']; 
	}
	
	static public function eliminar($cod_economico){
        $sql = "BEGIN 
        			:resultado := PKG_PR_ECONOMICO_GASTOS.eliminar_economico(:cod_economico);
    			END;";        	
        $parametros = [ 
        		[ 'nombre' => 'resultado', 
	              'tipo_dato' => PDO::PARAM_STR,
	              'longitud' => 4000,
	              'valor' => ''],
        		[ 'nombre' => 'cod_economico', 
	              'tipo_dato' => PDO::PARAM_STR,
	              'longitud' => 20,
	              'valor' => $cod_economico],
        ];
        return ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros); 
	}
	
	
	static public function cargar_economico($cod_economico){
       $sql = "BEGIN PKG_PR_ECONOMICO_GASTOS.CARGAR_ECONOMICO(:codigo, :descripcion, :nivel, :cod_economico_padre, :activo);END;";
       $parametros = array ( array(  'nombre' => 'codigo', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => $cod_economico), 
       						 array(  'nombre' => 'descripcion', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 100,
                                     'valor' => ''),
      						 array(  'nombre' => 'nivel', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 3,
                                     'valor' => ''),
      						 array(  'nombre' => 'cod_economico_padre', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => ''),
      						 array(  'nombre' => 'activo', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 1,
                                     'valor' => ''));
	      						 
        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
        $datos = array (	"cod_economico"=>$cod_economico, 
	        				"descripcion"=>$resultado[1]['valor'], 
	        				"nivel"=>$resultado[2]['valor'], 
         					"cod_economico_padre"=>$resultado[3]['valor'],
	        				"activo"=>$resultado[4]['valor'],
					      );
        return $datos;
   }
	
	static public function actualizar_economico ($cod_economico, $descripcion, $activo, $con_transaccion = true){
		try{
       		$sql = "BEGIN :resultado := PKG_PR_ECONOMICO_GASTOS.ACTUALIZAR_ECONOMICO(:codigo, :descripcion, :activo); END;";
       
	        $parametros = array (array(  'nombre' => 'codigo', 
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 9,
	                                     'valor' => $cod_economico), 
	       						 array(  'nombre' => 'descripcion', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 100,
	                                     'valor' => $descripcion),
	      						 array(  'nombre' => 'activo', 
	                                     'tipo_dato' => PDO::PARAM_STR,
	                                     'longitud' => 4,
	                                     'valor' => $activo),
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
	
	static public function cambiar_estado_activo_hijos ($cod_economico, $con_transaccion = true){
		try{
       		$sql = "BEGIN PKG_PR_ECONOMICO_GASTOS.CAMBIAR_ESTADO_ACTIVO_HIJOS($cod_economico); END;";
       
	        $parametros = array (array(  'nombre' => 'codigo', 
	                                     'tipo_dato' => PDO::PARAM_INT,
	                                     'longitud' => 9,
	                                     'valor' => $cod_economico));
	      	if ($con_transaccion)				 
            	toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, array());
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
   static public function crear_economico($cod_economico, $descripcion, $nivel, $cod_economico_padre, $activo, $con_transaccion = true){
   	try{
       $sql = "BEGIN :resultado := PKG_PR_ECONOMICO_GASTOS.CREAR_ECONOMICO(:codigo, :descripcion, :nivel, :cod_economico_padre, :activo); END;";
       
       $parametros = array ( array(  'nombre' => 'codigo', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => $cod_economico), 
       						 array(  'nombre' => 'descripcion', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 100,
                                     'valor' => $descripcion),
      						 array(  'nombre' => 'nivel', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 3,
                                     'valor' => $nivel),
      						 array(  'nombre' => 'cod_economico_padre', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 9,
                                     'valor' => $cod_economico_padre),
      						 array(  'nombre' => 'activo', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4,
                                     'valor' => $activo),
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