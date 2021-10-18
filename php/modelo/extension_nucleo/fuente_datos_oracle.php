<?php

include_once 'toba_dba_oracle.php';

class fuente_datos_oracle extends toba_fuente_datos
{

	/**
	 * Accede al objeto db que tiene el API para consultas/comandos sobre la fuente
	 * @return toba_db
	 */
	function get_db($reusar = true)
	{
		if ($reusar) {
			if (!isset($this->db)) {
				$this->pre_conectar();
				$this->db = toba_dba_oracle::get_db_de_fuente(toba::instancia()->get_id(),
															$this->definicion['proyecto'],
															$this->definicion['fuente_datos'],
															$reusar);
				$this->crear_usuario_para_auditoria($this->db);
				$this->post_conectar();
				if (isset($this->definicion['schema']) && $this->db->get_schema() == null) {
					$this->db->set_schema($this->get_conf_schemas());
				}
				$this->configurar_parseo_errores($this->db);
			}
			return $this->db;
		} else {
			//-- Se pide una conexión aislada, que no la reutilize ninguna otra parte de la aplicación
			// Esta el codigo anterior repetido porque si se unifica, el post_conectar asume la presencia de $this->db y no habria forma de pedir una conexion aislada
			$db = toba_dba_oracle::get_db_de_fuente(toba::instancia()->get_id(),
															$this->definicion['proyecto'],
															$this->definicion['fuente_datos'],
															$reusar);
			$this->crear_usuario_para_auditoria($db);
			if (isset($this->definicion['schema'])  && $this->db->get_schema() == null) {
				$db->set_schema($this->get_conf_schemas());
			}
			$this->configurar_parseo_errores($db);
			return $db;
		}
	}

	/**
	 * Libera el objeto db que tiene el API para consultas/comandos sobre la fuente
	 */
	function destruir_db()
	{
		toba_dba_oracle::desconectar_db_de_fuente(	toba::instancia()->get_id(),
													$this->definicion['proyecto'],
													$this->definicion['fuente_datos']);
		$this->db = null;
	}

}
