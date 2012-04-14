<?php
/**
 * @version $Id: reflector.php 23 2010-11-08 19:30:35Z elkuku $
 * @package		JFrameWorkDoc
 * @subpackage	Helpers
 * @author		Nikolai Plath (elkuku) {@link http://www.nik-it.de NiK-IT.de}
 * @author		Created on 24-Sep-2008
 * @license    GNU/GPL, see JROOT/LICENSE.php
 */

//-- No direct access
defined( '_JEXEC') or die('=;)');

class EasyReflector
{
    public static $extenders = array();

    public static function reflectFromXml($jTarget, $version, $path)
    {
        $xmlPath = JPATH_BULD.'/'.$jTarget.'/'.$version.'/xml/'.$path;

        $xml = JFactory::getXML($xmlPath);

        if( ! $xml)
            throw new Exception(__METHOD__.' - Unreadable XML file at: '.$path);

        //-- Identify extending classes

        /* @var SimpleXMLElement $class */
        foreach($xml->class as $class)
        {
            if($class->extends)
            {
                self::$extenders[(string)$class->attributes()->name][] = $class->extends->attributes()->class;
            }
        }

        $outputFormat = 'wikinafu';

        if( ! file_exists(JPATH_BASE.'/formats/'.$outputFormat.'.php'))
        {
            echo 'unknown format '.$outputFormat;

            return false;
        }

        require_once JPATH_BASE.'/formats/'.$outputFormat.'.php';

        $className = 'ReflectorFormat'.$outputFormat;

        if( ! class_exists($className)) {
            printf('Required class %s not found', $className);

            return false;
        }

        $reflector = new $className;

        return $reflector->reflectClass($xml, $path);
    }

}//class
