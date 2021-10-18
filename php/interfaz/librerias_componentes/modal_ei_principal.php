<?php

class modal_ei_principal extends principal_ei_formulario
{
	protected $titulo;

	public function setTitulo($titulo = ''){
		$this->titulo = $titulo;
	}

	function generar_html()
	{
		$titulo = $this->_info['titulo'];
		echo "<div id='{$this->_id[1]}' class='modalForm'>";
			echo "<div class='main_content_intervan'>";
				echo "<div class='sub_content_intervan'>";
					echo "<div class='titulo_sub_content_intervan'>{$this->titulo}</div>";
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
		parent::extender_objeto_js();

		echo "
		
		/**
		 * Funciones básicas del modal.
		 *
		*/
		{$this->objeto_js}.mostrarModal = function()
		{
			document.getElementById('{$this->_id[1]}').setAttribute('class','modalForm modalFormAct');
		}

		{$this->objeto_js}.ocultarModal = function()
		{
			document.getElementById('{$this->_id[1]}').setAttribute('class','modalForm');
		}

		
		{$this->objeto_js}.setParametros = function (objs)
		{
			var efs = this.efs();
			for (let elem in objs) {  
				try{
					this.ef(elem).set_estado(objs[elem]);
				}catch(err){
					console.log(err);
				}
			}
		}

		{$this->objeto_js}.limpiar_campos = function ()
		{
			var efs = this.efs();
			for (let elem in efs) {  
				try{
					this.ef(elem).resetear_estado();
					this.ef(elem).no_resaltar();
					this.ef(elem).resetear_error();
				}catch(err){
					console.log(err);
				}
			}
		}

		{$this->objeto_js}.evt__cancelar = function ()
		{
			this.limpiar_campos();
			this.ocultarModal();
			return false;
		}	
		";
	}

}