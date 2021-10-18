<?php

final class snapshot_tester
{
	private static $obj;
	private static $dir;
	private static $file;

	public function __construct($dir, $obj)
	{
		$file_name = get_class($obj).'.snap';

		self::$obj = $obj;
		self::$dir = "$dir/__snapshots__";
		self::$file = "$dir/__snapshots__/$file_name";
	}

	public function match_snapshot($content)
	{
		// Obtiene el nombre de la función llamadora
		$caller = debug_backtrace()[1]['function'];

		try {
			$existia = file_exists(self::$file);

			// Si no existe la carpeta, la crea
			if (!is_dir(dirname(self::$file))) {
				mkdir(dirname(self::$file).'/', 0777, true);
			}

			// Si no existe, no se compara.
			// Se escribe y listo.
			if (!$existia) {
				$file = fopen(self::$file, 'w');

				fwrite($file, serialize([
					$caller => $content,
				]));
				fclose($file);

				return;
			}
			$file = fopen(self::$file, 'r');
			$file_content = unserialize(fread($file, filesize(self::$file)));

			fclose($file);

			if (!isset($file_content[$caller])) {
				$file = fopen(self::$file, 'w');
				$file_content[$caller] = $content;

				fwrite($file, serialize($file_content));
				fclose($file);

				return;
			}
			$result = $file_content[$caller] === $content;

			if (!$result) {
				print_r("\n==================================\n");
				print_r("=========== NOT EQUAL ============\n");
				print_r("==================================\n");
				print_r($file_content[$caller]);
				print_r("\n==================================\n");
				print_r($content);
				print_r("\n==================================\n");
				print_r("== Para actualizar el snapshot ===\n");
				print_r("== debe borrar el archivo que ====\n");
				print_r("== tiene el nombre del test y ====\n");
				print_r("== reejecutar la suite de tests ==\n");
				print_r("==================================\n\n");
			}

			self::$obj->assertTrue($result);
		} catch (Exception $e) {
			throw $e;
		}
	}
}
