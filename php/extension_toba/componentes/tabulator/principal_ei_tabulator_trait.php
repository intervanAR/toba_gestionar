<?php
/**
 * Mixin que se encarga de inyectar en un CI los métodos necesarios
 * para que pueda procesar peticiones ajax para las lov.
 *
 * @author lgraziani
 */
trait principal_ei_tabulator_trait
{
	/**
	 * Tabulator utiliza internamente esta función para recuperar
	 * los datos del cuadro padre antes de persistir, de manera
	 * que pueda actualizar automáticamente los cuadros hijos.
	 *
	 * @return array
	 */
	final public function get_clave_relacion()
	{
		if (isset($this->s__seleccion)) {
			return $this->s__seleccion;
		}
		$ci_abuelo = $this->controlador();

		if (get_class($ci_abuelo) === get_class($this)) {
			return [];
		}
		if (method_exists($ci_abuelo, 'get_clave_relacion')) {
			return $ci_abuelo->get_clave_relacion();
		}

		return [];
	}

	final public function ajax__tabulator__persist(
		$dep,
		toba_ajax_respuesta $respuesta
	) {
		try {
			$data = isset($_POST) ? $_POST : [];

			if (empty($data['operations'])) {
				throw new toba_error('No se recibieron datos para actualizar');
			}
			$filtros = isset($_POST['filters']) ? $_POST['filters'] : [];
			$ordenamientos = isset($_POST['sorters']) ? $_POST['sorters'] : [];

			$cuadro = $this->dep($dep);

			$info = $cuadro->tabulator__persist($data['operations']);
			$datos = $cuadro->tabulator__tiene_paginacion_local()
				? $cuadro->tabulator__get_datos()
				: $cuadro->tabulator__get_pagina(
					intval($data['page_no']),
					$filtros,
					$ordenamientos
				);

			$respuesta->set([
				'status' => 200,
				'data' => $datos,
				'info' => $info,
			]);
		} catch (toba_error $error) {
			toba::logger()->error($error);

			$respuesta->set([
				'status' => 500,
				'error' => $error->get_mensaje(),
			]);
		}
	}

	final public function ajax__tabulator__requery_all(
		$dep,
		toba_ajax_respuesta $respuesta
	) {
		try {
			$cuadro = $this->dep($dep);

			$respuesta->set([
				'status' => 200,
				'data' => $cuadro->tabulator__get_datos(),
			]);
		} catch (toba_error $error) {
			toba::logger()->error($error);

			$respuesta->set([
				'status' => 500,
				'error' => $error->get_mensaje(),
			]);
		}
	}

	final public function ajax__tabulator__get_pagina(
		$dep,
		toba_ajax_respuesta $respuesta
	) {
		$pagina = intval($_POST['page']);
		$filtros = isset($_POST['filters']) ? $_POST['filters'] : [];
		$ordenamientos = isset($_POST['sorters']) ? $_POST['sorters'] : [];

		try {
			$cuadro = $this->dep($dep);
			$last_page = intval($cuadro->tabulator__get_total_paginas($filtros));
			$last_page = $last_page ? $last_page : 1;

			$respuesta->set([
				'status' => 200,
				'last_page' => $last_page,
				'data' => $cuadro->tabulator__get_pagina(
					$pagina,
					$filtros,
					$ordenamientos
				),
			]);
		} catch (toba_error $error) {
			toba::logger()->error($error);

			$respuesta->set([
				'status' => 500,
				'error' => $error->get_mensaje(),
			]);
		}
	}

	/**
	 * Función interna que se encarga de atrapar los pedidos de lov
	 * de la clase JS IntervanTabulator.
	 *
	 * @param string $metodo Nombre del método lov del CI.
	 * @param toba_ajax_respuesta $respuesta objeto Toba
	 */
	final public function ajax__tabulator__get_lov(
		$metodo,
		toba_ajax_respuesta $respuesta
	) {
		try {
			$mask = $_GET['mask'];
			$metodo = "lov__$metodo";
			$parents = isset($_GET['parents']) ? $_GET['parents'] : '{}';
			$parents = json_decode($parents, true);

			$options = $this->tabulator__map_lov_data(
				$this->$metodo($mask, $parents),
				$metodo
			);

			$respuesta->set([
				'status' => 200,
				'options' => $options,
			]);
		} catch (toba_error $error) {
			toba::logger()->error($error);

			$respuesta->set([
				'status' => 500,
				'error' => $error->get_mensaje(),
			]);
		}
	}

	/**
	 * Mapea un arreglo de datos a la estructura que utiliza
	 * IntervanTabulator en el cliente. Se encarga de que haya
	 * compatibilidad hacia atrás con las funciones de los DAO.
	 *
	 * Esta función supone que la estructura del arreglo asociativo
	 * tiene como primer atributo a la clave y como segundo atributo
	 * la descripción.
	 *
	 * @param array $data datos en crudo devueltos por el DAO
	 * @param string $method Nombre del método del CI que recupera los datos.
	 *
	 * @return array datos limpios
	 */
	final private function tabulator__map_lov_data($data, $method)
	{
		if (is_null($data)) {
			return [[
				'value' => null,
				'text' => 'La búsqueda requiere que ingrese más caracteres.',
			]];
		}
		if (empty($data)) {
			return [[
				'value' => null,
				'text' => 'No existen resultados para ese filtro',
			]];
		}

		// No necesita ningún mapeo
		if (isset($data[0]['value']) && isset($data[0]['text'])) {
			return $data;
		}

		$class_name = get_class($this);

		toba::logger()->warning(
			"[DEPRECADO] $class_name->$method invoca un método de un DAO que devuelve una estructura de datos deprecada. Ver documentación de IntervanTabulator para saber cómo es la nueva estructura de datos para las lovs."
		);

		$data = array_map(function ($elem) {
			$props = array_keys($elem);

			return [
				'value' => $elem[$props[0]],
				'text' => $elem[$props[1]],
			];
		}, $data);

		return $data;
	}
}
