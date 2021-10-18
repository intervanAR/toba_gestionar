<?php

class ci_abm_complejo_listado extends principal_ci
{
	protected $s__seleccion;

	// Clase para la carga del cuadro
	protected $clase_carga = '';
	// Metodo para obtener los datos del cuadro
	protected $metodo_carga = '';
	// Metodo para obtener la cantidad de datos del cuadro
	protected $metodo_carga_cantidad = '';
	protected $s__ordenar;

	// dependencia CI de edicion
	protected $ci_abm_complejo_edicion = '';

	// Datos tabla de la tabla encabezado
	public $dt_encabezado = '';
	// Datos tabla de la tabla detalle
	public $dt_detalle = '';
	// Datos tabla de la tabla de imputacion presupuestaria
	public $dt_imputacion_pre = '';
	// Datos tabla de la tabla de imputacion por centro de costos
	public $dt_imputacion_cos = '';

	// campo de la tabla maestra que indica la clave del comprobante
	public $campo_id_comprobante = '';
	// campo de la tabla maestra que indica que esta aprobado el comprobante
	public $campo_aprobado = '';
	// campo de la tabla maestra que indica que esta anulado el comprobante
	public $campo_anulado = '';
	// Indica si se formateara el cuadro con funciones de php
	public $formateo_php = true;
	// Indica si se colapsa el filtro cada vez que pasa por el conf del mismo
	public $colapsar_filtro = true;

	// Indica el color que tomara fila aprobada en el cuadro. rgb(0, 150, 0)
	public $color_aprobado = 'rgb(0, 150, 0)';
	// Indica el color que tomara fila anulado en el cuadro.  rgb(204, 0, 0)
	public $color_anulado = 'rgb(204, 0, 0)';

	public $s__eventos_onload;

	////////////////////////////////////////
	// Datos para la generación de listados
	// con cortes de control
	////////////////////////////////////////
	public $s__listados_activo = false;

	public $permite_eliminar = false;

	////////////////////////////////////////
	// Self
	////////////////////////////////////////
	public function ini__operacion()
	{
		parent::ini__operacion();
		toba::memoria()->eliminar_dato_instancia('exportar_campos');
		toba::memoria()->eliminar_dato_instancia('exportar_archivo');
	}

	public function ini()
	{
		parent::ini();
		$this->set_titulo_operacion();

		if ($this->existe_dependencia('filtro')) {
			$this->dep('filtro')->set_titulo("<div class='cont-titulo-filtro'><span class='tit-filtro'>Filtro</span></div>");
		}
	}

	public function evt__agregar()
	{
		$this->set_pantalla('pant_edicion');
	}

	public function evt__cancelar()
	{
		$this->s__onload = null;
		if (!isset($this->s__aplicacion_origen) || empty($this->s__aplicacion_origen)) {
			$this->resetear();
		}
	}

	public function evt__cancelar_edicion()
	{
		$this->resetear_y_cargar();
	}

	public function evt__eliminar()
	{
		try {
			$this->relacion()->eliminar_todo();
			$this->resetear();
		} catch (toba_error $e) {
			$this->resetear_y_cargar();

			throw $e;
		}
	}

	public function evt__guardar()
	{
		$this->guardar();
		$this->resetear_y_cargar();
	}

	////////////////////////////////////////
	// pant_inicial
	////////////////////////////////////////
	public function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		if (isset($this->s__filtro['ids_comprobantes'])) {
			if ($pantalla->existe_dependencia('filtro')) {
				$pantalla->eliminar_dep('filtro');
			}
		} else {
			if (!$pantalla->existe_dependencia('filtro')) {
				$pantalla->agregar_dep('filtro');
			}
		}

		if (($pantalla->existe_evento('exportar')) && ($this->existe_dependencia('cuadro'))) {
			$pantalla->evento('exportar')->vinculo()->agregar_parametro('origen', 'cuentas');

			$columnas = $this->dep('cuadro')->get_columnas();

			$campos = '';
			foreach ($columnas as $key => $value) {
				$campos .= "$key,".$columnas[$key]['titulo'].',';
			}

			$pantalla->evento('exportar')->vinculo()->agregar_parametro('campos', $campos);
			$pantalla->evento('exportar')->ocultar();
		}
		/*
		 * Feedback al usuario.
		 */
		$msj = toba::memoria()->get_dato('feedback');
		$tipo_notificacion = toba::memoria()->get_dato('tipo_notificacion');
		if (!empty($msj)) {
			if (!empty($tipo_notificacion) && strtolower($tipo_notificacion) == 'modal') {
				toba::notificacion()->info($msj);
				toba::memoria()->eliminar_dato('tipo_notificacion');
			} else {
				$this->pantalla()->set_descripcion($msj.'<br>');
			}
			toba::memoria()->eliminar_dato('feedback');
		}
	}

	////////////////////////////////////////
	// pant_edicion
	////////////////////////////////////////
	public function conf__pant_edicion(toba_ei_pantalla $pantalla)
	{
		if (!isset($this->s__id_comprobante) || empty($this->s__id_comprobante)) {
			if (!$pantalla->existe_evento('cancelar')) {
				$pantalla->agregar_evento('cancelar');
			}
		} else {
			if (isset($this->s__aplicacion_origen) && !empty($this->s__aplicacion_origen)) {
				if ($pantalla->existe_evento('cancelar')) {
					$pantalla->eliminar_evento('cancelar');
				}
			}
		}

		if ($pantalla->existe_evento('eliminar') && !$this->permite_eliminar) {
			$pantalla->evento('eliminar')->anular();
		}
	}

	////////////////////////////////////////
	// filtro
	////////////////////////////////////////
	public function conf__filtro($form)
	{
		$datos = [];
		if (isset($this->s__filtro) && !empty($this->s__filtro)) {
			$datos = $this->s__filtro;
		}
		if ($this->colapsar_filtro) {
			$form->colapsar();
		}

		return $datos;
	}

	public function evt__filtro__filtrar($datos)
	{
		$this->s__filtro = array_merge($this->s__filtro, $datos);
		if ($this->existe_dependencia('cuadro')) {
			$this->dep('cuadro')->set_pagina_actual(1);
		}
	}

	public function evt__filtro__cancelar()
	{
		$this->s__filtro = [];
		if ($this->existe_dependencia('cuadro')) {
			$this->dep('cuadro')->set_pagina_actual(1);
		}
	}

	////////////////////////////////////////
	// cuadro
	////////////////////////////////////////
	public function conf__cuadro($cuadro)
	{
		$datos = [];

		//Si las variables necesarias estan seteadas en la subclase.
		if (isset($this->clase_carga) && isset($this->metodo_carga)) {
			$filtro_consulta = $this->s__filtro;
			$ordenar = $this->s__ordenar;

			if ($cuadro->get_tipo_paginado() === 'C') {
				$tamanio_pagina = $cuadro->get_tamanio_pagina();
				$numrow_desde = ($cuadro->get_pagina_actual() - 1) * $tamanio_pagina;
				$numrow_hasta = $cuadro->get_pagina_actual() * $tamanio_pagina;

				if (!isset($this->metodo_carga_cantidad) || empty($this->metodo_carga_cantidad)) {
					$total_registros = dao_general::get_cuadro_total_paginas($this->dt_encabezado, $filtro_consulta);
					$filtro_consulta['numrow_desde'] = $numrow_desde;
					$filtro_consulta['numrow_hasta'] = $numrow_hasta;
				} else {
					eval("\$total_registros = {$this->clase_carga}::{$this->metodo_carga_cantidad}(\$filtro_consulta);");
					$filtro_consulta['numrow_desde'] = $numrow_desde;
					$filtro_consulta['numrow_hasta'] = $numrow_hasta;
				}

				$cuadro->set_total_registros($total_registros);
			}

			if (isset($filtro_consulta)) {
				eval("\$datos = {$this->clase_carga}::{$this->metodo_carga}(\$filtro_consulta, \$ordenar);");
			} elseif (!isset($this->cargar_sin_filtrar) || ($this->cargar_sin_filtrar === true)) {
				eval("\$datos = {$this->clase_carga}::{$this->metodo_carga}(null, \$ordenar);");
			}
		} else {
			toba::notificacion()->agregar('Falta setear la clase y el método de carga del cuadro');
		}
		if ($this->formateo_php) {
			$funciones_basicas = new ctr_funciones_basicas();
			$funciones_basicas->set_color_anulado($this->color_anulado);
			$funciones_basicas->set_color_aprobado($this->color_aprobado);

			return $funciones_basicas->formatear_cuadro($datos, $this->campo_aprobado, $this->campo_anulado);
		} else {
			return $datos;
		}
	}

	public function evt__cuadro__seleccion($seleccion)
	{
		foreach ($seleccion as $clave => $valor) {
			$seleccion[$clave] = strip_tags($valor);
		}
		$this->s__seleccion = $seleccion;
		$this->relacion()->cargar($this->s__seleccion);
		$this->setear_onload('addLoadEvent('.$this->s__eventos_onload.');');
		$this->set_pantalla('pant_edicion');
	}

	public function evt__cuadro__eliminar($seleccion)
	{
		$this->relacion()->cargar($seleccion);
		$this->evt__eliminar();
	}

	public function evt__cuadro__ordenar($param)
	{
		$this->s__ordenar = $param;
	}

	////////////////////////////////////////
	// auxiliares
	////////////////////////////////////////
	public function relacion()
	{
		return $this->dep('relacion');
	}

	/**
	 * TODO Eliminar todas las funciones
	 *	  que sobreescriben esta función.
	 */
	public function get_clave_relacion()
	{
		if (!isset($this->s__seleccion) || empty($this->s__seleccion)) {
			return;
		}

		return $this->s__seleccion;
	}

	public function set_clave_relacion($valor)
	{
		if (isset($this->campo_id_comprobante) && !empty($this->campo_id_comprobante) && isset($valor)) {
			$this->s__seleccion = [$this->campo_id_comprobante => $valor];
		}
	}

	public function tabla($nombre)
	{
		return $this->relacion()->tabla($nombre);
	}

	public function resetear()
	{
		if ($this->relacion()->esta_cargada()) {
			$this->relacion()->resetear();
		}
		$this->set_pantalla('pant_inicial');
		unset($this->s__seleccion);
		$this->dep($this->ci_abm_complejo_edicion)->resetear_cursor_detalle();
	}

	public function resetear_seleccion()
	{
		if ($this->relacion()->esta_cargada()) {
			$this->relacion()->resetear();
		}
		unset($this->s__seleccion);
		$this->dep($this->ci_abm_complejo_edicion)->resetear_cursor_detalle();
	}

	public function guardar()
	{
		$this->relacion()->sincronizar();

		$datos_comprobante = $this->tabla($this->dt_encabezado)->get();
		$claves_comprobante = $this->controlador()->tabla($this->controlador()->dt_encabezado)->get_clave();
		$this->s__seleccion = [];
		foreach ($claves_comprobante as $clave_comprobante) {
			$this->s__seleccion[$clave_comprobante] = $datos_comprobante[$clave_comprobante];
		}

		if (isset($this->controlador()->dt_detalle) && !empty($this->controlador()->dt_detalle) && $this->controlador()->tabla($this->controlador()->dt_detalle)->hay_cursor()) {
			$this->controlador()->tabla($this->controlador()->dt_detalle)->resetear_cursor();
		}
	}

	public function resetear_y_cargar()
	{
		$this->dep($this->ci_abm_complejo_edicion)->resetear_cursor_detalle();
		$this->relacion()->resetear();
		if (isset($this->s__seleccion)) {
			$this->relacion()->cargar($this->s__seleccion);
		}
	}

	public function get_campo_id_comprobante()
	{
		if (!isset($this->campo_id_comprobante) || empty($this->campo_id_comprobante)) {
			toba::notificacion()->error('Falta setear el campo clave de la tabla maestra.');
		} else {
			return $this->campo_id_comprobante;
		}
	}

	public function get_metodo_carga()
	{
		return $this->metodo_carga;
	}

	public function get_clase_carga()
	{
		return $this->clase_carga;
	}

	public function get_orden()
	{
		return $this->s__ordenar;
	}

	public function get_seleccion($formato = 'php')
	{
		if ($formato == 'json') {
			return json_encode($this->s__seleccion);
		} else {
			return $this->s__seleccion;
		}
	}
}
