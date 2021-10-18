<?php
class dao_consulta_comprobantes_pago {
	
	static public function get_comprobantes_x_tipo ($cod_tipo_comprobante, $filtro = array ()){
		if (isset($filtro['cod_unidad_administracion'])){
			$where = " 1=1";
			if (isset($filtro)){
				if (isset($filtro['fecha_desde'])){
					$where .=" AND V.FECHA_EMISION >= to_date('".$filtro['fecha_desde']."', 'YYYY-MM-DD')";
					unset($filtro['fecha_desde']);
				}
				if (isset($filtro['fecha_hasta'])){
					$where .=" AND V.FECHA_EMISION <= to_date('".$filtro['fecha_hasta']."','YYYY-MM-DD')";
					unset($filtro['fecha_hasta']);
				}
				
				$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro,'V',' 1=1 ');
			}
			
			if ($cod_tipo_comprobante === 'CHP'){
					$sql = "SELECT V.*, KRCUBA.NRO_CUENTA L_KRCUBA_NRO_CUENTA, KRCUBA.DESCRIPCION L_KRCUBA_DESCRIPCION,
							       KRSCB.COD_SUB_CUENTA_BANCO L_KRSCB_COD_SUB_CUENTA_BANCO, KRSCB.DESCRIPCION L_KRSCB_DESCRIPCION,
							       ADESCOPA.DESCRIPCION L_ADESCOPA_DESCRIPCION,
							       KRMO.DESCRIPCION L_KRMO_DESCRIPCION,
							       atf.id_transferencia_fondos ui_id_transferencia_fondo,
							       arp.id_recibo_pago ui_id_recibo_pago,
							       Pkg_Ad_Comprobantes_Pagos.obtener_id_conciliaciones(v.id_comprobante_pago) AS ui_id_conciliaciones,
							       be.nombre || ' ('|| be.cod_tipo_documento || ' ' || be.nro_documento || ')' ui_nombre_beneficiario
							FROM V_AD_COMPROBANTES_PAGO V 
							     LEFT JOIN ad_comprobantes_pago CPA ON V.ID_COMPROBANTE_PAGO = CPA.ID_COMPROBANTE_PAGO
							     LEFT JOIN KR_CUENTAS_BANCO KRCUBA ON V.ID_CUENTA_BANCO = KRCUBA.ID_CUENTA_BANCO    
							     LEFT JOIN KR_SUB_CUENTAS_BANCO KRSCB ON V.ID_SUB_CUENTA_BANCO = KRSCB.ID_SUB_CUENTA_BANCO 
							     LEFT JOIN AD_ESTADOS_COMP_PAGO ADESCOPA ON V.ESTADO = ADESCOPA.ESTADO
							     LEFT JOIN KR_MONEDAS KRMO ON V.COD_MONEDA = KRMO.COD_MONEDA
							     left join ad_transferencias_fondos atf on cpa.id_comprobante_pago = atf.id_comprobante_pago
							     left join ad_pagos ap on cpa.id_comprobante_pago = ap.id_comprobante_pago
							     left join ad_recibos_pago arp on ap.id_recibo_pago = arp.id_recibo_pago
							     left join ad_cheques_propios chp on cpa.id_comprobante_pago = chp.id_comprobante_pago
							     left join ad_beneficiarios be on chp.id_beneficiario = be.id_beneficiario
							WHERE $where and V.TIPO_COMPROBANTE_PAGO = '$cod_tipo_comprobante'
							ORDER BY V.NRO_COMPROBANTE DESC, 
							         V.ID_COMPROBANTE_PAGO DESC";
				}
			
				
			if ($cod_tipo_comprobante === 'CHT'){
				$sql = "SELECT V.*, KRCUBA.NRO_CUENTA L_KRCUBA_NRO_CUENTA, KRCUBA.DESCRIPCION L_KRCUBA_DESCRIPCION,
							       KRSCB.COD_SUB_CUENTA_BANCO L_KRSCB_COD_SUB_CUENTA_BANCO, KRSCB.DESCRIPCION L_KRSCB_DESCRIPCION,
							       ADESCOPA.DESCRIPCION L_ADESCOPA_DESCRIPCION,
							       KRMO.DESCRIPCION L_KRMO_DESCRIPCION,
							       atf.id_transferencia_fondos ui_id_transferencia_fondo,
							       arp.id_recibo_pago ui_id_recibo_pago,
							       Pkg_Ad_Comprobantes_Pagos.obtener_id_conciliaciones(v.id_comprobante_pago) AS ui_id_conciliaciones,
							       ba.descripcion ui_nombre_banco
							FROM V_AD_COMPROBANTES_PAGO V 
							     LEFT JOIN ad_comprobantes_pago CPA ON V.ID_COMPROBANTE_PAGO = CPA.ID_COMPROBANTE_PAGO
							     LEFT JOIN KR_CUENTAS_BANCO KRCUBA ON V.ID_CUENTA_BANCO = KRCUBA.ID_CUENTA_BANCO    
							     LEFT JOIN KR_SUB_CUENTAS_BANCO KRSCB ON V.ID_SUB_CUENTA_BANCO = KRSCB.ID_SUB_CUENTA_BANCO 
							     LEFT JOIN AD_ESTADOS_COMP_PAGO ADESCOPA ON V.ESTADO = ADESCOPA.ESTADO
							     LEFT JOIN KR_MONEDAS KRMO ON V.COD_MONEDA = KRMO.COD_MONEDA
							     left join ad_transferencias_fondos atf on cpa.id_comprobante_pago = atf.id_comprobante_pago
							     left join ad_pagos ap on cpa.id_comprobante_pago = ap.id_comprobante_pago
							     left join ad_recibos_pago arp on ap.id_recibo_pago = arp.id_recibo_pago
							     left join ad_cheques_terceros chte on cpa.id_comprobante_pago = chte.id_comprobante_pago
		     					 left join kr_bancos ba on chte.ID_BANCO = ba.ID_BANCO
							WHERE $where and V.TIPO_COMPROBANTE_PAGO = '$cod_tipo_comprobante'
							ORDER BY V.NRO_COMPROBANTE DESC, 
							         V.ID_COMPROBANTE_PAGO DESC";
			}
			
			if ($cod_tipo_comprobante ==='TRE'){
				$sql = "SELECT V.*, KRCUBA.NRO_CUENTA L_KRCUBA_NRO_CUENTA, KRCUBA.DESCRIPCION L_KRCUBA_DESCRIPCION,
							       KRSCB.COD_SUB_CUENTA_BANCO L_KRSCB_COD_SUB_CUENTA_BANCO, KRSCB.DESCRIPCION L_KRSCB_DESCRIPCION,
							       ADESCOPA.DESCRIPCION L_ADESCOPA_DESCRIPCION,
							       KRMO.DESCRIPCION L_KRMO_DESCRIPCION,
							       be.nombre || ' ('|| be.cod_tipo_documento || ' ' || be.nro_documento || ')' ui_nombre_beneficiario
							FROM V_AD_COMPROBANTES_PAGO V 
							     LEFT JOIN ad_comprobantes_pago CPA ON V.ID_COMPROBANTE_PAGO = CPA.ID_COMPROBANTE_PAGO
							     LEFT JOIN KR_CUENTAS_BANCO KRCUBA ON V.ID_CUENTA_BANCO = KRCUBA.ID_CUENTA_BANCO    
							     LEFT JOIN KR_SUB_CUENTAS_BANCO KRSCB ON V.ID_SUB_CUENTA_BANCO = KRSCB.ID_SUB_CUENTA_BANCO 
							     LEFT JOIN AD_ESTADOS_COMP_PAGO ADESCOPA ON V.ESTADO = ADESCOPA.ESTADO
							     LEFT JOIN KR_MONEDAS KRMO ON V.COD_MONEDA = KRMO.COD_MONEDA
							     left join AD_TRANSFERENCIAS_EFECTUADAS adte on adte.ID_COMPROBANTE_PAGO = cpa.id_comprobante_pago
							     left join ad_beneficiarios be on be.id_beneficiario = adte.id_beneficiario
							WHERE $where and V.TIPO_COMPROBANTE_PAGO = '$cod_tipo_comprobante'
							ORDER BY V.NRO_COMPROBANTE DESC, 
							         V.ID_COMPROBANTE_PAGO DESC";
			}
		
		 	if ($cod_tipo_comprobante ==='TRR'){
				$sql = "SELECT V.*, KRCUBA.NRO_CUENTA L_KRCUBA_NRO_CUENTA,
									KRCUBA.DESCRIPCION L_KRCUBA_DESCRIPCION,
							        KRSCB.COD_SUB_CUENTA_BANCO L_KRSCB_COD_SUB_CUENTA_BANCO, KRSCB.DESCRIPCION L_KRSCB_DESCRIPCION,
							        ADESCOPA.DESCRIPCION L_ADESCOPA_DESCRIPCION,
							        KRMO.DESCRIPCION L_KRMO_DESCRIPCION
							FROM V_AD_COMPROBANTES_PAGO V 
							     LEFT JOIN ad_comprobantes_pago CPA ON V.ID_COMPROBANTE_PAGO = CPA.ID_COMPROBANTE_PAGO
							     LEFT JOIN KR_CUENTAS_BANCO KRCUBA ON V.ID_CUENTA_BANCO_destino = KRCUBA.ID_CUENTA_BANCO    
							     LEFT JOIN KR_SUB_CUENTAS_BANCO KRSCB ON V.ID_SUB_CUENTA_BANCO_destino = KRSCB.ID_SUB_CUENTA_BANCO 
							     LEFT JOIN AD_ESTADOS_COMP_PAGO ADESCOPA ON V.ESTADO = ADESCOPA.ESTADO
							     LEFT JOIN KR_MONEDAS KRMO ON V.COD_MONEDA = KRMO.COD_MONEDA
							WHERE $where and V.TIPO_COMPROBANTE_PAGO = '$cod_tipo_comprobante'
							ORDER BY V.NRO_COMPROBANTE DESC, 
							         V.ID_COMPROBANTE_PAGO DESC";
			}
			
			if ($cod_tipo_comprobante ==='DOP' || $cod_tipo_comprobante === 'DOT'){
				$sql = "SELECT V.*, 
							       ADESCOPA.DESCRIPCION L_ADESCOPA_DESCRIPCION,
							       KRMO.DESCRIPCION L_KRMO_DESCRIPCION,
							       cldo.descripcion ui_clase_doc
							FROM V_AD_COMPROBANTES_PAGO V 
							     LEFT JOIN ad_comprobantes_pago CPA ON V.ID_COMPROBANTE_PAGO = CPA.ID_COMPROBANTE_PAGO
							     LEFT JOIN AD_ESTADOS_COMP_PAGO ADESCOPA ON V.ESTADO = ADESCOPA.ESTADO
							     LEFT JOIN KR_MONEDAS KRMO ON V.COD_MONEDA = KRMO.COD_MONEDA
							     left join ad_documentos_propio dopo on cpa.id_comprobante_pago = dopo.id_comprobante_pago
							     left join ad_clases_documento cldo on dopo.cod_clase_documento = cldo.cod_clase_documento
							WHERE $where and V.TIPO_COMPROBANTE_PAGO = '$cod_tipo_comprobante'
							ORDER BY V.NRO_COMPROBANTE DESC, 
							         V.ID_COMPROBANTE_PAGO DESC";
			}
			
			if ($cod_tipo_comprobante === 'REE' || $cod_tipo_comprobante === 'RER'){
				$sql = "SELECT V.*, 
							       ADESCOPA.DESCRIPCION L_ADESCOPA_DESCRIPCION,
							       KRMO.DESCRIPCION L_KRMO_DESCRIPCION,
							       cuco.nro_cuenta_corriente ui_nro_cta_cte
							FROM V_AD_COMPROBANTES_PAGO V 
							     LEFT JOIN ad_comprobantes_pago CPA ON V.ID_COMPROBANTE_PAGO = CPA.ID_COMPROBANTE_PAGO
							     LEFT JOIN AD_ESTADOS_COMP_PAGO ADESCOPA ON V.ESTADO = ADESCOPA.ESTADO
							     LEFT JOIN KR_MONEDAS KRMO ON V.COD_MONEDA = KRMO.COD_MONEDA
							     left join ad_retenciones_efectuadas reef on cpa.id_comprobante_pago = reef.id_comprobante_pago
							     left join kr_cuentas_corriente cuco on reef.id_cuenta_corriente = cuco.id_cuenta_corriente
							WHERE $where and V.TIPO_COMPROBANTE_PAGO = '$cod_tipo_comprobante'
							ORDER BY V.NRO_COMPROBANTE DESC, 
							         V.ID_COMPROBANTE_PAGO DESC";
			}
			
			if ($cod_tipo_comprobante === 'COD' 
			     || $cod_tipo_comprobante === 'CRE' || $cod_tipo_comprobante === 'CRR'  
			     || $cod_tipo_comprobante === 'LOT' || $cod_tipo_comprobante === 'TDE'
			     ){
					$sql = "SELECT V.*, 
							       ADESCOPA.DESCRIPCION L_ADESCOPA_DESCRIPCION,
							       KRMO.DESCRIPCION L_KRMO_DESCRIPCION
							FROM V_AD_COMPROBANTES_PAGO V 
							     LEFT JOIN ad_comprobantes_pago CPA ON V.ID_COMPROBANTE_PAGO = CPA.ID_COMPROBANTE_PAGO  
							     LEFT JOIN AD_ESTADOS_COMP_PAGO ADESCOPA ON V.ESTADO = ADESCOPA.ESTADO
							     LEFT JOIN KR_MONEDAS KRMO ON V.COD_MONEDA = KRMO.COD_MONEDA
							WHERE $where and V.TIPO_COMPROBANTE_PAGO = '$cod_tipo_comprobante'
							ORDER BY V.NRO_COMPROBANTE DESC, 
							         V.ID_COMPROBANTE_PAGO DESC";
				}
				$datos = toba::db()->consultar($sql);
				return $datos;
			}else{
			   return array();
		}
	}
	
	static public function get_ui_nro_cta_cte ($id_comprobante_pago){
		$sql =" SELECT cuco.nro_cuenta_corriente
				FROM ad_retenciones_efectuadas reef, kr_cuentas_corriente cuco
				WHERE reef.id_comprobante_pago = $id_comprobante_pago
				      AND cuco.id_cuenta_corriente = reef.id_cuenta_corriente;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['nro_cuenta_corriente'];
	}
	
	static public function get_estados_comp_pago (){
		$sql ="SELECT ESTADO, DESCRIPCION
			   FROM AD_ESTADOS_COMP_PAGO";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	
	static public function get_ui_id_conciliaciones ($id_comprobante_pago){
		if (isset($$id_comprobante_pago) && !empty($id_comprobante_pago)){
			$sql = "SELECT Pkg_Ad_Comprobantes_Pagos.obtener_id_conciliaciones($id_comprobante_pago) AS id_conciliacion FROM DUAL";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['id_conciliacion'];
		}else 
			return null;
	}
	
	static public function get_conciliaciones($id_comprobante_pago){
		if (is_null($id_comprobante_pago))
			return null;

		$sql = "select distinct c.id_conciliacion
				  from ad_conciliaciones c, ad_conciliaciones_det cd, kr_movimientos_banco mb, ad_comprobantes_pago cp
				 where c.id_conciliacion = cd.id_conciliacion
				 	and   cd.ID_MOVIMIENTO_BANCO = mb.ID_MOVIMIENTO_BANCO
					and   mb.ID_COMPROBANTE_PAGO = cp.ID_COMPROBANTE_PAGO
					and   c.confirmado = 'S'
					and   c.anulado = 'N'
					and   cp.ID_COMPROBANTE_PAGO = ".$id_comprobante_pago;

		return toba::db()->consultar($sql);
	}
}
?>