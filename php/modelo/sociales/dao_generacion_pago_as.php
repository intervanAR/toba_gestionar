<?php
class dao_generacion_pago_as {
	
	static public function get_pagos ($filtro = array()){
		$desde= null;
		$hasta= null;
		if(isset($filtro['desde'])){
			$desde= $filtro['desde'];
			$hasta= $filtro['hasta'];

			unset($filtro['desde']);
			unset($filtro['hasta']);
		}
		
		$where = "  1=1 ";
		if (isset($filtro) )
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'pag', '1=1');
		
		$sql = "SELECT pag.*, TO_CHAR (pag.fecha, 'DD/MM/YYYY') fecha_format,
				       astb.cod_tipo_beneficio || ' - ' || astb.descripcion tipo_beneficio
				       ,decode(pag.ANULADO,'S','SI','NO') anulado_format
       				   ,decode(pag.aprobado,'S','SI','NO') aprobado_format
				  FROM as_generaciones_pagos pag, as_tipos_beneficios astb
				 WHERE pag.cod_tipo_beneficio = astb.cod_tipo_beneficio and $where 
				 ORDER BY pag.id_generacion ";
		return toba::db()->consultar($sql);
	}
	
	static public function eliminar_orden($id_orden_pago){
		$sql = "delete from as_ordenes_pago where id_orden_pago = ".quote($id_orden_pago);
		return toba::db()->ejecutar($sql);
	}
	static public function get_ordenes_pago ($filtro = array()){
		$desde= null;
		$hasta= null;
		if(isset($filtro['desde'])){
			$desde= $filtro['desde'];
			$hasta= $filtro['hasta'];

			unset($filtro['desde']);
			unset($filtro['hasta']);
		}
		
		$where = "  1=1 ";
		if (isset($filtro) )
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'ord', '1=1');
		
		$sql = "SELECT ord.*, TO_CHAR (fecha, 'DD/MM/YYYY') fecha_format,
					   trim(to_char(ord.monto, '$999,999,999,990.00')) as monto_format,
				       DECODE (ord.anulado, 'N', 'No', 'S', 'Si') anulado_format,
				       DECODE (ord.aprobado, 'N', 'No', 'S', 'Si') aprobado_format,
				       ben.nombre, ben.nro_documento, benf.descripcion beneficio
				  FROM as_ordenes_pago ord,
				       as_solicitudes sol,
				       as_beneficiarios ben,
				       as_beneficios benf
				 WHERE ord.id_solicitud = sol.id_solicitud
				   AND sol.id_beneficiario = ben.id_beneficiario
				   AND sol.cod_beneficio = benf.cod_beneficio and $where 
				 ORDER BY ord.id_orden_pago";
		return toba::db()->consultar($sql);
	}
	
	static public function generar_seleccion ($id_generacion, $anio, $periodo, $cod_tipo_beneficio, $con_transaccion = true){
		$sql = "BEGIN :resultado := pkg_as_generaciones_pagos.generar_seleccion(:id_generacion, :anio, :periodo, :cod_tipo_beneficio);END;";
		$parametros = array ( array( 'nombre' => 'id_generacion', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 6,
                                     'valor' => $id_generacion),
							 array(  'nombre' => 'anio', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 6,
                                     'valor' => $anio),
      						 array(  'nombre' => 'periodo', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 6,
                                     'valor' => $periodo),
      						 array(  'nombre' => 'cod_tipo_beneficio', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 6,
                                     'valor' => $cod_tipo_beneficio),
      						 array(  'nombre' => 'resultado', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4000,
                                     'valor' => ''));
      	$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', $con_transaccion);
      	return $resultado[4]['valor'];
	}
	
	static public function aprobar_generacion ($id_generacion, $con_transaccion = true){
		if (!is_null($id_generacion)){
			$sql = "BEGIN :resultado := pkg_as_generaciones_pagos.aprobar_generacion_pago(:id_generacion); END;";
			$parametros = array(array(	'nombre' => 'id_generacion',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $id_generacion),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);
			$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql,$parametros, '','',$con_transaccion);
			return $resultado[1]['valor'];
		}
	}
	
	static public function anular_generacion ($id_generacion, $con_transaccion = true){
		if (!is_null($id_generacion)){
			$sql = "BEGIN :resultado := pkg_as_generaciones_pagos.anular_generacion_pago(:id_generacion); END;";
			$parametros = array(array(	'nombre' => 'id_generacion',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $id_generacion),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);
			$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql,$parametros, '','',$con_transaccion);
			return $resultado[1]['valor'];
		}
	}
	
	
} 