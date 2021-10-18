<?php
/**
 * Antes de generar un reporte, verifica si existe en Jasper,
 * sino, utiliza el de Oracle.
 *
 * v1.1.0: Se agregó el parámetro $window en el método generar_html
 * 		   definido en la interfaz.
 *
 * @author lgraziani
 *
 * @version 1.1.0
 */
class generador_reportes
{
	const WINDOW = 'window';
	const PARENT_WINDOW = 'top.opener';

	private $reporter;
	private $jasper_disponible = false;

	public function __construct($nombre_reporte)
	{
		if (!is_string($nombre_reporte)) {
			throw new InvalidArgumentException('$nombre_reporte debe ser un string');
		}
		$bases_ini = toba_dba::get_bases_definidas();
		$proyecto = toba::proyecto()->get_id();
		$conf_base_reporte = [
			'reporte' => $nombre_reporte,
		];

		/////////////////////////////
		// a. Si existe, uso Jasper.
		/////////////////////////////
		if (isset($bases_ini['reportes jasper'])) {

			//controlar que el archivo exista en jasper
			$jasper_ruta =str_replace('\\', '/', $bases_ini['reportes jasper']['ruta_reportes']).$proyecto;
			$nombre_reporte_lowercase = strtolower($nombre_reporte);
			$nombre_reporte_uppercase = strtoupper($nombre_reporte);
			$generic_path_lowercase = "{$jasper_ruta}/$nombre_reporte_lowercase.jasper";
			$generic_path_uppercase = "{$jasper_ruta}/$nombre_reporte_uppercase.jasper";

			$custom_report = isset($bases_ini['reportes jasper']['custom_reportes']) ? $bases_ini['reportes jasper']['custom_reportes']: 'undefined';
			$custom_path_lowercase = "{$jasper_ruta}/$custom_report/$nombre_reporte_lowercase.jasper";
			$custom_path_uppercase = "{$jasper_ruta}/$custom_report/$nombre_reporte_uppercase.jasper";

			if (file_exists($generic_path_lowercase) || file_exists($generic_path_uppercase) || file_exists($custom_path_lowercase) || file_exists($custom_path_uppercase))
				try {
					$this->reporter = new generador_reportes_jasper(
						array_merge($conf_base_reporte, [
							'proyecto' => $proyecto,
						]),
						$bases_ini['reportes jasper']
					);
					$this->jasper_disponible = true;

					return;
				} catch (Exception $error) {
					// Si el código del error es 404 significa que
					// no existe el reporte en Jasper.
					if ($error->getCode() !== 404) {
						throw $error;
					}
				}
		}
		/////////////////////////////
		// b. Si no está en Jasper,
		//    uso Oracle.
		/////////////////////////////
		toba::logger()->info(
			"No existe la instancia o el reporte de jasper en el proyecto $proyecto"
		);

		$parametros_base = toba::db()->get_parametros();

		$this->reporter = new generador_reportes_oracle(
			array_merge($conf_base_reporte, [
				'usuario' => strtoupper(toba::usuario()->get_id()),
				'base' => $parametros_base['base'],
				'esquema' => $parametros_base['schema'],
			]),
			$bases_ini['reportes oracle']
		);
	}

	/**
	 * Se encarga de generar el reporte. No devuelve nada porque la clase
	 * que lo genera, almacena temporalmente esa información hasta que
	 * se llama al generador de html.
	 *
	 * @param void|object $parametros_raw Datos específicos del reporte
	 *                                    a generar.
	 * @param void|string $formato [Oracle only]
	 */
	public function llamar_reporte($parametros_raw = [], $formato = 'pdf')
	{
		if (!is_array($parametros_raw)) {
			$parametros_raw = [];
		}

		$parametros_raw += [
			'p_usuario' => strtoupper(toba::usuario()->get_id()),
		];

		toba::logger()->debug('[generador_reportes] Parámetros RAW del reporte:');
		toba::logger()->var_dump($parametros_raw);

		$this->reporter->generar_doc($parametros_raw, $formato);
	}

	public function generar_html($window = self::WINDOW)
	{
		return $this->reporter->generar_html($window);
	}

	public function get_parametros()
	{
		return $this->reporter->get_parametros();
	}

	public function get_tipo()
	{
		return $this->reporter->get_tipo();
	}

	public function get_url()
	{
		return $this->reporter->get_url();
	}
}
