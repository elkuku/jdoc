<?php
/**
 * @version $Id: source.php 23 2010-11-08 19:30:35Z elkuku $
 * @package	JFrameWorkDoc
 * @subpackage	Formats
 * @author		Nikolai Plath (elkuku) {@link http://www.nik-it.de NiK-IT.de}
 * @author		Created on 24.09.2008
 */

//-- No direct access
defined( '_JEXEC') or die('=;)');

class ReflectorFormatSource
{
	function reflect($rawDoc, $fullPath)
	{
		if( ! file_exists($fullPath) )
		{
			return 'File not found - '.$fullPath;
		}
		$useGeshi = EasyRequest::getVar('use_geshi');
		$fileContents = file($fullPath);

		require_once JPATHROOT.DS.'assets'.DS.'js'.DS.'geshi.php';

		$html = '';
		$classPanel = '';

		foreach ($rawDoc->classes as $class)
		{
			$sourcePages = array();
			$wikiMethodsPages = array();
			$wikiClassPage = '';

			$parent = $class->getParentClass();
			$methods =  $class->getMethods();

			$displayClassName = '';
			foreach ($methods as $method)
			{
				//-- Ignore extended class methods
				$declaringClass = $method->getDeclaringClass()->getName();
				if( strtolower($declaringClass) != strtolower($class->getName()) ) { continue; }

				//-- Ignore everythig starting with a '_' - aka private.. @todo change for 1.6
				if( substr($method->name, 0, 1) == '_' && $method->name != '_' ) { continue; }

				//-- Ignore pseudo constructors
				if( $method->name == $class->getName() ) { continue; }

				$code = array();
				$codeRaw = '';
				for ($i = $method->getStartLine() - 1; $i < $method->getEndLine(); $i ++)
				{

					$l = rtrim($fileContents[$i]);

					//-- Strip leading tabs
					if( substr($l, 0, 1) == "\t")
					{
						$l = substr($l, 1);
					}

					//-- Convert tabs to three spaces
					$l = str_replace("\t", '   ', $l);

					$code[] = $l;
					$codeRaw .= sprintf('%4s', $i + 1).' '.$l.NL;
				}//for

				$code = implode("\n", $code);
				$comment =( $method->getDocComment() ) ? '<div class="DocComment">'.nl2br($method->getDocComment()).'</div>' : '';
				if( $useGeshi == 'true' )
				{

				$geshi = new GeSHi($code, 'php');
				$geshi->start_line_numbers_at($method->getStartLine());
				$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
				$code = $comment.$geshi->parse_code($code);

				}
				else
				{
					$code = $comment.'<pre>'.$codeRaw.'</pre>';
				}

				$sourcePages[$method->name] = $code;


				$parameters = $method->getParameters();
				$wikiParams = array();
				$wikiParamsDesc = '';

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
					$wikiParamsDesc .=($wikiDefault === 'NODEFAULT') ? '| ---'.NL : '|<tt>'.$wikiDefault.'</tt>'.NL;// : '|<tt>{{@todo|Default}}</tt>'.NL;
				}//foreach parameters

				$comment = explode(NL, $method->getDocComment());
				$searches = array('@return', '@since', '@static');

				$syntaxAdds = '';
				$docComOptions = self::parseDocComment($method->getDocComment(), $searches);
				$syntaxAdds .=( $docComOptions->return ) ? $docComOptions->return.NL : "* '''@return''' {{mark|XXXX}} {{@todo}}".NL;
				$syntaxAdds .=( $docComOptions->since ) ? $docComOptions->since.NL : "* '''@since''' {{JVer|1.5}}".NL;

				$wikiMethodsPage = '';
				$wikiMethodsPage .= "{{RightTOC}}'''".$class->getName().'/'.$method->name."'''".' {{@todo|Beschreibung}}'.NL.NL;
				if($method->getDocComment())
				{
					$wikiMethodsPage .= '<source lang="php">Der DocComment dient nur zur Referenz - bitte entfernen'.NL.$method->getDocComment().'</source>'.NL.NL;
				}
				$wikiMethodsPage .= '==Syntax=='.NL;
				$isStatic =($docComOptions->isStatic) ? true : false;//@todo
				$s =($isStatic) ? 'static ' : '';
				$wikiMethodsPage .= '<source lang="php">'.$s.$method->name.'( '.implode(', ', $wikiParams).' )</source>'.NL;
				$wikiMethodsPage .= $syntaxAdds.NL;



			}//foreach methods

			$s =($class->subPackageName) ? $class->subPackageName.'/' : '';
			$wikiClassPage .= '* <tt>[http://api.joomla.org/Joomla-Framework/'.$s.$class->getName().'.html '.$class->getName().']</tt> auf api.joomla.org'.NL.NL;

			$classPanel .= '<li>'.$class->getName().'</li>';

			$html .= '<div id="page-'.$class->getName().'" style="display: none;">';

			$html .= '<textarea  class="code" style="width: 100%" rows="40" cols="150" id="'.$class->getName().'-xxpage" onfocus="aSelect(\''.$class->getName().'-xxpage\');" onclick="aSelect(\''.$class->getName().'-xxpage\')">'.htmlspecialchars($wikiClassPage).'</textarea>';
			$html .= '</div>';

			foreach ($sourcePages as $pName => $page)
			{
				$title = $pName;
				$classPanel .= '<li class="st_method" id="switch-'.$pName.'" onclick="switchPage(\''.$pName.'\');">'.$pName.'</li>';
				$html .= '<div id="page-'.$pName.'" style="display: none;">';
				$html .= $page;
				$html .= '</div>';
			}//foreach

		}//foreach classes

		$classPanel = '<ul class="classpanel">'.$classPanel.'</ul>';

		return '<table><tr valign="top"><td>'.$classPanel.'</td><td>'.$html.'</td></tr></table>';
	}//function

	/**
	 *
	 * @param $docComment string
	 * @param $searchFor array
	 * @return object
	 */
	private function parseDocComment($docComment, $searchFor, $enclosings=array())
	{
		if( ! count($enclosings))
		{

		}
		$DComm = new stdClass();
		$DComm->isStatic = false;
		$DComm->since = '';
		$DComm->return = '';
		$comment = explode(NL, $docComment);
		foreach ($comment as $c)
		{
			foreach ($searchFor as $search)
			{
				if( strpos($c, $search))
				{
					if( $search == '@static'){ $DComm->isStatic = true; continue; }
					if( $search == '@return')
					{
						//						preg_match('%return\w(w?)%', $c, $matches);
						//						print_r($matches);
						//						$s =

						$DComm->return = trim(str_replace('@return', "'''@return'''", $c));
					}
					if( $search == '@since'){ $DComm->since = trim(str_replace('@since', "'''@since'''", $c)); continue;}
					//					$c = str_replace($search, "'''".$search."'''", $c);
					//					$syntaxAdds .= trim($c).NL;
				}
			}
		}//foreach

		return $DComm;
	}//function

}//class
