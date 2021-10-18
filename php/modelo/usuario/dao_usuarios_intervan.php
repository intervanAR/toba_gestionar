<?php

class dao_usuarios_intervan
{
	const TABLESPACE = 'USERS';
	const TEMPTS = 'TEMP';

	static function existe_usuario_administracion($nombre_usuario, $fuente=null)
	{
		$sql = "SELECT * 
				FROM KR_USUARIOS 
				WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")";
		$datos_usuario = toba::db($fuente)->consultar_fila($sql);
		if (!empty($datos_usuario)) {
		    return true;
		} else {
		    return false;
		}
	}
	
	static function crear_usuario_administracion($nombre_usuario, $nombres, $unidades_administracion_usuario, $unidades_ejecutoras_usuario=array(), $fuente=null)
	{
		if (self::existe_usuario_administracion($nombre_usuario, $fuente)) {
			throw new toba_error_usuario('Ya existe un usuario de administracion registrado con ese ID, intente otro por favor');
		} else {
			try {
				toba::db($fuente)->abrir_transaccion();
				
				$ua = isset($unidades_administracion_usuario[0])?quote($unidades_administracion_usuario[0]):'NULL';
				$ue = isset($unidades_ejecutoras_usuario[0])?quote($unidades_ejecutoras_usuario[0]):'NULL';
				
				//DOY DE ALTA EL USUARIO EN ADMINISTRACION
				$sql = "INSERT INTO kr_usuarios(usuario, nombre, cod_unidad_administracion, cod_unidad_ejecutora) 
						VALUES (UPPER('{$nombre_usuario}'), '{$nombres}', $ua, $ue)";

				toba::logger()->debug("Alta usuario administraccion: $sql");
				toba::db($fuente)->ejecutar($sql);

				dao_usuarios_intervan::modificar_unidades_administracion($nombre_usuario, $unidades_administracion_usuario, false, $fuente);
				dao_usuarios_intervan::modificar_unidades_ejecutoras($nombre_usuario, $unidades_ejecutoras_usuario, false, $fuente);
				
				toba::db($fuente)->cerrar_transaccion();
			} catch (toba_error $e) {
				toba::db($fuente)->abortar_transaccion();
				throw $e;
			}
		}
	}
	
	static function modificar_unidades_administracion($nombre_usuario, $unidades_administracion_usuario=array(), $con_transaccion = true, $fuente=null)
	{
		try {
			if ($con_transaccion) {
				toba::db($fuente)->abrir_transaccion();
			}

			$cod_unidad_administracion_default = dao_usuarios_logueado::get_cod_unidad_administracion_usuario($nombre_usuario, $fuente);

			if (!in_array($cod_unidad_administracion_default, $unidades_administracion_usuario)) {
				$ua = isset($unidades_administracion_usuario[0])?quote($unidades_administracion_usuario[0]):'NULL';
				$sql = "UPDATE kr_usuarios
						SET cod_unidad_administracion = $ua
						WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")";
				toba::db($fuente)->ejecutar($sql);
			}

			$arr_unidades_administracion = ctr_funciones_basicas::matriz_to_array(dao_unidades_administracion::get_unidades_administracion(array(), $fuente), 'cod_unidad_administracion');

			//ELIMINO las unidades de administracion del usuario
			$sql = "DELETE 
					FROM kr_usuarios_ua
					WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")";

			toba::db($fuente)->ejecutar($sql);

			// Agrego las unidades de administracion al usuario
			foreach ($unidades_administracion_usuario as $cod_unidad_administracion) {
				if (!in_array($cod_unidad_administracion, $arr_unidades_administracion)) {
					throw new toba_error("No existe la unidad de administracion $cod_unidad_administracion en la base de Negocios. Contactese con el administrador.");
				}
				$sql = "INSERT INTO kr_usuarios_ua(usuario, cod_unidad_administracion) 
						VALUES(UPPER('{$nombre_usuario}'), '$cod_unidad_administracion')";

				toba::db($fuente)->ejecutar($sql);
			}
			if ($con_transaccion) {
				toba::db($fuente)->cerrar_transaccion();
			}
		} catch (toba_error $e) {
			if ($con_transaccion) {
				toba::db($fuente)->abortar_transaccion();
			}
			throw $e;
		}
	}
	
	static function modificar_unidades_ejecutoras($nombre_usuario, $unidades_ejecutoras_usuario=array(), $con_transaccion = true, $fuente=null)
	{
		try {
			if ($con_transaccion) {
				toba::db($fuente)->abrir_transaccion();
			}

			$cod_unidad_ejecutora_default = dao_usuarios_logueado::get_cod_unidad_ejecutora_usuario($nombre_usuario, $fuente);

			if (!in_array($cod_unidad_ejecutora_default, $unidades_ejecutoras_usuario)) {
				$ue = isset($unidades_ejecutoras_usuario[0])?quote($unidades_ejecutoras_usuario[0]):'NULL';
				$sql = "UPDATE kr_usuarios
						SET cod_unidad_ejecutora = $ue
						WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")";
				toba::db($fuente)->ejecutar($sql);
			}

			$arr_unidades_ejecutoras = ctr_funciones_basicas::matriz_to_array(dao_unidades_ejecutoras::get_unidades_ejecutoras(array(), $fuente), 'cod_unidad_ejecutora');

			//ELIMINO las unidades ejecutoras del usuario
			$sql = "DELETE 
					FROM kr_usuarios_ue
					WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")";

			toba::db($fuente)->ejecutar($sql);

			// Agrego las unidades ejecutoras al usuario
			foreach ($unidades_ejecutoras_usuario as $cod_unidad_ejecutora) {
				if (!in_array($cod_unidad_ejecutora, $arr_unidades_ejecutoras)) {
					throw new toba_error("No existe la unidad ejecutora $cod_unidad_ejecutora en la base de Negocios. Contactese con el administrador.");
				}
				$sql = "INSERT INTO kr_usuarios_ue(usuario, cod_unidad_ejecutora) 
						VALUES(UPPER('{$nombre_usuario}'), '$cod_unidad_ejecutora')";

				toba::db($fuente)->ejecutar($sql);
			}
			if ($con_transaccion) {
				toba::db($fuente)->cerrar_transaccion();
			}
		} catch (toba_error $e) {
			if ($con_transaccion) {
				toba::db($fuente)->abortar_transaccion();
			}
			throw $e;
		}
	}
	
	static function existe_usuario_compras($nombre_usuario, $fuente=null)
	{
		$sql = "SELECT * 
				FROM CO_USUARIOS 
				WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")";
	    $datos_usuario = toba::db($fuente)->consultar_fila($sql);
		if (!empty($datos_usuario)) {
		    return true;
		} else {
		    return false;
		}
	}
	
	static function crear_usuario_compras($nombre_usuario, $nombres, $ambitos_compras_usuario, $sectores_compras_usuario, $fuente=null)
	{
		if (self::existe_usuario_compras($nombre_usuario, $fuente)) {
			throw new toba_error_usuario('Ya existe un usuario de compras registrado con ese ID, intente otro por favor');
		} else {
			try {
				toba::db($fuente)->abrir_transaccion();
				
				$sc = isset($sectores_compras_usuario[0])?quote($sectores_compras_usuario[0]):'NULL';
				
				//DOY DE ALTA EL USUARIO EN COMPRAS
				$sql = "INSERT INTO co_usuarios(usuario, nombre, cod_sector) 
						VALUES (UPPER('{$nombre_usuario}'), '{$nombres}', $sc)";

				toba::logger()->debug("Alta usuario compras: $sql");
				toba::db($fuente)->ejecutar($sql);

				dao_usuarios_intervan::modificar_ambitos_sectores_compras($nombre_usuario, $ambitos_compras_usuario, $sectores_compras_usuario , false, $fuente);
				
				toba::db($fuente)->cerrar_transaccion();
			} catch (toba_error $e) {
				toba::db($fuente)->abortar_transaccion();
				throw $e;
			}
		}
	}
	
	static function modificar_ambitos_sectores_compras($nombre_usuario, $ambitos_compras_usuario=array(), $sectores_compras_usuario=array(), $con_transaccion = true, $fuente=null)
	{
		try {
			if ($con_transaccion) {
				toba::db($fuente)->abrir_transaccion();
			}

			$cod_sector_compra_default = dao_usuarios_logueado::get_cod_sector_usuario($nombre_usuario, $fuente);

			if (!in_array($cod_sector_compra_default, $sectores_compras_usuario)) {
				$sc = isset($sectores_compras_usuario[0])?quote($sectores_compras_usuario[0]):'NULL';
				$sql = "UPDATE co_usuarios
						SET cod_sector = $sc
						WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")";
				toba::db($fuente)->ejecutar($sql);
			}

			$arr_ambitos_compras = ctr_funciones_basicas::matriz_to_array(dao_ambitos_sectores::get_ambitos_compra(array(), $fuente), 'cod_ambito');
			$arr_sectores_compras = ctr_funciones_basicas::matriz_to_array(dao_ambitos_sectores::get_sectores_compra(array(), $fuente), 'cod_sector');

			//ELIMINO los sectores de compras del usuario
			$sql = "DELETE 
					FROM co_usuarios_sectores
					WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")";

			toba::db($fuente)->ejecutar($sql);
			
			//ELIMINO los ambitos de compra del usuario
			$sql = "DELETE 
					FROM co_usuarios_ambitos
					WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")";

			toba::db($fuente)->ejecutar($sql);

			// Agrego los ambitos de compra al usuario
			foreach ($ambitos_compras_usuario as $cod_ambito) {
				if (!in_array($cod_ambito, $arr_ambitos_compras)) {
					throw new toba_error("No existe el ambito de compra $cod_ambito en la base de Negocios. Contactese con el administrador.");
				}
				$sql = "INSERT INTO co_usuarios_ambitos(usuario, cod_ambito) 
						VALUES(UPPER('{$nombre_usuario}'), '$cod_ambito')";

				toba::db($fuente)->ejecutar($sql);
			}

			// Agrego los sectores de compras al usuario
			foreach ($sectores_compras_usuario as $cod_sector) {
				if (!in_array($cod_sector, $arr_sectores_compras)) {
					throw new toba_error("No existe el sector de compra $cod_sector en la base de Negocios. Contactese con el administrador.");
				}
				$sql = "INSERT INTO co_usuarios_sectores(usuario, cod_sector, cod_ambito) 
						VALUES(UPPER('{$nombre_usuario}'), '$cod_sector', (SELECT cod_ambito FROM co_sectores cs WHERE cs.cod_sector = '$cod_sector' AND ROWNUM <= 1))";

				toba::db($fuente)->ejecutar($sql);
			}
			if ($con_transaccion) {
				toba::db($fuente)->cerrar_transaccion();
			}
		} catch (toba_error $e) {
			if ($con_transaccion) {
				toba::db($fuente)->abortar_transaccion();
			}
			throw $e;
		}
	}
	
	static function existe_usuario_rrhh($nombre_usuario, $fuente=null)
	{
		$sql = "SELECT * 
				FROM USUARIOS 
				WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")";
	    $datos_usuario = toba::db($fuente)->consultar_fila($sql);
		if (!empty($datos_usuario)) {
		    return true;
		} else {
		    return false;
		}
	}
	
	static function modificar_ent_org_rrhh($nombre_usuario, $ent_org_usuario=array(), $con_transaccion = true, $fuente=null)
	{
		try {
			if ($con_transaccion) {
				toba::db($fuente)->abrir_transaccion();
			}

			$arr_entidades_rrhh = ctr_funciones_basicas::matriz_to_array(dao_organizaciones_rrhh::get_lov_entidad_x_nombre(null, $fuente), 'id_entidad');
			$arr_organizaciones_rrhh = ctr_funciones_basicas::matriz_to_array(dao_organizaciones_rrhh::get_lov_organizacion_x_nombre(null, array(), $fuente), 'id_organizacion');
			
			//ELIMINO las entidades y organizaciones de RRHH del usuario
			$sql = "DELETE 
					FROM USUARIOS_ORGANIZACIONES
					WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")";

			toba::db($fuente)->ejecutar($sql);
			
			$id_entidad = null;
			$id_organizacion = null;
			$arr_entidades_usuario = array();
			$arr_organizaciones_usuario = array();

			// Agrego las entidades y organizaciones de RRHH al usuario
			foreach ($ent_org_usuario as $ent_org) {
				$cod_eng_org_rrhh = self::get_cod_eng_org_rrhh($ent_org, $fuente);
				$id_entidad = $cod_eng_org_rrhh['id_entidad'];
				$id_organizacion = $cod_eng_org_rrhh['id_organizacion'];
				if (!in_array($id_entidad, $arr_entidades_rrhh)) {
					throw new toba_error("No existe la entidad de RRHH $id_entidad en la base de Negocios. Contactese con el administrador.");
				}
				if (!in_array($id_organizacion, $arr_organizaciones_rrhh)) {
					throw new toba_error("No existe la organizacion de RRHH $id_organizacion en la base de Negocios. Contactese con el administrador.");
				}
				$arr_entidades_usuario[] = $id_entidad;
				$arr_organizaciones_usuario[] = $id_organizacion;
				$sql = "INSERT INTO USUARIOS_ORGANIZACIONES(usuario, id_entidad, id_organizacion) 
						VALUES(UPPER('{$nombre_usuario}'), '$id_entidad', '$id_organizacion')";
				toba::db($fuente)->ejecutar($sql);
			}
			
			// modifico la entidad y organizacion del usuario si la actual fue quitada del usuario
			if (isset($id_entidad) && isset($id_organizacion)) {
				$sql = "UPDATE USUARIOS
						SET id_entidad = " . quote($id_entidad) . ", 
							id_organizacion = " . quote($id_organizacion) . " 
						WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")
						AND (id_entidad NOT IN (" . implode(', ', $arr_entidades_usuario) . ")
							OR id_organizacion NOT IN (" . implode(', ', $arr_organizaciones_usuario) . "))";
				toba::db($fuente)->ejecutar($sql);
			}
			
			if ($con_transaccion) {
				toba::db($fuente)->cerrar_transaccion();
			}
		} catch (toba_error $e) {
			if ($con_transaccion) {
				toba::db($fuente)->abortar_transaccion();
			}
			throw $e;
		}
	}
	
	static function crear_usuario_rrhh($nombre_usuario, $nombres, $ent_org_usuario=array(), $fuente=null)
	{
		if (self::existe_usuario_rrhh($nombre_usuario, $fuente)) {
			throw new toba_error_usuario('Ya existe un usuario de RRHH registrado con ese ID, intente otro por favor');
		} elseif (isset ($ent_org_usuario) && isset ($ent_org_usuario[0]) && !empty ($ent_org_usuario[0])) {
			try {
				toba::db($fuente)->abrir_transaccion();
				
				$cod_eng_org_rrhh = self::get_cod_eng_org_rrhh($ent_org_usuario[0], $fuente);
				$id_entidad = $cod_eng_org_rrhh['id_entidad'];
				$id_organizacion = $cod_eng_org_rrhh['id_organizacion'];
				
				//DOY DE ALTA EL USUARIO EN RRHH
				$sql = "INSERT INTO USUARIOS(usuario, nombre, id_entidad, id_organizacion) 
						VALUES (UPPER('{$nombre_usuario}'), '{$nombres}', '{$id_entidad}', '{$id_organizacion}')";

				toba::logger()->debug("Alta usuario RRHH: $sql");
				toba::db($fuente)->ejecutar($sql);

				dao_usuarios_intervan::modificar_ent_org_rrhh($nombre_usuario, $ent_org_usuario , false, $fuente);
				
				toba::db($fuente)->cerrar_transaccion();
			} catch (toba_error $e) {
				toba::db($fuente)->abortar_transaccion();
				throw $e;
			}
		}
	}
	
	static function existe_usuario_ventas_agua($nombre_usuario, $fuente=null)
	{
		$sql = "SELECT * 
				FROM VTA_USUARIOS 
				WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")";
	    $datos_usuario = toba::db($fuente)->consultar_fila($sql);
		if (!empty($datos_usuario)) {
		    return true;
		} else {
		    return false;
		}
	}
	
	static function modificar_emp_suc_ventas_agua($nombre_usuario, $emp_suc_usuario=array(), $con_transaccion = true, $fuente=null)
	{
		try {
			if ($con_transaccion) {
				toba::db($fuente)->abrir_transaccion();
			}

			$arr_empresas_ventas_agua = ctr_funciones_basicas::matriz_to_array(dao_usuarios_intervan::get_empresas_ventas_agua($fuente), 'id_empresa');
			$arr_sucursales_ventas_agua = ctr_funciones_basicas::matriz_to_array(dao_usuarios_intervan::get_sucursales_ventas_agua($fuente), 'nro_sucursal');

			//Guardar la sucursal actual del usuario
			$sql= "SELECT id_empresa, id_sucursal
					FROM VTA_USUARIOS
					WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")";

			$suc_base= toba::db()->consultar_fila($sql);

			if ($suc_base['id_sucursal'] === '' || $suc_base['id_sucursal'] === null)
				throw new toba_error("El usuario no tiene seteado una sucursal base");
			
			//ELIMINO las empresas y sucursales de ventas_agua del usuario
			$sql = "DELETE 
					FROM VTA_USUARIOS_SUCURSALES
					WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")";

			toba::db($fuente)->ejecutar($sql);
			
			$id_empresa = null;
			$id_sucursal = null;
			$arr_empresas_usuario = array();
			$arr_sucursales_usuario = array();

			// Agrego las empresas y sucursales de ventas_agua al usuario
			foreach ($emp_suc_usuario as $emp_suc) {
				$cod_emp_suc_ventas_agua = self::get_cod_emp_suc_ventas_agua($emp_suc, $fuente);
				$id_empresa = $cod_emp_suc_ventas_agua['id_empresa'];
				$id_sucursal = $cod_emp_suc_ventas_agua['id_sucursal'];
				if (!in_array($id_empresa, $arr_empresas_ventas_agua)) {
					throw new toba_error("No existe la empresa de Ventas Agua $id_empresa en la base de Negocios. Contactese con el administrador.");
				}
				if (!in_array($id_sucursal, $arr_sucursales_ventas_agua)) {
					throw new toba_error("No existe la sucursal de Ventas Agua $id_sucursal en la base de Negocios. Contactese con el administrador.");
				}
				$arr_empresas_usuario[] = $id_empresa;
				$arr_sucursales_usuario[] = $id_sucursal;
				$sql = "INSERT INTO VTA_USUARIOS_SUCURSALES(usuario, id_empresa, id_sucursal) 
						VALUES(UPPER('{$nombre_usuario}'), '$id_empresa', '$id_sucursal')";
				toba::db($fuente)->ejecutar($sql);
			}

			//Seteo la sucursal actual como base
			$sql = "UPDATE VTA_USUARIOS_SUCURSALES
					SET sucursal_base= 'S'
					WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")
					AND id_empresa= ".$suc_base['id_empresa']."
					AND id_sucursal= ".$suc_base['id_sucursal'];

			toba::db($fuente)->ejecutar($sql);
			
			// modifico la empresa y sucursal del usuario si la actual fue quitada del usuario
			if (isset($id_empresa) && isset($id_sucursal)) {
				$sql = "UPDATE VTA_USUARIOS
						SET id_empresa = " . quote($id_empresa) . ", 
							id_sucursal = " . quote($id_sucursal) . " 
						WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")
						AND (id_empresa NOT IN (" . implode(', ', $arr_empresas_usuario) . ")
							OR id_sucursal NOT IN (" . implode(', ', $arr_sucursales_usuario) . "))";
				toba::db($fuente)->ejecutar($sql);
			}
			
			if ($con_transaccion) {
				toba::db($fuente)->cerrar_transaccion();
			}
		} catch (toba_error $e) {
			if ($con_transaccion) {
				toba::db($fuente)->abortar_transaccion();
			}
			throw $e;
		}
	}
	
	static function crear_usuario_ventas_agua($nombre_usuario, $nombres, $emp_suc_usuario=array(), $fuente=null)
	{
		if (self::existe_usuario_ventas_agua($nombre_usuario, $fuente)) {
			throw new toba_error_usuario('Ya existe un usuario de Ventas Agua registrado con ese ID, intente otro por favor');
		} elseif (isset ($emp_suc_usuario) && isset ($emp_suc_usuario[0]) && !empty ($emp_suc_usuario[0])) {
			try {
				toba::db($fuente)->abrir_transaccion();
				
				$cod_emp_suc_ventas_agua = self::get_cod_emp_suc_ventas_agua($emp_suc_usuario[0], $fuente);
				$id_empresa = $cod_emp_suc_ventas_agua['id_empresa'];
				$id_sucursal = $cod_emp_suc_ventas_agua['id_sucursal'];
				
				//DOY DE ALTA EL USUARIO EN VENTAS AGUA
				$sql = "INSERT INTO VTA_USUARIOS(usuario, nombre, id_empresa, id_sucursal, min_importe_cuota, max_cant_cuotas_conv) 
						VALUES (UPPER('{$nombre_usuario}'), '{$nombres}', '{$id_empresa}', '{$id_sucursal}', 0, 0)";

				toba::logger()->debug("Alta usuario VENTAS AGUA: $sql");
				toba::db($fuente)->ejecutar($sql);

				dao_usuarios_intervan::modificar_emp_suc_ventas_agua($nombre_usuario, $emp_suc_usuario , false, $fuente);
				
				toba::db($fuente)->cerrar_transaccion();
			} catch (toba_error $e) {
				toba::db($fuente)->abortar_transaccion();
				throw $e;
			}
		}
	}
	
	static function existe_usuario_sociales($nombre_usuario, $fuente=null)
	{
		$sql = "SELECT * 
				FROM AS_USUARIOS 
				WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")";
	    $datos_usuario = toba::db($fuente)->consultar_fila($sql);
		if (!empty($datos_usuario)) {
		    return true;
		} else {
		    return false;
		}
	}
	
	static function modificar_sectores_sociales($nombre_usuario, $sectores_usuario=array(), $con_transaccion = true, $fuente=null)
	{
		if (!empty($sectores_usuario)) {
			try {
				if ($con_transaccion) {
					toba::db($fuente)->abrir_transaccion();
				}

				$arr_sectores_sociales = ctr_funciones_basicas::matriz_to_array(dao_sectores_as::get_lov_sectores_x_nombre(null, array(), $fuente), 'cod_sector');		
				
				$datos_usuario = dao_usuarios_as::get_datos_usuario($nombre_usuario, $fuente);
				$cod_sector_act = $datos_usuario['cod_sector'];
				$sectores_usuario_act = ctr_funciones_basicas::matriz_to_array(dao_usuarios_as::get_sectores_usuarios($nombre_usuario, $fuente), 'cod_sector');
				
				// Verifico que los sectores existan
				foreach ($sectores_usuario as $cod_sector) {
					if (!in_array($cod_sector, $arr_sectores_sociales)) {
						throw new toba_error("No existe el sector de Sociales $cod_sector en la base de Negocios. Contactese con el administrador.");
					}
					if (!in_array($cod_sector, $sectores_usuario_act)) {
						$sql = "INSERT INTO AS_USUARIOS_SECTORES(usuario, cod_sector) 
							VALUES(LOWER('{$nombre_usuario}'), '$cod_sector')";
						toba::db($fuente)->ejecutar($sql);
					}	
				}
				
				// modifico el sector del usuario si el actual fue quitado del usuario
				if (!in_array($cod_sector_act, $sectores_usuario)) {
					$cod_sector = $sectores_usuario[0];
					$sql = "UPDATE AS_USUARIOS
							SET cod_sector = " . quote($cod_sector) . "
							WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")";
					toba::db($fuente)->ejecutar($sql);
				}
				
				//ELIMINO los sectores de Sociales del usuario
				$sql = "DELETE 
						FROM AS_USUARIOS_SECTORES
						WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")
						AND cod_sector NOT IN (" . implode(', ', $sectores_usuario) . ")";

				toba::db($fuente)->ejecutar($sql);

				if ($con_transaccion) {
					toba::db($fuente)->cerrar_transaccion();
				}
			} catch (toba_error $e) {
				if ($con_transaccion) {
					toba::db($fuente)->abortar_transaccion();
				}
				throw $e;
			}
		}
	}
	
	static function crear_usuario_sociales($nombre_usuario, $nombres, $sectores_usuario=array(), $fuente=null)
	{
		if (self::existe_usuario_sociales($nombre_usuario, $fuente)) {
			throw new toba_error_usuario('Ya existe un usuario de Sociales registrado con ese ID, intente otro por favor');
		} elseif (isset ($sectores_usuario) && isset ($sectores_usuario[0]) && !empty ($sectores_usuario[0])) {
			try {
				toba::db($fuente)->abrir_transaccion();
				
				$cod_sector = $sectores_usuario[0];
				
				//DOY DE ALTA EL USUARIO EN SOCIALES
				$sql = "INSERT INTO AS_USUARIOS(usuario, nombre, cod_sector) 
						VALUES (LOWER('{$nombre_usuario}'), '{$nombres}', '{$cod_sector}')";

				toba::logger()->debug("Alta usuario SOCIALES: $sql");
				toba::db($fuente)->ejecutar($sql);

				dao_usuarios_intervan::modificar_sectores_sociales($nombre_usuario, $sectores_usuario , false, $fuente);
				
				toba::db($fuente)->cerrar_transaccion();
			} catch (toba_error $e) {
				toba::db($fuente)->abortar_transaccion();
				throw $e;
			}
		}
	}
	
	static function existe_usuario_rentas($nombre_usuario, $fuente=null)
	{
		$sql = "SELECT * 
				FROM RE_USUARIOS 
				WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")";
		$datos_usuario = toba::db($fuente)->consultar_fila($sql);
		if (!empty($datos_usuario)) {
		    return true;
		} else {
		    return false;
		}
	}
	
	static function modificar_dependencias_rentas($nombre_usuario, $dependencias_usuario=array(), $con_transaccion = true, $fuente=null)
	{
		if (!empty($dependencias_usuario)) {
			try {
				if ($con_transaccion) {
					toba::db($fuente)->abrir_transaccion();
				}

				$arr_dependencias_rentas = ctr_funciones_basicas::matriz_to_array(dao_varios::get_lov_dependencias_x_nombre(), 'cod_dependencia');		
				
				$datos_usuario = dao_usuarios::get_datos_usuario($nombre_usuario);
				$cod_dependencia_act = $datos_usuario['cod_dependencia'];
				
				// modifico la dependencia del usuario si la actual fue quitada del usuario
				if (!in_array($cod_dependencia_act, $dependencias_usuario) && in_array($dependencias_usuario[0], $arr_dependencias_rentas)) {
					$cod_dependencia = $dependencias_usuario[0];
					$sql = "UPDATE RE_USUARIOS
							SET cod_dependencia = " . quote($cod_dependencia) . "
							WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")";
					toba::db($fuente)->ejecutar($sql);
				}
				
				if ($con_transaccion) {
					toba::db($fuente)->cerrar_transaccion();
				}
			} catch (toba_error $e) {
				if ($con_transaccion) {
					toba::db($fuente)->abortar_transaccion();
				}
				throw $e;
			}
		}
	}
	
	static function crear_usuario_rentas($nombre_usuario, $nombres, $dependencias_usuario=array(), $fuente=null)
	{
		if (self::existe_usuario_rentas($nombre_usuario, $fuente)) {
			throw new toba_error_usuario('Ya existe un usuario de Rentas registrado con ese ID, intente otro por favor');
		} elseif (isset ($dependencias_usuario) && isset ($dependencias_usuario[0]) && !empty ($dependencias_usuario[0])) {
			try {
				toba::db($fuente)->abrir_transaccion();
				
				$cod_dependencia = $dependencias_usuario[0];
				
				//DOY DE ALTA EL USUARIO EN RENTAS
				$sql = "INSERT INTO RE_USUARIOS(usuario, nombre, cod_dependencia) 
						VALUES (UPPER('{$nombre_usuario}'), '{$nombres}', '{$cod_dependencia}')";

				toba::logger()->debug("Alta usuario RENTAS: $sql");
				toba::db($fuente)->ejecutar($sql);

				toba::db($fuente)->cerrar_transaccion();
			} catch (toba_error $e) {
				toba::db($fuente)->abortar_transaccion();
				throw $e;
			}
		}
	}
	
	static function existe_usuario_db($nombre_usuario, $fuente=null)
	{
		$sql = "SELECT *
				FROM DBA_USERS
				WHERE upper(USERNAME) = UPPER(" . quote($nombre_usuario) . ")";
	    $datos_usuario = toba::db($fuente)->consultar_fila($sql);
		if (!empty($datos_usuario)) {
		    return true;
		} else {
		    return false;
		}
	}
	
	static function crear_usuario_db($nombre_usuario, $fuente=null)
	{
		if (self::existe_usuario_db($nombre_usuario, $fuente)) {
			throw new toba_error_usuario('Ya existe un usuario de base de datos registrado con ese ID, intente otro por favor');
		} else {
			try {
				toba::db($fuente)->abrir_transaccion();
				$nombre_usuario = strtoupper($nombre_usuario);
				
				$sql = "CREATE USER \"$nombre_usuario\" IDENTIFIED BY \"$nombre_usuario\" DEFAULT TABLESPACE " .self::TABLESPACE . " TEMPORARY TABLESPACE " . self::TEMPTS . " PROFILE DEFAULT;";

				toba::logger()->debug("Alta usuario base de datos: $sql");
				toba::db($fuente)->ejecutar($sql);
				
				$sql = "GRANT UNLIMITED TABLESPACE TO \"$nombre_usuario\"";
				toba::db($fuente)->ejecutar($sql);
				
				dao_usuarios_intervan::otorgar_permisos_db($nombre_usuario, false, $fuente);
				
				toba::db($fuente)->cerrar_transaccion();
			} catch (toba_error $e) {
				toba::db($fuente)->abortar_transaccion();
				throw $e;
			}
		}
	}
	
	static function otorgar_permisos_db($nombre_usuario, $con_transaccion = true, $fuente=null)
	{
		if (self::existe_usuario_db($nombre_usuario, $fuente)) {
			try {
				if ($con_transaccion) {
					toba::db($fuente)->abrir_transaccion();
				}
				$nombre_usuario = strtoupper($nombre_usuario);
				
				$sql = "GRANT CONNECT TO \"$nombre_usuario\"";
				toba::db($fuente)->ejecutar($sql);
				
				$sql = "GRANT RESOURCE TO \"$nombre_usuario\"";
				toba::db($fuente)->ejecutar($sql);
				
				$sql = "GRANT SGM_CONSULTA TO \"$nombre_usuario\"";
				toba::db($fuente)->ejecutar($sql);
				
				$sql = "GRANT SGM_INGRESO TO \"$nombre_usuario\"";
				toba::db($fuente)->ejecutar($sql);
				
				$sql = "GRANT SGR_CONSULTA TO \"$nombre_usuario\"";
				toba::db($fuente)->ejecutar($sql);
				
				$sql = "GRANT SGR_INGRESO TO \"$nombre_usuario\"";
				toba::db($fuente)->ejecutar($sql);
				
				$sql = "GRANT SGC_CONSULTA TO \"$nombre_usuario\"";
				toba::db($fuente)->ejecutar($sql);
				
				$sql = "GRANT SGC_INGRESO TO \"$nombre_usuario\"";
				toba::db($fuente)->ejecutar($sql);
				
				$arr_roles_otorgados = ctr_funciones_basicas::matriz_to_array(dao_usuarios_intervan::get_roles_otorgados($nombre_usuario, $fuente), 'granted_role');
				if (!empty($arr_roles_otorgados)) {
					$otros_roles = ", " . implode(', ', $arr_roles_otorgados);
				} else {
					$otros_roles = '';
				}
				
				$sql = "ALTER USER \"$nombre_usuario\" DEFAULT ROLE CONNECT, RESOURCE, SGM_CONSULTA, SGR_CONSULTA, SGC_CONSULTA $otros_roles";
				
				toba::db($fuente)->ejecutar($sql);
				
				if ($con_transaccion) {
					toba::db($fuente)->cerrar_transaccion();
				}
				
			} catch (toba_error $e) {
				if ($con_transaccion) {
					toba::db($fuente)->abortar_transaccion();
				}
				throw $e;
			}
		}
	}
	
	static function get_roles_otorgados($nombre_usuario, $fuente=null)
	{
		if (isset($nombre_usuario)) {
			$sql = "SELECT * 
					FROM DBA_ROLE_PRIVS 
					WHERE upper(grantee)= UPPER('$nombre_usuario')
					AND default_role = 'YES' 
					AND granted_role NOT IN('CONNECT','RESOURCE', 'SGM_CONSULTA','SGM_INGRESO','SGR_CONSULTA','SGR_INGRESO','SGC_CONSULTA', 'SGC_INGRESO');";
			return toba::db($fuente)->consultar($sql);
		} else {
			return array();
		}		
	}
	
	static function existe_usuario_esquema_seguridad($nombre_usuario, $cod_unidad_administracion, $fuente=null)
	{
		$sql = "SELECT *
				FROM SSE_USUARIOS 
				WHERE UPPER(usuario) = UPPER(" . quote($nombre_usuario) . ")
				AND empresa = " . quote($cod_unidad_administracion) . ";";
	    $datos_usuario = toba::db($fuente)->consultar_fila($sql);
		if (!empty($datos_usuario)) {
		    return true;
		} else {
		    return false;
		}
	}
	
	static function crear_usuario_esquema_seguridad($nombre_usuario, $nombres, $cod_unidad_administracion, $fuente=null)
	{
		if (self::existe_usuario_esquema_seguridad($nombre_usuario, $cod_unidad_administracion, $fuente)) {
			throw new toba_error_usuario('Ya existe un usuario en el esquema de seguridad registrado con ese ID, intente otro por favor');
		} else {
			try {
				toba::db($fuente)->abrir_transaccion();
				
				$sql = "INSERT INTO SSE_USUARIOS (empresa,usuario,descripcion) 
						VALUES ('{$cod_unidad_administracion}', UPPER('{$nombre_usuario}'), '{$nombres}')";

				toba::logger()->debug("Alta usuario del esquema de seguridad: $sql");
				toba::db($fuente)->ejecutar($sql);

				toba::db($fuente)->cerrar_transaccion();
			} catch (toba_error $e) {
				toba::db($fuente)->abortar_transaccion();
				throw $e;
			}
		}
	}
	
	static function actualizar_usuario_esquema_seguridad($nombre_usuario, $nombres, $cod_unidad_administracion, $fuente=null)
	{
		if (!self::existe_usuario_esquema_seguridad($nombre_usuario, $cod_unidad_administracion, $fuente)) {
			throw new toba_error_usuario('No existe el usuario en el esquema de seguridad');
		} else {
			try {
				toba::db($fuente)->abrir_transaccion();
				
				$sql = "UPDATE SSE_USUARIOS 
						SET descripcion = '{$nombres}'
						WHERE upper(usuario) = UPPER('{$nombre_usuario}');";

				toba::logger()->debug("Actualizacion del usuario del esquema de seguridad: $sql");
				toba::db($fuente)->ejecutar($sql);

				toba::db($fuente)->cerrar_transaccion();
			} catch (toba_error $e) {
				toba::db($fuente)->abortar_transaccion();
				throw $e;
			}
		}
	}
	
	static private function get_cod_eng_org_rrhh($dato, $fuente=null) {
		return array(
			'id_entidad' => intval(substr($dato, 0, 2)),
			'id_organizacion' => intval(substr($dato, 2, dao_usuarios_ldap::get_digitos_organizacion_rrhh())),
		);
	}
	
	static private function get_cod_emp_suc_ventas_agua($dato, $fuente=null) {
		return array(
			'id_empresa' => intval(substr($dato, 0, 2)),
			'id_sucursal' => intval(substr($dato, 2, dao_usuarios_ldap::get_digitos_sucursales_ventas_agua())),
		);
	}
	
	static function sincronizar_usuario_ldap($id_usuario, $proyecto, $fuente=null) {
		$archivo = toba::nucleo()->toba_instalacion_dir().'/instalacion.ini';
		$ini = parse_ini_file($archivo, true);
		if (isset($ini['sincronizar_usuarios_ldap']) && $ini['sincronizar_usuarios_ldap'] == 1 && !dao_usuarios_ldap::proyecto_excluido_sincronizacion($proyecto)) {
			try {
				
				// Usuarios y perfiles funcionales
				$datos_usuario_ldap = dao_usuarios_ldap::get_datos_usuario($id_usuario);
				$grupos_acceso = dao_usuarios_toba::get_perfiles_funcionales_usuario($datos_usuario_ldap['uid'], $proyecto);

				if (isset($datos_usuario_ldap) && !empty($datos_usuario_ldap)) {
					if (isset($ini['crear_usuarios_db']) && $ini['crear_usuarios_db'] == 1 && !in_array($proyecto, array('usuarios_ldap'))) {
						// controlo si existe en la base negocios
						if (dao_usuarios_intervan::existe_usuario_db($datos_usuario_ldap['uid'], $fuente)) { // el usuario existe en la base de negocios
							dao_usuarios_intervan::otorgar_permisos_db($datos_usuario_ldap['uid'], true, $fuente);
						} else { // el usuario no existe en la base de negocios
							dao_usuarios_intervan::crear_usuario_db($datos_usuario_ldap['uid'], $fuente);
						}
					}
					// Dependencias del usuario por proyecto
					dao_usuarios_intervan::sincronizar_dependencias_usuario_ldap($id_usuario, $proyecto, $datos_usuario_ldap, $fuente);
					
					// controlo si existe en la base de toba
					if (dao_usuarios_toba::existe_usuario_toba($datos_usuario_ldap['uid'])) { // el usuario existe en toba
						dao_usuarios_toba::modificar_grupos_acceso_toba($datos_usuario_ldap['uid'], $proyecto, $grupos_acceso, true);
					} else { // el usuario no existe en toba
						dao_usuarios_toba::crear_usuario_toba($datos_usuario_ldap['uid'], $datos_usuario_ldap['cn'], $datos_usuario_ldap['mail'], $proyecto, $grupos_acceso);
					}
				}			
			} catch (toba_error $e) {
				toba::notificacion()->error('Error en la sincronizacion de usuarios entre LDAP y Toba: ' . $e->get_mensaje());
				toba::logger()->error('Error en la sincronizacion de usuarios entre LDAP y Toba: ' . $e->get_mensaje());
				throw $e;
			}
		}
	}
	
	static function sincronizar_dependencias_usuario_ldap($id_usuario, $proyecto, $datos_usuario_ldap, $fuente=null) {
		try {
			if (in_array($proyecto, array('administracion', 'compras', 'costos', 'contabilidad', 'presupuesto'))) {
				// Usuario de administracion, unidades de administracion y unidades ejecutoras
				// Unidades de administracion
				$unidades_administracion_usuario = dao_usuarios_ldap::get_unidades_administracion($id_usuario);
				// Unidades ejecutoras
				$unidades_ejecutoras_usuario = dao_usuarios_ldap::get_unidades_ejecutoras($id_usuario);

				// controlo si existe el usuario de administracion en la base de negocios
				if (dao_usuarios_intervan::existe_usuario_administracion($datos_usuario_ldap['uid'], $fuente)) { // el usuario existe en administracion
					dao_usuarios_intervan::modificar_unidades_administracion($id_usuario, $unidades_administracion_usuario, true, $fuente);
					dao_usuarios_intervan::modificar_unidades_ejecutoras($id_usuario, $unidades_ejecutoras_usuario, true, $fuente);
				} else { // el usuario no existe en administracion
					dao_usuarios_intervan::crear_usuario_administracion($datos_usuario_ldap['uid'], $datos_usuario_ldap['cn'], $unidades_administracion_usuario, $unidades_ejecutoras_usuario, $fuente);
				}

				// Usuario de compras, ambitos y sectores de compra
				// Ambitos y sectores de compra
				$ambitos_compras_usuario = dao_usuarios_ldap::get_ambitos_compra($id_usuario);
				$sectores_compras_usuario = dao_usuarios_ldap::get_sectores_compra($id_usuario);

				// controlo si existe el usuario de compras en la base de negocios
				if (dao_usuarios_intervan::existe_usuario_compras($datos_usuario_ldap['uid'], $fuente)) { // el usuario existe en compras
					dao_usuarios_intervan::modificar_ambitos_sectores_compras($id_usuario, $ambitos_compras_usuario, $sectores_compras_usuario, true, $fuente);
				} else { // el usuario no existe en compras
					dao_usuarios_intervan::crear_usuario_compras($datos_usuario_ldap['uid'], $datos_usuario_ldap['cn'], $ambitos_compras_usuario, $sectores_compras_usuario, $fuente);
				}

				// controlo si existe el usuario del esquema de seguridad
				if (dao_usuarios_intervan::existe_usuario_esquema_seguridad($datos_usuario_ldap['uid'], dao_usuarios_logueado::get_cod_unidad_administracion_usuario($datos_usuario_ldap['uid'], $fuente), $fuente)) { // el usuario existe en el esquema de seguridad
					dao_usuarios_intervan::actualizar_usuario_esquema_seguridad($datos_usuario_ldap['uid'], $datos_usuario_ldap['cn'], dao_usuarios_logueado::get_cod_unidad_administracion_usuario($datos_usuario_ldap['uid'], $fuente), $fuente);
				} else { // el usuario no existe en la base de negocios
					dao_usuarios_intervan::crear_usuario_esquema_seguridad($datos_usuario_ldap['uid'], $datos_usuario_ldap['cn'], dao_usuarios_logueado::get_cod_unidad_administracion_usuario($datos_usuario_ldap['uid'], $fuente), $fuente);
				}
			} elseif (in_array($proyecto, array('rrhh'))) {
				// Usuario de RRHH, entidades y organizaciones
				$ent_org_rrhh_usuario = dao_usuarios_ldap::get_ent_org_rrhh($id_usuario);

				// controlo si existe el usuario de RRHH en la base de negocios
				if (dao_usuarios_intervan::existe_usuario_rrhh($datos_usuario_ldap['uid'], $fuente)) { // el usuario existe en RRHH
					dao_usuarios_intervan::modificar_ent_org_rrhh($id_usuario, $ent_org_rrhh_usuario, true, $fuente);
				} else { // el usuario no existe en rrhh
					dao_usuarios_intervan::crear_usuario_rrhh($datos_usuario_ldap['uid'], $datos_usuario_ldap['cn'], $ent_org_rrhh_usuario, $fuente);
				}
			} elseif (in_array($proyecto, array('sociales'))) {
				// Usuario de Sociales y sectores
				$sectores_sociales_usuario = dao_usuarios_ldap::get_sectores_sociales($id_usuario);

				// controlo si existe el usuario de Sociales en la base de negocios
				if (dao_usuarios_intervan::existe_usuario_sociales($datos_usuario_ldap['uid'], $fuente)) { // el usuario existe en Sociales
					dao_usuarios_intervan::modificar_sectores_sociales($id_usuario, $sectores_sociales_usuario, true, $fuente);
				} else { // el usuario no existe en Sociales
					dao_usuarios_intervan::crear_usuario_sociales($datos_usuario_ldap['uid'], $datos_usuario_ldap['cn'], $sectores_sociales_usuario, $fuente);
				}
			} elseif (in_array($proyecto, array('rentas'))) {
				// Usuario de Rentas y dependencias
				$dependencias_rentas_usuario = dao_usuarios_ldap::get_dependencias_rentas($id_usuario);

				// controlo si existe el usuario de Rentas en la base de negocios
				if (dao_usuarios_intervan::existe_usuario_rentas($datos_usuario_ldap['uid'], $fuente)) { // el usuario existe en Rentas
					dao_usuarios_intervan::modificar_dependencias_rentas($id_usuario, $dependencias_rentas_usuario, true, $fuente);
				} else { // el usuario no existe en Rentas
					dao_usuarios_intervan::crear_usuario_rentas($datos_usuario_ldap['uid'], $datos_usuario_ldap['cn'], $dependencias_rentas_usuario, $fuente);
				}
			} elseif (in_array($proyecto, array('ventas_agua'))) {
				// Usuario de ventas_agua, empresas y sucursales
				$emp_suc_ventas_agua_usuario = dao_usuarios_ldap::get_emp_suc_ventas_agua($id_usuario);

				// controlo si existe el usuario de ventas_agua en la base de negocios
				if (dao_usuarios_intervan::existe_usuario_ventas_agua($datos_usuario_ldap['uid'], $fuente)) { // el usuario existe en Ventas Agua
					dao_usuarios_intervan::modificar_emp_suc_ventas_agua($id_usuario, $emp_suc_ventas_agua_usuario, true, $fuente);
				} else { // el usuario no existe en ventas agua
					dao_usuarios_intervan::crear_usuario_ventas_agua($datos_usuario_ldap['uid'], $datos_usuario_ldap['cn'], $emp_suc_ventas_agua_usuario, $fuente);
				}
			}
		} catch (toba_error $e) {
			toba::notificacion()->error('Error en la sincronizacion de dependencias del usuario entre LDAP y Toba: ' . $e->get_mensaje());
			toba::logger()->error('Error en la sincronizacion de dependencias del usuario entre LDAP y Toba: ' . $e->get_mensaje());
			throw $e;
		}
	}
	
	static function sincronizar_usuarios_ldap($usuarios, $proyectos = array(), $fuente=null) {
		foreach ($usuarios as $usuario) {
			if (!isset($proyectos) || empty($proyectos)) {
				dao_usuarios_intervan::sincronizar_usuario_ldap($usuario, null, $fuente);
			} else {
				foreach ($proyectos as $proyecto) {
					dao_usuarios_intervan::sincronizar_usuario_ldap($usuario, $proyecto, $proyecto);	
				}
			}
		}
	}
	
	private static function get_empresas_ventas_agua($fuente=null) {
		$sql = "SELECT  e.*
				FROM empresas e";
		$datos = toba::db($fuente)->consultar($sql);
		return $datos;
	}
	
	private static function get_sucursales_ventas_agua($fuente=null) {
		$sql = "SELECT  s.*
				FROM sucursales s";
		$datos = toba::db($fuente)->consultar($sql);
		return $datos;
	}
	
}

?>
