<?php

class ctr_formatear_cadenas
{
    const CADENA_ESPECIALES = 'ÀÁÂÃÄÅàáâãäåÈÉÊËèéêëÌÍÎÏìíîïÒÓÔÕÖØòóôõöøðÙÚÛÜùúûüÿýÝçÇÑñ';
    const CADENA_BASICOS = 'AAAAAAaaaaaaEEEEeeeeIIIIiiiiOOOOOOoooooooUUUUuuuuyyYcCNn';

    const LISTA_ESPECIALES = 'À,Á,Â,Ã,Ä,Å,à,á,â,ã,ä,å,È,É,Ê,Ë,è,é,ê,ë,Ì,Í,Î,Ï,ì,í,î,ï,Ò,Ó,Ô,Õ,Ö,Ø,ò,ó,ô,õ,ö,ø,ð,Ù,Ú,Û,Ü,ù,ú,û,ü,ÿ,ý,Ý,ç,Ç,Ñ,ñ';
    const LISTA_BASICOS = 'A,A,A,A,A,A,a,a,a,a,a,a,E,E,E,E,e,e,e,e,I,I,I,I,i,i,i,i,O,O,O,O,O,O,o,o,o,o,o,o,o,U,U,U,U,u,u,u,u,y,y,Y,c,C,N,n';

    const LISTA_PALABRAS_BASICAS_IGNORADAS = ',a,ante,bajo,cabe,con,contra,de,del,desde,detras,e,el,en,entre,hacia,hasta,la,las,lo,los,o,otra,otro,para,por,que,no,segun,si,sin,sobre,su,sus,tras,u,un,una,unas,uno,unos,y,';

    public static function reemplazar_caracteres_especiales($cadena)
    {
        $cadena = strtr($cadena, self::CADENA_ESPECIALES, self::CADENA_BASICOS);

        $buscar[] = '\'';
        $reemplazar[] = '-';

        $buscar[] = '–';
        $reemplazar[] = '-';

        $buscar[] = '“';
        $reemplazar[] = '';

        $buscar[] = '”';
        $reemplazar[] = '';

        $buscar[] = '«';
        $reemplazar[] = '';

        $buscar[] = '»';
        $reemplazar[] = '';

        $buscar[] = '"';
        $reemplazar[] = '';

        $cadena = str_replace($buscar, $reemplazar, $cadena);

        return $cadena;
    }

    public function validar_palabra_no_ignorada($palabra)
    {
        $palabra = strtolower(self::reemplazar_caracteres_especiales($palabra));
        if (strpos(self::LISTA_PALABRAS_BASICAS_IGNORADAS, ','.$palabra.',') === false) {
            return true;
        } else {
            return false;
        }
    }

    /* Agrega botones para expandir y colapsar un texto largo.
     * Para utilizarlo se debe definir el ef de tipo ef_fijo que permita html en el estado.
     * En la configuracion del formulario se debe asignar a este ef el texto formateado con esta funcion.
     * Parametros:
        * texto: texto largo al que se le aplicara la logica de colapsado y expansion
        * clave: clave unica que representara al campo en la pagina actual. Ejemplo del campo observacion: Si es un formalario comun se puede usar 'observaciones', en cambio si es un formulario ml o un cuadro se puede usar el indice de la fila, por ejemplo 'observaciones_1', 'observaciones_2', ect.
        * max_caracteres: cantidad de caracteres necesarios para agregar la logica de expansion/colapsado
        * max_lineas: cantidad de renglones necesarios para agregar la logica de expansion/colapsado
        * espacios_irromplibles: booleano que determina si se va utilizar espacios irromplibles, con el efecto de mostrar el texto en un renglon.
     *
     * Ejemplo de utilizacion:
     *	//-----------------------------------------------------------------------------------
     *	//---- formulario_ml_detalle --------------------------------------------------------
     *	//-----------------------------------------------------------------------------------
     *	function conf__formulario_ml_detalle(administracion_ei_formulario_ml $form_ml)
     *	{
     *		$datos = parent::conf__formulario_ml_detalle($form_ml);
     *		foreach($datos as $clave => $dato) {
     *			$datos[$clave]['observaciones'] = ctr_formatear_cadenas::agregar_expandir_colapsar_texto($dato['observaciones'], 'observaciones'.$clave, 100, 3);
     *		$form_ml->set_datos($datos);
     *	}
     */
    public static function agregar_expandir_colapsar_texto($texto, $clave, $max_caracteres, $max_lineas, $espacios_irrompibles = false)
    {
        $texto = str_replace('<', '«', $texto);
        $texto = str_replace('>', '»', $texto);
        $texto_format = '';
        if (strlen($texto) > $max_caracteres && $max_caracteres >= 0 || count(explode("\n", $texto)) > $max_lineas) {
            $boton_expandir_texto = "&nbsp;&nbsp;<a style='cursor:pointer;color:blue;' title='Ver más' onClick=\"if(document.getElementById('contenedortextomensajecolapsado_$clave').style.display!='none'){document.getElementById('contenedortextomensajecolapsado_$clave').style.display='none';document.getElementById('contenedortextomensajecompleto_$clave').style.display='block';}\">[...]</a>";
            $boton_colapsar_texto = "&nbsp;&nbsp;<a style='cursor:pointer;color:blue;' title='Colapsar mensaje' onClick=\"if(document.getElementById('contenedortextomensajecolapsado_$clave').style.display=='none'){document.getElementById('contenedortextomensajecolapsado_$clave').style.display='block';document.getElementById('contenedortextomensajecompleto_$clave').style.display='none';}\">[&nbsp;<&nbsp;]</a>";
        } else {
            $boton_colapsar_texto = '';
            $boton_expandir_texto = '';
        }

        if (strlen($texto) > $max_caracteres && $max_caracteres >= 0 || count(explode("\n", $texto)) > $max_lineas) {
            $cadena = substr($texto, 0, $max_caracteres);
            $indice = strrpos($cadena, ' ');
            $cadena = substr($cadena, 0, $indice);

            $lineas = explode("\n", $cadena);
            $cant_lineas = count($lineas);

            if ($cant_lineas > $max_lineas) {
                $cadena_truncada = '';
                for ($i = 0; $i < $max_lineas; ++$i) {
                    $cadena_truncada = $cadena_truncada."\n".$lineas[$i];
                }
            } else {
                $cadena_truncada = $cadena;
            }
            if ($espacios_irrompibles) {
                $cadena_truncada = str_replace(' ', '&nbsp;', $cadena_truncada);
                $texto = str_replace(' ', '&nbsp;', $texto);
            }
            $texto_format .= '<div id="contenedortextomensajecolapsado_'.$clave.'" style="display:block;">'.$cadena_truncada.$boton_expandir_texto.'</div>';
            $texto_format .= '<div id="contenedortextomensajecompleto_'.$clave.'" style="display:none;">'.$texto.$boton_colapsar_texto.'</div>';
        } else {
            if ($espacios_irrompibles) {
                $texto = str_replace(' ', '&nbsp;', $texto);
            }
            $texto_format .= '<div id="contenedortextomensajecolapsado_'.$clave.'" style="display:block;">'.$texto.$boton_expandir_texto.'</div>';
        }

        return $texto_format;
    }
}
