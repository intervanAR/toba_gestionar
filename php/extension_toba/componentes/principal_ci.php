<?php

class principal_ci extends toba_ci
{
    use generador_reportes_trait;

    protected $s__filtro = [];
    protected $s__id_comprobante;

    protected $s__cod_proceso;
    protected $s__cod_actividad;
    protected $s__id_proceso;
    protected $s__id_actividad;
    protected $s__aplicacion_origen;

    protected $s__onload;

    // Path completo del reporte generado.
    protected $url_reporte;
    // Condición para el Showreport.
    protected $reporte_generado = false; //Quedará obsoleto

    protected $reporte;

    public function ini__operacion()
    {
        $this->s__aplicacion_origen = toba::memoria()->get_parametro('aplicacion_origen');
        $this->s__cod_proceso = toba::memoria()->get_parametro('cod_proceso');
        $this->s__cod_actividad = toba::memoria()->get_parametro('cod_actividad');
        $this->s__id_proceso = toba::memoria()->get_parametro('id_proceso');
        $this->s__id_actividad = toba::memoria()->get_parametro('id_actividad');
        $this->s__id_comprobante = toba::memoria()->get_parametro('id_comprobante');

        if ($this->s__aplicacion_origen === 'tramites') {
            return;
        }
        if (
            isset($this->s__id_comprobante) &&
            isset($this->campo_id_comprobante) &&
            !empty($this->campo_id_comprobante)
        ) {
            if (
                $this->existe_dependencia('cuadro') &&
                $this->dep('cuadro')->existe_evento('seleccion')
            ) {
                $clave_carga = $this->get_clave_carga(
                    $this->campo_id_comprobante,
                    $this->s__id_comprobante
                );

                $this->evt__cuadro__seleccion($clave_carga);
            }
        } else {
            if (
                isset($this->s__aplicacion_origen) &&
                !empty($this->s__aplicacion_origen)
            ) {
                $comprobantes = dao_bp_procesos::get_comprobantes(
                    $this->s__id_proceso,
                    $this->s__id_actividad
                );

                if (empty($comprobantes) && $this->existe_evento('agregar')) {
                    $this->evt__agregar();
                }
            }
        }
    }

    public function ini()
    {
        // determino si se muestra o no el menu de Toba
        toba::memoria()->eliminar_dato('muestra_menu');
        if (!isset($this->s__aplicacion_origen) || empty($this->s__aplicacion_origen)) {
            toba::memoria()->set_dato('muestra_menu', '1');
        } else {
            toba::memoria()->set_dato('muestra_menu', '0');
        }

        if ($this->s__aplicacion_origen === 'tramites') {
            return;
        }
        if (isset($this->s__id_proceso) && isset($this->s__id_actividad)) {
            $this->s__filtro['ids_comprobantes'] = dao_bp_procesos::get_comprobantes(
                $this->s__id_proceso,
                $this->s__id_actividad
            );
        }
    }

    public function get_filtro()
    {
        return $this->s__filtro;
    }

    public function get_filtro_x_campo($cod_campo)
    {
        $filtro = [];

        if (
            isset($this->s__cod_proceso) &&
            isset($this->s__cod_actividad) &&
            isset($cod_campo)
        ) {
            $valores_posible = dao_bp_procesos::get_valores_posibles(
                $this->s__cod_proceso,
                $this->s__cod_actividad,
                strtoupper($cod_campo)
            );

            $filtro[$cod_campo] = explode(',', $valores_posible);
        }

        return $filtro;
    }

    public function get_valor_por_defecto_x_campo($cod_campo)
    {
        if (
            !isset($this->s__cod_proceso) ||
            !isset($this->s__cod_actividad) ||
            !isset($cod_campo)
        ) {
            return;
        }

        return dao_bp_procesos::get_valor_por_defecto(
            $this->s__cod_proceso,
            $this->s__cod_actividad,
            strtoupper($cod_campo)
        );
    }

    public function setear_onload($onload)
    {
        $this->s__onload = $onload;
    }

    public function get_parametros_proceso_actividad()
    {
        return [
            'id_proceso' => $this->s__id_proceso,
            'id_actividad' => $this->s__id_actividad,
            'cod_proceso' => $this->s__cod_proceso,
            'cod_actividad' => $this->s__cod_actividad,
            'aplicacion_origen' => $this->s__aplicacion_origen,
        ];
    }

    //-----------------------------------------------------------------------------------
    //---- modal_imprimir ---------------------------------------------------------------
    //-----------------------------------------------------------------------------------
    function evt__modal_imprimir__aceptar($datos)
    {
        $parametros = json_decode($datos['parametros'],true);
        $parametros = $this->tratar_parametros_reporte($parametros);
        $this->reporte = new generador_reportes($datos['reporte']);
        $this->reporte->llamar_reporte($parametros, $datos['formato']);
    }

    public function formatear_cuadro($posicion, $estado_1, $estado_2)
    {
        toba::logger()->warning(
            "[DEPRECADO] Considere reemplazar la llamada a `principal_ci->formatear_cuadro` por el uso de la función `colorearCuadroTobaSegunEstados`. Para más información acceder a http://10.1.1.20/documentacion y buscar la función."
        );
        return "
            window.addEventListener('DOMContentLoaded', function() {
                var filas_impares= document.getElementsByClassName('ei-cuadro-celda-impar');
                var filas_pares= document.getElementsByClassName('ei-cuadro-celda-par');

                for(var i=0; i < filas_impares.length; i++){
                    var campos= filas_impares[i].getElementsByTagName('td');

                    var estado= campos[$posicion].textContent;
                    estado= estado.replace(new RegExp( '\\n', 'g' ), '');
                    estado= estado.toUpperCase();

                    if (estado == '$estado_1'){
                        filas_impares[i].setAttribute('style', 'color: green');
                    }else if( estado == '$estado_2'){
                        filas_impares[i].setAttribute('style', 'color: red');
                    }
                }

                for(var i=0; i < filas_pares.length; i++){
                    var campos= filas_pares[i].getElementsByTagName('td');

                    var estado= campos[$posicion].textContent;
                    estado= estado.replace(new RegExp( '\\n', 'g' ), '');
                    estado= estado.toUpperCase();

                    if (estado == '$estado_1'){
                        filas_pares[i].setAttribute('style', 'color: green');
                    }else if( estado == '$estado_2'){
                        filas_pares[i].setAttribute('style', 'color: red');
                    }
                }
            });
            ";
    }

    public function formatear_cuadro_confimado_anulado(
        $posicion_confirmado,
        $posicion_anulado
    ) {
        toba::logger()->warning(
            "[DEPRECADO] Considere reemplazar la llamada a `principal_ci->formatear_cuadro` por el uso de la función `colorearCuadroTobaSegunEstados`. Para más información acceder a http://10.1.1.20/documentacion y buscar la función."
        );
        return "
            window.onload = function(){
                var filas_impares= document.getElementsByClassName('ei-cuadro-celda-impar');
                var filas_pares= document.getElementsByClassName('ei-cuadro-celda-par');

                for(var i=0; i < filas_impares.length; i++){
                    var campos= filas_impares[i].getElementsByTagName('td');

                    var estado_confirmado= campos[$posicion_confirmado].textContent;
                    estado_confirmado= estado_confirmado.replace(new RegExp( '\\n', 'g' ), '');
                    estado_confirmado= estado_confirmado.toUpperCase();

                    var estado_anulado= campos[$posicion_anulado].textContent;
                    estado_anulado= estado_anulado.replace(new RegExp( '\\n', 'g' ), '');
                    estado_anulado= estado_anulado.toUpperCase();

                    if (estado_anulado == 'SI'){
                        filas_impares[i].setAttribute('style', 'color: red');
                    }else if( estado_confirmado == 'SI'){
                        filas_impares[i].setAttribute('style', 'color: green');
                    }
                }

                for(var i=0; i < filas_pares.length; i++){
                    var campos= filas_pares[i].getElementsByTagName('td');

                    var estado_confirmado= campos[$posicion_confirmado].textContent;
                    estado_confirmado= estado_confirmado.replace(new RegExp( '\\n', 'g' ), '');
                    estado_confirmado= estado_confirmado.toUpperCase();

                    var estado_anulado= campos[$posicion_anulado].textContent;
                    estado_anulado= estado_anulado.replace(new RegExp( '\\n', 'g' ), '');
                    estado_anulado= estado_anulado.toUpperCase();

                    if (estado_anulado == 'SI'){
                        filas_pares[i].setAttribute('style', 'color: red');
                    }else if( estado_confirmado == 'SI'){
                        filas_pares[i].setAttribute('style', 'color: green');
                    }
                }
            }
        ";
    }

    public function formatear_cuadro_ids($ids, $posicion, $color)
    {
        toba::logger()->warning(
            "[DEPRECADO] Considere reemplazar la llamada a `principal_ci->formatear_cuadro` por el uso de la función `colorearCuadroTobaSegunEstados`. Para más información acceder a http://10.1.1.20/documentacion y buscar la función."
        );
        $first = $ids[0] ?: '';
        $text = "var ids = [$first";

        for ($i = 1; $i < count($ids); ++$i) {
            $text .= ", {$ids[$i]}";
        }
        $text .= '];';

        return "
            $text

            function pintar_filas() {
                var filas_impares =
                    document.getElementsByClassName('ei-cuadro-celda-impar');
                var filas_pares = document.getElementsByClassName('ei-cuadro-celda-par');

                for (var i=0; i < filas_impares.length; i++) {
                    var campos= filas_impares[i].getElementsByTagName('td');

                    var id= campos[$posicion].textContent;
                    id= id.replace(new RegExp( '\\n', 'g' ), '');
                    id= id.toUpperCase();

                    if (ids.indexOf(id) >= 0){
                        filas_impares[i].setAttribute('style', 'color: $color');
                    }
                }

                for(var i=0; i < filas_pares.length; i++){
                    var campos= filas_pares[i].getElementsByTagName('td');

                    var id= campos[$posicion].textContent;
                    id= id.replace(new RegExp( '\\n', 'g' ), '');
                    id= id.toUpperCase();

                    if (ids.indexOf(id) >= 0){
                        filas_pares[i].setAttribute('style', 'color: $color');
                    }
                }
            }
        ";
    }

    //////////////////////////
    // JavaScript
    //////////////////////////
    public function extender_objeto_js()
    {
        toba::logger()->warning('[DEPRECADO] Considere eliminar el JS de principal_ci. Mucho código duplicado junto a rentas_ci');
        //Vista reporte de la clase "generador_reportes"
        if (isset($this->reporte)){
            $this->reporte->generar_html();
            unset($this->reporte);
        }

        //Evento imprimir Generico, recibe el nombre del reporte, los parametros y permite seleccionar el formato de salida.
        echo "
        {$this->objeto_js}.mostrar_modal_imprimir = function (datos)
        {
            var parametros = datos['parametros'];
            var reporte = datos['reporte'];
            console.log(parametros);
            console.log(reporte);

            for(var i in parametros)
            {
                console.log(i);
                console.log(parametros[i]);
            }

            this.dep('modal_imprimir').ef('reporte').set_estado(reporte);
            this.dep('modal_imprimir').ef('parametros').set_estado(JSON.stringify(parametros));
            document.getElementById('modal_imprimir').setAttribute('class','modalForm modalFormAct');
            return false;
        }
        ";

        //Para mostrar los reportes en pantalla
        if ($this->reporte_generado) {
            echo "
            win = window.open(
                '{$this->url_reporte}',
                '_blank',
                'scrollbars=1,resizable,height=600,width=600'
            );";

            $this->reporte_generado = false;
        }

        if ($this->existe_evento('imprimir')){
            echo "console.log('Existe Evento Imprimir')

            {$this->objeto_js}.consultar_formato = function()
            {
                console.log('Evento Imprimir');
            }

            ";
        }

        //funcion generica para encolar funciones onload
        echo "
        function addLoadEvent(func) {
          var oldonload = window.onload;
          if (typeof window.onload != 'function') {
            window.onload = func;
          } else {
            window.onload = function() {
              if (oldonload) {
                oldonload();
              }
              if (typeof func == 'function'){
                func();
                }
            }
          }
        }
        ";

        echo "
        function combo_click() {
            var container = $('.toolbarBlock');
            $(document).mousedown(function (e) {
                if (
                    // if the target of the click isn't the container...
                    !container.is(e.target) &&
                    // ... nor a descendant of the container
                    (container.has(e.target).length === 0) &&
                    // nor the scrollbar
                    (e.target != $('html').get(0)) &&
                    (e.target.className != 'dhx_selected_option')
                ) {
                    if (window.dhx_glbSelectAr)
                        for (var i = 0; i < dhx_glbSelectAr.length; i++) {
                          if (dhx_glbSelectAr[i].DOMlist.style.display == 'block') {
                            dhx_glbSelectAr[i].DOMlist.style.display = 'none';
                            if (_isIE) dhx_glbSelectAr[i]._IEFix(false)
                          }
                          dhx_glbSelectAr[i]._activeMode = false;
                        }
                }
           });
        }";

        echo "//cargar funciones onload
            addLoadEvent(combo_click);
            $this->s__onload";
        echo "
        var botonera= $('#botonera');
        var cuerpo_principal= $('.cuerpo');
        var encabezado= $('.encabezado');
        var barra_sup= $('.ei-barra-sup:not(.intervan-tabulator > .ei-barra-sup)');
        var div_bot_list= barra_sup[0];
        var botones= null;
        var botones_edicion= null;

        if (div_bot_list != 'undefined' && div_bot_list != null){
            var div_bot_edicion= barra_sup[barra_sup.length-1];
            botones= div_bot_list.getElementsByClassName('ei-botonera ');
            botones_edicion= div_bot_edicion.getElementsByClassName('ei-botonera ');
        }
        var menu2= encabezado[0].getElementsByClassName('horizontal');

        if(barra_sup.length >= 1){
            botonera.append(barra_sup[0]);
            botonera.append(barra_sup[1]);

            if ((botones != null)&&(botones.length == 0)) {
//                botonera.attr('style', 'margin-top: -22px !important;');
//                encabezado.attr('style', 'margin-top: -90px !important;');
//                cuerpo_principal.attr('style', 'margin-top: 90px !important;');
            } else{
                if (menu2.length == 0){
//                    botonera.attr('style', 'margin-top: -60px !important;');
//                    encabezado.attr('style', 'margin-top: -90px !important;');
//                    cuerpo_principal.attr('style', 'margin-top: 90px !important;');
                }else{
//                    botonera.attr('style', 'margin-top: -55px !important;');
//                    encabezado.attr('style', 'margin-top: -120px !important;');
//                    cuerpo_principal.attr('style', 'margin-top: 120px !important;');
                }
            }
        } else {
            cuerpo_principal.attr('style', 'margin-top: 50px !important;');
            botonera.attr('style', 'margin-top: 0px !important;');
        }";

        //hint para los campos de solo lectura
        echo"
        var campos_solo_lectura=
            document.getElementsByClassName('ef-input-solo-lectura');

        for (var i= 0; i < campos_solo_lectura.length; i++) {
            campos_solo_lectura[i].setAttribute('title', 'Solo lectura');
        }
        ";
    }

    public function bloquear_ventana_boton($id_ci, $boton, $mensaje)
    {
        //id_ci es el codigo que identifica el ci donde esta definido el boton
        //boton es el nombre que tiene
        //mensaje es lo que se muestra en la ventana que bloquea

        return "
        //$boton
        (() => {
            var btn = document.getElementById('$id_ci$boton');

            if (!btn){
                return;
            }
            btn.addEventListener('click', function() {
                var div_overlay = document.getElementById('overlay');
                var content = document.getElementById('overlay_contenido');
                var ps = content.getElementsByTagName('p');

                div_overlay.className = 'overlay_block';

                if (ps[0] != null){
                    content.removeChild(ps[0]);
                }
                var texto = document.createElement('p');

                texto.textContent= '$mensaje';
                content.appendChild(texto);

                window.onfocus = function() {
                    var ven = window.parent.frames;
                    var ventana_popup = ven.ventana_hija;
                    var keys = Object.keys(ventana_popup);

                    for (var i= 0; i < keys.length; i++){
                        if (ventana_popup[keys[i]] != null){
                            ventana_popup= ventana_popup[keys[i]];
                            delete ven.ventana_hija.ventana_popup;
                            break;
                        }
                    }

                    if (ventana_popup.closed) {
                        var div_overlay = document.getElementById('overlay');

                        div_overlay.className = null;
                    }
                };
            });
        })();
        ";
    }

    protected function completar_comprobante(
        $id_proceso = null,
        $id_actividad = null,
        $cod_proceso = null,
        $cod_actividad = null,
        $id_comprobante = null
    ) {
        if (
            !isset($id_proceso)
            && isset($id_comprobante)
            && isset($this->s__cod_proceso)
            && isset($this->s__cod_actividad)
            && isset($this->s__id_proceso)
            && isset($this->s__id_actividad)
        ) {
            dao_bp_procesos::insertar_comprobante(
                $this->s__id_proceso,
                $this->s__id_actividad,
                $id_comprobante,
                $this->s__cod_proceso,
                $this->s__cod_actividad
            );
        }
        if (isset($id_proceso) && isset($id_comprobante)) {
            dao_bp_procesos::insertar_comprobante(
                $id_proceso,
                $id_actividad,
                $id_comprobante,
                $cod_proceso,
                $cod_actividad
            );
        }
    }

    protected function eliminar_comprobante(
        $id_proceso = null,
        $id_actividad = null,
        $id_comprobante = null
    ) {
        if (
            !isset($id_proceso)
            && isset($id_comprobante)
            && isset($this->s__id_proceso)
            && isset($this->s__id_actividad)
        ) {
            dao_bp_procesos::borrar_comprobante(
                $this->s__id_proceso,
                $this->s__id_actividad,
                $id_comprobante
            );
        }
        if (
            isset($id_proceso)
            && isset($id_comprobante)
            && !empty($id_comprobante)
            && isset($id_actividad)
            && !empty($id_actividad)
        ) {
            dao_bp_procesos::borrar_comprobante(
                $id_proceso,
                $id_actividad,
                $id_comprobante
            );
        }
    }

    protected function get_comprobantes_anteriores()
    {
        if (!isset($this->s__id_proceso) || !isset($this->s__id_actividad)) {
            return;
        }

        return dao_bp_procesos::get_comprobantes_anteriores(
            $this->s__id_proceso,
            $this->s__id_actividad
        );
    }

    protected function set_titulo_operacion()
    {
        // Seteo el titulo de la operacion
        $titulo = toba::solicitud()->get_datos_item('item_nombre');
        $parametros = [];

        if (isset($this->s__aplicacion_origen)) {
            toba::logger()->info("aplicacion_origen: {$this->s__aplicacion_origen}");
        }
        if (isset($this->s__cod_proceso)) {
            $parametros[] = 'cod_proceso: '.$this->s__cod_proceso;
        }
        if (isset($this->s__cod_actividad)) {
            $parametros[] = 'cod_actividad: '.$this->s__cod_actividad;
        }
        if (isset($this->s__id_proceso)) {
            $parametros[] = 'id_proceso: '.$this->s__id_proceso;
        }
        if (isset($this->s__id_actividad)) {
            $parametros[] = 'id_actividad: '.$this->s__id_actividad;
        }
        if (!empty($parametros)) {
            $titulo .= ' - '.implode(' - ', $parametros);
        }

        toba::solicitud()->set_datos_item(['item_nombre' => $titulo]);
    }

    private function get_clave_carga($campos, $valores)
    {
        $arr_campos = explode(',', $campos);
        $arr_valores = explode(',', $valores);
        $clave_carga = [];

        foreach ($arr_campos as $clave => $campo) {
            $clave_carga[trim($campo)] = !isset($arr_valores[$clave])
                ? null
                : trim($arr_valores[$clave]);
        }

        return $clave_carga;
    }

    /*
     * Esta funcion es llamada siempre desde evt__modal_imprimir__aceptar
     * Antes de enviar los parametros al reporte para permitir hacer una oprecion
     * adicional sobre los datos. Para tal caso, deberá ser redefinada en el controlador.
     */
    function tratar_parametros_reporte ($parametros)
    {
        return $parametros;
    }
}
