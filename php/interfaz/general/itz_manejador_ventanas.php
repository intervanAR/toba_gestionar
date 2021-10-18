<?php
/**
 * Ofrece un conjunto de helpers para poder manipular las ventanas de popups
 * utilizando JS.
 *
 * v1.1.0: Se modific� la funci�n `generar_js_cerrar_popup` para agregarle
 * 		   un segundo par�metro para que pueda ser invocado dentro de la
 * 		   funci�n de extensi�n del objeto JS de Toba. El valor por defecto
 * 		   mantiene la funcionalidad original.
 *
 * @author fbohn
 * @author lgraziani
 *
 * @version 1.1.0
 */
class itz_manejador_ventanas
{
	/**
	 * Env�a al cliente el c�digo JavaScript para HTML
	 * para el cierre de la ventana actual (popup).
	 *
	 * @param string $mensaje_alerta
	 * @return void
	 */
	public static function generar_js_cerrar_popup(
		$mensaje_alerta = '',
		$con_script = true
	) {
		$html_cerrar = "
			var x= 0;

			while (x < 500000) {
				x++;
			}

			window.close();
		";

		if (!empty($mensaje_alerta)) {
			$html_cerrar = "
				alert('$mensaje_alerta');
				$html_cerrar
			";
		}

		echo $con_script ? '<script>' : '';
		echo $html_cerrar;
		echo $con_script ? '</script>' : '';
	}

	/**
	 * Devuelve el c�digo JavaScript para HTML que recarga las dependencias
	 * del CI sin recargar la p�gina completa.
	 *
	 * Esta funci�n se llama desde la ventana principal que ser� recargada.
	 *
	 * @param array [$parametros=[]] es un array de los identificadores
	 *                               js de todas las dependencias a recargar.
	 * @param bool [$con_tag_js=true] Si la operaci�n posee alg�n componente con
	 *                                extensiones y est� definido el m�todo
	 *                                `extender_objeto_js()`, �sta debe llamarse
	 *                                desde ese m�todo con valor `false`,
	 *                                caso contrario se puede llamar en el `ini()`
	 *                                de la extensi�n del CI con valor `true`.
	 * @return void
	 */
	public static function generar_js_recargar_ci_principal(
		$parametros = [], $con_tag_js=true
	) {

		if ($con_tag_js) {
			echo "
				<script type=\"text/javascript\">
			";
		}
		echo "
			function recargar_ci_principal() {
		";
		foreach ($parametros as $clave => $valor) {
			echo "
				if (this.$valor != undefined) {
					$valor.set_evento(
						new evento_ei('recargar_$valor', true, '' ),
						true,
						this
					);
				}";
		}
		echo "
			}
		";
		if ($con_tag_js) {
			echo "
				</script>
			";
		}
	}

	/**
	 * Devuelve el c�digo JavaScript para HTML que genera
	 * una llamada para recarga las dependencias del CI
	 * sin recargar la p�gina completa.
	 *
	 * Esta funci�n se llama desde el popup que debe recargar
	 * la pantalla principal.
	 *
	 * @return void
	 */
	public static function generar_js_llamada_recargar_ci_principal()
	{
		echo "
			<script type=\"text/javascript\">
				if (top.opener) {
					top.opener.recargar_ci_principal();
				}
			</script>
		";
	}

	/**
	 * Esta funci�n se llama desde el CI del popup
	 * que debe recargar la pantalla principal.
	 *
	 * Adem�s debe invocarse dentro del m�todo
	 * `extender_objeto_js` del controlador de la
	 * ventana.
	 *
	 * @return void
	 */
	public static function generar_js_recargar_ci_al_cerrar()
	{
		echo "
			window.addEventListener('beforeunload', () => {
				if (top.opener) {
					top.opener.recargar_ci_principal();
				}
			});
		";
	}

	/**
	 * Devuelve el c�digo JavaScript para
	 * navegar a una URL en el CI llamador.
	 *
	 * @param string $url
	 * @return void
	 */
	public static function generar_js_navegar_a_ci_principal($url)
	{
		echo toba_js::abrir();
		echo "
			if (top.opener != undefined ) {
				top.opener.location.href = '$url';
			}
		";
		echo toba_js::cerrar();
	}
}
