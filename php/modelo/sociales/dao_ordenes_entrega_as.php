<?php
class dao_ordenes_entrega_as {
	
	
	static public function get_ordenes_entrega ($filtro = array()){
		
		$where = " 1=1 ";
		if (isset($filtro) && !empty($filtro)){
			$where .= ' and '.ctr_construir_sentencias::get_where_filtro($filtro, 'ASOE','1=1');
		}
		
		$sql = "SELECT ASOE.*,
					   case when asoe.anulado = 'S' then
				            'Si'
				       else
				            'No'
				       end anulado_format,
				       case when asoe.aprobado = 'S' then
				            'Si'
				       else
				            'No'
				       end aprobado_format,
				       case when asoe.entregado = 'S' then
				            'Si'
				       else
				            'No'
				       end entregado_format,
				       to_char(trunc(asoe.fecha), 'YYYY/MM/DD') fecha_format,
				       sol.numero || ' - '|| ben.nombre || ' - '|| ben.nro_documento solicitud_format
				  FROM AS_ORDENES_ENTREGA ASOE, AS_SOLICITUDES SOL, AS_BENEFICIARIOS BEN
				 WHERE $where AND ASOE.ID_SOLICITUD = SOL.ID_SOLICITUD AND SOL.ID_BENEFICIARIO = BEN.ID_BENEFICIARIO
			  ORDER BY ID_ORDEN_ENTREGA";
		return toba::db()->consultar($sql);
		
	}
	
	
	static public function aprobar_orden_entrega ($id_orden, $con_transaccion = true){
		if (!is_null($id_orden)){
			$sql = "BEGIN :resultado := pkg_as_ordenes_entrega.confirmar_orden(:id_orden); END;";
			$parametros = array(array(	'nombre' => 'id_orden',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $id_orden),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);
			$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql,$parametros, '','',$con_transaccion);
			return $resultado[1]['valor'];
		}
	}
	
	static public function anular_orden_entrega ($id_orden, $con_transaccion = true){
		if (!is_null($id_orden)){
			$sql = "BEGIN :resultado := pkg_as_ordenes_entrega.anular_orden(:id_orden); END;";
			$parametros = array(array(	'nombre' => 'id_orden',
										'tipo_dato' => PDO::PARAM_INT,
										'longitud' => 32,
										'valor' => $id_orden),
								array(	'nombre' => 'resultado',
										'tipo_dato' => PDO::PARAM_STR,
										'longitud' => 4000,
										'valor' => ''),
								);
			$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql,$parametros, '','',$con_transaccion);
			return $resultado[1]['valor'];
		}
	}
	
	static public function get_cantidad_restante($cod_articulo, $id_solicitud){
		$sql = "SELECT PKG_AS_SOLICITUDES.entregados_restantes($id_solicitud, $cod_articulo) cantidad from dual";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cantidad'];
	}
	
	static public function get_entregas($id_orden_entrega){
		$sql = "select ent.*
				  from as_entregas ent
				 where id_orden_entrega = ".quote($id_orden_entrega)."
				 order by id_entrega";	
		return toba::db()->consultar($sql);
	}
	
	
}
?>