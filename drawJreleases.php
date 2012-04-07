<?php
/**
 * @version $Id: drawJreleases.php 512 2011-09-14 18:06:09Z elkuku $
 * @package     JFrameWorkDoc
 * @subpackage  External
 * @author		Nikolai Plath {@link http://www.nik-it.de}
 * @author		Created on 22-Jul-2009
 * @license    GNU/GPL, see JROOT/LICENSE.php
 */

error_reporting(E_STRICT);

define('JPATHROOT', dirname(__FILE__));

define('DS', DIRECTORY_SEPARATOR);
defined('BR') or define('BR', '<br />');
defined('NL') or define('NL', "\n");
#print_r($_REQUEST);
require_once JPATHROOT.DS.'helpers'.DS.'html.php';

$display = new EasyProjectDisplay();

$ID =(isset($_REQUEST['id'])) ? $_REQUEST['id'] : 0;
$ID = (string) preg_replace( '/[^0-9\.]/i', '', $ID );
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.w3.org/MarkUp/SCHEMA/xhtml11.xsd"
	xml:lang="de-de">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="robots" content="index, follow" />
<meta name="keywords" content="joomla, Joomla" />
<meta name="description"
	content="Joomla! - dynamische Portal-Engine und Content-Management-System" />
<meta name="generator"
	content="Joomla! 1.5 - Open Source Content Management" />
<title>Draw JReleases4Wiki</title>

<link href="/assets/images/jfavicon_t.ico" rel="shortcut icon"
	type="image/x-icon" />

<link rel="stylesheet" href="assets/css/default.css" type="text/css" />
</head>

<body>
<div id="outerx">
<div id="homeLink"><a href="index.php">Home</a></div>
<h3 style="float: right;">Downloads provided by <a
	href="http://joomlacode.org">JoomlaCode.org</a></h3>

<div><img src="assets/images/joomla_logo_black.jpg" alt="Joomla! Logo" />
Releases
<form action="drawJreleases.php">
<div>Joomla! Version: <select name="id" onchange="form.submit();" style="font-size: 1.4em;">
	<option value="0">Select a version...</option>
	<?php
	foreach($display->getReleases() as $idName => $idId)
	{
	    $selected =($idName == $ID) ? ' selected="selected"' : '';
	    echo '<option value="'.$idName.'"'.$selected.'>'.$idName.'</option>';
	}//foreach
	?>
</select>
<noscript>
<div style="display: inline;"><input type="submit" value="Submit" /></div>
</noscript>
</div>
</form>

<p>This cURLs the download page on <a href="http://joomlacode.org">Joomla!Code.org</a>,
extracts the "valuable information" and translates it to HTML and Wiki
syntax.</p>

<div style="background-color: #eee;"><?php
if( ! $ID)
{
    echo 'Please select a version...';
}
else
{
    $releases = $display->getReleases();

    if( ! array_key_exists($ID, $releases))
    {
        echo 'unknown version...';
    }
    else
    {
        foreach($releases[$ID] as $releaseID)
        {
            $display->drawRelease($releaseID);
        }//foreach
    }
}
?></div>
</div>
<h3 style="text-align: center;">Downloads provided by <a
	href="http://joomlacode.org">JoomlaCode.org</a></h3>

<?php EasyHtml::footer(); ?></div>
</body>

</html>
<?php
/*
 * END...
 */

class EasyProjectDisplay
{
    private $JReleases = array(
    '2.5.4' => array(16914, 16915)
    ,  '2.5.3' => array(16804, 16803)
     , '1.7.1' => array(15752, 15751)
    ,  '1.7.0' => array(15278, 15279)
    , '1.6.6' => array(15379, 15378)
    , '1.6.5' => array(15179, 15178)
    , '1.6.4' => array(15063, 15064)
    , '1.6.3' => array(14659, 14658)
    , '1.6.2' => array(14589, 14590)
    , '1.6.1' => array(14236, 14237)
    , '1.5.26' => array(16890, 16891)
    , '1.5.25' => array(16026, 16025)
    , '1.5.23' => array(14506, 14505)
    , '1.5.22' => array(13105, 13106)
    , '1.5.21'=>array(13034, 12974)
    , '1.5.20'=>array(12610, 12611)
    , '1.5.19'=>array(12583, 12584)
    , '1.5.18'=>array(12350, 12351)
    , '1.5.17'=>array(12193, 12192)
    , '1.5.16'=>array(12153, 12154)
    , '1.5.15'=>array(11396, 11395)
    , '1.5.14'=>array(10785, 10786)//4734)
    ,'1.5.13'=>array(10697, 10696)//4712)
    , '1.5.12'=>array(10547, 10548)//4665)
    , '1.5.11'=>array(10209, 10208)//4556)
    , '1.5.10'=>array(9910, 9911)//4460)
    , '1.5.9'=>array(9294, 9293)//4288)
    , '1.5.8'=>array(8897, 8898)//4136)
    ///// '1.5.7'=>3941
    , '1.5.6'=>array(8232, 8233)//3883)
    //, '1.5.5'=>3846
    , '1.5.4'=>array(7926, 7925)//3786)
    , '1.5.3'=>array(7369, 7370)//3587)
    , '1.5.2'=>array(7061, 7060)//3466)
    , '1.5.1'=>array(6731, 6732)//3322)
    , '1.5.0'=>array(5078)//2)
    );

    function __construct()
    {
    }//function

    public function getReleases()
    {
        return $this->JReleases;
    }//function

    public function drawRelease($ID)
    {
        $versionlinks = array();
        $updateLinks = array();
        $options = array(
        'baseURL'=>'http://joomlacode.org'
        , 'project'=>'joomla'
        );

        foreach($this->JReleases as $name => $ids)
        {
            if(in_array($ID, $ids))
            {
                $version = $name;

                break;
            }
            else
            {
                $version = 'unknown';
            }
        }//foreach

        $options['pkgID'] = $ID;

        $package = $this->getPackage($options);
        $regex = '/Joomla_(.*?)_to_/';

        foreach($package->items as $item)
        {
            $link = $item->link;

            if( ! strpos($link, 'download')) continue;

            $ext = substr($link, strrpos($link, '.') + 1);
            $ext =($ext == 'gz') ? 'tgz' : $ext;

            if(strpos($link, 'Stable-Full_Package'))
            {
                $versionLinks[$version][$ext]['link'] = $link;
                $versionLinks[$version][$ext]['md5'] = $item->md5;
            }
            elseif(strpos($link, 'Stable-Patch_Package'))
            {
                preg_match('/Joomla_(.*?)_to_/',$link, $matches);
                $updateLinks[$version][$matches[1]][$ext]['link'] = $link;
                $updateLinks[$version][$matches[1]][$ext]['md5'] = $item->md5;
            }

            if(isset($matches[1]))
            {
                arsort($updateLinks[$version][$matches[1]]);
            }
        }//foreach

        uksort($updateLinks[$version], 'EasyProjectDisplay::versionSort');

        echo '<hr /><h2>HTML</h2>';

        $html = '';
        $html .= '<div style="background-color: #ccffcc; padding: 1em;">';
        $html .= '<ul>'.NL;
        $html .= '<li class="version">Joomla! '.$version;

        foreach($versionLinks[$version] as $vExt => $vLink)
        {
            $html .= NL.'&nbsp;&bull;&nbsp;<a href="'.$vLink['link'].'">'.$vExt.'</a>';
            $html .= '<small><small><small><small>&nbsp;'.$vLink['md5'].'</small></small></small></small>';
        }//foreach

        $html .= '</li>';

        $html .= NL.'</ul>';

        $html .= '</div>';

        if(count($updateLinks[$version]))
        {

            $html .= NL.'<h3>Updates</h3>';
            $html .= '<div style="background-color: #ccffcc; padding: 1em;">';
            $html .= NL.'<ul>';

            foreach ($updateLinks[$version] as $uVersion=>$uLinks)
            {
                $html .= NL.'<li class="version">Update '.$uVersion.' => '.$version;

                foreach ($uLinks as $uExt => $uLink)
                {
                    $html .= NL.'&nbsp;&bull;&nbsp;<a href="'.$uLink['link'].'">'.$uExt.'</a>';
                    $html .= '<small><small><small><small>&nbsp;'.$uLink['md5'].'</small></small></small></small>';
                }//foreach

                $html .= '</li>';
            }//foreach

            $html .= NL.'</ul>';
            $html .= '</div>';
        }

        echo $html;
        echo '<textarea style="width: 100%; height: 100px;" cols="1000" rows="1000">'.htmlentities($html).'</textarea>';

        echo '<hr /><h2>Wiki</h2>';
        echo '<textarea style="width: 100%; height: 100px;" cols="1000" rows="1000">';

        #$stable =(strpos())
        if(count($versionLinks[$version]))
        {
            echo "== Joomla! $version Stable ==".NL;
        }

        foreach ($versionLinks[$version] as $vExt => $vLink)
        {
            echo '* ';
            echo ' ['.$vLink['link'].' Joomla! '.$version.'.'.$vExt.'] ';
            echo '<small><small><small><small>&nbsp;'.$vLink['md5'].'</small></small></small></small> '.NL;
        }//foreach

        foreach ($updateLinks[$version] as $uVersion=>$uLinks)
        {
            echo NL."*Update von '''$uVersion'''";
            foreach ($uLinks as $uExt => $uLink)
            {
                #    echo " [$uLink $uExt]";
                echo ' ['.$uLink['link'].' '.$uExt.'] ';
                echo '<small><small><small><small>&nbsp;'.$uLink['md5'].'</small></small></small></small> ';
            }//foreach
        }//foreach

        echo '</textarea>';
        echo '<hr /><h2>RAW</h2><hr />';
        echo $package->string;
        echo '<!-- ENDRAW -->';
    }//function

    /**
     * Enter description here...
     *
     * @param unknown_type $options
     * @return unknown
     */
    private static function getPackage($options)
    {
        #     $url = $options['baseURL'].'/gf/project/'.$options['project'].'/frs/?action=FrsReleaseBrowse&frs_package_id='.$options['pkgID'];
        $url = $options['baseURL'].'/gf/project/'.$options['project'].'/frs/?action=FrsReleaseView&release_id='.$options['pkgID'];
        #echo $url;
        $result = self::get_web_page( $url );
        $content = $result['content'];
        #echo '<pre>'.htmlentities($content).'</pre>';
        preg_match("~<div class=\"tabbertab\" title=\"Files\" id=\"filestab\">(.*)<div class=\"tabbertab\" title=\"Associated Tracker Items\" id=\"trackeritemstab\">~smU",$content, $matches);
        #       preg_match("~<div class=\"main\">(.*)<div class=\"paginator\">~smU",$content, $matches);
        $resultString = (isset($matches[1])) ? $matches[1] : '';

        preg_match_all("~<map.*>(.*)</map>~smU", $resultString, $matches);
        $k = 0;
        foreach ($matches[0] as $m)
        {
            $resultString = str_replace($m, '', $resultString);
        }//foreach

        preg_match_all("~<img.*>~smU",$resultString, $matches);
        $k = 0;
        foreach ($matches[0] as $m)
        {
            $resultString = str_replace($m, '', $resultString);
        }//foreach

        preg_match_all("~<table(.*)>~smU",$resultString, $matches);
        foreach ($matches[1] as $m)
        {
            $resultString = str_replace($m, '', $resultString);
        }//foreach

        preg_match_all("~<th(.*)>~smU",$resultString, $matches);
        foreach ($matches[1] as $m)
        {
            $resultString = str_replace($m, '', $resultString);
        }//foreach

        preg_match_all("~<tr(.*)>~smU",$resultString, $matches);
        foreach ($matches[1] as $m)
        {
            $resultString = str_replace($m, '', $resultString);
        }//foreach

        //      preg_match_all("~<td(.*)>~smU",$resultString, $matches);
        //      foreach ($matches[1] as $m)
        //      {
        //          $resultString = str_replace($m, '', $resultString);
        //      }//foreach

        preg_match_all("~<div.*>(.*)<\/div>~smU",$resultString, $matches);
        foreach ($matches[0] as $m)
        {
            $resultString = str_replace($m, '', $resultString);
        }//foreach

        $resultString = str_replace(array('nowrap="nowrap" ', 'bgcolor="#FFFFFF" '), '', $resultString);
        $resultString = str_replace(array('<p>', '</p>'), '', $resultString);
        $resultString = str_replace(array('<br />', '<br/>', '<strong>', '</strong>'), '', $resultString);
        $resultString = str_replace('</div>', '', $resultString);

        $resultString = str_replace('<table', '<table width="100%"', $resultString);

        $resultString = str_replace('a href="', 'a target="_blank" href="'.$options['baseURL'], $resultString);

        $buus = array(' valign="top"', ' target="_blank"');

        $resultString = str_replace($buus, '', $resultString);

        $lines = explode("\n", $resultString);

        $items = array();

        foreach($lines as $line)
        {
            if( ! strpos($line, '<tr><td >')) continue;

            $parts = explode('</td><td >', $line);

            $i = new stdClass();
            $regex = '/href\s*=\s*\"*([^\">]*)/i';
            preg_match_all($regex, $parts[0], $matches);
            $i->link = $matches[1][0];
            $i->size = $parts[1];
            $i->downloads = $parts[2];
            $i->md5 = str_replace('</td>', '', $parts[3]);

            $items[] = $i;

        }//foreach
        #echo '<pre>'.htmlentities($resultString).'</pre>';

        $regex = '/href\s*=\s*\"*([^\">]*)/i';
        preg_match_all($regex, $resultString, $matches);

        $links =( $matches[1] ) ? $matches[1] : array();
        $ret = new stdClass();
        $ret->string = $resultString;
        $ret->links = $links;
        $ret->items = $items;

        return $ret;
    }//function

    /**
     * Get a web file (HTML, XHTML, XML, image, etc.) from a URL.  Return an
     * array containing the HTTP server response header fields and content.
     */
    private static function get_web_page( $url )
    {
        $options = array(
        CURLOPT_RETURNTRANSFER => true,     // return web page
        CURLOPT_HEADER         => false,    // don't return headers
        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
        CURLOPT_ENCODING       => "",       // handle all encodings
        CURLOPT_USERAGENT      => "spider", // who am i
        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
        CURLOPT_TIMEOUT        => 120,      // timeout on response
        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
        );
        if ( ! function_exists('curl_setopt_array'))
        {
            //--For PHP 5.1.4
            function curl_setopt_array(&$ch, $curl_options)
            {
                foreach ($curl_options as $option => $value)
                {
                    if (!curl_setopt($ch, $option, $value))
                    {
                        return false;
                    }
                }//foreach
                return true;
            }
        }

        $ch = curl_init( $url );
        curl_setopt_array( $ch, $options );
        $content = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );

        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $content;

        return $header;
    }//function

    /**
     * Custom sort callback
     * @param string $a
     * @param string $b
     * @return true if $a < $b
     */
    private static function versionSort($a, $b)
    {
        $vs = explode('.', $a);
        $v1 = $vs[2];

        $vs = explode('.', $b);
        $v2 = $vs[2];

        return $v1 < $v2;
    }//function

}//class
