<?php
       //$www = toba::proyecto()->get_www();
       //$url = $www['url'];
        //toba::memoria()->set_dato_instancia('url_principal', $url);
    $url_homepage = dao_general::get_url_homepage();
    if ($url_homepage) {
        echo "
        <div style='margin-left: 20px;margin-right: 20px; height: 100%'>
            <iframe src='$url_homepage' frameborder='0' height='100%' width='100%'>
        </div>";
    } else {
        echo '<div class="logo">';
    	echo toba_recurso::imagen_proyecto('logo_grande.gif', true);
	    echo '</div>';
    }
?>