<?php

class dao_selecciones_as {
	
	static public function get_selecciones ($filtro = array()){
		$where = "  1=1 ";
		if (isset($filtro) )
			$where .= " AND ". ctr_construir_sentencias::get_where_filtro($filtro, 'SOL', '1=1');
			
		$sql = "SELECT sel.*,
				          tben.cod_tipo_beneficio
				       || ' - '
				       || tben.descripcion tipo_beneficio_format,
				       TO_CHAR (sel.fecha, 'dd/mm/yyyy') fecha_format,
				       TO_CHAR (sel.fecha_resolucion, 'dd/mm/yyyy') fecha_resolucion_format,
				       DECODE (sel.aprobada, 'S', 'Si', 'No') aprobada_format,
				       DECODE (sel.anulada, 'S', 'Si', 'No') anulada_format
				  FROM as_selecciones sel, as_tipos_beneficios tben
				 WHERE sel.cod_tipo_beneficio = tben.cod_tipo_beneficio and $where
				 ORDER BY sel.id_seleccion desc";
		
		return toba::db()->consultar($sql);
	}
	
	static public function get_solicitudes_seleccionadas ($id_seleccion){
		$sql = "SELECT   sol.*, TO_CHAR (sol.fecha_estado, 'YYYY/MM/DD') fecha_estado_format,
				         TO_CHAR (sol.fecha_inicio, 'YYYY/MM/DD') fecha_inicio_format,
				         TO_CHAR (sol.fecha_fin, 'YYYY/MM/DD') fecha_fin_format,
				         TO_CHAR (sol.fecha_inscripcion,
				                  'YYYY/MM/DD'
				                 ) fecha_inscripcion_format,
				         CASE sol.reinscripcion
				            WHEN 'S'
				               THEN 'Si'
				            ELSE 'No'
				         END reinscripcion_format,
				         CASE sol.renovacion
				            WHEN 'S'
				               THEN 'Si'
				            ELSE 'No'
				         END renovacion_format,
				         CASE sol.beneficiario_prog_ant
				            WHEN 'S'
				               THEN 'Si'
				            ELSE 'No'
				         END beneficiario_prog_ant_format,
				         sec.cod_sector || ' - ' || sec.nombre sector_format,
				         ben.nombre || ' - ' || ben.nro_documento beneficiario_format,
				            beneficio.cod_beneficio
				         || ' - '
				         || beneficio.descripcion beneficio_format,
				         (SELECT rv_meaning
				            FROM cg_ref_codes
				           WHERE rv_low_value = sol.estado
				             AND rv_domain = 'AS_ESTADO_SOLICITUD') estado_format,
				         solsel.DESESTIMADA,
				         case when solsel.DESESTIMADA ='S' then
				            'Si'
				         else
				            'No'
				         end desestimada_format, 
				         solsel.ID,
				         solsel.OBSERVACION observacion_sel
				    FROM as_solicitudes sol,
				         as_sectores sec,
				         as_beneficiarios ben,
				         as_beneficios beneficio,
				         as_solicitudes_seleccionadas solsel
				   WHERE sol.cod_sector = sec.cod_sector
				     AND sol.id_beneficiario = ben.id_beneficiario
				     AND sol.cod_beneficio = beneficio.cod_beneficio
				     AND solsel.id_solicitud = sol.id_solicitud
				     AND solsel.ID_SELECCION = ".quote($id_seleccion)." 
				ORDER BY solsel.id_solicitud DESC";
		return toba::db()->consultar($sql);
						 
	}
	
	static public function set_observacion_solicitud_seleccionada($id, $observacion){
		$sql = "UPDATE AS_SOLICITUDES_SELECCIONADAS SET OBSERVACION = ".quote($observacion)." WHERE ID = ".quote($id);
		return toba::db()->ejecutar($sql);
	}
	
	static public function borrar_solicitud_seleccionada ($id){
		$sql = "DELETE FROM as_solicitudes_seleccionadas
     			 WHERE ID = $id";
		return toba::db()->ejecutar($sql);
	}
	
	static public function importar_solicitud ($id_seleccion, $id_solicitud, $con_transaccion = false){
		$sql = "BEGIN :resultado := pkg_as_selecciones.importar_solicitud(:id_seleccion, :id_solicitud);END;";
		$parametros = array (array(  'nombre' => 'id_seleccion', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 12,
                                     'valor' => $id_seleccion),
		
      						 array(  'nombre' => 'id_solicitud', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 12,
                                     'valor' => $id_solicitud),
      						 
      						 array(  'nombre' => 'resultado', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4000,
                                     'valor' => ''));
      	$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', $con_transaccion);
      	return $resultado[2]['valor'];
	}
	
	static public function aprobar_seleccion ($id_seleccion, $con_transaccion = true){
		$sql = "BEGIN :resultado := pkg_as_selecciones.aprobar_seleccion(:id_seleccion);END;";
		$parametros = array (array(  'nombre' => 'id_seleccion', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 12,
                                     'valor' => $id_seleccion),
      						 array(  'nombre' => 'resultado', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4000,
                                     'valor' => ''));
      	$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', $con_transaccion);
      	if (isset($resultado[1]['valor']))
      		return $resultado[1]['valor'];
      	else
      		return null;
	}
	
	static public function anular_seleccion ($id_seleccion, $con_transaccion = true){
		$sql = "BEGIN :resultado := pkg_as_selecciones.anular_seleccion(:id_seleccion);END;";
		$parametros = array (array(  'nombre' => 'id_seleccion', 
                                     'tipo_dato' => PDO::PARAM_INT,
                                     'longitud' => 12,
                                     'valor' => $id_seleccion),
      						 array(  'nombre' => 'resultado', 
                                     'tipo_dato' => PDO::PARAM_STR,
                                     'longitud' => 4000,
                                     'valor' => ''));
      	$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros,'','', $con_transaccion);
      	if (isset($resultado[1]['valor']))
      		return $resultado[1]['valor'];
      	else
      		return null;
	}
}

?>