<?php
class cuadro_tablas extends principal_ei_cuadro
{
	//---- Config. EVENTOS sobre fila ---------------------------------------------------

	function conf_evt__eliminar_aud($evento, $fila)
	{
		if ($this->datos[$fila]['auditada']) {
			$evento->mostrar();
		} else {
			$evento->ocultar();
		}
	}

}
?>