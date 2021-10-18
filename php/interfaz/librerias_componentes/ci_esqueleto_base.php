<?php

class ci_esqueleto_base extends ci_abm_complejo_edicion
{
    // Variables del CI (incluye las de sesion)
    protected $s__variable_sesion = '';
    protected $variable_comun = '';

    ////////////////////////////////////////
    // self
    ////////////////////////////////////////
    public function ini__operacion()
    {
    }

    public function ini()
    {
    }

    public function conf()
    {
    }

    public function evt__evento1()
    {
    }

    public function evt__evento2()
    {
    }

    ////////////////////////////////////////
    // pant_pantalla_1
    ////////////////////////////////////////
    public function conf__pant_pantalla_1(toba_ei_pantalla $pantalla)
    {
    }

    ////////////////////////////////////////
    // pant_pantalla_2
    ////////////////////////////////////////
    public function conf__pant_pantalla_2(toba_ei_pantalla $pantalla)
    {
    }

    ////////////////////////////////////////
    // filtro
    ////////////////////////////////////////
    public function conf__filtro($form)
    {
    }

    public function evt__filtro__filtrar($datos)
    {
    }

    public function evt__filtro__cancelar()
    {
    }

    ////////////////////////////////////////
    // cuadro
    ////////////////////////////////////////
    public function conf__cuadro($cuadro)
    {
    }

    public function evt__cuadro__seleccion($seleccion)
    {
    }

    ////////////////////////////////////////
    // formulario
    ////////////////////////////////////////
    public function conf__formulario($form)
    {
    }

    public function evt__formulario__modificacion($datos)
    {
    }

    ////////////////////////////////////////
    // formulario_ml_1
    ////////////////////////////////////////
    public function conf__formulario_ml_1($form_ml)
    {
    }

    public function evt__formulario_ml_1__modificacion($datos)
    {
    }

    ////////////////////////////////////////
    // formulario_ml_2
    ////////////////////////////////////////
    public function conf__formulario_ml_2($form_ml)
    {
    }

    public function evt__formulario_ml_2__modificacion($datos)
    {
    }

    ////////////////////////////////////////
    // JavaScript
    ////////////////////////////////////////
    public function extender_objeto_js()
    {
    }

    ////////////////////////////////////////
    // AJAX
    ////////////////////////////////////////
    public function ajax__metodo1__controlador($parametros, toba_ajax_respuesta $respuesta)
    {
    }

    public function ajax__metodo2__controlador($parametros, toba_ajax_respuesta $respuesta)
    {
    }

    ////////////////////////////////////////
    // Auxiliares
    ////////////////////////////////////////
    public function metodo_auxiliar_1()
    {
        return [];
    }

    public function metodo_auxiliar_2()
    {
        return [];
    }
}
