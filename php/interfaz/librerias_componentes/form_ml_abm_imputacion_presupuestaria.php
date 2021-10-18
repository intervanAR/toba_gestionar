<?php
/**
 * Esta clase se utiliza como herencia de la clase que
 * extiende el formulario ml de imputacion presupuestaria:
 *
 * Requisitos:
 *  - El formulario ml debe tener definido el campo
 *    "afectacion_especifica" sin etiqueta de tipo
 *    ef_fijo y no obligatorio.
 *  - El cod_recurso debe ser no obligatorio, ya que esta
 *    clase se encarga de validar si se debe o no cargar
 *    el recurso.
 */
class form_ml_abm_imputacion_presupuestaria extends principal_ei_formulario_ml
{
    protected $valida_importe = false;
    protected $valida_cantidad = false;

    /////////////////////////////////////
    // JavaScript
    /////////////////////////////////////
    public function extender_objeto_js()
    {
        echo "
        //---- Procesamiento de EFs --------------------------------

        {$this->objeto_js}.evt__cod_fuente_financiera__procesar = function(es_inicial, fila)
        {
            if (this.ef('cod_fuente_financiera').ir_a_fila(fila).tiene_estado()){
                this.ajax_es_fuente_afectacion_especifica(fila);
            }
        }

        {$this->objeto_js}.ajax_es_fuente_afectacion_especifica = function(fila) {
            var cod_fuente_financiera = this.ef('cod_fuente_financiera').ir_a_fila(fila).get_estado();

            if (cod_fuente_financiera != '') {
                var parametros = [];
                parametros[0] = cod_fuente_financiera;
                parametros[1] = fila;

                this.controlador.ajax('es_fuente_afectacion_especifica__controlador', parametros, this, this.retorno_es_fuente_afectacion_especifica);
            }
        }

        {$this->objeto_js}.retorno_es_fuente_afectacion_especifica = function(resultado) {
            var es_fuente_afectacion_especifica = resultado['es_fuente_afectacion_especifica'];
            var fila = resultado['fila'];
            if (es_fuente_afectacion_especifica == 'N'){
                this.ef('cod_recurso').ir_a_fila(fila).mostrar(true);

                if (this.ef('cod_recurso').ir_a_fila(fila).tiene_estado()){
                    this.ef('cod_recurso').ir_a_fila(fila).resetear_estado();
                }

                this.ef('cod_recurso').ir_a_fila(fila).ocultar();
            } else {
                this.ef('cod_recurso').ir_a_fila(fila).mostrar(true);
            }
            this.ef('afectacion_especifica').ir_a_fila(fila).set_estado(es_fuente_afectacion_especifica);
            this.ef('afectacion_especifica').ir_a_fila(fila).ocultar();
        }

        //---- Validacion de EFs -----------------------------------

        {$this->objeto_js}.evt__cod_recurso__validar = function(fila)
        {
            afectacion_especifica = this.ef('afectacion_especifica').ir_a_fila(fila).get_estado();
            if (!this.ef('cod_recurso').ir_a_fila(fila).tiene_estado() && afectacion_especifica == 'S') {
                this.ef('cod_recurso').ir_a_fila(fila).set_error('es obligatorio.');
                return false;
            } else {
                return true;
            }
        }
        ";

        if ($this->valida_importe) {
            $datos_detalle = $this->controlador()->get_datos_detalle_seleccionado();
            $importe = 0;
            if (isset($datos_detalle['importe_neto'])) {
                $importe = $datos_detalle['importe_neto'];
            } elseif (isset($datos_detalle['total_estimado'])) {
                $importe = $datos_detalle['total_estimado'];
            } else {
                $importe = isset($datos_detalle['importe'])?$datos_detalle['importe']:0;
//                $importe = $datos_detalle['importe']==null?0:$datos_detalle['importe'];
            }
            echo "
                //---- Validacion general ----------------------------------

                {$this->objeto_js}.evt__validar_datos = function()
                {
                    if ($importe!='' && this.total('importe') != {$importe}) {
                        alert('El importe total de la imputaci�n presupuestaria es distinto al importe del detalle seleccionado.');
                        return false;
                    } else {
                        return true;
                    }
                }
                ";
        }

        if ($this->valida_cantidad) {
            $datos_detalle = $this->controlador()->get_datos_detalle_seleccionado();
            if (isset($datos_detalle['cantidad'])) {
                $cantidad = $datos_detalle['cantidad'];
            } else {
                $cantidad = $datos_detalle['cantidad'];
            }
            echo "
                //---- Validacion general ----------------------------------

                {$this->objeto_js}.evt__validar_datos = function()
                {
                    if (this.total('cantidad') != {$cantidad}) {
                        alert('La cantidad total de la imputaci�n presupuestaria es distinta a la cantidad del detalle seleccionado.');
                        return false;
                    } else {
                        return true;
                    }
                }
                ";
        }
    }
}
