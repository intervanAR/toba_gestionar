
------------------------------------------------------------
-- apex_pagina_tipo
------------------------------------------------------------
INSERT INTO apex_pagina_tipo (proyecto, pagina_tipo, descripcion, clase_nombre, clase_archivo, include_arriba, include_abajo, exclusivo_toba, contexto, punto_montaje) VALUES (
	'principal', --proyecto
	'tp_intervan_logon', --pagina_tipo
	'Tipo de pagina logon para Intervan', --descripcion
	'tp_intervan_logon', --clase_nombre
	'interfaz/tipos_paginas/tp_intervan_logon.php', --clase_archivo
	NULL, --include_arriba
	NULL, --include_abajo
	NULL, --exclusivo_toba
	NULL, --contexto
	'1000002'  --punto_montaje
);
INSERT INTO apex_pagina_tipo (proyecto, pagina_tipo, descripcion, clase_nombre, clase_archivo, include_arriba, include_abajo, exclusivo_toba, contexto, punto_montaje) VALUES (
	'principal', --proyecto
	'tp_intervan_normal', --pagina_tipo
	'Tipo de pagina normal para Intervan', --descripcion
	'tp_intervan_normal', --clase_nombre
	'interfaz/tipos_paginas/tp_intervan_normal.php', --clase_archivo
	NULL, --include_arriba
	NULL, --include_abajo
	NULL, --exclusivo_toba
	NULL, --contexto
	'1000002'  --punto_montaje
);
INSERT INTO apex_pagina_tipo (proyecto, pagina_tipo, descripcion, clase_nombre, clase_archivo, include_arriba, include_abajo, exclusivo_toba, contexto, punto_montaje) VALUES (
	'principal', --proyecto
	'tp_intervan_popup', --pagina_tipo
	'Tipo de pagina popup para Intervan', --descripcion
	'tp_intervan_popup', --clase_nombre
	'interfaz/tipos_paginas/tp_intervan_popup.php', --clase_archivo
	NULL, --include_arriba
	NULL, --include_abajo
	NULL, --exclusivo_toba
	NULL, --contexto
	'1000002'  --punto_montaje
);
