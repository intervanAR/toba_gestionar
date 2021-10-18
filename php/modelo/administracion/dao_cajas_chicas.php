<?php

class dao_cajas_chicas
{
	static public function get_cajas_chicas($filtro=array())
    {
    	$where = " 1=1 ";
    	if (isset($filtro['descripcion'])){
    		$where .=" and upper(acc.descripcion) like upper('%".$filtro['descripcion']."%')";
    		unset($filtro['descripcion']);
    	}
    	
	    $where .= " and " . ctr_construir_sentencias::get_where_filtro($filtro, 'acc', '1=1');
	    
	    $sql = "SELECT acc.*, 
	    			   DECODE(acc.estado, 'A','Abierta','C','Cerrada') estado_meaning,
	    			   KRUA.DESCRIPCION UNIDAD_ADMINISTRACION,
	    			   krue.descripcion unidad_ejecutora
				FROM ad_cajas_chicas acc, kr_unidades_ejecutoras krue, kr_unidades_administracion krua
				WHERE $where 
					  AND KRUA.COD_UNIDAD_ADMINISTRACION = ACC.COD_UNIDAD_ADMINISTRACION	
				      and krue.cod_unidad_ejecutora = acc.cod_unidad_ejecutora
				ORDER BY acc.descripcion DESC;";
	    $datos = toba::db()->consultar($sql);
	    return $datos;
	}
        
        static public function get_cajas_chicas_x_id ($id_caja_chica){
            if (isset($id_caja_chica)) {
	            $sql = "SELECT	ADCACH.*, 
								ADCACH.ID_CAJA_CHICA || '-' || L_KRAUEX.COD_AUXILIAR ||'-'|| ADCACH.DESCRIPCION as lov_descripcion
	                    FROM	AD_CAJAS_CHICAS ADCACH LEFT JOIN KR_AUXILIARES_EXT L_KRAUEX
	                    			ON l_krauex.COD_AUXILIAR = adcach.COD_AUXILIAR
	                    WHERE ADCACH.ID_CAJA_CHICA = ".quote($id_caja_chica);
	            $datos = toba::db()->consultar_fila($sql);
	            return $datos['lov_descripcion'];
            }
        }
		 
        static public function get_lov_caja_chica_x_nombre ($nombre, $filtro=array()){
            if (isset($nombre)) {
                $trans_id = ctr_construir_sentencias::construir_translate_ilike('ADCACH.ID_CAJA_CHICA', $nombre);
                $trans_descricpcion = ctr_construir_sentencias::construir_translate_ilike('ADCACH.DESCRIPCION', $nombre);
                $where = "($trans_id OR $trans_descricpcion)";
            } else {
                $where = "1=1";
            }
            
                if (isset($filtro['para_rendicion_caja_chica'])){
                    $where .= " AND ADCACH.COD_AUXILIAR = L_KRAUEX.COD_AUXILIAR AND
                                (ADCACH.cod_unidad_administracion = ".$filtro['cod_unidad_administracion']."
                                AND ADCACH.estado = 'A'AND PKG_KR_USUARIOS.tiene_acceso_cach_usuario(" . quote(toba::usuario()->get_id()) . ", ADCACH.id_caja_chica)='S')";
                    unset($filtro['para_rendicion_caja_chica']);
                    unset($filtro['cod_unidad_administracion']);
                }
                if (isset($filtro['para_comprobantes_caja_chica'])){
                    $where .= " AND ADCACH.COD_AUXILIAR = L_KRAUEX.COD_AUXILIAR
                                AND (ADCACH.cod_unidad_administracion = ".$filtro['cod_unidad_administracion']."
                                AND (('".$filtro['apertura']."' = 'S' AND ADCACH.estado = 'C') 
                                OR ('".$filtro['apertura']."' = 'N' AND ADCACH.estado = 'A')) 
                                AND PKG_KR_USUARIOS.tiene_acceso_cach_usuario(" . quote(toba::usuario()->get_id()) . ",ADCACH.id_caja_chica)='S')";
                    unset($filtro['para_comprobantes_caja_chica']);
                    unset($filtro['cod_unidad_administracion']);
                    unset($filtro['apertura']);
                }
			if (isset($filtro['con_acceso_usuario_cach']) && $filtro['con_acceso_usuario_cach'] == '1') {
				$where .= "AND PKG_KR_USUARIOS.TIENE_ACCESO_CACH_USUARIO(" . strtoupper(quote(toba::usuario()->get_id())) . ", ADCACH.ID_CAJA_CHICA)='S'";
				unset($filtro['con_acceso_usuario_cach']);
			}
                        
			$where .= ' AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'ADCACH', '1=1');
            
			$sql = "SELECT	ADCACH.*, ADCACH.ID_CAJA_CHICA || '-' || L_KRAUEX.COD_AUXILIAR ||'-'|| ADCACH.DESCRIPCION as lov_descripcion2,
						  ADCACH.ID_CAJA_CHICA ||'-'|| L_KRAUEX.COD_AUXILIAR ||'-'|| ADCACH.DESCRIPCION as lov_descripcion,
						  ADCACH.ID_CAJA_CHICA ||'-'|| ADCACH.DESCRIPCION as id_descripcion_caja_chica
                                FROM	AD_CAJAS_CHICAS ADCACH, KR_AUXILIARES_EXT L_KRAUEX
                                WHERE  ADCACH.COD_AUXILIAR = L_KRAUEX.COD_AUXILIAR AND $where
                                ORDER BY lov_descripcion;";
            $datos = toba::db()->consultar($sql);
            return $datos;  
       }
	   
	   static public function get_id_descripcion_caja_chica_x_id ($id_caja_chica){
            if (isset($id_caja_chica)) {
            $sql = "SELECT	ADCACH.*, 
							ADCACH.ID_CAJA_CHICA ||'-'|| ADCACH.DESCRIPCION as id_descripcion_caja_chica
                    FROM	AD_CAJAS_CHICAS ADCACH, 
							KR_AUXILIARES_EXT L_KRAUEX
                    WHERE ADCACH.ID_CAJA_CHICA = ".quote($id_caja_chica) ."
                    ORDER BY id_descripcion_caja_chica ASC;";
            $datos = toba::db()->consultar_fila($sql);
            if (isset($datos) && !empty($datos) && isset($datos['id_descripcion_caja_chica'])) {
                return $datos['id_descripcion_caja_chica'];
            } else {
                return '';
                }
             }
         }
		 
		static public function get_fondo_permanente_caja_chica ($id_caja_chica)
		{
            if (isset($id_caja_chica)) {
				$sql = "SELECT	ADCACH.fondo_permanente
						FROM	AD_CAJAS_CHICAS ADCACH 
						WHERE ADCACH.ID_CAJA_CHICA = ".quote($id_caja_chica) .";";
				$datos = toba::db()->consultar_fila($sql);
				if (isset($datos) && !empty($datos) && isset($datos['fondo_permanente'])) {
					return $datos['fondo_permanente'];
				} else {
					return 'N';
                }
             }
        }
        
		static public function get_estados_x_id_caja_chica($id_caja_chica){
			$sql = "select cc.*, cg.RV_MEANING estado_meaning
					from ad_cajas_chicas_estados cc, cg_ref_codes cg
					where cg.RV_DOMAIN = 'AD_ESTADO_CAJA_CHICA' AND CG.RV_LOW_VALUE = CC.ESTADO
					       and id_caja_chica = ".quote($id_caja_chica)."
					order by secuencia desc;";
			$datos = toba::db()->consultar($sql);
			return $datos;
		}
		
		static public function get_cuenta_corriente ($id_caja_chica){
			if (isset($id_caja_chica)){
				try{
					$sql = "BEGIN :resultado := pkg_kr_cuentas_corriente.id_de_la_ctacte('CAJ',pkg_kr_usuarios.ua_por_defecto(" . quote(toba::usuario()->get_id()) . "),:id_caja_chica); END;";
					$parametros = array(array(  'nombre' => 'id_caja_chica', 
												'tipo_dato' => PDO::PARAM_INT,
												'longitud' => 32,
												'valor' => $id_caja_chica),
										array(	'nombre' => 'resultado', 
												'tipo_dato' => PDO::PARAM_STR,
												'longitud' => 4000,
												'valor' => ''),
					);
					$resultado = ctr_procedimientos::ejecutar_functions_mensajes($sql, $parametros, '', 'Error', false);
					return $resultado[1]['valor'];
					$valor_resultado = '';
					
					if (isset($resultado[1]['valor']))
						$valor_resultado = $resultado[1]['valor'];
						
					return $valor_resultado;	
					
				} catch (toba_error_db $e_db) {
	                toba::notificacion()->error($mensaje_error . ' ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
	                toba::logger()->error($mensaje_error . ' ' . $e_db->get_mensaje() . ' ' . $e_db->get_mensaje_motor() . ' ' . $e_db->get_sql_ejecutado() . ' ' . $e_db->get_sqlstate());
					throw $e_db; 
				} catch (toba_error $e) {
	                toba::notificacion()->error($mensaje_error . ' ' . $e->get_mensaje());
	                toba::logger()->error($mensaje_error . ' ' . $e->get_mensaje());
					throw $e; 
				}
			} else 
				return null;
		}
		
		
	static public function eliminar_auditoria ($id_caja_chica, $con_transaccion = true){
		try{
			$sql = "BEGIN :resultado := pkg_ad_cajas_chica.eliminar_auditoria(:id_caja_chica);END;";		
			$parametros = array (   array(  'nombre' => 'id_caja_chica', 
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 20,
											'valor' => $id_caja_chica),
									array(  'nombre' => 'resultado', 
											'tipo_dato' => PDO::PARAM_STR,
											'longitud' => 1000,
											'valor' => ''));
			if ($con_transaccion)
				toba::db()->abrir_transaccion();
			
			$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
			if ($con_transaccion){ 
				if ($resultado[1]['valor'] != 'OK'){
					toba::db()->abortar_transaccion();
					return $resultado[1]['valor'];
				}else{
					toba::db()->cerrar_transaccion();
					return $resultado[1]['valor'];
				}
			}else{
				return $resultado[1]['valor'];
			}
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
        }
	}
	
	static public function get_ui_tipo_comprobante ($cod_tipo_comprobante){
		$sql = "SELECT adtcc.descripcion AS ui_tipo_comprobante
				  FROM ad_tipos_comprob_cach adtcc
				 WHERE adtcc.cod_tipo_comprobante = '".$cod_tipo_comprobante."'";
		return toba::db()->consultar_fila($sql);
	}
	
	static public function get_total_rendiciones ($id_caja_chica){
		$sql = "SELECT sum(importe_rendido) importe
				  FROM AD_RENDICIONES_CAJA_CHICA
				  WHERE ID_CAJA_CHICA = $id_caja_chica";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['importe'];
	}
	static public function get_total_comprobantes_caja_chica ($id_caja_chica){
		$sql = "SELECT SUM(IMPORTE) importe
				  FROM AD_COMPROBANTES_CAJA_CHICA 
				 WHERE ID_CAJA_CHICA = $id_caja_chica";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['importe'];
	}
}
?>
