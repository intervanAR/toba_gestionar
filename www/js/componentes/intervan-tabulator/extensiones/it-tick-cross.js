/**
 * Input tick/cross para IntervanTabulator.
 *
 * @author lgraziani
 */
/* globals: Tabulator */

const itTickCrossTrueValues = [true, 'true', 1, '1', 'S'];

Tabulator.extendExtension('format', 'formatters', {
	tickCross: (cell, formatterParams) => {
		const value = cell.getValue();
		const element = cell.getElement();
		const tick = '<svg enable-background="new 0 0 24 24" height="14" width="14" viewBox="0 0 24 24" xml:space="preserve" ><path fill="#2DC214" clip-rule="evenodd" d="M21.652,3.211c-0.293-0.295-0.77-0.295-1.061,0L9.41,14.34  c-0.293,0.297-0.771,0.297-1.062,0L3.449,9.351C3.304,9.203,3.114,9.13,2.923,9.129C2.73,9.128,2.534,9.201,2.387,9.351  l-2.165,1.946C0.078,11.445,0,11.63,0,11.823c0,0.194,0.078,0.397,0.223,0.544l4.94,5.184c0.292,0.296,0.771,0.776,1.062,1.07  l2.124,2.141c0.292,0.293,0.769,0.293,1.062,0l14.366-14.34c0.293-0.294,0.293-0.777,0-1.071L21.652,3.211z" fill-rule="evenodd"/></svg>';
		const cross = '<svg enable-background="new 0 0 24 24" height="14" width="14"  viewBox="0 0 24 24" xml:space="preserve" ><path fill="#CE1515" d="M22.245,4.015c0.313,0.313,0.313,0.826,0,1.139l-6.276,6.27c-0.313,0.312-0.313,0.826,0,1.14l6.273,6.272  c0.313,0.313,0.313,0.826,0,1.14l-2.285,2.277c-0.314,0.312-0.828,0.312-1.142,0l-6.271-6.271c-0.313-0.313-0.828-0.313-1.141,0  l-6.276,6.267c-0.313,0.313-0.828,0.313-1.141,0l-2.282-2.28c-0.313-0.313-0.313-0.826,0-1.14l6.278-6.269  c0.313-0.312,0.313-0.826,0-1.14L1.709,5.147c-0.314-0.313-0.314-0.827,0-1.14l2.284-2.278C4.308,1.417,4.821,1.417,5.135,1.73  L11.405,8c0.314,0.314,0.828,0.314,1.141,0.001l6.276-6.267c0.312-0.312,0.826-0.312,1.141,0L22.245,4.015z"/></svg>';

		if (itTickCrossTrueValues.includes(value)) {
			element.attr('aria-checked', true);

			return tick;
		}

		element.attr('aria-checked', false);

		return cross;
	},
});

Tabulator.extendExtension('edit', 'editors', {
	tickCross: (cell, onRendered, success, cancel, editorParams) => {
		const value = cell.getValue();
		const input = $('<input type="checkbox"/>');

		// create and style input
		input.css({
			'margin-top': '5px',
			'box-sizing': 'border-box',
		})
		.val(value);

		onRendered(() => {
			input.focus();
		});

		// Set state to input
		input.prop('checked', itTickCrossTrueValues.includes(value));

		// Submit new value on blur
		input.on('change blur', (e) => {
			success(input.is(':checked'));
		});

		//submit new value on enter
		input.on('keydown', (e) => {
			if (e.keyCode == 13) {
				success(input.is(':checked'));
			}
			if (e.keyCode == 27) {
				cancel();
			}
		});

		return input;
	},
});
