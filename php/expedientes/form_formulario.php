<?php
class form_formulario extends administracion_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.retorno_validar_mascara = function(resultado)
		{	
			if (resultado['rta']=='OK'){
			    this.ef('nro_expediente').set_estado(resultado['mascara']);
				return true;
			} else {			   
			    this.ef('nro_expediente').resetear_estado();
				alert(resultado['rta']);				
				/*this.ef('nro_expediente').set_error(resultado['rta']);*/
				return false;
			}	
			
		}
		
		{$this->objeto_js}.evt__nro_expediente__procesar = function(es_inicial)
		{			
			var nro_expediente  = null;
			this.ef('nro_expediente').no_resaltar();
			if (this.ef('nro_expediente').tiene_estado())
				nro_expediente = this.ef('nro_expediente').get_estado();
			var mascara = null;
			if (this.ef('mascara').tiene_estado())
				mascara = this.ef('mascara').get_estado();
			this.ef('mascara').ocultar();
			this.ef('usa_auto_numeracion').ocultar();
			
			 if ( this.ef('nro_expediente').tiene_estado() && this.ef('mascara').tiene_estado()){
		                            var parametros = [];
		                            parametros[0] = nro_expediente;
									parametros[1] = mascara;	                                   
		                            this.controlador.ajax('obtener_mascara_expedientes', parametros, this, this.retorno_validar_mascara);
		                        }
			 return true;
		}	
		
		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__usa_auto_numeracion__validar = function()
		{   
		     if (this.ef('usa_auto_numeracion').get_estado() == 'N'){
			     this.ef('nro_expediente').set_obligatorio(false);
				 this.ef('nro_expediente').no_resaltar();
			 } else {
			     this.ef('nro_expediente').set_obligatorio(true);
				 this.ef('nro_expediente').resaltar();
			 }
			 return true;
		}
		";
	}


}
?>