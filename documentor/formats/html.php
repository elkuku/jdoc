<?php
/**
 * @version $Id: html.php 23 2010-11-08 19:30:35Z elkuku $
 * @package	JFrameWorkDoc
 * @subpackage	Formats
 * @author		Nikolai Plath (elkuku) {@link http://www.nik-it.de NiK-IT.de}
 * @author		Created on 24.09.2008
 */

//-- No direct access
defined( '_JEXEC') or die('=;)');

class ReflectorFormatHtml
{
    function reflect($rawDoc)
    {
        $html = '';

        foreach ($rawDoc->classes as $class)
        {
            $parent = $class->getParentClass();
            $parentName =( $parent ) ? ' extends <span style="color: orange;">'.$parent->name.'</span>':'';

            $html .= '<h1>';
            $html .= sprintf(
               "%s%s%s %s" ,
            $class->isInternal() ? '':'', // 'internal' : 'user-defined',
            $class->isAbstract() ? ' abstract' : '',
            $class->isFinal() ? ' final' : '',
            $class->isInterface() ? 'Interface' : 'Class'
            );
            $html .= ' <span style="color: blue;">'.$class->getName().'</span>'.$parentName;
            $html .= '</h1>';
            $html .= 'Defined in <tt class="defined_in">'.substr($class->getFileName(), strpos($class->getFileName(), 'libraries')).'</tt>';
            $html .= '<pre>'.$class->getDocComment().'</pre>';
            $html .= NL.'<h2>Properties</h2>';

            $comment = explode(NL, $class->getDocComment());
            $searches = array('static', 'subpackage', 'since');
            $subPackage = '';
            foreach ($comment as $c)
            {
                foreach ($searches as $search)
                {
                    if( strpos($c, '@'.$search))
                    {
                        if( $search == 'subpackage')
                        {
                            $p =  strpos($c, $search);
                            $subPackage = trim(substr($c, strpos($c, $search)+strlen($search)));
                        }
                    }
                }//foreach
            }//foreach

            $subPackage =( $subPackage ) ? $subPackage : $class->subPackage;

            $class->subPackage = $class->subPackage;
            $class->subPackageName = $class->subPackageName;
            $class->subSubPackage = $class->subSubPackage;

            $properties = $class->getProperties();
            foreach ($properties as $prop)
            {
                $property = $class->getProperty($prop->name);

                $html .= sprintf(
                  "%s%s%s%s <strong>%s</strong>",
                $property->isPublic() ? ' <strong style="color: green">public</strong>' : '',
                $property->isPrivate() ? ' <strong style="color: orange">private</strong>' : '',
                $property->isProtected() ? ' <strong style="color: red">protected</strong>' : '',
                $property->isStatic() ? ' <strong style="color: black">static</strong>' : '',
                $property->getName()

                );
                $html .= BR;
            }//foreach

            $cMethods =  $class->getMethods();

            $indent = 0;
            $displayClassName = '';
            foreach ($cMethods as $cMethod)
            {
                $titel = sprintf(
                  "%s%s%s%s%s%s <strong style='color: blue;'>%s</strong>",
                $cMethod->isAbstract() ? ' abstract' : '',
                $cMethod->isFinal()       ? ' final' : '',
                $cMethod->isPublic()      ? " <strong style='color: green'>public</strong>" : '',
                $cMethod->isPrivate()  ? " <strong style='color: orange'>private</strong>" : '',
                $cMethod->isProtected()   ? " <strong style='color: red'>protected</strong>" : '',
                $cMethod->isStatic()      ? " <strong style='color: black'>static</strong>" : '',
                $cMethod->getName()
                );
                $pClass= $cMethod->getDeclaringClass();
                $declaringClass = $pClass->getName();
                if( $declaringClass != $displayClassName )
                {
                    if( $declaringClass != $class->getName() )
                    {
                                    $parent = $pClass->getParentClass();
            $parentName =( $parent ) ? ' extends <span style="color: orange;">'.$parent->name.'</span>':'';

                    $indent++;
                    $html .= '<h1>';
                    $html .= ( $displayClassName ) ? 'Class <span style="color: orange">'.$declaringClass.'</span>'.$parentName : $declaringClass;
                    $html .= '</h1>';
                    $html .= 'Defined in <tt  class="defined_in">'.substr($pClass->getFileName(), strpos($pClass->getFileName(), 'libraries')).'</tt>';
                    }
                    $displayClassName = $declaringClass;
                    $html .= NL.'<h2>Methods</h2>';

                }
                $paramString = array();
                $parameters = $cMethod->getParameters();
                foreach( $parameters as $parameter )
                {
                    $s = '';
                    $s .= sprintf("%s<strong style='color: brown;'>$%s</strong>",
                    $parameter->isPassedByReference() ? '<strong style="color: blue;"> & </strong>' : '',
                    $parameter->getName()
                    );

                    if( $parameter->isDefaultValueAvailable())
                    {
                        $def = $parameter->getDefaultValue();
                        if( $def === null)
                        {
                            $s .= '=null';
                        }
                        else if( $def === false )
                        {
                            $s .= '=false';
                        }
                        else if( $def === true )
                        {
                            $s .= '=true';
                        }
                        else if( $def === array() )
                        {
                            $s .= '=array()';
                        }

                        else if( $def === '' )
                        {
                            $s .= '=\'\'';
                        }
                        else
                        {
                            $s .= '='.$parameter->getDefaultValue();
                        }
                    }

                    $paramString[] = $s;

                }//foreach
                $comment = $cMethod->getDocComment();
                $paramString = implode(', ', $paramString);
                $mHead = $titel.'( '.$paramString.' )'.BR;
                $cS = '';
                if( $comment )
                {
                    $dC = nl2br(htmlentities($comment));
                }
                else
                {
                    $cS = '_no';
                    $dC = 'No DocComment available';
                }

                $tip = ' title="'.htmlentities($mHead).' Lines # '.$cMethod->getStartLine().' - '.$cMethod->getEndLine().'"';
                $tip .= ' rel = "'.$dC.'"';
                $html .= '<span class="img comment'.$cS.' hasTip"'.$tip.'></span>';
                $html .= $mHead;

                if( strtolower($declaringClass) == strtolower($class->getName()) )
                {
                    $comment = explode(NL, $cMethod->getDocComment());
                    $searches = array('@return', '@since');
                    $syntaxAdds = '';
                    $hasReturn = false;
                    foreach ($comment as $c)
                    {
                        foreach ($searches as $search)
                        {
                            if( strpos($c, $search))
                            {
                                $c = str_replace($search, "'''".$search."'''", $c);
                                $syntaxAdds .= trim($c).NL;
                                if( $search == '@return'){ $hasReturn = true; }
                            }
                        }
                    }//foreach
                }
            }//foreach methods
        }//foreach classes

        return $html;
    }//function

}//class
