<?php
class modal_imprimir extends administracion_ei_formulario
{
	function generar_html()
	{
		
		echo "<div id='modal_imprimir' class='modalForm'>";
			echo "<div class='main_content_intervan'>";
				echo "<div class='sub_content_intervan'>";
					echo "<div class='titulo_sub_content_intervan'>Imprimir";echo"</div>";
					parent::generar_html();
				echo "</div>";
			echo "</div>";
		echo "</div>";
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		
		{$this->objeto_js}.evt__cancelar = function()
		{
			document.getElementById('modal_imprimir').setAttribute('class','modalForm');
			return false;
		}
		
		//---- Eventos ---------------------------------------------
		
		{$this->objeto_js}.evt__aceptar = function()
		{
		}
		";
	}



}
?>