<?php
/**
 * @author fbohn
 * @author lgraziani
 * @version 1.1.0
 */
class ctr_procedimientos
{
	/**
	 * @deprecated En favor de self::ejecutar_procedimiento
	 */
	public static function ejecutar_procedure_mensajes(
		$sql,
		$parametros,
		$mensaje_exito = '',
		$mensaje_error = '',
		$con_transaccion = true,
		$cod_exito = 'OK',
		$fuente = null
	) {
		toba::logger()->warning('[DEPRECATED ctr_procedimientos::ejecutar_procedure_mensajes] Migrar a ctr_procedimientos::ejecutar_procedimiento');
		if (
			!isset($sql) || !isset($parametros)
			|| empty($sql) || empty($parametros)
		) {
			return;
		}
		try {
			if ($con_transaccion) {
				toba::db($fuente)->abrir_transaccion();
			}
			$resultado = toba::db($fuente)->ejecutar_store_procedure(
				$sql,
				$parametros
			);
			$valor_resultado = $resultado[count($resultado) - 1]['valor'];

			if ($valor_resultado != $cod_exito) {
				toba::notificacion()->error(self::procesar_error($valor_resultado));
				toba::logger()->error($valor_resultado);
				if ($con_transaccion) {
					toba::db($fuente)->abortar_transaccion();
				}
			} else {
				if (isset($mensaje_exito) && !empty($mensaje_exito)) {
					toba::notificacion()->info(self::procesar_error($mensaje_exito));
				}
				if ($con_transaccion) {
					toba::db($fuente)->cerrar_transaccion();
				}
			}

			return $resultado;
		} catch (toba_error_db $e_db) {
			toba::notificacion()->error(self::procesar_error($mensaje_error.' '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate()));
			toba::logger()->error(self::procesar_error($mensaje_error.' '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate()));
			if ($con_transaccion) {
				toba::db($fuente)->abortar_transaccion();
			} else {
				throw $e_db;
			}

			return [];
		} catch (toba_error $e) {
			toba::notificacion()->error(self::procesar_error($mensaje_error.' '.$e->get_mensaje()));
			toba::logger()->error(self::procesar_error($mensaje_error.' '.$e->get_mensaje()));
			if ($con_transaccion) {
				toba::db($fuente)->abortar_transaccion();
			} else {
				throw $e;
			}

			return [];
		} catch (PDOException $e_pdo) {
			toba::notificacion()->error(self::procesar_error($mensaje_error.' '.$e_pdo->getMessage()));
			toba::logger()->error(self::procesar_error($mensaje_error.' '.$e_pdo->getMessage()));
			if ($con_transaccion) {
				toba::db($fuente)->abortar_transaccion();
			} else {
				throw new toba_error(self::procesar_error($e_pdo->getMessage()));
			}

			return [];
		}
	}

	/**
	 * @deprecated En favor de self::ejecutar_procedimiento
	 */
	public static function ejecutar_functions_mensajes(
		$sql,
		$parametros,
		$mensaje_exito = '',
		$mensaje_error = '',
		$con_transaccion = true
	) {
		toba::logger()->warning('[DEPRECATED ctr_procedimientos::ejecutar_functions_mensajes] Migrar a ctr_procedimientos::ejecutar_procedimiento');

		if (
			!isset($sql) || !isset($parametros)
			|| empty($sql) || empty($parametros)
		) {
			return;
		}

		try {
			if ($con_transaccion) {
				toba::db()->abrir_transaccion();
			}
			$resultado = toba::db()->ejecutar_store_procedure($sql, $parametros);

			if (isset($mensaje_exito) && !empty($mensaje_exito)) {
				toba::notificacion()->info(self::procesar_error($mensaje_exito));
			}
			if ($con_transaccion) {
				toba::db()->cerrar_transaccion();
			}

			return $resultado;
		} catch (toba_error_db $e_db) {
			toba::notificacion()->error(self::procesar_error($mensaje_error.' '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate()));
			toba::logger()->error($mensaje_error.' '.$e_db->get_mensaje().' '.$e_db->get_mensaje_motor().' '.$e_db->get_sql_ejecutado().' '.$e_db->get_sqlstate());
			if ($con_transaccion) {
				toba::db()->abortar_transaccion();
			}

			return [];
		} catch (toba_error $e) {
			toba::notificacion()->error(self::procesar_error($mensaje_error.' '.$e->get_mensaje()));
			toba::logger()->error($mensaje_error.' '.$e->get_mensaje());
			if ($con_transaccion) {
				toba::db()->abortar_transaccion();
			}

			return [];
		} catch (PDOException $e_pdo) {
			toba::notificacion()->error(self::procesar_error($mensaje_error.' '.$e_pdo->getMessage()));
			toba::logger()->error($mensaje_error.' '.$e_pdo->getMessage());
			if ($con_transaccion) {
				toba::db()->abortar_transaccion();
			}

			return [];
		}
	}

	/**
	 * Ejecuta una operación enmarcada dentro de una transacción.
	 * En caso de éxito, no devuelve nada. En caso de un fallo, corta,
	 * loguea el error y lo tira al cliente.
	 *
	 * @param string $mensaje_error Mensaje en caso de que la consulta falle.
	 * @param string $sql Consulta a realizar.
	 * @return integer Total de filas afectadas.
	 */
	public static function ejecutar_transaccion_simple(
		$mensaje_error,
		$sql,
		$con_transaccion = 'deprecated'
	) {
		if ($con_transaccion !== 'deprecated') {
			toba::logger()->warning(
				"[DEPRECATED] [ctr_procedimientos::ejecutar_procedimiento] El parámetro nº 3 no se usa más. Por favor, borrar."
			);
		}

		$total_afectados;

		self::procesar_transaccion(
			$mensaje_error,
			function () use ($sql, &$total_afectados) {
				$total_afectados = toba::db()->ejecutar($sql);
			}
		);

		return $total_afectados;
	}

	/**
	 * Elimina los saltos de líneas, las tabulaciones y los
	 * espacios de más del inicio y final del string.
	 *
	 * De esta forma se puede desarrollar cómodamente una consulta
	 * a un procedimiento sin temor a fallos por estos caracteres.
	 *
	 * @param string $sql La consulta en bruto.
	 * @return string La consulta sanitizada.
	 */
	public static function sanitizar_consulta($sql)
	{
		// 1. Elimino los saltos de línea.
		$sql = trim(preg_replace('/\s+/', ' ', $sql));

		// 2. Elimino las tabulaciones.
		$sql = str_replace("\t", ' ', $sql);

		// 3. Elimino los espacios antes y después del string.
		$sql = trim($sql);

		return $sql;
	}

	/**
	 * Ejecuta una transacción donde se realiza más de una operación.
	 * En caso de éxito, no devuelve nada. En caso de un fallo, corta,
	 * loguea el error y lo tira al cliente.
	 *
	 * @param string $mensaje_error mensaje en caso de que
	 *                              la consulta falle
	 * @param Closure $clojure      función que se ejecuta
	 *                              dentro de una transacción.
	 */
	public static function ejecutar_transaccion_compuesta(
		$mensaje_error,
		Closure $clojure
	) {
		self::procesar_transaccion($mensaje_error, $clojure);
	}

	/**
	 * Procesa un conjunto de instrucciones PL/SQL dentro de una (posible)
	 * transacción.
	 * En caso de éxito, no devuelve nada.
	 * En caso de un fallo: corta, loguea el error y lo devuelve al cliente.
	 *
	 * @param string $mensaje_error mensaje en caso de que la consulta falle
	 * @param string $sql consulta a realizar
	 * @param array  [$parametros = []] Optional.
	 *  Listado de parametros del procedimiento. Por convención, el parámetro
	 *  de respuesta debe ser el primero.
	 * @param function $verificador
	 *
	 * @return array
	 */
	public static function ejecutar_procedimiento(
		$mensaje_error,
		$sql,
		$parametros = [],
		$verificador = null
	) {
		if (is_bool($verificador)) {
			toba::logger()->warning("[DEPRECATED] [ctr_procedimientos::ejecutar_procedimiento] El parámetro nº 4 no se usa más. Por favor, borrar.");

			$verificador = null;
		}
		if (is_null($verificador)) {
			$verificador = function($resultado) {
				if ($resultado[0]['valor'] !== 'OK') {
					throw new toba_error(self::procesar_error($resultado[0]['valor']));
				}
			};
		}
		$sql = self::sanitizar_consulta($sql);
		$resultado;

		self::procesar_transaccion(
			$mensaje_error,
			function () use ($sql, $parametros, $verificador, &$resultado) {
				$resultado = toba::db()->ejecutar_store_procedure(
					$sql,
					$parametros
				);

				if (!empty($parametros)) {
					$verificador($resultado);
				}
			}
		);

		return $resultado;
	}

	/**
	 * Método que se encarga de procesar la transacción, atrapar
	 * los errores y procesarlos como se debe.
	 *
	 * @param string $mensaje_error mensaje en caso de que la consulta falle
	 * @param Closure $clojure función que se ejecuta dentro de una transacción.
	 * @return void
	 */
	private static function procesar_transaccion(
		$mensaje_error,
		Closure $clojure
	) {
		$con_transaccion = !toba::db()->transaccion_abierta();

		try {
			if ($con_transaccion) {
				toba::db()->abrir_transaccion();
			}

			$clojure();

			if ($con_transaccion) {
				toba::db()->cerrar_transaccion();
			}
		} catch (toba_error_db $e) {
			$error = is_null($mensaje_error)
				? $e->get_mensaje()
				: "$mensaje_error: {$e->get_mensaje()}";

			toba::logger()->error($e);

			if ($con_transaccion) {
				toba::db()->abortar_transaccion();
			}

			throw new toba_error(self::procesar_error($error));
		} catch (Exception $e) {
			$error = is_null($mensaje_error)
				? $e->getMessage()
				: "$mensaje_error: {$e->getMessage()}";

			toba::logger()->error($e);

			if ($con_transaccion) {
				toba::db()->abortar_transaccion();
			}

			throw new toba_error(self::procesar_error($error));
		}
	}

	/**
	 * Recibe el mensaje de error que arma toba para luego mostrar en la modal y
	 * Separa los errores de usuario de los del kernel
	 *
	 * @param string Mensaje crudo.
	 * @return string Mensaje formateado.
	 */
	public static function procesar_error($mensaje)
	{
		$iniDelimiter = "«";
		$finDelimiter = "»";

		if (!strpos($mensaje, $finDelimiter)){
			//Si el mensaje no tiene mensaje de usuario retorna como esta
			return $mensaje;
		}

		$usuario = "";
		$debug = "";
		$part1 = explode($iniDelimiter,$mensaje);

		$i = 0;
		foreach ($part1 as $p){
			if (strpos($p, $finDelimiter)){
				$aux = explode($finDelimiter, $p);
				//substr($aux[0], 7,strlen());
				$usuario .= substr($aux[0], 7,strlen($aux[0]));
				//$usuario .= $aux[0];
				$debug .= $aux[0];
				$debug .= $aux[1];
			}else{
				$debug .= $p;
			}
			$i++;
		}

		$debug = "<div class='mensaje_debug'>
					<a class='link_debug' href='#' onclick=".'$("#debug").toggle()'.">Ver detalle</a>
					<div id='debug' hidden>
						$debug
					</div>
				</div>
				";
		return $usuario.$debug;
	}

	
}
