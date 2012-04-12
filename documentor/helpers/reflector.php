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
    private $baseDir;

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

    /**
     * reflect a class
     *
     * @param string $path from JROOT
     * @param string $file filename
     */
    public static function reflect($sub_package, $sub_sub_package, $sub_sub_sub_package, $fileName, $output_format, $jVersion1, $jVersion2='')
    {
        $rawObject = new EasyObject();
        $rawObject->subPackage = $sub_package;
        $rawObject->subSubPackage = $sub_sub_package;
        $rawObject->subSubSubPackage = $sub_sub_sub_package;
        $rawObject->fileName = $fileName;
        $rawObject->classes = array();

        $fullPath = JPATH_ROOT.DS.'sources'.DS.'joomla'.DS.$jVersion1.DS.'libraries'.DS.'joomla';

        $baseDir = $fullPath;
        $fullPath .=( $sub_package ) ? DS.$sub_package : '';
        $fullPath .=( $sub_sub_package ) ? DS.$sub_sub_package : '';
        $fullPath .=( $sub_sub_sub_package ) ? DS.$sub_sub_sub_package : '';

        $fullPath .= DS.$fileName;

        $html = '';

        /*
         * Fake J!
         */
        fakeJ($sub_package, $sub_sub_package, $sub_sub_sub_package, $fileName);

        self::tryInc($baseDir, 'base'.DS.'object.php');
        self::tryInc( $baseDir, 'filesystem'.DS.'path.php');

        //--argh...
        $libDir = substr($baseDir, 0, strpos($baseDir, 'libraries') + strlen('libraries'));
        self::tryInc( $libDir, 'phpgacl'.DS.'gacl.php');
        self::tryInc( $libDir, 'phpgacl'.DS.'gacl_api.php');

        //-- 1.6
        self::tryInc($baseDir, 'base'.DS.'adapterinstance.php');
        if($sub_package == 'access' || $sub_package == 'application' && $fileName != 'model') {
            self::tryInc( $baseDir, 'application'.DS.'component'.DS.'model.php');
        }

        if($sub_package == 'updater' && $fileName != 'adapter.php') {
            self::tryInc($baseDir, 'base'.DS.'adapter.php');
        }

        if($sub_package == 'cache') {
            if($sub_sub_package == 'handler') {
                self::tryInc( $baseDir, 'cache'.DS.'cache.php');
            }
            if($sub_sub_package == 'storage') {
                self::tryInc( $baseDir, 'cache'.DS.'storage.php');
            }
        }

        if( $sub_package == 'database' && $sub_sub_package == 'database' ){
            self::tryInc( $baseDir, 'database'.DS.'database.php');
        }

        if($sub_package == 'document') {
            if($sub_sub_package == 'html' && $sub_sub_sub_package == 'renderer') {
                self::tryInc( $baseDir,'document'.DS.'renderer.php');
            }
            if($sub_sub_package == 'feed' && $sub_sub_sub_package == 'renderer') {
                self::tryInc( $baseDir, 'document'.DS.'renderer.php');
            }

            if( $fileName != 'document.php') {
                self::tryInc( $baseDir, 'document'.DS.'document.php');
            }
        }

        if( $sub_package == 'event' ) {
            self::tryInc( $baseDir, 'base'.DS.'observable.php');
            self::tryInc( $baseDir, 'base'.DS.'observer.php');
        }

        if( $sub_package == 'form' ) {
            self::tryInc( $baseDir, 'form'.DS.'formrule.php');
            self::tryInc( $baseDir, 'form'.DS.'formfield.php');
        }

        if($sub_package == 'html') {
            if($sub_sub_package == 'parameter' && $sub_sub_sub_package == 'element') {
                self::tryInc( $baseDir, 'html'.DS.'parameter'.DS.'element.php');
            }
            if($sub_sub_package == 'toolbar' && $sub_sub_sub_package == 'button') {
                self::tryInc( $baseDir, 'html'.DS.'toolbar'.DS.'button.php');
            }
            if($fileName == 'editor.php') {
                self::tryInc( $baseDir, 'base'.DS.'adapter.php');
            }
            if($fileName == 'parameter.php') {
                #            class JRegistry {}
            }
        }

        if($sub_package == 'template') {
            $patPath = JPATH_ROOT.DS.'libraries'.DS.'pattemplate';
            if($sub_sub_package == 'html' && $sub_sub_sub_package == 'renderer') {
            }
            if($sub_sub_package == 'feed' && $sub_sub_sub_package == 'renderer') {
            }

        }

        if( $sub_package == 'updater') {
            if( $fileName != 'updateadapter.php') {
                self::tryInc( $baseDir, 'updater'.DS.'updateadapter.php');
            }
        }
        if($sub_package == 'user') {
            if($fileName == 'authentication.php') {
                self::tryInc( $baseDir, 'base'.DS.'observable.php');
            }
        }
        #    if( $fileName != 'object.php'){ #class JObject {} }
        #   if( $fileName != 'event.php'){ #class JEvent {} }

        $allClasses = get_declared_classes();

        /*
         * WE INCLUDE A FILE !!
         * TODO whatelse ??
         */
        if( ! file_exists($fullPath))
        {
            echo 'FILE NOT FOUND'.$fullPath;

            RETURN FALSE;
        }
        include_once $fullPath;
        # @todo jimport...

        $foundClasses = array_diff(get_declared_classes(), $allClasses);

        //--Exeptions from the rules..
        if( ! count($foundClasses))
        {
            #			return 'No classes found - '.BR.$fileName;
            if($fileName == 'methods.php')
            {
                $foundClasses = array('JRoute', 'JText');
            }
            if($fileName == 'ini.php')
            {
                $foundClasses = array('JRegistryFormatINI');
            }
            if($fileName == 'Sef.php')
            {
                $foundClasses = array('JRegistryFormatINI');
            }
        }

        if( ! count($foundClasses))
        {
            $testMessage = '';
            //--Check for classes already loaded
            $coreTest = EasyFile::stripExt(EasyFile::getName($fullPath));
            $coreTest =( strtolower($coreTest) == 'helper' ) ? 'J'.$sub_package.'Helper' : 'J'.$coreTest;
            $testMessage .= $coreTest.BR;
            if( class_exists($coreTest))
            {
                $foundClasses = array($coreTest);
            }
            else
            {
                $coreTest =  'J'.$sub_package.JFile::stripExt(JFile::getName($fullPath));
                $testMessage .= $coreTest.BR;
                if( class_exists($coreTest))
                {
                    $foundClasses = array($coreTest);
                }
                else
                {
                    if( $sub_sub_package )
                    {
                        $coreTest = 'J'.$sub_sub_package.JFile::stripExt(JFile::getName($fullPath));

                    }
                    if( class_exists($coreTest))
                    {
                        $foundClasses = array($coreTest);
                    }
                    else
                    {
                        return 'No classes found - '.BR.$testMessage;
                    }
                }
            }
        }

        foreach ($foundClasses as $clas)
        {
            $theClass = new ReflectionClass($clas);

            $rawObject->classes[] = $theClass;

            $comment = explode(NL, $theClass->getDocComment());
            $searches = array('static', 'subpackage', 'since');
            $subPackage = '';
            foreach ($comment as $c)
            {
                foreach ($searches as $search)
                {
                    if( strpos($c, '@'.$search))
                    {
                        #$wikiClassPage .= $c.NL;;
                        if( $search == 'subpackage')
                        {
                            $p =  strpos($c, $search);
                            $subPackage = trim(substr($c, strpos($c, $search)+strlen($search)));
                        }
                    }
                }//foreach
            }//foreach

            $subPackage =( $subPackage ) ? $subPackage : $sub_package;

            $theClass->subPackage = $sub_package;
            $theClass->subPackageName = $subPackage;
            $theClass->subSubPackage = $sub_sub_package;
            $theClass->subSubSubPackage = $sub_sub_sub_package;
            //
            //
            //			$cMethods =  $theClass->getMethods();
            //
            //			$indent = 0;
            //			$displayClassName = '';
            //			foreach ($cMethods as $cMethod)
            //			{
            //				$mPath = $cMethod->getFileName();
            //
            //				//--$this class or $that class ;)
            //				//--..base or extended
            //				//..also marks the extended extended classes orange.. TODO !
            //				$titel = sprintf(
            //                  "%s%s%s%s%s%s Method <strong style='color: orange;'>%s</strong>",
            //				$cMethod->isAbstract() ? ' abstract' : '',
            //				$cMethod->isFinal()       ? ' final' : '',
            //				$cMethod->isPublic()      ? ' <strong style="color: green">public</strong>' : '',
            //				$cMethod->isPrivate()  ? ' <strong style="color: orange">private</strong>' : '',
            //				$cMethod->isProtected()   ? ' <strong style="color: red">protected</strong>' : '',
            //				$cMethod->isStatic()      ? ' <strong style="color: black">static</strong>' : '',
            //				$cMethod->getName()
            //				);
            //				$pClass= $cMethod->getDeclaringClass();
            //				$declaringClass = $pClass->getName();
            //				if( $declaringClass != $displayClassName )
            //				{
            //
            //					$indent++;
            //					$html .= '<h1>';
            //					$html .= ( $displayClassName ) ? '<span style="color: orange">Extends</span>&nbsp;'.$declaringClass : $declaringClass;
            //					$html .= '</h1>';
            //					$displayClassName = $declaringClass;
            //					$html .= NL.'<h2>Methods</h2>';
            //
            //				}
            //				$paramString = array();
            //				$parameters = $cMethod->getParameters();
            //				}//foreach
            //				$paramString = implode(', ', $paramString);


        }//foreach

        if( ! file_exists(JPATHROOT.DS.'formats'.DS.$output_format.'.php'))
        {
            echo 'unknown format '.$output_format;

            return false;
        }

        require_once JPATHROOT.DS.'formats'.DS.$output_format.'.php';
        $className = 'ReflectorFormat'.$output_format;
        if( ! class_exists($className)) {
            printf('Required class %s not found', $className);

            return false;
        }

        $reflector = new $className();

        return $reflector->reflect($rawObject, $fullPath, $jVersion2);
    }//function



    private static function tryInc($base, $path)
    {
        if( ! EasyFile::exists($base.DS.$path)) { return false; }
        include_once($base.DS.$path);

        return true;
    }//function

}//class
