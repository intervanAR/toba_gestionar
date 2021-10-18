<?php

/**
 * Simple SQL authentication source
 *
 * This class is an example authentication source which authenticates an user
 * against a SQL database.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_intervanauth_Auth_Source_INTERVANUP extends sspmod_core_Auth_UserPassBase {


	/**
	 * The DSN we should connect to.
	 */
	private $dsn;


	/**
	 * The username we should connect to the database with.
	 */
	private $username;


	/**
	 * The password we should connect to the database with.
	 */
	private $password;


	/**
	 * The query we should use to retrieve the attributes for the user.
	 *
	 * The username and password will be available as :username and :password.
	 */
	private $query;


	/**
	 * Constructor for this authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($config)');

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);

		/* Make sure that all required parameters are present. */
		foreach (array('dsn', 'username', 'password', 'query') as $param) {
			if (!array_key_exists($param, $config)) {
				throw new Exception('Falta atributo requerido \'' . $param .
					'\' para la fuente de autenticacion ' . $this->authId);
			}

			if (!is_string($config[$param])) {
				throw new Exception('Parametro esperado \'' . $param .
					'\' para la fuente de autenticacion ' . $this->authId .
					' debe ser una cadena. En cambio, se recibio: ' .
					var_export($config[$param], TRUE));
			}
		}

		$this->dsn = $config['dsn'];
		$this->username = $config['username'];
		$this->password = $config['password'];
		$this->query = $config['query'];
	}


	/**
	 * Create a database connection.
	 *
	 * @return PDO  The database connection.
	 */
	private function connect() {
		try {
			$db = new PDO($this->dsn, $this->username, $this->password);
		} catch (PDOException $e) {
			throw new Exception('intervanauth:' . $this->authId . ': - Fallo la conexion a \'' .
				$this->dsn . '\': '. $e->getMessage());
		}

		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


		$driver = explode(':', $this->dsn, 2);
		$driver = strtolower($driver[0]);

		/* Driver specific initialization. */
		switch ($driver) {
		case 'mysql':
			/* Use UTF-8. */
			$db->exec("SET NAMES 'utf8'");
			break;
		case 'pgsql':
			/* Use UTF-8. */
			$db->exec("SET NAMES 'UTF8'");
			break;
		case 'oracle':
			/* Use UTF-8. */
			$db->exec("SET NAMES 'UTF8'");
			break;
		}

		return $db;
	}


	/**
	 * Attempt to log in using the given username and password.
	 *
	 * On a successful login, this function should return the users attributes. On failure,
	 * it should throw an exception. If the error was caused by the user entering the wrong
	 * username or password, a SimpleSAML_Error_Error('WRONGUSERPASS') should be thrown.
	 *
	 * Note that both the username and the password are UTF-8 encoded.
	 *
	 * @param string $username  The username the user wrote.
	 * @param string $password  The password the user wrote.
	 * @return array  Associative array with the users attributes.
	 */
	protected function login($username, $password) {
		assert('is_string($username)');
		assert('is_string($password)');

		try {
			$db = new PDO($this->dsn, $username, $password);
		} catch (PDOException $e) {
			SimpleSAML_Logger::error('intervanauth:' . $this->authId . ': - Fallo en la autenticacion a la base de datos \'' .
				$this->dsn . '\': '. $e->getMessage());
			throw new SimpleSAML_Error_Error('WRONGUSERPASS');
		}

		SimpleSAML_Logger::info('intervanauth:' . $this->authId . ': Autenticacion exitosa.');
		
		/* Extract attributes. We allow the resultset to consist of multiple rows. Attributes
		 * which are present in more than one row will become multivalued. NULL values and
		 * duplicate values will be skipped. All values will be converted to strings.
		 */
		$attributes = array('usuario' => array($username), 'clave_plana' => array($password));
		
		SimpleSAML_Logger::info('intervanauth:' . $this->authId . ': atributos: ' .
			implode(',', array_keys($attributes)));

		return $attributes;
	}

}

?>