/**
 * Input imagen para IntervanTabulator.
 *
 * @author lgraziani
 */
/* globals: Tabulator, jQuery */

Tabulator.extendExtension('format', 'formatters', {
	blobImage(cell, formatterParams) {
		let value = cell.getValue();

		if (!value) {
			return;
		}
		setTimeout(() => cell.getRow().normalizeHeight(), 100);

		const width = formatterParams.width || 30;

		value = value.includes('data:image/') ? value : `data:image/png;base64,${value}`;

		return `<img style="width: ${width}vw;" src="${value}" />`;
	},
});

Tabulator.extendExtension('edit', 'editors', {
	blobImage(cell, onRendered, success, cancel, editorParams) {
		const input = $('<input type="file" accept="image/*" />');
		const reader = new FileReader();

		reader.addEventListener('load', () => {
			success(reader.result);
		}, false);

		input.on('change blur', evt => {
			reader.readAsDataURL(evt.target.files[0]);
		});

		input.on('keydown', evt => {
			if (evt.keyCode == 27) {
				cancel();
			}
		});

		return input;
	},
});
