<?php
/**
 * Created by JetBrains PhpStorm.
 * User: elkuku
 * Date: 10.04.12
 * Time: 19:04
 * To change this template use File | Settings | File Templates.
 */

class JDocsReader
{
    public static $extenders = array();

    public static function reflectClass($jTarget, $version, $path, $className, $outputFormat = 'wikinafu')
    {
        $xmlPath = JPATH_BULD.'/'.$jTarget.'/'.$version.'/xml/'.$path;

        $xml = JFactory::getXML($xmlPath);

        if( ! $xml)
            throw new Exception(__METHOD__.' - Unreadable XML file at: '.$path);

        //-- Identify extending classes

        $xmlClasses = JFactory::getXML(JPATH_BULD.'/'.$jTarget.'/'.$version.'/xml/classes.xml');

        if( ! $xmlClasses)
            throw new Exception(__METHOD__.' - Unreadable XML file at: '.$path);

        /* @var SimpleXMLElement $class */
        foreach($xmlClasses->class as $class)
        {
            if($class->extends)
            {
                self::$extenders[(string)$class->extends->attributes()->class][] = (string)$class->attributes()->name;
            }
        }

        if( ! file_exists(JPATH_BASE.'/formats/'.$outputFormat.'.php'))
            throw new Exception(__METHOD__.' - Unknown format: '.$outputFormat);

        require_once JPATH_BASE.'/formats/'.$outputFormat.'.php';

        $cName = 'ReflectorFormat'.$outputFormat;

        if( ! class_exists($cName))
            throw new Exception(__METHOD__.sprintf('Required class %s not found', $cName));

        $reflector = new $cName;

        return $reflector->reflectClass($xml, $path, $className);
    }

    public static function parseClassList($path)
    {
        $xml = JFactory::getXml($path);

        if(! $xml)
            throw new Exception(__METHOD__.' - Unreadable XML file at: '.$path);

        $list = array();

        /* @var SimpleXMLElement $class */
        foreach($xml->class as $class)
        {
            $name = (string)$class->attributes()->name;

            if($name != (string)$class->attributes()->full)
                throw new Exception(__METHOD__.' dunno what to do :( --> '
                    .$name.' vs '.$class->attributes()->full);

            $parts = explode('/', $class->attributes()->xml);

            array_pop($parts);

            if(1 == count($parts))
            {
                $library = 'Base';
                $package = 'Base';
            }
            else
            {
                $library = ucfirst($parts[1]);
                $package =(isset($parts[2])) ? ucfirst($parts[2]) : 'Base';
            }

            if($class->extends)
            {
                self::$extenders[(string)$class->attributes()->name][] = $class->extends->attributes()->class;
            }

            $list[$library][$package][$name] = $class;
        }

        ksort($list);

        return $list;
    }
}
