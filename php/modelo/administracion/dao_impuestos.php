<?php

class dao_impuestos
{

	static public function get_impuestos ($filtro=[])
    {
        $where = "1= 1";
        $where.= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'adimp', '1=1');
        $sql = "SELECT adimp.*,
				       DECODE (adimp.controlar_en_cierre,
				               'S', 'Si',
				               'No'
				              ) controlar_en_cierre
				  FROM ad_impuestos adimp
				WHERE $where";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }

	static public function get_impuesto_x_codigo($cod_impuesto){
	   if (isset($cod_impuesto)) {
		   $sql = "SELECT ADIM.*, ADIM.cod_impuesto ||' - '|| ADIM.descripcion as lov_descripcion
				   FROM AD_IMPUESTOS ADIM
				   WHERE ADIM.cod_impuesto = ".quote($cod_impuesto) .";";
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
   static public function get_lov_impuesto_x_nombre($nombre, $filtro = array()){
	  if (isset($nombre)) {
		  $trans_cod = ctr_construir_sentencias::construir_translate_ilike('cod_impuesto', $nombre);
		  $trans_des = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
		  $where = "($trans_cod OR $trans_des)";
	  } else {
		  $where = '1=1';
	  }

	  if (isset($filtro['cod_tipo_factura'])) {
		  $where .= " AND exists(select 1 from ad_tipos_factura_impuesto where cod_impuesto = ADIM.cod_impuesto and automatico = 'N' and cod_tipo_factura = " . quote($filtro['cod_tipo_factura']) . ") ";
		  unset($filtro['cod_tipo_factura']);
	  }

	  if (isset($filtro['cod_concepto'])) {
		  $where .= " AND exists(select 1 from ad_conceptos_impuestos where cod_impuesto = ADIM.cod_impuesto AND cod_concepto = " . quote($filtro['cod_concepto']) . ") ";
		  unset($filtro['cod_concepto']);
	  }

	  $where.= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'ADIM', '1=1');

	  $sql=   "SELECT ADIM.*, ADIM.COD_IMPUESTO ||' - '|| ADIM.DESCRIPCION as lov_descripcion
			   FROM AD_IMPUESTOS ADIM
			   WHERE $where
			   ORDER BY lov_descripcion";
	  $datos = toba::db()->consultar($sql);
	  return $datos;
   }

}
?>
