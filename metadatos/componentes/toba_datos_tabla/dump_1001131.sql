------------------------------------------------------------
--[1001131]--  DT - AUDITORIA_2 
------------------------------------------------------------

------------------------------------------------------------
-- apex_objeto
------------------------------------------------------------

--- INICIO Grupo de desarrollo 1
INSERT INTO apex_objeto (proyecto, objeto, anterior, identificador, reflexivo, clase_proyecto, clase, punto_montaje, subclase, subclase_archivo, objeto_categoria_proyecto, objeto_categoria, nombre, titulo, colapsable, descripcion, fuente_datos_proyecto, fuente_datos, solicitud_registrar, solicitud_obj_obs_tipo, solicitud_obj_observacion, parametro_a, parametro_b, parametro_c, parametro_d, parametro_e, parametro_f, usuario, creacion, posicion_botonera) VALUES (
	'principal', --proyecto
	'1001131', --objeto
	NULL, --anterior
	NULL, --identificador
	NULL, --reflexivo
	'toba', --clase_proyecto
	'toba_datos_tabla', --clase
	'1000002', --punto_montaje
	NULL, --subclase
	NULL, --subclase_archivo
	NULL, --objeto_categoria_proyecto
	NULL, --objeto_categoria
	'DT - AUDITORIA_2', --nombre
	NULL, --titulo
	NULL, --colapsable
	NULL, --descripcion
	'principal', --fuente_datos_proyecto
	'principal', --fuente_datos
	NULL, --solicitud_registrar
	NULL, --solicitud_obj_obs_tipo
	NULL, --solicitud_obj_observacion
	NULL, --parametro_a
	NULL, --parametro_b
	NULL, --parametro_c
	NULL, --parametro_d
	NULL, --parametro_e
	NULL, --parametro_f
	NULL, --usuario
	'2017-07-18 05:21:45', --creacion
	NULL  --posicion_botonera
);
--- FIN Grupo de desarrollo 1

------------------------------------------------------------
-- apex_objeto_db_registros
------------------------------------------------------------
INSERT INTO apex_objeto_db_registros (objeto_proyecto, objeto, max_registros, min_registros, punto_montaje, ap, ap_clase, ap_archivo, tabla, tabla_ext, alias, modificar_claves, fuente_datos_proyecto, fuente_datos, permite_actualizacion_automatica, esquema, esquema_ext) VALUES (
	'principal', --objeto_proyecto
	'1001131', --objeto
	NULL, --max_registros
	NULL, --min_registros
	'1000002', --punto_montaje
	'1', --ap
	NULL, --ap_clase
	NULL, --ap_archivo
	'AUDITORIA_2', --tabla
	NULL, --tabla_ext
	NULL, --alias
	'0', --modificar_claves
	'principal', --fuente_datos_proyecto
	'principal', --fuente_datos
	'1', --permite_actualizacion_automatica
	NULL, --esquema
	'public'  --esquema_ext
);

------------------------------------------------------------
-- apex_objeto_db_registros_col
------------------------------------------------------------

--- INICIO Grupo de desarrollo 1
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'principal', --objeto_proyecto
	'1001131', --objeto
	'1001140', --col_id
	'usuario', --columna
	'C', --tipo
	'1', --pk
	NULL, --secuencia
	'100', --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	'0', --externa
	'AUDITORIA_2'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'principal', --objeto_proyecto
	'1001131', --objeto
	'1001141', --col_id
	'fecha', --columna
	'F', --tipo
	'1', --pk
	NULL, --secuencia
	NULL, --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	'0', --externa
	'AUDITORIA_2'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'principal', --objeto_proyecto
	'1001131', --objeto
	'1001142', --col_id
	'tabla', --columna
	'C', --tipo
	'1', --pk
	NULL, --secuencia
	'100', --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	'0', --externa
	'AUDITORIA_2'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'principal', --objeto_proyecto
	'1001131', --objeto
	'1001143', --col_id
	'campo', --columna
	'C', --tipo
	'0', --pk
	NULL, --secuencia
	'100', --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	'0', --externa
	'AUDITORIA_2'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'principal', --objeto_proyecto
	'1001131', --objeto
	'1001144', --col_id
	'valor', --columna
	'C', --tipo
	'0', --pk
	NULL, --secuencia
	'4000', --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	'0', --externa
	'AUDITORIA_2'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'principal', --objeto_proyecto
	'1001131', --objeto
	'1001145', --col_id
	'operacion', --columna
	'C', --tipo
	'0', --pk
	NULL, --secuencia
	'8', --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	'0', --externa
	'AUDITORIA_2'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'principal', --objeto_proyecto
	'1001131', --objeto
	'1001146', --col_id
	'pk', --columna
	'C', --tipo
	'0', --pk
	NULL, --secuencia
	'500', --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	'0', --externa
	'AUDITORIA_2'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'principal', --objeto_proyecto
	'1001131', --objeto
	'1001147', --col_id
	'clave_des', --columna
	'C', --tipo
	'0', --pk
	NULL, --secuencia
	'200', --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	'0', --externa
	'AUDITORIA_2'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'principal', --objeto_proyecto
	'1001131', --objeto
	'1001148', --col_id
	'campos_auditados', --columna
	'C', --tipo
	'0', --pk
	NULL, --secuencia
	'4000', --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	'0', --externa
	'AUDITORIA_2'  --tabla
);
--- FIN Grupo de desarrollo 1
