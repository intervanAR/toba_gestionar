<?php
class ci_expedientes_edicion extends ci_abm_complejo_edicion
{
	
	public function get_nro_expedientes_x_tipo($cod_tipo_expediente, $usa_auto_numeracion) {
		$datos = $this->controlador()->tabla($this->controlador()->dt_encabezado)->get();
        return dao_expedientes::get_nro_expedientes_x_tipo($datos['nro_expediente'], $cod_tipo_expediente, $usa_auto_numeracion);
    }
	
	//------------------------------------------------------------------------------------
    //-------AJAX----------------------------------------
    //------------------------------------------------------------------------------------    
    public function ajax__obtener_mascara_expedientes($parametros, toba_ajax_respuesta $respuesta) {
        if (isset($parametros[0])) {
            $rta = dao_expedientes::validar_mascara_expedientes($parametros[0],$parametros[1]);
			if (empty($rta['valor'])){
				$respuesta->set(array('rta' => 'El numero de expediente no se corresponde con la mascara.', 'mascara' => ''));			    
			} else {
			    $respuesta->set(array('rta' => 'OK', 'mascara' => $rta['valor']));			    
			}
			
        }else {
				$respuesta->set(array('rta' => 'El numero de expediente no se corresponde con la mascara.', 'mascara' => ''));
			
		}		
    }
	
}
?>