/**
 * Setea el campo como obligatorio y adem·s
 * le da estilo al label del input para que
 * la obligatoriedad sea visible.
 *
 * @author lgraziani
 */
function set_obligatorio(ef) {
    const label = ef.nodo().children[0];

    label.className += '-oblig';
    label.innerText += ' (*)';

    ef.set_obligatorio(true);
}

/*
 *
 * function modal
 *
 * Param:
 *  id: es el #fila en un cuadro
 *
 **/
function modal( id ){
    var selectorName    =   'popup'+id;
    $('div[name='+selectorName+']').fadeIn('slow');
    $('.popup-overlay').fadeIn('slow');
    $('.popup-overlay').height($(window).height());
}

/*
 * close, aplica a todos los tags cuyo class es close
 *
 **/
$(document).ready(function(){
    $('.close').click(function(){
       //efecto fade, para cualquier div con name que inicie con popup
       $('div[name^=popup]').fadeOut('slow');
        $('.popup-overlay').fadeOut('slow');
        return false;
    });
});

/*
 *@param fecha: Date
 *@param format: String, default 'Y-m-d'
 **/
function formatearFecha(fecha, format = 'Y-m-d') {

    if (typeof fecha != 'undefined' && fecha != null){
        var month   = '' + (fecha.getMonth() + 1);
        var day     = '' + fecha.getDate();
        var year    = fecha.getFullYear();

        if (month.length < 2)   {month  = '0' + month;}
        if (day.length < 2)     {day    = '0' + day;}

        return [year, month, day].join('-');
    }

    return '';
}


/*
 *@param Date fecha la fecha que a la que se le calcula el primer dÌa del mes
 *@return Date
 **/
function primerDiaDelMes(fecha) {

    if (typeof fecha != 'undefined' && fecha != null){
        return new Date(fecha.getFullYear(), fecha.getMonth(), 1);
    }

    return null;
}

/*
 *@param Date fecha la fecha que a la que se le calcula el ˙ltimo dÌa del mes
 *@return Date
 **/
function ultimoDiaDelMes(fecha) {

    if (typeof fecha != 'undefined' && fecha != null){
        return new Date(fecha.getFullYear(), fecha.getMonth() + 1, 0);
    }

    return null;
}


/*
 * Scripts que permiten insertar contenido en un text area, segun la posicion
 * del cursor.
 *
 * puede ser util.
 **/

function getInputSelection(el) {
    var start = 0, end = 0, normalizedValue, range,
        textInputRange, len, endRange;

    if (typeof el.selectionStart == "number" && typeof el.selectionEnd == "number") {
        start = el.selectionStart;
        end = el.selectionEnd;
    } else {
        range = document.selection.createRange();

        if (range && range.parentElement() == el) {
            len = el.value.length;
            normalizedValue = el.value.replace(/\r\n/g, "\n");

            // Create a working TextRange that lives only in the input
            textInputRange = el.createTextRange();
            textInputRange.moveToBookmark(range.getBookmark());

            // Check if the start and end of the selection are at the very end
            // of the input, since moveStart/moveEnd doesn't return what we want
            // in those cases
            endRange = el.createTextRange();
            endRange.collapse(false);

            if (textInputRange.compareEndPoints("StartToEnd", endRange) > -1) {
                start = end = len;
            } else {
                start = -textInputRange.moveStart("character", -len);
                start += normalizedValue.slice(0, start).split("\n").length - 1;

                if (textInputRange.compareEndPoints("EndToEnd", endRange) > -1) {
                    end = len;
                } else {
                    end = -textInputRange.moveEnd("character", -len);
                    end += normalizedValue.slice(0, end).split("\n").length - 1;
                }
            }
        }
    }

    return {
        start: start,
        end: end
    };
}

function offsetToRangeCharacterMove(el, offset) {
    return offset - (el.value.slice(0, offset).split("\r\n").length - 1);
}

function setSelection(el, start, end) {
    if (typeof el.selectionStart == "number" && typeof el.selectionEnd == "number") {
        el.selectionStart = start;
        el.selectionEnd = end;
    } else if (typeof el.createTextRange != "undefined") {
        var range = el.createTextRange();
        var startCharMove = offsetToRangeCharacterMove(el, start);
        range.collapse(true);
        if (start == end) {
            range.move("character", startCharMove);
        } else {
            range.moveEnd("character", offsetToRangeCharacterMove(el, end));
            range.moveStart("character", startCharMove);
        }
        range.select();
    }
}

function insertTextAtCaret(el, text) {
    var pos = getInputSelection(el).end;
    var newPos = pos + text.length;
    var val = el.value;
    el.value = val.slice(0, pos) + text + val.slice(pos);
    setSelection(el, newPos, newPos);
}

function agregarCadenaEnCursor(id, texto) {
    var textarea = document.getElementById(id);
    textarea.focus();
    texto = texto.replace( /&lt;/gi,'<' );
    texto = texto.replace( /&gt;/gi,'>' );
    insertTextAtCaret(textarea, texto);
    return false;
};

/*
* @param cadena: String
* @return True/False
* Valida su una cadena representa una fecha en formato MMYYYY. Ej: 042017
*/
validateMMYYYY = function(cadena) {
    var reg = new RegExp('(((0[123456789]|10|11|12)(([1][9][0-9][0-9])|([2][0-9][0-9][0-9]))))');
    if (reg.test(cadena)){
        return true;
    }else{
        return false;
    }
};
/*
* Oculta el modal "procesando..."
*/
ocultar_esperar = function()
{
    scroll(0,0);
    var capa_espera = document.getElementById('capa_espera');
    if (capa_espera) {
        capa_espera.style.visibility = 'hidden';
    }
}

/*
* @param mensaje: String
* @param tipo_notificacion: String
* Genera un modal con el mensaje y el tipo de notificaciÛn ingresados.
*/
function imprimir_notificacion(mensaje, tipo_notificacion) {
    notificacion.limpiar();
    notificacion.agregar(mensaje, tipo_notificacion);
    notificacion.mostrar();
    notificacion.limpiar();
}

/*
* @param str: String
* Formatea la cadena 'str' eliminando acentos y Ò.
*/
normalizar = (function() {
    var caracteres_especiales = '√¿¡ƒ¬»…À ÃÕœŒ“”÷‘Ÿ⁄‹€„‡·‰‚ËÈÎÍÏÌÔÓÚÛˆÙ˘˙¸˚—Ò«Á';
    var caracteres_estandar   = 'AAAAAEEEEIIIIOOOOUUUUaaaaaeeeeiiiioooouuuunncc';
    mapping = {};

    for(var i = 0, j = caracteres_especiales.length; i < j; i++ )
        mapping[ caracteres_especiales.charAt( i ) ] = caracteres_estandar.charAt( i );

    return function( str ) {
        var ret = [];
        for( var i = 0, j = str.length; i < j; i++ ) {
            var c = str.charAt( i );
            if( mapping.hasOwnProperty( str.charAt( i ) ) )
                ret.push( mapping[ c ] );
            else
                ret.push( c );
        }
        return ret.join( '' );
    }

})();

mostrarModal = function()
{
    var parametros_modal = [];
    for (arg = 0; arg < arguments.length; arg++){
        parametros_modal[arg] = arguments[arg];
    }
    var params = parametros_modal.join('||');
    var modal = document.getElementById('modal_fecha_generico');
    if ( $('#ef_form_113000705_formularioparam').length ){
        //existe el modal e imput param
        js_form_113000705_formulario.config(params);
    }
    modal.className='modalForm modalFormAct';
}

/**
 * [showModal]
 * Argumentos[0] String : ID del div donde est· contenido el modal.
 * Argumentos[1] Object : Objeto_js del formulario contenido en el modal para su posterior configuraciÛn.
 * @return {[modal]} [ Muestra un modal configurado seg˙n los argumentos anteriores ]
 */
showModal = function()
{
    var argumentos_modal = [];
    for (arg = 0; arg < arguments.length; arg++){
        argumentos_modal[arg] = arguments[arg];
    }

    var modal = document.getElementById(argumentos_modal[0]);

    if (argumentos_modal[1] !== undefined) {
        //existe el modal e imput param
        argumentos_modal[1].config(argumentos_modal);
    }

    modal.className='modalForm modalFormAct';
}
