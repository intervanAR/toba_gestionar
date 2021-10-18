<?php

class dao_estados_compra {
	
	public static function get_estados_compra($estados_comprobante, $estado, $cod_sector, $cod_ambito, $tipo_comprobante, $presupuestario = '', $interna, $tipo_compra = '') {
        if (isset($estados_comprobante) && isset($estado) && isset($tipo_comprobante) && isset($interna)) {
        	$sql_orden = "	(SELECT NVL(cgrco.rv_abbreviation, 0) as orden
							FROM   cg_ref_codes cgrco
							WHERE  cgrco.RV_DOMAIN = ". quote($estados_comprobante) . " 
							AND cgrco.RV_LOW_VALUE = " . quote($estado) . ") ";
			
			$sql = "SELECT (CASE 
							WHEN  cgrc.RV_ABBREVIATION > $sql_orden THEN 'OK' 
							WHEN  cgrc.RV_ABBREVIATION < $sql_orden THEN 'no OK' 
							ELSE 'Idem' END) || ' => ' || cgrc.RV_MEANING descripcion, 
							cgrc.RV_LOW_VALUE estado
					FROM   CG_REF_CODES cgrc
					WHERE  cgrc.RV_DOMAIN = " . quote($estados_comprobante) . "
					AND cgrc.RV_ABBREVIATION IS NOT NULL
					AND PKG_ESTADOS.cambio_permitido(". quote($tipo_comprobante) . ", ". quote($tipo_compra) . ", ". quote($presupuestario) . ", ". quote($interna) . ", ". quote($estado) . ", cgrc.RV_LOW_VALUE, 'S') = 'S' 
					AND PKG_USUARIOS.cambio_permitido (" . quote(toba::usuario()->get_id()) . ", ". quote($cod_sector) . ", ". quote($cod_ambito) . ", ". quote($tipo_comprobante) . ", ". quote($tipo_compra) . ", ". quote($presupuestario) . ", ". quote($interna) . ", ". quote($estado) . ", cgrc.RV_LOW_VALUE, 'S') = 'S'
					ORDER BY RV_ABBREVIATION;";
			$datos = toba::db()->consultar($sql);
			return $datos;
		} else {
			return array();
		}
    }

    	
}

?>
