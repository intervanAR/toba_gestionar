/**
 * Esta clase se encarga de procesar las operaciones del cliente.
 *
 * @author lgraziani
 * @see principal_ei_tabulator
 * @see principal/www/css/componentes/intervan-tabulator.css
 */
/* globals: imprimir_notificacion, $, jQuery, conexion, notificacion, vinculador, ajax_respuesta, evento_ei */

/**
 * El constructor se encarga instanciar Tabulator en un DIV.
 *
 * @param {string} id
 * 	El ID del div en el cual se instanciará Tabulator.
 * @param {Object} options
 * 	El conjunto de opciones de configuración de Tabulator.
 * 	Para más información, ver http://tabulator.info/docs/3.3#options
 * @param {ei_cuadro} table Instancia del cuadro.
 * @class
 */
function IntervanTabulator(id, options, table) {
	colums= options['columns'];
	for (var i = 0; i < colums.length; i++) {
		if (colums[i]['validatorFunction']) {
			colums[i]['validator'].push(new Function('cell', 'value', 'parameters', colums[i]['validatorFunction']));
		}
		
	}
	//options['columns'][2]['validator'][3]= test;//(options['columns'][2]['validator'][3]).replace('"','');
	console.log(options);
	this._id = id;
	this._table = table;
	this.domElement = $(`#${id} > .container`);
	/**
	 * Flag que permite saber si la paginación es por AJAX o no.
	 */
	this._isLocalPagination = options.pagination !== 'remote';
	/**
	 * Contiene el arreglo de datos iniciales.
	 * Se utiliza cuando se quiere deshacer los cambios.
	 *
	 * Se carga de datos en el evento `dataLoaded` de tabulator.
	 *
	 * @see generateFinalConfig
	 * @type {Array<Object>}
	 */
	this._initData;
	/**
	 * Contiene el arreglo de datos editados.
	 * Se utiliza durante la comunicación AJAX de actualización.
	 *
	 * @type {Array<Object>}
	 */
	this._editedData = new Map();
	/**
	 * Contiene el arreglo de filas seleccionadas.
	 * Se utiliza para el evento de eliminación.
	 *
	 * @type {Array<Row>}
	 */
	this._selectedData = [];
	/**
	 * Variable autoincremental que se utiliza para las
	 * filas agregadas.
	 *
	 * @type {number}
	 */
	this._autoIncrementIdx = 1;

	///////////////////////////////////////////////////////
	// Se posterga al siguiente ciclo del motor de JS la
	// instanciación de tabulator para que toba asigne al
	// objeto de la tabla la instancia de su CI.
	setTimeout(() => {
		this.domElement.tabulator(generateFinalConfig(this, id, table, options));
	}, 0);

	////////////////////////////////////////////////////////////
	// Instancio las operaciones de ABM
	////////////////////////////////////////////////////////////
	// La utilización de setTimeout tiene como objetivo
	// retrasar la manipulación de datos como la clonación de
	// arreglos. Este retraso permite reducir el tiempo que
	// tarda el explorador en mostrar los elementos visuales.
	//
	// setTimeout se encarga de ejecutar una función luego de un
	// tiempo definido como segundo parámetro. En este caso, al
	// ser 100, se retrasa 100ms.
	setTimeout(() => {
		defineAddRowEvent(this, id);
		defineRollbackEvent(this, id);
		defineDeleteRowsEvent(this, id);
		defineRequeryEvent(this, id);
		definePersistEvent(this, id, table);
	}, 100);
}

IntervanTabulator.prototype.constructor = IntervanTabulator;
IntervanTabulator.TYPE_INSERT = 'i';
IntervanTabulator.TYPE_UPDATE = 'm';
IntervanTabulator.TYPE_DELETE = 'd';

/**
 * Devuelve una clonación del arreglo de datos que recibe como parámetro.
 *
 * @param {Array<Object>} data Arreglo de datos para Tabulator.
 * @return {Array<Object} Nuevo arreglo clonado e independiente.
 */
IntervanTabulator.cloneData = data => data.map(elem => Object.assign({}, elem));
/**
 * Abre o cierra el popup que bloquea la ventana
 * durante una operación AJAX.
 *
 * @param {boolean} open Flag para abrir o cerrar el popup.
 * @return {void}
 */
IntervanTabulator.dialog = (open) => {
	if (open === undefined) {
		throw new Error('Param `open` is undefined. Expected boolean.');
	}
	window.location.hash = open ? 'modal-html' : '';
};

IntervanTabulator.prototype.callTobaEvent = function(eventName, id, caller) {
	const hacerSubmit = true;
	const mensajeConfirmacion = '';
	const seValida = false;
	const data = this._initData;

	caller.set_evento(
		new evento_ei(
			eventName,
			seValida,
			mensajeConfirmacion,
			JSON.stringify(this._initData.find(data => data.id === id))
		),
		hacerSubmit
	);
}

/**
 * Realiza la persistencia al servidor.
 *
 * @param {IntervanTabulator} self
 * 	Instancia del objeto IntervanTabulator. Se necesita para manipular su estado.
 * @param {ei_cuadro} table Instancia del cuadro.
 * @return {void}
 */
IntervanTabulator.persist = (self, table) => {
	IntervanTabulator.dialog(true);

	const values = {
		page_no: self.domElement.tabulator('getPage'),
		operations: Array.from(self._editedData.values()),
	};
	const metadata = {
		filters: self.domElement.tabulator('getFilters', true),
		sorters: self.domElement.tabulator('getSorters'),
	};

	if (metadata.filters.length) {
		values.filters = metadata.filters;
	}
	if (metadata.sorters.length) {
		values.sorters =
			metadata.sorters.map(({ field, dir }) => ({ field, dir }));
	}

	const clearExtraProps = data => (partial, key) => {
		if (key === 'inline_evts' || /(_lov_desc|_lv)$/i.test(key)) {
			return partial;
		}
		partial[key] = data[key];

		return partial;
	};
	values.operations = values.operations.map(operation => {
		if (operation.data) {
			operation.data =
				Object.keys(operation.data)
					.reduce(clearExtraProps(operation.data), {});
		}
		if (operation.original) {
			operation.original =
				Object.keys(operation.original)
					.reduce(clearExtraProps(operation.original), {});
		}

		return operation;
	});

	IntervanTabulator.sendAjax(self, table, 'persist', values, self.persist_response);
};

IntervanTabulator.resetMetadata = self => {
	// Una vez persistido todo,
	// y habiendo recibido y seteado nuevos datos
	// en la tabla, resetea la caché de las extensiones
	Object.values(IntervanTabulator.extensiones).forEach(
		extension => {
			extension.onRollback(self._id);
		}
	);

	self._editedData = new Map();
	self._autoIncrementIdx = 1;
};

IntervanTabulator.prototype.requeryData = function() {
	if (!this._isLocalPagination) {
		this.domElement.tabulator(
			'setPage',
			this.domElement.tabulator('getPage')
		);

		IntervanTabulator.resetMetadata(this);

		return;
	}

	if (
		this._editedData.size > 0 &&
		!confirm('¿Desea reconsultar los datos contra el servidor? Se perderán todos los cambios.')
	) {
		return;
	}

	IntervanTabulator.dialog(true);

	const response = result => {
		const isStatusOk = IntervanTabulator.processErrors(result);

		if (!isStatusOk) {
			return;
		}

		this.domElement.tabulator('setData', result.data);

		this.domElement.tabulator(
			'setPage',
			this.domElement.tabulator('getPage')
		);

		IntervanTabulator.resetMetadata(this);

		IntervanTabulator.dialog(false);
	};

	IntervanTabulator.sendAjax(this, this._table, 'requery_all', null, response);
};

/**
 * Implementación interna del método AJAX de Toba para el verbo POST.
 *
 * @param {IntervanTabulator} self
 * 	Instancia del objeto IntervanTabulator. Se necesita para manipular su estado.
 * @param {ei_cuadro} table Instancia del cuadro.
 * @param {String} method Nombre del método ajax sin el prefijo tabulator__
 * @param {any} values Datos a enviar.
 * @param {Function} callback Función que se invocará en la respuesta.
 * @return {void}
 */
IntervanTabulator.sendAjax = (self, table, method, values, callback) => {
	const respuesta = new ajax_respuesta('D');

	respuesta.set_callback(self, callback);

	const callback_real = {
		success(response) {
			IntervanTabulator.dialog(false);

			respuesta.recibir_respuesta(response);
		},
		scope: respuesta,
		failure(error) {
			IntervanTabulator.dialog(false);

			toba.error_comunicacion(error);
		},
	};
	const param = {
		'ajax-metodo': `tabulator__${method}`,
		'ajax-modo': 'D',
		'ajax-param': table._id_dep,
	};
	const vinculo = vinculador.get_url(
		null,
		null,
		'ajax',
		param,
		[table.controlador._id]
	);
	////////////////////////////////////////////////////////////////////
	// En esta operación, la función de la operación de serialización
	// del JSON es un `replacer` y se encarga de pasar a `null` a todos
	// los atributos que estén como `undefined`. Sin la función,
	// `JSON.stringify` no contemplaría esos atributos, y enviaría
	// objetos con faltante de atributos.
	conexion.asyncRequest(
		'POST',
		vinculo,
		callback_real,
		values && jQuery.param(values) || null
	);
};

/**
 * Tabulator necesita obligatoriamente un ID para las filas.
 * Esta función se encarga de generar un nuevo id temporal
 * para las filas que se insertan.
 *
 * Autoincrementa el índice para cada petición devuelva uno distinto.
 *
 * @return {string} El nuevo id.
 */
IntervanTabulator.prototype.newStringId = function() {
	return `nuevo_${this._autoIncrementIdx++}`;
};
IntervanTabulator.prototype.hasEditedData = function() {
	return !!this._editedData.size;
};

IntervanTabulator.processErrors = (result, callback) => {
	if (result.status == 200) {
		return true;
	}
	const error_interno = 'Error interno, por favor comuníquese con un administrador.';

	if (!result.error) {
		imprimir_notificacion(error_interno, 'error');

		return;
	}
	if (callback) {
		callback();

		return;
	}

	if (typeof result.error === 'string') {
		imprimir_notificacion(result.error, 'error');

		return;
	} else {
		console.error(result);
	}
};
/**
 * Función que se invoca durante la respuesta del AJAX de persistencia.
 *
 * @param {Object} result
 * 	Contiene la respuesta del servidor.
 * @param {Array<Object>} [result.data]
 * 	La lista de datos nuevos.
 * @param {string} result.status
 * 	Código HTTP del resultado.
 * @param {string} [result.error]
 * 	Mensaje de error.
 * @return {void}
 */
IntervanTabulator.prototype.persist_response = function(result) {
	const isStatusOk = IntervanTabulator.processErrors(result, () => {
		try {
			const data = JSON.parse(result.error);
			const fila = this.domElement.tabulator('getRow', data.id);

			notificacion.limpiar();

			data.errores.forEach(error => {
				fila
					.getCell(error.campo)
					.getElement()
					.addClass('tabulator-validation-fail');
				notificacion.agregar(error.mensaje, 'error');
			});
			notificacion.mostrar();
		} catch (err) {
			imprimir_notificacion(result.error, 'error');
		}
	});

	if (!isStatusOk) {
		return;
	}

	notificacion.limpiar();
	notificacion.agregar('Los datos se actualizaron correctamente.', 'info');

	result.info.forEach(info => {
		notificacion.agregar(info.mensaje, info.tipo);
	});

	notificacion.mostrar();

	this.domElement.tabulator('setData', result.data);

	if (this._isLocalPagination) {
		this.domElement.tabulator(
			'setPage',
			this.domElement.tabulator('getPage')
		);
	}

	IntervanTabulator.resetMetadata(this);
};

/**
 * Configuración del evento de insersión de filas.
 *
 * @param {IntervanTabulator} self
 * 	Instancia del objeto IntervanTabulator. Se necesita para manipular su estado.
 * @param {string} id
 * 	El ID del div contenedor de la instancia. Se utiliza para acceder a los
 * 	elementos de los controles.
 * @return {void}
 */
function defineAddRowEvent(self, id) {
	$(`#${id} > .controls .add-row`).click(() => {
		// 1. Genero el nuevo ID a partir
		const newId = self.newStringId();

		if (self._isLocalPagination) {
			// 2. Limpio el filtro para que se vea la nueva fila.
			self.domElement.tabulator('clearFilter', true);
		}

		// 3. Agrego la fila en el cuadro.
		const row = self.domElement.tabulator('addRow', {
			id: newId,
		});

		row.getElement().addClass('added');

		// 4. Guardo el registro en el mapa de elementos editados.
		self._editedData.set(
			newId,
			{
				type: IntervanTabulator.TYPE_INSERT,
				data: {
					id: newId,
				},
			}
		);
	});
}

/**
 * Configuración del evento de rollback de los cambios.
 *
 * @param {IntervanTabulator} self
 * 	Instancia del objeto IntervanTabulator. Se necesita para manipular su estado.
 * @param {string} id
 * 	El ID del div contenedor de la instancia. Se utiliza para acceder a los
 * 	elementos de los controles.
 * @return {void}
 */
function defineRollbackEvent(self, id) {
	$(`#${id} > .controls .rollback`).click(() => {
		self.domElement.tabulator('setData', self._initData);

		IntervanTabulator.resetMetadata(self);
	});
}

/**
 * Configuración del evento de eliminación de las filas seleccionadas.
 *
 * @param {IntervanTabulator} self
 * 	Instancia del objeto IntervanTabulator. Se necesita para manipular su estado.
 * @param {string} id
 * 	El ID del div contenedor de la instancia. Se utiliza para acceder a los
 * 	elementos de los controles.
 * @return {void}
 */
function defineDeleteRowsEvent(self, id) {
	const mensajeNotificacion = `No hay filas seleccionadas.

Para seleccionar una fila mantenga apretada la tecla Shift y cliquee una o varias filas.
	`;

	$(`#${id} > .controls .delete`).click(() => {
		if (self._selectedData.length === 0) {
			notificacion.limpiar();
			notificacion.agregar(mensajeNotificacion, 'info');
			notificacion.mostrar();

			return;
		}

		self._selectedData.forEach(row => {
			const rowId = row.getIndex();

			self.domElement.tabulator('deleteRow', rowId);

			Object.values(IntervanTabulator.extensiones).forEach(
				extension => {
					extension.onRowDelete(id, rowId);
				}
			);

			if (rowId.indexOf('nuevo_') !== -1) {
				self._editedData.delete(rowId);

				return;
			}

			self._editedData.set(rowId, {
				type: IntervanTabulator.TYPE_DELETE,
				original: self._initData.find(data => data.id === rowId),
			});
		});

		self.domElement.tabulator('deselectRow');

		self._selectedData = [];
	});
}

function defineRequeryEvent(self, id) {
	$(`#${id} > .controls .requery-data`).click(() => {
		self.requeryData();
	});
}

/**
 * Configuración del evento de persistencia.
 *
 * @param {IntervanTabulator} self
 * 	Instancia del objeto IntervanTabulator. Se necesita para manipular su estado.
 * @param {string} id
 * 	El ID del div contenedor de la instancia. Se utiliza para acceder a los
 * 	elementos de los controles.
 * @param {ei_cuadro} table Instancia cuadro.
 * @return {void}
 */
function definePersistEvent(self, id, table) {
	$(`#${id} > .controls .persist`).click(() => {
		if (self._editedData.size === 0) {
			imprimir_notificacion('No se realizaron cambios.', 'info');

			return;
		}

		IntervanTabulator.persist(self, table);
	});
}

/**
 * Procesa los datos de una fila, y limpia aquellas columnas
 * de tipo lov.
 *
 * TODO: Extraer esta función en hooks para los tipos de campo
 * custom, para que sea posible que cada uno sepa cómo procesar
 * sus datos y no tener una función gigante en el core donde
 * se acople a todos los componentes.
 *
 * @param {Object} row La fila actualizada.
 * @return {Object} Los datos procesados.
 */
IntervanTabulator.prototype.processRowData = (row) => {
	// FIXME: Borrar el assign cuando se corrija #1170
	const data = Object.assign({}, row.getData());
	const keys = Object.keys(data);

	keys.forEach(key => {
		const element = data[key];

		if (row.row.getCell(key) === undefined) {
			return;
		}

		if (row.getCell(key).cell.column.definition.formatter === 'combo_editable') {
			data[key] = (element && element.value) || element;
			data[`${key}_lov_desc`] =
				(element && element.text) || data[`${key}_lv`] || data[`${key}_lov_desc`];

			return;
		}

		if (row.getCell(key).cell.column.definition.formatter === 'tickCross') {
			data[key] = itTickCrossTrueValues.includes(element) ? 'S' : 'N';

			return;
		}
	});

	return data;
};

/**
 * Definición de la configuración que utiliza el estado del cuadro.
 *
 * @param {IntervanTabulator} self
 * 	Instancia del objeto IntervanTabulator. Se necesita para manipular su estado.
 * @param {Object} options
 * 	El conjunto de opciones de configuración de Tabulator.
 * @param {ei_cuadro} table Instancia del cuadro.
 * @return {Object}
 * 	La configuración final de Tabulator.
 */
function generateFinalConfig(self, id, table, options) {
	/**
	 * Actualiza el arreglo de datos modificados si se edita
	 * una columna o si se actualiza una fila entera desde JS.
	 *
	 * @param {Object} row Instancia de la fila actualizada.
	 * @return {void}
	 */
	const updateEditedData = row => {
		const rowId = row.getIndex();
		const isNotNew = rowId.indexOf('nuevo_') === -1;

		if (isNotNew) {
			row.getElement().addClass('edited');
		}

		self._editedData.set(
			rowId,
			{
				type: isNotNew
					? IntervanTabulator.TYPE_UPDATE
					: IntervanTabulator.TYPE_INSERT,
				data: self.processRowData(row),
				original: self._initData.find(data => data.id === rowId),
			}
		);
	};

	if (options['pagination'] === 'remote') {
		options['ajaxURL'] = vinculador.get_url(
			null,
			null,
			'ajax',
			{
				'ajax-metodo': 'tabulator__get_pagina',
				'ajax-modo': 'D',
				'ajax-param': table._id_dep,
			},
			[table.controlador._id]
		);

		options['ajaxFiltering'] = true;
		options['ajaxSorting'] = true;
		options['ajaxConfig'] = 'POST';
		options['ajaxResponse'] = (url, params, response) => {
			if (response.status != 200) {
				const mensaje_error = !response.error
					? 'Error interno, por favor comuníquese con un administrador.'
					: response.error;

				imprimir_notificacion(mensaje_error, 'error');

				return;
			}

			return response;
		};
		options['ajaxRequesting'] = () => {
			if (self._editedData.size > 0) {
				imprimir_notificacion(
					'Antes order, filtrar o cambiar de página, por favor guarde/deshaga los cambios.',
					'info'
				);

				return false;
			}

			return true;
		};
	}

	return Object.assign({
		addRowPos: 'top',
		cellEdited: cell => updateEditedData(cell.getRow()),
		rowUpdated: updateEditedData,

		selectable: true,
		rowSelectionChanged(data, rows) {
			self._selectedData = rows;
		},
		dataLoaded(data) {
			if (!Array.isArray(data)) {
				return;
			}

			self._initData = IntervanTabulator.cloneData(data);
		},

		locale: 'es',
		langs: {
			es: {
				ajax: {
					loading: 'Cargando',
					error: 'Error',
				},
				groups: {
					item: 'ítem',
					items: 'ítems',
				},
				pagination: {
					first: 'Primera',
					first_title: 'Primera página',
					last: 'Última',
					last_title: 'Última página',
					prev: 'Anterior',
					prev_title: 'Página anterior',
					next: 'Siguiente',
					next_title: 'Página siguiente',
				},
				headerFilters: {
					default: 'filtrar columna...',
				},
			},
		},
	}, options);
}

////////////////////////////////////////////////////
// Esta función se invoca una vez que la página
// cargó.
// Se encarga de inyectar el modal de espera por
// operación AJAX una única vez.
////////////////////////////////////////////////////
$(document).ready(() => {
	window.location.hash = '';

	document.documentElement
		.insertAdjacentHTML(
			'beforeend',
			`
<div id='modal-html' class='modalDialog'>
	<div>
		<h2>Por favor, espere...</h2>
		<p>Se está procesando la operación.</p>
	</div>
</div>
`
		);
});

IntervanTabulator.isANewRow = cell => (
	cell.getRow().getIndex().indexOf('nuevo') !== -1
);

////////////////////////////////
// Métodos públicos estáticos
////////////////////////////////
IntervanTabulator.editableOnAddOnly = IntervanTabulator.isANewRow;
IntervanTabulator.delay = (() => {
	let timer = 0;

	return (callback, ms) => {
		clearTimeout(timer);
		timer = setTimeout(callback, ms);
	};
})();
