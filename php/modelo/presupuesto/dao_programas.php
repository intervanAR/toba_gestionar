<?php

class dao_programas {

    static public function get_datos($filtro = [])
    {
        $where= "1=1";

        if (isset($filtro['id_adre']))
        {
            $where .=" and pp.id_entidad = ".$filtro['id_padre'];
            unset($filtro['id_padre']);
        }

        
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'pp', '1=1');

        $sql = "SELECT pp.*, '['  || pkg_pr_programas.MASCARA_APLICAR(pp.cod_programa) ||'] ' || pp.descripcion as descripcion_2,
        pkg_pr_programas.MASCARA_APLICAR(pp.cod_programa) cod_masc, pp.id_programa id
                FROM PR_PROGRAMAS pp
                WHERE  $where
                order by cod_programa desc;";

        $datos = toba::db()->consultar($sql);

        return $datos;
    }

    static public function get_programas(){
        $where= "1=1";
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'pp', '1=1');

        $sql = "SELECT pp.*, '['  || pkg_pr_programas.MASCARA_APLICAR(pp.cod_programa) ||'] ' || pp.descripcion as descripcion_2
                FROM PR_PROGRAMAS pp
                WHERE  $where
                order by cod_programa desc;";

        $datos = toba::db()->consultar($sql);

        return $datos;
    }

 	static public function get_tipos_proyecto (){
    	$sql  = "	SELECT cod_tipo_proyecto, cod_tipo_proyecto ||' - '|| descripcion as descripcion
					FROM PR_TIPOS_PROYECTOS
					where activo = 'S'";
    	$datos = toba::db()->consultar($sql);
        return $datos;
    }
    static public function get_descripcion_x_codigo ($cod_programa){
    	if (isset($cod_programa) && !empty($cod_programa)){
	    	$sql ="select descripcion
					from pr_programas
					where cod_programa = $cod_programa ";
	    	$datos = toba::db()->consultar_fila($sql);
	    	return $datos['descripcion'];
    	}else{
    		return null;
    	}
    }
    static public function get_programa_x_id_programa($id_programa){
    	if (isset($id_programa) && !empty($id_programa)){
        $sql = "SELECT PRPR.*, '['  || pkg_pr_programas.MASCARA_APLICAR(PRPR.cod_programa) ||'] ' || PRPR.descripcion as descripcion_2
                FROM PR_PROGRAMAS PRPR
                WHERE  PRPR.id_programa = $id_programa;";
        $datos = toba::db()->consultar_fila($sql);
        return $datos;
    	}else return null;
    }
    static public function get_lov_programas_x_id_cod ($id_cod)
    {
        if (!is_null($id_cod)) {
            $clave = explode(apex_qs_separador, $id_cod);
            if (count($clave) > 1) {
                $id_programa = $clave[0];
                $cod_programa = $clave[1];

                $sql = "SELECT pkg_pr_programas.mascara_aplicar(pp.cod_programa) ||' - '|| pkg_pr_programas.cargar_descripcion(pp.id_programa) lov_descripcion
                    FROM PR_PROGRAMAS pp
                    WHERE id_programa = ".quote($id_programa) .";";

                $datos = toba::db()->consultar_fila($sql);
                return $datos['lov_descripcion'];
            }

        }
    }
    static public function get_lov_programas_x_id($id_programa) {
        if (isset($id_programa)) {
            $sql = "SELECT pkg_pr_programas.mascara_aplicar(pp.cod_programa) ||' - '|| pkg_pr_programas.cargar_descripcion(pp.id_programa) lov_descripcion
                    FROM PR_PROGRAMAS pp
                    WHERE id_programa = ".quote($id_programa) .";";

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
 	static public function get_lov_programas_x_codigo($codigo) {
        if (isset($codigo)) {
            $sql = "SELECT pkg_pr_programas.mascara_aplicar(pp.cod_programa) ||' - '|| pkg_pr_programas.cargar_descripcion(pp.id_programa) lov_descripcion
                    FROM PR_PROGRAMAS pp
                    WHERE cod_programa = ".quote($codigo) .";";

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
    static public function get_lov_programas_x_nombre($nombre, $filtro = array()) {
       if (isset($nombre)) {
			$campos = array(
						'pp.cod_programa',
						'pkg_pr_programas.mascara_aplicar(pp.cod_programa)',
						'pp.descripcion',
						"pp.cod_programa ||' - '|| pkg_pr_programas.cargar_descripcion(pp.id_programa)",
						"pkg_pr_programas.mascara_aplicar(pp.cod_programa) ||' - '|| pkg_pr_programas.cargar_descripcion(pp.id_programa)",
				);
			$where = ctr_construir_sentencias::construir_sentencia_busqueda($nombre, $campos, false);
		} else {
            $where = '1=1';
        }

        if (isset($filtro['para_formulacion_pre']) && isset($filtro['id_ejercicio']) && isset($filtro['id_entidad']) && isset($filtro['id_formulacion']))
        {
            $where .=" and pkg_kr_ejercicios.Pertenece_Prog_A_Estruc_Recond(".$filtro['id_formulacion'].",".$filtro['id_entidad'].",pp.id_programa) = 'S'";
            
            unset($filtro['para_formulacion_pre']);
            unset($filtro['id_ejercicio']);
            unset($filtro['id_entidad']);
            unset($filtro['id_formulacion']);
        }

        if (isset($filtro['para_modificacion_presupuesto']) && isset($filtro['id_ejercicio']) && isset($filtro['id_entidad'])){

            $usuario = strtoupper(toba::usuario()->get_id());

        	$where .=" and (
         (
          (Pkg_Pr_Programas.Usuario_Pertenece_Ue ('$usuario', pp.ID_PROGRAMA, pp.Id_Entidad) = 
      'S') 
             Or ((Pkg_Pr_Programas.Usuario_Pertenece_Ue ('$usuario', pp.ID_PROGRAMA, pp.Id_Entidad) = 'N') 
             And Not Exists (Select 1 
                               From Pr_Programas 
                              Where (Pkg_Pr_Programas.Usuario_Pertenece_Ue ('$usuario', Id_Programa, Id_Entidad) = 'S' )))))
        AND (Pkg_kr_Ejercicios.Pertenece_Programa_A_Estruc (".$filtro['id_ejercicio']." ,".$filtro['id_entidad']." ,pp.Id_Programa ) = 'S' 
      And Pkg_Pr_Programas.imputable(pp.Id_Programa) = 'S')";
        	unset($filtro['para_modificacion_presupuesto']);
        	unset($filtro['id_entidad']);
        	unset($filtro['id_ejercicio']);
        }

        if (isset($filtro['para_orden_compra'])){
        	$parametros = $filtro['parametros'];
        	$where .= " and (Pkg_kr_Ejercicios.Pertenece_Programa_A_Estruc(".$parametros['id_ejercicio'].",
        																   ".$parametros['id_entidad'].",
        																   pp.Id_Programa) ='S'
        					 And Pkg_Pr_Programas.Imputable (pp.Id_Programa) = 'S')";
        	unset($filtro['para_orden_compra']);
        	unset($filtro['parametros']);
        }
        if (isset($filtro['entidad']) && !empty($filtro['entidad'])){
            $where .= " AND (    ((   (pkg_pr_programas.usuario_pertenece_ue (" . strtoupper(quote(toba::usuario()->get_id())) . ",
                                                         pp.id_programa,
                                                         ".$filtro['entidad']."
                                                        ) = 'S'
                 )
              OR (    (pkg_pr_programas.usuario_pertenece_ue
                                                            (" . strtoupper(quote(toba::usuario()->get_id())) . ",
                                                             pp.id_programa,
                                                             ".$filtro['entidad']."
                                                            ) = 'N'
                      )
                  AND NOT EXISTS (
                         SELECT 1
                           FROM pr_programas
                          WHERE (pkg_pr_programas.usuario_pertenece_ue
                                                                 (" .strtoupper(quote(toba::usuario()->get_id()))  . ",
                                                                  id_programa,
                                                                  ".$filtro['entidad']."
                                                                 ) = 'S'
                                ))
                 )
             )
            )";
        }

        if (isset($filtro['ejercicio']) && !empty($filtro['ejercicio']) && isset($filtro['entidad']) && !empty($filtro['entidad'])) {
            $where .=" AND (    pkg_kr_ejercicios.pertenece_programa_a_estruc
                                                            (" . $filtro['ejercicio'] . ",
                                                             " . $filtro['entidad'] . ",
                                                             pp.id_programa
                                                            ) = 'S'
             AND pkg_pr_programas.imputable (pp.id_programa) = 'S'
            )
       )";
        unset($filtro['ejercicio']);
        unset($filtro['entidad']);
        }

        if (isset($filtro['devengado']) && isset($filtro['id_entidad']) && isset($filtro['ui_id_ejercicio'])){
            if (empty($filtro['id_compromiso']))
                $filtro['id_compromiso'] = 'NULL';
            $where .= " AND ( (((Pkg_Pr_Programas.Usuario_Pertenece_Ue ('".$filtro['usuario']."', pp.ID_PROGRAMA, pp.Id_Entidad) = 'S') Or ((Pkg_Pr_Programas.Usuario_Pertenece_Ue ('".$filtro['usuario']."', pp.ID_PROGRAMA, pp.Id_Entidad) = 'N') And Not Exists (Select 1 From Pr_Programas Where (Pkg_Pr_Programas.Usuario_Pertenece_Ue ('".$filtro['usuario']."', Id_Programa, Id_Entidad) = 'S' ))))))
      AND
      ((Pkg_kr_Ejercicios.Pertenece_Programa_A_Estruc(".$filtro['ui_id_ejercicio'].",".$filtro['id_entidad'].",pp.Id_Programa)='S' And Pkg_Pr_Programas.Imputable (pp.Id_Programa) = 'S' And (".$filtro['id_compromiso']." is null Or (".$filtro['id_compromiso']." Is Not Null And Exists (Select 1 From ad_compromisos adco, ad_compromisos_det adcode, ad_compromisos_imp adcoim where (adco.id_compromiso = ".$filtro['id_compromiso']." OR adco.id_compromiso_aju = ".$filtro['id_compromiso'].") and adco.aprobado = 'S' and adco.anulado = 'N' and adco.id_compromiso = adcode.id_compromiso and adcode.id_compromiso = adcoim.id_compromiso and adcode.id_detalle = adcoim.id_detalle And Adcode.Cod_Partida = '".$filtro['cod_partida']."' And Adcoim.Id_Entidad = ".$filtro['id_entidad']." And Adcoim.Id_Programa = pp.Id_Programa)))))
      ";
            unset($filtro['usuario']);
            unset($filtro['id_compromiso']);
            unset($filtro['cod_partida']);
            unset($filtro['ui_id_ejercicio']);
            unset($filtro['id_entidad']);
            unset($filtro['devengado']);

       }

        if (isset($filtro['id_entidad']) && isset($filtro['ui_id_ejercicio'])) {
            $where.= "AND (Pkg_kr_Ejercicios.Pertenece_Programa_A_Estruc(".$filtro['ui_id_ejercicio'].",".$filtro['id_entidad'].",pp.Id_Programa) = 'S'
                           And Pkg_Pr_Programas.Imputable (pp.Id_Programa) = 'S')";
        }

        if (isset($filtro['ui_control_pres']) && isset($filtro['id_entidad'])){
            $where .= " And ( '".$filtro['ui_sin_control_pres']."' = 'N' and ((p_id_preventivo is null Or (p_id_preventivo Is Not Null
                        And Exists(Select  1 From ad_preventivos adpr, ad_preventivos_det adprde, ad_preventivos_imp adprim
                        where (adpr.id_preventivo = p_id_preventivo OR adpr.id_preventivo_aju = p_id_preventivo)
                        AND adpr.ANULADO = 'N' AND adpr.APROBADO = 'S' AND adpr.id_preventivo = adprde.id_preventivo
                        AND Adprde.Id_Preventivo = Adprim.Id_Preventivo And Adprde.Id_Detalle = Adprim.Id_Detalle
                        And Adprde.Cod_Partida =".$filtro['cod_partida_compromiso']." And Adprim.Id_Entidad =".$filtro['id_entidad']." And Adprim.Id_Programa = pp.Id_Programa)))))";
        }

		if (isset($filtro['id_preventivo'])) {
			if (empty($filtro['id_preventivo'])){
				$where = str_replace("p_id_preventivo", "null", $where, $count);
			}else{
				$where = str_replace("p_id_preventivo", $filtro['id_preventivo'], $where, $count);
			}
		}

        if (isset($filtro['programa_en_estructura'])){
            $where .="and pp.id_programa in (SELECT esen.id_programa FROM  PR_ESTRUCTURAS_PROGRAMAS esen, KR_EJERCICIOS ej WHERE ej.id_estructura = esen.id_estructura AND ej.id_ejercicio = ".$filtro['id_ejercicio'].")";
            unset($filtro['programa_en_estructura']);
            unset($filtro['id_ejercicio']);
        }

		if (isset($filtro['imputable'])) {
			$where .= " AND PKG_PR_PROGRAMAS.IMPUTABLE(PP.ID_PROGRAMA) = 'S' ";
			unset($filtro['imputable']);
		}

		if (isset($filtro['en_actividad'])) {
			$where .= " AND PKG_PR_PROGRAMAS.ACTIVO(PP.ID_PROGRAMA) = 'S' ";
			unset($filtro['en_actividad']);
		}

		if (isset($filtro['id_entidad'])) {
			$where .= " AND PKG_PR_PROGRAMAS.ID_ENTIDAD(PP.ID_PROGRAMA) = " . quote($filtro['id_entidad'])." ";
			unset($filtro['id_entidad']);
		}
        /*-- Se parametrizo el select para que ande con lovs de tabulator --*/
        $select = "pp.*,
                        pkg_pr_programas.mascara_aplicar(pp.cod_programa) ||' - '|| pkg_pr_programas.cargar_descripcion(pp.id_programa) as lov_descripcion";
        if (isset($filtro['select_tabulator'])){
            $select = "pp.id_programa, pkg_pr_programas.mascara_aplicar(pp.cod_programa) ||' - '|| pkg_pr_programas.cargar_descripcion(pp.id_programa) as lov_descripcion";
            unset($filtro['select_tabulator']);
        }
        /* -- --*/
        unset($filtro['usuario']);
        unset($filtro['id_compromiso']);
        unset($filtro['ui_sin_control_pres']);
        unset($filtro['cod_partida_compromiso']);
        unset($filtro['ui_id_ejercicio']);
        unset($filtro['id_preventivo']);

        $where .= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'pp', '1=1');

        $sql = "SELECT $select
                FROM PR_PROGRAMAS pp
                WHERE $where
                ORDER BY lov_descripcion ASC";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }
	static public function get_lov_programas_con_saldo_x_nombre($nombre, $filtro = array())
	{
		if (isset($filtro['cod_unidad_administracion']) && isset($filtro['cod_partida']) && isset($filtro['fecha_comprobante']) && isset($filtro['id_entidad'])) {

			$cod_unidad_administracion = $filtro['cod_unidad_administracion'];
			$fecha_comprobante = $filtro['fecha_comprobante'];
			$cod_partida = $filtro['cod_partida'];
			$id_entidad = $filtro['id_entidad'];

			if (isset($nombre)) {
				$campos = array(
						'pp.cod_programa',
						'pkg_pr_programas.mascara_aplicar(pp.cod_programa)',
						'pp.descripcion',
						"pp.cod_programa ||' - '|| pkg_pr_programas.cargar_descripcion(pp.id_programa)",
						"pkg_pr_programas.mascara_aplicar(pp.cod_programa) ||' - '|| pkg_pr_programas.cargar_descripcion(pp.id_programa)",
				);
				$where = ctr_construir_sentencias::construir_sentencia_busqueda($nombre, $campos, true);
			} else {
				$where = '1=1';
			}

			if (isset($filtro['para_orden_compra'])){
                /*
			 						$where .=" and (((Pkg_Pr_Programas.Usuario_Pertenece_Ue (" . quote(toba::usuario()->get_id()) . ", pp.ID_PROGRAMA, pp.Id_Entidad) = 'S')
						           Or ((Pkg_Pr_Programas.Usuario_Pertenece_Ue (" . quote(toba::usuario()->get_id()) . ", pp.ID_PROGRAMA, pp.Id_Entidad) = 'N')
						                And Not Exists (Select 1 From Pr_Programas Where
						                                    (Pkg_Pr_Programas.Usuario_Pertenece_Ue (" . quote(toba::usuario()->get_id()) . ", Id_Programa, Id_Entidad) = 'S' ))
						            )
							        )
							      ) */
                                  $where .="

                                  and (Pkg_kr_Ejercicios.Pertenece_Programa_A_Estruc(".$filtro['id_ejercicio']." , $id_entidad ,pp.Id_Programa)='S'
							        And Pkg_Pr_Programas.Imputable (pp.Id_Programa) = 'S')";

				unset($filtro['para_orden_compra']);
				unset($filtro['id_ejercicio']);
	        	unset($filtro['parametros']);

			}
			if (isset($filtro['entidad']) && !empty($filtro['entidad'])){
				$where .= " AND (    ((   (pkg_pr_programas.usuario_pertenece_ue (" . quote(toba::usuario()->get_id()) . ",
															 pp.id_programa,
															 ".$filtro['entidad']."
															) = 'S'
					 )
				  OR (    (pkg_pr_programas.usuario_pertenece_ue
																(" . quote(toba::usuario()->get_id()) . ",
																 pp.id_programa,
																 ".$filtro['entidad']."
																) = 'N'
						  )
					  AND NOT EXISTS (
							 SELECT 1
							   FROM pr_programas
							  WHERE (pkg_pr_programas.usuario_pertenece_ue
																	 (" . quote(toba::usuario()->get_id()) . ",
																	  id_programa,
																	  ".$filtro['entidad']."
																	 ) = 'S'
									))
					 )
				 )
				)";
			}

			if (isset($filtro['ejercicio']) && !empty($filtro['ejercicio']) && isset($filtro['entidad']) && !empty($filtro['entidad'])) {
				$where .=" AND (    pkg_kr_ejercicios.pertenece_programa_a_estruc
																(" . $filtro['ejercicio'] . ",
																 " . $filtro['entidad'] . ",
																 pp.id_programa
																) = 'S'
				 AND pkg_pr_programas.imputable (pp.id_programa) = 'S'
				)
			)";
			unset($filtro['ejercicio']);
			unset($filtro['entidad']);
			}

			if (isset($filtro['devengado'])){
				if (empty($filtro['id_compromiso']))
					$filtro['id_compromiso'] = 'NULL';
				$where .= " AND ( (((Pkg_Pr_Programas.Usuario_Pertenece_Ue ('".$filtro['usuario']."', pp.ID_PROGRAMA, pp.Id_Entidad) = 'S') Or ((Pkg_Pr_Programas.Usuario_Pertenece_Ue ('".$filtro['usuario']."', pp.ID_PROGRAMA, pp.Id_Entidad) = 'N') And Not Exists (Select 1 From Pr_Programas Where (Pkg_Pr_Programas.Usuario_Pertenece_Ue ('".$filtro['usuario']."', Id_Programa, Id_Entidad) = 'S' ))))))
		  AND
		  ((Pkg_kr_Ejercicios.Pertenece_Programa_A_Estruc(".$filtro['ui_id_ejercicio'].",".$filtro['id_entidad'].",pp.Id_Programa)='S' And Pkg_Pr_Programas.Imputable (pp.Id_Programa) = 'S' And (".$filtro['id_compromiso']." is null Or (".$filtro['id_compromiso']." Is Not Null And Exists (Select 1 From ad_compromisos adco, ad_compromisos_det adcode, ad_compromisos_imp adcoim where (adco.id_compromiso = ".$filtro['id_compromiso']." OR adco.id_compromiso_aju = ".$filtro['id_compromiso'].") and adco.aprobado = 'S' and adco.anulado = 'N' and adco.id_compromiso = adcode.id_compromiso and adcode.id_compromiso = adcoim.id_compromiso and adcode.id_detalle = adcoim.id_detalle And Adcode.Cod_Partida = '".$filtro['cod_partida']."' And Adcoim.Id_Entidad = ".$filtro['id_entidad']." And Adcoim.Id_Programa = pp.Id_Programa)))))
		  ";
				unset($filtro['usuario']);
				unset($filtro['id_compromiso']);
				unset($filtro['cod_partida']);
				unset($filtro['ui_id_ejercicio']);
				unset($filtro['id_entidad']);
				unset($filtro['devengado']);

			}

			if (isset($filtro['id_entidad']) && isset($filtro['ui_id_ejercicio'])) {
				$where.= "AND (Pkg_kr_Ejercicios.Pertenece_Programa_A_Estruc(".$filtro['ui_id_ejercicio'].",".$filtro['id_entidad'].",pp.Id_Programa) = 'S'
							   And Pkg_Pr_Programas.Imputable (pp.Id_Programa) = 'S')";
			}

			if (isset($filtro['ui_control_pres'])){
				$where .= " And ( '".$filtro['ui_sin_control_pres']."' = 'N' and ((p_id_preventivo is null Or (p_id_preventivo Is Not Null
							And Exists(Select  1 From ad_preventivos adpr, ad_preventivos_det adprde, ad_preventivos_imp adprim
							where (adpr.id_preventivo = p_id_preventivo OR adpr.id_preventivo_aju = p_id_preventivo)
							AND adpr.ANULADO = 'N' AND adpr.APROBADO = 'S' AND adpr.id_preventivo = adprde.id_preventivo
							AND Adprde.Id_Preventivo = Adprim.Id_Preventivo And Adprde.Id_Detalle = Adprim.Id_Detalle
							And Adprde.Cod_Partida =".$filtro['cod_partida_compromiso']." And Adprim.Id_Entidad =".$filtro['id_entidad']." And Adprim.Id_Programa = pp.Id_Programa)))))";
			}

			if (isset($filtro['id_preventivo'])) {
				if (empty($filtro['id_preventivo'])){
					$where = str_replace("p_id_preventivo", "null", $where, $count);
				}else{
					$where = str_replace("p_id_preventivo", $filtro['id_preventivo'], $where, $count);
				}
			}

			if (isset($filtro['imputable'])) {
				$where .= " AND PKG_PR_PROGRAMAS.IMPUTABLE(PP.ID_PROGRAMA) = 'S' ";
				unset($filtro['imputable']);
			}

			if (isset($filtro['en_actividad'])) {
				$where .= " AND PKG_PR_PROGRAMAS.ACTIVO(PP.ID_PROGRAMA) = 'S' ";
				unset($filtro['en_actividad']);
			}

			unset($filtro['usuario']);
			unset($filtro['id_compromiso']);
			unset($filtro['ui_sin_control_pres']);
			unset($filtro['cod_partida_compromiso']);
			unset($filtro['ui_id_ejercicio']);
			unset($filtro['id_preventivo']);
			unset($filtro['cod_unidad_administracion']);
			unset($filtro['fecha_comprobante']);
			unset($filtro['cod_partida']);

			$where .= 'AND '. ctr_construir_sentencias::get_where_filtro($filtro, 'pp', '1=1');

			$sql = "SELECT  pp.*,
							pkg_pr_programas.mascara_aplicar(pp.cod_programa) ||' - '|| pkg_pr_programas.cargar_descripcion(pp.id_programa) || ' (' || TRIM(to_char(PKG_PR_TOTALES.SALDO_ACUMULADO_EGRESO(" . quote($cod_unidad_administracion) . ", PKG_KR_EJERCICIOS.RETORNAR_EJERCICIO(" . quote($fecha_comprobante) . "), " . quote($id_entidad) . ", pp.id_programa, " . quote($cod_partida) . ", NULL, NULL, 'PRES', SYSDATE), '$999,999,999,990.00')) ||')' as lov_descripcion_saldo
					FROM PR_PROGRAMAS pp
					WHERE $where
					ORDER BY lov_descripcion_saldo ASC;"; 
			$datos = toba::db()->consultar($sql);

			return $datos;
		} else {
			return array();
		}
	}
 	static public function get_lov_programas_sin_saldo ($nombre, $filtro = array() ){
            if (isset($nombre)) {
				$campos = array(
						'prpr.cod_programa',
						'prpr.descripcion',
						"prpr.cod_programa ||' - '|| pkg_pr_programas.cargar_descripcion(prpr.id_programa)",
				);
				$where = ctr_construir_sentencias::construir_sentencia_busqueda($nombre, $campos, false);
            } else {
                $where = '1=1';
           }
           if (isset($filtro['id_ejercicio']) && isset($filtro['id_entidad'])){
               $where .= " AND(((Pkg_Pr_Programas.Usuario_Pertenece_Ue (" . quote(toba::usuario()->get_id()) . ", PRPR.ID_PROGRAMA, PRPR.Id_Entidad) = 'S') Or ((Pkg_Pr_Programas.Usuario_Pertenece_Ue (" . quote(toba::usuario()->get_id()) . ", PRPR.ID_PROGRAMA, PRPR.Id_Entidad) = 'N') And Not Exists (Select 1 From Pr_Programas Where (Pkg_Pr_Programas.Usuario_Pertenece_Ue (" . quote(toba::usuario()->get_id()) . ", Id_Programa, Id_Entidad) = 'S' )))))
       AND (Pkg_kr_Ejercicios.Pertenece_Programa_A_Estruc(".$filtro['id_ejercicio'].",".$filtro['id_entidad'].",Prpr.Id_Programa)='S')
";
               unset($filtro['id_ejercicio']);
               unset($filtro['id_entidad']);
               $where .= 'AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'pe', '1=1');
               $sql = "SELECT  prpr.*, prpr.cod_programa || ' - ' || prpr.descripcion as lov_descripcion
                        FROM PR_PROGRAMAS prpr
			WHERE $where
			ORDER BY lov_descripcion ASC;";
			$datos = toba::db()->consultar($sql);
			return $datos;
		} else {
			return array();
                }

     }

     static public function get_lov_programas_sin_saldo2 ($nombre, $filtro = array() ){
            if (isset($nombre)) {
                $campos = array(
                        'prpr.cod_programa',
                        'prpr.descripcion',
                        "prpr.cod_programa ||' - '|| pkg_pr_programas.cargar_descripcion(prpr.id_programa)",
                );
                $where = ctr_construir_sentencias::construir_sentencia_busqueda($nombre, $campos, false);
            } else {
                $where = '1=1';
           }
           if (isset($filtro['imputable'])) {
               $where .=" and Pkg_Pr_Programas.Imputable(Prpr.Id_Programa) = '".$filtro['imputable']."'";
               unset($filtro['imputable']);
           }

           if (isset($filtro['id_ejercicio']) && isset($filtro['id_entidad'])){
               $where .= " AND(((Pkg_Pr_Programas.Usuario_Pertenece_Ue (" . quote(toba::usuario()->get_id()) . ", PRPR.ID_PROGRAMA, PRPR.Id_Entidad) = 'S') Or ((Pkg_Pr_Programas.Usuario_Pertenece_Ue (" . quote(toba::usuario()->get_id()) . ", PRPR.ID_PROGRAMA, PRPR.Id_Entidad) = 'N') And Not Exists (Select 1 From Pr_Programas Where (Pkg_Pr_Programas.Usuario_Pertenece_Ue (" . quote(toba::usuario()->get_id()) . ", Id_Programa, Id_Entidad) = 'S' )))))
       AND (Pkg_kr_Ejercicios.Pertenece_Programa_A_Estruc(".$filtro['id_ejercicio'].",".$filtro['id_entidad'].",Prpr.Id_Programa)='S')
";
               unset($filtro['id_ejercicio']);
               unset($filtro['id_entidad']);
               $where .= 'AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'pe', '1=1');
               $sql = "SELECT  prpr.*, prpr.cod_programa || ' - ' || prpr.descripcion as lov_descripcion
                        FROM PR_PROGRAMAS prpr
            WHERE $where
            ORDER BY lov_descripcion ASC;";
            $datos = toba::db()->consultar($sql); 
            return $datos;
        } else {
            return array();
                }

     }
    static public function get_nivel_programa ($cod_programa){
     	$sql ="select distinct nivel
			   from pr_programas
			   where cod_programa = $cod_programa";
     	$datos = toba::db()->consultar_fila($sql);
     	return $datos['nivel'];
     }
    static public function get_nodos_sin_padres_x_id_entidad($id_entidad){
     	$sql = "SELECT PRPR.*, '['  || pkg_pr_programas.MASCARA_APLICAR(PRPR.cod_programa) ||'] ' || PRPR.descripcion as descripcion_2
				FROM PR_PROGRAMAS PRPR
				WHERE PRPR.ID_PROGRAMA_PADRE IS NULL AND PRPR.ID_ENTIDAD = $id_entidad
				ORDER BY PRPR.COD_PROGRAMA desc;";
		$datos = toba::db()->consultar($sql);
		return $datos;
     }
	static public function tiene_hijos ($id_programa){
		if (isset($id_programa)){
			$sql = "select pkg_pr_programas.tiene_hijos($id_programa) as resultado from dual;";
			$datos = toba::db()->consultar_fila($sql);
			if ($datos['resultado'] === 'S')
				return true;
			elseif($datos['resultado'] === 'N')
				return false;
			else return null;
		}
	}
	static public function activo ($id_programa){
		if (isset($id_programa) && $id_programa != null)
			$sql = "select pkg_pr_programas.activo($id_programa) as activa from dual;";
		else
			$sql = "select pkg_pr_programas.activo(NULL) as activa from dual;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['activa'];
	}
	static public function get_hijos($id_programa){
		$sql = "SELECT PRPR.*, '['  || pkg_pr_programas.MASCARA_APLICAR(PRPR.cod_programa) ||'] ' || PRPR.descripcion as descripcion_2,
        prpr.id_programa id, pkg_pr_programas.MASCARA_APLICAR(prpr.cod_programa) cod_masc, 
        pkg_pr_programas.activo(prpr.id_programa) ui_activo,
        pkg_pr_programas.rrhh(prpr.id_programa) ui_rrhh
				FROM PR_PROGRAMAS PRPR
				WHERE PRPR.ID_PROGRAMA_PADRE = $id_programa
				ORDER BY PRPR.ID_PROGRAMA asc;";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	static public function get_programas_x_entidad_a_nivel ($id_entidad, $nivel){
		$sql = "SELECT LEVEL, P.*, '['  || pkg_pr_programas.MASCARA_APLICAR(p.cod_programa) ||'] ' || p.descripcion as descripcion_2, p.id_programa id, pkg_pr_programas.MASCARA_APLICAR(p.cod_programa) cod_masc, 
        pkg_pr_programas.activo(p.id_programa) ui_activo,
        pkg_pr_programas.rrhh(p.id_programa) ui_rrhh
				FROM PR_PROGRAMAS P
				WHERE EXISTS (SELECT P2.* FROM PR_PROGRAMAS P2 , PR_ENTIDADES E2
				                  WHERE P2.ID_ENTIDAD = E2.ID_ENTIDAD
				                  AND E2.ID_ENTIDAD = $id_entidad
				                  AND P.ID_PROGRAMA = P2.ID_PROGRAMA)
				              and p.nivel = $nivel
				CONNECT BY PRIOR P.ID_PROGRAMA = P.ID_PROGRAMA_PADRE
				START WITH P.ID_PROGRAMA_PADRE IS NULL
				ORDER BY LEVEL, P.COD_PROGRAMA";
		$datos = toba::db()->consultar($sql);
		return $datos;
	}
	static public function es_hoja ($id_programa){
		$sql = "select PKG_PR_PROGRAMAS.ES_HOJA($id_programa) as es_hoja from dual;";
		$datos = toba::db()->consultar_fila($sql);
		if ($datos['es_hoja'] == 'S')
			return true;
		elseif ($datos['es_hoja'] == 'N')
			return false;
	    else return null;
	}
    static public function get_nivel_programa_x_id ($id_programa){
     	$sql ="select distinct nivel
			   from pr_programas
			   where id_programa = $id_programa";
     	$datos = toba::db()->consultar_fila($sql);
     	return $datos['nivel'];
     }
    static public function get_lov_nivel_programa_x_nivel ($nivel){
    	$sql ="SELECT PRNIPR.NIVEL ||' - '|| PRNIPR.DESCRIPCION as lov_descripcion
			   FROM PR_NIVELES_PROGRAMA PRNIPR
			   WHERE PRNIPR.nivel = $nivel;";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['lov_descripcion'];
    }
    static public function get_lov_nivel_programa_x_nombre ($nombre, $filtro){
    if (isset($nombre)) {
			$trans_nivel = ctr_construir_sentencias::construir_translate_ilike('nivel', $nombre);
			$trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
			$where = "($trans_nivel OR $trans_descripcion)";
	    } else {
			$where = '1=1';
	    }
    	if (isset($filtro['nivel_real']) && !empty($filtro['nivel_real'])){
    		$where .= " and PRNIPR.nivel >= ".$filtro['nivel_real']."";
    		unset($filtro['nivel_real']);
    	}
  		$sql ="SELECT PRNIPR.*, PRNIPR.NIVEL ||' - '|| PRNIPR.DESCRIPCION as lov_descripcion
			   FROM PR_NIVELES_PROGRAMA PRNIPR
			   WHERE $where
			   ORDER BY NIVEL ASC;";
    	$datos = toba::db()->consultar($sql);
    	return $datos;
   }
    static public function cant_niveles (){
	   $sql ="SELECT pkg_pr_PROGRAMAS.cant_niveles NIVELES FROM DUAL;";
	   $datos = toba::db()->consultar_fila($sql);
	   return $datos['niveles'];
   }
    static public function cant_digitos_niv($nivel){
	   	$sql ="SELECT PKG_PR_PROGRAMAS.cant_digitos_niv($nivel) AS digitos FROM DUAL;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['digitos'];

   }
    static public function rrhh ($id_programa){
   		$sql ="SELECT PKG_PR_PROGRAMAS.RRHH($id_programa) AS rrhh FROM DUAL;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['rrhh'];
   }
    static public function cargar_codigo_programa($id_programa){
	   	$sql ="SELECT PKG_PR_PROGRAMAS.CARGAR_CODIGO($id_programa) AS codigo FROM DUAL;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['codigo'];
   }
	static public function get_entidad_x_id_programa ($id_programa){
		$sql ="SELECT ID_ENTIDAD FROM PR_PROGRAMAS WHERE ID_PROGRAMA =$id_programa;";
		$datos = toba::db()->consultar_fila($sql);
		return $datos['id_entidad'];
   	}
    static public function cargar_codigo_unidad_ejecutora ($id_programa){
   $sql ="SELECT PKG_PR_PROGRAMAS.COD_EJECUTORA($id_programa) codigo from dual;";
   $datos = toba::db()->consultar_fila($sql);
   return $datos['codigo'];
   }
	static public function cargar_codigo_proyecto ($id_programa){
	   $sql ="SELECT PKG_PR_PROGRAMAS.COD_PROYECTO($id_programa) codigo from dual;";
	   $datos = toba::db()->consultar_fila($sql);
	   return $datos['codigo'];
    }
	static public function cargar_codigo_funcion ($id_programa){
   		$sql ="SELECT PKG_PR_PROGRAMAS.COD_FUNCION($id_programa) codigo from dual;";
   		$datos = toba::db()->consultar_fila($sql);
   		return $datos['codigo'];
    }
	static public function cargar_descripcion ($id_programa){
		if ($id_programa != null){
	   		$sql ="SELECT PKG_PR_PROGRAMAS.CARGAR_DESCRIPCION($id_programa) descripcion from dual;";
	   		$datos = toba::db()->consultar_fila($sql);
	   		return $datos['descripcion'];
		}else return null;
    }
    static public function ultimo_del_nivel ($id_programa_padre, $id_entidad){
    	if ($id_programa_padre == null)
    		$id_programa_padre = 'null';
    	if ($id_entidad == null)
    		$id_entidad = 'null';
    	$sql = "SELECT pkg_pr_programas.ultimo_del_nivel ($id_programa_padre, $id_entidad) valor from dual;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['valor'];

    }
	static public function ultimo_del_nivel_especifico ($id_programa_padre, $id_entidad, $nivel){
    	if ($id_programa_padre == null)
    		$id_programa_padre = 'null';
    	if ($id_entidad == null)
    		$id_entidad = 'null';
    	if ($nivel == null)
    		$nivel ='null';
    	$sql = "SELECT pkg_pr_programas.ultimo_del_nivel_especifico($id_programa_padre, $id_entidad, $nivel) valor from dual;";
	   	$datos = toba::db()->consultar_fila($sql);
	   	return $datos['valor'];

    }
    static public function valor_del_nivel ($cod_programa, $nivel){
    	if ($cod_programa == null)
    		$cod_programa = 'null';
    	if ($nivel == null)
    		$nivel = 'null';
    	if (isset($nivel)  && !empty($nivel)){
	    	$sql ="SELECT pkg_pr_programas.valor_del_nivel($cod_programa, $nivel) valor from dual;";
	   		$datos = toba::db()->consultar_fila($sql);
	   		return $datos['valor'];
    	}else return null;
    }
    static public function get_lov_cod_geografico_x_codigo($codigo){
    	$sql ="SELECT PRCOGE.COD_GEOGRAFICO ||' - '|| PRCOGE.DESCRIPCION as lov_descripcion
	    	   FROM PR_CODIGOS_GEOGRAFICOS PRCOGE
			   WHERE PRCOGE.ACTIVO = 'S' and cod_geografico = $codigo;";
    	$datos = toba::db()->consultar_fila($sql);
    	return $datos['lov_descripcion'];
    }
    static public function get_lov_cod_geografico_x_nombre ($nombre, $filtro = array() ){
        if (isset($nombre)) {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('cod_geografico', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
            $where = "($trans_codigo OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
        $where .= 'AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'PRCOGE', '1=1');
        $sql = "SELECT  PRCOGE.*, PRCOGE.COD_GEOGRAFICO ||' - '|| PRCOGE.DESCRIPCION as lov_descripcion
    	    	FROM PR_CODIGOS_GEOGRAFICOS PRCOGE
    	    	WHERE $where
    	    	ORDER BY COD_GEOGRAFICO ASC;";
		$datos = toba::db()->consultar($sql);
		return $datos;
    }
 	static public function get_lov_proyecto_x_codigo($codigo){
	    	$sql =" SELECT PRTIPR.COD_TIPO_PROYECTO ||' - '|| PRTIPR.DESCRIPCION as lov_descripcion
					FROM PR_TIPOS_PROYECTOS PRTIPR
					WHERE PKG_PR_TIPO_PROYECTOS.ACTIVO(PRTIPR.COD_TIPO_PROYECTO) = 'S' and prtipr.cod_tipo_proyecto = $codigo;";
	    	$datos = toba::db()->consultar_fila($sql);
	    	return $datos['lov_descripcion'];
    }
    static public function get_lov_proyecto_x_nombre ($nombre, $filtro = array() ){
        if (isset($nombre)) {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('cod_tipo_proyecto', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
            $where = "($trans_codigo OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
        $where .= 'AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'PRTIPR', '1=1');
        $sql = "SELECT PRTIPR.*, PRTIPR.COD_TIPO_PROYECTO ||' - '|| PRTIPR.DESCRIPCION as lov_descripcion
				FROM PR_TIPOS_PROYECTOS PRTIPR
    	    	WHERE PKG_PR_TIPO_PROYECTOS.ACTIVO(PRTIPR.COD_TIPO_PROYECTO) = 'S' and $where
    	    	ORDER BY COD_TIPO_PROYECTO ASC;";
		$datos = toba::db()->consultar($sql);
		return $datos;
    }
	static public function get_lov_funcion_x_codigo($codigo){
		if (!empty($codigo)){
	    	$sql =" SELECT PRFU.COD_FUNCION ||' - '|| PRFU.DESCRIPCION as lov_descripcion
					FROM PR_FUNCIONES PRFU
					WHERE (PKG_PR_FUNCIONES.ACTIVA(PRFU.COD_FUNCION) = 'S' AND PKG_PR_FUNCIONES.IMPUTABLE(PRFU.COD_FUNCION) = 'S') and
					       prfu.cod_funcion = $codigo;";
	    	$datos = toba::db()->consultar_fila($sql);
	    	return $datos['lov_descripcion'];
		}else return null;

    }
    static public function get_lov_funcion_x_nombre ($nombre, $filtro = array() ){
        if (isset($nombre)) {
            $trans_codigo = ctr_construir_sentencias::construir_translate_ilike('cod_funcion', $nombre);
            $trans_descripcion = ctr_construir_sentencias::construir_translate_ilike('descripcion', $nombre);
            $where = "($trans_codigo OR $trans_descripcion)";
        } else {
            $where = '1=1';
        }
        $where .= 'AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'PRFU', '1=1');
        $sql = "SELECT prfu.*, PRFU.COD_FUNCION ||' - '|| PRFU.DESCRIPCION as lov_descripcion
					FROM PR_FUNCIONES PRFU
					WHERE (PKG_PR_FUNCIONES.ACTIVA(PRFU.COD_FUNCION) = 'S' AND PKG_PR_FUNCIONES.IMPUTABLE(PRFU.COD_FUNCION) = 'S') and $where
    	    	ORDER BY COD_FUNCION ASC;";
		$datos = toba::db()->consultar($sql);
		return $datos;
    }

    static public function eliminar ($id_programa)
    {
        $sql = "BEGIN 
                    :resultado := PKG_PR_PROGRAMAS.ELIMINAR_PROGRAMA(:id_programa);
                END;";
        $parametros = [ 
            [ 'nombre' => 'resultado',
              'tipo_dato' => PDO::PARAM_STR,
              'longitud' => 4000,
              'valor' => ''],
            [ 'nombre' => 'id_programa',
              'tipo_dato' => PDO::PARAM_INT,
              'longitud' => 20,
              'valor' => $id_programa],
        ];
        ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
    }

	static public function agregar_programa ($cod_programa, $descripcion, $nivel, $padre, $id_entidad, $cod_funcion,
											 $cod_tipo_proyecto, $cod_unidad_ejecutora,	$cod_geo, $activo, $rrhh){
        try{
        	if ($cod_geo == null)
        		$cod_geo = '';
        	if ($padre == null)
        		$padre = '';

        	$sql = "BEGIN :resultado := PKG_PR_PROGRAMAS.CREAR_PROGRAMA(:cod_programa, :descripcion, :nivel, :padre, :id_entidad, :cod_funcion, :cod_tipo_proyecto, :cod_unidad_ejecutora, :cod_geo, :activo, :rrhh);END;";
        		$parametros = array ( array(  'nombre' => 'cod_programa',
                                          'tipo_dato' => PDO::PARAM_INT,
                                          'longitud' => 20,
                                          'valor' => $cod_programa),

        							array(  'nombre' => 'descripcion',
				                                          'tipo_dato' => PDO::PARAM_STR,
				                                          'longitud' => 1000,
				                                          'valor' => $descripcion),

							            array(  'nombre' => 'nivel',
				                                          'tipo_dato' => PDO::PARAM_INT,
				                                          'longitud' => 1000,
				                                          'valor' => $nivel),

						             array(  'nombre' => 'padre',
			                                          'tipo_dato' => PDO::PARAM_INT,
			                                          'longitud' => 1000,
			                                          'valor' => $padre),

						             array( 'nombre' => 'id_entidad',
			                                          'tipo_dato' => PDO::PARAM_INT,
			                                          'longitud' => 1000,
			                                          'valor' => $id_entidad),

					                array( 'nombre' => 'cod_funcion',
		                                          'tipo_dato' => PDO::PARAM_INT,
		                                          'longitud' => 1000,
		                                          'valor' => $cod_funcion),
				                    array( 'nombre' => 'cod_tipo_proyecto',
		                                          'tipo_dato' => PDO::PARAM_INT,
		                                          'longitud' => 1000,
		                                          'valor' => $cod_tipo_proyecto),
				                    array( 'nombre' => 'cod_unidad_ejecutora',
	                                          'tipo_dato' => PDO::PARAM_INT,
	                                          'longitud' => 1000,
	                                          'valor' => $cod_unidad_ejecutora),
				                      array( 'nombre' => 'cod_geo',
	                                          'tipo_dato' => PDO::PARAM_STR,
	                                          'longitud' => 1000,
	                                          'valor' => $cod_geo),
				                        array( 'nombre' => 'activo',
	                                          'tipo_dato' => PDO::PARAM_STR,
	                                          'longitud' => 1000,
	                                          'valor' => $activo),
				                          array( 'nombre' => 'rrhh',
	                                          'tipo_dato' => PDO::PARAM_STR,
	                                          'longitud' => 1000,
	                                          'valor' => $rrhh),
                                  array(  'nombre' => 'resultado',
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => '')
                            );
            toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            if ($resultado[11]['valor'] == 'OK'){
           		toba::db()->cerrar_transaccion();
            }else{
           		toba::db()->abortar_transaccion();
           	 	toba::notificacion()->info($resultado[11]['valor']);
            }
            return $resultado[11]['valor'];

          } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
            toba::db()->abortar_transaccion();
        }
   	}
	static public function actualizar_programa ($id_programa, $descripcion, $id_entidad, $cod_funcion,
											 $cod_tipo_proyecto, $cod_unidad_ejecutora,	$cod_geo, $activo, $rrhh){
        try{
        	if ($cod_geo == null)
        		$cod_geo = '';

        	$sql = "BEGIN :resultado := PKG_PR_PROGRAMAS.ACTUALIZAR_PROGRAMA(:id_programa, :descripcion, :id_entidad, :cod_funcion, :cod_tipo_proyecto, :cod_unidad_ejecutora, :cod_geo, :activo, :rrhh);END;";
        	$parametros = array ( array(  'nombre' => 'id_programa',
                                          'tipo_dato' => PDO::PARAM_INT,
                                          'longitud' => 20,
                                          'valor' => $id_programa),
        						  array(  'nombre' => 'descripcion',
				                          'tipo_dato' => PDO::PARAM_STR,
				                          'longitud' => 1000,
				                          'valor' => $descripcion),
						          array(  'nombre' => 'id_entidad',
			                              'tipo_dato' => PDO::PARAM_INT,
			                              'longitud' => 1000,
			                              'valor' => $id_entidad),
					              array(  'nombre' => 'cod_funcion',
		                                  'tipo_dato' => PDO::PARAM_INT,
		                                  'longitud' => 1000,
		                                  'valor' => $cod_funcion),
				                  array(  'nombre' => 'cod_tipo_proyecto',
		                                  'tipo_dato' => PDO::PARAM_INT,
		                                  'longitud' => 1000,
		                                  'valor' => $cod_tipo_proyecto),
				                  array(  'nombre' => 'cod_unidad_ejecutora',
	                                      'tipo_dato' => PDO::PARAM_INT,
	                                      'longitud' => 1000,
	                                      'valor' => $cod_unidad_ejecutora),
				                  array(  'nombre' => 'cod_geo',
	                                      'tipo_dato' => PDO::PARAM_INT,
	                                      'longitud' => 1000,
	                                      'valor' => $cod_geo),
				                  array(  'nombre' => 'activo',
	                                      'tipo_dato' => PDO::PARAM_STR,
	                                      'longitud' => 5,
	                                      'valor' => $activo),
				                  array(  'nombre' => 'rrhh',
	                                      'tipo_dato' => PDO::PARAM_STR,
	                                      'longitud' => 5,
	                                      'valor' => $rrhh),
                                  array(  'nombre' => 'resultado',
                                          'tipo_dato' => PDO::PARAM_STR,
                                          'longitud' => 4000,
                                          'valor' => '')                   );
            toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            if ($resultado[9]['valor'] == 'OK'){
           		toba::db()->cerrar_transaccion();
            }else{
           		toba::db()->abortar_transaccion();
           	 	toba::notificacion()->info($resultado[9]['valor']);
            }
            return $resultado[9]['valor'];
          } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::db()->abortar_transaccion();
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
            toba::db()->abortar_transaccion();
        }
   	}
	static public function mascara_aplicar ($cod_programa){
		if (isset($cod_programa)){
			$sql = "SELECT pkg_pr_programas.MASCARA_APLICAR($cod_programa) COD_PROGRAMA FROM DUAL";
			$datos = toba::db()->consultar_fila($sql);
			return $datos['cod_programa'];
		}else
			return null;
	}
}

?>
