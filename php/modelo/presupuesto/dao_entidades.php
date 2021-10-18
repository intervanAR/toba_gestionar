<?php

class dao_entidades {

    static public function get_datos($filtro = array()) {

        $where = "1=1";
        if (isset($filtro['imputable'])){
            $where .=" and PKG_PR_ENTIDADES.IMPUTABLE(ID_ENTIDAD) = 'S'";
            unset($filtro['imputable']);
        }
        if (isset($filtro['id_padre'])){
            $where .=" and pe.id_entidad_padre = ".$filtro['id_padre'];
            unset($filtro['id_padre']);
        }

        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'pe', '1=1' );
        $sql = "SELECT pe.*, pkg_pr_entidades.activa (pe.id_entidad) ui_activa,
       pkg_pr_entidades.entidad_recursos (pe.id_entidad) ui_entidad_recurso,
       pkg_pr_entidades.entidad_gastos (pe.id_entidad) ui_entidad_gasto,
       pkg_pr_entidades.mascara_aplicar (pe.cod_entidad) cod_entidad_masc,
       pkg_pr_entidades.MASCARA_APLICAR(pe.cod_entidad) cod_masc, pe.id_entidad id,
          '['
       || pkg_pr_entidades.mascara_aplicar (pe.cod_entidad)
       || '] '
       || pe.descripcion AS descripcion_2
                FROM PR_ENTIDADES pe
                WHERE  $where
                order by id_entidad asc;";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }

    static public function get_entidades($filtro = array()) {

        $where = "1=1";
        if (isset($filtro['imputable'])){
            $where .=" and PKG_PR_ENTIDADES.IMPUTABLE(ID_ENTIDAD) = 'S'";
            unset($filtro['imputable']);
        }
        if (isset($filtro['sin_padre'])){
            $where .=" and pe.id_entidad_padre is null";
            unset($filtro['sin_padre']);
        }
        $where .= " AND " . ctr_construir_sentencias::get_where_filtro($filtro, 'pe', '1=1');
        $sql = "SELECT pe.*, '['  || pkg_pr_entidades.MASCARA_APLICAR(pe.cod_entidad) ||'] ' || pe.descripcion as descripcion_2
                FROM PR_ENTIDADES pe
                WHERE  $where
                order by id_entidad asc;";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }
    static public function get_entidad_x_id($id_entidad){
        if (isset($id_entidad) && !empty($id_entidad)){
            $sql = "SELECT pe.*, '['  || pkg_pr_entidades.MASCARA_APLICAR(pe.cod_entidad) ||'] ' || pe.descripcion as descripcion_2 
                    FROM PR_ENTIDADES pe
                    WHERE pe.ID_ENTIDAD = $id_entidad";
            return toba::db()->consultar_fila($sql);
        }
    } 
    static public function mascara_aplicar($cod_entidad){
        $sql ="select pkg_pr_ENTIDADES.mascara_aplicar($cod_entidad) codigo from dual;";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['codigo'];
    }
    
    static public function get_lov_entidades_x_id($id_entidad) {
        if (isset($id_entidad)) {
            $sql = "SELECT  pkg_pr_entidades.mascara_aplicar(pe.cod_entidad) ||' - '|| pkg_pr_entidades.cargar_descripcion(pe.id_entidad) lov_descripcion
                    FROM PR_ENTIDADES pe
                    WHERE id_entidad = " . quote($id_entidad) . ";";
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

    static public function get_lov_entidades_x_nombre($nombre, $filtro = array()) 
    {
        if (isset($nombre)) {
            $campos = array(
                        'pe.cod_entidad',
                        'pkg_pr_entidades.mascara_aplicar(pe.cod_entidad)',
                        'pe.descripcion',
                        "pkg_pr_entidades.mascara_aplicar(pe.cod_entidad) ||' - '|| pkg_pr_entidades.cargar_descripcion(pe.id_entidad)",
                );
            $where = ctr_construir_sentencias::construir_sentencia_busqueda($nombre, $campos, false);
        } else {
            $where = '1=1';
        }
        if ((isset($filtro['cod_unidad_administradora']) && !empty($filtro['cod_unidad_administradora']))
                && (isset($filtro['ui_id_ejercicio']) && !empty($filtro['ui_id_ejercicio']))) {
            $where .= " AND ((   (pkg_pr_entidades.usuario_pertenece_ua (" . quote(toba::usuario()->get_id()) . ", pe.id_entidad) = 'S')
                         OR (    pkg_pr_entidades.usuario_pertenece_ua (" . quote(toba::usuario()->get_id()) . ", pe.id_entidad) = 'N'
                        AND NOT EXISTS (
                               SELECT 1
                                 FROM pr_entidades
                                WHERE pkg_pr_entidades.usuario_pertenece_ua (" . quote(toba::usuario()->get_id()) . ",
                                                                             id_entidad
                                                                            ) = 'S')
                       )
                       )
                       AND (    pkg_kr_ejercicios.pertenece_entidad_a_estruc
                                    (" . $filtro['cod_unidad_administradora'] . ",
                                     " . $filtro['ui_id_ejercicio'] . ",
                                     pe.id_entidad ) = 'S'
                      AND pkg_pr_entidades.imputable (pe.id_entidad) = 'S'))";
            unset($filtro['ui_id_ejercicio']);
        }
        if (isset($filtro['cod_un_ad'])) {

            $where.= " AND (Pkg_Kr_Ejercicios.Pertenece_Entidad_A_Estruc(" . $filtro['cod_un_ad'] . "," . $filtro['ui_id_ejercicio'] . "
            ,pe.id_entidad) = 'S' And Pkg_Pr_Entidades.Imputable (pe.id_entidad) = 'S')
            And ( p_id_preventivo Is Null Or ( p_id_preventivo Is Not Null And Exists (    
            Select 1 From ad_preventivos adpr, ad_preventivos_det adprde, ad_preventivos_imp adprim 
            where (adpr.id_preventivo = p_id_preventivo OR adpr.id_preventivo_aju = p_id_preventivo)
            AND adpr.ANULADO = 'N' AND adpr.APROBADO = 'S' AND adpr.id_preventivo = adprde.id_preventivo    
            AND Adprde.Id_Preventivo = Adprim.Id_Preventivo And Adprde.Id_Detalle = Adprim.Id_Detalle
            And Adprde.Cod_Partida =" . $filtro['cod_partida'] . " And Adprim.Id_Entidad = pe.id_entidad)))";

            unset($filtro['cod_un_ad']);
            unset($filtro['ui_id_ejercicio']);            
            
        }

        if (isset($filtro['cod_partida'])) { 
            $where .= " And ( p_id_preventivo Is Null Or ( p_id_preventivo Is Not Null And Exists (    
                 Select 1 
                 From ad_preventivos adpr, ad_preventivos_det adprde, ad_preventivos_imp adprim 
                 where (adpr.id_preventivo = p_id_preventivo OR adpr.id_preventivo_aju = p_id_preventivo)
                        AND adpr.ANULADO = 'N' AND adpr.APROBADO = 'S' AND adpr.id_preventivo = adprde.id_preventivo    
                        AND Adprde.Id_Preventivo = Adprim.Id_Preventivo And Adprde.Id_Detalle = Adprim.Id_Detalle
                        And Adprde.Cod_Partida =" . $filtro['cod_partida'] . " And Adprim.Id_Entidad = pe.id_entidad)))";            
        }
        if (isset($filtro['para_modificacion_presupuesto'])){
            $where .=" AND ((Pkg_Pr_Entidades.Usuario_Pertenece_Ua (" . quote(toba::usuario()->get_id()) . ", PE.ID_ENTIDAD) = 'S') 
                        Or ((Pkg_Pr_Entidades.Usuario_Pertenece_Ua (" . quote(toba::usuario()->get_id()) . ", PE.ID_ENTIDAD) = 'N') 
                            And Not Exists (Select 1 
                                            From Pr_Entidades 
                                            Where (Pkg_Pr_Entidades.Usuario_Pertenece_Ua (" . quote(toba::usuario()->get_id()) . ", Id_Entidad) = 'S')
                                            )
                            )
                            )
                        AND (Pkg_Kr_Ejercicios.Pertenece_Entidad_A_Estruc (".$filtro['cod_unidad_administracion'].",
                                                      ".$filtro['id_ejercicio'].", pe.Id_Entidad ) = 'S' 
                             And Pkg_Pr_Entidades.Imputable (pe.Id_Entidad) = 'S') ";
            unset($filtro['para_modificacion_presupuesto']);
            unset($filtro['cod_unidad_administracion']);
            unset($filtro['id_ejercicio']);
        }
        if (isset($filtro['id_compromiso'])) {// where devengado edicion
            $where .= " AND (" . $filtro['id_compromiso'] . " Is Null 
                            Or (" . $filtro['id_compromiso'] . " Is Not Null And Exists (Select 1 
                                                  From ad_compromisos adco, ad_compromisos_det adcode, ad_compromisos_imp adcoim 
                                                  where (adco.id_compromiso = " . $filtro['id_compromiso'] . " 
                                                         OR adco.id_compromiso_aju = " . $filtro['id_compromiso'] . ") 
                                                         and adco.aprobado = 'S' and adco.anulado = 'N' 
                                                         and adco.id_compromiso = adcode.id_compromiso 
                                                         and adcode.id_compromiso = adcoim.id_compromiso 
                                                         and adcode.id_detalle = adcoim.id_detalle 
                                                         And Adcode.Cod_Partida = " . $filtro['cod_partida'] . " 
                                                         And Adcoim.Id_Entidad = pe.Id_Entidad)))";
            unset($filtro['id_compromiso']);
        }
        if (isset($filtro['usuario'])) { //where devengado edicion
            $where .= " AND (((Pkg_Pr_Entidades.Usuario_Pertenece_Ua ('" . $filtro['usuario'] . "', pe.ID_ENTIDAD) = 'S') 
                              Or ((Pkg_Pr_Entidades.Usuario_Pertenece_Ua ('" . $filtro['usuario'] . "', pe.ID_ENTIDAD) = 'N') 
                              And Not Exists (Select 1 
                                              From Pr_Entidades 
                                              Where (Pkg_Pr_Entidades.Usuario_Pertenece_Ua ('" . $filtro['usuario'] . "', Id_Entidad) = 'S')))))";
            unset($filtro['usuario']);
        }
        if (empty($filtro['id_preventivo'])) {
            $where = str_replace("p_id_preventivo", "null", $where, $count);
        } else {
            $where = str_replace("p_id_preventivo", $filtro['id_preventivo'], $where, $count);
            unset($filtro['id_preventivo']);
        }

        if (isset($filtro['imputable'])) {
            $where .= " AND PKG_PR_ENTIDADES.IMPUTABLE(PE.ID_ENTIDAD) = 'S' ";
            unset($filtro['imputable']);
        }
        
        if (isset($filtro['en_actividad'])) {
            $where .= " AND PKG_PR_ENTIDADES.ACTIVA(PE.ID_ENTIDAD) = 'S' ";
            unset($filtro['en_actividad']);
        }
        if (isset($filtro['devengado'])){
            unset($filtro['cod_partida']);
            unset($filtro['devengado']);
        }
        
        if (isset($filtro['cod_un_ad'])){
            unset($filtro['cod_partida']);
        }
        
        if (isset($filtro['usuario_activa_imputable'])) {
            $where .= " AND PKG_KR_USUARIOS.TIENE_UA(upper('".$filtro['usuario1']."'),COD_UNIDAD_ADMINISTRACION) = 'S'
                        AND PKG_PR_ENTIDADES.ACTIVA(ID_ENTIDAD) = 'S'
                        AND PKG_PR_ENTIDADES.IMPUTABLE(ID_ENTIDAD) = 'S'";

            unset($filtro['usuario_activa_imputable']);
            unset($filtro['usuario1']);
        }
        
        if (isset($filtro['exista_saldo_ingreso']) && $filtro['exista_saldo_ingreso'] == '1' && isset($filtro['cod_unidad_administracion']) && isset($filtro['fecha_comprobante']) && isset($filtro['cod_recurso'])) {
            $where .= " AND EXISTS (SELECT 1 
                                    FROM V_PR_SALDOS_INGRESOS V_SAIN 
                                    WHERE /*V_SAIN.ID_EJERCICIO = ( SELECT KE.ID_EJERCICIO 
                                                                    FROM KR_EJERCICIOS KE
                                                                    WHERE KE.ABIERTO = 'S' 
                                                                    AND KE.CERRADO = 'N'
                                                                    AND (" . quote($filtro['fecha_comprobante']) ." BETWEEN KE.FECHA_INICIO AND KE.FECHA_FIN)
                                                                )
                                    AND*/ PE.ID_ENTIDAD = V_SAIN.ID_ENTIDAD AND
                                    V_SAIN.COD_RECURSO  = " . quote($filtro['cod_recurso']) . "
                                    AND V_SAIN.COD_UNIDAD_ADMINISTRACION = " . quote($filtro['cod_unidad_administracion']) . ") ";
            unset($filtro['exista_saldo_ingreso']);
        }
        
        unset($filtro['cod_unidad_administradora']);
        unset($filtro['fecha_comprobante']);
        unset($filtro['cod_recurso']);
        
        if (isset($filtro['pertenece_estructura']) && isset($filtro['id_ejercicio'])) {
            $where .= " AND Pkg_Kr_Ejercicios.Pertenece_Entidad_A_Estruc
                                              (PE.COD_UNIDAD_ADMINISTRACION
                                              ," . quote($filtro['id_ejercicio']) . "
                                              ,PE.Id_Entidad
                                              ) = 'S'  ";
            unset($filtro['pertenece_estructura']);
            unset($filtro['id_ejercicio']);
        }

        if (isset($filtro['es_entidad_del_sector']) && isset($filtro['cod_sector'])) {
            $where .= " And pkg_solicitudes.es_entidad_del_sector(" . quote($filtro['cod_sector']) . ", pe.id_entidad) = 'S'";
            unset($filtro['es_entidad_del_sector']);
            unset($filtro['cod_sector']);
        }

        if (isset($filtro['entidad_gastos'])) {
            $where .= " And pkg_pr_entidades.entidad_gastos(pe.id_entidad) = '".$filtro['entidad_gastos']."'";
            unset($filtro['entidad_gastos']);
        }

        if (isset($filtro['entidad_en_estructura']) && isset($filtro['id_ejercicio'])) {
            $where .= " And pkg_pr_entidades.entidad_en_estructura(pe.id_entidad, ".$filtro['id_ejercicio'].") = '".$filtro['entidad_en_estructura']."'";
            unset($filtro['entidad_en_estructura']);
            unset($filtro['id_ejercicio']);
        }
        
        unset($filtro['cod_partida']); 
        $where .= 'AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'pe', '1=1');

        $sql = "SELECT  pe.*, 
                        pkg_pr_entidades.mascara_aplicar(pe.cod_entidad) ||' - '|| pkg_pr_entidades.cargar_descripcion(pe.id_entidad) as lov_descripcion
                FROM PR_ENTIDADES pe
                WHERE $where
                ORDER BY lov_descripcion ASC;";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }
    
    static public function get_lov_entidades_con_saldo_x_nombre($nombre, $filtro = array()) 
    {
        if (isset($filtro['cod_partida']) && isset($filtro['fecha_comprobante'])) { 
            
            $fecha_comprobante = $filtro['fecha_comprobante'];
            $cod_partida = $filtro['cod_partida'];
            if (isset($filtro['cod_unidad_administracion'])) {
                $cod_unidad_administracion = quote($filtro['cod_unidad_administracion']);
            } else {
                $cod_unidad_administracion = "pe.cod_unidad_administracion";
            }
            
            
            if (isset($nombre)) {
                $campos = array(
                        'pe.cod_entidad',
                        'pkg_pr_entidades.mascara_aplicar(pe.cod_entidad)',
                        'pe.descripcion',
                        "pe.cod_entidad || ' - ' || pe.descripcion",
                        "pkg_pr_entidades.mascara_aplicar(pe.cod_entidad) || ' - ' || pe.descripcion",
                );
                $where = ctr_construir_sentencias::construir_sentencia_busqueda($nombre, $campos, true);    
            } else {
                $where = '1=1';
            }
            if ((isset($filtro['cod_unidad_administradora']) && !empty($filtro['cod_unidad_administradora']))
                    && (isset($filtro['ui_id_ejercicio']) && !empty($filtro['ui_id_ejercicio']))) {
                $where .= " AND ((   (pkg_pr_entidades.usuario_pertenece_ua (" . quote(toba::usuario()->get_id()) . ", pe.id_entidad) = 'S')
                             OR (    pkg_pr_entidades.usuario_pertenece_ua (" . quote(toba::usuario()->get_id()) . ", pe.id_entidad) = 'N'
                            AND NOT EXISTS (
                                   SELECT 1
                                     FROM pr_entidades
                                    WHERE pkg_pr_entidades.usuario_pertenece_ua (" . quote(toba::usuario()->get_id()) . ",
                                                                                 id_entidad
                                                                                ) = 'S')
                           )
                           )
                           AND (    pkg_kr_ejercicios.pertenece_entidad_a_estruc
                                        (" . $filtro['cod_unidad_administradora'] . ",
                                         " . $filtro['ui_id_ejercicio'] . ",
                                         pe.id_entidad ) = 'S'
                          AND pkg_pr_entidades.imputable (pe.id_entidad) = 'S'))";
                unset($filtro['cod_unidad_administradora']);
                unset($filtro['ui_id_ejercicio']);
            }
            if (isset($filtro['cod_un_ad'])) {
                
                $where.= " AND (Pkg_Kr_Ejercicios.Pertenece_Entidad_A_Estruc(" . $filtro['cod_un_ad'] . "," . $filtro['ui_id_ejercicio'] . "
                ,pe.id_entidad) = 'S' And Pkg_Pr_Entidades.Imputable (pe.id_entidad) = 'S')
                And ( p_id_preventivo Is Null Or ( p_id_preventivo Is Not Null And Exists (    
                Select 1 From ad_preventivos adpr, ad_preventivos_det adprde, ad_preventivos_imp adprim 
                where (adpr.id_preventivo = p_id_preventivo OR adpr.id_preventivo_aju = p_id_preventivo)
                AND adpr.ANULADO = 'N' AND adpr.APROBADO = 'S' AND adpr.id_preventivo = adprde.id_preventivo    
                AND Adprde.Id_Preventivo = Adprim.Id_Preventivo And Adprde.Id_Detalle = Adprim.Id_Detalle
                And Adprde.Cod_Partida =" . $filtro['cod_partida'] . " And Adprim.Id_Entidad = pe.id_entidad)))";
                
                unset($filtro['cod_un_ad']);
                unset($filtro['ui_id_ejercicio']);
            }
            
            if (isset($filtro['id_compromiso'])) {// where devengado edicion
                $where .= " AND (" . $filtro['id_compromiso'] . " Is Null 
                                Or (" . $filtro['id_compromiso'] . " Is Not Null And Exists (Select 1 
                                                      From ad_compromisos adco, ad_compromisos_det adcode, ad_compromisos_imp adcoim 
                                                      where (adco.id_compromiso = " . $filtro['id_compromiso'] . " 
                                                             OR adco.id_compromiso_aju = " . $filtro['id_compromiso'] . ") 
                                                             and adco.aprobado = 'S' and adco.anulado = 'N' 
                                                             and adco.id_compromiso = adcode.id_compromiso 
                                                             and adcode.id_compromiso = adcoim.id_compromiso 
                                                             and adcode.id_detalle = adcoim.id_detalle 
                                                             And Adcode.Cod_Partida = " . $filtro['cod_partida'] . " 
                                                             And Adcoim.Id_Entidad = pe.Id_Entidad)))";
                unset($filtro['id_compromiso']);
                unset($filtro['cod_partida']);
            }
            if (isset($filtro['usuario'])) { //where devengado edicion
                $where .= " AND (((Pkg_Pr_Entidades.Usuario_Pertenece_Ua ('" . $filtro['usuario'] . "', pe.ID_ENTIDAD) = 'S') 
                                  Or ((Pkg_Pr_Entidades.Usuario_Pertenece_Ua ('" . $filtro['usuario'] . "', pe.ID_ENTIDAD) = 'N') 
                                  And Not Exists (Select 1 
                                                  From Pr_Entidades 
                                                  Where (Pkg_Pr_Entidades.Usuario_Pertenece_Ua ('" . $filtro['usuario'] . "', Id_Entidad) = 'S')))))";
                unset($filtro['usuario']);
            }
            if (empty($filtro['id_preventivo'])) {
                $where = str_replace("p_id_preventivo", "null", $where, $count);
            } else {
                $where = str_replace("p_id_preventivo", $filtro['id_preventivo'], $where, $count);
                unset($filtro['id_preventivo']);
            }
            
            if (isset($filtro['imputable'])) {
                $where .= " AND PKG_PR_ENTIDADES.IMPUTABLE(PE.ID_ENTIDAD) = 'S' ";
                unset($filtro['imputable']);
            }
            
            if (isset($filtro['en_actividad'])) {
                $where .= " AND PKG_PR_ENTIDADES.ACTIVA(PE.ID_ENTIDAD) = 'S' ";
                unset($filtro['en_actividad']);
            }
                        
            unset($filtro['cod_unidad_administracion']);
            unset($filtro['fecha_comprobante']);
            unset($filtro['cod_partida']);
            
            if (isset($filtro['pertenece_estructura']) && isset($filtro['id_ejercicio'])) {
                $where .= " AND Pkg_Kr_Ejercicios.Pertenece_Entidad_A_Estruc
                                                  (PE.COD_UNIDAD_ADMINISTRACION
                                                  ," . quote($filtro['id_ejercicio']) . "
                                                  ,PE.Id_Entidad
                                                  ) = 'S'  ";
                unset($filtro['pertenece_estructura']);
                unset($filtro['id_ejercicio']);
            }

            if (isset($filtro['es_entidad_del_sector']) && isset($filtro['cod_sector'])) {
                $where .= " And pkg_solicitudes.es_entidad_del_sector(" . quote($filtro['cod_sector']) . ", pe.id_entidad) = 'S'";
                unset($filtro['es_entidad_del_sector']);
                unset($filtro['cod_sector']);
            }
            
            $where .= 'AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'pe', '1=1');
            
            $sql = "SELECT  pe.*, 
                            pkg_pr_entidades.mascara_aplicar(pe.cod_entidad) ||' - '|| pkg_pr_entidades.cargar_descripcion(pe.id_entidad) || ' (' || TRIM(to_char(PKG_PR_TOTALES.SALDO_ACUMULADO_EGRESO($cod_unidad_administracion, PKG_KR_EJERCICIOS.RETORNAR_EJERCICIO(" . quote($fecha_comprobante) . "), PE.ID_ENTIDAD, NULL, " . quote($cod_partida) . ", NULL, NULL, 'PRES', SYSDATE), '$999,999,999,990.00')) ||')' as lov_descripcion_saldo
                    FROM PR_ENTIDADES pe
                    WHERE $where
                    ORDER BY lov_descripcion_saldo ASC;";
            $datos = toba::db()->consultar($sql);
            return $datos;
        } else {
            return array();
        }
    }
        
    static public function get_lov_entidades_sin_saldo ($nombre, $filtro = array() ){
          if (isset($nombre)) {
              $campos = array(
                        'PREN.cod_entidad',
                        'PREN.descripcion',
                        "PREN.cod_entidad || ' - ' || PREN.descripcion",
                );
                $where = ctr_construir_sentencias::construir_sentencia_busqueda($nombre, $campos, false);
            } else {
                $where = '1=1'; 
           }
           if (isset($filtro['id_ejercicio']) && isset($filtro['cod_unidad_administracion'])){
               $where .= " AND (((Pkg_Pr_Entidades.Usuario_Pertenece_Ua (" . quote(toba::usuario()->get_id()) . ", PREN.ID_ENTIDAD) = 'S') Or ((Pkg_Pr_Entidades.Usuario_Pertenece_Ua (" . quote(toba::usuario()->get_id()) . ", PREN.ID_ENTIDAD) = 'N') And Not Exists (Select 1 From Pr_Entidades Where (Pkg_Pr_Entidades.Usuario_Pertenece_Ua (" . quote(toba::usuario()->get_id()) . ", Id_Entidad) = 'S')))))
                           AND (Pkg_Kr_Ejercicios.Pertenece_Entidad_A_Estruc(".$filtro['cod_unidad_administracion'].", ".$filtro['id_ejercicio'].",Pren.Id_Entidad) = 'S' And Pkg_Pr_Entidades.Imputable (Pren.Id_Entidad) = 'S')";
               unset($filtro['cod_unidad_administracion']);
               unset($filtro['id_ejercicio']);
               $where .= 'AND ' . ctr_construir_sentencias::get_where_filtro($filtro, 'pe', '1=1');
               $sql = "SELECT  PREN.*, PREN.cod_entidad || ' - ' || PREN.descripcion as lov_descripcion 
                        FROM PR_ENTIDADES PREN
        WHERE $where
        ORDER BY lov_descripcion ASC;";
        $datos = toba::db()->consultar($sql);
        return $datos;
    } else {
        return array();
                    }
        
     }  

     /* Utilizado en Formulario de Saldos. */
    static public function get_lov_entidades_sin_saldo_2 ($nombre, $filtro = array() ){
        if (isset($nombre)) {
            $campos = array(
                    'PREN.cod_entidad',
                    'PREN.descripcion',
                    "PREN.cod_entidad || ' - ' || PREN.descripcion",);
                $where = ctr_construir_sentencias::construir_sentencia_busqueda($nombre, $campos, false);
        } else {
            $where = '1=1'; 
        }

        if (isset($filtro['id_ejercicio'])){
            if (isset($filtro['cod_unidad_administracion']) && !empty($filtro['cod_unidad_administracion'])){
                $where .= " and Pkg_Kr_Ejercicios.Pertenece_Entidad_A_Estruc (".$filtro['cod_unidad_administracion']." ,".$filtro['id_ejercicio']." ,Pren.Id_Entidad ) = 'S'";
                unset($filtro['cod_unidad_administracion']);
            }else{
                $where .= " and Pkg_Kr_Ejercicios.Pertenece_Entidad_A_Estruc (null, ".$filtro['id_ejercicio']." ,Pren.Id_Entidad ) = 'S'";
            }
            unset($filtro['id_ejercicio']);
        }

        if (isset($filtro['imputable'])){
            $where .= " And Pkg_Pr_Entidades.Imputable (Pren.Id_Entidad) = '".$filtro['imputable']."'";
            unset($filtro['imputable']);
        }

        $sql = "SELECT  PREN.*, PREN.cod_entidad || ' - ' || PREN.descripcion as lov_descripcion 
                        FROM PR_ENTIDADES PREN
                 WHERE $where
              ORDER BY lov_descripcion ASC";

        $datos = toba::db()->consultar($sql);
        return $datos;

    }    
     
    static public function tiene_programa ($id_entidad){
        $sql = "select PKG_PR_ENTIDADES.TIENE_PROGRAMA($id_entidad) tiene_entidad from dual;";
        $datos = toba::db()->consultar_fila($sql);
        if ($datos['tiene_entidad'] === 'S'){
            return true;
        }elseif ($datos['tiene_entidad'] === 'N')
            return false;
        else return null;
    }
     
    static public function get_nivel_entidad ($id_entidad){
         $sql = "select nivel
                from pr_entidades
                where id_entidad = $id_entidad;";
         $datos = toba::db()->consultar_fila($sql);
         return $datos['nivel'];
    }
     
    static public function cargar_codigo_entidad($id_entidad){
        $sql ="SELECT PKG_PR_ENTIDADES.CARGAR_CODIGO($id_entidad) AS codigo FROM DUAL;";
        $datos = toba::db()->consultar_fila($sql);
        if ($datos['codigo']){
            return $datos['codigo'];
        } else 
            return null;
   }
   
    static public function cargar_descripcion_entidad($id_entidad){
        $sql ="SELECT PKG_PR_ENTIDADES.CARGAR_DESCRIPCION($id_entidad) AS descripcion FROM DUAL;";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['descripcion'];
   }

    static public function es_hoja ($id_entidad){
        $sql = "SELECT PKG_PR_ENTIDADES.ES_HOJA($id_entidad) AS ES_HOJA FROM DUAL;";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['es_hoja'];
    }
   
    static public function activa ($cod_dependencia){
        $sql = "SELECT PKG_PR_ENTIDADES.ACTIVA($cod_dependencia) AS activa FROM DUAL;";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['activa'];
    }
    
    static public function entidad_recursos ($id_entidad){
        $sql = "SELECT PKG_PR_ENTIDADES.ENTIDAD_RECURSOS($id_entidad) AS er FROM DUAL;";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['er'];
    }
    
    static public function entidad_gastos ($id_entidad){
        $sql = "SELECT PKG_PR_ENTIDADES.ENTIDAD_GASTOS($id_entidad) AS eg FROM DUAL;";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['eg'];
    }
    
    static public function codigo_a_nivel_hasta ($cod_entidad_hasta, $nivel_entidad){
        $sql = "SELECT Pkg_Pr_Entidades.CODIGO_A_NIVEL_HASTA($cod_entidad_hasta, $nivel_entidad) AS codigo FROM DUAL;";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['codigo'];
    }
    
    static public function get_hijos ($id_entidad){
        $sql = "select PE.*, '['  || pkg_pr_entidades.MASCARA_APLICAR(pe.cod_entidad) ||'] ' || pe.descripcion as descripcion_2
                from pr_entidades PE
                where PE.id_entidad_padre = $id_entidad
                order by cod_entidad asc;";
        $datos = toba::db()->consultar($sql);
        return $datos;
    }
    
    static public function cant_niveles (){
        $sql ="SELECT pkg_pr_ENTIDADES.cant_niveles AS cant_niveles FROM DUAL;";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['cant_niveles']; 
    }
    
    static public function get_cod_ua ($id_entidad){
        $sql ="SELECT PKG_PR_ENTIDADES.COD_UA($id_entidad) AS ua FROM DUAL;";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['ua']; 
    }
    
    static public function valor_del_nivel ($id_entidad, $nivel){
        $sql ="SELECT PKG_PR_ENTIDADES.VALOR_DEL_NIVEL($id_entidad, $nivel) AS valor FROM DUAL;";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['valor']; 
    }
    
    static public function ultimo_del_nivel ($nivel){
        $sql ="SELECT PKG_PR_ENTIDADES.ULTIMO_DEL_NIVEL($nivel) AS valor FROM DUAL;";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['valor']; 
    }
    
    static public function armar_codigo_entidad ($nivel, $cod_entidad, $cod_entidad_padre){
        $sql ="SELECT  PKG_PR_ENTIDADES.ARMAR_CODIGO($nivel, $cod_entidad, $cod_entidad_padre) AS valor FROM DUAL;";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['valor']; 
    }
    static public function entidad_imputable ($id_entidad){
        $sql ="SELECT pkg_pr_entidades.imputable($id_entidad) AS imputable FROM DUAL;";
        $datos = toba::db()->consultar_fila($sql);
        return $datos['imputable']; 
    }
    static public function existe_entidad_activa ($cod_entidad){
        $sql = "SELECT NVL(MIN(1),0) as valor
                FROM PR_ENTIDADES E
                WHERE E.COD_ENTIDAD = $cod_entidad AND Pkg_Pr_Entidades.ACTIVA(E.ID_ENTIDAD) = 'S';";               
        $datos = toba::db()->consultar_fila($sql);
        return $datos['valor'];
    }
    
    static public function crear_nueva_entidad ($cod_entidad, $descripcion, $nivel, $id_entidad_padre, $cod_unidad_administracion, $eg, $er, $ac){
        try{
            $sql = "BEGIN :resultado := PKG_PR_ENTIDADES.CREAR_ENTIDAD(:cod_entidad, :descripcion, :nivel, :id_entidad_padre, :cod_unidad_administracion, :eg, :er, :ac); END;";        
            $parametros = array ( array(  'nombre' => 'cod_entidad', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 20,
                                            'valor' => $cod_entidad),
                                    array(  'nombre' => 'descripcion', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 100,
                                            'valor' => $descripcion),
                                    array(  'nombre' => 'nivel', 
                                            'tipo_dato' => PDO::PARAM_INT,
                                            'longitud' => 6,
                                            'valor' => $nivel),
                                    array(  'nombre' => 'cod_unidad_administracion', 
                                            'tipo_dato' => PDO::PARAM_INT,
                                            'longitud' => 10,
                                            'valor' => $cod_unidad_administracion),
                                    array(  'nombre' => 'id_entidad_padre', 
                                            'tipo_dato' => PDO::PARAM_INT,
                                            'longitud' => 10,
                                            'valor' => $id_entidad_padre),
                                    array(  'nombre' => 'eg', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 1,
                                            'valor' => $eg),
                                    array(  'nombre' => 'er', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 1,
                                            'valor' => $er),
                                    array(  'nombre' => 'ac', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 1,
                                            'valor' => $ac),
                                    array(  'nombre' => 'resultado', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 1000,
                                            'valor' => '')
                            );
            toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);                
            if ($resultado[8]['valor'] == 'OK'){
                toba::db()->cerrar_transaccion();
            }else{
                toba::db()->abortar_transaccion();
                toba::notificacion()->info($resultado[8]['valor']);
            }
            return $resultado[8]['valor'];
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
        }
    }   
    
    
    static public function actualizar_entidad ($id_entidad, $descripcion, $cod_unidad_administracion, $eg, $er, $ac){
        try{
            $sql = "BEGIN :resultado := PKG_PR_ENTIDADES.ACTUALIZAR_ENTIDAD(:id_entidad, :descripcion, :cod_unidad_administracion, :eg, :er, :ac); END;";       
            $parametros = array (   array(  'nombre' => 'descripcion', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 100,
                                            'valor' => $descripcion),
                                    array(  'nombre' => 'cod_unidad_administracion', 
                                            'tipo_dato' => PDO::PARAM_INT,
                                            'longitud' => 10,
                                            'valor' => $cod_unidad_administracion),
                                    array(  'nombre' => 'id_entidad', 
                                            'tipo_dato' => PDO::PARAM_INT,
                                            'longitud' => 10,
                                            'valor' => $id_entidad),
                                    array(  'nombre' => 'eg', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 1,
                                            'valor' => $eg),
                                    array(  'nombre' => 'er', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 1,
                                            'valor' => $er),
                                    array(  'nombre' => 'ac', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 1,
                                            'valor' => $ac),
                                    array(  'nombre' => 'resultado', 
                                            'tipo_dato' => PDO::PARAM_STR,
                                            'longitud' => 1000,
                                            'valor' => '')
                            );
            toba::db()->abrir_transaccion();
            $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);
            if ($resultado[6]['valor'] == 'OK'){
                toba::db()->cerrar_transaccion();
            }else{
                toba::db()->abortar_transaccion();
                toba::notificacion()->info($resultado[6]['valor']);
            }
            return $resultado[6]['valor'];
        } catch (toba_error_db $e_db) {
            toba::notificacion()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
            toba::logger()->error('Error '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
        } catch (toba_error $e) {
            toba::notificacion()->error('Error '.$e->get_mensaje());
            toba::logger()->error('Error '.$e->get_mensaje());
        }
    }   
    
    static public function eliminar ($id_entidad)
    {
        $sql = "BEGIN 
                    :resultado := PKG_PR_ENTIDADES.ELIMINAR_ENTIDAD(:id_entidad); 
                END;";          
        $parametros = [ 
                [ 'nombre' => 'resultado', 
                  'tipo_dato' => PDO::PARAM_STR,
                  'longitud' => 4000,
                  'valor' => ''],
                [ 'nombre' => 'id_entidad', 
                  'tipo_dato' => PDO::PARAM_STR,
                  'longitud' => 20,
                  'valor' => $id_entidad],
            ];
        ctr_procedimientos::ejecutar_procedimiento(null,$sql,$parametros);
    }

    static public function cargar_entidad ($id_entidad){
        $sql = "BEGIN PKG_PR_ENTIDADES.CARGAR_ENTIDAD(:id_entidad, :cod_entidad, :descripcion, :nivel, :id_entidad_padre, :cod_unidad_administracion, :eg, :er, :ac); END;";        
        $parametros = array ( 
                            array(  'nombre' => 'id_entidad', 
                                    'tipo_dato' => PDO::PARAM_INT,
                                    'longitud' => 20,
                                    'valor' => $id_entidad),
                            array(  'nombre' => 'cod_entidad', 
                                    'tipo_dato' => PDO::PARAM_STR,
                                    'longitud' => 20,
                                    'valor' => ''),
                            array(  'nombre' => 'descripcion', 
                                    'tipo_dato' => PDO::PARAM_STR,
                                    'longitud' => 100,
                                    'valor' => ''),
                            array(  'nombre' => 'nivel', 
                                    'tipo_dato' => PDO::PARAM_INT,
                                    'longitud' => 6,
                                    'valor' => ''),
                            array(  'nombre' => 'cod_unidad_administracion', 
                                    'tipo_dato' => PDO::PARAM_INT,
                                    'longitud' => 10,
                                    'valor' => ''),
                            array(  'nombre' => 'id_entidad_padre', 
                                    'tipo_dato' => PDO::PARAM_INT,
                                    'longitud' => 10,
                                    'valor' => ''),
                            array(  'nombre' => 'eg', 
                                    'tipo_dato' => PDO::PARAM_STR,
                                    'longitud' => 1,
                                    'valor' => ''),
                            array(  'nombre' => 'er', 
                                    'tipo_dato' => PDO::PARAM_STR,
                                    'longitud' => 1,
                                    'valor' => ''),
                            array(  'nombre' => 'ac', 
                                    'tipo_dato' => PDO::PARAM_STR,
                                    'longitud' => 1,
                                    'valor' => '')
                    );
        $resultado = toba::db()->ejecutar_store_procedure($sql, $parametros); 
        $datos = array();
        $datos['id_entidad'] = $resultado[0]['valor'];               
        $datos['cod_entidad'] = $resultado[1]['valor'];
        $datos['descripcion'] = $resultado[2]['valor'];
        $datos['nivel'] = $resultado[3]['valor'];
        $datos['cod_unidad_administracion'] = $resultado[4]['valor'];
        $datos['id_entidad_padre'] = $resultado[5]['valor'];
        $datos['entidad_gastos'] = $resultado[6]['valor'];
        $datos['entidad_recursos'] = $resultado[7]['valor'];
        $datos['activa'] = $resultado[8]['valor'];
        return $datos;
    }   
}
?>
