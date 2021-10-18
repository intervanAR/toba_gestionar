<?php

/**
 * Este tipo de página incluye una cabecera con:
 *  - Menú
 *  - Logo
 *  - Información básica del usuario logueado
 *  - Capacidad de cambiar de proyecto
 *  - Capacidad de desloguearse
 * @package SalidaGrafica
 */
class tp_intervan_normal extends toba_tp_basico_titulo
{
	protected $menu;
	protected $ini;
	protected $menu_vertical = false;
	protected $alto_cabecera = "34px";
	private $oper_popup_desde_menu = array(
		'administracion' => array(
			"'12000146'", // principal
		),
		'compras' => array(
			"'1000329'", // principal
		),
		'contabilidad' => array(
			"'12000153'", // principal
		),
		'costos' => array(
			"'1000340'", // principal
		),
		'presupuesto' => array(
			"'12000159'", // principal
		),
		'rentas' => array(
			"'12000160'", // principal
		),
		'rrhh' => array(
			"'1000341'", // principal
		),
		'sociales' => array(
			"'12000200'", // principal
		),
		'usuarios_ldap' => array(
			"'1000356'", // principal
		),
		'ventas_agua' => array(
		"'12000258'", // principal
		),
		'principal' => array(
			"'12000103'", // modulo administracion
			"'12000112'", // modulo presupuesto
			"'12000113'", // modulo contabilidad
			"'12000114'", // modulo compras
			"'12000115'", // modulo costos
			"'12000116'", // modulo rentas
			"'12000117'", // modulo RRHH
			"'12000201'", // modulo sociales
			"'12000257'", // modulo ventas agua
			"'12000252'", // modulo usuarios LDAP
		),
	);
	private $oper_popup_nuevo_target = array(
		"'12000103'", // modulo administracion
		"'12000112'", // modulo presupuesto
		"'12000113'", // modulo contabilidad
		"'12000114'", // modulo compras
		"'12000115'", // modulo costos
		"'12000116'", // modulo rentas
		"'12000117'", // modulo RRHH
		"'12000201'", // modulo sociales
		"'12000257'", // modulo ventas agua
		//"'12000252'", // modulo usuarios LDAP
	);

	function __construct()
	{
		$this->menu = toba::menu();
		$archivo = toba::nucleo()->toba_instalacion_dir().'/instalacion.ini';
		$this->ini = parse_ini_file($archivo, true);

		if (!dao_usuarios_ldap::posee_acceso_sistema_por_IP()) {
            die("No es posible acceder al sistema desde la IP actual");
        }
		$datos_instancia = toba::instancia()->get_datos_instancia(toba::instancia()->get_id());
		$proyecto = toba::proyecto()->get_id();
		$this->menu_vertical = isset($datos_instancia[$proyecto]['menu_vertical']) && $datos_instancia[$proyecto]['menu_vertical'];
	}

	function encabezado()
	{
		parent::encabezado();
		echo "</div>\n<div id='botonera' class= 'botonera_nueva'> </div>";
	}

	protected function comienzo_cuerpo()
	{
		parent::comienzo_cuerpo();
		$muestra_menu = toba::memoria()->get_dato('muestra_menu');
		if (!isset($muestra_menu) || $muestra_menu == '1') {
			$this->menu();
			$this->cabecera_aplicacion();
		}
	}

	protected function menu()
	{
		if (isset($this->menu)) {
			$this->menu->mostrar();
		}
	}

	protected function plantillas_css()
	{
		if (isset($this->menu)) {
			$estilo = $this->menu->plantilla_css();
			if ($estilo != '') {
				echo toba_recurso::link_css($estilo, 'screen', false);
			}
		}
		parent::plantillas_css();
		echo $this->get_plantilla_css_menu_vertical();
		echo $this->get_html_css_intervan();
	}

	protected function cabecera_aplicacion()
	{
		if ( toba::proyecto()->get_parametro('requiere_validacion') ) {
			//--- Salir
			$js = toba_editor::modo_prueba() ? 'window.close()' : 'salir()';
			echo '<a href="#" class="enc-salir" title="Cerrar la sesión" onclick="javascript:'.$js.'">';
			echo toba_recurso::imagen_toba('finalizar_sesion.gif', true, null, null, 'Cerrar la sesión');
			echo '</a>';
			//--- Usuario
			$this->info_usuario();
		}

		$muestra = toba::proyecto()->get_parametro('proyecto', 'mostrar_resize_fuente', false);
		if (! is_null($muestra) && $muestra) {
			$this->mostrar_resize_fuente();
		}

		//--- Proyecto
		if(toba::proyecto()->es_multiproyecto()) {
			$this->cambio_proyecto();
		}
		if (toba::proyecto()->permite_cambio_perfiles()) {
			$this->cambio_perfil();
		}

		//--- Logo
		echo "<div id='enc-logo' style='height:{$this->alto_cabecera}'>";
		$this->mostrar_logo();
		echo "</div>\n";

		$tag_hasher = new tag_hasher('principal');

		$tag_hasher->js('js/jquery-3.3.1.min.js');
		$tag_hasher->js('js/jquery-ui.min.js');
		$tag_hasher->js('js/custom.js');
		$tag_hasher->js('js/ajuste_menu_scroll.js');
		$tag_hasher->js('js/moment/moment.js');
		$tag_hasher->js('js/highlight-5.js');
		$tag_hasher->js('js/jquery.inputmask.bundle.min.js');
		$tag_hasher->js('js/prism.js');
		$tag_hasher->js('js/jstree.min.js');
		$tag_hasher->js('js/tabulator.min.js');
	}

	/**
	 * Genera el HTML que posibilita cambiar entre procesos
	 * @ventana
	 */
	protected function cambio_proyecto()
	{
		$proyectos = toba::instancia()->get_proyectos_accesibles();
		$actual = toba::proyecto()->get_id();
		if (count($proyectos) > 1) {
			//-- Si hay al menos dos proyectos
			echo '<div class="enc-cambio-proy">';
			echo '<a href="#" title="Ir a la inicio" onclick="vinculador.ir_a_proyecto(\''.$actual.'\');">'.
					toba_recurso::imagen_toba("home.png",true).'</a>';
			$datos = rs_convertir_asociativo($proyectos, array(0), 1);
			echo toba_form::select(apex_sesion_qs_cambio_proyecto, $actual,
								$datos, 'ef-combo', 'onchange="vinculador.ir_a_proyecto(this.value)"');
			echo toba_js::abrir();
			echo 'var url_proyectos = '.toba_js::arreglo(toba::instancia()->get_url_proyectos(array_keys($datos)), true);
			echo toba_js::cerrar();
			echo '</div>';
		}
	}

	function cambio_perfil()
	{
		$perfiles = toba::instancia()->get_datos_perfiles_funcionales_usuario_proyecto( toba::usuario()->get_id(), toba::proyecto()->get_id());
		if (count($perfiles) > 1) {
			//-- Si hay al menos dos perfiles funcionales
			echo '<div class="enc-cambio-proy">';
			$perfiles[] = array('grupo_acceso' => apex_ef_no_seteado, 'nombre' => ' Todos ' );
			$datos = rs_convertir_asociativo($perfiles, array('grupo_acceso' ), 'nombre');
			$actual = toba::memoria()->get_dato('usuario_perfil_funcional_seleccionado');
			if (is_null($actual)) {
				$actual = apex_ef_no_seteado;
			}
			echo toba_form::abrir('chng_profile', toba::vinculador()->get_url());
			echo toba_form::select(apex_sesion_qs_cambio_pf, $actual, $datos, 'ef-combo', 'onchange="submit();"');
			echo toba_form::cerrar();
			echo '</div>';
		}
	}

	protected function mostrar_logo()
	{
		echo toba_recurso::imagen_proyecto('logo.gif', true);
	}

	protected function info_usuario()
	{
		echo '<div class="enc-usuario">';
		echo "<span class='enc-usuario-nom'>".texto_plano(toba::usuario()->get_nombre())."</span>";
		echo "<span class='enc-usuario-id'>".texto_plano(toba::usuario()->get_id())."</span>";
		echo '</div>';
	}

	protected function info_version()
	{
		$version = toba::proyecto()->get_version();
		if( $version ) {
			$texto = '<strong>' . ucfirst(toba::proyecto()->get_id()) . '</strong> - Versión '.((!toba::instalacion()->es_produccion())?'de desarrollo ':''). '<strong>' . $version .'</strong>';
			$info = "class='enc-version'";
			echo "<div $info >";
			if (isset($this->ini['url_changelog']) && !empty($this->ini['url_changelog']) && $this->es_proyecto_valido(toba::proyecto()->get_id())) {
				$url = $this->ini['url_changelog'] . '?proyecto=' . toba::proyecto()->get_id() . '&version=' . $version;
				echo '<a href="' . $url . '" target="changelog_' . toba::proyecto()->get_id() . '" style="text-decoration:none;color:white;">' . $texto . '</a>';
			} else {
				echo $texto;
			}
			echo '</div>';
			if (toba::proyecto()->get_id() == 'rrhh'){
				$id_entidad = dao_usuarios_logueado::get_entidad_rrhh();
				$ent_desc= dao_entidades_rrhh::get_lov_entidades_x_codigo($id_entidad);
				$id_organizacion = dao_usuarios_logueado::get_organizacion_usuario_rrhh();
				$org_desc= dao_organizaciones_rrhh::get_lov_organizacion_x_codigo($id_organizacion);

				echo "<div style='float: right; margin-right: 60px; margin-top: 5px;'>";
				echo "<span> Entidad: $ent_desc - Org.: $org_desc</span>";
				echo "</div>";
			}
			if (toba::proyecto()->get_id() == 'ventas_agua'){
                                $id_sucursal=dao_usuarios_ventas_agua::get_usuarios_x_usuario(strtoupper (toba::usuario()->get_id()))['id_sucursal'];
                                $sucursal_desc=dao_sucursales::get_sucursal_x_id($id_sucursal)['descripcion'];
				echo "<div style='float: right; margin-right: 60px; margin-top: 5px;'>";
				echo "<strong> <span> Sucursal: $id_sucursal -  $sucursal_desc</strong></span>";
				echo "</div>";
                        }
		}
	}

	private function es_proyecto_valido($proyecto)
	{
		return in_array($proyecto, array('administracion', 'compras', 'costos', 'contabilidad', 'presupuesto', 'rentas', 'rrhh', 'sociales', 'ventas_agua'));
	}

	public function get_html_css_intervan()
	{
		$link = '';
		$archivo = 'intervan';
		$rol = 'screen';
		$proyecto = 'principal';
		$version = toba::memoria()->get_dato_instancia('toba_revision_recursos_cliente');
		$agregado_url = (!  is_null($version)) ? "?av=$version": '';

		$path = toba::instancia()->get_path_proyecto($proyecto)."/www/css/$archivo.css";
		if (file_exists($path)) {
			$url = toba_recurso::url_proyecto($proyecto) . "/css/$archivo.css$agregado_url";
			$link .= "<link href='$url' rel='stylesheet' type='text/css' media='$rol'/>\n";
		}

		$path = toba::instancia()->get_path_proyecto($proyecto)."/www/css/prism.css";
		if (file_exists($path)) {
			$url = toba_recurso::url_proyecto($proyecto) . "/css/prism.css$agregado_url";
			$link .= "<link href='$url' rel='stylesheet' type='text/css' media='$rol'/>\n";
		}

		$path = toba::instancia()->get_path_proyecto($proyecto)."/www/css/jstree/style.min.css";
		if (file_exists($path)) {
			$url = toba_recurso::url_proyecto($proyecto) . "/css/jstree/style.min.css$agregado_url";
			$link .= "<link href='$url' rel='stylesheet' type='text/css' media='$rol'/>\n";
		}

		$path = toba::instancia()->get_path_proyecto($proyecto)."/www/css/tabulator.min.css";
		if (file_exists($path)) {
			$url = toba_recurso::url_proyecto($proyecto) . "/css/tabulator.min.css$agregado_url";
			$link .= "<link href='$url' rel='stylesheet' type='text/css' media='$rol'/>\n";
		}

		/*if ((toba::proyecto()->get_id() == 'rrhh_sse')){
			$link .= "<link href='https://fonts.googleapis.com/icon?family=Material+Icons' rel='stylesheet' >\n";
			$link .= "<link href='https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css' rel='stylesheet'/>\n";
			$link .= "<link href='https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js' rel='stylesheet'/>\n";
			$link .= "<meta name='viewport' content='width=device-width, initial-scale=1.0'/>\n";
		}*/

		return $link;
	}

	public function get_plantilla_css_menu_vertical()
	{
		$link = '';
		$archivo = 'menu_vertical';
		$rol = 'screen';
		$proyecto = toba::proyecto()->get_id();
		$version = toba::memoria()->get_dato_instancia('toba_revision_recursos_cliente');
		$agregado_url = (!  is_null($version)) ? "?av=$version": '';

		$path = toba::instancia()->get_path_proyecto($proyecto)."/www/css/$archivo.css";
		if ($this->menu_vertical && file_exists($path)) {
			$url = toba_recurso::url_proyecto($proyecto) . "/css/$archivo.css$agregado_url";
			$link .= "<link href='$url' rel='stylesheet' type='text/css' media='$rol'/>\n";
		}

		return $link;
	}

	function pre_contenido()
	{
		echo "\n<div align='center' class='cuerpo'>\n";
		$js_oper_popup = '';
		if (isset($this->oper_popup_desde_menu[toba::proyecto()->get_id()])) {
			$js_oper_popup = implode(',', $this->oper_popup_desde_menu[toba::proyecto()->get_id()]);
		}
		$js_oper_popup_nuevo_target = implode(',', $this->oper_popup_nuevo_target);
		$popup_nuevo_target = (isset($this->ini['popup_nuevo_target']) && $this->ini['popup_nuevo_target']) ? 'true' : 'false';

		echo toba_js::ejecutar("
			/**
			* Navega hacia una opción del menú
			* @param {string} proyecto Nombre del Proyecto
			* @param {string} operacion Id operación destino
			* @param {boolean} es_popup Indica si se abrira en la ventana actual o una nueva.
			* @param {boolean} es_zona Indica si propaga la zona actualmente cargada (si la hay)
			*/
		   toba.ir_a_operacion = function(proyecto, operacion, es_popup, es_zona) {
				var nueva_solapa = [$js_oper_popup].indexOf(operacion) > -1;
				var nuevo_target = [$js_oper_popup_nuevo_target].indexOf(operacion) > -1 && $popup_nuevo_target;

				var mapeo_operaciones = {
				'principal': {
						'12000103': {
							proyecto: 'administracion',
							operacion: '2'
						},
						'12000112': {
							proyecto: 'presupuesto',
							operacion: '2'
						},
						'12000113': {
							proyecto: 'contabilidad',
							operacion: '2'
						},
						'12000114': {
							proyecto: 'compras',
							operacion: '2'
						},
						'12000115': {
							proyecto: 'costos',
							operacion: '2'
						},
						'12000116': {
							proyecto: 'rentas',
							operacion: '2'
						},
						'12000117': {
							proyecto: 'rrhh',
							operacion: '2'
						},
						'12000201': {
							proyecto: 'sociales',
							operacion: '2'
						},
						'12000257': {
							proyecto: 'ventas_agua',
							operacion: '2'
						}
					},
					'administracion': {
						'12000146': {
							proyecto: 'principal',
							operacion: '2'
						}
					},
					'presupuesto': {
						'12000159': {
							proyecto: 'principal',
							operacion: '2'
						}
					},
					'contabilidad': {
						'12000153': {
							proyecto: 'principal',
							operacion: '2'
						}
					},
					'compras': {
						'1000329': {
							proyecto: 'principal',
							operacion: '2'
						}
					},
					'costos': {
						'1000340': {
							proyecto: 'principal',
							operacion: '2'
						}
					},
					'rentas': {
						'12000160': {
							proyecto: 'principal',
							operacion: '2'
						}
					},
					'rrhh': {
						'1000341': {
							proyecto: 'principal',
							operacion: '2'
						}
					},
					'sociales': {
						'12000200': {
							proyecto: 'principal',
							operacion: '2'
						}
					},
					'ventas_agua': {
						'12000258': {
							proyecto: 'principal',
							operacion: '2'
						}
					},
			   };
			   var mapeo = (mapeo_operaciones[proyecto] || {})[operacion] || null;
				if (mapeo) {
					proyecto = mapeo.proyecto;
					operacion = mapeo.operacion;
				}
			   if (this._menu_popup) {
				   es_popup = true;
			   }
			   if (typeof es_zona == 'undefined') {
				   es_zona = false;
			   }
			   var url = vinculador.get_url(proyecto, operacion, null, null, null, true, es_zona);
			   if (isset(this._callback_menu)) {
				   var continuar = this._callback_menu[0].call(this._callback_menu[1], proyecto, operacion, url, es_popup);
				   if (! continuar) {
					   return false;
				   }
			   }
			   if (! isset(es_popup) || ! es_popup) {
					prefijo = ('0000000000' + Math.floor((Math.random() * 1000000000) + 1)).slice(-10);
					oper = ('0000000000' + operacion).slice(-9);
					celda = 'icm'.concat(prefijo).concat(oper);
					url = vinculador.concatenar_parametros_url(url, {'tcm': celda});
					if (nueva_solapa) {
						target = proyecto.concat(operacion);
						if (nuevo_target) {
							target = target.concat(prefijo);
						}
						window.open(url, target);
					} else {
						document.location.href = url;
					}
			   } else {
				   celda = 'paralela';
				   parametros = {'resizable':1, 'scrollbars' : '1'};
				   url = vinculador.concatenar_parametros_url(url, {'tcm': celda});
				   abrir_popup(celda, url, parametros);
				   setTimeout (\"toba.set_menu_popup(false)\", 100);	//Para evitar que quede fijo
			   }
			   if (this._menu_popup) {
				   return false;
			   }
		   };");
	}

}
?>
