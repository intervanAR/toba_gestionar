<?php 


class dao_tipos_embargos {

	public static function get_tipos_embargos($filtro = [])
	{
		$where = ' 1=1 ';
		if (isset($filtro))
		{
			$where = ctr_construir_sentencias::get_where_filtro($filtro, 'ADTE', '1=1', array('nombre'));
		}
		$sql = "
			SELECT ADTE.*, DECODE (adte.por_factura, 'S', 'Si', 'No') por_factura_format
					,admp.COD_MEDIO_PAGO||' - '||admp.DESCRIPCION medio_pago
					,krcc.NRO_CUENTA_CORRIENTE ||' - '||krcc.DESCRIPCION cuenta_corriente
			  FROM ad_tipos_embargos adte, ad_medios_pago admp, kr_cuentas_corriente krcc
			  where adte.COD_MEDIO_PAGO = admp.COD_MEDIO_PAGO
			  and adte.ID_CUENTA_CORRIENTE = krcc.ID_CUENTA_CORRIENTE
				and $where
			ORDER BY
				ADTE.COD_TIPO ";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

}