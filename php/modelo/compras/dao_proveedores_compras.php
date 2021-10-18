<?php

class dao_proveedores_compras
{

	public static function get_proveedores ($filtro = array()){
		unset($filtro['ambito_usuario']);
		$where = " 1=1 ";

		if (isset($filtro['id_rubro']) && !empty($filtro['id_rubro'])){

			$where .=" and copr.id_proveedor in (
                 SELECT copr.id_proveedor
                   FROM co_rubros r, co_proveedores_rubros copr
                  WHERE r.id_rubro = copr.id_rubro
                        AND r.id_rubro = ".quote($filtro['id_rubro']).")";
			unset($filtro['id_rubro']);
		}

		if (isset($filtro['razon_social']) && !empty($filtro['razon_social'])){
			$where .=" and upper(copr.RAZON_SOCIAL) like upper('%".$filtro['razon_social']."%')";
			unset($filtro['razon_social']);
		}

		if (isset($filtro['cuit']) && !empty($filtro['cuit'])){
			$where .=" and upper(copr.cuit) like upper('%".$filtro['cuit']."%')";
			unset($filtro['cuit']);
		}


		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'COPR', '1=1');
		$sql = "SELECT COPR.*, CORP.DESCRIPCION registro_descripcion,
		 			(select nro_expediente from kr_expedientes where id_expediente = copr.id_expediente) nro_expediente,
				       to_char(COPR.ESTADO_FECHA, 'DD/MM/YYYY') as fecha_estado_format,
				       to_char(COPR.fecha_renovacion, 'DD/MM/YYYY') as fecha_renovacion_format,
				       to_char(COPR.fecha_vencimiento, 'DD/MM/YYYY') as fecha_vencimiento_format,
				       (SELECT RV_MEANING FROM CG_REF_CODES WHERE RV_DOMAIN = 'CO_DESTINO_COMPRA' AND RV_LOW_VALUE = COPR.DESTINO_COMPRA) destino_compra_format,
				       (SELECT RV_MEANING FROM CG_REF_CODES WHERE RV_DOMAIN = 'CO_PROVINCIAS' AND RV_LOW_VALUE = COPR.PROVINCIA) provincia_format,
				       (SELECT RV_MEANING FROM CG_REF_CODES WHERE RV_DOMAIN = 'CO_ESTADO_PROVEEDOR' AND RV_LOW_VALUE = COPR.ESTADO) estado_format
				FROM CO_PROVEEDORES COPR
				     LEFT JOIN CO_REGISTROS_PROVEEDORES CORP
				            ON CORP.COD_REGISTRO = COPR.COD_REGISTRO
				WHERE $where AND CORP.cod_registro in (
                        SELECT amre.cod_registro
                        FROM co_usuarios usu,
                        co_sectores sec,
                        co_ambitos  amb,
                        co_ambitos_registros amre,
                        co_registros_proveedores reg
                        WHERE  sec.cod_sector = usu.cod_sector
                        and    amb.cod_ambito = sec.cod_ambito
                        and    amre.cod_ambito = amb.cod_ambito
                        and    reg.cod_registro = amre.cod_registro
                        and    usu.usuario = " . quote(strtoupper(toba::usuario()->get_id())) . ")
				ORDER BY ID_PROVEEDOR DESC;";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}


	public static function get_proveedores_compras_x_nombre($nombre = null, $filtro = array())
	{
		$where = ' 1=1 ';
		if (isset($nombre)) {
            $trans_id_proveedor = ctr_construir_sentencias::construir_translate_ilike('cp.id_proveedor', $nombre);
			$trans_razon_social = ctr_construir_sentencias::construir_translate_ilike('cp.razon_social', $nombre);
            $where .= " AND ($trans_id_proveedor OR $trans_razon_social)";
        }

		if (isset($filtro['cod_ambito']) && isset($filtro['destino_compra'])) {
			$where .= " AND CP.cod_registro in (select cod_registro
												from   co_ambitos_registros
												where  cod_ambito = " . quote($filtro['cod_ambito']) . "
												and    destino_compra = " . quote($filtro['destino_compra']) . ") ";
			unset($filtro['cod_ambito']);
			unset($filtro['destino_compra']);
		}

		if (isset($filtro['prov_presentado']) && isset($filtro['nro_compra']) && isset($filtro['nro_item'])) {
			$where .= " AND CP.id_proveedor IN (SELECT PRPR.id_proveedor
												FROM co_proveedores_presentados PRPR,
												co_items_compra_precios ITCOPR,
												co_items_compra ITCO
												WHERE PRPR.nro_compra = ITCOPR.nro_compra
												AND PRPR.id_proveedor = ITCOPR.id_proveedor
												AND ITCO.nro_compra = ITCOPR.nro_compra
												AND ITCO.nro_renglon = ITCOPR.nro_renglon
												AND ITCO.nro_compra = " . quote($filtro['nro_compra']) . "
												AND ITCO.nro_item = " . quote($filtro['nro_item']) . ") ";
			unset($filtro['prov_presentado']);
			unset($filtro['nro_compra']);
			unset($filtro['nro_item']);
		}

		if (isset($filtro['nro_orden'])) {
			$where .= " AND CP.id_proveedor IN (SELECT CO.id_proveedor
												FROM co_ordenes co
												WHERE co.id_proveedor = cp.id_proveedor
												AND co.nro_orden = " . quote($filtro['nro_orden']) . ") ";
			unset($filtro['nro_orden']);
		}

		if (isset($filtro['arr_estado'])) {
			if (!empty($filtro['arr_estado'])) {
				$where .= " AND CP.estado in (" . implode(', ', $filtro['arr_estado']) . ") ";
			}
			unset($filtro['arr_estado']);
		}

		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'cp', '1=1');

		$sql_sel = "SELECT	CP.*,
							cp.id_proveedor || ' - ' || cp.razon_social || ' (Cod.: ' ||cp.cod_proveedor ||')' as lov_descripcion
					FROM	CO_PROVEEDORES CP
					WHERE $where
					ORDER BY CP.ID_PROVEEDOR ASC";
		return toba::db()->consultar($sql_sel);
	}

	static public function get_id_razon_social_cod_x_id_proveedor($id_proveedor)
	{
		if (isset($id_proveedor)) {
            $sql = "SELECT	cp.id_proveedor || ' - ' || cp.razon_social || ' (Cod.: ' ||cp.cod_proveedor ||')' as lov_descripcion
					FROM CO_PROVEEDORES CP
					WHERE cp.id_proveedor = " . quote($id_proveedor) . ";";
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

	static public function get_modelos_cartas($filtro = array())
	{
		$where = ' 1 = 1';
		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'cmc', '1=1');

		$sql_sel = "SELECT	CMC.*,
							CMC.nro_modelo || ' - ' || CMC.descripcion modelo
					FROM	CO_MODELOS_CARTAS CMC
					WHERE $where;";
		return toba::db()->consultar($sql_sel);
    }

    static public function get_lov_registros_x_codigo ($codigo){
    	if (!is_null($codigo)){
	    	$sql = "SELECT CORP.COD_REGISTRO ||' - '|| CORP.DESCRIPCION AS LOV_DESCRIPCION
					FROM CO_REGISTROS_PROVEEDORES CORP
					WHERE CORP.COD_REGISTRO = $codigo";
	    	$datos = toba::db()->consultar_fila($sql);
	    	return $datos['lov_descripcion'];
    	}else return '';
    }

    static public function get_lov_registros_x_nombre ($nombre, $filtro = array()){
   		$where = ' 1=1 ';
		if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('CORP.COD_REGISTRO', $nombre);
			$trans_des = ctr_construir_sentencias::construir_translate_ilike('CORP.DESCRIPCION', $nombre);
            $where .= " AND ($trans_cod OR $trans_des)";
        }
        if (!empty($filtro))
    		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'COPR', '1=1');

    	$sql = "SELECT CORP.*, CORP.COD_REGISTRO ||' - '|| CORP.DESCRIPCION AS LOV_DESCRIPCION
				FROM CO_REGISTROS_PROVEEDORES CORP
				WHERE $where AND CORP.COD_REGISTRO IN (SELECT cod_registro
				                            FROM   co_registros_proveedores
				                            WHERE  cod_sector = PKG_USUARIOS.sector_usuario(" . quote(toba::usuario()->get_id()) . "))";
    	$datos = toba::db()->consultar($sql);
    	return $datos;
    }

  	static public function get_lov_rubros_x_nombre ($nombre, $filtro = array()){
   		$where = ' 1=1 ';
		if (isset($nombre)) {
			$trans_cod = ctr_construir_sentencias::construir_translate_ilike('RUB.COD_RUBRO', $nombre);
			$trans_des = ctr_construir_sentencias::construir_translate_ilike('RUB.DESCRIPCION', $nombre);
            $where .= " AND ($trans_cod OR $trans_des)";
        }
        if (!empty($filtro))
    		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'RUB', '1=1');

    	$sql = "SELECT RUB.*, RUB.COD_RUBRO ||' - '|| RUB.DESCRIPCION AS LOV_DESCRIPCION
				FROM CO_RUBROS RUB
				WHERE $where
				ORDER BY COD_RUBRO ASC;";
    	$datos = toba::db()->consultar($sql);
    	return $datos;
    }

	static public function get_lov_rubro_x_id ($id_rubro){
    	$sql = "SELECT RUB.COD_RUBRO ||' - '|| RUB.DESCRIPCION AS LOV_DESCRIPCION
				FROM CO_RUBROS RUB
				WHERE RUB.id_rubro = ".quote($id_rubro);
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['lov_descripcion'];
    }

	static public function get_id_rubro_x_codigo ($cod_rubro){
    	$sql = "SELECT RUB.ID_RUBRO
				FROM CO_RUBROS RUB
				WHERE RUB.cod_rubro = ".quote($cod_rubro);
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['id_rubro'];
    }

	static public function get_descripcion_estado ($estado){
		if(isset($estado)){
		$sql = "SELECT RV_MEANING estado
				FROM CG_REF_CODES
				WHERE RV_DOMAIN = 'CO_ESTADO_PROVEEDOR'
				      AND RV_LOW_VALUE = ".quote($estado);
		$datos = toba::db()->consultar_fila($sql);
		return $datos['estado'];
    	}else return array();
	}

	static public function cambiar_estado($id_proveedor, $estado){
		if(isset($id_proveedor) && isset($estado)){
			$sql = "UPDATE co_proveedores set estado = ".quote($estado)." where id_proveedor = ".quote($id_proveedor).";";
			toba::db()->ejecutar($sql);
		}
	}

	static public function calcular_codigo_registro_x_sector_usuario($cod_sector){
		$datos = array();
		$sql = "select count(*) cant
				  from   co_registros_proveedores
				  where  cod_sector = ".quote($cod_sector);
		$datos = toba::db()->consultar_fila($sql);

		if ($datos['cant'] = 1){
			$sql = "SELECT cod_registro
					  FROM co_registros_proveedores
					 WHERE cod_sector = ".quote($cod_sector);
			$datos = toba::db()->consultar_fila($sql);
			return $datos['cod_registro'];
		}else{
			return null;
		}
	}

	static public function calcular_codigo_proveedor ($cod_registro = null){
		if (is_null($cod_registro))
			$cod_registro = 'NULL';

		$sql = "SELECT NVL(MAX(COD_PROVEEDOR),0)+1 cod_proveedor
				FROM CO_PROVEEDORES
				WHERE COD_REGISTRO = $cod_registro ";
		$datos = toba::db()->consultar_fila($sql);
    	return $datos['cod_proveedor'];
	}


	public static function interfase_ren_retornar_estado($id_comercio_re) {
		$sql = "select pkg_interfase_ren.retornar_estado($id_comercio_re) estado from dual";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['estado'];
	}

	public static function buscar_proveedor($cuit) {
		try{
			$sql = "BEGIN :resultado := pkg_proveedores.buscar_proveedor (:cuit, :razon_social, :direccion, :telefono, :nro_iibb, :provincia, :localidad, :tipo_iva); END;";
			$parametros = array(
								array(	'nombre' => 'cuit',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 11,
										'valor' => $cuit),
								array(	'nombre' => 'razon_social',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 200,
										'valor' => ''),
								array(	'nombre' => 'direccion',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 100,
										'valor' => ''),
								array(	'nombre' => 'telefono',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 100,
										'valor' => ''),
								array(	'nombre' => 'nro_iibb',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 30,
										'valor' => ''),
								array(	'nombre' => 'provincia',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 3,
										'valor' => ''),
								array(	'nombre' => 'localidad',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 100,
										'valor' => ''),
								array(	'nombre' => 'tipo_iva',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 3,
										'valor' => ''),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''));
			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, '', 'Error al obtener datos del proveedor.', false);

			$datos = array();
			$datos['razon_social'] = ''; $datos['direccion']= ''; $datos['telefono']= ''; $datos['nro_iibb'] = '';
			$datos['provincia']    = ''; $datos['localidad']= ''; $datos['tipo_iva']= ''; $datos['existe_afi'] = 'N';

			if ($resultado[8]['valor'] == 'S'){

				$datos['razon_social'] = $resultado[1]['valor'];
				$datos['direccion']    = $resultado[2]['valor'];
				$datos['telefono'] 	   = $resultado[3]['valor'];
				$datos['nro_iibb'] 	   = $resultado[4]['valor'];
				$datos['provincia']    = $resultado[5]['valor'];
				$datos['localidad']    = $resultado[6]['valor'];
				$datos['tipo_iva'] 	   = $resultado[7]['valor'];
				$datos['existe_afi']	   = $resultado[8]['valor'];
			}
			return $datos;
		}catch (Exception $e) {
			throw new toba_error_db($e->getMessage());
		}catch (Exception $e){
			throw new toba_error($e->getMessage());
		}
	}

	public static function buscar_comercio_ren($nro_comercio) {
		if (isset($nro_comercio)){
			$sql = "
		SELECT  PKG_INTERFASE_REN.retornar_id_comercio(".quote($nro_comercio).") id_comercio_re,
			PKG_INTERFASE_REN.retornar_razon_social(PKG_INTERFASE_REN.retornar_id_comercio(".quote($nro_comercio).")) razon_social,
			PKG_INTERFASE_REN.retornar_deuda(PKG_INTERFASE_REN.retornar_id_comercio(".quote($nro_comercio).") ) deuda,
	        PKG_INTERFASE_REN.retornar_nro_habilitacion(PKG_INTERFASE_REN.retornar_id_comercio(".quote($nro_comercio).") ) nro_habilitacion,
	        pkg_interfase_ren.retornar_estado(PKG_INTERFASE_REN.retornar_id_comercio(".quote($nro_comercio).")) interface_estado
					FROM DUAL;";

			$datos = toba::db()->consultar_fila($sql);
			return $datos;
		}else
			return null;

	}

	static public function buscar_nro_comercio_ren($id_comercio_re)
	{
 		$sql = "SELECT  PKG_INTERFASE_REN.retornar_nro_comercio(".quote($id_comercio_re).") nro_comercio FROM dual";
 		$datos = toba::db()->consultar_fila($sql);
		return $datos['nro_comercio'];
	}

	public static function existe_codigo_proveedor($cod_proveedor, $cod_registro){
		$sql = "SELECT COUNT (*) cant
				  FROM co_proveedores
				 WHERE cod_registro = ".quote($cod_registro)."
				   AND cod_proveedor = ".quote($cod_proveedor);
		$datos = toba::db()->consultar_fila($sql);
		if ($datos['cant'] == '0'){
			return false;
		}else{
			return true;
		}
	}

	public static function existe_cuit_proveedor($cuit, $cod_registro){
		if (isset($cod_registro) && isset($cuit)){
			$sql = "SELECT COUNT(*) cant , min(cod_proveedor) cod_proveedor
			        FROM CO_PROVEEDORES
			        WHERE cuit = ".quote($cuit)."
			        	  AND   cod_registro = ".quote($cod_registro);
			$datos = toba::db()->consultar_fila($sql);
			if ($datos['cant'] == '0'){
				return false;
			}else{
				return $datos['cod_proveedor'];
			}
		}else return null;
	}

	public static function existe_cuit_proveedor_rentas($cuit){
		if (isset($cuit)){
			$sql = "SELECT pkg_interfase_ren.existe_cuit($cuit) existe from dual";
			$datos = toba::db()->consultar_fila($sql);
			if ($datos['existe'] == 'S'){
				return true;
			}else{
				return false;
			}
		}else return null;
	}

	public static function existe_nro_comercio($nro_comercio){

		try{

			$sql = "SELECT pkg_interfase_ren.existe_nro_comercio($nro_comercio) existe from dual";
			$datos = toba::db()->consultar_fila($sql);

			if ($datos['existe'] == 'S'){
				return true;
			}
			return false;

		}catch (toba_error_db $e_db) {
			toba::logger()->error($e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
		}
	}

	public static function tiene_permiso_registro ($cod_registro){
		$user = toba::usuario()->get_id();
		$sql = "  select count(*) cant
				  from   co_registros_proveedores
				  where  cod_registro = ".quote($cod_registro)."
				  and    cod_sector = PKG_USUARIOS.sector_usuario(".quote($cod_registro).");";
		$datos = toba::db()->consultar_fila($sql);
		if ($datos['cant'] == '0'){
			return false;
		}else{
			return true;
		}
	}

	public static function calcular_fecha_vencimiento ($fecha_renovacion){
		$meses = dao_general::meses_vencimiento_proveedor();
		$sql = "  SELECT TO_CHAR(LAST_DAY(ADD_MONTHS(TRUNC(TO_DATE(".quote($fecha_renovacion).",'DD/MM/YYYY')), $meses)),'DD/MM/YYYY') fecha_vencimiento FROM DUAL;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['fecha_vencimiento'];
	}

	public static function importar_rubros ($id_proveedor){
		if (isset($id_proveedor)){
			$sql = "BEGIN :resultado := Pkg_Proveedores.incorporar_rubros(:id_proveedor); END;";
			$parametros = array(array(	'nombre' => 'id_proveedor',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 12,
										'valor' => $id_proveedor),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''));
			toba::db()->abrir_transaccion();
			$resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, 'Los rubros fueron importados.', 'Error importando rubros.', false);
			if ($resultado[1]['valor'] != 'OK'){
				toba::db()->abortar_transaccion();
			}else{
				toba::db()->cerrar_transaccion();
			}
			return $resultado[1]['valor'];
		}
	}

	/* recupera el registro de la tabla AD_PROVEEDORES en base al proveedor de compras*/
	public static function get_proveedor_ad($id_proveedor){
		$sql = "SELECT adp.*
				  FROM CO_PROVEEDORES cop, AD_PROVEEDORES adp
				 WHERE cop.id_proveedor_ad = adp.id_proveedor
				   and cop.id_proveedor = ".quote($id_proveedor);
		return toba::db()->consultar_fila($sql);
	}

	public static function get_proveedores_presentados($filtro = [])
	{
		$where = ' 1 = 1';
		if (!empty($filtro))
			$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'cp', '1=1');

		$sql = "
			SELECT cp.*
			FROM co_proveedores_presentados cp
			WHERE $where
		";
		return toba::db()->consultar($sql);
    }
}
?>
