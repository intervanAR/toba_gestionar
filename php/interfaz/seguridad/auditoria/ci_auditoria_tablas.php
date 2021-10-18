<?php
class ci_auditoria_tablas extends ci_abm_complejo_listado
{
	protected $clase_carga = 'dao_auditoria_tablas';
	protected $metodo_carga_cantidad = 'get_cantidad_registros_auditados';
	protected $metodo_carga = 'get_registros_auditoria';
	public $colapsar_filtro = false; // Indica si se colapsa el filtro cada vez que pasa por el conf del mismo
	protected $s__tabla;
	protected $s__filtro_tabla = array();
	protected $s__datos_auditoria;
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_tablas(toba_ei_pantalla $pantalla)
	{
		if (!isset($this->s__tabla) && $pantalla->existe_dependencia('formulario')) {
				$pantalla->eliminar_dep('formulario');
				$pantalla->eliminar_evento('auditar');
				$pantalla->eliminar_evento('no_auditar');
				$pantalla->eliminar_evento('cancelar');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__auditar()
	{
		if (isset($this->s__datos_auditoria) && isset($this->s__filtro_tabla) && !empty($this->s__filtro_tabla)) {
			dao_auditoria_tablas::crear_auditoria($this->s__filtro_tabla['modulo'], $this->s__tabla, $this->s__datos_auditoria['operacion'], $this->s__datos_auditoria['campo']);
		}
		$this->evt__cancelar();
	}

	function evt__no_auditar()
	{
		if (isset($this->s__filtro_tabla) && !empty($this->s__filtro_tabla)) {
			dao_auditoria_tablas::eliminar_auditoria($this->s__filtro_tabla['modulo'], $this->s__tabla);
			$this->evt__cancelar();
		}
	}
	
	function evt__cancelar()
	{
		unset($this->s__tabla);
		unset($this->s__datos_auditoria);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_tablas ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_tablas(principal_ei_cuadro $cuadro)
	{
		$cuadro->set_exportacion_toba(true);
		if (isset($this->s__filtro_tabla) && !empty($this->s__filtro_tabla)) {
			$filtro = $this->s__filtro_tabla;
			$datos = dao_auditoria_tablas::get_tablas($filtro);
			$cuadro->set_datos($datos);
			if (isset($this->s__tabla)) {
				$cuadro->eliminar_evento('seleccion');
				if ($cuadro->existe_evento('eliminar_aud')) $cuadro->eliminar_evento('eliminar_aud');
				if ($this->dependencia('filtro_tabla')->existe_evento('filtrar')) $this->dependencia('filtro_tabla')->evento('filtrar')->ocultar();
				if ($this->dependencia('filtro_tabla')->existe_evento('cancelar')) $this->dependencia('filtro_tabla')->evento('cancelar')->ocultar();
			}
		}
	}

	function evt__cuadro_tablas__seleccion($seleccion)
	{
		$this->s__tabla = $seleccion['tabla'];
	}
	
	function evt__cuadro_tablas__eliminar_aud($seleccion)
	{
		$this->evt__cuadro_tablas__seleccion($seleccion);
		$this->evt__no_auditar();
		$this->dep('cuadro_tablas')->deseleccionar();
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(principal_ei_formulario $form)
	{
		if (isset($this->s__filtro_tabla) && !empty($this->s__filtro_tabla)) {
			$datos = dao_auditoria_tablas::datos_trigger_x_tabla($this->s__filtro_tabla['modulo'], $this->s__tabla);
			if (!$datos['auditada']) {
				$this->evento('no_auditar')->ocultar();
			}
			$form->set_datos($datos);
		}
	}

	function evt__formulario__modificacion($datos)
	{
		$this->s__datos_auditoria = $datos;
	}
	
	function get_campos_tabla()
	{
		return dao_auditoria_tablas::get_campos_tabla($this->s__filtro_tabla['modulo'], $this->s__tabla);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_tabla -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_tabla(principal_ei_formulario $form)
	{
		if (!empty($this->s__filtro_tabla)) $form->set_datos($this->s__filtro_tabla);
	}

	function evt__filtro_tabla__filtrar($datos)
	{
		$this->s__filtro_tabla = $datos;
		$this->dep('cuadro_tablas')->set_pagina_actual(1);
	}

	function evt__filtro_tabla__cancelar()
	{
		$this->s__filtro_tabla = array();
		$this->dep('cuadro_tablas')->set_pagina_actual(1);
	}
	
	function get_tablas_auditadas($nombre, $modulo) {
		return dao_auditoria_tablas::get_tablas(array('modulo' => $modulo, 'tabla' => $nombre,'auditada' => 'S'));
	}
}
?>