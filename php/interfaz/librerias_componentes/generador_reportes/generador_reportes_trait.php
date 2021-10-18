<?php
/**
 * Permite a un controlador implementar la vista Jasper
 * sin necesidad de heredar de ning�n otro controlador.
 *
 * v1.2.1: Se corrigi� un error que causaba que reportes de sociales fallaran.
 * v1.2.0: Se agreg� la posibilidad de pasar el path del
 *         reporte desde el cliente. Necesario para los
 *         reportes custom de cada municipio.
 *
 * @author lgraziani
 *
 * @version 1.2.1
 */
trait generador_reportes_trait
{
	private $proyectos_financieros = [
		'administracion',
		'compras',
		'contabilidad',
		'presupuesto',
	];

	/**
	 * Configura la vista de jasper para ejecute el reporte que recibi�
	 * y le asigne la conexi�n actual a la DB.
	 *
	 * Adem�s agrega los par�metros gen�ricos.
	 *
	 * @param toba_vista_jasperreports $report la instancia de la vista Jasper
	 */
	public function vista_jasperreports(toba_vista_jasperreports $report)
	{
		$parametros_raw = toba::memoria()->get_parametros();
		$datos_ini = $this->get_datos_config();
		$usuario = strtoupper(toba::usuario()->get_id());

		/*
	     * 1. Decodifico los par�metros
	     */
		$parametros = array_map(function ($parametro) {
			return urldecode($parametro);
		}, $parametros_raw);

		/*
	     * 2. Configuro datos b�sicos
	     */
		toba::logger()->info(
			'-------------- Inicio Configuraci�n Reporte Jasper --------------'
		);
		if (!isset($parametros['p_logo'])) {
			$parametros['p_logo'] = $datos_ini['p_logo'];
		}

		$this->config_datos_financiero_sociales($parametros, $report);

		/*
	     * 3. Asigno path del archivo
	     */
		$report->set_path_reporte($this->get_path_reporte($parametros));

		unset($parametros['reporte']);

		/*
	     * 4. Seteo par�metros por defecto
	     */
		$report->set_parametro('p_usuario', 'S', $usuario);
		$report->set_parametro('p_logo', 'S', $parametros['p_logo']);
		$report->set_parametro('p_sistema', 'S', $datos_ini['p_sistema']);
		$report->set_parametro('p_municipio', 'S', $datos_ini['p_municipio']);

		/*
	     * 5. Saco los par�metros que no recibe el reporte
	     */
		toba::logger()->info("path : {$parametros['path']}");
		toba::logger()->info("p_logo : {$parametros['p_logo']}");

		unset($parametros['path']);
		unset($parametros['p_logo']);

		/*
	     * 6. Agrego los parametros restantes
	     *	  que corresponden al reporte.
	     */
		foreach ($parametros as $key => $value) {
			$val = urldecode($value);

			toba::logger()->info("$key : $val");

			$report->set_parametro($key, 'S', $val);
		}

		/*
	     * 7. Seteo la conexi�n a la DB.
	     */
		$proyecto_id = toba::proyecto()->get_id();
		// Recupero una conexi�n db para el proyecto
		$db = toba::db($proyecto_id);

		$report->set_conexion($db);

		toba::logger()->info(
			'---------------- Fin Configuraci�n Reporte Jasper ---------------'
		);
	}

	/**
	 * Arma segun el proyecto y retorna el path de la carpeta de reportes
	 * jasper.
	 *
	 * @deprecated Los reportes generados con la clase generador_reportes
	 *			   env�an el path entre los par�metros.
	 *
	 * @return string el path absoluto de la carpeta donde se encuentran
	 *                los reportes Jasper
	 */
	public function get_path_reportes_jasper()
	{
		$bases_ini = toba_dba::get_bases_definidas();
		$proyecto = toba::proyecto()->get_id();

		if (!isset($bases_ini['reportes jasper']['ruta_reportes'])) {
			// Si no existe, se escribe en el log un error
			// sin afectar al cliente
			toba::logger()->error(
				'Error: `ruta_reportes` de reportes no definido en el bases.ini'
			);

			return '';
		}
		$path = $bases_ini['reportes jasper']['ruta_reportes'];

		if (in_array($proyecto, $this->proyectos_financieros)) {
			$proyecto = 'financiero';
		}

		$path .= "$proyecto/";

		return $path;
	}

	protected function get_path_reporte($parametros)
	{
		return !empty($parametros['path'])
			? $parametros['path']
			: "{$this->get_path_reportes_jasper()}/{$parametros['reporte']}.jasper";
	}

	private function get_datos_config()
	{
		$bases_ini = toba_dba::get_bases_definidas();

		if (!isset($bases_ini['reportes jasper'])) {
			// En vez de un throw, es recomendable logear un warning para evitar
			// que la aplicaci�n crashee
			toba::logger()->warning(
				'La configuraci�n bases.ini para Jasper no est� definida.'
			);

			return;
		}
		$jasper = $bases_ini['reportes jasper'];

		if (!isset($jasper['p_municipio'])) {
			$jasper['p_municipio'] = '';

			toba::logger()->warning('Falta setear `p_municipio` en el bases.ini');
		}
		if (!isset($jasper['p_sistema'])) {
			$jasper['p_sistema'] = '';

			toba::logger()->warning('Falta setear `p_sistema` en el bases.ini');
		}
		if (!isset($jasper['p_logo'])) {
			$jasper['p_logo'] = '';

			toba::logger()->warning('Falta setear `p_logo` en el bases.ini');
		}
		if (!isset($jasper['custom_reportes'])) {
			$jasper['custom_reportes'] = '';

			toba::logger()->warning('Falta setear `custom_reportes` en el bases.ini');
		}

		return $jasper;
	}

	/**
	 * TODO: Refactor. Esto es de los sistemas financieros y sociales exclusivamente.
	 *
	 * Esta funci�n se encarga de configurar todo lo necesario
	 * para que estos sistemas reciban los par�metros por defecto
	 * que utilizan.
	 */
	private function config_datos_financiero_sociales(
		&$parametros,
		toba_vista_jasperreports &$report
	) {
		$proyecto = toba::proyecto()->get_id();
		$proyectos_tienen_kr_reportes = array_merge(
			$this->proyectos_financieros,
			['sociales']
		);

		if (!in_array($proyecto, $proyectos_tienen_kr_reportes)) {
			return;
		}

		if (!isset($parametros['titulo']) || empty($parametros['subtitulo'])) {
			$rep = dao_reportes_general::get_reporte($parametros['reporte']);

			$parametros['titulo'] = isset($rep['titulo']) ? $rep['titulo'] : '';
			$parametros['subtitulo'] = isset($rep['subtitulo']) ? $rep['subtitulo'] : ' ';
		}

		$report->set_parametro('p_reporte', 'S', $parametros['reporte']);
		$report->set_parametro('p_titulo', 'S', $parametros['titulo']);
		$report->set_parametro('p_subtitulo', 'S', $parametros['subtitulo']);

		toba::logger()->info("reporte : {$parametros['reporte']}");
		toba::logger()->info("titulo : {$parametros['titulo']}");
		toba::logger()->info("subtitulo : {$parametros['subtitulo']}");

		unset($parametros['titulo']);
		unset($parametros['subtitulo']);
	}
}
