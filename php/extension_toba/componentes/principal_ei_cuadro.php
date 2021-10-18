<?php

class principal_ei_cuadro extends toba_ei_cuadro
{
	protected $exportacion_toba = false;

    function inicializar($parametros=array()) {
       $this->set_manejador_salida('html', 'intervan_ei_cuadro_salida_html');
       //$this->set_manejador_salida('excel', 'intervan_ei_cuadro_salida_excel');
        parent::inicializar($parametros);
    }

	public function set_exportacion_toba($valor) {
		$this->exportacion_toba = $valor;
	}
	
	protected function actualizar_acumulador_general($dato)
	{
		if(!isset($this->_acumulador))
		{
			return;
		}
		foreach(array_keys($this->_acumulador) as $columna)
		{
			$this->_acumulador[$columna] += strip_tags(
				$this->datos[$dato][$columna]
			);
		}
	}

	####################################
	## VISTAS DE EXPORTACION
	####################################

	public function vista_pdf(toba_vista_pdf $salida)
	{
		if ($this->exportacion_toba) {
			self::vista_pdf_intervan($salida);
			return;
		}
		// configuramos el nombre que tendrá el archivo
		$nombre = toba::memoria()->get_dato_instancia('exportar_archivo');
		$nombre = isset($nombre) && !empty($nombre) ? $nombre : 'archivo';
		$nombre .= '.pdf';

		$salida->set_nombre_archivo($nombre);

		// Si entra acá, significa que se quiere generar un PDF con un cuadro con cortes de control
		if ($this->controlador()->s__listados_activo)
		{
			$salida->titulo(
				$this->get_titulo(),
				count($this->_columnas)
			);
			$this->generar_salida('pdf', $salida);

			return;
		}

		$salida->set_nombre_archivo($nombre);

		//recuperamos el objteo ezPDF para agregar la cabecera y el pie de página
		$pdf = $salida->get_pdf();
		//modificamos los márgenes de la hoja top, bottom, left, right
		$pdf->ezSetMargins(80, 50, 30, 30);
		//Configuramos el pie de página. El mismo, tendra el número de página centrado en la página y la fecha ubicada a la derecha.
		//Primero definimos la plantilla para el número de página.
		$formato = 'Página {PAGENUM} de {TOTALPAGENUM}';
		//Determinamos la ubicación del número página en el pié de pagina definiendo las coordenadas x y, tamaño de letra, posición, texto, pagina inicio
		$pdf->ezStartPageNumbers(300, 20, 8, 'left', utf8_d_seguro($formato), 1);
		//Luego definimos la ubicación de la fecha en el pie de página.
		$pdf->addText(480,20,8,date('d/m/Y h:i:s a'));
		//Configuración de Título.
		$titulo_lista= $this->controlador()->get_titulo();
		$salida->titulo(utf8_d_seguro("$titulo_lista"));
		//Configuración de Subtítulo.
		//$salida->subtitulo(utf8_d_seguro("Listado de Cuentas"));
		//Invoco la salida pdf original del cuadro
		//parent::vista_pdf($salida);

		$filtro= $this->controlador()->get_filtro();

		if(!isset($filtro))
			$filtro= array();

		$orden= $this->controlador()->get_orden();

		if(!isset($orden))
			$orden= array();

		$metodo= $this->controlador()->get_clase_carga();
		if (isset($metodo)) {
			eval("\$datos = {$this->controlador()->get_clase_carga()}::{$this->controlador()->get_metodo_carga()}(\$filtro, \$orden);");

			//$datos= dao_cuentas::get_cuentas($filtro, $orden);

			$campos = toba::memoria()->get_dato_instancia('exportar_campos');

			$columnas= $this->get_columnas();
			//$keep = array('cuic', 'tipo_cuenta_fmt', 'nro_cuenta', 'estado_fmt', 'persona', 'barrio', 'calle', 'nro');
			$keep= array();
			if (!isset($campos)||empty($campos)){
				foreach ($columnas as $key => $value) {
					array_push($keep, $key);
				}
			}else{
				$keep= explode(',', $campos);
			}

			foreach ($datos as $key => $value) {
				foreach ($datos[$key] as $key2 => $value2) {
					if (!in_array($key2, $keep))
						unset($datos[$key][$key2]);
					$datos[$key][$key2] = strip_tags($value2); //Quita los tags html que puedan tener los datos
				}
			}

			$titulos= array();
			foreach ($keep as $key => $value) {
				$titulos[$value]= $columnas[$value]['titulo'];
			}

			//$titulos= array('cuic' => 'Cuic', 'tipo_cuenta_fmt' => 'Tipo Cuenta', 'nro_cuenta' => 'Nro de cuenta', 'estado_fmt' => 'Estado', 'persona' => 'Nombre Persona', 'barrio' => 'Barrio', 'calle' => 'Calle', 'nro' => 'Nro');
			$datos_salida= array('datos_tabla' => $datos, 'titulo_tabla' => '',
				'titulos_columnas' => $titulos);

			$salida->tabla( $datos_salida, true, 8,array());

			//Encabezado: Logo Organización - Nombre
			//Recorremos cada una de las hojas del documento para agregar el encabezado
			foreach ($pdf->ezPages as $pageNum=>$id){
				$pdf->reopenObject($id);
				//definimos el path a la imagen de logo de la organizacion
				$imagen = toba::proyecto()->get_path().'/www/img/logo.jpg';
				//agregamos al documento la imagen y definimos su posición a través de las coordenadas (x,y) y el ancho y el alto.
				$pdf->addJpegFromFile($imagen, 40, 760, 80, 80);
				//Agregamos el nombre de la organización a la cabecera junto al logo
				$bases_ini = toba_dba::get_bases_definidas();

				if (isset($bases_ini['reportes jasper']['p_municipio']))
					$municipio=$bases_ini['reportes jasper']['p_municipio'];
				else
					$municipio= dao_varios::valor_configuraciones('NOMBRE_MUNICIPIO');
				$pdf->addText(200,800,12,$municipio);
				$pdf->closeObject();
			}
		}else{
			parent::vista_pdf($salida);
		}
	}
	

	/**
	 * @param toba_vista_excel $salida
	 */
	
	public function vista_excel(toba_vista_excel $salida)
	{
		if ($this->exportacion_toba) {
			parent::vista_excel_intervan($salida);
			return;
		}
		//configuramos el nombre que tendrá el archivo
		$nombre = toba::memoria()->get_dato_instancia('exportar_archivo');
		$nombre = isset($nombre) && !empty($nombre) ? $nombre : 'archivo';
		$nombre .= '.xls';

		$salida->set_nombre_archivo($nombre);

		// Si entra acá, significa que se quiere generar un EXCEL con un cuadro con cortes de control
		if ($this->controlador()->s__listados_activo)
		{
			$salida->titulo(
				$this->get_titulo(),
				count($this->_columnas)
			);
			$this->generar_salida('excel', $salida);

			return;
		}

		$salida->set_tipo_salida('HTML');

		$filtro= $this->controlador()->get_filtro();

		if(!isset($filtro))
			$filtro= array();

		$orden= $this->controlador()->get_orden();

		if(!isset($orden))
			$orden= array();

		$metodo= $this->controlador()->get_clase_carga();
		if (isset($metodo)) {
			eval("\$datos = {$this->controlador()->get_clase_carga()}::{$this->controlador()->get_metodo_carga()}(\$filtro, \$orden);");


			$campos = toba::memoria()->get_dato_instancia('exportar_campos');

			$columnas= $this->get_columnas();
			//$keep = array('cuic', 'tipo_cuenta_fmt', 'nro_cuenta', 'estado_fmt', 'persona', 'barrio', 'calle', 'nro');
			$keep= array();

			if (!isset($campos)||empty($campos))
			{
				foreach ($columnas as $key => $value) {
					array_push($keep, $key);

					foreach ($datos as $key2 => $value2) {
						// Quita los tags html que puedan tener los datos
						if (isset($datos[$key2][$key])) {
							$datos[$key2][$key] = strip_tags($datos[$key2][$key]);
						}
					}
				}
			} else {
				$keep = explode(',', $campos);
			}

			$titulos = array();
			foreach ($keep as $key => $value) {
				$titulos[$value]= $columnas[$value]['titulo'];
			}

			$datos_salida = array();
			foreach ($datos as $key => $value) {
				$datos_salida[$key] = array();
				foreach ($keep as $index => $col) {
					
					//Zanitiza los datos reemplazando nulos por espacios.
					if (empty($datos[$key][$col]) || is_null($datos[$key][$col]))
					{
						$datos[$key][$col] = ' ';
					}

					if (isset($datos[$key][$col])) {
						$datos_salida[$key][$col] = strip_tags($datos[$key][$col]);
					}
				}
			}
			$salida->tabla($datos_salida, $titulos);
		} else {
			parent::vista_excel($salida);
		}
	}

	function analizar_cortes_excel()
	{
		$this->_salida_sin_cortes = toba::memoria()->get_parametro('es_plano');

		if ($this->_salida_sin_cortes)
		{
			$this->aplanar_cortes_control();
		}
	}


	private function agregar_columnas_perezoso($columnas)
	{
		$arreglo_default = array('estilo' => 'col-tex-p1', 'estilo_titulo' => 'ei-cuadro-col-tit', 'total_cc' => '',
			'total' => '0', 'usar_vinculo' => '0', 'no_ordenar' => '0', 'formateo' => null);

		foreach ($columnas as $clave => $valor) {
			$columnas[$clave] = array_merge($arreglo_default, $valor);
		}
		$this->_info_cuadro_columna = array_merge($this->_info_cuadro_columna, array_values($columnas));
	}

	protected function aplanar_cortes_control()
 	{
 		if (empty($this->_info_cuadro_cortes)) return;		// no hay nada que aplanar

 		$columnas = array();
 		foreach ($this->_info_cuadro_cortes as $cortes) {
 			$ids = explode(',', $cortes['columnas_id']);
 			foreach ($ids as $id) {
 				$columna = array(
 					'clave'  => $id,
 					'titulo' => $id,
 					'formateo' => 'forzar_cadena'
 				);
 				$columnas[] = $columna;
 			}
 		}
		$this->agregar_columnas_perezoso($columnas);
 	}


 	public function vista_pdf_intervan(toba_vista_pdf $salida)
	{
		//configuramos el nombre que tendrá el archivo
		$nombre = toba::memoria()->get_dato_instancia('exportar_archivo');
		$nombre = isset($nombre) && !empty($nombre) ? $nombre : 'archivo';
		$nombre .= '.pdf';

		$salida->set_nombre_archivo($nombre);

		// Si entra acá, significa que se quiere generar un PDF con un cuadro con cortes de control
		if ($this->controlador()->s__listados_activo)
		{
			$salida->titulo($this->get_titulo(), $this->controlador()->s__listados_titulo_columns);
			$this->generar_salida('pdf', $salida);

			return;
		}

		$salida->set_nombre_archivo($nombre);

		//recuperamos el objteo ezPDF para agregar la cabecera y el pie de página
		$pdf = $salida->get_pdf();
		//modificamos los márgenes de la hoja top, bottom, left, right
		$pdf->ezSetMargins(80, 50, 30, 30);
		//Configuramos el pie de página. El mismo, tendra el número de página centrado en la página y la fecha ubicada a la derecha.
		//Primero definimos la plantilla para el número de página.
		$formato = 'Página {PAGENUM} de {TOTALPAGENUM}';
		//Determinamos la ubicación del número página en el pié de pagina definiendo las coordenadas x y, tamaño de letra, posición, texto, pagina inicio
		$pdf->ezStartPageNumbers(300, 20, 8, 'left', utf8_d_seguro($formato), 1);
		//Luego definimos la ubicación de la fecha en el pie de página.
		$pdf->addText(480,20,8,date('d/m/Y h:i:s a'));
		//Configuración de Título.
		$titulo_lista= $this->controlador()->get_titulo();
		$salida->titulo(utf8_d_seguro("$titulo_lista
			"));
		//Configuración de Subtítulo.
		//$salida->subtitulo(utf8_d_seguro("Listado de Cuentas"));
		//Invoco la salida pdf original del cuadro
		//parent::vista_pdf($salida);

		$filtro= $this->controlador()->get_filtro();

		if(!isset($filtro))
			$filtro= array();

		$orden= $this->controlador()->get_orden();

		if(!isset($orden))
			$orden= array();

		$metodo= $this->controlador()->get_clase_carga();
		if (isset($metodo)) {
			eval("\$datos = {$this->controlador()->get_clase_carga()}::{$this->controlador()->get_metodo_carga()}(\$filtro, \$orden);");

			//$datos= dao_cuentas::get_cuentas($filtro, $orden);

			$campos = toba::memoria()->get_dato_instancia('exportar_campos');

			$columnas= $this->get_columnas();
			//$keep = array('cuic', 'tipo_cuenta_fmt', 'nro_cuenta', 'estado_fmt', 'persona', 'barrio', 'calle', 'nro');
			$keep= array();
			if (!isset($campos)||empty($campos)){
				foreach ($columnas as $key => $value) {
					array_push($keep, $key);
				}
			}else{
				$keep= explode(',', $campos);
			}

			foreach ($datos as $key => $value) {
				foreach ($datos[$key] as $key2 => $value2) {
					if (!in_array($key2, $keep))
						unset($datos[$key][$key2]);
					$datos[$key][$key2] = strip_tags($value2); //Quita los tags html que puedan tener los datos
				}
			}

			$titulos= array();
			foreach ($keep as $key => $value) {
				$titulos[$value]= $columnas[$value]['titulo'];
			}

			//$titulos= array('cuic' => 'Cuic', 'tipo_cuenta_fmt' => 'Tipo Cuenta', 'nro_cuenta' => 'Nro de cuenta', 'estado_fmt' => 'Estado', 'persona' => 'Nombre Persona', 'barrio' => 'Barrio', 'calle' => 'Calle', 'nro' => 'Nro');
			$datos_salida= array('datos_tabla' => $datos, 'titulo_tabla' => '',
				'titulos_columnas' => $titulos);

			$salida->tabla( $datos_salida, true, 8,array());

			//Encabezado: Logo Organización - Nombre
			//Recorremos cada una de las hojas del documento para agregar el encabezado
			foreach ($pdf->ezPages as $pageNum=>$id){
				$pdf->reopenObject($id);
				//definimos el path a la imagen de logo de la organizacion
				$imagen = toba::proyecto()->get_path().'/www/img/logo.jpg';
				//agregamos al documento la imagen y definimos su posición a través de las coordenadas (x,y) y el ancho y el alto.
				$pdf->addJpegFromFile($imagen, 40, 760, 80, 80);
				//Agregamos el nombre de la organización a la cabecera junto al logo
				$bases_ini = toba_dba::get_bases_definidas();

				if (isset($bases_ini['reportes jasper']['p_municipio']))
					$municipio=$bases_ini['reportes jasper']['p_municipio'];
				else
					$municipio= dao_varios::valor_configuraciones('NOMBRE_MUNICIPIO');
				$pdf->addText(200,800,12,$municipio);
				$pdf->closeObject();
			}
		}else{
			parent::vista_pdf($salida);
		}
	}

	private function vista_excel_intervan(toba_vista_excel $salida)
    {
        //configuramos el nombre que tendrá el archivo
        $nombre = toba::memoria()->get_dato_instancia('exportar_archivo');
        $nombre = isset($nombre) && !empty($nombre) ? $nombre : 'archivo';
        $nombre .= '.xls';

        $salida->set_nombre_archivo($nombre);

        // Si entra acá, significa que se quiere generar un EXCEL con un cuadro con cortes de control
        if ($this->controlador()->s__listados_activo)
        {
            $salida->titulo($this->get_titulo(), $this->controlador()->s__listados_titulo_columns);
            $this->generar_salida('excel', $salida);

            return;
        }

        $salida->set_tipo_salida('HTML');

        $filtro= $this->controlador()->get_filtro();

        if(!isset($filtro))
            $filtro= array();

        $orden= $this->controlador()->get_orden();

        if(!isset($orden))
            $orden= array();

        $metodo= $this->controlador()->get_clase_carga();
        if (isset($metodo)) {
            eval("\$datos = {$this->controlador()->get_clase_carga()}::{$this->controlador()->get_metodo_carga()}(\$filtro, \$orden);");


            $campos = toba::memoria()->get_dato_instancia('exportar_campos');

            $columnas= $this->get_columnas();
            //$keep = array('cuic', 'tipo_cuenta_fmt', 'nro_cuenta', 'estado_fmt', 'persona', 'barrio', 'calle', 'nro');
            $keep= array();
            if (!isset($campos)||empty($campos))
            {
                foreach ($columnas as $key => $value) {
                    array_push($keep, $key);
                }
            } else {
                $keep= explode(',', $campos);
            }
            
            $titulos= array();
            foreach ($keep as $key => $value) {
                $titulos[$value]= $columnas[$value]['titulo'];
            }
            
            $datos_salida= array();
            foreach ($datos as $key => $value) {
                $datos_salida[$key]= array();
                foreach ($keep as $index => $col) {
                    $datos_salida[$key][$col]= strip_tags($datos[$key][$col]);
                }
            }
            $salida->tabla($datos_salida, $titulos);
        } else {
            parent::vista_excel($salida);
        }
    }

    protected function generar_salida($tipo, $objeto_toba_salida = null)
	{
		if($tipo !== 'html'
			&& $tipo !== 'impresion_html'
			&& $tipo !== 'pdf'
			&& $tipo !== 'excel'
			&& $tipo !== 'xml')
		{
			throw new toba_error_seguridad("El tipo de salida '$tipo' es invalida");
		}
		$this->_tipo_salida = $tipo;
		$this->instanciar_manejador_tipo_salida($tipo);

		if (! is_null($objeto_toba_salida))
		{
			//Si se esta usando una clase particular de toba para la salida
			$this->_salida->set_instancia_toba_salida($objeto_toba_salida);
		}

		if ($this->datos_cargados())
		{
			$this->inicializar_generacion();
			$this->generar_inicio();
			//Generacion de contenido
			if ($this->existen_cortes_control())
			{
				$this->generar_cortes_control();
			} else {
				$filas = array_keys($this->datos);
				$this->generar_cuadro($filas, $this->_acumulador);
			}
			$this->generar_fin();
			if (false && $this->existen_cortes_control())
			{
				ei_arbol($this->_sum_usuario, '$this->_sum_usuario');
				ei_arbol($this->_cortes_def, '$this->_cortes_def');
				ei_arbol($this->_cortes_control, '$this->_cortes_control');
			}
		} else {
			if ($this->_info_cuadro['eof_invisible'] != 1)
			{
				if(trim($this->_info_cuadro['eof_customizado']) !== ''){
					$texto = $this->_info_cuadro['eof_customizado'];
				}else{
					$texto = 'No hay datos cargados';
				}
				$this->generar_mensaje_cuadro_vacio($texto);
			}
		}
	}

}
