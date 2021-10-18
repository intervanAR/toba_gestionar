<?php

class ci_abm_complejo_edicion extends principal_ci
{
    protected $s__cursor_detalle;
    protected $s__imputacion_presupuestaria;
    protected $s__imputacion_costos;

    /////////////////////////////
    // Configuraciones
    /////////////////////////////
    public function conf()
    {
        parent::conf();
        $seleccion = $this->controlador()->get_clave_relacion();

        if (isset($seleccion)) {
            return;
        }
        if (
            isset($this->controlador()->dt_detalle)
            && !empty($this->controlador()->dt_detalle)
        ) {
            $this->pantalla()->tab('pant_detalle')->anular();
        }
        if ($this->controlador()->pantalla('edicion')->existe_evento('eliminar')) {
            $this
                ->controlador()
                ->pantalla('edicion')
                ->eliminar_evento('eliminar');
        }
        if ($this->controlador()->pantalla('edicion')->existe_evento('cancelar_edicion')) {
            $this
                ->controlador()
                ->pantalla('edicion')
                ->eliminar_evento('cancelar_edicion');
        }
    }

    /**
     * Esta función vacía es requerida por los controladores
     * que invocan a parent::conf__pant_encabezado
     *
     * @param toba_ei_pantalla $pantalla La pantalla detalle
     *                                   a configurar.
     */
    public function conf__pant_encabezado(toba_ei_pantalla $pantalla)
    {
    }

    // TODO Revisar si esto es exclusivo de administración
    public function conf__pant_detalle(toba_ei_pantalla $pantalla)
    {
        // Reglas de corte
        if ((
            !isset($this->controlador()->dt_imputacion_pre)
            || empty($this->controlador()->dt_imputacion_pre)
        ) && (
            !isset($this->controlador()->dt_imputacion_cos)
            || empty($this->controlador()->dt_imputacion_cos)
        )) {
            return;
        }
        // Proceso principal
        if (!isset($this->s__cursor_detalle)) {
            if ($pantalla->existe_dependencia('formulario_ml_imputacion_pre')) {
                $pantalla->eliminar_dep('formulario_ml_imputacion_pre');
            }
            if ($pantalla->existe_dependencia('formulario_ml_imputacion_cos')) {
                $pantalla->eliminar_dep('formulario_ml_imputacion_cos');
            }
        } else {
            if (!$pantalla->existe_dependencia('formulario_ml_imputacion_pre')) {
                $pantalla->agregar_dep('formulario_ml_imputacion_pre');
            }
            if (!$pantalla->existe_dependencia('formulario_ml_imputacion_cos')) {
                $pantalla->agregar_dep('formulario_ml_imputacion_cos');
            }
        }
        if (
            !dao_configuraciones::get_imputacion_presupuestaria()
            && $pantalla->existe_dependencia('formulario_ml_imputacion_pre')
        ) {
            $pantalla->eliminar_dep('formulario_ml_imputacion_pre');
        }
        if (
            !dao_configuraciones::get_imputacion_centro_costos()
            && $pantalla->existe_dependencia('formulario_ml_imputacion_cos')
        ) {
            $pantalla->eliminar_dep('formulario_ml_imputacion_cos');
        }
    }

    public function conf__formulario($form)
    {
        $datos = !$this->controlador()->relacion()->esta_cargada()
                ? []
                : $this->controlador()->tabla($this->controlador()->dt_encabezado)->get();

        // recorro todos los campos y seteo los valores por defecto
        foreach ($form->get_nombres_ef() as $ef) {
            $valor_defecto = $this->controlador()->get_valor_por_defecto_x_campo(
                $form->ef($ef)->get_id()
            );

            if (isset($valor_defecto)) {
                $form->ef($ef)->set_estado_defecto($valor_defecto);
            }
        }

        if (toba::proyecto()->get_id() == 'rentas' || toba::proyecto()->get_id() == 'rrhh') {
            dao_varios::setear_prompts_formulario($form);
        }

        return $datos;
    }

    public function conf__formulario_ml_detalle($form_ml)
    {
        $datos = [];
        if ($this->controlador()->relacion()->esta_cargada()) {
            $datos = $this->controlador()->tabla($this->controlador()->dt_detalle)->get_filas();

            if (isset($this->s__cursor_detalle)) {
                $form_ml->desactivar_agregado_filas();
                $form_ml->eliminar_evento('seleccion');
            }
        }
        if (empty($datos)) {
            $form_ml->set_registro_nuevo();
        }

        return $datos;
    }

    public function conf__formulario_ml_imputacion_pre($form_ml)
    {
        $datos = [];

        if (
            $this->controlador()->relacion()->esta_cargada()
            && isset($this->s__cursor_detalle)
        ) {
            $this
                ->controlador()
                ->tabla($this->controlador()->dt_detalle)
                ->set_cursor($this->s__cursor_detalle);
            $datos = $this
                ->controlador()
                ->tabla($this->controlador()->dt_imputacion_pre)
                ->get_filas();

            // seteo la imputacion presupuestaria por defecto
            if (
                isset($this->s__imputacion_presupuestaria['cod_seq_sector'])
                && isset($this->s__imputacion_presupuestaria['seq_sector'])
            ) {
                $form_ml->ef('cod_seq_sector')->set_estado_defecto(
                    $this->s__imputacion_presupuestaria['cod_seq_sector']
                    . apex_qs_separador
                    . $this->s__imputacion_presupuestaria['seq_sector']
                );
                $form_ml->ef('seq_sector')->set_estado_defecto(
                    $this->s__imputacion_presupuestaria['seq_sector']
                );
            }
            if (isset($this->s__imputacion_presupuestaria['id_entidad'])) {
                $form_ml->ef('id_entidad')->set_estado_defecto(
                    $this->s__imputacion_presupuestaria['id_entidad']
                );
            }
            if (isset($this->s__imputacion_presupuestaria['id_programa'])) {
                $form_ml->ef('id_programa')->set_estado_defecto(
                    $this->s__imputacion_presupuestaria['id_programa']
                );
            }
            if (isset($this->s__imputacion_presupuestaria['cod_fuente_financiera'])) {
                $form_ml->ef('cod_fuente_financiera')->set_estado_defecto(
                    $this->s__imputacion_presupuestaria['cod_fuente_financiera']
                );
            }
            if (isset($this->s__imputacion_presupuestaria['cod_recurso'])) {
                $form_ml->ef('cod_recurso')->set_estado_defecto(
                    $this->s__imputacion_presupuestaria['cod_recurso']
                );
            }
        }
        if (empty($datos)) {
            $form_ml->set_registro_nuevo();
        }

        return $datos;
    }

     public function conf__formulario_ml_imputacion_cos($form_ml)
    {
        $datos = [];

        if (
            $this->controlador()->relacion()->esta_cargada()
            && isset($this->s__cursor_detalle)
        ) {
            $this
                ->controlador()
                ->tabla($this->controlador()->dt_detalle)
                ->set_cursor($this->s__cursor_detalle);
            $datos = $this
                ->controlador()
                ->tabla($this->controlador()->dt_imputacion_cos)
                ->get_filas();

            // seteo la imputacion presupuestaria por defecto
            if (
                isset($this->s__imputacion_costos['cod_seq_sector'])
                && isset($this->s__imputacion_costos['seq_sector'])
            ) {
                $form_ml->ef('cod_seq_sector')->set_estado_defecto(
                    $this->s__imputacion_costos['cod_seq_sector']
                    . apex_qs_separador
                    . $this->s__imputacion_costos['seq_sector']
                );
                $form_ml->ef('seq_sector')->set_estado_defecto(
                    $this->s__imputacion_costos['seq_sector']
                );
            }
            if (isset($this->s__imputacion_costos['id_entidad'])) {
                $form_ml->ef('id_entidad')->set_estado_defecto(
                    $this->s__imputacion_costos['id_entidad']
                );
            }
            if (isset($this->s__imputacion_costos['id_programa'])) {
                $form_ml->ef('id_programa')->set_estado_defecto(
                    $this->s__imputacion_costos['id_programa']
                );
            }
            if (isset($this->s__imputacion_costos['cod_fuente_financiera'])) {
                $form_ml->ef('cod_fuente_financiera')->set_estado_defecto(
                    $this->s__imputacion_costos['cod_fuente_financiera']
                );
            }
            if (isset($this->s__imputacion_costos['cod_recurso'])) {
                $form_ml->ef('cod_recurso')->set_estado_defecto(
                    $this->s__imputacion_costos['cod_recurso']
                );
            }
        }
        if (empty($datos)) {
            $form_ml->set_registro_nuevo();
        }

        return $datos;
    }

    /////////////////////////////
    // Eventos
    /////////////////////////////
    public function evt__formulario__modificacion($datos)
    {
        if (
            !isset($datos[$this->controlador()->get_campo_id_comprobante()])
            || empty($datos[$this->controlador()->get_campo_id_comprobante()])
        ) {
            if (!isset($datos['usuario_carga']) || empty($datos['usuario_carga'])) {
                $datos['usuario_carga'] = toba::usuario()->get_id();
            }
            if (!isset($datos['fecha_carga']) || empty($datos['fecha_carga'])) {
                $datos['fecha_carga'] = date('Y-m-d H:i:s');
            }
        }

        // Vacio la UE si tiene el valor 0
        if (
            isset($datos['cod_unidad_ejecutora'])
            && $datos['cod_unidad_ejecutora'] == '0'
        ) {
            unset($datos['cod_unidad_ejecutora']);
        }
        $this->controlador()->tabla($this->controlador()->dt_encabezado)->set($datos);
    }

    public function evt__formulario_ml_detalle__modificacion($datos)
    {
        $this
            ->controlador()
            ->tabla($this->controlador()->dt_detalle)
            ->procesar_filas($datos);
    }

    public function evt__formulario_ml_detalle__seleccion($seleccion)
    {
        $this->controlador()->evt__guardar();

        $this->s__cursor_detalle = $seleccion;
    }

    public function conf_evt__formulario_ml_detalle__seleccion(
        toba_evento_usuario $evento, $fila
    ) {
        $datos = $this->dep('formulario_ml_detalle')->get_datos();

        if (!isset($datos[$fila]) || empty($datos[$fila])) {
            $evento->anular();
        } else {
            $evento->mostrar();
        }
    }

    public function evt__formulario_ml_imputacion_pre__modificacion($datos)
    {
        if (
            !$this->controlador()->tabla($this->controlador()->dt_detalle)->hay_cursor()
        ) {
            return;
        }
        $this
            ->controlador()
            ->tabla($this->controlador()->dt_imputacion_pre)
            ->procesar_filas($datos);

        // seteo la ultima imputacion cargada
        foreach ($datos as $dato) {
            $this->s__imputacion_presupuestaria = $dato;
        }
    }

    public function evt__formulario_ml_imputacion_cos__modificacion($datos)
    {
        if (
            !$this->controlador()->tabla($this->controlador()->dt_detalle)->hay_cursor()
        ) {
            return;
        }
        $this
            ->controlador()
            ->tabla($this->controlador()->dt_imputacion_cos)
            ->procesar_filas($datos);

        // seteo la ultima imputacion cargada
        foreach ($datos as $dato) {
            $this->s__imputacion_costos = $dato;
        }
    }

    public function evt__aprobar()
    {
        if (!$this->controlador()->relacion()->esta_cargada()) {
            return;
        }
        $datos_comp = $this
            ->controlador()
            ->tabla($this->controlador()->dt_encabezado)
            ->get();

        if (
            isset($this->controlador()->campo_id_comprobante)
            && isset($datos_comp[$this->controlador()->campo_id_comprobante])
        ) {
            // Completar comprobante
            $this->completar_comprobante(
                null,
                null,
                null,
                null,
                $datos_comp[$this->controlador()->campo_id_comprobante]
            );
        }
    }

    /////////////////////////////
    // AJAX
    /////////////////////////////
    public function ajax__es_fuente_afectacion_especifica__controlador(
        $parametros,
        toba_ajax_respuesta $respuesta
    ) {
        if (isset($parametros[0]) && isset($parametros[1])) {
            $es_fuente_afectacion_especifica =
                dao_fuentes_financieras::get_es_afectacion_especifica_x_cod_fuente(
                    $parametros[0]
                );

            $respuesta->set([
                'es_fuente_afectacion_especifica' => $es_fuente_afectacion_especifica,
                'fila' => $parametros[1],
            ]);
        } else {
            $respuesta->set([
                'es_fuente_afectacion_especifica' => 'S',
                'fila' => 0,
            ]);
        }
    }

    /////////////////////////////
    // Auxiliares
    /////////////////////////////
    public function resetear_cursor_detalle()
    {
        unset($this->s__cursor_detalle);
    }

    public function get_cursor_detalle()
    {
        if (isset($this->s__cursor_detalle)) {
            return $this->s__cursor_detalle;
        }
    }

    public function get_datos_detalle_seleccionado()
    {
        if (
            !$this->controlador()->relacion()->esta_cargada()
            || !isset($this->s__cursor_detalle)
        ) {
            return;
        }
        $datos_detalle = $this
            ->controlador()
            ->tabla($this->controlador()->dt_detalle)
            ->get_filas();
        $cursor_detalle = $this->get_cursor_detalle();

        return $datos_detalle[$cursor_detalle];
    }

    /////////////////////////////
    // JS
    /////////////////////////////
    public function get_js_validar_signo_importe($objeto_js, $positivo = 'S')
    {
        echo "
        //---- Validacion de EFs -----------------------------------

        {$objeto_js}.evt__importe__validar = function(fila)
        {
            if (!this.ef('importe').ir_a_fila(fila).tiene_estado()) {
                return true;
            }
            if (
                '$positivo' === 'S'
                && this.ef('importe').ir_a_fila(fila).get_estado() < 0
            ) {
                this.ef('importe')
                    .ir_a_fila(fila)
                    .set_error('El importe debe ser positivo.');

                return false;
            }
            if (
                '$positivo' === 'N'
                && this.ef('importe').ir_a_fila(fila).get_estado() > 0
            ) {
                this.ef('importe')
                    .ir_a_fila(fila)
                    .set_error('El importe debe ser negativo.');

                return false;
            }

            return true;
        }
        ";
    }

    /**
     * En vez de que esta función esté asociada a una instancia del CI,
     * es preferible que esté como una funcionalidad extra disponible
     * para cualquier componente.
     *
     * El método de la clase generador_vinculos es exactamente igual
     * al original de éste.
     *
     * @deprecated
     * @see principal\php\interfaz\general\generador_vinculos
     */
    public function get_html_navegar_comprobante(
        $proyecto, $item, $id_comprobante, $title, $texto = '', $tipo = 'link'
    ) {
        return generador_vinculos::get_html_navegar_comprobante(
            $proyecto, $item, $id_comprobante, $title, $texto, $tipo
        );
    }
}
