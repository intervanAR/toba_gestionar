<?php
/**
 * @author lgraziani
 */
final class Paginador
{
	private $pdo;
	private $select;
	private $columns;
	private $joins;
	private $where;
	private $params = [];
	private $orders;

	public function __construct($joins)
	{
		if (empty($joins)) {
			throw new toba_error('[Paginador] Debe haber al menos una tabla.');
		}
		$this->pdo = toba::db()->get_pdo();

		$join = array_shift($joins);
		$this->joins = array_reduce($joins, function($sql, $join) {
			return "$sql,\n	$join";
		}, "	$join");

		return $this;
	}

	public function where($filters)
	{
		if (empty($filters)) {
			throw new toba_error('[Paginador] Debe haber al menos un filtro.');
		}
		$filter = array_shift($filters);
		$this->where = array_reduce($filters, function($sql, $filter) {
				return "$sql\n	AND $filter";
		}, "	$filter");

		return $this;
	}

	public function params($params)
	{
		if (empty($params)) {
			throw new toba_error('[Paginador] Debe haber al menos un parámetro.');
		}
		$this->params = $params;

		return $this;
	}

	public function columns($columns)
	{
		if (empty($columns)) {
			throw new toba_error('[Paginador] Debe haber al menos una columna para procesar.');
		}
		$this->columns = $this->processColumns($columns);

		return $this;
	}

	public function select($columns)
	{
		if (empty($columns)) {
			throw new toba_error('[Paginador] Debe haber al menos una columna para mostrar.');
		}
		$this->select = $this->processColumns($columns);

		return $this;
	}

	public function orders($orders)
	{
		if (empty($orders)) {
			throw new toba_error('[Paginador] Debe haber al menos un ordenamiento.');
		}
		$order = array_shift($orders);
		$direction = $order['sentido'] === 'asc' ? 'asc' : 'desc';
		$this->orders = array_reduce($orders, function($sql, $order) {
			$direction = $order['sentido'] === 'asc' ? 'asc' : 'desc';

			return "$sql,\n	{$order['columna']} $direction";
		}, "	{$order['columna']} $direction");

		return $this;
	}

	public function paginate($filters)
	{
		$params = $this->params;
		$where = $this->where;

		if (isset($filters['numrow_desde'])) {
			$from = $filters['numrow_desde'] == 1
				? 1
				: intval($filters['numrow_desde']) + 1;
			$to = $filters['numrow_hasta'];

			array_push($params, [
				'name' => ':from_row',
				'value' => $from,
			]);
			array_push($params, [
				'name' => ':to_row',
				'value' => $to,
			]);
			unset($filters['numrow_desde']);
			unset($filters['numrow_hasta']);
		} else {
			array_push($params, [
				'name' => ':from_row',
				'type' => PDO::PARAM_NULL,
			]);
			array_push($params, [
				'name' => ':to_row',
				'type' => PDO::PARAM_NULL,
			]);
		}

		foreach ($filters as $filter => $value) {
			if (is_null($value)) {
				continue;
			}
			$where .= "\n	AND $filter = :$filter";

			array_push($params, [
				'name' => ":$filter",
				'value' => $value,
			]);
		}

		$sql = "
			SELECT
				$this->select
			FROM (
				SELECT ROWNUM nrow, tab.*
				FROM (
					SELECT
						$this->columns
					FROM
						$this->joins
					WHERE
						$where
					ORDER BY
						$this->orders
				) tab
			) tab
			WHERE
				:from_row IS NULL
				OR nrow BETWEEN :from_row AND :to_row
		";

		toba::logger()->debug('================= Paginador START  =================');
		toba::logger()->debug(ctr_procedimientos::sanitizar_consulta($sql));
		toba::logger()->debug($params);
		toba::logger()->debug('================= Paginador FINISH =================');

		$sent = $this->pdo->prepare($sql);

		array_walk($params, function($param) use(&$sent) {
			$sent->bindParam(
				$param['name'],
				$param['value'],
				isset($param['type']) ? $param['type'] : PDO::PARAM_STR
			);
		});

		$sent->execute();

		return $sent->fetchAll(PDO::FETCH_ASSOC);
	}

	private function processColumns($columns)
	{
		$column = array_shift($columns);

		return array_reduce($columns, function($sql, $column) {
			return "$sql,\n	$column";
		}, "	$column");
	}
}
