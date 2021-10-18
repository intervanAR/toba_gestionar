<?php

class dao_bancos
{
	static public function get_bancos($filtro=array())
    {	
    	$where = " 1=1 ";
    	if (isset($filtro['descripcion'])){
    		$where .= " AND KB.DESCRIPCION LIKE '%".$filtro['descripcion']."%' ";
    		unset($filtro['descripcion']);
    	}
    	if (!empty($filtro))
    		$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'kb', '1=1');
    		
	    $sql = "SELECT kb.*, DECODE(kb.activo, 'S', 'Si','No') as activo_format  
			      FROM kr_bancos kb
			     WHERE $where
			  ORDER BY KB.ID_BANCO DESC";
	    $datos = toba::db()->consultar($sql);
	    return $datos;
	}
	
	static public function get_lov_banco_x_id($id_banco) {
        if (isset($id_banco)) {
            $sql = "SELECT b.*, b.id_banco||' - '||b.descripcion lov_descripcion
                        FROM KR_BANCOS b
                        WHERE id_banco = " . quote($id_banco) . ";";

            $datos = toba::db()->consultar_fila($sql);

            if (isset($datos) && !empty($datos) && isset($datos['lov_descripcion'])) {
                return $datos['lov_descripcion'];
            } else {
                return '';
            }
        } else {
            return '';
        }
    }
	
	static public function get_lov_bancos_x_nombre($nombre, $filtro = array()) {
        if (isset($nombre)) {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('id_banco', $nombre);
            $trans_nombre = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
            $where = "($trans_codigo OR $trans_nombre)";
        } else {
            $where = '1=1';
        }
		
        $where .= 'AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'b', '1=1');
		
        $sql = "SELECT b.*, b.id_banco||' - '|| b.descripcion as lov_descripcion
		    FROM KR_BANCOS b
		    WHERE $where
		    ORDER BY lov_descripcion ASC;";        
       
        $datos = toba::db()->consultar($sql);
        return $datos;
    }
	
	public static function get_movimientos_banco($filtro=array())
	{
		$where = " 1=1 ";
		$where .= ' AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'KRMOBA', '1=1');

        $sql = "SELECT	KRMOBA.*,
						to_char(KRMOBA.FECHA_MOVIMIENTO, 'dd/mm/yyyy') fecha_movimiento_format,
						SUBSTR(KRMOBA.DETALLE,1,400) UI_DETALLE,
						KRMOBA.HABER IMPORTE,
						KRMOBA.NRO_MOVIMIENTO || ' (ID: ' || KRMOBA.ID_MOVIMIENTO_BANCO || ') ' || to_char(KRMOBA.FECHA_MOVIMIENTO,'dd/mm/rr') || ' (' || SUBSTR(KRMOBA.DETALLE,1,400) || ')    Debe: ' || trim(to_char(KRMOBA.DEBE, '$999,999,999,990.00')) || '    Importe: ' || trim(to_char(KRMOBA.HABER, '$999,999,999,990.00')) lov_descripcion,
						'N' as confirmado,
						KRCUBA.nro_cuenta || '  (ID: ' || KRCUBA.id_cuenta_banco || ') - ' || KRCUBA.descripcion cuenta_banco,
						CASE
							WHEN KRSUCUBA.cod_sub_cuenta_banco IS NOT NULL THEN KRSUCUBA.cod_sub_cuenta_banco || ' - ' || KRSUCUBA.descripcion
							ELSE '' 
						END sub_cuenta_banco,
						CASE
							WHEN KRCUBA.tipo_cuenta_banco = 'CAJ' THEN 'Caja'
							WHEN KRCUBA.tipo_cuenta_banco = 'BAN' THEN 'Banco'
						END tipo_cuenta_banco_des	
				FROM KR_MOVIMIENTOS_BANCO KRMOBA
				JOIN KR_CUENTAS_BANCO KRCUBA ON (KRMOBA.id_cuenta_banco = KRCUBA.id_cuenta_banco)
				LEFT OUTER JOIN KR_SUB_CUENTAS_BANCO KRSUCUBA ON (KRMOBA.id_sub_cuenta_banco = KRSUCUBA.id_sub_cuenta_banco)
				WHERE $where
                ORDER BY id_movimiento_banco ASC;";		
		
        $datos = toba::db()->consultar($sql);

        return $datos;
	}
}
?>
