<?php
class tp_intervan_logon extends toba_tp_logon
{
	function post_contenido()
	{
		echo "</div>";		
		echo "<div class='login-pie'>";
		echo "<div>Desarrollado por <strong><a href='http://www.intervan.com.ar' style='text-decoration: none' target='_blank'>Intervan</a></strong></div>
			<div>&copy; 1999-".date('Y')."</div>";
		echo "</div>";
	}
}
?>
