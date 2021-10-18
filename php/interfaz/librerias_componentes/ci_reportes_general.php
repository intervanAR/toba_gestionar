<?php
/**
 * Este ci utiliza la tabla KR_REPORTES para configurar los distintos
 * reportes que comparten un mismo formulario.
 */
class ci_reportes_general extends ci_abm_complejo_edicion
{
    public $tabla_reporte = 'KR_REPORTES';
    public $clase_carga = 'dao_reportes_general';
    public $metodo_carga = 'get_reporte';
    /**
     * Nombre del reporte pasado por parametro en la operación.
     * Equivalente al nombre en la tabla.
     */
    public $s__reporte = null;
    /**
     * Para el registro de la tabla de reportes.
     */
    public $s__datos = null;
    /**
     * Para conservar la carga del formulario
     * luego de la ejecucion de un evento.
     */
    public $s__datos_formulario = null;

    /////////////////////////////////////
    // Datos para Jasper Report
    /////////////////////////////////////
    // Indica si está disponible el Reporte en Jasper.
    public $s__jasper_disponible;
    // Condición para invocar el llamador a los reportes de Jasper.
    public $s__llamar_jasper = false;
    // Parametros para reportes.
    public $s__parametros = [];
    // Path del archivo temmplate del reporte.
    public $s__path_jasperreport = '';

    /////////////////////////////////////
    // LOVs
    /////////////////////////////////////
    public function get_lov_unidad_administracion($nombre)
    {
        $filtro = ['activa' => 'S'];

        return dao_unidades_administracion::get_lov_unidades_administracion_x_nombre(
            $nombre,
            $filtro
        );
    }

    public function get_lov_ejercicios($nombre)
    {
        return dao_ejercicios::get_lov_ejercicio_x_nombre($nombre);
    }

    public function get_lov_fuentes_financieras($nombre)
    {
        $filtro = [
            'en_actividad' => '',
            'imputable' => '',
        ];

        return dao_fuentes_financieras::get_lov_fuentes_x_nombre($nombre, $filtro);
    }

    public function get_lov_programas_2(
        $nombre,
        $id_ejercicio,
        $cod_entidad_desde,
        $cod_entidad_hasta
    ) {
        $filtro = [
            'id_ejercicio' => $id_ejercicio,
            'cod_entidad_desde' => $cod_entidad_desde,
            'cod_entidad_hasta' => $cod_entidad_hasta,
        ];

        return dao_listados_recursos::get_lov_programas_x_nombre($nombre, $filtro);
    }

    public function get_lov_entidad_a_nivel(
        $nombre,
        $cod_unidad_administracion,
        $nivel_entidad,
        $id_ejercicio
    ) {
        $filtro = [
            'cod_unidad_administracion' => $cod_unidad_administracion,
            'id_ejercicio' => $id_ejercicio,
            'nivel_entidad' => $nivel_entidad,
        ];

        return dao_listados_recursos::get_lov_entidades_a_nivel_x_nombre(
            $nombre,
            $filtro
        );
    }

    public function get_lov_entidad_a_nivel1(
        $nombre,
        $cod_unidad_administracion,
        $nivel_entidad,
        $id_ejercicio,
        $cod_entidad_desde
    ) {
        if ($cod_entidad_desde == '0') {
            $cod_entidad_desde = null;
        }
        $filtro = [
            'cod_unidad_administracion' => $cod_unidad_administracion,
            'id_ejercicio' => $id_ejercicio,
            'nivel_entidad' => $nivel_entidad,
            'cod_entidad_desde' => $cod_entidad_desde,
        ];

        return dao_listados_recursos::get_lov_entidades_a_nivel_x_nombre(
            $nombre,
            $filtro
        );
    }

    public function get_lov_nivel_partida($nombre)
    {
        $filtro = [];

        return dao_listados_recursos::get_lov_nivel_partida_x_nombre($nombre, $filtro);
    }

    public function get_lov_partida_a_nivel($nombre, $nivel_partida)
    {
        if ($nivel_partida == '-1') {
            $nivel_partida = 'null';
        }
        $filtro = ['nivel_partida' => $nivel_partida];

        return dao_listados_recursos::get_lov_partidas_a_nivel_x_nombre(
            $nombre,
            $filtro
        );
    }

    public function get_lov_partida_a_nivel_1(
        $nombre,
        $nivel_partida,
        $cod_partida_desde
    ) {
        if ($cod_partida_desde == '0') {
            $cod_partida_desde = 'null';
        }
        if ($nivel_partida == '-1') {
            $nivel_partida = 'null';
        }
        $filtro = [
            'nivel_partida' => $nivel_partida,
            'cod_partida_desde' => $cod_partida_desde,
        ];

        return dao_listados_recursos::get_lov_partidas_a_nivel_x_nombre(
            $nombre,
            $filtro
        );
    }

    public function get_lov_nivel_recurso($nombre, $cod_fuente = '0')
    {
        $filtro = [
            'cod_fuente' => $cod_fuente == '0' ? null : $cod_fuente,
        ];

        return dao_listados_recursos::get_lov_nivel_recursos_x_nombre($nombre, $filtro);
    }

    public function get_lov_recurso_a_nivel(
        $nombre,
        $cod_fuente = '0',
        $nivel_recurso = '-1'
    ) {
        $filtro = [
            'nivel_recurso' => $nivel_recurso,
            'cod_fuente' => $cod_fuente,
        ];

        return dao_listados_recursos::get_lov_recurso_a_nivel_x_nombre(
            $nombre,
            $filtro
        );
    }

    public function get_lov_recurso_a_nivel_1(
        $nombre,
        $cod_fuente = '0',
        $nivel_recurso = '-1',
        $cod_recurso_desde = '0'
    ) {
        $filtro = [
            'nivel_recurso' => $nivel_recurso,
            'cod_recurso_desde' => $cod_recurso_desde,
            'cod_fuente' => $cod_fuente,
        ];

        return dao_listados_recursos::get_lov_recurso_a_nivel_x_nombre(
            $nombre,
            $filtro
        );
    }

    public function get_lov_recursos($nombre, $cod_fuente)
    {
        $filtro = ['cod_fuente' => $cod_fuente];

        return dao_listados_recursos::get_lov_recursos_x_nombre($nombre, $filtro);
    }

    /////////////////////////////////////
    // Para listados de Administracion
    /////////////////////////////////////
    public function get_tipos_cuenta()
    {
        return dao_valor_dominios::get('KR_TIPO_CUENTA_CORRIENTE');
    }

    public function get_tipo_clasificacion()
    {
        return dao_valor_dominios::get('KR_TIPO_CLASIFICACION_CTA_CTE');
    }

    public function get_lov_cuentas_corrientes_x_nro($nombre, $cod_unidad_administracion, $tipo_cuenta)
    {
        $filtro = ['cod_unidad_administracion' => $cod_unidad_administracion, 'tipo_cuenta' => $tipo_cuenta];

        return dao_listados::get_lov_cuentas_corriente_x_nro($nombre, $filtro);
    }

    public function get_lov_cuentas_corrientes_x_nro_desde($nombre, $cod_unidad_administracion, $tipo_cuenta, $cod_cuenta_desde)
    {
        $filtro = ['cod_unidad_administracion' => $cod_unidad_administracion, 'tipo_cuenta' => $tipo_cuenta, 'cod_cuenta_desde' => $cod_cuenta_desde];

        return dao_listados::get_lov_cuentas_corriente_x_nro($nombre, $filtro);
    }

    public function get_lov_beneficiario($nombre)
    {
        return dao_listados::get_lov_beneficiario_x_nombre($nombre);
    }

    public function get_lov_programas($nombre)
    {
        return dao_programas::get_lov_programas_x_nombre($nombre);
    }

    public function get_lov_auxiliares_ext($nombre)
    {
        return dao_auxiliares_extrapresupuestarios::get_auxiliares_extrapresupuestarios_x_nombre($nombre);
    }

    public function get_lov_nivel_entidades($nombre, $cod_unidad_administracion = '0')
    {
        /*
         * El cod_unidad_administracion = 0 cuando no se selecciono ninguna.
         * Se debe pasar el valor NULL para realizar la consulta.
         */
        if ($cod_unidad_administracion == '0') {
            $filtro = ['cod_unidad_administracion' => null];
        } else {
            $filtro = ['cod_unidad_administracion' => $cod_unidad_administracion];
        }

        return dao_listados_recursos::get_lov_nivel_x_nombre($nombre, $filtro);
    }

    /////////////////////////////////////
    // self
    /////////////////////////////////////
    public function ini__operacion()
    {
        if (isset($this->s__reporte)) {
            return;
        }

        $this->s__reporte = toba::solicitud()->get_datos_item('item_parametro_a');

        if ($this->s__reporte == '') {
            toba::notificacion()->info('Debe setear item_parametro_a de la operación');

            return;
        }

        // Recupero Archiv de parametros bases.ini
        $bases_ini = toba_dba::get_bases_definidas();

        // Seteo el path donde se encuentra el `.jasper`
        // del reporte segun el sistema corriendo
        if (!isset($bases_ini['reportes jasper']['ruta_reportes'])) {
            toba::logger()->error('Error: no esta seteado la ruta de los reportes .jasper en el archivo bases.ini');
        } else {
            $path_proyecto = $bases_ini['reportes jasper']['ruta_reportes'];

            $proyecto_id = toba::proyecto()->get_id();

            switch ($proyecto_id) {
                case 'administracion':
                    $path_proyecto .= 'Financiero/';
                break;
                case 'presupuesto':
                    $path_proyecto .= 'Financiero/';
                break;
                case 'compras':
                    $path_proyecto .= 'Financiero/';
                break;
                case 'rrhh':
                    $path_proyecto .= 'RRHH/';
                break;
                case 'rentas':
                    $path_proyecto .= 'Rentas/';
                break;
            }
            $this->s__path_jasperreport = "$path_proyecto{$this->s__reporte}.jasper";
        }

        // Verifico si existe el archivo del reporte
        if (file_exists($this->s__path_jasperreport)) {
            // Seteo condicion para imprimir Reporte Jasper.
            $this->s__jasper_disponible = true;

            if (isset($bases_ini['reportes jasper']['p_sistema'])) {
                $this->s__parametros['p_sistema'] = $bases_ini['reportes jasper']['p_sistema'];
            }

            // Obtengo el TITULO y SUBTITULO del Reporte.
            $this->s__parametros = array_merge($this->s__parametros, dao_reportes_general::obtener_titulo_subtitulo($this->s__reporte));
            $this->s__parametros['p_municipio'] = dao_reportes_general::get_nombre_municipio();
        }
    }

    public function conf()
    {
        //Se recupera la configuracion del reporte en KR_REPORTES
        $datos = [];

        eval("\$datos = {$this->clase_carga}::{$this->metodo_carga}(\$this->s__reporte);");
        $this->s__datos = $datos;
    }

    /////////////////////////////////////
    // formulario
    /////////////////////////////////////
    public function conf__formulario($form)
    {
        if (!isset($this->s__datos['reporte'])) {
            $this->s__datos['habilitado'] = 'NNNNNNNN';
            $this->s__datos['obligatorio'] = 'NNNNNNNN';
            $this->s__datos['titulo'] = 'Reportes';
            $this->s__datos['subtitulo'] = '';
        } else {
            //$form->set_datos(array("reporte"=>$this->s__reporte));
        }
        $form->set_titulo($this->s__datos['titulo'].$this->s__datos['subtitulo']);
    }

    public function evt__formulario__cancelar()
    {
        $this->s__datos_formulario = null;
    }

    public function llamar_reporte($datos, $formato)
    {
        $this->s__parametros = array_merge($datos, $this->s__parametros);

        if ($this->s__jasper_disponible) {
            //Si está disponible un template para generar el reporte en jasper, seteo condición para el llamado.
            $this->s__llamar_jasper = true;
        } else {
            //Jasper No disponible. Lllamo al servicio de Pentaho u Oracle.
            $llamador = new reporte_llamador($this->s__reporte);

            $respuesta = $llamador->llamar_reporte($datos, $formato, 'pentaho');

            if ($respuesta['error']) {
                $respuesta = $llamador->llamar_reporte($datos, $formato, 'oracle');
            }
            if (isset($respuesta['nombre_archivo']) && !empty($respuesta['nombre_archivo'])) {
                $this->url_reporte = $respuesta['url'];
                $this->reporte_generado = true;
            } else {
                toba::notificacion()->info('No se puedo generar el Reporte: '.$respuesta['mensaje']);
            }
        }
    }


    /////////////////////////////////////
    //  Auxiliares
    /////////////////////////////////////
    public function formatear_parametros_nulos($parametros)
    {
        $parametros_format = [];
        foreach ($parametros as $key => $value) {
            if (empty($parametros[$key])) {
                $parametros_format[$key] = '';
            } else {
                $parametros_format[$key] = "&$key=$value";
            }
        }
    }

    /////////////////////////////////////
    // AJAX
    /////////////////////////////////////
    public function ajax__ejercicio__controlador($parametros, toba_ajax_respuesta $respuesta)
    {
        $id_formulacion = $parametros[0];
        $id_ejercicio = dao_formulaciones_presupuestarias::get_ui_ejercicio_x_formulacion($id_formulacion);
        if (isset($id_ejercicio) && (!empty($id_ejercicio))) {
            $respuesta->set(['id_ejercicio' => $id_ejercicio]);
        } else {
            $respuesta->set(['id_ejercicio' => null]);
        }
    }

    public function extender_objeto_js()
    {
        parent::extender_objeto_js();
        /*
         * Llamado a JasperReport.
         * Se llama a la function 'vista_jasperreports' que genera el reporte.
         */
        if ($this->s__llamar_jasper) {
            if (isset($this->s__path_jasperreport)) {
                echo "
					location.href = vinculador.get_url(null, null, 'vista_jasperreports', null);
					";
                $this->s__llamar_jasper = false;
                '
					return false;
				';
            } else {
                toba::notificacion()->info('No existe el archivo o fichero. '.$this->s__path_jasperreport);
            }
        }
    }
}
