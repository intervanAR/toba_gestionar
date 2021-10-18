/**
 * Combo editable para IntervanTabulator.
 *
 * @author lgraziani
 */
/* globals: Tabulator, d5XCombo, dhx, notificacion, IntervanTabulatorHelpers, IntervanTabulator */

IntervanTabulator.extensiones.comboEditable = {
	containers: new Map(),
	/**
	 * Hook que se invoca durante la fase de rollback de tabulator.
	 *
	 * @param {string} cuadroId ID del DOM del cuadro generado
	 * por la clase de PHP de tabulator.
	 * @return void
	 */
	onRollback(cuadroId) {
		if (!this.containers.has(cuadroId)) {
			return;
		}
		this.containers.get(cuadroId).forEach(rowContainers => {
			rowContainers.forEach(({ combo }) => {
				combo.unload();
			});
		});
		this.containers.set(cuadroId, new Map());
	},
	onRowDelete(cuadroId, rowId) {
		const tableContainers = this.get(cuadroId);

		if (!tableContainers.has(rowId)) {
			return;
		}
		tableContainers.get(rowId).forEach(({ combo }) => {
			combo.unload();
		});
		tableContainers.delete(rowId);

		this.containers.set(cuadroId, tableContainers);
	},
	/**
	 * Método interno del componente que permite recuperar
	 * el contenedor de un cuadro en particular.
	 *
	 * @param {string} cuadroId ID del DOM del cuadro generado
	 * por la clase de PHP de tabulator.
	 * @return {Map} El contenedor de instancias de combos editables.
	 */
	get(cuadroId) {
		if (!this.containers.has(cuadroId)) {
			this.containers.set(cuadroId, new Map());
		}

		return this.containers.get(cuadroId);
	},
	/**
	 * Método interno del componente que permite guardar un contenedor
	 * de un cuadro en particular.
	 *
	 * @param {string} cuadroId ID del DOM del cuadro generado
	 * por la clase de PHP de tabulator.
	 * @param {Map} El contenedor de instancias de combos editables.
	 * @return void
	 */
	set({ cuadroId, rowId, field, tableContainers, container }) {
		if (!tableContainers.has(rowId)) {
			tableContainers.set(rowId, new Map());
		}
		const rowContainers = tableContainers.get(rowId);

		rowContainers.set(field, container);
		tableContainers.set(rowId, rowContainers);

		this.containers.set(cuadroId, tableContainers);
	},
	/**
	 * Esta función se encarga de procesar el cambio de
	 * valor del combo.
	 */
	handleComboChange: cell => (value, text) => {
		if (cell.getValue() === value) {
			return;
		}
		cell.setValue({ value, text });
	},
	/**
	 * Esta función se encarga de cancelar la operación
	 * cuando se cliquea enter.
	 */
	handleComboKeypress: cancel => keyCode => {
		if (keyCode !== 13 && keyCode !== 27) {
			return;
		}
		cancel();
	},
	/**
	 * Esta función se encarga de cancelar la operación
	 * cuando se sale del input.
	 */
	handleInputBlur: cancel => () => {
		cancel();
	},
	/**
	 * Crea los eventos para el filtro a nivel de columna.
	 */
	createHeaderFilterEvents(cell, combo, success) {
		const input = combo.getInput();

		combo.attachEvent('onChange', success);
		combo.attachEvent(
			'onKeyPressed',
			keyCode => {
				if (keyCode === 27) {
					combo.unSelectOption();
				}
				if ([13, 9].includes(keyCode)) {
					success(combo.getSelectedValue());
				}
			}
		);
		input.addEventListener('search', evt => {
			evt.stopImmediatePropagation();
		});
		input.addEventListener('keyup', evt => {
			evt.stopImmediatePropagation();

			if (!evt.target.value) {
				combo.unSelectOption();
			}
		});
	},
	/**
	 * Crea los eventos para aplicar o cancelar
	 * la edición.
	 */
	createEvents(cell, combo, onRendered, cancel) {
		const input = combo.getInput();
		const changeEvtId = combo.attachEvent(
			'onChange',
			this.handleComboChange(cell)
		);
		const keypressEvtId = combo.attachEvent(
			'onKeyPressed',
			this.handleComboKeypress(cancel)
		);
		const inputBlurEvt = this.handleInputBlur(cancel);

		input.addEventListener('blur', inputBlurEvt);

		onRendered(() => {
			input.focus();
		});

		return {
			changeEvtId,
			keypressEvtId,
			inputBlurEvt,
		};
	},
	setInitialValue(cell, combo) {
		const initialValue = cell.getValue();
		const field = cell.getField();

		if (initialValue && initialValue.indexOf('nuevo') === -1) {
			const data = cell.getData();
			const text = data[`${field}_lv`] || data[`${field}_lov_desc`];

			if (text == null) {
				console.warn(`[it-combo-editable] El atributo \`${field}_lv\` o \`${field}_lov_desc\` no está definido.`);
			}

			combo.addOption([{
				value: initialValue,
				text: text || '',
			}]);

			combo.setComboValue(initialValue);
		}
	},
	setAjaxToCombo(table, cell, combo, editorParams) {
		combo.enableFilteringMode(true, 'dummy', true);
		combo.attachEvent('onDynXLS', text => {
			if (!text) {
				return;
			}

			if (text === '*') {
				text = '';
				combo.getInput().value = '';
			}
			IntervanTabulator.delay(() => {
				combo.clearAll();

				const vinculo = this.getVinculo(table, editorParams);
				const data = cell.getData && cell.getData() || {};
				const parents = !editorParams.parents
					? {}
					: editorParams.parents.reduce((partial, parentId) => {
						const id = data[parentId];

						partial[parentId] = id.value != null ? id.value : id;

						return partial;
					}, {});

				dhx.ajax.get(`${vinculo}&mask=${encodeURIComponent(text)}&parents=${JSON.stringify(parents)}`, response => {
					const data = JSON.parse(response.xmlDoc.responseText);

					if (data.status !== 200) {
						const error_interno = 'Error interno, por favor comuníquese con un administrador.';

						imprimir_notificacion(data.error || error_interno, 'error');

						console.error(data.error);

						return;
					}

					combo.load(data, () => {
						combo.openSelect();
					});
				});
			}, 500);
		});

	},
	getVinculo(table, editorParams) {
		const ci = IntervanTabulatorHelpers.ciQueManipulaLaInstancia(table);
		const param = {
			'ajax-metodo': 'tabulator__get_lov',
			'ajax-modo': 'D',
			'ajax-param': editorParams.metodo_lov,
		};

		return vinculador.get_url(
			null,
			null,
			'ajax',
			param,
			[ci._id]
		);
	},
};

Tabulator.extendExtension('edit', 'editors', {
	combo_editable(cell, onRendered, success, cancel, editorParams) {
		const ComboEditableExt = IntervanTabulator.extensiones.comboEditable;
		const field = cell.getField();
		const row = cell.getRow();
		// Si la función no está definida, es la lov de filtro
		const rowId = row.getIndex ? row.getIndex() : -1;
		const cuadroId = this.table.element.parent().attr('id');
		const tableContainers = ComboEditableExt.get(cuadroId);

		if (tableContainers.has(rowId)) {
			const rowContainers = tableContainers.get(rowId);

			if (rowContainers.has(field)) {
				const {
					container,
					combo,
					changeEvtId,
					keypressEvtId,
					inputBlurEvt,
				} = rowContainers.get(field);
				const input = combo.getInput();

				// Cada vez que se vuelve a operar con un combo
				// se necesita reaplicar el evento que actualiza
				// los datos porque es probable que la celda
				// cambió (reordenamiento, filtro o persistencia).
				combo.detachEvent(changeEvtId);
				combo.detachEvent(keypressEvtId);
				input.removeEventListener('blur', inputBlurEvt);

				const evts = ComboEditableExt.createEvents(
					cell,
					combo,
					onRendered,
					cancel,
					rowId
				);

				ComboEditableExt.set({
					cuadroId,
					rowId,
					field,
					tableContainers,
					container: Object.assign({
						container,
						combo,
					}, evts),
				});

				return container;
			}
		}

		// Instanciación del combo
		const container = $('<div></div>');
		const combo = new d5XCombo(
			container.get(0),
			'combo_editable',
			rowId === -1 ? 393 : 400
		);
		const input = combo.getInput();

		input.style.margin = '0 0 0 .25em';

		ComboEditableExt.setAjaxToCombo(
			this.table.element,
			cell,
			combo,
			editorParams
		);

		if (rowId === -1) {
			input.placeholder = 'filtrar columna...';
			ComboEditableExt.createHeaderFilterEvents(
				cell,
				combo,
				success
			);

			return container;
		}

		ComboEditableExt.setInitialValue(cell, combo);

		const evts = ComboEditableExt.createEvents(
			cell,
			combo,
			onRendered,
			cancel
		);

		ComboEditableExt.set({
			cuadroId,
			rowId,
			field,
			tableContainers,
			container: Object.assign({
				container,
				combo,
			}, evts),
		});

		return container;
	},
});

Tabulator.extendExtension('format', 'formatters', {
	combo_editable(cell) {
		const data = cell.getValue();

		if (data && data.text) {
			return data.text;
		}

		if (data) {
			const cellData = cell.getData();
			const field = cell.getField();

			return cellData[`${field}_lv`] || cellData[`${field}_lov_desc`];
		}

		if (!!cell.getColumn().getDefinition()['editor']) {
			return '<i>Clic para editar...</i>';
		}
	},
});

Tabulator.extendExtension('validate', 'validators', {
	combo_editable: (cell, content) => (
		typeof content === 'undefined'
		|| (
			typeof content === 'object'
			&& typeof content.value !== 'undefined'
			&& content.value !== ''
			&& content.value !== null
		)
	),
});

Tabulator.extendExtension('sort', 'sorters', {
	combo_editable(a, b) {
		const first = !a ? 0 : a.value || a;
		const second = !b ? 0 : b.value || b;

		return String(first)
			.localeCompare(String(second), 'kn', {
				numeric: true,
			});
	},
});

Tabulator.extendExtension('filter', 'filters', {
	combo_editable: (filterVal, rowVal) => (filterVal === rowVal),
});
