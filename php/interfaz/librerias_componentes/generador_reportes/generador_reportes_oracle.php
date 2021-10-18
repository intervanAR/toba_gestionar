<?php
/**
 * Contiene todo lo relacionado con Oracle Reports.
 *
 * Para pasar parámetros que contienen caracteres especiales como los
 * espacios, debe cerrarse la cadena con doble comillas.
 * Por ejemplo:
 *
 * $params = ['titulo' => '"Recibo Manual"'];
 *
 * @author lgraziani
 *
 * v1.2.1: Rollback de la codificación de los parámetros.
 * v1.2.0: Se codificaron los parámetros de usuario que se envían al
 * 		   servidor de generación de reportes de forma que se puedan
 * 		   pasar cadenas de texto con espacios o caracteres especiales.
 * v1.1.0: Se cerró el canal de comunicación para evitar mayor consumo
 * 		   de memoria.
 *
 * @version 1.2.1
 */
class generador_reportes_oracle implements generador_reporte_interfaz
{
	// Time out para el llamado.
	const TIMEOUT = 0;

	private $tipo = 'ORA';
	private $base_conexion_params;
	private $oracle_ruta_entrada;
	private $oracle_ruta_salida;
	private $url;
	private $parametros;

	/**
	 * Inicializa el generador.
	 * NO llama al servidor, ni sabe qué reporte generar.
	 *
	 * @param object $conf_reporte Datos de configuración del reporte.
	 * @param object $conf_conexion Datos de configuración para la conexión al
	 *						  servidor de oracle.
	 *
	 * @return generador_reportes_oracle el objeto que genera reportes
	 */
	public function __construct($conf_reporte, $conf_conexion)
	{
		if (!function_exists('curl_init')) {
			throw new toba_error('El mecanismo CURL necesario para generar el reporte de Oracle no está habilitado, por favor comuníquese con un administrador');
		}
		if (
			!isset($conf_conexion['ruta_entrada']) ||
			!isset($conf_conexion['ruta_salida'])
		) {
			throw new toba_error('Debe setear los parametros `ruta_entrada` y `ruta_salida` en la sección `reportes oracle` del bases.ini');
		}
		$ruta_entrada = $conf_conexion['ruta_entrada'];
		$ruta_salida = $conf_conexion['ruta_salida'];

		// Evalua si es necesario agregar el nombre del host
		$this->oracle_ruta_entrada =
			strpos($ruta_entrada, 'http') !== 0
				? "http://{$this->get_host()}{$conf_conexion['ruta_entrada']}"
				: $conf_conexion['ruta_entrada'];
		$this->oracle_ruta_salida =
			strpos($ruta_salida, 'http') !== 0
				? "http://{$this->get_host()}:{$this->get_port()}{$conf_conexion['ruta_salida']}"
				: $conf_conexion['ruta_salida'];
		$this->base_conexion_params = $conf_reporte;
	}

	public function generar_html($window)
	{
		if ($this->url === null) {
			return;
		}
		echo "window.open('{$this->url}','_blank','scrollbars, resizable, height=600, width=600');";
	}

	public function generar_doc($parametros_raw, $formato)
	{
		$parametros = '';

		if (isset($parametros_raw)) {
			// Construyo el string de parametros
			foreach ($parametros_raw as $key => $value) {
				$parametros .= " $key=$value";
			}
		}
		$datos = array_merge(
			$this->base_conexion_params, [
			'formato' => $formato,
			'otros' => $parametros,
		]);
		$this->parametros = $parametros;

		$ch = curl_init();

		//////////////////////////////////////////
		// 1. Llamo al servidor de Oracle
		//	Reports para que genere el PDF.
		//////////////////////////////////////////
		curl_setopt($ch, CURLOPT_URL, $this->oracle_ruta_entrada);
		curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $datos);

		$resultado = curl_exec($ch);

		curl_close($ch);

		$error = strpos($resultado, 'Error');

		//////////////////////////////////////////
		// 2. Si el resultado no es el esperado,
		//	se debe informar.
		//////////////////////////////////////////
		if ($error === 0) {
			throw new toba_error($resultado);
		}
		if (empty($resultado)) {
			throw new toba_error('El servidor no está disponible');
		}
		$this->url = $this->oracle_ruta_salida.$resultado;
	}

	/**
	 * Retorna direccion ip del servidor
	 * donde se encuentra instalado toba.
	 *
	 * @return string host name
	 */
	private function get_host()
	{
		$http_host = explode(':', $_SERVER['HTTP_HOST']);

		return $http_host[0];
	}

	/**
	 * Devuelve el puerto donde escucha
	 * el servidor de toba.
	 *
	 * @return string el puerto
	 */
	private function get_port()
	{
		$http_host = explode(':', $_SERVER['HTTP_HOST']);

		// Devuelve puerto implicito si no está seteado
		return isset($http_host[1]) ? $http_host[1] : '80';
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
		return $this->url;
	}
}
