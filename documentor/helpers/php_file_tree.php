<?php
/**
 * @version $Id: php_file_tree.php 23 2010-11-08 19:30:35Z elkuku $
 * @package    JFrameWorkDoc
 * @subpackage Helpers
 * @author      Cory S.N. LaViska {@link http://abeautifulsite.net/}
 * @author      Nikolai Plath (elkuku) {@link http://www.nik-it.de NiK-IT.de}
 */

//--No direct access
defined('_JEXEC') or die(';)');

/**
 *  == PHP FILE TREE ==
 *  Let's call it...oh, say...version 1?
 * @author		Cory S.N. LaViska {@link http://abeautifulsite.net/}
 * For documentation and updates, visit
 * @documentation {@link http://abeautifulsite.net/notebook.php?article=21}
 *
 * @version Let's call this one... version 2 =;)
 * @author		Nikolai Plath (elkuku) {@link http://www.nik-it.de NiK-IT.de}
 */
class phpFileTree
{
    public $directory = '';

    public $href = '';

    public $jsFolder = '';

    public $jsFile = '';

    public $extensionsOnly = array();

    public $extensionsExclude = array();

    public $filesExclude = array();

    public $showExtension = true;

    public $reverse = false;

    public $indent = 0;

    /**
     * @var integer
     */
    public $linkId = 0;

    public $replacePath = '';


    /**
     *
     * @param $directory
     * @param $href
     * @param $jsFile
     * @param $extensionsOnly
     * @param $reverse
     * @return void
     */
    public function __construct($directory='', $href='', $jsFile = '', $jsFolder = '', $extensionsOnly = array(), $reverse = false)
    {
        $this->directory = $directory;
        $this->href = $href;
        $this->jsFile = $jsFile;
        $this->jsFolder = $jsFolder;

        $this->extensionsOnly = $extensionsOnly;
        $this->reverse = $reverse;
    }

    /**
     *
     * @return string
     */
    public function drawTree()
    {
        // Generates a valid XHTML list of all directories, sub-directories, and files in $directory
        // Remove trailing slash
        $d = $this->directory;
        if( substr($d, -1) == DS ) { $d = substr($d, 0, strlen($d) - 1); }

        return $this->scanDir($d);;
    }//function

    /**
     *
     * @return string
     */
    public function drawFullTree()
    {
        $r = '';
        $r .= $this->startTree();
        $d = $this->directory;
        if( substr($d, -1) == DS ) { $d = substr($d, 0, strlen($d) - 1); }

        $r .= $this->scanDir($d);
        $r .= $this->endTree();

        return $r;
    }

    /**
     *
     * @param string $directory
     * @return void
     */
    public function setDir($directory)
    {
        $this->directory = $directory;
    }//function

    /**
     *
     * @param string $type
     * @param string $js
     * @return void
     */
    public function setJs($type, $js)
    {
        switch($type)
        {
            case 'folder':
                $this->jsFolder = $js;
                break;

            case 'file':
                $this->jsFile = $js;
                break;
        }//switch
    }//function

    /**
     *
     * @return string
     */
    public function startTree()
    {
        $s = '';
        $s .= NL.'<!-- PHPFileTree Start -->';
        $s .= NL.'<div class="php-file-tree">';

        $this->indent = 0;

        return $s;
    }//function

    /**
     *
     * @return string
     */
    public function endTree()
    {
        $s = '';
        $s .= NL.'</div>';
        $s .= NL.'<!-- PHPFileTree End -->'.NL;
        return $s;

        return $s;
    }//function

    /**
     * Recursive function to list directories/files
     * @param string $directory
     * @return string
     */
    private function scanDir($directory)
    {
        // Get and sort directories/files
        $entries = scandir($directory);
        natcasesort($entries);
        if( $this->reverse ) $entries = array_reverse($entries);

        // Make directories first
        $files = $dirs = array();
        foreach($entries as $this_file) {
            if( is_dir($directory.DS.$this_file ) ) $dirs[] = $this_file; else $files[] = $this_file;
        }
        $entries = array_merge($dirs, $files);

        // Filter unwanted extensions
        if( ! empty($this->extensionsOnly) )
        {
            foreach( array_keys($entries) as $key )
            {
                if( ! is_dir($directory.DS.$entries[$key]) )
                {
                    $ext = substr($entries[$key], strrpos($entries[$key], '.') + 1);
                    if( ! in_array($ext, $this->extensionsOnly) ) unset($entries[$key]);
                }
            }//foreach
        }

        $php_file_tree = '';
        if( count($entries) > 2 )
        { // Use 2 instead of 0 to account for . and .. "directories"
            $php_file_tree .= $this->idtAdd();
            $php_file_tree .= '<ul>';
            $this->indent ++;
            foreach( $entries as $this_file )
            {
                if( $this_file != '.' && $this_file != '..' && $this_file != '.svn' )
                {
                    if( is_dir($directory.DS.$this_file) )
                    {
                        //-- Directory
                        $li =($directory == JPATH_ROOT) ? '' : str_replace(JPATH_ROOT.DS, '', $directory);
                        if( substr($li, -1) != DS ) $li .= DS;
                        $d = $this_file;
                        if( strpos($d, '-'))
                        {
                            $d = substr($d, strpos($d, '-') + 1);
                        }
                        $js = $this->parseLink($this->jsFolder, $li, $d);
                        $php_file_tree .= $this->idt();
                        $php_file_tree .= '<li class="pft-directory">';
                        if( $this->href )
                        {
                            $php_file_tree .= '<a href="javascript:"'.$js.'>'.htmlspecialchars($d).'</a>';
                        }
                        else
                        {
                            $php_file_tree .= '<div'.$js.'>'.htmlspecialchars($d).'</div>';
                        }

                        //-- Recurse...
                        $php_file_tree .= $this->scanDir($directory.DS.$this_file);

                        $php_file_tree .= $this->idtDel();
                        $php_file_tree .= '</li>';
                    }
                    else
                    {
                        //-- File
                        if( ! in_array($this_file, $this->filesExclude) )
                        {
                            $php_file_tree .= $this->getLink($directory, $this_file);
                        }
                    }
                }
            }//foreach

            $php_file_tree .= $this->idtDel();
            $php_file_tree .= '</ul>';
        }

        return $php_file_tree;
    }//function

    private function idtAdd()
    {
        $this->indent ++;
        return NL.str_repeat('   ', $this->indent);
    }//function

    private function idtDel()
    {
        $this->indent --;
        if( $this->indent < 0 ) $this->indent = 0;
        return NL.str_repeat('   ', $this->indent);
    }//function

    private function idt()
    {
        return NL.str_repeat('   ', $this->indent);
    }//function

    /**
     * Displays a link
     *
     * @param $folder
     * @param $file
     * @return string
     */
    public function getLink($folder, $file)
    {
        $ext = 'ext-' . substr($file, strrpos($file, '.') + 1);

        $href = $this->parseLink($this->href, $folder, $file);
        $js = $this->parseLink($this->jsFile, $folder, $file);
        $s = '';
        $s .= $this->idt();

        $s .= '<li class="pft-file '.strtolower($ext).'" id="tl_'.$this->linkId.'" '.$js.'>';
        if( ! $this->showExtension )
        {
            $file = JFile::stripExt($file);
        }
        $s .= htmlspecialchars($file);
        $s .= '</li>';

        $this->linkId ++;

        return $s;
    }//function

    /**
     *
     * @param $string
     * @param $folder
     * @param $file
     * @return unknown_type
     */
    private function parseLink($string, $folder, $file)
    {
        $s = $string;
        if( $this->replacePath)
        {
            if( $folder.DS == $this->replacePath )
            {
                $folder = '';
            }
            else
            {
                 $folder = str_replace($this->replacePath, '', $folder);
            }
        }
        $s = str_replace('[folder]', $folder, $s);
        $s = str_replace('[link]', $folder, $s);
        $s = str_replace('[file]', urlencode($file), $s);
        $s = str_replace('[id]', 'tl_'.$this->linkId, $s);

        return $s;
    }//function

}//class
