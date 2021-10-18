/**
 * Se encarga de colorear las filas de los cuadros seg�n los posibles estados.
 *
 * @param {Object} params Contiene los "named params" de la funci�n.
 * @param {string} [params.idTable=document] Contiene el ID de la tabla.
 * @param {number} params.cellPosition Determina qu� celda act�a como clave.
 * @param {string} params.key
 *  Contiene el nombre del atributo. Junto al par�metro anterior, se realiza
 *  la b�squeda de los datos en el arreglo de datos.
 * @param {Object} params.statesAndColors
 *  Contiene los posibles atributos, sus posibles estados y los colores asociados.
 *  La prioridad se define de forma descendente.
 *  Por ejemplo:
 *
 *    {
 *      cobro_estado: {
 *        2: 'orange',
 *      },
 *      estado: {
 *        CON: 'green',
 *        ANU: 'red',
 *      },
 *    }
 *
 *  Esto significa que si una fila tiene el estado del cobro en 2, se colorea de
 *  naranja. Sino, chequea que el estado est� en confirmado, y entonces colorea
 *  de verde. Sino, chequea que el estado est� en anulado, y entonces colorea
 *  de rojo. Por �ltimo no hace nada.
 *
 * @param {Object} params.data Los datos del cuadro.
 *
 * @author lgraziani
 * @version 1.1.0
 */
function colorearCuadroTobaSegunEstados(params) {
	const tabla = params.idTable ? document.getElementById(params.idTable) : document;
	const filas_impares = tabla.getElementsByClassName('ei-cuadro-celda-impar');
	const filas_pares = tabla.getElementsByClassName('ei-cuadro-celda-par');
	const filas = Array.from(filas_impares).concat(Array.from(filas_pares));

	filas.forEach(fila => {
		// Cada celda est� separada por un \n, por lo que la fila posee
		// no s�lo celdas, sino tambi�n saltos de l�nea. Por ejemplo:
		// [#text "\n", td.ei-cuadro-fila.0, ... , #text "\n", td.ei-cuadro-fila.n]
		// Por eso debe sumarse 1 al �ndice que se pasa como par�metro.
		let valorCelda = fila.childNodes[params['cellPosition'] + 1];
		let objetoDeLaFila;

		// Limpia el contenido al reemplazar los saltos de l�nea y espacios.
		valorCelda = valorCelda.innerText.replace(new RegExp( '\\n', 'g' ), '');

		// 1. Recupero el objeto de la fila
		for (let i = 0; i < params.data.length; i++) {
			objetoDeLaFila = params.data[i];

			if (objetoDeLaFila[params.key] == valorCelda) {
				break;
			}
		}

		if (objetoDeLaFila === undefined) {
			throw new Error(
				`La clave ${params.key} con el valor ${valorCelda} no existe.`
			);
		}

		// 2. Recorro el objetos de estados.
		// 2.1. Primero recorro las claves del objeto principal, que contienen
		// los nombres de los atributos de los datos del cuadro que se consideran
		// para colorear.
		// 2.2. Despu�s, y por cada atributo, recorro el arreglo de sus estados,
		// para comprobar si alguno de esos estados est� en verdadero.
		const atributos = Object.keys(params.statesAndColors);

		for (let i = 0; i < atributos.length; i++) {
			const atributo = atributos[i];
			const estados = Object.keys(params.statesAndColors[atributo]);

			for (let j = 0; j < estados.length; j++) {
				const estado = estados[j];

				if (estado == objetoDeLaFila[atributo]) {
					fila.setAttribute(
						'style',
						`color: ${params.statesAndColors[atributo][estado]}`
					);

					// Este return corta la ejecuci�n de la funci�n del forEach
					// principal, asociado a una fila en particular.
					return;
				}
			}
		}
	});
}

