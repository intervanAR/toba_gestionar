<?php
/**
 * Todos los métodos deben ser chain methods.
 *
 * @author lgraziani
 */
class principal_ei_tabulator_column
{
	private $data;

	private function __construct($field, $title)
	{
		$this->data = [
			'title' => $title,
			'field' => $field,
		];
	}

	public static function crear($field, $title)
	{
		return new self($field, $title);
	}

	public function build(principal_ei_tabulator $cuadro)
	{
		if ($cuadro->tabulator__no_permite_actualizacion()) {
			$this->data['editable'] = /* javascript */'IntervanTabulator.isANewRow';
		} elseif (isset($this->data['editable'])) {
			$this->data['editable'] = implode('', $this->data['editable']);
			$this->data['editable'] = /* javascript */"
				cell => {
					{$this->data['editable']}

					return true;
				}
			";
		}

		return $this->data;
	}

	public function obligatorio()
	{
		if (!isset($this->data['validator'])) {
			$this->data['validator'] = [];
		}
		array_push($this->data['validator'], 'required');

		$this->data['title'] = "{$this->data['title']} *";

		return $this;
	}

	public function fija()
	{
		$this->data['frozen'] = true;

		return $this;
	}

	public function ancho($ancho)
	{
		$this->data['width'] = $ancho;

		return $this;
	}

	public function editor_por_defecto()
	{
		toba::logger()->warning(
			'[DEPRECADO] Considere reemplazar la llamada a `editor_por_defecto` por la función `editor`. Para más información acceder a http://10.1.1.20/documentacion.'
		);

		return $this->editor();
	}

	public function editor($tipo = true)
	{
		$this->data['editor'] = $tipo;

		return $this;
	}

	public function fecha($params = [])
	{
		$this->data['formatter'] = "'fecha'";
		$this->data['sorter'] = 'date';

		if (isset($params['tipo']) && $params['tipo'] === 'datetime') {
			$this->data['editorParams'] = ['mask' => 'datetime'];
		}
		if (!isset($this->data['validator'])) {
			$this->data['validator'] = [];
		}
		// TODO: validar si el parámetro que se recibe
		// es una columna válida.
		// Validar si es una fecha válida.
		// Si ninguno de los dos cumple, lanzar error.
		if (isset($params['menor_que'])) {
			// Verifica si el contenido hace referencia a otro atributo
			if (isset($params['menor_que']['clave'])) {
				if ($this->data['field'] === $params['menor_que']['clave']) {
					throw new toba_error("El campo `{$this->data['field']}`no puede comparar si es menor o igual a sí mismo.");
				}
				array_push(
					$this->data['validator'],
					"fecha_menor_que:{$params['menor_que']['clave']}@{$params['menor_que']['descripcion']}"
				);
			// Sino, el contenido es una fecha
			} else {
				array_push(
					$this->data['validator'],
					"fecha_menor_que:{$params['menor_que']}"
				);
			}
		}

		if (isset($params['mayor_que'])) {
			// Verifica si el contenido hace referencia a otro atributo
			if (isset($params['mayor_que']['clave'])) {
				if ($this->data['field'] === $params['mayor_que']['clave']) {
					throw new toba_error("El campo `{$this->data['field']}`no puede comparar si es mayor o igual a sí mismo.");
				}
				array_push(
					$this->data['validator'],
					"fecha_mayor_que:{$params['mayor_que']['clave']}@{$params['mayor_que']['descripcion']}"
				);
			// Sino, el contenido es una fecha
			} else {
				array_push(
					$this->data['validator'],
					"fecha_mayor_que:{$params['mayor_que']}"
				);
			}
		}

		return $this;
	}

	public function entero($params = [])
	{
		if (!isset($this->data['validator'])) {
			$this->data['validator'] = [];
		}
		array_push($this->data['validator'], 'integer');

		$this->number_validations($params);

		return $this;
	}

	public function porcentaje()
	{
		return $this->decimal(['min' => 0, 'max' => 100]);
	}

	public function moneda($params = [])
	{
		$this->data['formatter'] = "'money'";
		$this->data['formatterParams'] = [
			'decimal' => ',',
			'thousand' => '.',
			'symbol' => '$',
		];
		$this->data['align'] = 'right';

		if (!isset($this->data['validator'])) {
			$this->data['validator'] = [];
		}
		array_push($this->data['validator'], 'numeric');

		$this->number_validations($params);

		return $this;
	}

	public function decimal($params = [])
	{
		$this->data['align'] = 'right';

		if (!isset($this->data['validator'])) {
			$this->data['validator'] = [];
		}
		array_push($this->data['validator'], 'numeric');

		$this->number_validations($params);

		return $this;
	}

	public function editor_solo_insert()
	{
		if (!isset($this->data['editable'])) {
			$this->data['editable'] = [];
		}

		array_push($this->data['editable'], /* javascript */'
			if (!IntervanTabulator.editableOnAddOnly(cell)) {
				return false;
			}
		');

		return $this;
	}

	public function combo_editable($metodo_lov)
	{
		if (!isset($this->data['editorParams'])) {
			$this->data['editorParams'] = [];
		}
		if (!isset($this->data['validator'])) {
			$this->data['validator'] = [];
		}
		$this->data['formatter'] = "'combo_editable'";
		$this->data['sorter'] = 'combo_editable';
		$this->data['headerFilterFunc'] = "'combo_editable'";
		$this->data['editorParams']['metodo_lov'] = $metodo_lov;

		array_push($this->data['validator'], 'combo_editable');

		return $this;
	}

	public function link()
	{
		$this->data['formatterParams'] = [
			'labelField' => $this->data['field'],
		];
		$this->data['formatter'] = "'link'";

		return $this;
	}

	public function tick_cross()
	{
		$this->data['formatter'] = "'tickCross'";
		$this->data['align'] = 'center';

		return $this;
	}

	public function textarea()
	{
		$this->data['formatter'] = "'textarea'";

		return $this;
	}

	public function blobImage($width = null)
	{
		$this->data['formatter'] = "'blobImage'";
		$this->data['variableHeight'] = true;
		$this->data['blob'] = true;

		if (!empty($width)) {
			$this->data['formatterParams'] = [
				'width' => $width,
			];
		}

		return $this;
	}

	public function wysiwyg()
	{
		// TODO Refactorizar los tooltip con algo como esto: https://codepen.io/cbracco/pen/qzukg
		$this->data['formatter'] = "'textarea'";
		$this->data['headerTooltip'] =
			$this->data['tooltip'] = 'Cómo dar formarto al texto:

- <p></p>: contiene un párrafo. Ejemplo:
    <p>Esto es el contenido de un párrafo</p>
- <br/>: salto de línea. Ejemplo
    <p>Esto es el contenido de <br/>un párrafo con salto del línea</p>
- <b></b>: pone negrita al texto que contiene. Ejemplo:
    <p>Esto es el <b>contenido en negrita</b> de un párrafo</p>
- <i></i>: pone en cursiva al texto que contiene. Ejemplo:
    <p>Esto es el <i>contenido en cursiva</i> de un párrafo</p>
- <font></font>: configura el tipo y el tamaño de la fuente del texto contenido.
Atributos: (1) size: tamaño de la fuente, (2) color: color de la fuente, (3) face: nombre del tipo de fuente. Ejemplo:
    <p><font size="44" color="red" face="Verdana">Este texto es enorme, en rojo y con fuente Verdana</font>, este otro no</p>
- <ul></ul>: contiene una lista sin orden. Ejemplo:
    <ul>
      <li>Primer elemento de la lista sin orden.</li>
      <li>Segundo elemento de la lista sin orden.</li>
    </ul>
- <ol></ol>: contiene una lista ordenada. Ejemplo:
    <ol>
      <li>Primer elemento de la lista ordenada.</li>
      <li>Segundo elemento de la lista ordenada.</li>
    </ol>';
		$this->data['clob'] = true;

		return $this;
	}

	/**
	 * Configura la dependencia en cascada como hijo de otros campos.
	 *
	 * @param array $padres lista de los campos padres
	 *
	 * @return principal_ei_tabulator_column chain method
	 */
	public function cascada_padres($padres, $config = [])
	{
		if (!isset($this->data['editorParams'])) {
			$this->data['editorParams'] = [];
		}
		if (!isset($this->data['editable'])) {
			$this->data['editable'] = [];
		}
		$this->data['editorParams']['parents'] = $padres;

		array_push($this->data['editable'], /* javascript */'
			if (!IntervanTabulator.extensionesHelpers.cascadaIsEditable(cell)) {
				return false;
			}
		');

		if (isset($config['editable'])) {
			array_push($this->data['editable'], /* javascript */"
				if (!({$config['editable']})(cell)) {
					return false;
				}
			");
		}

		return $this;
	}

	public function cascada_hijos($hijos, $config = [])
	{
		$hijos = array_map(function ($hijo) {
			return "'$hijo'";
		}, $hijos);

		$hijos = implode(',', $hijos);

		if (!isset($config['cellEdited'])) {
			$this->data['cellEdited'] = /* javascript */"
				IntervanTabulator.extensionesHelpers.resetearCascada([$hijos])
			";
		} else {
			$this->data['cellEdited'] = /* javascript */"cell => {
				IntervanTabulator.extensionesHelpers.resetearCascada([$hijos])(cell);
				({$config['cellEdited']})(cell);
			}";
		}

		return $this;
	}

	public function filtro($editor = true)
	{
		$this->data['headerFilter'] = $editor;

		if (isset($this->data['editorParams'])) {
			$this->data['headerFilterParams'] = array_merge(
				[null => 'Sin filtro'],
				$this->data['editorParams']
			);
		}

		return $this;
	}

	public function filtro_params($params)
	{
		if (!isset($this->data['headerFilter'])) {
			throw new toba_error('Antes de configurar los parámetros del filtro, primero configure el filtro.');
		}
		$this->data['headerFilterParams'] = $params;

		return $this;
	}

	public function ordenacion($conf = [])
	{
		if (isset($conf['activo'])) {
			if (!is_bool($conf['activo'])) {
				throw new toba_error('El atributo `activo` debe ser boolean');
			}
			if (!$conf['activo']) {
				$this->data['headerSorter'] = false;
			}
		}
		if (isset($conf['orden']) && !isset($this->data['headerSorter'])) {
			if ($conf['orden'] !== 'asc' && $conf['orden'] !== 'desc') {
				throw new toba_error(
					'El atributo `orden` debe contener `asc` o `desc`'
				);
			}
			if ($conf['orden'] === 'desc') {
				$this->data['headerSortStartingDir'] = 'desc';
			}
		}

		return $this;
	}

	public function ordenacion_tipo($tipo)
	{
		if (isset($this->data['headerSorter'])) {
			throw new toba_error('La ordenación está deshabilitada, no es necesario definir el tipo de ordenación.');
		}
		$this->data['sorter'] = $tipo;

		return $this;
	}

	public function ordenacion_params($params)
	{
		if (isset($this->data['headerSorter'])) {
			throw new toba_error('La ordenación está deshabilitada, no es necesario definir los parámetros de ordenación.');
		}
		$this->data['sorterParams'] = $params;

		return $this;
	}

	public function unico()
	{
		if (!isset($this->data['validator'])) {
			$this->data['validator'] = [];
		}
		array_push($this->data['validator'], 'unico');

		return $this;
	}

	public function combo($datos)
	{
		if (isset($datos[0]['rv_low_value']) || isset($datos[0]['clave'])) {
			$datos = array_reduce($datos, function ($partial, $dato) {
				$clave = $dato['rv_low_value'] ? $dato['rv_low_value'] : $dato['clave'];
				$descripcion = $dato['rv_meaning'] ? $dato['rv_meaning'] : $dato['descripcion'];

				$partial[$clave] = $descripcion;

				return $partial;
			}, []);
		}
		$this->data['formatter'] = "'lookup'";
		$this->data['formatterParams'] = $datos;

		//if (isset($this->data['editor']) && $this->data['editor']) {
			$this->data['editor'] = 'select';
			$this->data['editorParams'] = $datos;
		//}
		//else if(isset($datos['editorParams']))
			//$this->data['editorParams'] = ["defaultValue" => 'CF'];//$datos['editorParams'];

		return $this;
	}

	public function ayuda($ayuda)
	{
		$this->data['headerTooltip'] =
			$this->data['tooltip'] = $ayuda;

		return $this;
	}

	private function number_validations($params)
	{
		if (isset($params['min'])) {
			array_push($this->data['validator'], "min:{$params['min']}");
		}

		if (isset($params['max'])) {
			array_push($this->data['validator'], "max:{$params['max']}");
		}

		// TODO: validar si el parámetro que se recibe
		// es una columna válida.
		// Validar si es una fecha válida.
		// Si ninguno de los dos cumple, lanzar error.
		if (isset($params['menor_que'])) {
			if ($this->data['field'] === $params['menor_que']['clave']) {
				throw new toba_error("El campo `{$this->data['field']}`no puede comparar si es menor o igual a sí mismo.");
			}
			array_push(
				$this->data['validator'],
				"comparar_numero:{$params['menor_que']['clave']}@{$params['menor_que']['descripcion']}@menor"
			);
		}

		if (isset($params['mayor_que'])) {
			if ($this->data['field'] === $params['mayor_que']['clave']) {
				throw new toba_error("El campo `{$this->data['field']}`no puede comparar si es mayor o igual a sí mismo.");
			}
			array_push(
				$this->data['validator'],
				"comparar_numero:{$params['mayor_que']['clave']}@{$params['mayor_que']['descripcion']}@mayor"
			);
		}
	}
}
