<?php
/**
 * @version $Id: docswiki.php 23 2010-11-08 19:30:35Z elkuku $
 * @package     JFrameWorkDoc
 * @subpackage  Formats
 * @author		Nikolai Plath (elkuku) {@link http://www.nik-it.de NiK-IT.de}
 * @author		Created on 24.09.2008
 */

//-- No direct access
defined( '_JEXEC') or die('=;)');

class ReflectorFormatDocsWiki
{
    function reflect($rawDoc)
    {
        $wikiClassPrefix = 'Joomla!_Programmierung/Framework';

        $html = '';
        $classPanel = '';

        foreach ($rawDoc->classes as $class)
        {
            $wikiMethodsPages = array();
            $wikiClassPage = '';
           $wikiClassPage .= '{{RightTOC}}'.NL;
            $wikiClassPage .= '==Description=='.NL;
            $wikiClassPage .= "'''".$class->getName()."'''".' @todo->Description'.NL.NL;
            $since = '';
            $isStatic = false;
            if($class->getDocComment())
            {
                $wikiClassPage .= '<source lang="php">DocComment is for reference only - please DELETE'.NL.$class->getDocComment().NL.'</source>'.NL.NL;
                $comment = explode(NL, $class->getDocComment());
                $searches = array('@since', '@static');
                foreach ($comment as $c)
                {
                    foreach ($searches as $search)
                    {
                        if( strpos($c, $search))
                        {
                            if( $search == '@static'){ $isStatic = true; continue; }
                            if( $search == '@since')
                            {
                                if( strpos($c, '1.5') )
                                {
                                    $since = '{{JVer|1.5}}';
                                }
                                if( strpos($c, '1.6') )
                                {
                                    $since = '{{JVer|1.5}} {{JVer|1.6}}';
                                }
//                                $since = str_replace('@since', "'''@since'''", $c);
//                                $since = str_replace('1.5', '{{JVer|1.5}}', $since);
//                                $since = str_replace('1.6', '{{JVer|1.6}}', $since);
                            }
                        }
                    }//foreach
                }//foreach
            }

            $parent = $class->getParentClass();

            $wikiClassPage .= '==Availability=='.NL;
  #          $wikiClassPage .=($isStatic) ? "* '''@static'''".NL : '';
            $wikiClassPage .=($since) ? ($since) : '{{JVer|1.5}} {{JVer|1.6}}';//"* '''@since''' {{JVer|1.5}}".NL;
            $wikiClassPage .= NL;

            $definedIn = '';
            $definedIn .= '==Defined in=='.NL;
            $definedIn .= 'libraries/joomla';

            if( $class->subPackage )
            {
                $definedIn .= '/'.$class->subPackage;
            }
            if( $class->subSubPackage )
            {
                $definedIn .= '/'.$class->subSubPackage;
            }
            if( $class->subSubSubPackage )
            {
                $definedIn .= '/'.$class->subSubSubPackage;
            }
            $definedIn .= '/'.$rawDoc->fileName.NL.NL;

            $wikiClassPage .= $definedIn;


            $wikiClassPage .= '==Importing=='.NL;
            $s = ($class->subSubPackage) ? $class->subSubPackage.'.' : '';
            $fN =(strpos($rawDoc->fileName, '.')) ? substr($rawDoc->fileName, 0, strpos($rawDoc->fileName, '.' )) : $rawDoc->fileName;
            $wikiClassPage .= '<source lang="php">jimport( \'joomla.'.$class->subPackage.'.'.$s.$fN.'\' );</source>'.NL.NL;

            $wikiClassPage .= '==Extends=='.NL;
            $wikiClassPage .=( $parent ) ? '[['.$parent->name.']]' : '* None';
            $wikiClassPage .= NL.NL;

            $wikiClassPage .= '==Extended by=='.NL;
            $extenders = getExtendingClasses();
            if( array_key_exists($class->getName(), $extenders))
            {
                foreach ($extenders[$class->getName()] as $ex)
                {
                    $wikiClassPage .= '* [['.$ex.']]'.NL;
                }//foreach
            }
            else
            {
                $wikiClassPage .= '* None'.NL;
            }

            $wikiClassPage .= NL;

            $methods =  $class->getMethods();
            if($methods)
            {
                $wikiClassPage .= '==Methods=='.NL;
                $wikiClassPage .= '{| class="wikitable"'.NL;
                $wikiClassPage .= '|-'.NL;
                $wikiClassPage .= '!Name'.NL;
                $wikiClassPage .= '!Description'.NL;
            }

            $displayClassName = '';
            foreach ($methods as $method)
            {
                $declaringClass= $method->getDeclaringClass()->getName();
                if( strtolower($declaringClass) != strtolower($class->getName()) )
                {
                    continue;
                }

                $parameters = $method->getParameters();
                $wikiParams = array();
                $wikiParamsDesc = '';

                $comment = explode(NL, $method->getDocComment());
                $searches = array('@return', '@since', '@static', '@param');

                $docComOptions = self::parseDocComment($method->getDocComment(), $searches);

                foreach( $parameters as $parameter )
                {
                    if( $parameter->isDefaultValueAvailable())
                    {
                        $def = $parameter->getDefaultValue();

                        if( $def === null)
                        {
                            $wikiDefault = 'null';
                        }
                        else if( $def === false )
                        {
                            $wikiDefault = 'false';
                        }
                        else if( $def === true )
                        {
                            $wikiDefault = 'true';
                        }
                        else if( $def === array() )
                        {
                            $wikiDefault = 'array()';
                        }
                        else if( $def === '' )
                        {
                            $wikiDefault = "''";
                        }
                        else
                        {
                            $wikiDefault = $def;
                        }
                    }
                    else
                    {
                        $wikiDefault = 'NODEFAULT';
                    }

                    $wikiP = '$'.$parameter->getName();
                    if( $parameter->isOptional() )
                    {
                        $wikiP = '['.$wikiP.']';
                    }
                    $wikiParams[] = $wikiP;

                    $wikiParamsDesc .= '|-'.NL;
                    $wikiParamsDesc .= '|<tt>'.$wikiP.'</tt>'.NL;
                    $p = str_replace(array('[', ']'), '',$wikiP);
                    if( array_key_exists($p, $docComOptions->params) )
                    {
                        $wikiParamsDesc .= '|<tt>'.$docComOptions->params[$p]['type'].'</tt>'.NL;//--Typ
                        $wikiParamsDesc .= '|'.$docComOptions->params[$p]['desc'].NL;
                    }
                    else
                    {
                        $wikiParamsDesc .= '|<tt>@todo->Type</tt>'.NL;
                        $wikiParamsDesc .= '|@todo->Description'.NL;
                    }
                    $wikiParamsDesc .=($wikiDefault === 'NODEFAULT') ? '| ---'.NL : '|<tt>'.$wikiDefault.'</tt>'.NL;
                }//foreach parameters

                $syntaxAdds = '';
                $syntaxAdds .=( $docComOptions->return ) ? $docComOptions->return.NL : "* '''@return''' @todo".NL;
                $syntaxAdds .=( $docComOptions->since ) ? $docComOptions->since.NL : "* '''@since''' {{JVer|1.5}}".NL;

                $wikiMethodsPage = '';
#                $wikiMethodsPage .= "{{RightTOC}}";
                $wikiMethodsPage .= '==Description=='.NL;
                $wikiMethodsPage .= "'''".$class->getName().'/'.$method->name."'''".' @todo->Description'.NL.NL;
                if($method->getDocComment())
                {
                    $wikiMethodsPage .= '<source lang="php">The DocComment is for reference only - please remove'.NL.$method->getDocComment().'</source>'.NL.NL;
                }
                $wikiMethodsPage .= '==Syntax=='.NL;
                $isStatic =($docComOptions->isStatic || $method->isStatic() ) ? true : false;//@todo
                $s =($isStatic) ? 'static ' : '';
                $wikiMethodsPage .= '<source lang="php">'.$s.$method->name.'( '.implode(', ', $wikiParams).' )</source>'.NL;
                $wikiMethodsPage .= $syntaxAdds.NL;

                if( $wikiParamsDesc )
                {
                    $wikiMethodsPage .= '{| class="wikitable"'.NL;
                    $wikiMethodsPage .= '!Parameter'.NL;
                    $wikiMethodsPage .= '!Datatyp'.NL;
                    $wikiMethodsPage .= '!Description'.NL;
                    $wikiMethodsPage .= '!Default'.NL;

                    $wikiMethodsPage .= $wikiParamsDesc;
                    $wikiMethodsPage .= '|}'.NL.NL;
                }

                $wikiMethodsPage .= '==Examples=='.NL;
                $wikiMethodsPage .= '@todo: examples Baby...'.NL.NL;
                $wikiMethodsPage .= '<source lang="php">'.NL.'//-- Your example'.NL.'</source>'.NL.NL;

                $wikiMethodsPage .= '==Quellcode=='.NL;

                $s = ($class->subPackage) ? $class->subPackage.'/' : '';
                $ss = ($class->subSubPackage) ? $class->subSubPackage.'/' : '';
                $wikiMethodsPage .= '<jcodedisplay>'.$class->getName().'/'.$method->name.'</jcodedisplay>'.NL.NL;

                $wikiMethodsPage .= '==See also=='.NL;
                $s =($class->subPackageName) ? $class->subPackageName.'/' : '';
                $wikiMethodsPage .= '* <tt>[http://api.joomla.org/Joomla-Framework/'.$s.$class->getName().'.html#'.$method->name.' '.$class->getName().'->'.$method->name.'()]</tt> on api.joomla.org'.NL.NL;

//                $wikiMethodsPage .= '[[Kategorie:Experten|'.$method->name.']]'.NL;
//                $wikiMethodsPage .= '[[Kategorie:Joomla! Programmierung|'.$method->name.']]'.NL;
                $wikiMethodsPage .= '[[Category:Framework|'.$method->name.']]'.NL;
                $wikiMethodsPage .= '[[Category:'.$class->getName().'|'.$method->name.']]'.NL;

                //--Everythig starting with a '_' will be ignored - aka private.. @todo change for 1.6
                if( $method->name == '_' || substr($method->name, 0, 1) != '_')
                {
                    $wikiMethodsPages[$method->name] = $wikiMethodsPage;
                    $wikiClassPage .= '|-'.NL;
                    $wikiClassPage .= '| [[/'.$method->name.'|'.$method->name.']]'.NL;
                    #$wikiClassPage .= '| [['.$wikiClassPrefix.'/'.$class->getName().'/'.$method->name.'|'.$method->name.']]'.NL;
                    $wikiClassPage .= '| @todo->Description'.NL;
                }

            }//foreach methods

            if($methods)
            {
                $wikiClassPage .= '|}'.NL.NL;
            }
            $wikiClassPage .= '==See also=='.NL;
            $s =($class->subPackageName) ? $class->subPackageName.'/' : '';
            $wikiClassPage .= '* <tt>[http://api.joomla.org/Joomla-Framework/'.$s.$class->getName().'.html '.$class->getName().']</tt> auf api.joomla.org'.NL.NL;
            $wikiClassPage .= '[[Kategorie:Experten|'.$class->getName().']]'.NL;
            $wikiClassPage .= '[[Kategorie:Joomla! Programmierung|'.$class->getName().']]'.NL;
            $wikiClassPage .= '[[Kategorie:Framework|'.$class->getName().']]'.NL;
            $wikiClassPage .= '[[Kategorie:'.$class->getName().'|'.$class->getName().']]'.NL;

            $classPanel .= '<li class="st_class" id="switch-'.$class->getName().'" onclick="switchPage(\''.$class->getName().'\');">'.$class->getName().'</li>';

            $html .= '<div id="page-'.$class->getName().'" style="display: none;">';

            $html .= '<textarea  class="code" style="width: 100%" rows="40" cols="150" id="'.$class->getName().'-xxpage" onfocus="aSelect(\''.$class->getName().'-xxpage\');" onclick="aSelect(\''.$class->getName().'-xxpage\')">'.htmlspecialchars($wikiClassPage).'</textarea>';
            $html .= '</div>';

            foreach ($wikiMethodsPages as $pName => $page)
            {
                $title = $pName;
                $classPanel .= '<li class="st_method" id="switch-'.$pName.'" onclick="switchPage(\''.$pName.'\');">'.$pName.'</li>';
                $html .= '<div id="page-'.$pName.'" style="display: none;">';
                $html .= '<textarea  class="code" style="width: 100%" rows="40" cols="150" id="'.$title.'-xxpage" onfocus="aSelect(\''.$title.'-xxpage\');" onclick="aSelect(\''.$title.'-xxpage\');">'.htmlspecialchars($page).'</textarea>';
                $html .= '</div>';
            }//foreach

        }//foreach classes

        $chk = '<input type="checkbox" id="chk_aselect" checked="checked" /> <label for="chk_aselect">Auto select</label>';
        $classPanel = '<ul class="classpanel">'.$classPanel.'</ul>'.$chk;

        return '<table><tr valign="top"><td>'.$classPanel.'</td><td>'.$html.'</td></tr></table>';
    }//function

    /**
     *
     * @param $docComment string
     * @param $searchFor array
     * @return object
     */
    private function parseDocComment($docComment, $searchFor)
    {
        $DComm = new stdClass();
        $DComm->isStatic = false;
        $DComm->since = '';
        $DComm->return = '';
        $DComm->params = array();

        $comment = explode(NL, $docComment);
        foreach ($comment as $c)
        {
            foreach ($searchFor as $search)
            {
                if( strpos($c, $search))
                {
                    switch ($search)
                    {
                        case '@static':
                            $DComm->isStatic = true;
                            break;

                        case '@return':
                            preg_match ('/@return[\s\t](.*?\w)[\s\t](.+)/', $c, $matches);
                            $DComm->return = $c;
                            if( isset($matches[1]))
                            {
                                $DComm->return = str_replace($matches[1], '{{mark|'.$matches[1].'}}', $DComm->return);
                            }
                            $DComm->return = trim(str_replace('@return', "'''@return'''", $DComm->return));
                            break;

                        case '@since':
                            $DComm->since = trim(str_replace('@since', "'''@since'''", $c));
                            $DComm->since = str_replace('1.5', '{{JVer|1.5}}', $DComm->since);
                            $DComm->since = str_replace('1.6', '{{JVer|1.6}}', $DComm->since);
                            break;

                        case '@param':
                            preg_match ('/@param[\s\t](.+)[\s\t]\$(.*?\w)[\s\t](.+)/', $c, $matches);
                            if( isset($matches[1]) && isset($matches[2]) && isset($matches[3]))
                            {
                                $DComm->params['$'.trim($matches[2])] = array(
                                    'type'=>trim($matches[1])
                                , 'name'=>trim($matches[2])
                                , 'desc'=>trim($matches[3])
                                );
                            }
                            break;
                    }//switch
                }
            }//foreach
        }//foreach

        return $DComm;
    }//function

}//class
