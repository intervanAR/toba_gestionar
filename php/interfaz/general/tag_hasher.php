<?php
/**
 * Imprime tags HTML para asociar archivos CSS y JS a una vista.
 *
 * @author lgraziani
 * @version 1.0.0
 */
class tag_hasher
{
	private $base_path;
	private $base_url;

	/**
	 * Constructor de la clase.
	 *
	 * @param string $proyecto Nombre del proyecto.
	 * @return tag_hasher La instancia.
	 */
	public function __construct($proyecto)
	{
		$instancia = toba::instancia();

		$this->base_url = $instancia->get_url_proyectos([$proyecto])[$proyecto];
		$this->base_path = $instancia->get_path_proyecto($proyecto) . '/www';
	}

	public function css($relative_path)
	{
		$hash = hash_file('md5', "$this->base_path/$relative_path");

		echo "
			<link
				href='$this->base_url/$relative_path?md5=$hash'
				rel='stylesheet'
			>
		";
	}

	public function js($relative_path, $async = false)
	{
		$hash = hash_file('md5', "$this->base_path/$relative_path");
		$async = $async ? 'async' : '';

		echo "
			<script
				src='$this->base_url/$relative_path?md5=$hash'
				$async
			></script>
		";
	}
}
