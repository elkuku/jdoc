/**
 * @version $Id: jframeworkdoc.js 23 2010-11-08 19:30:35Z elkuku $
 * @package     JFrameWorkDoc
 * @subpackage Javascript
 * @author      EasyJoomla {@link http://www.easy-joomla.org Easy-Joomla.org}
 * @author      Nikolai Plath (elkuku) {@link http://www.nik-it.de NiK-IT.de}
 * @author      Created on 24.09.2008
 */

var lastDiv = '';

var loaderPic = new Image();
loaderPic.src = 'themes/jdoc/assets/images/ajax-loader.gif';

var Tix;

function load_file(path, className, d) {
    var req = null;

    postData = '';
    postData += 'path=' + path + '&className=' + className;
    //postData += '&output_format='+$('output_format').value;
    //postData += '&j_version='+$('j_version').value;
    //postData +=( $('j_version2') != undefined ) ? '&j_version2='+$('j_version2').value : '';
    //postData +=( $('use_geshi') != undefined ) ? '&use_geshi='+$('use_geshi').checked : '';
    //$('permaLink').href = 'jdoc.php?'+postData;
    postData += '&task=show_class';

    if (lastDiv != '') {
        //$(lastDiv).setStyle('color', 'black');
        //$(lastDiv).setStyle('font-weight', 'normal');
    }

    if (d) {
        //$(d).setStyle('color', 'blue');
        //$(d).setStyle('font-weight', 'bold');
        //lastDiv = d;
    }

    new Request.HTML({
        url:'index.php', update:$('jdocDisplay'), onRequest:function () {
            $('jdocDisplay').innerHTML = '<img src="" id="ajax-loader" /><br />Loading....';
            $('ajax-loader').src = loaderPic.src;
        }, onSuccess:function () {
            openedDiv = '';
            //if($('output_format').value == 'html') {
            //	Tix = new Tips('.hasTip',{ hideDelay: 400, fixed: true });
            //}
        }
    }).post("index.php?" + postData);
}//function

var openedDiv = '';

function switchPage(divName) {
    if (openedDiv !== '') {
        $('page-' + openedDiv).setStyle('display', 'none');
        $('switch-' + openedDiv).setStyle('color', 'black');
    }

    $('page-' + divName).setStyle('display', 'block');
    $('switch-' + divName).setStyle('color', 'blue');
    openedDiv = divName;

    /*	link = $('permaLink').href;
     pos = link.indexOf('&page');

     if(pos != -1)
     {
     link = link.slice(0, pos)
     }

     $('permaLink').href = link + '&page=' + divName;
     */
}//function

function xxx$(id) {
    return document.getElementById(id);
}//function

function aSelect(id) {
    if ($('chk_aselect').checked == 1) {
        $(id).select();
    }
}//function
