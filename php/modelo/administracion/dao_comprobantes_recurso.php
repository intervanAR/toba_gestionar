<?php

class dao_comprobantes_recurso {
	
	static public function get_comprobantes_recurso($filtro= array(), $orden = array()){
        $desde= null; $hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];
			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}
		$where = self::armar_where($filtro);
		
        $sql = "SELECT	cr.*, 
        				decode(cr.aprobado,'S','Si','No') aprobado_format,
        			    decode(cr.anulado,'S','Si','No') anulado_format, 
						to_char(cr.fecha_comprobante, 'dd/mm/yyyy') fecha_comprobante_format, 
						pkg_kr_transacciones.saldo_transaccion(cr.id_transaccion, cr.id_cuenta_corriente, sysdate) saldo_transaccion,
						atcr.descripcion tipo_comprobante,
						kcc.nro_cuenta_corriente || ' - ' || kcc.descripcion cuenta_corriente,
						trim(to_char(cr.importe, '$999,999,999,990.00')) as importe_format,
						to_char(cr.fecha_anulacion, 'dd/mm/yyyy') fecha_anulacion_format, 
						CASE
							WHEN cr.clase_comprobante = 'NOR' THEN 'Normal'
							WHEN cr.clase_comprobante = 'AJU' THEN 'Ajuste'
							WHEN cr.clase_comprobante = 'REI' THEN 'Reimputación'
							ELSE 'Normal'
						END clase_comprobante_format
                FROM AD_COMPROBANTES_RECURSO cr
				JOIN AD_TIPOS_COMPROBANTE_RECURSO atcr ON (cr.cod_tipo_comprobante = atcr.cod_tipo_comprobante)
				JOIN KR_CUENTAS_CORRIENTE kcc ON cr.id_cuenta_corriente =  kcc.id_cuenta_corriente
                WHERE  $where
		ORDER BY id_comprobante_recurso DESC";
        
        $sql= dao_varios::paginador($sql, null, $desde, $hasta, null, $orden);
	    $datos = toba::db()->consultar($sql);
        return $datos;        
    }
    
	static public function armar_where ($filtro = array())
	{
        $where= " 1=1 ";
		if (isset($filtro['ids_comprobantes'])) {
			$where .= " AND CR.id_comprobante_recurso IN (" . $filtro['ids_comprobantes'] . ") ";
			unset($filtro['ids_comprobantes']);
		}
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'cr', '1=1');
        return $where;
	}
	
	static public function get_cantidad ($filtro = array())
	{
		$where = self::armar_where($filtro);
		$sql = " SELECT count(*) cantidad
				   FROM AD_COMPROBANTES_RECURSO cr
				   JOIN AD_TIPOS_COMPROBANTE_RECURSO atcr ON (cr.cod_tipo_comprobante = atcr.cod_tipo_comprobante)
				   JOIN KR_CUENTAS_CORRIENTE kcc ON cr.id_cuenta_corriente =  kcc.id_cuenta_corriente
                  WHERE $where ";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cantidad'];
	}
	
	static public function get_comprobante_recurso_x_id($id_comprobante_recurso){     
		if (isset($id_comprobante_recurso) && !empty($id_comprobante_recurso)) {
           $sql = "SELECT	cr.*, 
							'(' || cr.ID_COMPROBANTE_RECURSO||') '||cr.nro_comprobante || ' - ' ||to_char(cr.FECHA_COMPROBANTE,'dd/mm/rr')|| ' ('||trim(to_char(cr.importe, '$999,999,999,990.00')) || ')' as lov_descripcion
                   FROM AD_COMPROBANTES_RECURSO cr
                   WHERE cr.id_comprobante_recurso = $id_comprobante_recurso
                   ORDER BY lov_descripcion;";  
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
	
	static public function get_datos_comprobante_recurso_x_id($id_comprobante_recurso){     
       if (isset($id_comprobante_recurso)) {
           $sql = "SELECT	cr.*, 
							'(' || cr.ID_COMPROBANTE_RECURSO||') '||cr.nro_comprobante || ' - ' ||to_char(cr.FECHA_COMPROBANTE,'dd/mm/rr')|| ' ('||trim(to_char(cr.importe, '$999,999,999,990.00')) || ')' as lov_descripcion,
							atcr.negativo
                   FROM AD_COMPROBANTES_RECURSO cr
				   JOIN AD_TIPOS_COMPROBANTE_RECURSO atcr ON (cr.cod_tipo_comprobante = atcr.cod_tipo_comprobante)
                   WHERE cr.id_comprobante_recurso = $id_comprobante_recurso
                   ORDER BY lov_descripcion;";  
           $datos = toba::db()->consultar_fila($sql);
           return $datos;
       } else {
           return array();
       }
    } 
	
	static public function get_lov_comprobantes_recurso_x_nombre($nombre, $filtro = array()) {
		if (isset($nombre)) {
			$trans_codigo = ctr_construir_sentencias::construir_translate_ilike('id_comprobante_recurso', $nombre);			
			$trans_nro = ctr_construir_sentencias::construir_translate_ilike('nro_comprobante', $nombre);
			$where = "($trans_codigo OR $trans_nro)";
        } else {
            $where = '1=1';
        }
		
		if (isset($filtro['presupuestario'])) {
			$where.= " AND PKG_AD_COMPROBANTES_PAGOS.muestro_recurso(cr.ANULADO, cr.APROBADO, '".$filtro['presupuestario']."',
					cr.IMPORTE, 
					Pkg_Kr_Transacciones.saldo_transaccion (cr.ID_TRANSACCION, cr.ID_CUENTA_CORRIENTE, NULL),
					'".$filtro['tipo_cuenta_corriente']."', '".$filtro['ejercicio_anterior']."',
					cr.FECHA_COMPROBANTE, to_date(substr('".$filtro['fecha_comprobante']."',1,10),'yyyy-mm-dd'),
					cr.ID_CUENTA_CORRIENTE , ".$filtro['id_cuenta_corriente'].") = 'S'
					AND PKG_KR_USUARIOS.TIENE_UA(upper('".$filtro['usuario']."'),COD_UNIDAD_ADMINISTRACION) = 'S'";
								
			unset($filtro['presupuestario']);
			unset($filtro['tipo_cuenta_corriente']);
			unset($filtro['ejercicio_anterior']);
			unset($filtro['fecha_comprobante']);
			unset($filtro['usuario']);
			unset($filtro['id_cuenta_corriente']);
		}
		if (isset($filtro['para_ordenes_pago'])) {
			$where .= " AND       (    cr.aprobado = 'S'
						AND cr.anulado = 'N'
						AND cr.importe < 0
						AND ".$filtro['cod_uni_admin']." =
															  cr.cod_unidad_administracion
						AND (   (    '".$filtro['ejercicio_anterior']."' = 'N'
								 AND pkg_kr_ejercicios.retornar_nro_ejercicio
															  (to_date(substr('".$filtro['fecha_orden_pago']."',1,10),'yyyy-mm-dd')) =
										pkg_kr_ejercicios.retornar_nro_ejercicio
																	 (cr.fecha_comprobante)
								)
							 OR (    '".$filtro['ejercicio_anterior']."' = 'S'
								 AND pkg_kr_ejercicios.retornar_nro_ejercicio
															  (to_date(substr('".$filtro['fecha_orden_pago']."',1,10),'yyyy-mm-dd')) =
										  pkg_kr_ejercicios.retornar_nro_ejercicio
																	 (cr.fecha_comprobante)
										+ 1
								)
							)
					   )";
			unset($filtro['para_ordenes_pago']);
			unset($filtro['cod_uni_admin']);
			unset($filtro['ejercicio_anterior']);
			unset($filtro['fecha_orden_pago']);
		}
		
		$where .= ' AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'cr', '1=1');

        $sql = "SELECT  cr.*, 
						'(' || cr.id_comprobante_recurso||') '||cr.nro_comprobante||' - '||to_char(cr.fecha_comprobante,'dd/mm/rr')||' ('||trim(to_char(cr.importe, '$999,999,999,990.00')) || ')' as lov_descripcion
				FROM AD_COMPROBANTES_RECURSO cr
                WHERE $where
                ORDER BY lov_descripcion ASC;";		
	
        $datos = toba::db()->consultar($sql);

        return $datos;
		
	}
	
	public static function get_importes_encabezado_comprobante_recurso($id_comprobante_recurso) 
	{
        if (isset($id_comprobante_recurso)) {
            $sql_sel = "SELECT  acr.importe,
								PKG_KR_TRANSACCIONES.SALDO_TRANSACCION(acr.ID_TRANSACCION,acr.ID_CUENTA_CORRIENTE, NULL) saldo
					FROM ad_comprobantes_recurso acr
					WHERE acr.id_comprobante_recurso = " . quote($id_comprobante_recurso) . ";";
            $datos = toba::db()->consultar_fila($sql_sel);
            return $datos;
        } else {
            return array();
        }
    }
	
	static public function get_datos_extras_comprobante_recurso_x_id($id_comprobante_recurso) 
	{
        if (isset($id_comprobante_recurso)) {
            $sql = "SELECT	acr.anulado,
							acr.aprobado,
							acr.fecha_anulacion
					FROM ad_comprobantes_recurso acr
					WHERE acr.id_comprobante_recurso = " . quote($id_comprobante_recurso) . ";";

            $datos = toba::db()->consultar_fila($sql);

            return $datos;
        } else {
            return array();
        }
    }
	
	static public function get_tipos_comprobantes_recurso($filtro = array()) 
	{
        $where = " 1=1 ";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'ADTICORE', '1=1');
        $sql_sel = "SELECT	ADTICORE.COD_TIPO_COMPROBANTE COD_TIPO_COMPROBANTE,
							ADTICORE.DESCRIPCION DESCRIPCION,
							ADTICORE.COD_TIPO_COMPROBANTE || ' - ' || ADTICORE.DESCRIPCION LOV_DESCRIPCION,
							ADTICORE.NEGATIVO NEGATIVO,
							ADTICORE.PRESTAMO PRESTAMO,
							ADTICORE.TIPO_CUENTA_CORRIENTE TIPO_CUENTA_CORRIENTE,
							ADTICORE.NOTA_CREDITO NOTA_CREDITO
					FROM AD_TIPOS_COMPROBANTE_RECURSO ADTICORE
					WHERE $where
					ORDER BY LOV_DESCRIPCION";
        $datos = toba::db()->consultar($sql_sel);
        return $datos;
    }
	
	static public function get_clases_comprobantes_recurso() 
	{
		$datos = array(
						array(	'clase_comprobante' => 'NOR',
								'descripcion' => 'Normal'),
						array(	'clase_comprobante' => 'AJU',
								'descripcion' => 'Ajuste'),
						array(	'clase_comprobante' => 'REI',
								'descripcion' => 'Reimputación'),
		);
        return $datos;
    }
	
	static public function confirmar_comprobante_recurso($id_comprobante_recurso) {
        if (isset($id_comprobante_recurso)) {
            $mensaje_error = 'Error en la confirmación del comprobante de recurso.';
            try {
                toba::db()->abrir_transaccion();
                $sql = "BEGIN :resultado := pkg_kr_transacciones.confirmar_comp_recurso(:id_comprobante_recurso); END;";

                $parametros = array(array(	'nombre' => 'id_comprobante_recurso',
											'tipo_dato' => PDO::PARAM_INT,
											'longitud' => 32,
											'valor' => $id_comprobante_recurso),
									array(	'nombre' => 'resultado',
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 4000,
											'valor' => ''),
								);

                $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
                $valor_resultado = $resultado[count($resultado) - 1]['valor'];
                if ($valor_resultado != 'OK') {
                    toba::notificacion()->error($valor_resultado);
                    toba::logger()->error($valor_resultado);
                    toba::db()->abortar_transaccion();
                } else {
					toba::notificacion()->info('El comprobante de recurso se confirmó exitosamente.');
					toba::db()->cerrar_transaccion();
				}
            } catch (toba_error_db $e_db) {
                toba::notificacion()->error($mensaje_error . ' ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
                toba::logger()->error($mensaje_error . ' ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
                toba::db()->abortar_transaccion();
            } catch (toba_error $e) {
                toba::notificacion()->error($mensaje_error . ' ' . $e->get_mensaje());
                toba::logger()->error($mensaje_error . ' ' . $e->get_mensaje());
                toba::db()->abortar_transaccion();
            }
        }
    }
	
	static public function anular_comprobante_recurso($id_comprobante_recurso, $fecha_anulacion) {
        if (isset($id_comprobante_recurso) && isset($fecha_anulacion)) {
            $sql = "BEGIN :resultado := pkg_kr_transacciones.anular_comp_recurso(:id_comprobante_recurso, to_date(substr(:fecha_anulacion,1,10),'yyyy-mm-dd')); END;";

            $parametros = array(array(	'nombre' => 'id_comprobante_recurso',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $id_comprobante_recurso),
								array(	'nombre' => 'fecha_anulacion',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 32,
										'valor' => $fecha_anulacion),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
							);
            $resultado = ctr_procedimientos::ejecutar_procedure_mensajes($sql, $parametros, 'El comprobante de recurso se anuló exitosamente.', 'Error en la anulación del comprobante de recurso.', false);
            return $resultado[2]['valor'];
        }
    }
	
}

?>
