<?php
class dao_co_parametros {

	static public function get_parametros ($filtro = [])
	{
		$where = "1=1";

		if (isset($filtro['parametro'])){
			$where .=" and upper(p.parametro) like upper('%".$filtro['parametro']."%')";
			unset($filtro['parametro']);
		}

		if (isset($filtro['valor'])){
			$where .=" and upper(p.valor) like upper('%".$filtro['valor']."%')";
			unset($filtro['valor']);
		}
		
		$where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'p', '1=1');

		$sql = "SELECT p.parametro, p.valor
				  FROM co_parametros p 
				 WHERE $where 
			  ORDER BY p.parametro";
		return principal_ei_tabulator_consultar::todos_los_datos($sql);
	}

	static public function get_valor_parametro ($parametro)
	{
		$sql = "select valor 
				  from co_parametros 
				 where parametro = upper('".$parametro."')";
				 
		$datos = toba::db()->consultar_fila($sql);
		return $datos['valor'];
	}

	public static function guardar($operaciones)
	{
		$tabla = 'CO_PARAMETROS';
		$mensaje_error = 'Error al guardar';

		foreach ($operaciones as $operacion) {
			$parametro = $operacion['data'];
			

			$clave =
				isset($parametro['parametro'])
					? quote($parametro['parametro'])
					: null;

			if ($operacion['type'] === principal_ei_tabulator::TYPE_DELETE) {
				$sql = "
					DELETE FROM $tabla
					WHERE parametro = $clave
				";

				toba::db()->ejecutar($sql);

				continue;
			}

			$original = $operacion['original'];

			$ori_parametro = quote($original["parametro"]);
			$new_parametro = quote($parametro["parametro"]);
			$valor = quote($parametro["valor"]);

			$sql = $operacion['type'] === principal_ei_tabulator::TYPE_INSERT
				? "
					INSERT INTO $tabla (
						parametro, valor
					) VALUES (
						$new_parametro, $valor
					);
				"
				: "
					UPDATE $tabla
					SET
						parametro = $new_parametro,
						valor = $valor
					WHERE
						parametro = $ori_parametro;
				";

			toba::db()->ejecutar($sql);
		}
	}
}
?>