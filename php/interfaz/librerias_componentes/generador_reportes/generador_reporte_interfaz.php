<?php
/**
 * Esta interfaz debe ser implementada por cada generador
 * de reportes.
 *
 * v1.1.0: Se agregó el parámetro $window al método generar_html de forma que
 * 		   pueda ser posible elegir si la ventana actual o la contenedora es
 * 		   la que invoca la vista de jasper.
 *
 * @author lgraziani
 *
 * @version 1.1.0
 */
interface generador_reporte_interfaz
{
	/**
	 * @param object $parametros_raw Datos específicos del reporte
	 *                               a generar.
	 * @param string $formato [Oracle only] String que define
	 *                        el tipo de salida
	 */
	public function generar_doc($parametros_raw, $formato);

	/**
	 * Hace un `echo` con el contenido JS necesario para mostrar
	 * el reporte generado.
	 */
	public function generar_html($window);
}
