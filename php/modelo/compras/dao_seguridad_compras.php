<?php

class dao_seguridad_compras {

    public static function get_objetos_validar_toba($operacion, $estado) {
		if (isset($operacion) && isset($estado)) {
			$sql_sel = "(SELECT * 
						FROM SSE_OBJETOS_VALIDAR_TOBA sovt
						WHERE sovt.operacion = " . quote($operacion) . "
						AND sovt.estado = " . quote($estado) . "
						UNION
						SELECT *
						FROM SSE_OBJETOS_VALIDAR_TOBA sovt
						WHERE sovt.operacion = " . quote($operacion) . "
						AND NOT EXISTS (SELECT 1
										FROM SSE_OBJETOS_VALIDAR_TOBA sovt1
										WHERE sovt1.operacion = sovt.operacion
										AND sovt1.tipo_objeto = sovt.tipo_objeto
										AND sovt1.objeto = sovt.objeto
										AND sovt1.estado = " . quote($estado) . ")
						AND sovt.estado = '%')
						ORDER BY 3 ASC;";
			$datos = toba::db()->consultar($sql_sel);

			return $datos;
		} else { 
			return array();
		}
    }
	
}

?>
