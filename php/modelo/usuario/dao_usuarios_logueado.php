<?php

class dao_usuarios_logueado {

    static public function get_cod_unidad_administracion_usuario($forzar_usuario = null, $fuente=null) 
	{
		if (!isset($forzar_usuario)) {
			$usuario = toba::usuario()->get_id();
		} else {
			$usuario = $forzar_usuario;
		}
		$sql_sel = "SELECT USU.COD_UNIDAD_ADMINISTRACION
					FROM KR_USUARIOS USU, 
					KR_UNIDADES_ADMINISTRACION ADM
					WHERE USU.COD_UNIDAD_ADMINISTRACION = ADM.COD_UNIDAD_ADMINISTRACION 
					AND USU.USUARIO = " . quote(strtoupper($usuario)) . ";";
		$datos = toba::db($fuente)->consultar_fila($sql_sel);
		if (isset($datos) && !empty($datos) && isset($datos['cod_unidad_administracion'])) {
			return $datos['cod_unidad_administracion'];
		} else {
			return null;
		}
	}
	
	static public function get_cod_unidad_ejecutora_usuario($forzar_usuario = null, $fuente=null) 
	{
		if (!isset($forzar_usuario)) {
			$usuario = toba::usuario()->get_id();
		} else {
			$usuario = $forzar_usuario;
		}
		$sql_sel = "SELECT USU.COD_UNIDAD_EJECUTORA
					FROM KR_USUARIOS USU, 
					KR_UNIDADES_EJECUTORAS UE
					WHERE USU.COD_UNIDAD_EJECUTORA = UE.COD_UNIDAD_EJECUTORA
					AND USU.USUARIO = " . quote(strtoupper($usuario)) . ";";
		$datos = toba::db($fuente)->consultar_fila($sql_sel);
		if (isset($datos) && !empty($datos) && isset($datos['cod_unidad_ejecutora'])) {
			return $datos['cod_unidad_ejecutora'];
		} else {
			return null;
		}
	}
	
	static public function get_cod_sector_usuario($forzar_usuario = null, $fuente=null) 
	{
		if (!isset($forzar_usuario)) {
			$usuario = toba::usuario()->get_id();
		} else {
			$usuario = $forzar_usuario;
		}
		$sql_sel = "SELECT USU.COD_SECTOR
					FROM CO_USUARIOS USU, 
					CO_SECTORES CS
					WHERE USU.COD_SECTOR = CS.COD_SECTOR
					AND USU.USUARIO = " . quote(strtoupper($usuario)) . ";";
		$datos = toba::db($fuente)->consultar_fila($sql_sel);
		if (isset($datos) && !empty($datos) && isset($datos['cod_sector'])) {
			return $datos['cod_sector'];
		} else {
			return null;
		}
	}
	
	static public function get_datos_sector_usuario($forzar_usuario = null) 
	{
		if (!isset($forzar_usuario)) {
			$usuario = toba::usuario()->get_id();
		} else {
			$usuario = $forzar_usuario;
		}
		$sql_sel = "SELECT	cod_sector, 
							seq_sector, 
							sec_descripcion,
							cod_ambito, 
							seq_ambito, 
							amb_descripcion
					FROM V_CO_USUARIOS_SECTORES
					WHERE  usuario = " . quote(strtoupper($usuario)) . ";";
		$datos = toba::db()->consultar_fila($sql_sel);
		if (isset($datos) && !empty($datos)) {
			return $datos;
		} else {
			throw new toba_error('No se puede rescatar el sector del usuario '. strtoupper($usuario));
		}
	}
	
	static public function ingreso_permitido($cod_sector, $cod_ambito, $tipo_comprobante, $tipo_compra, $presupuestario, $interna) {
		if (isset($cod_sector) && isset($cod_ambito) && isset($tipo_comprobante) && isset($tipo_compra) && isset($presupuestario) && isset($interna)) {
			$usuario = toba::usuario()->get_id();
			$sql_sel = "SELECT	pkg_usuarios.ingreso_permitido(" . quote(strtoupper($usuario)) . ", " . quote($cod_sector) . ", " . quote($cod_ambito) . ", " . quote($tipo_comprobante) . ", " . quote($tipo_compra) . ", " . quote($presupuestario) . ", " . quote($interna) . ") resultado
						FROM DUAL;";
			$datos = toba::db()->consultar_fila($sql_sel);
			if (isset($datos) && !empty($datos) && isset($datos['resultado'])) {
				return $datos['resultado'];
			} else {
				return 'N';
			}
		} else {
			return 'N';
		}
	}
	
	static public function esta_en_bandeja($cod_sector, $cod_ambito, $tipo_comprobante, $tipo_compra, $presupuestario, $interna, $estado) {
		if (isset($cod_sector) && isset($cod_ambito) && isset($tipo_comprobante) && isset($interna) && isset($estado)) {
			if (isset($tipo_compra)) {
				$tipo_compra = quote($tipo_compra);
			} else {
				$tipo_compra = 'NULL';
			}
			if (isset($presupuestario)) {
				$presupuestario = quote($presupuestario);
			} else {
				$presupuestario = 'NULL';
			}
			$usuario = toba::usuario()->get_id();
			$sql_sel = "SELECT	pkg_usuarios.esta_en_bandeja(" . quote(strtoupper($usuario)) . ", " . quote($cod_sector) . ", " . quote($cod_ambito) . ", " . quote($tipo_comprobante) . ", $tipo_compra, $presupuestario, " . quote($interna) . ", " . quote($estado) . ") resultado
						FROM DUAL;";
			$datos = toba::db()->consultar_fila($sql_sel);
			if (isset($datos) && !empty($datos) && isset($datos['resultado'])) {
				return $datos['resultado'];
			} else {
				return 'N';
			}
		} else {
			return 'N';
		}
	}
	
	static public function armar_datos_temporales_compras($cod_sector, $cod_ambito, $tipo_comprobante, $tipo_compra, $presupuestario, $interna) {
		if (isset($cod_sector) && isset($cod_ambito) && isset($tipo_comprobante) && isset($interna)) {
			if (!isset($tipo_compra)) {
				$tipo_compra = '';
			}
			if (!isset($presupuestario)) {
				$presupuestario = '';
			}
				
			$sql = "BEGIN :resultado := PKG_USUARIOS.arma_temporal(:cod_sector,:cod_ambito,:tipo_comprobante,:tipo_compra,:presupuestario,:interna); END;";
			
			$parametros = array(array(	'nombre' => 'cod_sector',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $cod_sector),
								array(	'nombre' => 'cod_ambito',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $cod_ambito),
								array(	'nombre' => 'tipo_comprobante',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 32,
										'valor' => $tipo_comprobante),
								array(	'nombre' => 'tipo_compra',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 32,
										'valor' => $tipo_compra),
								array(	'nombre' => 'presupuestario',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 32,
										'valor' => $presupuestario),
								array(	'nombre' => 'interna',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 32,
										'valor' => $interna),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);

			ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros, '', 'Error al armar el arreglo temporal de compras.', true);
		}
	}
	
	static public function get_sector_usuario($forzar_usuario = null) 
	{
		if (!isset($forzar_usuario)) {
			$usuario = toba::usuario()->get_id();
		} else {
			$usuario = $forzar_usuario;
		}
		$sql_sel = "SELECT	pkg_usuarios.sector_usuario(" . quote(strtoupper($usuario)) . ") resultado
					FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql_sel);
		if (isset($datos) && !empty($datos) && isset($datos['resultado'])) {
			return $datos['resultado'];
		} else {
			return null;
		}
	}
	
	static public function get_pertenece_a_unidad($cod_ambito, $forzar_usuario = null) 
	{
		if (isset($cod_ambito)) {
			if (!isset($forzar_usuario)) {
				$usuario = toba::usuario()->get_id();
			} else {
				$usuario = $forzar_usuario;
			}
			$sql_sel = "SELECT	PKG_USUARIOS.pertenece_a_unidad (" . quote(strtoupper($usuario)) . "," . quote($cod_ambito) . ", PKG_GENERAL.valor_parametro('COD_UNIDAD_COMPRA_EXTERNA')) resultado
						FROM DUAL;";
			$datos = toba::db()->consultar_fila($sql_sel);
			if (isset($datos) && !empty($datos) && isset($datos['resultado'])) {
				return $datos['resultado'];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}
	
	//---- Obtenet entidad del usuario logueado ------------------------------
        public static function get_entidad_rrhh() {

            $user = strtoupper(toba::usuario()->get_id());

            if (!empty($user)){
                $sql = "
                        SELECT 
                            id_entidad id_entidad
                        FROM 
                            usuarios
                        WHERE 
                            usuario = '$user' AND activo = 'S'
                ;";

                $datos = toba::db()->consultar_fila($sql);
                return $datos['id_entidad'];
            }
            return null;
        }         

        public static function get_entidad_rrhh_old() {

            $user = strtoupper(toba::usuario()->get_id());
            $entidad = '';
            $organizacion = '';
            $organizaciones_dependientes = '';

            $sql = "BEGIN IF (pkg_usuarios.organizacion(:user, :entidad, :organizacion, :organizaciones_dependientes)) THEN :resultado := 'S'; ELSE :resultado := 'N'; END IF; END;";
            $parametros = array(
                array('nombre' => 'user',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 20,
                    'valor' => $user),
                array('nombre' => 'entidad',
                    'tipo_dato' => PDO::PARAM_INT,
                    'longitud' => 20,
                    'valor' => $entidad),
                array('nombre' => 'organizacion',
                    'tipo_dato' => PDO::PARAM_INT,
                    'longitud' => 3,
                    'valor' => $organizacion),
                array('nombre' => 'organizaciones_dependientes',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $organizaciones_dependientes),
                array('nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 10,
                    'valor' => ''),
            );

            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

            if ($resultado[4]['valor'] == 'S') {
                return $resultado[1]['valor'];
            }
        }
    
        //---- Obtener la organización del usuario logueado ------------------------------
        public static function get_organizacion_usuario_rrhh($fuente=null) {

            $user = strtoupper(toba::usuario()->get_id());

            if (!empty($user)){
                $sql = "
                        SELECT 
                            id_organizacion id_organizacion
                        FROM 
                            usuarios
                        WHERE 
                            usuario = '$user' AND activo = 'S'        
                ;";

                $datos = toba::db($fuente)->consultar_fila($sql);
                return $datos['id_organizacion'];
            }
            return null;
        }

        ////////// VENTAS AGUA /////////////////////////////////////////////////////
        //---- Obtener el usuario logueado-----------------------------------------
        public static function get_usuario_ventas_agua($fuente=null) {

            $user = strtoupper(toba::usuario()->get_id());

            if (!empty($user)){
                $sql = "
                        SELECT *
                        FROM VTA_USUARIOS
                        WHERE usuario = '$user'
                ;";

                $datos = toba::db($fuente)->consultar_fila($sql);
                return $datos;
            }
            return [];
        }    
}
?>
