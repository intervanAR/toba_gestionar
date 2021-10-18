/**
 * @author lgraziani
 */

// Instancio el objeto si no existe
var IntervanTabulatorHelpers = window.IntervanTabulatorHelpers || {};

/**
 * Recupera el nombre del objeto_js del CI m�s cercano al elemento que se
 * pasa por par�metro. Se usa en las extensiones para recuperar la instancia
 * del objeto JS del CI para mandar datos por AJAX.
 *
 * @param {Object} elem El elemento desde donde se inicia la b�squeda.
 * @return {Object} La instancia JS del CI que manipula el elemento.
 */
IntervanTabulatorHelpers.ciQueManipulaLaInstancia = elem => (
	window[elem.closest('div[id^=cuerpo_js_ci_]').get(0).id.replace('cuerpo_', '')]
);

/**
 * M�todo que atrapa el click de un evento en l�nea e invoca
 * el evento por el CI.
 */
IntervanTabulatorHelpers.inlineEvt = (ci, id, evtName) => {
	ci.tabulator_instance.callTobaEvent(evtName, id, ci);
};

// Exporto el objeto al scope global
window.IntervanTabulatorHelpers = IntervanTabulatorHelpers;
