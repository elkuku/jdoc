<?php
/**
 * @version SVN: $Id: filesystem.php 23 2010-11-08 19:30:35Z elkuku $
 * @package
 * @subpackage
 * @author     Nikolai Plath {@link http://www.nik-it.de}
 * @author     Created on 12.04.2010
 * @license    GNU/GPL, see JROOT/LICENSE.php
 */

/**
 *
 *
 */
class EasyFolder
{
    /**
     * Utility function to read the files in a folder.
     *
     * @param	string	The path of the folder to read.
     * @param	string	A filter for file names.
     * @param	mixed	True to recursively search into sub-folders, or an
     * integer to specify the maximum depth.
     * @param	boolean	True to return the full path to the file.
     * @param	array	Array with names of files which should not be shown in
     * the result.
     * @return	array	Files in the given folder.
     * @since 1.5
     * @renamed to "Easy" to avoid conflicts when reflecting the original class
     */
    public static function files($path, $filter = '.', $recurse = false, $fullpath = false, $stripPath = '', $exclude = array('.svn', 'CVS'))
    {
        // Initialize variables
        $arr = array();

        // Check to make sure the path valid and clean
        #		$path = JPath::clean($path);

        // Is the path a folder?
        if (!is_dir($path)) {
            echo 'EasyFolder::files: Path is not a folder: ' . $path;
            return false;
        }

        // read the source directory
        $handle = opendir($path);
        while (($file = readdir($handle)) !== false)
        {
            if (($file != '.') && ($file != '..') && (!in_array($file, $exclude))) {
                $dir = $path . DS . $file;
                $isDir = is_dir($dir);
                if ($isDir) {
                    if ($recurse) {
                        if (is_integer($recurse)) {
                            $arr2 = EasyFolder::files($dir, $filter, $recurse - 1, $fullpath);
                        } else {
                            $arr2 = EasyFolder::files($dir, $filter, $recurse, $fullpath);
                        }

                        $arr = array_merge($arr, $arr2);
                    }
                } else {
                    if (preg_match("/$filter/", $file)) {
                        if ($fullpath) {

                            $arr[] = $path.DS.$file;
                        } else {
                            $arr[] = $file;
                        }
                    }
                }
            }
        }
        closedir($handle);

        asort($arr);
        return $arr;
    }//function

    /**
     * Utility function to read the folders in a folder.
     *
     * @param	string	The path of the folder to read.
     * @param	string	A filter for folder names.
     * @param	mixed	True to recursively search into sub-folders, or an
     * integer to specify the maximum depth.
     * @param	boolean	True to return the full path to the folders.
     * @param	array	Array with names of folders which should not be shown in
     * the result.
     * @return	array	Folders in the given folder.
     * @since 1.5
     */
    public static function folders($path, $filter = '.', $recurse = false, $fullpath = false, $exclude = array('.svn', 'CVS'))
    {
        // Initialize variables
        $arr = array();

        // Check to make sure the path valid and clean
        #		$path = JPath::clean($path);

        // Is the path a folder?
        if (!is_dir($path)) {
            echo 'EasyFolder::folders: Path is not a folder ' . $path;
            #			JError::raiseWarning(21, 'JFolder::folder: ' . JText::_('Path is not a folder'), 'Path: ' . $path);
            return false;
        }

        // read the source directory
        $handle = opendir($path);
        while (($file = readdir($handle)) !== false)
        {
            if (($file != '.') && ($file != '..') && (!in_array($file, $exclude))) {
                $dir = $path . DS . $file;
                $isDir = is_dir($dir);
                if ($isDir) {
                    // Removes filtered directories
                    if (preg_match("/$filter/", $file)) {
                        if ($fullpath) {
                            $arr[] = $dir;
                        } else {
                            $arr[] = $file;
                        }
                    }
                    if ($recurse) {
                        if (is_integer($recurse)) {
                            $arr2 = JFolder::folders($dir, $filter, $recurse - 1, $fullpath);
                        } else {
                            $arr2 = JFolder::folders($dir, $filter, $recurse, $fullpath);
                        }

                        $arr = array_merge($arr, $arr2);
                    }
                }
            }
        }
        closedir($handle);

        asort($arr);
        return $arr;
    }//function

    /**
     * Wrapper for the standard file_exists function
     *
     * @param string Folder name relative to installation dir
     * @return boolean True if path is a folder
     * @since 1.5
     */
    function exists($path)
    {
        return is_dir($path);
    }

}//class

/**
 * A File handling class
 *
 * @static
 * @package     Joomla.Framework
 * @subpackage  FileSystem
 * @since       1.5
 */
class EasyFile
{
    /**
     * Strips the last extension off a file name
     *
     * @param string $file The file name
     * @return string The file name without the extension
     * @since 1.5
     */
    public static function stripExt($file) {
        return preg_replace('#\.[^.]*$#', '', $file);
    }

    /**
     * Returns the name, sans any path
     *
     * param string $file File path
     * @return string filename
     * @since 1.5
     */
    public static function getName($file) {
        $slash = strrpos($file, DS);
        if ($slash !== false) {
            return substr($file, $slash + 1);
        } else {
            return $file;
        }
    }

    /**
     * Wrapper for the standard file_exists function
     *
     * @param string $file File path
     * @return boolean True if path is a file
     * @since 1.5
     */
    public static function exists($file)
    {
        return is_file(EasyPath::clean($file));
    }

}//class
