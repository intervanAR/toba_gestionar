<?php
class dao_cuentas_contables {
	
	
	static public function get_cuenta_nro ($nro_cuenta_contable){
		if (isset($nro_cuenta_contable)){
			$sql = "SELECT * FROM CP_CUENTAS WHERE NRO_CUENTA_CONTABLE = $nro_cuenta_contable;";
			$datos = toba::db()->consultar_fila($sql);
			return $datos;
		}else return null;
	}
	
	static public function get_nodos_sin_padres (){
		$sql = "SELECT   c.*, ' ['|| pkg_cp_cuentas.mascara_aplicar(c.nro_cuenta_contable)||'] '||c.descripcion as descripcion2 
				  FROM CP_CUENTAS C
        		WHERE C.NRO_CUENTA_CONTABLE_PADRE IS NULL
        		ORDER BY C.NRO_CUENTA_CONTABLE";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	
	
	static public function get_hijos($nro_cuenta_contable){
		$sql = "SELECT C.*, ' ['|| pkg_cp_cuentas.mascara_aplicar(c.nro_cuenta_contable)||'] '||c.descripcion as descripcion2 
				FROM CP_CUENTAS C
				WHERE C.NRO_CUENTA_CONTABLE_PADRE = $nro_cuenta_contable
				ORDER BY C.NRO_CUENTA_CONTABLE;";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	
	
	static public function get_nivel ($nro_cuenta_contable){
		if (isset($nro_cuenta_contable)){
			$sql = "SELECT NIVEL FROM CP_CUENTAS WHERE NRO_CUENTA_CONTABLE = $nro_cuenta_contable;";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['nivel'];
		}else return null;
	}
	
	
	//---------------------------------------------------------------------------
	//---------------- FUNCIONES ALMACENADAS ------------------------------------
	//---------------------------------------------------------------------------
	
	static public function eliminar_cuenta ($nro_cuenta_contable){
		 try{
            $sql = "BEGIN :resultado := PKG_CP_CUENTAS.ELIMINAR_CUENTA(:nro_cuenta_contable); END;";        	
            $parametros = array ( array(  'nombre' => 'nro_cuenta_contable', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 20,
                                          'valor' => $nro_cuenta_contable),
                                  array(  'nombre' => 'resultado', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => ''),
                            );
            toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            if ($resultado[1]['valor'] == 'OK'){
                toba::db()->cerrar_transaccion();
            }else{
                toba::db()->abortar_transaccion();
                toba::notificacion()->error($resultado[1]['valor']);
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
	
	static public function get_descripcion ($nro_cuenta_contable){
		if (isset($nro_cuenta_contable)){
			$sql = "SELECT PKG_CP_CUENTAS.CARGAR_DESCRIPCION($nro_cuenta_contable) as resultado FROM DUAL;";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['resultado'];
		}else return null;
	}
	static public function es_hoja ($nro_cuenta_contable){
		if (isset($nro_cuenta_contable)){
			$sql = "select pkg_cp_cuentas.ES_HOJA($nro_cuenta_contable) as resultado from dual;";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['resultado'];
		}
	}	
	static public function imputable ($nro_cuenta_contable){
		if (isset($nro_cuenta_contable)){
			$sql = "select pkg_cp_cuentas.imputable($nro_cuenta_contable) as resultado from dual;";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['resultado'];
		}
	}
	static public function tiene_hijos ($nro_cuenta_contable){
		if (isset($nro_cuenta_contable)){
			$sql = "select pkg_cp_cuentas.tiene_hijos($nro_cuenta_contable) as resultado from dual;";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['resultado'];
		}
	}
	static public function activa ($nro_cuenta_contable){
		
		if (isset($nro_cuenta_contable) && $nro_cuenta_contable != null)
			$sql = "select pkg_cp_cuentas.activa($nro_cuenta_contable) as activa from dual;";
		else
			$sql = "select pkg_cp_cuentas.activa(NULL) as activa from dual;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['activa'];
		
	}
	static public function cant_niveles (){
		$sql = "select pkg_CP_CUENTAS.cant_niveles as resultado from dual;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['resultado'];
	}
	static public function valor_del_nivel ($nodo_padre, $nivel){
		if ($nodo_padre == null)
			$sql = "select PKG_CP_CUENTAS.VALOR_DEL_NIVEL(PKG_CP_CUENTAS.ULTIMO_DEL_NIVEL(null),$nivel)+1 as resultado from dual;";
		else
			$sql = "select PKG_CP_CUENTAS.VALOR_DEL_NIVEL(PKG_CP_CUENTAS.ULTIMO_DEL_NIVEL($nodo_padre),$nivel)+1 as resultado from dual;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['resultado'];
	}
	static public function cta_cte ($nro_cuenta_contable){
		if ($nro_cuenta_contable == null)
			$sql = "select PKG_CP_CUENTAS.cta_cte(null) as resultado from dual;";
		else
			$sql = "select PKG_CP_CUENTAS.cta_cte($nro_cuenta_contable) as resultado from dual;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['resultado'];
	}
	static public function cta_bco ($nro_cuenta_contable){
		if ($nro_cuenta_contable == null)
			$sql = "select PKG_CP_CUENTAS.cta_bco(null) as resultado from dual;";
		else 
			$sql = "select PKG_CP_CUENTAS.cta_bco($nro_cuenta_contable) as resultado from dual;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['resultado'];
	}
	
	static public function armar_codigo ($nivel, $nro_cuenta_contable, $nro_cuenta_contable_padre){
		if ($nro_cuenta_contable_padre == null)
			$sql = "SELECT PKG_CP_CUENTAS.ARMAR_CODIGO($nivel, $nro_cuenta_contable, NULL) AS CODIGO FROM DUAL;";
		else 	
			$sql = "SELECT PKG_CP_CUENTAS.ARMAR_CODIGO($nivel, $nro_cuenta_contable, $nro_cuenta_contable_padre) AS CODIGO FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['codigo'];
	}	
	
	static public function existe_cuenta($nro_cuenta_contable){
		$sql ="SELECT NVL (MIN (1), 0) as existe
			   FROM CP_CUENTAS c
			   WHERE c.nro_cuenta_contable = $nro_cuenta_contable;";
		$datos = toba::db()->consultar_fila($sql);
		if ($datos['existe'] == '1')
			return true;
		else return false;
	}
	
	static public function crear_cuenta ($nro_cuenta_contable, $descripcion, $nivel,
 										 $nro_cuenta_contable_padre, $cuenta_corriente, $cuenta_banco, $activa){
        try{
        	if ($nro_cuenta_contable_padre == null)
        		$nro_cuenta_contable_padre = "";
        	if ($cuenta_banco == null)
        		$cuenta_banco = "";
        	if ($cuenta_corriente == null)
        		$cuenta_corriente = "";
        	
        	
            $sql = "BEGIN :resultado := PKG_CP_CUENTAS.CREAR_CUENTA(:nro_cuenta_contable, :descripcion, :nivel, :nro_cuenta_contable_padre, :activa, :cuenta_corriente, :cuenta_banco); END;";        	
            		
            $parametros = array ( array(  'nombre' => 'nro_cuenta_contable', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 20,
                                          'valor' => $nro_cuenta_contable),
            
					            array(  'nombre' => 'descripcion', 
		                                          'tipo_dato' => PDO::PARAM_STR,
		                                          'longitud' => 1000,
		                                          'valor' => $descripcion),
					            
					            array(  'nombre' => 'nivel', 
		                                          'tipo_dato' => PDO::PARAM_STR,
		                                          'longitud' => 10,
		                                          'valor' => $nivel),
					            
					            array(  'nombre' => 'nro_cuenta_contable_padre', 
		                                          'tipo_dato' => PDO::PARAM_STR,
		                                          'longitud' => 20,
		                                          'valor' => $nro_cuenta_contable_padre),
					            
					            array(  'nombre' => 'cuenta_corriente', 
		                                          'tipo_dato' => PDO::PARAM_STR,
		                                          'longitud' => 20,
		                                          'valor' => $cuenta_corriente),
					            
					             array(  'nombre' => 'cuenta_banco', 
		                                          'tipo_dato' => PDO::PARAM_STR,
		                                          'longitud' => 20,
		                                          'valor' => $cuenta_banco),
					             
					              array( 'nombre' => 'activa', 
		                                          'tipo_dato' => PDO::PARAM_STR,
		                                          'longitud' => 10,
		                                          'valor' => $activa),
		            
                                  array(  'nombre' => 'resultado', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => ''),
                            );
            toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            if ($resultado[7]['valor'] == 'OK'){
                toba::db()->cerrar_transaccion();
            }else{
                toba::db()->abortar_transaccion();
                toba::notificacion()->error($resultado[7]['valor']);
            }
            return $resultado[7]['valor'];
           
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

	static public function actualizar_cuenta ($nro_cuenta_contable, $descripcion, $activa,
	 										  $cuenta_corriente, $cuenta_banco){
        try{
        	
        	if ($cuenta_banco == null)
        		$cuenta_banco = "";
        	if ($cuenta_corriente == null)
        		$cuenta_corriente = "";
        	if ($activa == null)
        		$activa = "";
        	
        	$sql = "BEGIN :resultado := PKG_CP_CUENTAS.ACTUALIZAR_CUENTA(:nro_cuenta_contable, :descripcion, :activa, :cuenta_corriente, :cuenta_banco); PKG_CP_CUENTAS.CAMBIAR_ESTADO_ACTIVO_HIJOS(:nro_cuenta_contable); PKG_CP_CUENTAS.CAMBIAR_ESTADO_C_BCO_HIJOS(:nro_cuenta_contable,:cuenta_banco);PKG_CP_CUENTAS.CAMBIAR_ESTADO_C_CTE_HIJOS(:nro_cuenta_contable,:cuenta_corriente);END;"; 
        		$parametros = array ( array(  'nombre' => 'nro_cuenta_contable', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 20,
                                          'valor' => $nro_cuenta_contable),
            
					            array(  'nombre' => 'descripcion', 
		                                          'tipo_dato' => PDO::PARAM_STR,
		                                          'longitud' => 1000,
		                                          'valor' => $descripcion),
					            
					            array(  'nombre' => 'cuenta_corriente', 
		                                          'tipo_dato' => PDO::PARAM_STR,
		                                          'longitud' => 20,
		                                          'valor' => $cuenta_corriente),
					            
					             array(  'nombre' => 'cuenta_banco', 
		                                          'tipo_dato' => PDO::PARAM_STR,
		                                          'longitud' => 20,
		                                          'valor' => $cuenta_banco),
					             
					              array( 'nombre' => 'activa', 
		                                          'tipo_dato' => PDO::PARAM_STR,
		                                          'longitud' => 10,
		                                          'valor' => $activa),
		            
                                  array(  'nombre' => 'resultado', 
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => '')
                            );
            toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            if ($resultado[5]['valor'] == 'OK'){
           		toba::db()->cerrar_transaccion();
            }else{
           		toba::db()->abortar_transaccion();
           	 	toba::notificacion()->info($resultado[5]['valor']);
            }
            return $resultado[5]['valor']; 
        	
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
}

?>