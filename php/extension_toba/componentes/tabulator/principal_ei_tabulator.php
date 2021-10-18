<?php
/**
 * Ofrece funcionalidades para integrar tabulator en un cuadro.
 *
 * @author lgraziani
 *
 * @see IntervanTabulator Es la clase JS de este componente.
 * @see principal/www/css/componentes/intervan-tabulator.css
 */
abstract class principal_ei_tabulator extends toba_ei_cuadro
{
	private $pagination_size;
	public $s__default_hegiht= '325px';
	private $is_pagination_remote;
	// Se resetea con cada petición
	// Flag para saber si ya se cargaron el JS y el CSS
	private static $cargado = false;

	// Flags de ABM
	private $not_insert;
	private $not_update;
	private $not_delete;
	private $tabla_nombre;
	private $claves_relacion;

	/**
	 * Este mapa asociativo contiene funciones que reciben un valor
	 * y lo transforman para que pueda se inyectado dentro del JS
	 * final de la clase.
	 *
	 * Se utiliza durante el parseo de la configuración. Se necesita
	 * para poder parsear funciones JS (en forma de strings de PHP)
	 * y booleanos desde PHP a JS.
	 *
	 * @var {Array<Array<string, any>>}
	 */
	private $config_parsers;

	/**
	 * @return {boolean} Verdadero si la paginación es remota.
	 */
	final public function tabulator__tiene_paginacion_remota()
	{
		return $this->is_pagination_remote;
	}

	/**
	 * @return {boolean} Verdadero si la paginación es local.
	 */
	final public function tabulator__tiene_paginacion_local()
	{
		return !$this->is_pagination_remote;
	}

	/**
	 * Hook de toba para configurar el componente antes de su
	 * inicialización.
	 *
	 * No permite reimplementación.
	 *
	 * @return {void}
	 */
	final public function pre_configurar()
	{
		parent::pre_configurar();

		// Configuración de la paginación
		$this->is_pagination_remote =
			$this->existe_paginado() && $this->get_tipo_paginado() === 'C';
		$this->pagination_size = $this->get_tamanio_pagina();

		// Configuración de ABM
		$abm_config = $this->tabulator__abm_config();
		$this->not_insert = !in_array('insertar', $abm_config['operaciones']);
		$this->not_update = !in_array('actualizar', $abm_config['operaciones']);
		$this->not_delete = !in_array('eliminar', $abm_config['operaciones']);
		$this->tabla_nombre = $abm_config['tabla_nombre'];

		/*
	     * Es un arreglo de funciones que parsean los datos
	     * de las configuraciones.
	     *
	     * Entre los datos pueden haber funciones, booleanos,
	     * números o strings.
	     *
	     * @type {<Array<Function>>}
	     */
		$this->config_parsers = [
			'string' => function ($value) {
				return json_encode(mb_convert_encoding($value, 'utf-8'));
			},
			'boolean' => function ($value) {
				return $value ? 'true' : 'false';
			},
			'default' => function ($value) {
				return isset($value) ? $value : "''";
			},
		];
		// Configuración de opciones de tipo función
		$this->config_parsers['cellClick'] = $this->config_parsers['default'];
		$this->config_parsers['editable'] = $this->config_parsers['default'];
		$this->config_parsers['formatter'] = $this->config_parsers['default'];
		$this->config_parsers['cellEditing'] = $this->config_parsers['default'];
		$this->config_parsers['cellEdited'] = $this->config_parsers['default'];
		$this->config_parsers['headerFilterFunc'] = $this->config_parsers['default'];
	}

	/**
	 * Reimplementa el método de generación del html de toba.
	 *
	 * No permite reimplementación.
	 */
	final public function generar_html()
	{
		$tag_hasher = new tag_hasher('principal');

		$id = $this->tabulator__id();
		$ini_debug = toba::instalacion()->es_produccion()
			? '' : "<!-- ***************** Inicio EI TABULATOR ($id) *********** -->";
		$fin_debug = toba::instalacion()->es_produccion()
			? '' : "<!-- ***************** Fin EI TABULATOR ($id) *********** -->";

		echo $ini_debug;

		echo $this->tabulator__generate_inline_events_inputs();

		////////////////////////////////////////////////////////
		// Este estilo se encarga de corregir la visualización
		// del cuadro de Tabulator.
		////////////////////////////////////////////////////////
		if (!self::$cargado) {
			$tag_hasher->css('js/dhtmlx/combo_terrace.min.css');
			$tag_hasher->css('css/tabulator.min.css');
			$tag_hasher->css('css/componentes/intervan-tabulator.css');
		}

		echo "<div id='$id' class='intervan-tabulator'>";

		$titulo = $this->get_titulo();

		if (!empty($titulo)) {
			echo "
				<div class='ei-barra-sup ei-barra-sup-sin-botonera'>
					<span class='ei-barra-sup-tit'>$titulo</span>
				</div>
			";
		}

		echo $this->tabulator__configure_abm_controls();

		echo "
				<div class='container'></div>
		";
		$this->generar_botones();
		echo '
			</div>
		';
		/////////////////////////////////////////////////////////////////////
		// Estas son las dependencias JS. Se requieren una única vez.
		// Esto hace segura la existencia de múltiples cuadros de este tipo.
		/////////////////////////////////////////////////////////////////////
		if (self::$cargado) {
			echo $fin_debug;

			return;
		}

		self::$cargado = true;

		///////////////////////////////////////////////
		// Dependencias de terceros
		///////////////////////////////////////////////
		// Esto se puede optimizar al requerir sólo
		// lo que se necesita
		$tag_hasher->js('js/jquery.inputmask.bundle.min.js', true);
		$tag_hasher->js('js/dhtmlx/core.min.js');
		$tag_hasher->js('js/dhtmlx/common.min.js');
		$tag_hasher->js('js/dhtmlx/combo.min.js');
		$tag_hasher->js('js/tabulator.min.js');

		/////////////////////
		// it-conf
		/////////////////////
		$it_folder = 'js/componentes/intervan-tabulator';
		$tag_hasher->js("$it_folder/helpers/it-ci-helper.js");
		$tag_hasher->js("$it_folder/it-core.js");
		$tag_hasher->js("$it_folder/extensiones/it-extensiones.js");
		$tag_hasher->js("$it_folder/extensiones/it-combo-editable.js");
		$tag_hasher->js("$it_folder/extensiones/it-blob-image.js");
		$tag_hasher->js("$it_folder/extensiones/it-fecha.js");
		$tag_hasher->js("$it_folder/extensiones/it-tick-cross.js");

		echo $fin_debug;
	}

	/**
	 * Reimplementa la extensión del objeto de Toba.
	 * Se encarga de configurar Tabulator del lado del cliente.
	 *
	 * No permite reimplementación.
	 *
	 * @return {void}
	 */
	final public function extender_objeto_js()
	{
		parent::extender_objeto_js();

		$tabulator_id = $this->tabulator__id();

		echo "
			$this->objeto_js.tabulator_instance = new IntervanTabulator(
				'$tabulator_id',
				{$this->tabulator__generate_config()},
				$this->objeto_js
			);
		";

		// FIXME Monkeypatch de tabulator para que corrija el formato del textarea.
		echo "
			setTimeout(() => {
				$this->objeto_js.tabulator_instance.domElement.tabulator('redraw');
			}, 100);
		";

		$this->extender_eventos($this->objeto_js);
	}

	/**
	 * Método para persistir los datos.
	 *
	 * @param {Array<Array<string, any>>} $operaciones
	 */
	final public function tabulator__persist($operaciones)
	{
		$class_name = get_class($this);

		return principal_ei_tabulator_persistidor::procesar(
			$operaciones,
			$this->tabulator__columns(),
			[
				'not_update' => $this->not_update,
				'not_insert' => $this->not_insert,
				'not_delete' => $this->not_delete,
				'class_name' => $class_name,
				'tabla_nombre' => $this->tabla_nombre,
				'columnas_clave' => $this->_columnas_clave,
			],
			$this
		);
	}

	/**
	 * Hook que reimplementa la instancia del cuadro.
	 */
	public function procesar_datos_pre_eliminar($original)
	{
		return array_merge($this->get_clave_relacion(), $original);
	}

	/**
	 * Hook que reimplementa la instancia del cuadro.
	 */
	public function procesar_datos_pre_insertar($data)
	{
		return array_merge($this->get_clave_relacion(), $data);
	}

	/**
	 * Hook que reimplementa la instancia del cuadro.
	 */
	public function procesar_datos_pre_actualizar($data, $original)
	{
		$original = array_merge($this->get_clave_relacion(), $original);

		return ['data' => $data, 'original' => $original];
	}

	public function after_guardar($operaciones, $metadatos, $config, $cuadro){
		return "OK";
	}

	/**
	 * Recupera los datos iniciales del cuadro cuando no tiene paginación
	 * o la paginación es local.
	 *
	 * @return {Array<Array<string, any>>} Arreglo asociativo de datos
	 */
	public function tabulator__get_datos()
	{
		$class_name = get_class($this);

		throw new toba_error_def("La paginación de la tabla $class_name es local o no tiene. Por favor implemente el método `public function tabulator__get_datos()`.");
	}

	/**
	 * Recupera el total de páginas cuando la configuración de la tabla
	 * es paginación remota.
	 *
	 * @param array $filtros filtros
	 *
	 * @return number Devuelve la cantidad de páginas.
	 */
	public function tabulator__get_total_paginas($filtros)
	{
		$class_name = get_class($this);

		throw new toba_error_def("La paginación de la tabla <b>$class_name</b> es remota. Por favor implemente los métodos:<br><br>public function tabulator__get_total_paginas(\$filtros)<br>public function tabulator__get_pagina(\$pagina, \$filtros, \$ordenamientos)");
	}

	/**
	 * Recupera los datos de una página cuando la configuración de la tabla
	 * es paginación remota.
	 *
	 * @param number $pagina Número de página a recuperar.
	 * @param array $filtros       filtros
	 * @param array $ordenamientos ordenamientos
	 *
	 * @return array Devuelve los datos de la nueva página.
	 */
	public function tabulator__get_pagina($pagina, $filtros, $ordenamientos)
	{
		$class_name = get_class($this);

		throw new toba_error_def("La paginación de la tabla <b>$class_name</b> es remota. Por favor implemente los métodos:<br><br>public function tabulator__get_total_paginas(\$filtros)<br>public function tabulator__get_pagina(\$pagina, \$filtros, \$ordenamientos)");
	}

	/**
	 * Se utiliza exclusivamente durante la configuración
	 * de las columnas con el fin de configurar si las celdas
	 * se pueden o no editar.
	 *
	 * @see principal_ei_tabulator_column#26
	 */
	final public function tabulator__no_permite_actualizacion()
	{
		return $this->not_update;
	}

	/**
	 * A diferencia de la implementación de Toba, ésta chequea contra las
	 * restricciones funcionales ANTES de renderizar los botones en la vista.
	 * De esta manera se asegura que los botones nunca estén.
	 *
	 * @param string $clase
	 * @param string $extra
	 */
	final public function generar_botones($clase = '', $extra = '')
	{
		$eventos_abm = ['insertar', 'actualizar', 'eliminar'];
		$no_visibles = toba::perfil_funcional()->get_rf_eventos_no_visibles();
		$eventos_activos = array_filter(
			$this->_eventos_usuario_utilizados,
			function (toba_evento_usuario $evento) use ($eventos_abm, $no_visibles) {
				return !in_array($evento->get_id(), $eventos_abm) &&
					!in_array($evento->get_id_metadato(), $no_visibles) &&
					$evento->esta_en_botonera();
			}
		);

		if (!sizeof($eventos_activos)) {
			return;
		}
		echo "<div class='ei-botonera $clase'>";

		array_walk($eventos_activos, function ($evento) {
			if (in_array($evento->get_id(), $this->_botones_graficados_ad_hoc)) {
				return;
			}

			$this->generar_boton($evento->get_id());
		});

		echo '</div>';
	}

	public function tabulator__validar($operaciones)
	{
	}

	final protected function get_clave_relacion()
	{
		if (isset($this->claves_relacion)) {
			return $this->claves_relacion;
		}
		$claves_relaciones = $this->controlador()->get_clave_relacion();

		$this->claves_relacion = array_reduce(
			$this->_columnas_clave,
			function ($partial, $columna) use ($claves_relaciones) {
				if (isset($claves_relaciones[$columna])) {
					$partial[$columna] = $claves_relaciones[$columna];
				}

				return $partial;
			},
			[]
		);

		return $this->claves_relacion;
	}

	/**
	 * Reimplementación del método de la clase toba_ei_cuadro:753.
	 *
	 * Esto se debe a que con esta clase nunca se cargan los datos del cuadro
	 * directamente en la instancia de toba_ei_cuadro, sino que se envían al
	 * cliente sin otra configuración por parte del servidor.
	 *
	 * Cuando se intenta invocar un evento en línea con los registros, Toba por
	 * defecto intenta cargar la fila asociada a la clave enviada por el cliente.
	 * Sin embargo falla porque no hay datos cargados.
	 */
	final protected function cargar_seleccion()
	{
		// La seleccion se actualiza cuando el cliente lo pide explicitamente
		if (empty($_POST[$this->_submit_seleccion])) {
			return;
		}
		$this->_clave_seleccionada =
			ctr_funciones_basicas::ansi_data_converter(
				json_decode(
					mb_convert_encoding(
						$_POST[$this->_submit_seleccion],
						'utf-8'
					),
					true
				)
			);
	}

	/**
	 * Esta función es obligatoria para que tabulator funcione.
	 * Se encarga de definir la información de cada columna, como
	 * el título, el campo, entre otros.
	 *
	 * Para configurar una columna, se recomienda utilizar la clase
	 * principal_ei_tabulator_column
	 *
	 * @see principal_ei_tabulator_column
	 * @see http://tabulator.info/docs/#options
	 *
	 * @return array Los metadatos que describen las columnas
	 */
	abstract protected function tabulator__columns();

	/**
	 * Devuelve el nombre de la tabla en la que
	 * se aplican las operaciones de ABM.
	 *
	 * @return string El nombre de la tabla
	 */
	abstract protected function tabulator__tabla_nombre();

	/**
	 * Permite reimplementar las funciones de los eventos
	 * del lado del cliente.
	 *
	 * @param objeto_js $cuadro Instancia JS del cuadro
	 */
	protected function extender_eventos($cuadro)
	{
	}

	/**
	 * Se utiliza durante la instanciación del cuadro.
	 * Debe devolver un arreglo de arreglos asociativos.
	 * Cada arreglo asociativo está formado por dos claves:
	 * 	column: nombre de la columna.
	 * 	dir: 'asc' | 'desc'.
	 *
	 * @example
	 * 	[
	 * 		[
	 * 			'column' => 'id',
	 * 			'dir' => 'desc',
	 * 		],
	 * 		[
	 * 			'column' => 'nombre',
	 * 			'dir' => 'asc',
	 * 		],
	 * 	]
	 *
	 * @see http://tabulator.info/docs/#options
	 *
	 * @return {Array<['column' => string, 'dir' => 'asc' | 'desc']>}
	 */
	protected function tabulator__initial_sort()
	{
		return [];
	}

	/**
	 * Devuelve la configuración del layout.
	 * Los valores aceptados son 'fitData' | 'fitDataFill' | 'fitColumns'.
	 *
	 * @return {string}
	 */
	protected function tabulator__layout()
	{
		return 'fitDataFill';
	}

	/**
	 * Devuelve un arreglo con las operaciones de persistencia
	 * permitida según el estado de los datos.
	 * A diferencia de las restricciones funcionales, éstas
	 * están dadas por datos asociados a los datos que se manipulan.
	 *
	 * @return array
	 */
	protected function tabulator__abm_pre_config()
	{
		return [
			'insertar' => false,
			'actualizar' => false,
			'eliminar' => false,
		];
	}

	/**
	 * Permite determinar si hay operaciones ABM, y cuáles se aceptarán.
	 * Aplica restricciones funcionales sobre los eventos.
	 *
	 * @return array La configuración.
	 */
	final private function tabulator__abm_config()
	{
		$eventos_abm = $this->tabulator__abm_pre_config();
		$no_visibles = toba::perfil_funcional()->get_rf_eventos_no_visibles();

		$eventos_activos = array_filter(
			$this->_info_eventos,
			function ($evento) use ($eventos_abm, $no_visibles) {
				return array_key_exists($evento['identificador'], $eventos_abm)
					&& (
						!in_array($evento['evento_id'], $no_visibles)
						|| $eventos_abm[$evento['identificador']]
					);
			});
		$eventos_activos = array_reduce(
			$eventos_activos,
			function ($partial, $evento) {
				array_push($partial, $evento['identificador']);

				return $partial;
			},
			[]
		);

		$abm_config = [
			'tabla_nombre' => $this->tabulator__tabla_nombre(),
			'operaciones' => array_filter(
				array_keys($eventos_abm),
				function ($evento) use ($eventos_activos) {
					return in_array($evento, $eventos_activos);
				}
			),
		];
		
		return $abm_config;
	}

	public function tabulator_get_hight(){
		return $this->s__default_hegiht;
	}

	public function tabulator_set_hight($height){
		$this->s__default_hegiht= $height;
	}	
	

	/**
	 * Devuelve un arreglo asociativo con la configuración de la paginación.
	 *
	 * @see http://tabulator.info/docs/#options
	 *
	 * @return {Array<string, any>} Pagination configuration
	 */
	final private function tabulator__pagination()
	{
		// Si no hay paginado, defino una altura fija de 400px,
		// con un buffer virtual de 200px
		// y con todos los datos
		if (!$this->existe_paginado()) {
			return [
				'height' => $this->tabulator_get_hight(),
				'virtualDomBuffer' => 200,
				'data' => $this->tabulator__get_datos(),
			];
		}
		// Si existe la paginación, configuro todo según el tipo.
		$config = [
			'paginationSize' => $this->pagination_size,
			'pagination' => $this->is_pagination_remote ? 'remote' : 'local',
		];

		if (!$this->is_pagination_remote) {
			$config['data'] = $this->tabulator__get_datos();
		}

		return $config;
	}

	/**
	 * Genera un id que permite sabe que
	 * hace referencia a esta librería.
	 *
	 * @return {string} ID específico para tabulator.
	 */
	final private function tabulator__id()
	{
		return "ei_tabulator_{$this->get_id()[1]}";
	}

	/**
	 * Genera toda la configuración de Tabulator.
	 *
	 * @return {string} Configuración serializada como JSON.
	 */
	final private function tabulator__generate_config()
	{
		$options = [
			'layout' => $this->tabulator__layout(),
			'initialSort' => $this->tabulator__initial_sort(),
			'columns' => $this->tabulator__columns(),
		];
		$options += $this->tabulator__pagination();

		// Configura la columna de eventos en línea, en caso de que hayan.
		$options = $this->tabulator__set_events_column($options);

		// Genero el objeto JS serializado y elimino la última coma.
		return substr($this->tabulator__encode_config($options), 0, -1);
	}

	/**
	 * Agrega la columna de eventos en línea con los registros
	 * en caso de existir algún evento de este tipo.
	 *
	 * @param {Array<Array<string, any>>} $options
	 * 	Opciones de configuración del cuadro.
	 *
	 * @return {Array<Array<string, any>>}
	 * 	Opciones con o sin la columna de eventos en línea.
	 */
	final private function tabulator__set_events_column($options)
	{
		$no_visibles = toba::perfil_funcional()->get_rf_eventos_no_visibles();
		// Recupero todos los eventos en línea y
		// me quedo con los eventos activos
		$eventos = array_filter(
			$this->get_eventos_sobre_fila(),
			function (toba_evento_usuario $evento) use ($no_visibles) {
				return !in_array($evento->get_id_metadato(), $no_visibles);
			}
		);

		if (sizeof($eventos) === 0) {
			return $options;
		}

		// Reduzco el arreglo unidimensional de claves del arreglo asociativo
		// de los eventos.
		// Por cada uno de los eventos, genero el HTML del botón.
		$eventos = array_reduce(
			// Recorro el arreglo de claves.
			array_keys($eventos),
			function ($total, $evento) use ($eventos) {
				$tip = $eventos[$evento]->get_msg_ayuda();
				$tip = empty($tip) ? '' : "title='$tip'";

				return "$total<button type='button' $tip onclick=\"IntervanTabulatorHelpers.inlineEvt($this->objeto_js, '\${id}', '$evento')\">{$eventos[$evento]->get_imagen()}</button>";
			},
			''
		);

		// Configuro la última columna del cuadro como columna de eventos en línea.
		$columna_eventos = [
			'field' => 'inline_evts',
			'frozen' => true,
			'headerSort' => false,
			'cssClass' => 'inline-event',
			'formatter' => "cell => {
				if (IntervanTabulator.isANewRow(cell)) {
					return;
				}
				const id = cell.getRow().getIndex();

				return `$eventos`;
			}",
		];

		// Pusheo la columna de eventos al final del arreglo de columnas
		array_push($options['columns'], $columna_eventos);

		// Devuelvo las nuevas opciones
		return $options;
	}

	/**
	 * Genera los hidden inputs para manipular eventos en línea.
	 *
	 * @return {string} El HTML de los inputs
	 */
	final private function tabulator__generate_inline_events_inputs()
	{
		$id = $this->get_id_form();

		return "
			<input name='$id' id='$id' type='hidden'>
			<input name='{$id}__seleccion' id='{$id}__seleccion' type='hidden'>
		";
	}

	// TODO: Refactorizar.
	// Recorro un arreglo asociativo.
	// Por cada atributo me fijo si:
	//  1. es un arreglo,
	//  2. si es areglo asociativo,
	//  3. está el nombre de la clave,
	//  4. está el tipo de dato,
	//  5. no existe, y uso el por defecto.
	final private function tabulator__encode_config($options, $key = null)
	{
		$es_arreglo_secuencial = ctr_funciones_basicas::es_arreglo_secuencial($options);
		$opciones_edicion = ['editable', 'editor', 'editorParams'];

		// Primera prioridad, es un arreglo secuencial.
		if ($es_arreglo_secuencial) {
			$array = '[';

			$array .= array_reduce($options, function ($carry, $option) {
				return $carry.$this->tabulator__encode_config($option);
			});

			// Elimino la última coma
			if (substr($array, -1, 1) === ',') {
				$array = substr($array, 0, -1);
			}

			$array .= ']';

			return $array;
		}

		// Segunda prioridad, es arreglo asociativo.
		if (is_bool($es_arreglo_secuencial)) {
			$child = '{';

			// NOTA: No se puede utilizar json_encode porque
			// La codificación de caracteres que usa toba y
			// la DB es ANSI y json_encode sólo acepta UTF-8.
			foreach ($options as $key => $option) {
				// Este check evita mandar al cliente la configuración
				// de edición de las columnas en caso de que
				if (
					in_array($key, $opciones_edicion)
					&& $this->not_insert
					&& $this->not_update
				) {
					continue;
				}
				$value = $this->tabulator__encode_config($option, $key);
				$child .= "'$key':$value";

				if (substr($child, -1, 1) !== ',') {
					$child .= ',';
				}
				// Preconfiguro el ancho del combo editable
				// TODO Refactorizar fuera de esta función. Para eso hay
				// que habilitar hooks.
				if (strpos($value, 'combo_editable')) {
					$child .= 'minWidth:400,';
				}
			}

			$child .= '},';

			return $child;
		}

		// Tercera prioridad, parser con nombre de la clave.
		if (isset($this->config_parsers[$key])) {
			return $this->config_parsers[$key]($options).',';
		}

		// Cuarta prioridad, parser de tipo de dato.
		if (isset($this->config_parsers[gettype($options)])) {
			return $this->config_parsers[gettype($options)]($options).',';
		}

		// Quinta prioridad, no existe por lo que uso el por defecto.
		return $this->config_parsers['default']($options).',';
	}

	/**
	 * Determina qué controles debe mostrar según la configuración
	 * definida por el método `tabulator__abm_config` implementado
	 * en la clase hija.
	 *
	 * @return string
	 */
	final private function tabulator__configure_abm_controls()
	{
		// Si está todo en falso no se define ningún botón
		if ($this->not_insert && $this->not_update && $this->not_delete) {
			return "
				<div class='controls'>
					<div class='right'>
						<button type='button' class='requery-data'>Reconsultar datos</button>
					</div>
				</div>
			";
		}

		// Si el evento de actualización es verdadero, agrego el botón de insertar
		$insert_button = $this->not_insert ? ''
			: "<button type='button' class='add-row'>Agregar fila</button>";

		// Si el evento de eliminación es verdadero, agrego el botón de eliminar
		$delete_button = $this->not_delete ? ''
			: "<button type='button' class='delete'>Eliminar filas seleccionadas</button>
				<span style='font-size: 10px;'>Para seleccionar una fila mantenga apretada la tecla Shift y cliquee una o varias filas.</span>
		";

		return "
				<div class='controls'>
					<div class='left'>
						$insert_button
						<button type='button' class='rollback'>Deshacer cambios</button>
						$delete_button
					</div>
					<div class='right'>
						<button type='button' class='requery-data'>Reconsultar datos</button>
						<button type='button' class='persist'>Guardar datos</button>
					</div>
				</div>
		";
	}
}
