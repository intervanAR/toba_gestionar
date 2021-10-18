<?php 
class dao_tipos_comprobantes {

	public static function get_tipos_comprobantes ($filtro = []){
		$where = ' 1=1 ';
		if (isset($filtro) )
		{
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'adtc', '1=1');
		}
		$sql = "
			SELECT adtc.*
				, decode(adtc.positivo,'S','Si','No') positivo_format
				, decode(adtc.numeracion_aut,'S','Si','No') numeracion_aut_format
				, (select rv_meaning from cg_ref_codes where rv_domain ='AD_TIPOS_COMPROBANTE' and rv_low_value = adtc.tipo_comprobante) tipo_comprobante_format
			FROM
				AD_TIPOS_COMPROBANTE adtc
			WHERE
				$where
			ORDER BY
				adtc.id_tipo_comprobante ASC
		";
		$datos = toba::db()->consultar($sql);

		return $datos;
	}
}