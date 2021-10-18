/**
 * Input fecha para IntervanTabulator.
 *
 * @author lgraziani
 */
/* globals: Tabulator, inputmask */

Tabulator.extendExtension('edit', 'editors', {
	fecha(cell, onRendered, success, cancel, editorParams) {
		const input = $(`<input class="it-fecha" value="${cell.getValue()}" />`);

		if (editorParams && editorParams.mask === 'datetime') {
			input.inputmask('datetime', {
				mask: '1/2/y h:s:s',
				placeholder: 'dd/mm/yyyy hh:mm:ss',
			});
		} else {
			input.inputmask('date');
		}

		onRendered(() => {
			input.focus();
		});

		input.on('blur', () => {
			if (input.prop('readonly')) {
				cancel();
			}
			if (!input.inputmask('isComplete')) {
				success('');
			} else {
				success(input.val());
			}
		});

		input.on('keydown', evt => {
			if (evt.key === 'Escape') {
				cancel();

				return;
			}
			if (evt.key != 'Enter') {
				return;
			}

			input.blur();
		});

		return input;
	},
});
