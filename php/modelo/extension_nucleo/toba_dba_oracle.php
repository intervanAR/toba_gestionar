<?php

class toba_dba_oracle //extends toba_dba
{
	private static $dba;						// Implementacion del singleton.
	private static $info_bases;					// Parametros de las conexiones ABIERTAS
	private static $bases_definidas = null;		// Bases declaradas en BASES.INI
	private static $alias_de_base = array();
	private $bases_conectadas = array();		// Conexiones abiertas

	private function __construct()
	{
		self::cargar_bases_definidas();		
	}
	
	static function get_path_archivo_bases()
	{
		return toba::nucleo()->toba_instalacion_dir().'/bases.ini';
	}
	
	/**
	*	Levanta la lista de bases definidas
	*/
	static function cargar_bases_definidas()
	{
		$bases_definidas = array();
		self::$bases_definidas = parse_ini_file( self::get_path_archivo_bases(), true );
		$pendientes = array();
		foreach (self::$bases_definidas as $id_base => $parametros) {
			if (empty($parametros)) {
				//Meterlos en una cola de bases que toman su definicion de la siguiente
				$pendientes[] = $id_base;
			} else {
				//Llenar la cola de pendientes con alias hacia la def. actual
				foreach ($pendientes as $id_base_pendiente) {
					self::$bases_definidas[$id_base_pendiente] = $parametros;
					self::$alias_de_base[$id_base_pendiente] = $id_base;
				}
				$pendientes = array();
			}
		}
	}

	/**
	*	Busca la definicion de una base en 'bases.ini'
	*/
	static function get_parametros_base( $id_base )
	{
		if ( ! isset( self::$bases_definidas ) ) {
			self::cargar_bases_definidas();
		}
		if ( isset( self::$bases_definidas[ $id_base ] ) ) {
			return self::$bases_definidas[ $id_base ];
		} else {
			throw new toba_error("DBA: La BASE [$id_base] no esta definida en el archivo de definicion de BASES: '" . self::get_path_archivo_bases() . "'" );
		}
	}
	
	/**
	*	Cambia la definicion de una base durante este pedido de p�gina
	*/
	static function set_parametros_base($id_base, $parametros)
	{
		self::$bases_definidas[$id_base] = $parametros;
	}	
	
	/**
	 * Retorna un arreglo de configuraciones de bases definidas en bases.ini
	 * @return array()
	 */
	static function get_bases_definidas()
	{
		return self::$bases_definidas;
	}
	
	//------------------------------------------------------------------------
	// Administracion de conexiones
	//------------------------------------------------------------------------

	/**
	*	Retorna una referencia a una CONEXION con una base
	*	@param string $nombre Por defecto toma la constante fuente_datos_defecto o la misma base de toba
	*	@return db
	*/
	static function get_db( $nombre, $reusar=true )
	{
		return self::get_instancia()->get_conexion( $nombre, $reusar );
	}
	
	static function get_db_de_fuente($instancia, $proyecto, $fuente, $reusar=true )
	{
		$nombre = $instancia.' '.$proyecto.' '.$fuente;
		return self::get_instancia()->get_conexion( $nombre, $reusar );
	}	
	
	/**
	* Hay una conexi�n abierta a la base?
	*/
	static function existe_conexion( $nombre )
	{
		return self::get_instancia()->existe_conexion_privado( $nombre );	
	}

	/**
	*	Fuerza la recarga de los parametros de una conexion y reconecta a la base
	*/	
	static function refrescar( $nombre )
	{
		$dba = self::get_instancia();
		$dba->desconectar_db( $nombre );
		return self::get_db( $nombre );
	}

	/**
	*	Desconecta una DB
	*/	
	static function desconectar( $nombre )
	{
		$dba = self::get_instancia();
		$dba->desconectar_db( $nombre );
	}

	//------------------------------------------------------------------------
	// Servicios internos
	//------------------------------------------------------------------------

	private function get_alias_base($nombre)
	{
		if (isset(self::$alias_de_base[$nombre])) {
			return self::$alias_de_base[$nombre];
		} else {
			return $nombre;	
		}
	}
	
	/**
	*	Administracion interna de CONEXIONES.
	*/
	private function get_conexion( $nombre, $reusar = true )
	{
		$nombre = $this->get_alias_base($nombre);
		if ($reusar) {
			if( ! isset( $this->bases_conectadas[$nombre] ) ) {
				$this->bases_conectadas[$nombre] = self::conectar_db($nombre);
			}
			return $this->bases_conectadas[$nombre];
		} else {
			return self::conectar_db($nombre);
		}
	}
	
	/**
	*	Creacion de conexiones
	*/
	private static function conectar_db($id_base)
	{
		$parametros = self::get_parametros_base( $id_base );
		//Controlo que esten todos los parametros
		if( !( isset($parametros['motor']) && isset($parametros['profile']) 
				&& isset($parametros['usuario']) && isset($parametros['clave'])
				&& isset($parametros['base']) ) ) {
			throw new toba_error("DBA: La BASE '$id_base' no esta definida correctamente." );
		}
		$puerto = isset($parametros['puerto']) ? $parametros['puerto'] : '';
		$server = isset($parametros['server']) ? $parametros['server'] : '';
		$archivo = "lib/db/toba_db_" . $parametros['motor'] . ".php";
		$clase = "toba_db_" . $parametros['motor'];
		list($usuario, $clave) = self::get_usuario_db($id_base, $parametros);
		
		$objeto_db = new $clase(	$parametros['profile'],
									$usuario,
									$clave,
									$parametros['base'],
									$puerto,
									$server );
        /* TODO: DESCOMENTAR EN TOBA 3
		$logger = toba_logger::instancia();
        $objeto_db->set_logger($logger);*/
		$objeto_db->conectar();
		//Si existe el parametro del schema, ponerlo por defecto para la conexi�n
		if (isset($parametros['schema']) && $parametros['schema'] != '') {
			$objeto_db->set_schema($parametros['schema']);
		}		
		//Si existe el parametro del encoding, ponerlo por defecto para la conexi�n
		if (isset($parametros['encoding']) && $parametros['encoding'] != '') {
			$objeto_db->set_encoding($parametros['encoding']);
		}			
		//Si existe el parametro del rol, ponerlo por defecto para la conexi�n
		if (isset($parametros['rol']) && $parametros['rol'] != '') {
			$objeto_db->set_rol($parametros['rol']);
		}
        // SETEAR USUARIO EN CONTEXTO
        $usuario_sesion = toba::usuario()->get_id();
        $objeto_db->set_usuario($usuario_sesion);

        //$sol=toba_nucleo::instancia()->get_solicitud();
        $sol=toba::solicitud();
        if (isset($sol)) {
          $oper=toba::solicitud()->get_datos_item('item_nombre');
          $objeto_db->set_formulario($oper);
        }
                       
		return $objeto_db;
	}

	/**
	*	Fuerza a reconectar en el proximo pedido de bases
	*/
	private function desconectar_db($nombre)
	{
		$nombre = $this->get_alias_base($nombre);
		if ( isset( self::$info_bases[$nombre] ) ) {
			unset( self::$info_bases[$nombre] );
		}
		if ( isset( $this->bases_conectadas[$nombre] ) ) {
			$this->bases_conectadas[$nombre]->destruir();
			unset( $this->bases_conectadas[$nombre] );
		}
	}		

	private function existe_conexion_privado( $nombre )
	{
		$nombre = $this->get_alias_base($nombre);
		return isset($this->bases_conectadas[$nombre]);
	}

	/**
	*	Devuelve una referencia a la instancia
	*/
	private static function get_instancia()
	{
	   if (!isset(self::$dba)){
		   $c =	__CLASS__;
		   self::$dba = new $c;
	   }
	   return self::$dba;
	}
	
	
	/********************************************************************************************************
	*********************************************************************************************************
	METODO PERSONALIZADO PARA CONECTAR A LA BASE DE DATOS ORACLE CON EL USUARIO LOGUEADO, 
	EL RESTO DE LOS METODOS SON DE TOBA_DBA
	*********************************************************************************************************
	********************************************************************************************************
        SE COMENTA ESA FUNCIONALIDAD, SOLO USUARIO DEL POOL
        ********************************************************************************************************/
	
	static function get_usuario_db($id_base, $parametros) 
	{
		$usuario = $parametros['usuario'];
		$clave = $parametros['clave'];		
		if (isset($parametros['conexiones_perfiles'])) {
			//Trata de sacarlo del archivo .ini asociado
			$perfiles = toba::manejador_sesiones()->get_perfiles_funcionales_activos();
			if (empty($perfiles)) {
				$seccion = 'no_autenticado';
			} else {
				$seccion = implode(", ", $perfiles);
			}
			$archivo = toba::nucleo()->toba_instalacion_dir().'/'.$parametros['conexiones_perfiles'];
			if (! file_exists($archivo) || is_dir($archivo)) {
				throw new toba_error_def("La base '$id_base' posee una referencia a un archivo de conexiones de perfiles inexistente: '$archivo'");
			}
			$usuarios = parse_ini_file($archivo, true );	
			if (isset($usuarios[$seccion]))	{
				if (! isset($usuarios[$seccion]['usuario'])) {
					throw new toba_error_def("La definici�n '$seccion' del archivo '$archivo' no posee el valor 'usuario'");
				}
				if (! isset($usuarios[$seccion]['clave'])) {
					throw new toba_error_def("La definici�n '$seccion' del archivo '$archivo' no posee el valor 'clave'");					
				}				
				return array($usuarios[$seccion]['usuario'], $usuarios[$seccion]['clave']);
			}
		}
		return array($usuario, $clave);
	}
	
	static function desconectar_db_de_fuente($instancia, $proyecto, $fuente)
	{
		$nombre = $instancia.' '.$proyecto.' '.$fuente;
		self::get_instancia()->desconectar_db( $nombre );
	}
}
?>
