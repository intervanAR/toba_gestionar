/**
 * Configuración genérica de las extensiones.
 *
 * @author lgraziani
 */

////////////////////////////////////////
// Namespace
////////////////////////////////////////
IntervanTabulator.extensiones = {};
IntervanTabulator.extensionesHelpers = {};

/**
 * Este objeto se utiliza como configuración por defecto
 * para las extensiones.
 */
IntervanTabulator.DefaultExtensionConfiguration = {
	/**
	 * Hook que se invoca durante la fase de rollback de tabulator.
	 *
	 * @param {string} cuadroId ID del DOM del cuadro generado
	 * por la clase de PHP de tabulator.
	 * @return void
	 */
	onRollback(cuadroId) {},
	onRowDelete(cuadroId, rowId) {},
};

IntervanTabulator.extensionesHelpers.resetearCascada = hijos => cell => {
	const row = cell.getRow();

	hijos.forEach(hijo => {
		row.getCell(hijo).setValue('');
	});
};

IntervanTabulator.extensionesHelpers.validarCasacada = (row, data, editorParams) =>
	editorParams['parents']
		.filter(parent => (data[parent] == null || data[parent] === ''))
		.map(parent => (
			'Cargue __' +
			row.getCell(parent)
				.getColumn()
				.getDefinition()
				.title +
			'__ antes'
		));

IntervanTabulator.extensionesHelpers.cascadaIsEditable = cell => {
	const row = cell.getRow();
	const editorParams = cell.getColumn().getDefinition().editorParams;

	// 0. Chequear si no se configuraron los campos padres.
	if (
		!editorParams
		|| typeof editorParams !== 'object'
		|| !Array.isArray(editorParams['parents'])
		|| editorParams['parents'].length === 0
	) {
		return true;
	}
	const data = row.getData();

	// 1. Chequear si los padres tienen datos.
	const parentsWithoutData =
		IntervanTabulator.extensionesHelpers.validarCasacada(
			row,
			data,
			editorParams
		);

	// 1.1. Si alguno no tiene, mostrar un mensaje que ayude.
	if (parentsWithoutData.length > 0) {
		cell.getElement().html(`<i>${parentsWithoutData[0]}</i>`);

		return false;
	}

	return true;
}
