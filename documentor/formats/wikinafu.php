<?php
/**
 * @version       $Id: wikinafu.php 23 2010-11-08 19:30:35Z elkuku $
 * @package       JFrameWorkDoc
 * @subpackage    Formats
 * @author        Nikolai Plath (elkuku)
 * @author        Created on 24.09.2008
 */

//-- No direct access
defined('_JEXEC') or die('=;)');

define('W_BOLD', "'''");

class ReflectorFormatWikiNafu
{
    private $className = '';

    private $path = '';

    private function formatClassMembers($xml)
    {
        if(! $xml->member)
            return '';

        $wikitext = array();

        $wikitext[] = '== Eigenschaften ==';

        $wikitext[] = '{| class="wikitable"';
        $wikitext[] = '|-';
        $wikitext[] = '!Access';
        $wikitext[] = '!Typ';
        $wikitext[] = '!Name';
        $wikitext[] = '!Beschreibung';

        /* @var SimpleXMLElement $member */
        foreach($xml->member as $member)
        {
            $wikitext[] = '|-';

            $wikitext[] = '| '
                .($member->attributes()->visibility ? $member->attributes()->visibility.' ' : '')
                .('true' == (string)$member->attributes()->static ? 'static ' : '');

            $type = ($member->docblock && $member->docblock->var)
                ? $member->docblock->var->attributes()->type
                : '{{@todo|typ}}';

            $type = ('J' == substr($type, 0, 1)) ? '[['.$type.']]' : $type;

            $wikitext[] = '|'.$type;

            $wikitext[] = '| '.W_BOLD.'<tt>$'.$member->attributes()->name.'</tt>'.W_BOLD;

            $wikitext[] = ($member->docblock && $member->docblock->description)
                ? '| '.$member->docblock->description->attributes()->compact.' {{@todo|übersetzen}}'
                : '| {{@todo|Beschreibung}}';
        }

        $wikitext[] = '|}';

        return implode(NL, $wikitext).NL.NL;
    }

    private function formatClassMethods($xml)
    {
        if(! $xml->method)
            return '';

        $wikiClassPage = '';

        $wikiClassPage .= '==Methoden=='.NL;
        $wikiClassPage .= '{| class="wikitable"'.NL;
        $wikiClassPage .= '|-'.NL;
        $wikiClassPage .= '!Access'.NL;
        $wikiClassPage .= '!Name'.NL;

        /* @var SimpleXMLElement $method */
        foreach($xml->method as $method)
        {
            $params = array();

            /* @var SimpleXMLElement $parameter */
            foreach($method->parameter as $parameter)
            {
                $p = '$'.$parameter->attributes()->name;

                if('true' == $parameter->attributes()->optional)
                {
                    $def = (string)$parameter->default;

                    //Mediawiki tweak..
                    $def = $def == "''" ? "' '" : $def;

                    $p = '['.$p.' = '.$def.']';
                }

                $params[] = $p;
            }

            $isStatic = 'true' == (string)$method->attributes()->static;

            $deprecated = ($method->docblock && $method->docblock->deprecated) ? '<br />'.W_BOLD.'@deprecated'.W_BOLD : '';

            $wikiClassPage .= '|-'.NL;
            $wikiClassPage .= '| '
                .($method->attributes()->visibility ? $method->attributes()->visibility.' ' : '')
                .('true' == (string)$method->attributes()->abstract ? 'abstract ' : '')
                .('true' == (string)$method->attributes()->final ? 'final ' : '')
                .($isStatic ? 'static ' : '')
                .$deprecated
                .NL;

            $wikiClassPage .= '| <tt>[[/'.$method->attributes()->name.'|'
                //  .($isStatic ? '::' : '->')
                .W_BOLD.$method->attributes()->name.W_BOLD.'('.implode(', ', $params).')]]</tt>'.NL;

            if($method->docblock->description)
                $wikiClassPage .= $method->docblock->description->attributes()->compact.'{{@todo|Beschreibung übersetzen}}'.NL;
        }

        $wikiClassPage .= '|}'.NL.NL;

        return $wikiClassPage;
    }

    private function formatMethodPage(SimpleXMLElement $method, $className)
    {
        $methodName = $method->attributes()->name;
        $page = array();

        $paramString = '';
        $params = array();

        $i = 0;

        foreach($method->parameter as $parameter)
        {
            $p = '$'.$parameter->attributes()->name;
            $pTable = W_BOLD.$p.W_BOLD;

            if('true' == (string)$parameter->attributes()->optional)
            {
                $def = (string)$parameter->default;

                //Mediawiki tweak..
                $def = $def == "''" ? "' '" : $def;

                $p = '['.$p.' = '.$def.']';
                $pTable = '['.$pTable.' = '.$def.']';
            }

            $params[] = $p;

            $paramString .= '|-'.NL;

            if($method->docblock->param)
            {
                foreach($method->docblock->param as $dParam)
                {
                    if('$'.$parameter->attributes()->name != (string)$dParam->attributes()->variable)
                        continue;

                    $paramString .= '|<tt>'.$dParam->attributes()->type.'</tt>'.NL;
                    $paramString .= '|<tt>'.$pTable.'</tt>'.NL;
                    $paramString .= '|'.$dParam->attributes()->description.NL;
                }
            }
            else
            {
                $paramString .= '|<tt>{{@todo|Typ}}</tt>'.NL;
                $paramString .= '|<tt>'.$pTable.'</tt>'.NL;
                $paramString .= '|{{@todo|Beschreibung}}'.NL;
            }

            $i ++;
        }

        $page[] = '== Beschreibung ==';
        $page[] = '{{@todo|Beschreibung übersetzen}}'.NL;

        $fullSig = '';
        $fullSig .= $method->attributes()->visibility.' ';
        $fullSig .= ('true' == (string)$method->attributes()->final) ? 'final ' : '';
        $fullSig .= ('true' == (string)$method->attributes()->abstract) ? 'abstract ' : '';
        $fullSig .= ('true' == (string)$method->attributes()->static) ? 'static ' : '';
        $fullSig .= 'function '.$methodName;

        if($method->docblock)
        {
            $page[] = $method->docblock->description->attributes()->compact.NL.NL;
            $page[] = $method->docblock->description.NL.NL;
        }

        $page[] = '==Syntax==';
        $page[] = '{{syntax|<source lang="php" enclose="none">'.$fullSig.'('.implode(', ', $params).')</source>}}'.NL;

        if($method->docblock)
        {
            if($method->docblock->return)
            {
                $ret = '';
                $ret .= '* '.W_BOLD.'@return'.W_BOLD;

                $t = $method->docblock->return->attributes()->type;

                $t = ('J' == substr($t, 0, 1)) ? '[['.$t.']]' : $t;

                $ret .= ' {{mark|'.$t.'}}';
                $ret .= ' '.$method->docblock->return->attributes()->description;

                $page[] = $ret;
            }

            if($method->docblock->since)
            {
                $page[] = '* '.W_BOLD.'@since'.W_BOLD.' {{JVer|'.$method->docblock->since->attributes()->value.'}}';
            }

            if($method->docblock->deprecated)
            {
                $page[] = '* '.W_BOLD.'@deprecated'.W_BOLD.' '.$method->docblock->deprecated->attributes()->value;
            }
        }

        $page[] = '';

        if($paramString)
        {
            $page[] = '{| class="wikitable"';
            $page[] = '!Datentyp';
            $page[] = '!Parameter';
            $page[] = '!Beschreibung';
            $page[] = $paramString.'|}'.NL;
        }

        $page[] = '==Beispiele==';
        $page[] = NL.'{{@todo|Beispiele Baby...}}'.NL;
        $page[] = '<source lang="php">'.NL.'//-- Dein Beispiel'.NL.'</source>'.NL;

        $page[] = '==Quellcode==';

        $page[] = '<nafucode>@J/'.$className.'/'.$methodName.'</nafucode>'.NL;

        $page[] = '==Siehe auch==';
        $sig = ('true' == (string)$method->attributes()->static) ? '::' : '->';
        /*
        $page[] = '* <tt>[http://api.joomla.org/Joomla-Platform/'.$s.$className.'.html#'
            .$methodName.' '.$className.'->'.$methodName
            .'()]</tt> auf api.joomla.org'.NL;
        */
        $page[] = '* <tt>['.$this->getApiLink($methodName).' '.$className.$sig.$methodName.']</tt> auf api.joomla.org'.NL;

        $page[] = '[[Kategorie:Joomla! Programmierung|'.$methodName.']]';
        $page[] = '[[Kategorie:Framework|'.$methodName.']]';
        $page[] = '[[Kategorie:'.$className.'|'.$methodName.']]';

        return implode(NL, $page);
    }

    public function reflectClass(SimpleXMLElement $xml, $path, $className)
    {
        $this->path = $path;
        $this->className = $className;

        $html = '';
        $classPanel = '';

        $class = false;

        foreach($xml->class as $c)
        {
            if($className != (string)$c->attributes()->name)
                continue;

            $class = $c;
        }

        if(! $class)
            throw new Exception(__METHOD__.sprintf(' - Class %s not fund in %s', $className, $path));

        $extends = ($class->extends) ? (string)$class->extends->attributes()->class : '';
        $className = (string)$class->attributes()->name;

        $wikiMethodsPages = array();
        $wikiClassPage = '';

        $wikiClassPage .= '== Beschreibung =='.NL;
        $wikiClassPage .= W_BOLD.'<tt>';
        $wikiClassPage .= ('true' == (string)$class->attributes()->final) ? 'final ' : '';
        $wikiClassPage .= ('true' == (string)$class->attributes()->abstract) ? 'abstract ' : '';
        $wikiClassPage .= 'class '.$className;
        $wikiClassPage .= ($extends) ? ' extends [['.$extends.']]' : '';
        $wikiClassPage .= '</tt>'.W_BOLD;
        $wikiClassPage .= NL.NL;

        $wikiClassPage .= '{{@todo|Beschreibung übersetzen}}'.NL.NL;
        if($class->docblock)
        {

            $wikiClassPage .= $class->docblock->description->attributes()->compact.NL.NL;
            $wikiClassPage .= $class->docblock->description.NL;
        }

        $wikiClassPage .= '== Definiert in =='.NL;

        $wikiClassPage .= '{{folder|/'.substr($path, 0, strrpos($path, '.')).'}}';
        $wikiClassPage .= NL.NL;

        $wikiClassPage .= '== Status =='.NL;

        if($class->docblock->since)
        {
            $wikiClassPage .= '* '.W_BOLD.'@since'.W_BOLD.' {{JVer|'.$class->docblock->since->attributes()->value.'}}'.NL;
        }

        if($class->docblock->deprecated)
        {
            $wikiClassPage .= '* '.W_BOLD.'@deprecated'.W_BOLD.' '.$class->docblock->deprecated->attributes()->value.NL;
        }

        $wikiClassPage .= NL;

        $wikiClassPage .= '== Importieren =='.NL;
        $wikiClassPage .= '{{@todo}} jimport oder autloader ??'.NL.NL;

        if($extends)
        {
            $wikiClassPage .= '== Erweitert =='.NL;
            $extends = $class->extends->attributes()->class;
            $wikiClassPage .= '[['.$extends.']]';
            $wikiClassPage .= NL.NL;
        }

        if(array_key_exists($className, JDocsReader::$extenders))
        {
            $wikiClassPage .= '== Wird erweitert von =='.NL;

            sort(JDocsReader::$extenders[$className]);

            $wikiClassPage .= '[['.implode(']], [[', JDocsReader::$extenders[$className]).']].'.NL.NL;
        }

        $wikiClassPage .= $this->formatClassMembers($class);
        $wikiClassPage .= $this->formatClassMethods($class);

        $wikiClassPage .= '== Siehe auch =='.NL;
        $wikiClassPage .= '* <tt>['.$this->getApiLink().' '.$className.']</tt> auf api.joomla.org'.NL.NL;

        $wikiClassPage .= '[[Kategorie:Joomla! Programmierung|'.$className.']]'.NL;
        $wikiClassPage .= '[[Kategorie:Framework|'.$className.']]'.NL;
        $wikiClassPage .= '[[Kategorie:'.$className.'|'.$className.']]'.NL;

        foreach($class->method as $method)
        {
            $wikiMethodsPages[(string)$method->attributes()->name] = $this->formatMethodPage($method, $className);
        }

        $classPanel .= '<li class="st_class" id="switch-'.$className.'" onclick="switchPage(\''.$className.'\');">'.$className.'</li>';

        $html .= '<div id="page-'.$className.'" style="display: none;">';

        $html .= '<textarea class="code" style="width: 100%" rows="40" cols="150" id="'.$className.'-xxpage"'
            .' onfocus="aSelect(\''.$className.'-xxpage\');" onclick="aSelect(\''.$className.'-xxpage\')">'
            .htmlspecialchars($wikiClassPage)
            .'</textarea>';

        $html .= '</div>';

        foreach($wikiMethodsPages as $pName => $page)
        {
            $title = $pName;
            $classPanel .= '<li class="st_method" id="switch-'.$pName.'" onclick="switchPage(\''.$pName.'\');">'.$pName.'</li>';
            $html .= '<div id="page-'.$pName.'" style="display: none;">';
            $html .= '<textarea  class="code" style="width: 100%" rows="40" cols="150" id="'.$title.'-xxpage" onfocus="aSelect(\''.$title.'-xxpage\');" onclick="aSelect(\''.$title.'-xxpage\');">'.htmlspecialchars($page).'</textarea>';
            $html .= '</div>';
        }

        $chk = '<input type="checkbox" id="chk_aselect" checked="checked" /> <label for="chk_aselect">Auto select</label>';
        $classPanel = '<ul class="classpanel">'.$classPanel.'</ul>'.$chk;

        return '<table><tr valign="top"><td>'.$classPanel.'</td><td>'.$html.'</td></tr></table>';
    }

    private function getApiLink($method = '')
    {
        // The "replacements" have differences from the standard naming scheme..
        $replacements = array(
            'Filesystem' => 'FileSystem'
        , 'Github' => 'GitHub'
        , 'Html' => 'HTML'
        , 'Http' => 'HTTP'
        );

        $parts = explode('/', $this->path);

        if(! count($parts) > 1)
            return '';

        array_pop($parts);

        if(isset($parts[1]) && 'joomla' != $parts[1])
        {
            // !!! Only Joomla! platform classes are documented on api.joomla.org !!!
            return '';
        }

        $package = '';

        if(isset($parts[2]))
        {
            $package = ucfirst($parts[2]);
            $package = (array_key_exists($package, $replacements)) ? $replacements[$package] : $package;
            $package .= '/';
        }

        $method = ($method) ? '#'.$method : '';

        return 'http://api.joomla.org/Joomla-Platform/'.$package.$this->className.'.html'.$method;
    }
}//class
