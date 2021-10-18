<?php
class form_parametros extends principal_ei_formulario
{

	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{

		$principal = toba::instancia()->get_url_proyectos(['principal'])['principal'];
		$proyecto = toba::proyecto()->get_id();
		
		echo "
		//---- Eventos ---------------------------------------------
		var efs = {$this->objeto_js}.efs();

		{$this->objeto_js}.ef('reporte').ocultar();

		for (i in efs){
			var efx= efs[i]._id;

			if (efx != undefined && efx != '') {
				{$this->objeto_js}.ef(efx).ocultar();
			}
		}

		{$this->objeto_js}.evt__cancelar = function() {
			var modal = document.getElementById('form_parametros');

			modal.className='modalForm close';

			return false;
		}

		{$this->objeto_js}.evt__generar = function() {
			var id = null;

			reporte = this.ef('reporte').get_estado();
			altura_titulo = 20; //this.ef('tamanio_titulo').get_estado();
			tipo_salida = this.ef('tipo_salida').get_estado();

			if (!reporte) {
				return;
			}
			
			var efs = this.efs();
			var parametros = '{';
			var parametros_mostrar= '';

			for (i in efs) {
				var ef= efs[i]._id;

				if ((ef === 'reporte')||(ef.search('tipo') > -1)){
					continue;
				}
				if ((!this.ef(ef).tiene_estado())&&(this.ef(ef).es_oculto())) {
					continue;
				}
				var valor= null;
				if (parametros_mostrar == '')
					parametros_mostrar= this.ef(ef).nodo().firstElementChild.textContent + ': ' + this.ef(ef).input().value;
				else
					parametros_mostrar+= ', '+this.ef(ef).nodo().firstElementChild.textContent + ': ' + this.ef(ef).input().value;

				if(this.ef(ef).tiene_estado()){
					valor= this.ef(ef).get_estado();
				}else{
					valor= null;
				}
				if (parametros === '{') {
					parametros += '\"'+ef+'\"'+':'+'\"'+valor+'\"';
				} else {
					parametros += ','+'\"'+ef+'\"'+':'+'\"'+valor+'\"';
				}
			}
			parametros = parametros + '}';
			if (tipo_salida == 'html'){
				
				var win = window.open('$principal/pivot.php?reporte='+reporte+'&proyecto=$proyecto&parametros='+parametros, '_blank');

				//Browser has allowed it to be opened
				win.focus();
				return false;
			}
			location.href = vinculador.get_url(null, null, 'generar_consulta', {
				path: '',
				reporte: reporte,
				tipo_salida: tipo_salida,
				parametros_mostrar: parametros_mostrar,
				parametros: parametros,
			});

			return false;
		}
		";
	}
}
