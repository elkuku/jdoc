<?php
/**
 * @version SVN: $Id: request.php 23 2010-11-08 19:30:35Z elkuku $
 * @package
 * @subpackage
 * @author     Nikolai Plath {@link http://www.nik-it.de}
 * @author     Created on 12-Apr-2010
 * @license    GNU/GPL, see JROOT/LICENSE.php
 */

/**
 * JRequest Class - renamed to "Easy" to avoyd conflicts when reflecting the original class
 *
 * This class serves to provide the Joomla Framework with a common interface to access
 * request variables.  This includes $_POST, $_GET, and naturally $_REQUEST.  Variables
 * can be passed through an input filter to avoid injection or returned raw.
 *
 * @static
 * @package		Joomla.Framework
 * @subpackage	Environment
 * @since		1.5
 *
 * @renamed to "Easy" to avoid conflicts when reflecting the original class
 */
class EasyRequest
{
    /**
     * Fetches and returns a given variable.
     *
     * The default behaviour is fetching variables depending on the
     * current request method: GET and HEAD will result in returning
     * an entry from $_GET, POST and PUT will result in returning an
     * entry from $_POST.
     *
     * You can force the source by setting the $hash parameter:
     *
     *   post		$_POST
     *   get		$_GET
     *   files		$_FILES
     *   cookie		$_COOKIE
     *   env		$_ENV
     *   server		$_SERVER
     *   method		via current $_SERVER['REQUEST_METHOD']
     *   default	$_REQUEST
     *
     * @static
     * @param	string	$name		Variable name
     * @param	string	$default	Default value if the variable does not exist
     * @param	string	$hash		Where the var should come from (POST, GET, FILES, COOKIE, METHOD)
     * @param	string	$type		Return type for the variable, for valid values see {@link JFilterInput::clean()}
     * @param	int		$mask		Filter mask for the variable
     * @return	mixed	Requested variable
     * @since	1.5
     */
    public static function getVar($name, $default = null, $hash = 'default', $type = 'none', $mask = 0)
    {
        // Ensure hash and type are uppercase
        $hash = strtoupper( $hash );
        if ($hash === 'METHOD') {
            $hash = strtoupper( $_SERVER['REQUEST_METHOD'] );
        }
        $type	= strtoupper( $type );
        $sig	= $hash.$type.$mask;

        // Get the input hash
        switch ($hash)
        {
            case 'GET' :
                $input = &$_GET;
                break;
            case 'POST' :
                $input = &$_POST;
                break;
            case 'FILES' :
                $input = &$_FILES;
                break;
            case 'COOKIE' :
                $input = &$_COOKIE;
                break;
            case 'ENV'    :
                $input = &$_ENV;
                break;
            case 'SERVER'    :
                $input = &$_SERVER;
                break;
            default:
                $input = &$_REQUEST;
                $hash = 'REQUEST';
                break;
        }

        if (isset($GLOBALS['_JREQUEST'][$name]['SET.'.$hash]) && ($GLOBALS['_JREQUEST'][$name]['SET.'.$hash] === true)) {
            // Get the variable from the input hash
            $var = (isset($input[$name]) && $input[$name] !== null) ? $input[$name] : $default;
            $var = JRequest::_cleanVar($var, $mask, $type);
        }
        elseif (!isset($GLOBALS['_JREQUEST'][$name][$sig]))
        {
            if (isset($input[$name]) && $input[$name] !== null) {
                // Get the variable from the input hash and clean it
                $var = EasyRequest::_cleanVar($input[$name], $mask, $type);

                // Handle magic quotes compatability
                if (get_magic_quotes_gpc() && ($var != $default) && ($hash != 'FILES')) {
                    $var = EasyRequest::_stripSlashesRecursive( $var );
                }

                $GLOBALS['_JREQUEST'][$name][$sig] = $var;
            }
            elseif ($default !== null) {
                // Clean the default value
                $var = EasyRequest::_cleanVar($default, $mask, $type);
            }
            else {
                $var = $default;
            }
        } else {
            $var = $GLOBALS['_JREQUEST'][$name][$sig];
        }

        return $var;
    }
    /**
     * Clean up an input variable.
     *
     * @param mixed The input variable.
     * @param int Filter bit mask. 1=no trim: If this flag is cleared and the
     * input is a string, the string will have leading and trailing whitespace
     * trimmed. 2=allow_raw: If set, no more filtering is performed, higher bits
     * are ignored. 4=allow_html: HTML is allowed, but passed through a safe
     * HTML filter first. If set, no more filtering is performed. If no bits
     * other than the 1 bit is set, a strict filter is applied.
     * @param string The variable type {@see JFilterInput::clean()}.
     */
    public static function _cleanVar($var, $mask = 0, $type=null)
    {
        // Static input filters for specific settings
        static $noHtmlFilter	= null;
        static $safeHtmlFilter	= null;

        // If the no trim flag is not set, trim the variable
        if (!($mask & 1) && is_string($var)) {
            $var = trim($var);
        }

        // Now we handle input filtering
        if ($mask & 2)
        {
            // If the allow raw flag is set, do not modify the variable
            $var = $var;
        }
        elseif ($mask & 4)
        {
            // If the allow html flag is set, apply a safe html filter to the variable
            if (is_null($safeHtmlFilter)) {
                $safeHtmlFilter = & JFilterInput::getInstance(null, null, 1, 1);
            }
            $var = $safeHtmlFilter->clean($var, $type);
        }
        else
        {
            // Since no allow flags were set, we will apply the most strict filter to the variable
            if (is_null($noHtmlFilter)) {
                $noHtmlFilter = & EasyFilterInput::getInstance(/* $tags, $attr, $tag_method, $attr_method, $xss_auto */);
            }
            $var = $noHtmlFilter->clean($var, $type);
        }
        return $var;
    }

    /**
     * Strips slashes recursively on an array
     *
     * @access	protected
     * @param	array	$array		Array of (nested arrays of) strings
     * @return	array	The input array with stripshlashes applied to it
     */
    public static function _stripSlashesRecursive( $value )
    {
        $value = is_array( $value ) ? array_map( array( 'JRequest', '_stripSlashesRecursive' ), $value ) : stripslashes( $value );
        return $value;
    }
}//class

/**
 * JFilterInput is a class for filtering input from any data source
 *
 * Forked from the php input filter library by: Daniel Morris <dan@rootcube.com>
 * Original Contributors: Gianpaolo Racca, Ghislain Picard, Marco Wandschneider, Chris Tobin and Andrew Eddie.
 *
 * @package 	Joomla.Framework
 * @subpackage		Filter
 * @since		1.5
 */
class EasyFilterInput extends EasyObject
{
    /**
     * Returns a reference to an input filter object, only creating it if it doesn't already exist.
     *
     * This method must be invoked as:
     * 		<pre>  $filter = & JFilterInput::getInstance();</pre>
     *
     * @static
     * @param	array	$tagsArray	list of user-defined tags
     * @param	array	$attrArray	list of user-defined attributes
     * @param	int		$tagsMethod	WhiteList method = 0, BlackList method = 1
     * @param	int		$attrMethod	WhiteList method = 0, BlackList method = 1
     * @param	int		$xssAuto	Only auto clean essentials = 0, Allow clean blacklisted tags/attr = 1
     * @return	object	The JFilterInput object.
     * @since	1.5
     */
    public static function & getInstance($tagsArray = array(), $attrArray = array(), $tagsMethod = 0, $attrMethod = 0, $xssAuto = 1)
    {
        static $instances;

        $sig = md5(serialize(array($tagsArray,$attrArray,$tagsMethod,$attrMethod,$xssAuto)));

        if (!isset ($instances)) {
            $instances = array();
        }

        if (empty ($instances[$sig])) {
            $instances[$sig] = new EasyFilterInput($tagsArray, $attrArray, $tagsMethod, $attrMethod, $xssAuto);
        }

        return $instances[$sig];
    }


    /**
     * Method to be called by another php script. Processes for XSS and
     * specified bad code.
     *
     * @access	public
     * @param	mixed	$source	Input string/array-of-string to be 'cleaned'
     * @param	string	$type	Return type for the variable (INT, FLOAT, BOOLEAN, WORD, ALNUM, CMD, BASE64, STRING, ARRAY, PATH, NONE)
     * @return	mixed	'Cleaned' version of input parameter
     * @since	1.5
     * @static
     */
    public static function clean($source, $type='string')
    {
        // Handle the type constraint
        switch (strtoupper($type))
        {
            case 'INT' :
            case 'INTEGER' :
                // Only use the first integer value
                preg_match('/-?[0-9]+/', (string) $source, $matches);
                $result = @ (int) $matches[0];
                break;

            case 'FLOAT' :
            case 'DOUBLE' :
                // Only use the first floating point value
                preg_match('/-?[0-9]+(\.[0-9]+)?/', (string) $source, $matches);
                $result = @ (float) $matches[0];
                break;

            case 'BOOL' :
            case 'BOOLEAN' :
                $result = (bool) $source;
                break;

            case 'WORD' :
                $result = (string) preg_replace( '/[^A-Z_]/i', '', $source );
                break;

            case 'ALNUM' :
                $result = (string) preg_replace( '/[^A-Z0-9]/i', '', $source );
                break;

            case 'CMD' :
                $result = (string) preg_replace( '/[^A-Z0-9_\.-]/i', '', $source );
                $result = ltrim($result, '.');
                break;

            case 'BASE64' :
                $result = (string) preg_replace( '/[^A-Z0-9\/+=]/i', '', $source );
                break;

            case 'STRING' :
                // Check for static usage and assign $filter the proper variable
                if(isset($this) && is_a( $this, 'JFilterInput' )) {
                    $filter =& $this;
                } else {
                    $filter =& JFilterInput::getInstance();
                }
                $result = (string) $filter->_remove($filter->_decode((string) $source));
                break;

            case 'ARRAY' :
                $result = (array) $source;
                break;

            case 'PATH' :
                $pattern = '/^[A-Za-z0-9_-]+[A-Za-z0-9_\.-]*([\\\\\/][A-Za-z0-9_-]+[A-Za-z0-9_\.-]*)*$/';
                preg_match($pattern, (string) $source, $matches);
                $result = @ (string) $matches[0];
                break;

            case 'USERNAME' :
                $result = (string) preg_replace( '/[\x00-\x1F\x7F<>"\'%&]/', '', $source );
                break;

            default :
                // Check for static usage and assign $filter the proper variable
                    $filter =& EasyFilterInput::getInstance();
//                if(is_object($this) && get_class($this) == 'JFilterInput') {
//                    $filter =& $this;
//                } else {
//                }
                // Are we dealing with an array?
                if (is_array($source)) {
                    foreach ($source as $key => $value)
                    {
                        // filter element for XSS and other 'bad' code etc.
                        if (is_string($value)) {
                            $source[$key] = $filter->_remove($filter->_decode($value));
                        }
                    }
                    $result = $source;
                } else {
                    // Or a string?
                    if (is_string($source) && !empty ($source)) {
                        // filter source for XSS and other 'bad' code etc.
                        $result = $filter->_remove($filter->_decode($source));
                    } else {
                        // Not an array or string.. return the passed parameter
                        $result = $source;
                    }
                }
                break;
        }
        return $result;
    }

    /**
     * Internal method to iteratively remove all unwanted tags and attributes
     *
     * @access	protected
     * @param	string	$source	Input string to be 'cleaned'
     * @return	string	'Cleaned' version of input parameter
     * @since	1.5
     */
    function _remove($source)
    {
        $loopCounter = 0;

        // Iteration provides nested tag protection
        while ($source != $this->_cleanTags($source))
        {
            $source = $this->_cleanTags($source);
            $loopCounter ++;
        }
        return $source;
    }

    /**
     * Try to convert to plaintext
     *
     * @access	protected
     * @param	string	$source
     * @return	string	Plaintext string
     * @since	1.5
     */
    function _decode($source)
    {
        // entity decode
        $trans_tbl = get_html_translation_table(HTML_ENTITIES);
        foreach($trans_tbl as $k => $v) {
            $ttr[$v] = utf8_encode($k);
        }
        $source = strtr($source, $ttr);
        // convert decimal
        $source = preg_replace('/&#(\d+);/me', "utf8_encode(chr(\\1))", $source); // decimal notation
        // convert hex
        $source = preg_replace('/&#x([a-f0-9]+);/mei', "utf8_encode(chr(0x\\1))", $source); // hex notation
        return $source;
    }
    /**
     * Internal method to strip a string of certain tags
     *
     * @access	protected
     * @param	string	$source	Input string to be 'cleaned'
     * @return	string	'Cleaned' version of input parameter
     * @since	1.5
     */
    function _cleanTags($source)
    {
        /*
         * In the beginning we don't really have a tag, so everything is
         * postTag
         */
        $preTag		= null;
        $postTag	= $source;
        $currentSpace = false;
        $attr = '';	 // moffats: setting to null due to issues in migration system - undefined variable errors

        // Is there a tag? If so it will certainly start with a '<'
        $tagOpen_start	= strpos($source, '<');

        while ($tagOpen_start !== false)
        {
            // Get some information about the tag we are processing
            $preTag			.= substr($postTag, 0, $tagOpen_start);
            $postTag		= substr($postTag, $tagOpen_start);
            $fromTagOpen	= substr($postTag, 1);
            $tagOpen_end	= strpos($fromTagOpen, '>');

            // Let's catch any non-terminated tags and skip over them
            if ($tagOpen_end === false) {
                $postTag		= substr($postTag, $tagOpen_start +1);
                $tagOpen_start	= strpos($postTag, '<');
                continue;
            }

            // Do we have a nested tag?
            $tagOpen_nested = strpos($fromTagOpen, '<');
            $tagOpen_nested_end	= strpos(substr($postTag, $tagOpen_end), '>');
            if (($tagOpen_nested !== false) && ($tagOpen_nested < $tagOpen_end)) {
                $preTag			.= substr($postTag, 0, ($tagOpen_nested +1));
                $postTag		= substr($postTag, ($tagOpen_nested +1));
                $tagOpen_start	= strpos($postTag, '<');
                continue;
            }

            // Lets get some information about our tag and setup attribute pairs
            $tagOpen_nested	= (strpos($fromTagOpen, '<') + $tagOpen_start +1);
            $currentTag		= substr($fromTagOpen, 0, $tagOpen_end);
            $tagLength		= strlen($currentTag);
            $tagLeft		= $currentTag;
            $attrSet		= array ();
            $currentSpace	= strpos($tagLeft, ' ');

            // Are we an open tag or a close tag?
            if (substr($currentTag, 0, 1) == '/') {
                // Close Tag
                $isCloseTag		= true;
                list ($tagName)	= explode(' ', $currentTag);
                $tagName		= substr($tagName, 1);
            } else {
                // Open Tag
                $isCloseTag		= false;
                list ($tagName)	= explode(' ', $currentTag);
            }

            /*
             * Exclude all "non-regular" tagnames
             * OR no tagname
             * OR remove if xssauto is on and tag is blacklisted
             */
            if ((!preg_match("/^[a-z][a-z0-9]*$/i", $tagName)) || (!$tagName) || ((in_array(strtolower($tagName), $this->tagBlacklist)) && ($this->xssAuto))) {
                $postTag		= substr($postTag, ($tagLength +2));
                $tagOpen_start	= strpos($postTag, '<');
                // Strip tag
                continue;
            }

            /*
             * Time to grab any attributes from the tag... need this section in
             * case attributes have spaces in the values.
             */
            while ($currentSpace !== false)
            {
                $attr			= '';
                $fromSpace		= substr($tagLeft, ($currentSpace +1));
                $nextSpace		= strpos($fromSpace, ' ');
                $openQuotes		= strpos($fromSpace, '"');
                $closeQuotes	= strpos(substr($fromSpace, ($openQuotes +1)), '"') + $openQuotes +1;

                // Do we have an attribute to process? [check for equal sign]
                if (strpos($fromSpace, '=') !== false) {
                    /*
                     * If the attribute value is wrapped in quotes we need to
                     * grab the substring from the closing quote, otherwise grab
                     * till the next space
                     */
                    if (($openQuotes !== false) && (strpos(substr($fromSpace, ($openQuotes +1)), '"') !== false)) {
                        $attr = substr($fromSpace, 0, ($closeQuotes +1));
                    } else {
                        $attr = substr($fromSpace, 0, $nextSpace);
                    }
                } else {
                    /*
                     * No more equal signs so add any extra text in the tag into
                     * the attribute array [eg. checked]
                     */
                    if ($fromSpace != '/') {
                        $attr = substr($fromSpace, 0, $nextSpace);
                    }
                }

                // Last Attribute Pair
                if (!$attr && $fromSpace != '/') {
                    $attr = $fromSpace;
                }

                // Add attribute pair to the attribute array
                $attrSet[] = $attr;

                // Move search point and continue iteration
                $tagLeft		= substr($fromSpace, strlen($attr));
                $currentSpace	= strpos($tagLeft, ' ');
            }

            // Is our tag in the user input array?
            $tagFound = in_array(strtolower($tagName), $this->tagsArray);

            // If the tag is allowed lets append it to the output string
            if ((!$tagFound && $this->tagsMethod) || ($tagFound && !$this->tagsMethod)) {

                // Reconstruct tag with allowed attributes
                if (!$isCloseTag) {
                    // Open or Single tag
                    $attrSet = $this->_cleanAttributes($attrSet);
                    $preTag .= '<'.$tagName;
                    for ($i = 0; $i < count($attrSet); $i ++)
                    {
                        $preTag .= ' '.$attrSet[$i];
                    }

                    // Reformat single tags to XHTML
                    if (strpos($fromTagOpen, '</'.$tagName)) {
                        $preTag .= '>';
                    } else {
                        $preTag .= ' />';
                    }
                } else {
                    // Closing Tag
                    $preTag .= '</'.$tagName.'>';
                }
            }

            // Find next tag's start and continue iteration
            $postTag		= substr($postTag, ($tagLength +2));
            $tagOpen_start	= strpos($postTag, '<');
        }

        // Append any code after the end of tags and return
        if ($postTag != '<') {
            $preTag .= $postTag;
        }
        return $preTag;
    }

}//class

class EasyPath
{
    /**
     * Function to strip additional / or \ in a path name
     *
     * @static
     * @param   string  $path   The path to clean
     * @param   string  $ds     Directory separator (optional)
     * @return  string  The cleaned path
     * @since   1.5
     */
    public static function clean($path, $ds=DS)
    {
        $path = trim($path);

        if (empty($path)) {
            $path = JPATH_ROOT;
        } else {
            // Remove double slashes and backslahses and convert all slashes and backslashes to DS
            $path = preg_replace('#[/\\\\]+#', $ds, $path);
        }

        return $path;
    }



}//class
