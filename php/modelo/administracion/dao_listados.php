<?php
class dao_listados {


	static public function get_lov_cuenta_corriente_x_nro ($nro_cuenta_corriente){

		$sql =" SELECT KRCTCT.*, KRCTCT.NRO_CUENTA_CORRIENTE ||' - '|| KRCTCT.DESCRIPCION ||' - '|| L_ADPR.CUIT as lov_descripcion
				FROM KR_CUENTAS_CORRIENTE KRCTCT,
				     AD_CAJAS_CHICAS L_ADCACH,
				     AD_PROVEEDORES L_ADPR,
				     CG_REF_CODES CG
				WHERE KRCTCT.ID_CAJA_CHICA = L_ADCACH.ID_CAJA_CHICA (+) AND
				      KRCTCT.ID_PROVEEDOR = L_ADPR.ID_PROVEEDOR (+)
				       AND CG.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE'
				       AND CG.RV_LOW_VALUE = KRCTCT.TIPO_CUENTA_CORRIENTE and krctct.nro_cuenta_corriente = $nro_cuenta_corriente";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];

	}

	static public function get_rango_de_cuentas($filtro = array()){
		$where = ' 1=1 ';
		if (isset($filtro['cod_unidad_administracion'])){
			$where .=" and KRCTCT.cod_unidad_administracion = ".$filtro['cod_unidad_administracion']."";
			unset($filtro['cod_unidad_administracion']);
		}

		if (isset($filtro['tipo_cuenta'])){
			$where .= " and KRCTCT.tipo_cuenta_corriente = '".strtoupper($filtro['tipo_cuenta'])."'";
			unset($filtro['tipo_cuenta']);
		}

		if (isset($filtro['cod_cuenta_desde'])){
			$where .= " AND KRCTCT.nro_cuenta_corriente >= ".$filtro['cod_cuenta_desde']."";
			unset($filtro['cod_cuenta_desde']);
		}
		$sql = "SELECT min(nro_cuenta_corriente) min, max(nro_cuenta_corriente) max
				  FROM KR_CUENTAS_CORRIENTE KRCTCT,
					   AD_CAJAS_CHICAS L_ADCACH,
					   AD_PROVEEDORES L_ADPR,
					   CG_REF_CODES CG
				 WHERE KRCTCT.ID_CAJA_CHICA = L_ADCACH.ID_CAJA_CHICA (+) AND
				       KRCTCT.ID_PROVEEDOR = L_ADPR.ID_PROVEEDOR (+)
				       AND CG.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE'
				       AND CG.RV_LOW_VALUE = KRCTCT.TIPO_CUENTA_CORRIENTE and $where
			   	 ORDER BY NRO_CUENTA_CORRIENTE ASC";
	 	return toba::db()->consultar_fila($sql);
	}

	static public function get_lov_cuentas_corriente_x_nro ($nombre, $filtro = array()){

		if (isset($nombre)) {
            //$trans_id  = ctr_construir_sentencias::construir_translate_ilike('KRCTCT.id_cuenta_corriente', $nombre);
            $trans_des = ctr_construir_sentencias::construir_translate_ilike('KRCTCT.descripcion', $nombre);
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('KRCTCT.nro_cuenta_corriente', $nombre);
            $where = " and ($trans_des OR $trans_nro)";
        } else {
            $where = " and 1=1 ";
        }

		if (isset($filtro['cod_unidad_administracion'])){
			$where .=" and KRCTCT.cod_unidad_administracion = ".$filtro['cod_unidad_administracion']."";
			unset($filtro['cod_unidad_administracion']);
		}

		if (isset($filtro['tipo_cuenta'])){
			$where .= " and KRCTCT.tipo_cuenta_corriente = '".strtoupper($filtro['tipo_cuenta'])."'";
			unset($filtro['tipo_cuenta']);
		}

		if (isset($filtro['cod_cuenta_desde'])){
			$where .= " AND KRCTCT.nro_cuenta_corriente >= ".$filtro['cod_cuenta_desde']."";
			unset($filtro['cod_cuenta_desde']);
		}

		$sql =" SELECT KRCTCT.*, KRCTCT.NRO_CUENTA_CORRIENTE ||' - '|| KRCTCT.DESCRIPCION ||' - '|| L_ADPR.CUIT as lov_descripcion
				FROM KR_CUENTAS_CORRIENTE KRCTCT,
				     AD_CAJAS_CHICAS L_ADCACH,
				     AD_PROVEEDORES L_ADPR,
				     CG_REF_CODES CG
				WHERE KRCTCT.ID_CAJA_CHICA = L_ADCACH.ID_CAJA_CHICA (+) AND
				      KRCTCT.ID_PROVEEDOR = L_ADPR.ID_PROVEEDOR (+)
				      AND CG.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE'
				      AND CG.RV_LOW_VALUE = KRCTCT.TIPO_CUENTA_CORRIENTE
				      $where
				ORDER BY NRO_CUENTA_CORRIENTE ASC;";
		$datos = toba::db()->consultar($sql);

		return $datos;

	}


	public static function get_lov_beneficiario_x_id ($id_beneficiario){
		$sql =" SELECT ADBE.*, ADBE.ID_BENEFICIARIO ||' - '|| ADBE.NOMBRE ||' - '|| ADBE.COD_TIPO_DOCUMENTO ||': '|| ADBE.NRO_DOCUMENTO as lov_descripcion
				FROM AD_BENEFICIARIOS ADBE
				WHERE id_beneficiario = ".quote($id_beneficiario)."
				ORDER BY ID_BENEFICIARIO ASC";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}

	static public function get_min_beneficiario ()
	{
		$sql = "SELECT min(id_beneficiario) id_beneficiario
				FROM AD_BENEFICIARIOS";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['id_beneficiario'];
	}

	static public function get_max_beneficiario ()
	{
		$sql = "SELECT max(id_beneficiario) id_beneficiario
				FROM AD_BENEFICIARIOS";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['id_beneficiario'];
	}

	static public function get_lov_beneficiario_x_nombre ($nombre, $filtro = array()){
		$where = " 1=1 ";
		if (isset($nombre)) {
            //$trans_id  = ctr_construir_sentencias::construir_translate_ilike('KRCTCT.id_cuenta_corriente', $nombre);
            $trans_des = ctr_construir_sentencias::construir_translate_ilike('ADBE.nombre', $nombre);
            $trans_id = ctr_construir_sentencias::construir_translate_ilike('ADBE.id_beneficiario', $nombre);
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('ADBE.nro_documento', $nombre);
            $where .= " and ($trans_des OR $trans_nro OR $trans_id)";
        }
		$sql =" SELECT ADBE.*, ADBE.ID_BENEFICIARIO ||' - '|| ADBE.NOMBRE ||' - '|| ADBE.COD_TIPO_DOCUMENTO ||': '|| ADBE.NRO_DOCUMENTO as lov_descripcion
				FROM AD_BENEFICIARIOS ADBE
				WHERE $where
				ORDER BY ID_BENEFICIARIO ASC";

		$datos = toba::db()->consultar($sql);
		return $datos;
	}


	public static function get_lov_auxiliar_x_codigo ($codigo){
		$sql =" SELECT KRAUEX.COD_AUXILIAR ||' - '|| KRAUEX.DESCRIPCION as lov_descripcion
				FROM   KR_AUXILIARES_EXT KRAUEX
				WHERE (pkg_pr_auxiliares.activo(KRAUEX.COD_AUXILIAR) = 'S') and cod_auxiliar = $codigo
				ORDER BY COD_AUXILIAR;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}

	public static function get_min_auxiliar()
	{
		$sql = "SELECT min(cod_auxiliar) cod_auxiliar
				 from KR_AUXILIARES_EXT";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cod_auxiliar'];
	}

	public static function get_max_auxiliar()
	{
		$sql = "SELECT max(cod_auxiliar) cod_auxiliar
				 from KR_AUXILIARES_EXT";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cod_auxiliar'];
	}

	public static function get_lov_auxiliares_x_nombre ($nombre){
		$where = " 1=1 ";
		if (isset($nombre)) {
            $trans_des = ctr_construir_sentencias::construir_translate_ilike('KRAUEX.DESCRIPCION', $nombre);
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('KRAUEX.COD_AUXILIAR', $nombre);
            $where .= " and ($trans_des OR $trans_cod)";
        }
		$sql =" SELECT KRAUEX.*, KRAUEX.COD_AUXILIAR ||' - '|| KRAUEX.DESCRIPCION as lov_descripcion
				FROM   KR_AUXILIARES_EXT KRAUEX
				WHERE $where and (pkg_pr_auxiliares.activo(KRAUEX.COD_AUXILIAR) = 'S')
				ORDER BY COD_AUXILIAR;";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

	public static function get_lov_tipo_retencion_x_codigo ($codigo){
		$sql =" SELECT ADTIRE.COD_TIPO_RETENCION ||' - '|| ADTIRE.DESCRIPCION as lov_descripcion
				FROM AD_TIPOS_RETENCION ADTIRE, CG_REF_CODES CG
				WHERE CG.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE' AND CG.RV_LOW_VALUE = ADTIRE.TIPO_CUENTA_CORRIENTE and
					  ADTIRE.COD_TIPO_RETENCION = $codigo";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}

	public static function get_lov_tipo_rentencion_x_nombre ($nombre){
		$where = " 1=1 ";
		if (isset($nombre)) {
            $trans_des = ctr_construir_sentencias::construir_translate_ilike('ADTIRE.DESCRIPCION', $nombre);
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('ADTIRE.COD_TIPO_RETENCION', $nombre);
            $where .= " and ($trans_des OR $trans_cod)";
        }
		$sql =" SELECT ADTIRE.*, ADTIRE.COD_TIPO_RETENCION ||' - '|| ADTIRE.DESCRIPCION as lov_descripcion
				FROM AD_TIPOS_RETENCION ADTIRE, CG_REF_CODES CG
				WHERE $where and CG.RV_DOMAIN = 'KR_TIPO_CUENTA_CORRIENTE' AND CG.RV_LOW_VALUE = ADTIRE.TIPO_CUENTA_CORRIENTE;";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

	public static function get_min_cuenta_banco ($filtro = [])
	{
		$where = " 1=1 ";
		$where .=" and " . ctr_construir_sentencias::get_where_filtro($filtro,'krcb','1=1') ;
		$sql ="SELECT min(krcb.nro_cuenta) nro_cuenta
				FROM kr_cuentas_banco krcb
			    WHERE $where ";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['nro_cuenta'];
	}

	public static function get_max_cuenta_banco ($filtro = [])
	{
		$where = " 1=1 ";
		$where .=" and " . ctr_construir_sentencias::get_where_filtro($filtro,'krcb','1=1') ;
		$sql ="SELECT max(krcb.nro_cuenta) nro_cuenta
				FROM kr_cuentas_banco krcb
			    WHERE $where ";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['nro_cuenta'];
	}

	public static function get_lov_cuentas_banco ($nombre, $filtro = array()){
		$where = " 1=1 ";
		if (isset($nombre)) {
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('KRCUBA.NRO_CUENTA', $nombre);
            $trans_des = ctr_construir_sentencias::construir_translate_ilike('KRCUBA.DESCRIPCION', $nombre);
            $where .= " and ($trans_des OR $trans_nro)";
        }

        $sql = "SELECT KRCUBA.*, KRCUBA.NRO_CUENTA ||' - '|| KRCUBA.DESCRIPCION as lov_descripcion
				FROM KR_CUENTAS_BANCO KRCUBA
				WHERE $where and (KRCUBA.cod_unidad_administracion = ".$filtro['cod_unidad_administracion']."
				      AND KRCUBA.tipo_cuenta_banco = '".strtoupper($filtro['tipo_cuenta_global'])."'
				      AND KRCUBA.clase_cuenta_banco = '".strtoupper($filtro['clase_cuenta'])."'
				      AND ((PKG_KR_USUARIOS.USUARIO_TIENE_UES(" . quote(toba::usuario()->get_id()) . ") = 'S'
				      AND PKG_KR_USUARIOS.TIENE_UE(" . quote(toba::usuario()->get_id()) . ",krcuba.COD_UNIDAD_EJECUTORA) = 'S')
				      OR (PKG_KR_USUARIOS.USUARIO_TIENE_UES(" . quote(toba::usuario()->get_id()) . ") = 'N')))
				ORDER BY NRO_CUENTA;";

		$datos = toba::db()->consultar($sql);
		return $datos;
	}

	public static function get_lov_cuentas_bco_x_id($id_cuenta_banco)
	{
		if (!empty($id_cuenta_banco)) {
	        $sql = "SELECT KRCUBA.*, KRCUBA.NRO_CUENTA ||' - '|| KRCUBA.DESCRIPCION as lov_descripcion
					FROM KR_CUENTAS_BANCO KRCUBA
					WHERE KRCUBA.ID_CUENTA_BANCO = $id_cuenta_banco";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}
	}

	public static function get_id_cuenta_bco_x_nro ($nro_cuenta){
		$sql = "SELECT id_cuenta_banco
				FROM KR_CUENTAS_BANCO
				WHERE nro_cuenta = ".quote($nro_cuenta);
		$datos = toba::db()->consultar_fila($sql);
		//echo $sql;
		return $datos['id_cuenta_banco'];
	}

	public static function get_lov_cuentas_bco_x_nro ($nro_cuenta){
        $sql = "SELECT KRCUBA.*, KRCUBA.NRO_CUENTA ||' - '|| KRCUBA.DESCRIPCION as lov_descripcion
				FROM KR_CUENTAS_BANCO KRCUBA
				WHERE KRCUBA.nro_CUENTA = ".quote($nro_cuenta);
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}



	public static function get_lov_sub_cuenta_bco_x_id ($id){
		$sql = "SELECT KRSCB.COD_SUB_CUENTA_BANCO ||' - '|| KRSCB.DESCRIPCION ||' - ID: '|| KRSCB.ID_SUB_CUENTA_BANCO lov_descripcion
				FROM KR_SUB_CUENTAS_BANCO KRSCB
				WHERE krscb.id_sub_cuenta_banco = $id";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];
	}

	public static function get_lov_sub_cuenta_bco_x_nombre ($nombre, $filtro = array()){
		$where = " 1=1 ";
		if (isset($nombre)) {
			$trans_id = ctr_construir_sentencias::construir_translate_ilike('KRSCB.ID_SUB_CUENTA_BANCO', $nombre);
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('KRSCB.COD_SUB_CUENTA_BANCO', $nombre);
            $trans_des = ctr_construir_sentencias::construir_translate_ilike('KRSCB.DESCRIPCION', $nombre);
            $where .= " and ($trans_des OR $trans_cod OR $trans_id)";
        }

		$sql = "SELECT KRSCB.*, KRSCB.COD_SUB_CUENTA_BANCO ||' - '|| KRSCB.DESCRIPCION ||' - ID: '|| KRSCB.ID_SUB_CUENTA_BANCO lov_descripcion
				FROM KR_SUB_CUENTAS_BANCO KRSCB
				WHERE $where and (krscb.id_cuenta_banco = ".$filtro['id_cuenta_desde']."
					         AND exists (select 1
					                     from kr_cuentas_banco
					                     where id_cuenta_banco = krscb.id_cuenta_banco
					                            AND sub_cuenta_banco = 'S'))";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}


	static public function get_lov_comprobante_caja_chica_x_id ($id_caja_chica){
		$sql = "SELECT ADCACH.ID_CAJA_CHICA ||' - '|| ADCACH.DESCRIPCION lov_descripcion
				FROM AD_CAJAS_CHICAS ADCACH, KR_AUXILIARES_EXT L_KRAUEX
				WHERE ADCACH.COD_AUXILIAR = L_KRAUEX.COD_AUXILIAR AND (PKG_KR_USUARIOS.tiene_acceso_cach_usuario(" . quote(toba::usuario()->get_id()) . ", ADCACH.id_caja_chica)='S')
				      AND ADCACH.ID_CAJA_CHICA = ".quote($id_caja_chica);
		$datos = toba::db()->consultar_fila($sql);
		return $datos['lov_descripcion'];

	}

	static public function get_lov_comprobantes_caja_chica_x_nombre ($nombre, $filtro = array()){
		$where = " 1=1 ";
		if (isset($nombre)) {
			$trans_id = ctr_construir_sentencias::construir_translate_ilike('ADCACH.ID_CAJA_CHICA', $nombre);
            $trans_des = ctr_construir_sentencias::construir_translate_ilike('ADCACH.DESCRIPCION', $nombre);
            $where .= " and ($trans_des OR $trans_id)";
        }

		$sql = "SELECT ADCACH.*, ADCACH.ID_CAJA_CHICA ||' - '|| ADCACH.DESCRIPCION lov_descripcion
				FROM AD_CAJAS_CHICAS ADCACH, KR_AUXILIARES_EXT L_KRAUEX
				WHERE $where and ADCACH.COD_AUXILIAR = L_KRAUEX.COD_AUXILIAR AND (PKG_KR_USUARIOS.tiene_acceso_cach_usuario(" . quote(toba::usuario()->get_id()) . ", ADCACH.id_caja_chica)='S')";

		$datos = toba::db()->consultar($sql);
		return $datos;
	}

	public static function generar_archivo_libro_iva($cod_unidad_administracion,$fecha_desde,$fecha_hasta,$periodo,$tipo_reg){

		 try {
            
                $fecha_desde = dao_general::transformar_fecha($fecha_desde);
                $fecha_hasta = dao_general::transformar_fecha($fecha_hasta);

                $sql = "
                    BEGIN
                        :resultado := exportar_del(
                            :p_unidad_administracion
                            , :periodo
                            , :tipo_reg
                            , to_date(:fecha_desde, 'DD/MM/YYYY')
                            , to_date(:fecha_hasta, 'DD/MM/YYYY')
                              );
                    END; ";
                $sql = ctr_procedimientos::sanitizar_consulta($sql);
                $parametros = [
                    [
                    'nombre' => 'p_unidad_administracion',
                    'tipo_dato' => PDO::PARAM_INT,
                    'longitud' => 20,
                    'valor' => $cod_unidad_administracion
                    ],
                    [
                    'nombre' => 'periodo',
                    'tipo_dato' => PDO::PARAM_INT,
                    'longitud' => 20,
                    'valor' => $periodo],
                    [
                    'nombre' => 'tipo_reg',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 20,
                    'valor' => $tipo_reg],
                    [
                    'nombre' => 'fecha_desde',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 20,
                    'valor' => $fecha_desde],
                    [
                    'nombre' => 'fecha_hasta',
                    'tipo_dato' => PDO::PARAM_INT,
                    'longitud' => 20,
                    'valor' => $fecha_hasta],
                    [
                    'nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''
                    ],
                ];
                
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                
                if ( $resultado[5]['valor'] != '' ) {
                    if ($con_transaccion) {
                            toba::db()->cerrar_transaccion();
                    }
                } else {
                    if ($con_transaccion) {
                        toba::db()->abortar_transaccion();
                    }
                }
                return $resultado[5]['valor'];
           
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            if ($con_transaccion) {
                    toba::db()->abortar_transaccion();
            }
            return 'Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
            if ($con_transaccion) {
                    toba::db()->abortar_transaccion();
            }
            return 'Error ' . $e->get_mensaje();
        }

	}


	public static function crear_archivos_ventas($cod_unidad_administracion,$fecha_desde,$fecha_hasta){

		 try {
            
                $sql = "
                    BEGIN
                        :resultado := PKG_AFIP_VENTAS_COMPRAS.crear_archivos_ventas(
                            :p_unidad_administracion
                            ,  to_date(:fecha_desde, 'DD/MM/YYYY')
                            , to_date(:fecha_hasta, 'DD/MM/YYYY')
                              );
                    END; ";
                $sql = ctr_procedimientos::sanitizar_consulta($sql);
                $parametros = [
                    [
                    'nombre' => 'p_unidad_administracion',
                    'tipo_dato' => PDO::PARAM_INT,
                    'longitud' => 20,
                    'valor' => $cod_unidad_administracion
                    ],
                    [
                    'nombre' => 'fecha_desde',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 20,
                    'valor' => $fecha_desde],
                    [
                    'nombre' => 'fecha_hasta',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 20,
                    'valor' => $fecha_hasta],
                    [
                    'nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''
                    ],
                ];
                
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                
                // if ( $resultado[3]['valor'] != '' ) {
                //     if ($con_transaccion) {
                //             toba::db()->cerrar_transaccion();
                //     }
                // } else {
                //     if ($con_transaccion) {
                //         toba::db()->abortar_transaccion();
                //     }
                // }
                return $resultado[3]['valor'];
           
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            // if ($con_transaccion) {
            //         toba::db()->abortar_transaccion();
            // }
            return 'Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
            // if ($con_transaccion) {
            //         toba::db()->abortar_transaccion();
            // }
            return 'Error ' . $e->get_mensaje();
        }

	}

	public static function crear_archivos_compras($cod_unidad_administracion,$fecha_desde,$fecha_hasta){

		 try {
            
                // $fecha_desde = dao_general::transformar_fecha($fecha_desde);
                // $fecha_hasta = dao_general::transformar_fecha($fecha_hasta);

                $sql = "
                    BEGIN
                        :resultado :=  PKG_AFIP_VENTAS_COMPRAS.crear_archivos_compras(
                            :p_unidad_administracion
                            ,  to_date(:fecha_desde, 'DD/MM/YYYY')
                            , to_date(:fecha_hasta, 'DD/MM/YYYY')
                              );
                    END; ";
                $sql = ctr_procedimientos::sanitizar_consulta($sql);
                $parametros = [
                    [
                    'nombre' => 'p_unidad_administracion',
                    'tipo_dato' => PDO::PARAM_INT,
                    'longitud' => 20,
                    'valor' => $cod_unidad_administracion
                    ],
                    [
                    'nombre' => 'fecha_desde',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 20,
                    'valor' => $fecha_desde],
                    [
                    'nombre' => 'fecha_hasta',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 20,
                    'valor' => $fecha_hasta],
                    [
                    'nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''
                    ],
                ];
                
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                
                // if ( $resultado[3]['valor'] != '' ) {
                //     if ($con_transaccion) {
                //             toba::db()->cerrar_transaccion();
                //     }
                // } else {
                //     if ($con_transaccion) {
                //         toba::db()->abortar_transaccion();
                //     }
                // }
                return $resultado[3]['valor'];
           
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            // if ($con_transaccion) {
            //         toba::db()->abortar_transaccion();
            // }
            return 'Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
            // if ($con_transaccion) {
            //         toba::db()->abortar_transaccion();
            // }
            return 'Error ' . $e->get_mensaje();
        }

	}

	public static function mostrar_archivo($unidad_de_administracion,$periodo,$tipo_reg){

		$sql = "SELECT linea 
                FROM registro_afip_ventas_compras
                WHERE cod_unidad_administracion = $unidad_de_administracion
                AND periodo= '$periodo'
                AND tipo_registro= '$tipo_reg'";

        $datos = toba::db()->consultar($sql);
		return $datos;


	}
}
