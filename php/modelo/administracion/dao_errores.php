<?php

class dao_errores {

	static public function get_errores ($filtro = []){
		$where = "1=1";

		if (isset($filtro['mensaje'])){
			$where .=" and upper(kre.mensaje) like upper('%".$filtro['mensaje']."%')";
			unset($filtro['mensaje']);
		}

		if (isset($filtro['cod_error'])){
			$where .=" and upper(kre.cod_error) like upper('%".$filtro['cod_error']."%')";
			unset($filtro['cod_error']);
		}
		
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'kre', '1=1');

		$sql = "SELECT kre.cod_error, kre.mensaje, kre.tipo
				  FROM kr_errores kre
				 WHERE $where 
			  ORDER BY kre.cod_error ";
			
		return principal_ei_tabulator_consultar::todos_los_datos($sql);
	}

	static public function guardar ($operaciones)
	{
		$tabla = 'KR_ERRORES';
		$mensaje_error = 'Error al guardar';

		foreach ($operaciones as $operacion) {
			$error = $operacion['data'];
			$cod_error = quote($error['cod_error']);
			$mensaje = quote($error['mensaje']);

			$sql = "UPDATE $tabla
					SET mensaje = $mensaje
					WHERE
						cod_error = $cod_error;
				";
			toba::db()->ejecutar($sql);
		}
	}

}

?>