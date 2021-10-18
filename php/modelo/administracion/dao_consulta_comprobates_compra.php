<?php

class dao_consulta_comprobantes_compra {

	static public function get_comprobantes ($tipo_comprobante, $filtro = array(), $orden = 1){

		$desde= null;
		$hasta= null;
		if(isset($filtro['numrow_desde'])){
			$desde= $filtro['numrow_desde'];
			$hasta= $filtro['numrow_hasta'];

			unset($filtro['numrow_desde']);
			unset($filtro['numrow_hasta']);
		}



		$where = "";
		$usuario = strtoupper(toba::usuario()->get_id());
		$nombre_tabla = '';
		$prefijo = '';
		$select = '1';
		
		if ($tipo_comprobante == 'COMPRAS'){
			$nombre_tabla = 'CO_COMPRAS';
			$prefijo = 'COM';
			$select .= ", (select RV_MEANING FROM cg_ref_codes WHERE RV_LOW_VALUE = $prefijo.tipo_compra AND RV_DOMAIN = 'CO_TIPO_COMPRA') tipo_compra_format";
			$select .= ", (select RV_MEANING FROM cg_ref_codes WHERE RV_LOW_VALUE = $prefijo.ESTADO AND RV_DOMAIN = 'CO_ESTADO_PEDIDO') estado_format";

			$select .= ", to_char($prefijo.valor_compra,'9G999G999G999G990D99') valor_compra_format";

			$select .= ", (select to_char(nvl(sum(precio * cantidad),0),'9G999G999G999G990D99') from co_items_adjudicacion where nro_compra = $prefijo.nro_compra) valor_adjudicado";

			switch ($filtro['tipo']) {
				case 'PRO':
					$where .= "INSTR(PKG_USUARIOS.ambitos_usuario ('".$usuario."'), COM.cod_ambito_ejecuta) > 0 AND ORIGEN_SISTEMA = 'S' AND FINALIZADA = 'N' 
  								AND PKG_USUARIOS.esta_en_bandeja('".$usuario."',COM.COD_SECTOR,COM.COD_AMBITO_EJECUTA,'COM',COM.TIPO_COMPRA,COM.PRESUPUESTARIO,COM.INTERNA,COM.ESTADO) = 'S'";
				;
				break;
				case 'OTR':
					$where .= "INSTR(PKG_USUARIOS.ambitos_usuario ('".$usuario."'), COM.cod_ambito_ejecuta) > 0 AND ORIGEN_SISTEMA = 'S' AND FINALIZADA = 'N'  
  								AND PKG_USUARIOS.esta_en_bandeja('".$usuario."',COM.COD_SECTOR,COM.COD_AMBITO_EJECUTA,'COM',COM.TIPO_COMPRA,COM.PRESUPUESTARIO,COM.INTERNA,COM.ESTADO) = 'N'";
				break;
				case 'FIN':
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), COM.cod_ambito_ejecuta) > 0 AND ORIGEN_SISTEMA = 'S' AND FINALIZADA = 'S'";
				break;
				default:
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), COM.cod_ambito_ejecuta) > 0 AND ORIGEN_SISTEMA = 'S'";
				break;
			}
			
		}elseif ($tipo_comprobante == 'SOLICITUDES'){
			
			$nombre_tabla = 'CO_SOLICITUDES';
			$prefijo = 'SOL';

			$select .= ", (select RV_MEANING FROM cg_ref_codes WHERE RV_LOW_VALUE = $prefijo.tipo_compra AND RV_DOMAIN = 'CO_TIPO_COMPRA') tipo_compra_format";

			$select .= ", (select RV_MEANING FROM cg_ref_codes WHERE RV_LOW_VALUE = $prefijo.ESTADO AND RV_DOMAIN = 'CO_ESTADO_SOLICITUD') ESTADO_format";

			$select .= ", (select RV_MEANING FROM cg_ref_codes WHERE RV_LOW_VALUE = $prefijo.destino_compra AND RV_DOMAIN = 'CO_DESTINO_COMPRA') destino_compra_format";

			$select .= ", to_char($prefijo.valor_estimado,'9G999G999G999G990D99') valor_estimado_format";



			switch ($filtro['tipo']) {
				case 'PRO':
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), SOL.cod_ambito) > 0 AND FINALIZADA = 'N'  
  								 AND PKG_USUARIOS.esta_en_bandeja('".$usuario."',SOL.COD_SECTOR,SOL.COD_AMBITO,'SOL',nvl(SOL.TIPO_COMPRA,null),SOL.PRESUPUESTARIO,SOL.INTERNA,SOL.ESTADO) = 'S'";
				;
				break;
				case 'OTR':
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), SOL.cod_ambito) > 0 AND FINALIZADA = 'N'  
  								AND PKG_USUARIOS.esta_en_bandeja('".$usuario."',SOL.COD_SECTOR,SOL.COD_AMBITO,'SOL',nvl(SOL.TIPO_COMPRA,null),SOL.PRESUPUESTARIO,SOL.INTERNA,SOL.ESTADO) = 'N'";
				break;
				case 'FIN':
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), SOL.cod_ambito) > 0 AND  FINALIZADA = 'S'";
				break;
				default:
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), SOL.cod_ambito) > 0";
				break;
			}	
			
		}elseif($tipo_comprobante == 'ORDENES'){
			$nombre_tabla = 'CO_ORDENES';
			$prefijo = 'ORD';
			
			$select .= ", (select RV_MEANING FROM cg_ref_codes WHERE RV_LOW_VALUE = $prefijo.tipo_compra AND RV_DOMAIN = 'CO_TIPO_COMPRA') tipo_compra_format";

			$select .= ", (select RV_MEANING FROM cg_ref_codes WHERE RV_LOW_VALUE = $prefijo.ESTADO AND RV_DOMAIN = 'CO_ESTADO_ORDEN') estado_format";

			$select .= ", (select RV_MEANING FROM cg_ref_codes WHERE RV_LOW_VALUE = $prefijo.destino_compra AND RV_DOMAIN = 'CO_DESTINO_COMPRA') destino_compra_format";

			$select .= ", to_char($prefijo.valor_total,'9G999G999G999G990D99') valor_total_format";

			$select .= ", (select '#'||id_proveedor ||' '|| razon_social from co_proveedores where id_proveedor = $prefijo.id_proveedor ) proveedor ";

			$select .= ", (select to_char(nvl(sum(precio * cantidad),0),'9G999G999G999G990D99') from co_items_adjudicacion where nro_compra = $prefijo.nro_compra) valor_adjudicado";

			switch ($filtro['tipo']) {
				case 'PRO':
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), ORD.cod_ambito) > 0 AND FINALIZADA = 'N'  AND
      								 PKG_USUARIOS.esta_en_bandeja('".$usuario."',ORD.COD_SECTOR,ORD.COD_AMBITO,'ORD',null,null,ORD.INTERNA,ORD.ESTADO) = 'S'";
				;
				break;
				case 'OTR':
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), ORD.cod_ambito) > 0 AND FINALIZADA = 'N'  AND 
      								PKG_USUARIOS.esta_en_bandeja('".$usuario."',ORD.COD_SECTOR,ORD.COD_AMBITO,'ORD',null,null,ORD.INTERNA,ORD.ESTADO) = 'N'";
				break;
				case 'FIN':
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), ORD.cod_ambito) > 0 AND FINALIZADA = 'S'";
				break;
				default:
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), ORD.cod_ambito) > 0";
				break;
			}	
			
		}elseif ($tipo_comprobante == 'RECEPCIONES'){
			$nombre_tabla = 'CO_RECEPCIONES';
			$prefijo = 'RE';
			$select .= ", (select RV_MEANING FROM cg_ref_codes WHERE RV_LOW_VALUE = $prefijo.ESTADO AND RV_DOMAIN = 'CO_ESTADO_RECEPCION') ESTADO_format";

			$select .= ", (select '#'||id_proveedor ||' '|| razon_social from co_proveedores where id_proveedor = $prefijo.id_proveedor ) proveedor ";

			switch ($filtro['tipo']) {
				case 'PRO':
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), RE.cod_ambito) > 0 AND FINALIZADA = 'N'  AND 
      								 PKG_USUARIOS.esta_en_bandeja('".$usuario."',RE.COD_SECTOR,RE.COD_AMBITO,'REC',null,null,RE.INTERNA,RE.ESTADO) = 'S'";
				;
				break;
				case 'OTR':
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), RE.cod_ambito) > 0 AND FINALIZADA = 'N'  AND 
      								PKG_USUARIOS.esta_en_bandeja('".$usuario."',RE.COD_SECTOR,RE.COD_AMBITO,'REC',null,null,RE.INTERNA,RE.ESTADO) = 'N'";
				break;
				case 'FIN':
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), RE.cod_ambito) > 0 AND FINALIZADA = 'S'";
				break;
				default:
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), RE.cod_ambito) > 0";
				break;
			}
		}
		
		
		if (isset($filtro['tipo']))
			unset($filtro['tipo']);
			
		if (isset($filtro) && !empty($filtro))
			$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro, $prefijo, ' 1=1 ');
		
		
		$sql = "SELECT $prefijo.*,
						decode($prefijo.finalizada,'S','Si','No') finalizada_format,
					   $select, 
						'#'||SEC.COD_SECTOR ||' '|| SEC.DESCRIPCION SECTOR_FORMAT,
						(SELECT nro_expediente FROM KR_EXPEDIENTES WHERE ID_EXPEDIENTE = $prefijo.ID_EXPEDIENTE) EXPEDIENTE,
						'#'||amb.cod_ambito ||' '||amb.descripcion as ambito_format
				  FROM $nombre_tabla $prefijo, CO_SECTORES SEC, co_ambitos amb
				 WHERE $prefijo.COD_SECTOR = SEC.COD_SECTOR AND $prefijo.cod_ambito = amb.cod_ambito 
				   and $where ";
			
		$sql = ctr_construir_sentencias::armar_order_by($sql, $orden);		   
		$sql = dao_varios::paginador($sql, null, $desde, $hasta);					   
		return toba::db()->consultar($sql);
	}


	static public function get_cantidad_comprobantes ($tipo_comprobante, $filtro = array()){
		$where = "";
		$usuario = strtoupper(toba::usuario()->get_id());
		$nombre_tabla = '';
		$prefijo = '';
		$select = '1';
		
		if ($tipo_comprobante == 'COMPRAS'){
			$nombre_tabla = 'CO_COMPRAS';
			$prefijo = 'COM';
			$select .= ", (select RV_MEANING FROM cg_ref_codes WHERE RV_LOW_VALUE = $prefijo.tipo_compra AND RV_DOMAIN = 'CO_TIPO_COMPRA') tipo_compra_format";
			$select .= ", (select RV_MEANING FROM cg_ref_codes WHERE RV_LOW_VALUE = $prefijo.ESTADO AND RV_DOMAIN = 'CO_ESTADO_PEDIDO') estado_format";

			$select .= ", to_char($prefijo.valor_compra,'9G999G999G999G990D99') valor_compra_format";

			$select .= ", (select to_char(nvl(sum(precio * cantidad),0),'9G999G999G999G990D99') from co_items_adjudicacion where nro_compra = $prefijo.nro_compra) valor_adjudicado";

			switch ($filtro['tipo']) {
				case 'PRO':
					$where .= "INSTR(PKG_USUARIOS.ambitos_usuario ('".$usuario."'), COM.cod_ambito_ejecuta) > 0 AND ORIGEN_SISTEMA = 'S' AND FINALIZADA = 'N' 
  								AND PKG_USUARIOS.esta_en_bandeja('".$usuario."',COM.COD_SECTOR,COM.COD_AMBITO_EJECUTA,'COM',COM.TIPO_COMPRA,COM.PRESUPUESTARIO,COM.INTERNA,COM.ESTADO) = 'S'";
				;
				break;
				case 'OTR':
					$where .= "INSTR(PKG_USUARIOS.ambitos_usuario ('".$usuario."'), COM.cod_ambito_ejecuta) > 0 AND ORIGEN_SISTEMA = 'S' AND FINALIZADA = 'N'  
  								AND PKG_USUARIOS.esta_en_bandeja('".$usuario."',COM.COD_SECTOR,COM.COD_AMBITO_EJECUTA,'COM',COM.TIPO_COMPRA,COM.PRESUPUESTARIO,COM.INTERNA,COM.ESTADO) = 'N'";
				break;
				case 'FIN':
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), COM.cod_ambito_ejecuta) > 0 AND ORIGEN_SISTEMA = 'S' AND FINALIZADA = 'S'";
				break;
				default:
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), COM.cod_ambito_ejecuta) > 0 AND ORIGEN_SISTEMA = 'S'";
				break;
			}
			
		}elseif ($tipo_comprobante == 'SOLICITUDES'){
			
			$nombre_tabla = 'CO_SOLICITUDES';
			$prefijo = 'SOL';

			$select .= ", (select RV_MEANING FROM cg_ref_codes WHERE RV_LOW_VALUE = $prefijo.tipo_compra AND RV_DOMAIN = 'CO_TIPO_COMPRA') tipo_compra_format";

			$select .= ", (select RV_MEANING FROM cg_ref_codes WHERE RV_LOW_VALUE = $prefijo.ESTADO AND RV_DOMAIN = 'CO_ESTADO_SOLICITUD') ESTADO_format";

			$select .= ", (select RV_MEANING FROM cg_ref_codes WHERE RV_LOW_VALUE = $prefijo.destino_compra AND RV_DOMAIN = 'CO_DESTINO_COMPRA') destino_compra_format";

			$select .= ", to_char($prefijo.valor_estimado,'9G999G999G999G990D99') valor_estimado_format";



			switch ($filtro['tipo']) {
				case 'PRO':
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), SOL.cod_ambito) > 0 AND FINALIZADA = 'N'  
  								 AND PKG_USUARIOS.esta_en_bandeja('".$usuario."',SOL.COD_SECTOR,SOL.COD_AMBITO,'SOL',nvl(SOL.TIPO_COMPRA,null),SOL.PRESUPUESTARIO,SOL.INTERNA,SOL.ESTADO) = 'S'";
				;
				break;
				case 'OTR':
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), SOL.cod_ambito) > 0 AND FINALIZADA = 'N'  
  								AND PKG_USUARIOS.esta_en_bandeja('".$usuario."',SOL.COD_SECTOR,SOL.COD_AMBITO,'SOL',nvl(SOL.TIPO_COMPRA,null),SOL.PRESUPUESTARIO,SOL.INTERNA,SOL.ESTADO) = 'N'";
				break;
				case 'FIN':
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), SOL.cod_ambito) > 0 AND  FINALIZADA = 'S'";
				break;
				default:
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), SOL.cod_ambito) > 0";
				break;
			}	
			
		}elseif($tipo_comprobante == 'ORDENES'){
			$nombre_tabla = 'CO_ORDENES';
			$prefijo = 'ORD';
			
			$select .= ", (select RV_MEANING FROM cg_ref_codes WHERE RV_LOW_VALUE = $prefijo.tipo_compra AND RV_DOMAIN = 'CO_TIPO_COMPRA') tipo_compra_format";

			$select .= ", (select RV_MEANING FROM cg_ref_codes WHERE RV_LOW_VALUE = $prefijo.ESTADO AND RV_DOMAIN = 'CO_ESTADO_ORDEN') estado_format";

			$select .= ", (select RV_MEANING FROM cg_ref_codes WHERE RV_LOW_VALUE = $prefijo.destino_compra AND RV_DOMAIN = 'CO_DESTINO_COMPRA') destino_compra_format";

			$select .= ", to_char($prefijo.valor_total,'9G999G999G999G990D99') valor_total_format";

			$select .= ", (select '#'||id_proveedor ||' '|| razon_social from co_proveedores where id_proveedor = $prefijo.id_proveedor ) proveedor ";

			$select .= ", (select to_char(nvl(sum(precio * cantidad),0),'9G999G999G999G990D99') from co_items_adjudicacion where nro_compra = $prefijo.nro_compra) valor_adjudicado";

			switch ($filtro['tipo']) {
				case 'PRO':
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), ORD.cod_ambito) > 0 AND FINALIZADA = 'N'  AND
      								 PKG_USUARIOS.esta_en_bandeja('".$usuario."',ORD.COD_SECTOR,ORD.COD_AMBITO,'ORD',null,null,ORD.INTERNA,ORD.ESTADO) = 'S'";
				;
				break;
				case 'OTR':
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), ORD.cod_ambito) > 0 AND FINALIZADA = 'N'  AND 
      								PKG_USUARIOS.esta_en_bandeja('".$usuario."',ORD.COD_SECTOR,ORD.COD_AMBITO,'ORD',null,null,ORD.INTERNA,ORD.ESTADO) = 'N'";
				break;
				case 'FIN':
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), ORD.cod_ambito) > 0 AND FINALIZADA = 'S'";
				break;
				default:
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), ORD.cod_ambito) > 0";
				break;
			}	
			
		}elseif ($tipo_comprobante == 'RECEPCIONES'){
			$nombre_tabla = 'CO_RECEPCIONES';
			$prefijo = 'RE';
			$select .= ", (select RV_MEANING FROM cg_ref_codes WHERE RV_LOW_VALUE = $prefijo.ESTADO AND RV_DOMAIN = 'CO_ESTADO_RECEPCION') ESTADO_format";

			$select .= ", (select '#'||id_proveedor ||' '|| razon_social from co_proveedores where id_proveedor = $prefijo.id_proveedor ) proveedor ";

			switch ($filtro['tipo']) {
				case 'PRO':
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), RE.cod_ambito) > 0 AND FINALIZADA = 'N'  AND 
      								 PKG_USUARIOS.esta_en_bandeja('".$usuario."',RE.COD_SECTOR,RE.COD_AMBITO,'REC',null,null,RE.INTERNA,RE.ESTADO) = 'S'";
				;
				break;
				case 'OTR':
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), RE.cod_ambito) > 0 AND FINALIZADA = 'N'  AND 
      								PKG_USUARIOS.esta_en_bandeja('".$usuario."',RE.COD_SECTOR,RE.COD_AMBITO,'REC',null,null,RE.INTERNA,RE.ESTADO) = 'N'";
				break;
				case 'FIN':
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), RE.cod_ambito) > 0 AND FINALIZADA = 'S'";
				break;
				default:
					$where .= "INSTR(Pkg_Usuarios.ambitos_usuario ('".$usuario."'), RE.cod_ambito) > 0";
				break;
			}
		}
		
		
		if (isset($filtro['tipo']))
			unset($filtro['tipo']);
			
		if (isset($filtro) && !empty($filtro))
			$where .= " and ".ctr_construir_sentencias::get_where_filtro($filtro, $prefijo, ' 1=1 ');
		
		
		$sql = "SELECT count(1) cantidad
				  FROM $nombre_tabla $prefijo, CO_SECTORES SEC, co_ambitos amb
				 WHERE $prefijo.COD_SECTOR = SEC.COD_SECTOR AND $prefijo.cod_ambito = amb.cod_ambito 
				   and $where    ";
					   
		
		$datos = toba::db()->consultar_fila($sql);
		return $datos['cantidad'];
	}
	
	static function get_sectores_usuarios($estado){
		$sql = "select min(estado_hasta) estado_hasta
			      from co_tmp_estados_sectores
			     where estado_desde = '$estado'";
		$datos = toba::db()->consultar_fila($sql);
		
		if (!empty($datos['estado_hasta'])){
			$sql = "select sectores, usuarios
			          from co_tmp_estados_sectores
			         where estado_desde = '$estado'
			               and estado_hasta = '".$datos['estado_hasta']."'";
			return toba::db()->consultar_fila($sql);
		}
		return array('sectores'=>'','usuarios'=>'');
	}
	
	
	static public function arma_temporal ($cod_sector, $cod_ambito, $tipo_comprobante, $tipo_compra, $presupuestario, $interna){
		try{
			if (is_null($presupuestario)){
				$presupuestario = '';
			}
			if (is_null($interna)){
				$interna = '';
			}
			if (is_null($tipo_compra)){
				$tipo_compra = '';
			}
				
			$sql = "BEGIN :resultado := PKG_USUARIOS.arma_temporal(:cod_sector, :cod_ambito, :tipo_comprobante, :tipo_compra, :presupuestario, :interna); END;";		
			$parametros = array (   array(  'nombre' => 'cod_sector', 
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 6,
											'valor' => $cod_sector),
									array(  'nombre' => 'cod_ambito', 
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 4,
											'valor' => $cod_ambito),
									array(  'nombre' => 'tipo_comprobante', 
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 3,
											'valor' => $tipo_comprobante),
									array(  'nombre' => 'tipo_compra', 
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 3,
											'valor' => $tipo_compra),
									array(  'nombre' => 'presupuestario', 
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 4,
											'valor' => $presupuestario),
									array(  'nombre' => 'interna', 
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 4,
											'valor' => $interna),
									array(  'nombre' => 'resultado', 
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 1000,
											'valor' => ''));
									
			toba::db()->abrir_transaccion();
			$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
			
			if ($resultado[6]['valor'] != 'OK'){
				toba::db()->abortar_transaccion();
			}else{
				toba::db()->cerrar_transaccion();
			}
			return $resultado[6]['valor'];
        } catch (toba_error_db $e_db) {
        	toba::db()->abortar_transaccion();
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
        } catch (toba_error $e) {
        	toba::db()->abortar_transaccion();
            toba::logger()->error('Error '.$e->get_mensaje());
        }
	}
	
	
}

?>