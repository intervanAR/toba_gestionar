<?php
/**
 * @author hmargiotta
 * @author lgraziani
 */
class generador_vinculos
{
	/**
	 * Devuelve un html con un v�nculo de navegaci�n que abre una nueva pesta�a.
	 *
	 * @param string $proyecto        Nombre del proyecto donde se encuentra
	 *                                la operaci�n
	 * @param string $item            ID de la operaci�n
	 * @param int    $id_comprobante  ID del comprobante de la operaci�n
	 * @param string $title           Descripci�n del tooltip del html a generar
	 * @param string $texto           Optional. Nombre o valor que se visualizar�
	 *                                al dibujar el html. Si no se especifica,
	 *                                usa el valor de $id_comprobante
	 * @param string $tipo            Optional.
	 *                                "link"  genera un elemento 'a' con la
	 *                                        navegaci�n,
	 *                                "boton" genera un elemento 'button' con la
	 *                                        navegaci�n.
	 *                                Por defecto genera un "link"
	 * @param array $parametros		  Arreglo asociativo de parametros opcionales.
	 */
	static public function get_html_navegar_comprobante(
		$proyecto, $item, $id_comprobante, $title, $texto = '', $tipo = 'link'
	, $parametros = [], $clase_css = '') {
		$id_comprobante = strip_tags($id_comprobante);
		$nombre_ventana = $proyecto . $item;
		$prefijo = str_pad(rand(0, getrandmax()), 10, 0, STR_PAD_LEFT);
		$oper = str_pad($item, 10, 0, STR_PAD_LEFT);
		$celda_memoria = 'icm' . $prefijo . $oper;
		$parametros['id_comprobante'] = $id_comprobante;
		$opciones = [
			'celda_memoria' => $celda_memoria,
			'texto' => $id_comprobante,
			'nombre_ventana' => $nombre_ventana,
			'menu' => 1,
		];
		$vinculo = toba::vinculador()->get_url(
			$proyecto,
			$item,
			$parametros,
			$opciones
		);

		if ($texto === '')
		{
			$texto = $id_comprobante;
		}
		switch ($tipo)
		{
			case 'boton':
				return "
					<button
						class=\"ei-boton ei-boton-defecto $clase_css\"
						href=\"#\"
						onclick=\"window.open(
							'$vinculo',
							'$nombre_ventana'
						)\"
						title=\"$title\"
					>$texto</button>
				";
			case 'link':
			default:
				return "
					<a
						href=\"#\"
						onclick=\"window.open(
							'$vinculo',
							'$nombre_ventana'
						)\"
						title=\"$title\"
					>$texto</a>
				";
		}
	}
}
