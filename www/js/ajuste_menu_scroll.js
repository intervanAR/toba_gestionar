var menuh= document.getElementById('menu-h');
var opciones= menuh.children;
for (var i = 0; i < opciones.length; i++) {
	var item= opciones[i].children[1];
	if((item != null)&&(item != 'undifined')){
		var listas= item.getElementsByTagName('ul');
		if(listas.length > 0){
			item.className= 'menu_carpeta';
		}else{
			item.className= 'menu_opciones';
		}
	}	
};