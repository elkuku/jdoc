<?php
/**
 * @package    J!Doc
 * @subpackage Documentor
 * @author     Nikolai Plath {elkuku}
 * @author     Created on 23-Jun-2009
 * @license    GNU/GPL, see JROOT/LICENSE.php
 */

// We are a valid Joomla entry point.
define('_JEXEC', 1);

// Setup the base path related constant.
define('JPATH_BASE', dirname(__FILE__));
define('JPATH_SITE', JPATH_BASE);
define('JPATH_THEMES', JPATH_BASE.'/themes');

define('JPATH_BULD', dirname(dirname(__FILE__)).'/build');

define('NL', "\n");
define('BR', '<br />');

// Increase error reporting to that any errors are displayed.
// Note, you would not use these settings in production.
error_reporting(E_ALL);
ini_set('display_errors', true);

// Bootstrap the application.
$platformPath = dirname(dirname(__FILE__)).'/sources/joomla-platform/11.4';
require $platformPath.'/libraries/import.php';

require 'helpers/jdoxreader.php';

/**
 * An example Joomla! web application class.
 *
 * @package JDoc
 */
class JDoc extends JApplicationWeb
{
    private function showClass()
    {
        $input = JFactory::getApplication()->input;

        $jTarget = $input->get('jtarget', 'joomla-cms');
        $version = $input->get('version', '2.5.4');
        $path = $input->get('path', '', 'path');
        $className = $input->get('className');

        echo JDocsReader::reflectClass($jTarget, $version, $path, $className);
    }

    /**
     * Overrides the parent doExecute method to run the web application.
     *
     * This method should include your custom code that runs the application.
     *
     * @return  void
     */
    protected function doExecute()
    {
        $input = JFactory::getApplication()->input;

        $task = $input->get('task');

        switch($task)
        {
            case 'show_class' :
                require 'helpers/reflector.php';

                $this->showClass();
                return;
                break;
        }

        $jTarget = 'joomla-cms';
        $version = '2.5.4';

        $path = JPATH_BULD.'/'.$jTarget.'/'.$version.'/xml/classes.xml';
        $classList = JDocsReader::parseClassList($path);

        $html = array();
        $html[] = '<h1>'.ucfirst($jTarget).' '.$version.'</h1>';
        $html[] = '<div id="pane-selector" class="php-file-tree">';
        $html[] = '   <ul>';

        foreach($classList as $library => $packages)
        {
            ksort($packages);
            $html[] = '      <li class="pft-directory"><div>'.$library.'</div>';
            $html[] = '         <ul>';

            foreach($packages as $package => $classes)
            {
                ksort($classes);
                $html[] = '            <li class="pft-directory"><div>'.$package.'</div>';
                $html[] = '               <ul>';
                foreach($classes as $name => $class)
                {
                    $id = $name;
                    $d = $class->attributes()->xml;
                    $f = $name;

                    $html[] = "                  <li class=\"pft-file ext-joo\" id=\"tl_$id\""
                        ." onclick=\"load_file('$d', '$f', 'tl_$id');\">$name</li>";
                }

                $html[] = '               </ul>';
                $html[] = '            </li>';
            }

            $html[] = '         </ul>';
            $html[] = '      </li>';
        }

        $html[] = '   </ul>';
        $html[] = '</div>';
        $html[] = '<div id="pane-code">';
        $html[] = '<div id="jdocDisplay"></div>';
        $html[] = '</div>';
        $html[] = '<div style="clear: both;"></div>';

        $output = implode(NL, $html);

        $this->document->setTitle('J!Doc Documentor');
        $this->document->setBuffer($output, array('type' => 'component', 'name' => 'main'));
    }

    /**
     * Method to get the template name. This is needed for compatability with JApplication.
     *
     * @return string The theme name.
     *
     * @since 12.1
     */
    public function getTemplate()
    {
        return $this->get('theme');
    }
}

try
{

// Instantiate the application object, passing the class name to JApplicationWeb::getInstance
// and use chaining to execute the application.
// Instantiate the application.
    $application = JApplicationWeb::getInstance('JDoc');

// Initialise the application.
    $application->initialise();

// Store the application.
    JFactory::$application = $application;

// Execute the application.
    $application->execute();
}
catch(Exception $e)
{
    echo $e->getMessage();
}
return;

//--DEBUG
#error_reporting(E_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/Los_Angeles');
define('DEBUG', 1);

define('DS', DIRECTORY_SEPARATOR);
define('NL', "\n");
define('BR', '<br />');

define('JVERSION', '1.5.26');
define('JPATHROOT', dirname(__FILE__));
define('JPATH_ROOT', JPATHROOT);
define('JPATH_BASE', '');
define('JPATH_LIBRARIES', '');
define('_JEXEC', 1);

define('PATH_SOURCES', dirname(__DIR__).'/sources');

require_once JPATHROOT.DS.'helpers'.DS.'object.php';
require_once JPATHROOT.DS.'helpers'.DS.'request.php';
require_once JPATHROOT.DS.'helpers'.DS.'filesystem.php';
require_once JPATHROOT.DS.'helpers'.DS.'jfaker.php';
require_once JPATHROOT.DS.'helpers'.DS.'reflector.php';
require_once JPATHROOT.DS.'helpers'.DS.'html.php';

$outputFormats = array(
    'html' => 'HTML'
, 'wikinafu' => 'wiki.joomla-nafu.de'
, 'docswiki' => 'docs.joomla.org'
, 'source' => 'Source Code'
, 'compare' => 'Compare Versions'
);

$jTarget = 'joomla-cms';

if(! is_dir(PATH_SOURCES.'/'.$jTarget))
{
    echo '<h1>No Joomla! Versions found :(</h1>';

    return;
}

$jVersionDirs = EasyFolder::folders(PATH_SOURCES.'/'.$jTarget);

natsort($jVersionDirs);

$fileName = JPATH_ROOT.DS.'svn_info';

if(file_exists($fileName))
{
    $lines = file($fileName);

    foreach($lines as $line)
    {
        if(strpos($line, 'Revision') === 0)
        {
            preg_match('/-?[0-9]+/', (string)$line, $matches);
            $LatestRevNum = (int)$matches[0];
            $LatestRev = $line;
            break;
        }
    }
    //foreach
}

$jVersion = EasyRequest::getVar('j_version', JVERSION);
$jVersion2 = EasyRequest::getVar('j_version2', JVERSION);

if(! in_array($jVersion, $jVersionDirs))
{
    $jVersion = JVERSION;
}

if(! in_array($jVersion2, $jVersionDirs))
{
    $jVersion2 = JVERSION;
}

$fName = 'jclasslist_'.str_replace('.', '_', $jVersion).'.php';

if(! file_exists(JPATH_ROOT.DS.'sources'.DS.'joomla'.DS.$fName))
{
    //-- Class list NOT FOUND
    //-- Build it !
    $host = $_SERVER['HTTP_HOST'];
    $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $extra = 'sources/jmethodlister.php?jver='.$jVersion;
    header("Location: http://$host$uri/$extra");

    return;
}

require_once JPATH_ROOT.DS.'sources'.DS.'joomla'.DS.$fName;
$cList = getJoomlaClasses();
$packages = getJoomlaPackages();

$baseDir = JPATH_ROOT.DS.'sources'.DS.'joomla'.DS.$jVersion.DS.'libraries'.DS.'joomla';

$output_format = EasyRequest::getVar('output_format', 'html');
$task = isset($_REQUEST['task']) ? $_REQUEST['task'] : '';

$path = EasyRequest::getVar('path');
$file = EasyRequest::getVar('file');
$ps = explode('/', $path);
$pSub = (isset($ps[0])) ? $ps[0] : '';
$pSubSub = (isset($ps[1])) ? $ps[1] : '';
$pSubSubSub = (isset($ps[2])) ? $ps[2] : '';

switch($task)
{
    case 'show_class':
        echo EasyReflector::reflect($pSub, $pSubSub, $pSubSubSub, $file, $output_format, $jVersion, $jVersion2);

        return;
        break;

    default:
        ;
        break;
}

require_once JPATH_ROOT.DS.'helpers'.DS.'php_file_tree.php';
$jsFile = "onclick=\"load_file('[folder]', '[file]', '[id]');\"";
$fileTree = new phpFileTree($baseDir, '', $jsFile, '', array('php'));
$fileTree->filesExclude = array('import.php');
$fileTree->replacePath = $baseDir.DS;
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://www.w3.org/MarkUp/SCHEMA/xhtml11.xsd"
      xml:lang="de-de">

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <meta name="robots" content="index, follow"/>
    <meta name="keywords" content="joomla, Joomla"/>
    <meta name="description"
          content="Joomla! - dynamische Portal-Engine und Content-Management-System"/>
    <meta name="generator"
          content="Joomla! 1.5 - Open Source Content Management"/>
    <title>JFrameworkDocumentor</title>
    <link href="assets/images/jfavicon_t.ico" rel="shortcut icon"
          type="image/x-icon"/>

    <script type="text/javascript" src="assets/js/mootools123-core-nc.js"></script>
    <script type="text/javascript" src="assets/js/mootools123-more-nc.js"></script>

    <script type="text/javascript" src="assets/js/jframeworkdoc.js"></script>
    <script type="text/javascript" src="assets/js/php_file_tree.js"></script>
    <link rel="stylesheet" href="assets/css/default.css" type="text/css"/>
    <link rel="stylesheet" href="assets/css/php_file_tree.css"
          type="text/css"/>
    <link rel="stylesheet" href="assets/css/diff.css" type="text/css"/>
</head>

<body>
<?php echo (isset($_REQUEST['mlist_built'])) ? '<span style="color: green;">Method list for '.$jVersion.' has been built</span>'.BR : ''; ?>
<div>

<form action="jdoc.php" method="post" id="form-jdocForm">

<table width="100%">
<tr valign="top">
<td><span class="img icon-16-joomla" style="font-weight: bold;">Framework</span>

    <div class="php-file-tree">
        <ul>
            <li class="pft-directory">
                <div style="font-size: 1.3em;">By Name</div>
                <ul>
                    <?php
                    $ltr = '';
                    $id = 0;

                    foreach($cList as $cName => $cl)
                    {
                        $t = substr($cName, 1, 1);

                        if($t != $ltr)
                        {
                            if($ltr)
                            {
                                echo EasyHtml::idt('-').'</ul>';
                                echo EasyHtml::idt('-').'</li>';
                            }

                            $title = (substr($cName, 0, 1) == 'J') ? strtoupper($t) : $cName;
                            echo EasyHtml::idt('+').'<li class="pft-directory"><div style="font-size: 1.2em; font-weight: bold;">'.$title.'</div>';
                            echo EasyHtml::idt('+').'<ul>';
                            $ltr = $t;
                        }

                        if(strpos($cl[1], DS))
                        {
                            $d = substr($cl[1], 0, strrpos($cl[1], DS) + 1);
                            $f = substr($cl[1], strrpos($cl[1], DS) + 1);
                        }
                        else
                        {
                            $d = '';
                            $f = $cl[1];
                        }

                        echo EasyHtml::idt()."<li class=\"pft-file ext-joo\" id=\"tl_$id\" onclick=\"load_file('$d', '$f', 'tl_$id');\">$cName</li>";
                        $id ++;
                    }//foreach
                    echo EasyHtml::idt('-').'</ul>';
                    echo EasyHtml::idt('-').'</li>';
                    ?>

                </ul>
            </li>
        </ul>
    </div>

    <div class="php-file-tree">
        <ul>
            <li class="pft-directory">
                <div style="font-size: 1.3em;">By Package</div>
                <ul>
                    <?php
                    natcasesort($packages);
                    $base = 'sources'.DS.'joomla'.DS.$jVersion.DS.'libraries'.DS.'joomla';
                    foreach($packages as $pName)
                    {
                        echo EasyHtml::idt('+').'<li class="pft-directory"><div>'.$pName.'</div>';
                        echo EasyHtml::idt('+').'<ul>';

                        foreach($cList as $cName => $cl)
                        {
                            if($cl[0] != $pName)
                            {
                                continue;
                            }
                            if(strpos($cl[1], DS))
                            {
                                $d = substr($cl[1], 0, strrpos($cl[1], DS) + 1);
                                $f = substr($cl[1], strrpos($cl[1], DS) + 1);
                            }
                            else
                            {
                                $d = '';
                                $f = $cl[1];
                            }
                            echo EasyHtml::idt()."<li class=\"pft-file ext-joo\" id=\"tl_$id\" onclick=\"load_file('$d', '$f', 'tl_$id');\">$cName</li>";
                            $id ++;
                        }
                        //foreach
                        echo EasyHtml::idt('-').'</ul>';
                        echo EasyHtml::idt('-').'</li>';
                    }//foreach
                    ?>
                </ul>
            </li>
        </ul>
    </div>

    <?php $fileTree->linkId = $id; ?> <?php echo $fileTree->startTree(); ?>
    <ul>
        <li class="pft-directory">
            <div style="font-size: 1.3em;">By File</div>
            libraries/joomla <?php echo $fileTree->drawTree(); ?></li>
    </ul>
    <?php echo $fileTree->endTree(); ?></td>
<td>

    <div style="float: right; text-align: right;">
        <div id="homeLink" style="margin-bottom: 1em;"><a href="index.php">Home</a></div>
        <div style="text-align: left;">Output format</div>
        <select name="output_format" style="font-size: 1.2em;"
                id="output_format" onchange="$('form-jdocForm').submit();">
            <?php
            foreach($outputFormats as $fName => $fFormat)
            {
                $selected = ($output_format == $fName) ? ' selected="selected"' : '';

                echo '        <option value="'.$fName.'"'.$selected.'>'.$fFormat.'</option>'.NL;
            }//foreach
            ?>
        </select> <br/>
        <span class="img icon-16-joomla">Version</span> <select
        style="font-size: 1.2em;" name="j_version" id="j_version"
        onchange="$('form-jdocForm').submit();">
        <?php
        $verFirst = '';
        foreach($jVersionDirs as $dir)
        {
            if(strpos($dir, 'install_sql')) continue;
            if(! $verFirst) $verFirst = $dir;

            $selected = ($dir == $jVersion) ? ' selected="selected"' : '';
            $d = ($dir == 'trunk') ? $dir.' #'.$LatestRevNum : $dir;
            echo '        <option value="'.$dir.'"'.$selected.'>'.$d.'</option>'.NL;
        }
        $verLast = $dir;
        ?>
    </select> <br/>
        <?php
        switch($output_format)
        {
            case 'compare':
                ?> Compare to: <select style="font-size: 1.2em;"
                                       id="j_version2" name="j_version2"
                                       onchange="$('form-jdocForm').submit();">
                    <?php
                    foreach($jVersionDirs as $dir)
                    {
                        $selected = ($dir == $jVersion2) ? ' selected="selected"' : '';

                        echo '    <option value="'.$dir.'"'.$selected.'>'.$dir.'</option>'.NL;
                    }//foreach
                    ?>
                </select> <?php
                break;

            case 'source':
                echo '<label for="use_geshi">Use GeSHi: </label><input type="checkbox" id="use_geshi" checked="checked" >'.BR.NL;
                break;
        }//switch

        ?> <a id="permaLink" href="<?php echo ($path) ? '' : '#'; ?>">Perma link</a></div>

    <img src="assets/images/joomla_logo_black.jpg" alt="Joomla! Logo"/> <span
    style="font-size: 1.8em; font-weight: bold;">FrameworkDocumentor</span>
    <br/>

    <noscript>
        <h1 style="color: red; font-size: 3em;">Activate Javascript... please
            <tt>=;)</tt></h1>
    </noscript>

    <div style="clear: both;"></div>
    <div id="jdocDisplay" class="jdocDisplay" style="width: 100%">
        <?php if($path || $file)
    {
        echo EasyReflector::reflect($pSub, $pSubSub, $pSubSubSub, $file, $output_format, $jVersion, $jVersion2);
        $page = EasyRequest::getVar('page');

        if($page)
        {
            echo "<script>
                $('page-$page').setStyle('display', 'block');
            	$('switch-$page').setStyle('color', 'blue');
            	openedDiv = '$page'</script>";
        }
    }
    else
    {
        ?>
        <p><img src="assets/images/welcome1.png"
                alt="Welcome my fellow documentors"/> <br/>
            Here you will find a complete list of the <a href="http://joomla.org"
                                                         class="external">Joomla!</a> Framework classes from Version
            <strong
                style="color: orange;"><?php echo $verFirst; ?></strong> up to
            Version <strong
                style="color: orange;"><?php echo $verLast.' '; echo (isset($LatestRev)) ? $LatestRev : '';?></strong>.
        </p>
        <p>This <em>"Thingy"</em> has been made to help documenting the
            Joomla! Framework on <a
                href="http://wiki.joomla-nafu.de/joomla-dokumentation/Joomla!_Programmierung/Framework"
                class="external">wiki.joomla-nafu.de</a> and <a
                href="http://docs.joomla.org/Framework" class="external">docs.joomla.org</a>
        </p>
        <p><em>You should select your desired <strong>output format</strong>
            first.</em> &rArr;<small> Current is: <strong><?php echo $outputFormats[$output_format]; ?></strong></small>
        </p>
        After that you have to choose... <br/>
        <br/>
        <h1 style="text-align: center; color: blue;">What's your Class,
            (wo)man ?<br/>
            <br/>
            <tt>=;)</tt></h1>
        <?php } ?>
    </div>
</td>
</tr>
</table>

</form>

</div>

<div class="easy_footer"><a class="toplink" href="#">Top</a>

    <div class="valid_xhtml"><a
        href="http://validator.w3.org/check?uri=referer" class="external">XHTML
        1.1</a><br/>
        <a href="http://jigsaw.w3.org/css-validator/check/referer"
           class="external">CSS 2.1</a></div>
    Developed 2009 by <a href="http://joomlacode.org/gf/project/elkuku" class="external">El KuKu</a> <br/>
    <small>rev# <?php echo EasyHtml::getVersionFromFile(JPATH_ROOT.DS.'CHANGELOG.php')?></small>
    &bull;&bull;&bull; <em>Have FUN <tt>=;)</tt></em>

    <div style="clear: both;"></div>
    <div style="float: left; text-align: left;">See also:<br/>
        Joomlacode.org <a href="drawJreleases.php" style="font-size: 1em;">DownloadLister</a>
        Joomla! framework <a href="doccommenterrors.html"
                             style="font-size: 1em;">DocComment errors</a></div>
    <div style="float: right;">
        <small><strong style="color: red;">Oh...</strong>
            if you like, you can also<br/>
            <a class="jfd_dl" href="http://anonymous@joomlacode.org/svn/elkuku/jdoc/trunk">download this thingy from
                SVN</a></small>
    </div>
    <div style="clear: both;"></div>
</div>
<?php #var_dump($_REQUEST); ?>
</body>

</html>
