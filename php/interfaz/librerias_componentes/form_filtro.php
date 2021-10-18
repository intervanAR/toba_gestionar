<?php
/*
 *  Maneja un rango de fechas.
 *
 *  El formulario debe tener los campos ui_fecha_desde y ui_fecha_hasta
 *
 */

class form_filtro extends principal_ei_formulario
{
    //-----------------------------------------------------------------------------------
    //---- JAVASCRIPT -------------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    public function extender_objeto_js()
    {
        echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__ui_fecha_desde__procesar = function(es_inicial)
		{
			if (this.ef('ui_fecha_desde').tiene_estado() && !this.ef('ui_fecha_hasta').tiene_estado()){
				this.ef('ui_fecha_hasta').set_estado(this.ef('ui_fecha_desde').get_estado());
				this.ef('ui_fecha_hasta').set_obligatorio(true);
			}else{
				this.ef('ui_fecha_hasta').set_obligatorio(false);
			}
		}
		
		{$this->objeto_js}.evt__ui_fecha_hasta__procesar = function(es_inicial)
		{
			if (this.ef('ui_fecha_hasta').tiene_estado()){
				this.ef('ui_fecha_desde').set_obligatorio(true);
			}else{
				this.ef('ui_fecha_desde').set_obligatorio(false);
			}
		}
		";
    }
}
