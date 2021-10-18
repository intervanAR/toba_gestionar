<?php

class dao_beneficiarios {

    static public function get_beneficiarios($filtro = array()) {
        $where = "  1=1 ";
    	if (isset($filtro) )
    		$where = ctr_construir_sentencias::get_where_filtro($filtro, 'ab', '1=1', array('nombre'));
        $sql = "SELECT ab.*
		    FROM ad_beneficiarios ab
		    WHERE $where
		    ORDER BY ab.id_beneficiario DESC;";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }

    static public function get_lov_beneficiarios_x_id($id_beneficiario) {
        if (isset($id_beneficiario)) {
            $sql = "SELECT ab.*, ab.id_beneficiario||' - '||ab.nombre lov_descripcion
                        FROM AD_BENEFICIARIOS ab
                        WHERE id_beneficiario = " . quote($id_beneficiario) . ";";

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

    static public function get_beneficiario_x_id ($id){
    	$sql = "SELECT * FROM AD_BENEFICIARIOS
    			WHERE ID_BENEFICIARIO = $id";
    	return toba::db()->consultar_fila($sql);
    }


    static public function get_lov_beneficiarios_x_nombre($nombre, $filtro = array()) {
        if (isset($nombre)) {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('id_beneficiario', $nombre);
            $trans_nombre = ctr_construir_sentencias::construir_translate_ilike('nombre', $nombre);
            $where = "($trans_codigo OR $trans_nombre)";
        } else {
            $where = '1=1';
        }
        if (isset($filtro['id_cuenta_corriente'])) {
            $where .= " AND ((exists(select 1 from ad_beneficiarios_pago abp where abp.id_beneficiario = ab.id_beneficiario and abp.activo = 'S' and abp.id_cuenta_corriente = " . quote($filtro['id_cuenta_corriente']) . "))";
            if (isset($filtro['incluir_default']) && $filtro['incluir_default'] == '1') {
                $where .= " OR ab.id_beneficiario = pkg_kr_general.VALOR_PARAMETRO('ID_BENEFICIARIO_DEFECTO')) ";
                unset($filtro['incluir_default']);
            } else {
                $where .= ") ";
            }
            unset($filtro['id_cuenta_corriente']);
        }


        if (isset($filtro['id_cuenta_corriente_1'])) {

            $where.= " AND (( " . $filtro['cc_tiene_beneficiarios'] . " >= 1 and exists(select 1 from ad_beneficiarios_pago
				   where id_beneficiario = ab.id_beneficiario and activo = 'S'
				   and id_cuenta_corriente = " . $filtro['id_cuenta_corriente_1'] . " ))
				   or ( " . $filtro['cc_tiene_beneficiarios'] . " = 0) OR ab.id_beneficiario = pkg_kr_general.VALOR_PARAMETRO('ID_BENEFICIARIO_DEFECTO'))";

            unset($filtro['id_cuenta_corriente_1']);
            unset($filtro['cc_tiene_beneficiarios']);
        }

        if (isset($filtro['para_ordenes_pago'])) {
            $where .= " AND (   (    " . $filtro['tiene_beneficiarios'] . " >= 1
              AND EXISTS (
                     SELECT 1
                       FROM ad_beneficiarios_pago
                      WHERE id_beneficiario = ab.id_beneficiario
                        AND activo = 'S'
                        AND id_cuenta_corriente =
                                            " . $filtro['cta_cte'] . ")
             )
          OR (" . $filtro['tiene_beneficiarios'] . " = 0)
         )";
            unset($filtro['tiene_beneficiarios']);
            unset($filtro['cta_cte']);
            unset($filtro['para_ordenes_pago']);
        }

		if (isset($filtro['id_recibo_pago'])) {
			$where.= "AND (mostrar_beneficiario(".$filtro['id_recibo_pago'].", ab.id_beneficiario) = 'S'
					OR ab.id_beneficiario = pkg_kr_general.VALOR_PARAMETRO('ID_BENEFICIARIO_DEFECTO'))";

	        unset($filtro['id_recibo_pago']);
		}

        $where .= 'AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'ab', '1=1');
        $sql = "SELECT ab.*, ab.id_beneficiario || '-' || ab.nombre ||'-'|| ab.cod_tipo_documento ||''|| ab.nro_documento as lov_descripcion
		    FROM ad_beneficiarios ab
		    WHERE $where
		    ORDER BY nombre ASC;";

        $datos = toba::db()->consultar($sql);
        return $datos;
    }

    static public function get_nombre_beneficiario_x_id_beneficiario($id_beneficiario) {
        if (isset($id_beneficiario)) {
            $sql = "SELECT ab.nombre
				FROM ad_beneficiarios ab
				WHERE id_beneficiario = " . quote($id_beneficiario) . ";";
            $datos = toba::db()->consultar_fila($sql);
            if (isset($datos) && !empty($datos) && isset($datos['nombre'])) {
                return $datos['nombre'];
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

	static public function get_lov_tipo_documento_x_codigo ($codigo){
		if (isset($codigo)){
			$sql = "SELECT adtd.*, adtd.cod_tipo_documento ||' - '|| adtd.descripcion as lov_descripcion
					FROM AD_TIPOS_DOCUMENTO adtd
					WHERE adtd.cod_tipo_documento = '$codigo'
					ORDER BY lov_descripcion ASC;";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['lov_descripcion'];
		}else return null;
	}

	static public function get_lov_tipo_documento_x_nombre ($nombre, $filtro = array()){
		$where =" ";
		if (isset($nombre)) {
            $trans_cod = ctr_construir_sentencias::construir_translate_ilike('cod_tipo_documento', $nombre);
            $trans_des = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
            $where = "($trans_cod OR $trans_des)";
        } else {
            $where = '1=1';
        }
        if (isset($filtro))
            $where .= 'AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'adtd', '1=1');
        $sql = "SELECT adtd.*, adtd.cod_tipo_documento ||' - '|| adtd.descripcion as lov_descripcion
					FROM AD_TIPOS_DOCUMENTO adtd
					WHERE $where
					ORDER BY lov_descripcion ASC;";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}

	static public function existe_nro_documento ($nro_documento, $cod_tipo_documento){
		if (isset($nro_documento) && isset($cod_tipo_documento)){
			$sql = "select 1 as existe
					from AD_BENEFICIARIOS
					where nro_documento = ".quote($nro_documento)." and cod_tipo_documento = ".quote($cod_tipo_documento).";";
			$datos = toba::db()->consultar_fila($sql);
			if (isset($datos) && $datos['existe'] == '1'){
				return true;
			}else return false;
		}else return false;
	}
}

?>
