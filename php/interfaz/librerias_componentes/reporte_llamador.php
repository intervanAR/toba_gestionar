<?php

class reporte_llamador
{
    //Time out para el llamado.
    const TIMEOUT = 0;
    /**
     * Nombre del reporte pasado por parametro en la operacion.
     * Equivalente al nombre en la tabla.
     */
    private $nombre_reporte = null;
    private $ruta_entrada_oracle = null;
    private $ruta_salida_oracle = null;
    private $ruta_entrada_pentaho = null;
    private $ruta_salida_pentaho = null;
    private $reporte_generado = false;
    private $base = null;
    private $esquema = null;
    private $usuario = null;

    private $ruta_entrada_jasper = null;
    private $ruta_salida_jasper = null;
    private $p_sistema = '';

    public function __construct($nombre_reporte)
    {
        $parametros_base = toba::db()->get_parametros();

        if (isset($parametros_base['usuario'])) {
            //nombre de usuario de la Base.
            $this->usuario = $parametros_base['usuario'];
        }
        $this->nombre_reporte = $nombre_reporte;
        // 1.1. Recupero Nombre de la base.
        $this->base = $parametros_base['base'];
        // 1.2. Recupero Esquema de la base.
        $this->esquema = $parametros_base['schema'];
        // 1.3. Recupero el archivo 'bases.ini'
        $bases_ini = toba_dba::get_bases_definidas();

        //Configuracion para llamar a Reportes Pentaho
        if (isset($bases_ini['reportes pentaho']['ruta_entrada']) & isset($bases_ini['reportes pentaho']['ruta_salida'])) {
            $this->ruta_entrada_pentaho = $bases_ini['reportes pentaho']['ruta_entrada'];
            $this->ruta_salida_pentaho = $bases_ini['reportes pentaho']['ruta_salida'];
            if (isset($bases_ini['reportes pentaho']['p_sistema'])) {
                $this->p_sistema = $bases_ini['reportes pentaho']['p_sistema'];
            }
        }

        //Configuracion para llamar a Reportes Oracle
        if (isset($bases_ini['reportes oracle']['ruta_entrada'])) {
            $ruta_entrada = $bases_ini['reportes oracle']['ruta_entrada'];
            $ruta_salida = $bases_ini['reportes oracle']['ruta_salida'];

            /* Evalua si es necesario agregar el nombre del host */
            if (strpos($ruta_entrada, 'http') !== 0) {
                $this->ruta_entrada_oracle = 'http://'.$this->get_host().$bases_ini['reportes oracle']['ruta_entrada'];
            } else {
                $this->ruta_entrada_oracle = $bases_ini['reportes oracle']['ruta_entrada'];
            }

            if (strpos($ruta_salida, 'http') !== 0) {
                $this->ruta_salida_oracle = 'http://'.$this->get_host().':'.$this->get_port().$bases_ini['reportes oracle']['ruta_salida'];
            } else {
                $this->ruta_salida_oracle = $bases_ini['reportes oracle']['ruta_salida'];
            }
        } else {
            toba::notificacion()->info("Debe setear los parametros 'ruta_entrada' y 'ruta_salida' en la sección 'reportes oracle' del bases.ini");
        }
    }

    public function set_nombre_reporte($nombre_reporte)
    {
        $this->nombre_reporte = $nombre_reporte;
    }

    private function llamar_get($url, $ruta_salida)
    {
        if (function_exists('curl_init')) { // Comprobamos si hay soporte para cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $resultado = curl_exec($ch);
            $error = strpos($resultado, 'Error');
            if ($error === 0) {
                $respuesta['error'] = 1;
                $respuesta['mensaje'] = $resultado;

                return $respuesta;
            } else {
                if (!empty($resultado)) {
                    $respuesta['error'] = 0;
                    $respuesta['url'] = $ruta_salida.$resultado;
                    $respuesta['ruta_reporte'] = $ruta_salida;
                    $respuesta['nombre_archivo'] = $resultado;
                    $respuesta['mensaje'] = 'Reporte generado.';
                } else {
                    $respuesta['error'] = 1;
                    $respuesta['mensaje'] = 'El servidor no esta disponible';
                }

                return $respuesta;
            }
        } else {
            $respuesta['error'] = 1;
            $respuesta['mensaje'] = 'Conexion no soportada';
        }
    }

    private function llamar_post($postDatos, $ruta_entrada, $ruta_salida)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $ruta_entrada);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postDatos);

            $respuesta = [];
            $resultado = curl_exec($ch);
            $error = strpos($resultado, 'Error');
            if ($error === 0) {
                $respuesta['error'] = 1;
                $respuesta['mensaje'] = $resultado;

                return $respuesta;
            } else {
                if (!empty($resultado)) {
                    $respuesta['error'] = 0;
                    $respuesta['url'] = $ruta_salida.$resultado;
                    $respuesta['ruta_reporte'] = $ruta_salida;
                    $respuesta['nombre_archivo'] = $resultado;
                    $respuesta['mensaje'] = 'Reporte generado.';
                } else {
                    $respuesta['error'] = 1;
                    $respuesta['mensaje'] = 'El servidor no esta disponible';
                }

                return $respuesta;
            }
        } else {
            $respuesta['error'] = 1;
            $respuesta['mensaje'] = 'Conexion no soportada';
        }
    }

    public function llamar_reporte($parametros, $formato, $servicio)
    {
        /*
         * Retorna un arreglo con el siguiente formato:
         * -------------------------------------------
         * [error] 			   // 1 indica que el reporte se genero, 0 caso contrario.
         * [ruta_reporte]	   // Path url donde esta el archivo.
         * [nombre_archivo]    // Nombre del archivo que se genero.
         * [url] 			   // URL completa para el show del archivo
         * [mensaje]
         *
         */

                $usuario = toba::usuario()->get_id();

                //Ajuste para los reportes del proyecto rrhh_sse (autoservicio)
                $id_proyecto = toba::proyecto()->get_id();
        if (strtolower($id_proyecto) === 'rrhh_sse') {
            $usuario = $this->usuario;
            toba::logger()->debug('reporte autoservicio');
        }

        if ($servicio == 'pentaho') {
            //---------------------------------------------------------------------
            // ------- Reportes Pentaho -------------------------------------------
            //---------------------------------------------------------------------
            $url_parametros = '';
            $url = '';
            if (isset($parametros) && !empty($parametros)) {
                foreach ($parametros as $key => $value) {
                    if (empty($parametros[$key])) {
                        $url_parametros .= "&$key=null";
                    } else {
                        $url_parametros .= "&$key=$value";
                    }
                }
            }
            $url = $this->ruta_entrada_pentaho.'?nombreRep='.$this->nombre_reporte.'&tipoSalida='.$formato.'&usuario='.$usuario.'&p_sistema='.$this->p_sistema.$url_parametros;
            $respuesta = $this->llamar_get($url, $this->ruta_salida_pentaho);

            return $respuesta;
        } elseif ($servicio == 'oracle') {
            //---------------------------------------------------------------------
            // ------- Reportes Oracle --------------------------------------------
            //---------------------------------------------------------------------
            $otros = '';
            if (isset($parametros) && !empty($parametros)) {
                foreach ($parametros as $key => $value) { //Construyo el string de parametros
                    $otros .= ' '.$key.'='.$value.'';
                }
            }
            $postDatos = ['usuario' => $usuario,
                                'formato' => $formato,
                                'base' => $this->base,
                                'esquema' => $this->esquema,
                                'reporte' => $this->nombre_reporte,
                                'otros' => $otros, ];
            $respuesta = $this->llamar_post($postDatos, $this->ruta_entrada_oracle, $this->ruta_salida_oracle);

            return $respuesta;
        } else {
            $respuesta['error'] = 1;
            $respuesta['mensaje'] = 'Servicio indicado no disponible';

            return $respuesta;
        }
    }

    /**
     * Retorna direccion ip del servidor donde se encuentra instalado toba.
     */
    public function get_host()
    {
        $http_host = explode(':', $_SERVER['HTTP_HOST']);

        return $http_host[0];
    }

    public function get_port()
    {
        $http_host = explode(':', $_SERVER['HTTP_HOST']);

        // Devuelve puerto implicito si no está seteado
        return isset($http_host[1]) ? $http_host[1] : '80';
    }
}
