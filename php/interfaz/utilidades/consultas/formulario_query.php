<?php
class formulario_query extends principal_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		function cerrarQuery(){
			var modal = document.getElementById('formulario_query');
			modal.className='modalForm close';			
			return false;
		}
		
		{$this->objeto_js}.evt__cerrar = function()
		{
			cerrarQuery();
			return false;
		}
		";
	}

}

?>