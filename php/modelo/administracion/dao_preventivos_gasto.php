<?php

/**
 * Description of dao_preventivos_gasto
 *
 * @author lwolcan
 */
class dao_preventivos_gasto {

    static public function get_preventivo($filtro = array(), $orden = array()) {
		$desde= null;
		$hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}
        $where = self::armar_where($filtro);
        
        $sql = "SELECT adpv.id_preventivo, 
				       adpv.cod_unidad_administracion||'-'||krua.DESCRIPCION cod_unidad_administracion, 
				       adpv.nro_preventivo,
				       decode(adpv.aprobado,'S','Si','No') aprobado_format,
        			   decode(adpv.anulado,'S','Si','No') anulado_format,
				       to_char(adpv.fecha_comprobante, 'dd/mm/yyyy') fecha_comprobante,
				       adpv.clase_comprobante,
				       (select rv_meaning from cg_ref_codes where rv_domain = 'AD_CLASE_COMPROBANTE' AND rv_low_value = adpv.clase_comprobante ) clase_comprobante_format, 
				       adpv.id_preventivo_aju,
				       adpv.id_preventivo_rei, 
				       trim(to_char(adpv.importe, '$999,999,999,990.00'))  importe, 
				       adpv.usuario_carga, 
				       to_char(adpv.fecha_carga, 'dd/mm/yyyy') fecha_carga, 
				       adpv.aprobado,
				       adpv.id_transaccion, 
				       adpv.usuario_aprueba, 
				       to_char(adpv.fecha_aprueba, 'dd/mm/yyyy') fecha_aprueba,
				       adpv.anulado,
				       to_char(adpv.fecha_anulacion, 'dd/mm/yyyy') fecha_anulacion,
				       adpv.usuario_anula, 
				       to_char(adpv.fecha_anula, 'dd/mm/yyyy') fecha_anula,
				       adpv.observaciones,
				       adtp.DESCRIPCION cod_tipo_preventivo, 
				       adpv.id_expediente||'-'||kex.nro_expediente id_expediente
				  FROM ad_preventivos adpv left join kr_expedientes kex on kex.ID_EXPEDIENTE = adpv.ID_EXPEDIENTE, ad_tipos_preventivo adtp, KR_UNIDADES_ADMINISTRACION krua 
				  where adtp.COD_TIPO_PREVENTIVO = adpv.COD_TIPO_PREVENTIVO
				  and   adpv.COD_UNIDAD_ADMINISTRACION = krua.COD_UNIDAD_ADMINISTRACION
				  and $where
				  order by adpv.fecha_comprobante desc, adpv.id_preventivo desc";

		$sql= dao_varios::paginador($sql, null, $desde, $hasta, null, $orden);
        $datos = toba::db()->consultar($sql);
        /* foreach ($datos as $clave => $dato) {
          $datos[$clave]['des_clase_comprobante'] = self::get_descripcion_clase_comprobante($datos[$clave]['clase_comprobante']);
          } */
        return $datos;
    }
    
	static public function armar_where ($filtro = array())
	{
		$where = " 1=1 ";
        if (isset($filtro['fecha_comprobante'])) {
            $where .= "AND ADPV.fecha_comprobante = to_date(" . quote($filtro['fecha_comprobante']) . ", 'YYYY-MM-DD') ";
            unset($filtro['fecha_comprobante']);
        }
        if (isset($filtro['observaciones'])) {
            $where .= "AND " . ctr_construir_sentencias::construir_translate_ilike('observaciones', $filtro['observaciones']);
            unset($filtro['observaciones']);
        }
		if (isset($filtro['ids_comprobantes'])) {
			$where .= "AND ADPV.id_preventivo IN (" . $filtro['ids_comprobantes'] . ") ";
			unset($filtro['ids_comprobantes']);
		}
        if (isset($filtro['id_entidad'])) {
            $where .= "AND adpv.id_preventivo in (SELECT adprimp.id_preventivo
                                                    FROM ad_preventivos_imp adprimp
                                                   WHERE adprimp.id_entidad = ".$filtro['id_entidad'].")";
            unset($filtro['id_entidad']);
        }

        if (isset($filtro['id_programa'])) {
            $where .= "AND adpv.id_preventivo in (SELECT adprimp.id_preventivo
                                                    FROM ad_preventivos_imp adprimp
                                                   WHERE adprimp.id_programa = ".$filtro['id_programa'].")";
            unset($filtro['id_programa']);
        }
        if (isset($filtro['cod_recurso'])) {
            $where .= "AND adpv.id_preventivo in (SELECT adprimp.id_preventivo
                                                    FROM ad_preventivos_imp adprimp
                                                   WHERE adprimp.cod_recurso = ".$filtro['cod_recurso'].")";
            unset($filtro['cod_recurso']);
        }
        //Importe Ajustado
        if (isset($filtro['importe_ajustado'])){
            $where .=" and (select sum(pkg_pr_totales.total_transaccion_egreso(id_egreso, null, null, null, null, null)) importe       
                from pr_egresos
                where id_transaccion = adpv.id_transaccion) = ".$filtro['importe_ajustado'];
            unset($filtro['importe_ajustado']);
        }

        $sql_auxiliar = "select (PKG_KR_USUARIOS.in_ua_tiene_acceso(upper('" . toba::usuario()->get_id() . "'))) unidades from dual";
        $conjunto_unidades = toba::db()->consultar_fila($sql_auxiliar);
        $where .= " and  adpv.COD_UNIDAD_ADMINISTRACION in " . $conjunto_unidades['unidades'];
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ADPV', '1=1');
		return $where;
	}

    static public function get_tipos_preventivos ($filtro)
    {
        $where = ' 1=1 ';
        if (isset($filtro))
        {
            $where = ctr_construir_sentencias::get_where_filtro($filtro, 'ADTP', '1=1', array('nombre'));
        }
        $sql = "
            SELECT ADTP.*
                , DECODE (adtp.negativo, 'S', 'Si', 'No') negativo_format
                , DECODE (adtp.automatico, 'S', 'Si', 'No') automatico_format
                ,(select cod_tipo_transaccion ||' - '||descripcion from kr_tipos_transaccion where cod_tipo_transaccion = adtp.cod_tipo_transaccion) tipo_transaccion_format
                ,(select cod_tipo_transaccion ||' - '||descripcion from kr_tipos_transaccion where cod_tipo_transaccion = adtp.cod_tipo_transaccion_reimputa) tipo_transaccion_rei_format
                ,(select cod_tipo_transaccion ||' - '||descripcion from kr_tipos_transaccion where cod_tipo_transaccion = adtp.cod_tipo_transaccion) tipo_transaccion_aju_format
              FROM AD_TIPOS_PREVENTIVO ADTP
            WHERE
                $where
            ORDER BY
                adtp.cod_tipo_preventivo ";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }

	static public function get_cantidad ($filtro = array())
	{
		$where = self::armar_where($filtro);
		$sql = "select count(*) cantidad
				 FROM ad_preventivos adpv left join kr_expedientes kex on kex.ID_EXPEDIENTE = adpv.ID_EXPEDIENTE, ad_tipos_preventivo adtp, KR_UNIDADES_ADMINISTRACION krua 
				  where adtp.COD_TIPO_PREVENTIVO = adpv.COD_TIPO_PREVENTIVO
				  and   adpv.COD_UNIDAD_ADMINISTRACION = krua.COD_UNIDAD_ADMINISTRACION
				  and $where ";
			
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cantidad'];
	}
	
    static public function get_preventivo_gasto_x_id($id_preventivo) {
        if (isset($id_preventivo)) {
            $sql = "SELECT c.*
                        FROM AD_PREVENTIVOS c
                        WHERE c.id_preventivo= " . $id_preventivo;

            $datos = toba::db()->consultar_fila($sql);

            return $datos;
        } else {
            return array();
        }
    }

    static public function get_lov_preventivo($codigo) {
        if (isset($codigo)) {
            $sql = "SELECT ADPV.*, ADPV.id_preventivo || ' - ' || ADPV.nro_preventivo as lov_descripcion
		FROM AD_PREVENTIVOS ADPV
		WHERE ADPV.id_preventivo = " . quote($codigo) . ";";
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

    static public function get_lov_preventivo_x_nombre($nombre, $filtro = array()) {
        if (isset($nombre)) {
            $trans_id = ctr_construir_sentencias::construir_translate_ilike('id_preventivo', $nombre);
            $trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_preventivo', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('observaciones', $nombre);
            $where = "( $trans_id OR $trans_nro OR $trans_descripcion )";
        } else {
            $where = '1=1';
        }
        if (isset($filtro['cod_unidad_administracion']) && !empty($filtro['cod_unidad_administracion'])) {
            $where .= " AND adpv.cod_unidad_administracion = " . $filtro['cod_unidad_administracion'];
            unset($filtro['cod_unidad_administracion']);
        }
        if (isset($filtro['fecha_comprobante']) && !empty($filtro['fecha_comprobante'])) {
            $where .=" AND pkg_kr_ejercicios.retornar_ejercicio (adpv.fecha_comprobante) =
                     pkg_kr_ejercicios.retornar_ejercicio (to_date('" . $filtro['fecha_comprobante'] . "','DD/MM/YYYY'))";


            unset($filtro['fecha_comprobante']);
        }

        if (isset($filtro['con_saldo'])) {
            if ($filtro['con_saldo'] == 'S'){
                $where .=" AND saldo_preventivo(adpv.id_preventivo) > 0";
            }
            if ($filtro['con_saldo'] == 'N'){
                $where .=" AND saldo_preventivo(adpv.id_preventivo) = 0";
            }
            unset($filtro['con_saldo']);
        }
        
		if (isset($filtro['not_in_solicitudes_compra'])) {
			$where .= " AND adpv.id_preventivo NOT IN (SELECT id_preventivo
                                   FROM co_solicitudes_preventivos csp
                                  WHERE  csp.id_preventivo IS NOT NULL) ";
			unset($filtro['not_in_solicitudes_compra']);
		}
		
		if (isset($filtro['not_in_pedidos_cotizacion'])) {
			$where .= " AND adpv.id_preventivo NOT IN (SELECT id_preventivo
                                   FROM co_compras_preventivos ccp
                                  WHERE  ccp.id_preventivo IS NOT NULL) ";
			unset($filtro['not_in_pedidos_cotizacion']);
		}
		
		if (isset($filtro['mayor_igual_anio'])) {
			$where .= " and to_char(adpv.Fecha_Comprobante,'YYYY') >= {$filtro['mayor_igual_anio']} ";
			unset($filtro['mayor_igual_anio']);
		}
		
		if (isset($filtro['igual_anio'])) {
			$where .= " and to_char(adpv.Fecha_Comprobante,'YYYY') = {$filtro['igual_anio']} ";
			unset($filtro['igual_anio']);
		}

        $where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'ADPV', '1=1');

        $sql = "SELECT  ADPV.*,ADPV.ID_PREVENTIVO || ' - ' || 
                              ADPV.NRO_PREVENTIVO ||' - '|| 
                              substr(observaciones,1,2000)|| ' - ' || 
                              ADPV.ID_EXPEDIENTE lov_descripcion
                  FROM  AD_PREVENTIVOS ADPV
                        LEFT JOIN KR_EXPEDIENTES KREX ON adpv.id_expediente = krex.id_expediente
                  WHERE $where
                  ORDER BY lov_descripcion;";

        $datos = toba::db()->consultar($sql);

        return $datos;
    }

    static public function get_ui_sin_control_pres($cod_tipo_preventivo) {
        try {
            // toba::db()->abrir_transaccion();
            $sql = "BEGIN :resultado := pkg_ad_comprobantes_gasto.preventivo_sin_control(:cod_tipo_preventivo); END;";
            $parametros = array(array('nombre' => 'cod_tipo_preventivo',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $cod_tipo_preventivo),
                array('nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''),
            );
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

            //$datos["ui_sin_control_pres"]= $resultado[1]['valor'];
            // toba::db()->cerrar_transaccion();
            //return $resultado[1]['valor'];

            return array("ui_sin_control_pres" => $resultado[1]['valor']);
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            // toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
            // toba::db()->abortar_transaccion();
        }
    }

    static public function get_afectacion_especifica($cod_fuente_financiera) {
        try {
            if (isset($cod_fuente_financiera) && (!empty($cod_fuente_financiera))) {
                //      toba::db()->abrir_transaccion();
                $sql = "BEGIN :resultado := pkg_pr_fuentes.afectacion_especifica(:cod_fuente_financiera); END;";
                $parametros = array(array('nombre' => 'cod_fuente_financiera',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 32,
                        'valor' => $cod_fuente_financiera),
                    array('nombre' => 'resultado',
                        'tipo_dato' => PDO::PARAM_STR,
                        'longitud' => 4000,
                        'valor' => ''),
                );
                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

                //         toba::db()->cerrar_transaccion();

                return $resultado[1]['valor'];
            } else {
                return '';
            }
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            //      toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
            //      toba::db()->abortar_transaccion();
        }
    }

    static public function anular_preventivo_gasto($id_preventivo, $fecha, $con_transaccion = true){

        try {

            if ($con_transaccion)
                toba::db()->abrir_transaccion();

            $sql = "BEGIN :resultado := pkg_kr_transacciones.anular_preventivo(:id_preventivo, to_date(substr(:fecha,1,10),'yyyy-mm-dd')); END;";
            $parametros = array(array('nombre' => 'id_preventivo',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $id_preventivo),
                array('nombre' => 'fecha',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $fecha),
                array('nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''),
            );
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

            if ($con_transaccion){
                if ($resultado == 'OK')
                    toba::db()->cerrar_transaccion();
                else
                    toba::db()->abortar_transaccion();
            }

            return $resultado[2]['valor'];

        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            if ($con_transaccion)
                toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
            if ($con_transaccion)
                toba::db()->abortar_transaccion();
        }
    }

    static public function aprobar_preventivo_gasto($id_preventivo) {

        $sql = "SELECT count(1) cant
                FROM AD_PREVENTIVOS_IMP
                WHERE id_preventivo = " . $id_preventivo . ";";

        $datos = toba::db()->consultar_fila($sql);

        if ($datos["cant"] == '0') {
            return 'Se debe cargar una imputación para el preventivo.';
        }

        $sql = "SELECT count(1) cant
                FROM AD_PREVENTIVOS_DET
                WHERE id_preventivo = " . $id_preventivo . ";";

        $datos = toba::db()->consultar_fila($sql);

        if ($datos["cant"] == '0') {
            return 'Se debe cargar un detalle para el preventivo.';
        }

        try {
            toba::db()->abrir_transaccion();
            $sql = "BEGIN :resultado := pkg_kr_transacciones.confirmar_preventivo(:id_preventivo); END;";
            $parametros = array(array('nombre' => 'id_preventivo',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 32,
                    'valor' => $id_preventivo),
                array('nombre' => 'resultado',
                    'tipo_dato' => PDO::PARAM_STR,
                    'longitud' => 4000,
                    'valor' => ''),
            );
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

            toba::db()->cerrar_transaccion();

            //return $resultado[1]['valor'];

            return $resultado[1]['valor'];
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::logger()->error('Error ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
            toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error ' . $e->get_mensaje());
            toba::logger()->error('Error ' . $e->get_mensaje());
            toba::db()->abortar_transaccion();
        }
    }

    static public function get_importe_ajustado($id_transaccion, $aprobado, $anulado) {
        if ($aprobado == 'S' && $anulado == 'N') {
            $sql = "select sum(pkg_pr_totales.total_transaccion_egreso(id_egreso, null, null, null, null, null)) importe       
                     from pr_egresos
                     where id_transaccion = $id_transaccion";
            $datos = toba::db()->consultar_fila($sql);

            return $datos["importe"];
        } else {
            return 0;
        }
    }

    static public function get_importe_saldo($id_preventivo, $aprobado, $anulado) {
        if ($aprobado == 'S' && $anulado == 'N') {
            $sql = "select (saldo_preventivo($id_preventivo)) importe from dual;";
            $datos = toba::db()->consultar_fila($sql);

            return $datos["importe"];
        } else {
            return 0;
        }
    }

    static public function get_importe($id_preventivo) {
        if (isset($id_preventivo)) {
            $sql = "select nvl(sum(importe),0) importe    
                     from ad_preventivos_imp
                     where id_preventivo = $id_preventivo;";
            $datos = toba::db()->consultar_fila($sql);

            return $datos["importe"];
        } else {
            return 0;
        }
    }

    static public function get_importe_detalle($id_preventivo, $id_detalle) {
        if (isset($id_preventivo) && isset($id_detalle)) {
            $sql = "select nvl(sum(importe),0) importe    
                     from ad_preventivos_imp
                     where id_preventivo = $id_preventivo
                         and id_detalle=$id_detalle;";
            $datos = toba::db()->consultar_fila($sql);

            return $datos["importe"];
        } else {
            return 0;
        }
    }

    static public function get_datos_extras_preventivo_gasto_x_id($id_preventivo) {
        if (isset($id_preventivo)) {
            $sql = "SELECT	p.anulado,
							p.aprobado
                        FROM AD_PREVENTIVOS p
                        WHERE p.id_preventivo= " . $id_preventivo;

            $datos = toba::db()->consultar_fila($sql);

            return $datos;
        } else {
            return array();
        }
    }
	
	static public function get_lov_tipos_preventivo($codigo) 
	{
        if (isset($codigo)) {
            $sql = "SELECT ADTIPR.*, ADTIPR.cod_tipo_preventivo || ' - ' || ADTIPR.descripcion as lov_descripcion
		FROM AD_TIPOS_PREVENTIVO ADTIPR
		WHERE ADTIPR.cod_tipo_preventivo = " . quote($codigo) . ";";
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

    static public function get_lov_tipos_preventivo_x_nombre($nombre, $filtro) 
	{
        if (isset($nombre)) {
            $trans_cod_tipo_preventivo = ctr_construir_sentencias::construir_translate_ilike('ADTIPR.cod_tipo_preventivo', $nombre);
            $trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('ADTIPR.descripcion', $nombre);
            $where = "($trans_cod_tipo_preventivo OR $trans_descricpcion)";
        } else {
            $where = "1=1";
        }
        
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ADTIPR', '1=1');
        $sql = "SELECT ADTIPR.*, ADTIPR.COD_TIPO_PREVENTIVO || ' - ' || ADTIPR.DESCRIPCION lov_descripcion
                  FROM AD_TIPOS_PREVENTIVO ADTIPR 
                 WHERE $where ORDER BY lov_descripcion;";
       
        $datos = toba::db()->consultar($sql);
        return $datos; 
    }
    
     static public function get_tipos_preventivo_negativo($codigo) 
	{
        if (isset($codigo)) {
            $sql = "SELECT ADTIPR.negativo positivo
		FROM AD_TIPOS_PREVENTIVO ADTIPR
		WHERE ADTIPR.cod_tipo_preventivo = " . quote($codigo) . ";";
            $datos = toba::db()->consultar_fila($sql);
            if (isset($datos) && !empty($datos) && isset($datos['positivo'])) {
                return $datos['positivo'];
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

}

?>
