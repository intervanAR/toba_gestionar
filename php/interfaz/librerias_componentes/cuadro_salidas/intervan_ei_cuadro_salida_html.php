<?php

class intervan_ei_cuadro_salida_html extends toba_ei_cuadro_salida_html
{

	protected function html_cabecera()
	{
		//AGREGADO POR HUGO 02/01/2017
		$img = toba_recurso::imagen_toba('configurar.png', true);
		$id_ci= $this->_cuadro->get_id_objeto_js();
		$ci= $this->_cuadro->controlador();

		if (($ci->existe_dependencia('cuadro'))&&($ci->pantalla()->existe_evento('exportar'))){
			echo "<a href=# onclick="."$id_ci.invocar_vinculo('exportar','0');"."  title='Configurar Exportador'>$img</a>";
		}
		parent::html_cabecera();
	}
}
