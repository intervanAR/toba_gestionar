<?php
$toba_dir= getenv('TOBA_DIR');
$proyecto= $_GET['proyecto'];
$reporte= $_GET['reporte'];
$parametros= $_GET['parametros'];
$parametros= str_replace("'", "\"", $parametros);

$parametros= json_decode($parametros, true);

include_once($toba_dir."/php/nucleo/toba.php");
include_once($toba_dir."/php/nucleo/toba_nucleo.php");
include_once($toba_dir."/php/nucleo/lib/toba_db.php");
include_once($toba_dir."/php/nucleo/lib/toba_memoria.php");
include_once($toba_dir."/php/nucleo/lib/toba_error.php");
include_once($toba_dir."/php/nucleo/lib/toba_dba.php");
include_once($toba_dir."/php/lib/toba_varios.php");
include_once($toba_dir."/proyectos/principal/php/modelo/generico/dao_general.php");

if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
	if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
		header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

	if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
	   	header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
}

toba_dba::cargar_bases_definidas();
$bases_ini = toba_dba::get_bases_definidas();

$base= $bases_ini["desarrollo $proyecto $proyecto"]['base'];
$usuario= $bases_ini["desarrollo $proyecto $proyecto"]['usuario'];
$clave= $bases_ini["desarrollo $proyecto $proyecto"]['clave'];
$profile= $bases_ini["desarrollo $proyecto $proyecto"]['profile'];
$puerto= $bases_ini["desarrollo $proyecto $proyecto"]['puerto'];

$dsn= 'oci:dbname=//'.$profile.':'.$puerto.'/'.$base.';charset=WE8ISO8859P1';

$conexion = new PDO($dsn, $usuario, $clave, null);
//$conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conexion->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

define('fetch_asoc', PDO::FETCH_ASSOC);
$tipo_fetch=fetch_asoc;

$sql= "select *
from GE_CONSULTAS
where reporte='$reporte'";

$statement = $conexion->query($sql);
$consulta= $statement->fetchAll($tipo_fetch);

$sql= $datos['query']= stream_get_contents($consulta[0]['query'], -1, -1);

$parametros_query= dao_general::escanear_parametros($sql);

$aux= array();
for ($i=0; $i < count($parametros_query); $i++) {
	$x= $i + 1;
	if (isset($parametros["param_$x"])) {
		array_push($aux, array('parametro' => $parametros_query[$i], 'valor' => $parametros["param_$x"]));
	}elseif (isset($parametros["param_multiple_$x"])) {
		$valores= str_replace(",", "','", ($parametros["param_multiple_$x"]));
		$valores= "'".$valores."'";
		array_push($aux, array('parametro' => $parametros_query[$i], 'valor' => $valores));
	}elseif (isset($parametros["param_lista_$x"])) {
		array_push($aux, array('parametro' => $parametros_query[$i], 'valor' => $parametros["param_lista_$x"]));
	}
}
//print_r($aux);
$query= strtoupper(dao_general::reemplazar_parametros($sql, $aux));

//////////////////////////////////////////////////

$campos= substr($query, strpos($query, "SELECT")+ 7, strpos($query, "FROM") - strpos($query, "SELECT") - 8);
//$campos= str_replace(" ", "", $campos);

$ini= dao_general::strpos_r($campos, "{");
$fin= dao_general::strpos_r($campos, "}");
$aux= explode(",", $campos);

for ($i=0; $i < count($ini); $i++) {
	$campos= substr($campos, 0, $ini[$i]-1);
	$campo= trim(substr($campos, strripos($campos, "\"")+1));

	if (!isset($prompts))
		$prompts= $campo;
	else
		$prompts=  "$campo,".$prompts;
}

//$sql2= "SELECT $prompts ".substr($query, strpos($query, "FROM"));

$handle= fopen('datos2.csv', 'w');
fwrite($handle, $prompts. "\r\n");
$delimitador= ",";
try {
	$stmt = $conexion->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$stmt->execute();
	$linea;
	$titulos;
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT)) {
	  unset($linea);

	  foreach ($row as $key => $value) {
	  	if (!isset($linea)||empty($linea)) {
	  		$linea= $row[$key];
	  	}else{
	  		$linea= $linea.$delimitador.str_replace(",",".",$row[$key]);
	  	}
	  }
	  fwrite($handle, $linea. "\r\n");
	}
	$stmt = null;
} catch (PDOException $e) {
	toba::logger()->error($e->getMessage());
	$toba::db()->cortar_sql($sql);
}

fclose($handle);



$html_code= $consulta[0]['html'];
/*$html_code= str_replace("<", "&lt;", $consulta['html']);
$html_code= str_replace(">", "&gt;", $html_code);*/
/*$html_code= str_replace('\"', '"', $html_code);
$html_code= str_replace('$reporte', $reporte, $html_code);
$html_code= str_replace('$parametros', $parametros, $html_code);*/
//<link href='https://use.fontawesome.com/releases/v5.0.4/css/all.css' rel='stylesheet'>
echo "
	<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
	<html xmlns='http://www.w3.org/1999/xhtml'>
	<head>
		<title>$reporte</title>
		<link href='css/toba.css' rel='stylesheet' type='text/css' />
		<link href='css/jquery-ui.min.css' rel='stylesheet' type='text/css' />
		<link href='css/pivot.min.css' rel='stylesheet' type='text/css' />
		<link href='css/common.css' rel='stylesheet' type='text/css' />
		<link href='css/style1.css' rel='stylesheet' type='text/css' />
		<link href='css/demo.css' rel='stylesheet' type='text/css' />
		<link href='https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' rel='stylesheet' integrity='sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN' crossorigin='anonymous'>
	</head>
	<body>
		<!-- 2. Include js files -->
		<script type='text/javascript' src='js/jquery-3.3.1.min.js'></script>
		<script type='text/javascript' src='js/jquery-ui.min.js'></script>
		<script type='text/javascript' src='js/papaparse.min.js'></script>
		<script type='text/javascript' src='js/pivot.min.js'></script>
		<script type='text/javascript' src='js/plotly.min.js'></script>
		<script type='text/javascript' src='js/plotly_renderers.js'></script>
		<script type='text/javascript' src='js/jquery.dropdown.js'></script>
		<script type='text/javascript' src='js/modernizr.custom.63321.js'></script>
		<script type='text/javascript' src='js/tableExport.js'></script>
		<script type='text/javascript' src='js/jquery.base64.js'></script>
		<script type='text/javascript' src='js/jspdf/libs/sprintf.js'></script>
		<script type='text/javascript' src='js/jspdf/jspdf.js'></script>
		<script type='text/javascript' src='js/jspdf/libs/base64.js'></script>

		<div class='modalDiv'>
			<h2 class='elegantshadow'>$reporte</h2>
		</div>
		<div class='modalDivExp'>
			<button class='btn-danger' onclick=exportar()>Exportar</button>
			<div class='dropdown'>
				<section>
	                    <select id='cd-dropdown' name='cd-dropdown' class='cd-select'>
	                        <option value='-1' selected>Elija una opcion para exportar</option>
	                        <option value='1' class='fa fa-file-pdf-o dropdown-list' aria-hidden='true'>PDF</option>
	                        <option value='2' class='fa fa-file-excel-o dropdown-list' aria-hidden='true'>EXCEL</option>
	                        <option value='3' class='fa fa-file-text-o dropdown-list' aria-hidden='true'>CSV</option>
	                        <option value='4' class='fa fa-file-code-o dropdown-list' aria-hidden='true'>JSON</option>
	                    </select>
	            </section>
	        </div>
	    </div>
	    <script type='text/javascript'>

			$( function() {

				$( '#cd-dropdown' ).dropdown( {
					gutter : 5
				} );

			});

		</script>

		<script type='text/javascript'>
			function exportar(){
				var aux= $('input[name=cd-dropdown]').val();
				if (aux == 1){
					$(\".pvtUi\").tableExport({type:'pdf',escape:'false'});
				}else if(aux == 2){
					$(\".pvtUi\").tableExport({type:'excel',escape:'false',htmlContent:'true'});
				}else if(aux == 3){
					$(\".pvtUi\").tableExport({type:'csv',escape:'false'});
				}else if(aux == 4){
					$(\".pvtUi\").tableExport({type:'json',escape:'false'});
				}
			}
		</script>
		<div class='modalDiv'>
			<div id='output'>
			</div>
		</div>
		$html_code
	</body>
	</html>
";

//<link href='css/icons.css' rel='stylesheet' type='text/css' />
?>
