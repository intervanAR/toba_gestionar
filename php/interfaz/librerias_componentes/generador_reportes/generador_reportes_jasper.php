<?php
/**
 * Contiene todo lo relacionado con Jasper Reports.
 *
 * v1.5.0: Busca archivos en todo minúsculas o todo mayúsculas.
 * v1.4.0: Recupera parámetro debug_jasper del bases.ini
 * v1.3.0: Se agregó el parámetro $window en el método generar_html
 * 		   definido en la interfaz.
 * v1.2.1: Estandariza los nombres de los reportes a lowercase.
 * v1.2.0: Se agregó la posibilidad de pasar el path del
 * 		   reporte desde el cliente. Necesario para los
 * 		   reportes custom de cada municipio.
 *
 * @author lgraziani
 *
 * @version 1.4.0
 */
class generador_reportes_jasper implements generador_reporte_interfaz
{
	private $tipo = 'JAS';

	private $proyectos_financieros = [
		'administracion',
		'compras',
		'contabilidad',
		'presupuesto',
	];
	private $nombre_reporte;
	// Path del archivo template del reporte.
	private $jasper_path;
	private $jasper_ruta;
	private $debug_jasper;
	private $parametros;

	public function __construct($conf_reporte, $conf_data)
	{
		// Por defecto no aplica debug a jasper
		$this->debug_jasper =
			isset($conf_data['debug_jasper'])
			? $conf_data['debug_jasper']
			: 'N';
		/*
	     * 1. Concatena la ruta base más
	     *    la carpeta específica del proyecto.
	     */
		$proyecto =
			in_array($conf_reporte['proyecto'], $this->proyectos_financieros)
				? 'financiero'
				: $conf_reporte['proyecto'];
		$this->jasper_ruta =
			str_replace('\\', '/', $conf_data['ruta_reportes']).$proyecto;

		$nombre_reporte_lowercase = strtolower($conf_reporte['reporte']);
		$nombre_reporte_uppercase = strtoupper($conf_reporte['reporte']);
		$generic_path_lowercase =
			"{$this->jasper_ruta}/$nombre_reporte_lowercase.jasper";
		$generic_path_uppercase =
			"{$this->jasper_ruta}/$nombre_reporte_uppercase.jasper";
		/**
		 * Si la variable de configuración no existe,
		 * se utiliza 'undefined' por defecto.
		 *
		 * @var string
		 */
		$custom_report = isset($conf_data['custom_reportes'])
			? $conf_data['custom_reportes']
			: 'undefined';
		$custom_path_lowercase =
			"{$this->jasper_ruta}/$custom_report/$nombre_reporte_lowercase.jasper";
		$custom_path_uppercase =
			"{$this->jasper_ruta}/$custom_report/$nombre_reporte_uppercase.jasper";

		/*
	     * 2.1. Verifico si el archivo existe en
	     *      la carpeta custom del municipio.
	     */
		if (file_exists($custom_path_lowercase)) {
			$this->nombre_reporte = $nombre_reporte_lowercase;
			$this->jasper_path = $custom_path_lowercase;
		} elseif (file_exists($custom_path_uppercase)) {
			$this->nombre_reporte = $nombre_reporte_uppercase;
			$this->jasper_path = $custom_path_uppercase;
		} elseif (file_exists($generic_path_lowercase)) {
			$this->nombre_reporte = $nombre_reporte_lowercase;
			$this->jasper_path = $generic_path_lowercase;
		} elseif (file_exists($generic_path_uppercase)) {
			$this->nombre_reporte = $nombre_reporte_uppercase;
			$this->jasper_path = $generic_path_uppercase;
		} else {
			$error_mensaje = "El reporte $nombre_reporte_lowercase no está en Jasper ni en mayúsculas ni minúsculas.";
			/*
	         * Este reporte en particular no existe en Jasper.
	         * Usar Oracle.
	         */
			toba::logger()->info($error_mensaje);

			throw new Exception($error_mensaje, 404);
		}
	}

	public function generar_html($window = self::WINDOW)
	{
		echo "
			try{
				$window.location.href = $window.vinculador.get_url(
					null,
					null,
					'vista_jasperreports',
					{
						$this->parametros
					}
				);
			}catch(e){
				console.log(e);
			}
		";
	}

	public function generar_doc($parametros_raw, $formato)
	{
		$parametros = '';
		$proyecto = toba::proyecto()->get_id();

		// Defino los parámetros propios de Jasper
		if (!isset($parametros_raw['p_logo'])) {
			$parametros_raw['p_logo'] = toba_proyecto::get_path().'/www/img/';
		}

		$nombre_reporte = urlencode($this->nombre_reporte);
		$jasper_path = urlencode($this->jasper_path);

		$parametros .= "reporte: '$nombre_reporte',\n";
		$parametros .= "path: '$jasper_path',\n";
		$parametros .= "DEBUG_QUERIES: '$this->debug_jasper',\n";

		foreach ($parametros_raw as $key => $value) {
			$val = urlencode($value);

			$parametros .= "'$key': '$val',\n";
		}
		$this->parametros = $parametros;
	}

	public function get_parametros()
	{
		return $this->parametros;
	}

	public function get_tipo()
	{
		return $this->tipo;
	}

	public function get_url()
	{
		return $this->jasper_path;
	}
}
