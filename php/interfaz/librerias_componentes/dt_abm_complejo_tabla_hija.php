<?php

class dt_abm_complejo_tabla_hija extends principal_datos_tabla
{
    protected $clave = ''; // clave de la tabla que no se actualiza por medio de un trigger

    public function procesar_filas($filas, $ids_padres = null)
    {
        if (isset($this->clave) && !empty($this->clave) && isset($filas) && !empty($filas)) {
            $array_claves = ctr_funciones_basicas::matriz_to_array($filas, $this->clave);
            if (isset($array_claves) && !empty($array_claves)) {
                $nueva_clave = max($array_claves) + 1;
            } else {
                $nueva_clave = 1;
            }
            foreach ($filas as $clave => $fila) {
                if (!isset($fila[$this->clave]) || empty($fila[$this->clave])) {
                    $filas[$clave][$this->clave] = $nueva_clave;
                    ++$nueva_clave;
                }
            }
        }
        parent::procesar_filas($filas, $ids_padres);
    }
}
