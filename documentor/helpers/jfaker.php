<?php
/**
 * @version $Id: jfaker.php 23 2010-11-08 19:30:35Z elkuku $
 * @package JFrameWorkDoc
 * @subpackage  Helpers
 * @author      EasyJoomla {@link http://www.easy-joomla.org Easy-Joomla.org}
 * @author      Nikolai Plath {@link http://www.nik-it.de}
 * @author      Created on 18-Jul-2009
 * @license    GNU/GPL, see JROOT/LICENSE.php
 */

######################################
## helpers..
######################################
defined('_JEXEC') or die('=;)');

function fakeJ($sub_package, $sub_sub_package, $sub_sub_sub_package, $fileName)
{
    if( $sub_package == 'mail' && $fileName == 'mail.php' ){ class PHPMailer {} }
    if( $sub_package == 'database' && $fileName != 'table.php' ){ class JTable {} }
 #   if( $sub_package == 'database' && $sub_sub_package == 'database' ){ class JDatabase {} }
if( $fileName != 'registry.php' ){ class JRegistry {} }
    if( $sub_package == 'registry' && $sub_sub_package == 'format' ) { class JRegistryFormat {} }

    if( $fileName != 'observable.php') { class JObservable {} }
    /*
     * Joomla! 1.6
     */
    #	if( $sub_package == 'access' || $sub_package == 'application' && $fileName != 'model.php'){ class JModel{} }
    if( $fileName != 'storage.php') { class JSessionStorage {} }
}
function jimport() { }//function

class JLoader
{
    function register() {}//function
    function import() {}//function
}//class

#class gacl_api {}
class patTemplate {}
class patTemplate_Modifier {}
class patTemplate_Function {}

class PHPMailer {}
