<?php
/**
 * @version $Id: compare.php 23 2010-11-08 19:30:35Z elkuku $
 * @package     JFrameWorkDoc
 * @subpackage  Formats
 * @author		Nikolai Plath (elkuku) {@link http://www.nik-it.de NiK-IT.de}
 * @author		Created on 24.09.2008
 */

//-- No direct access
defined( '_JEXEC') or die('=;)');

class ReflectorFormatCompare
{
	function reflect($rawDoc, $fullPath, $jVersion2)
	{
		require_once JPATHROOT.DS.'helpers'.DS.'DifferenceEngine.php';

		if( ! file_exists($fullPath) )
		{
			return 'File not found - '.$fullPath;
		}

		$fileContents = file($fullPath);

		$basePath2 = JPATHROOT.DS.'sources'.DS.'joomla';

		$partPath = substr($fullPath, strpos($fullPath, 'libraries'.DS.'joomla'.DS)+strlen('libraries'.DS.'joomla'.DS));
		$fullPath2 = $basePath2.DS.$jVersion2.DS.'libraries'.DS.'joomla'.DS.$partPath;

		if( ! file_exists($fullPath2))
		{
			echo 'File does not exist in Joomla! Version '.$jVersion2.BR.DS.'libraries'.DS.'joomla'.DS.$partPath;

			return;
		}
		$fileContents2 = file($fullPath2);

		$fName = 'jmethodlist_'.str_replace('.', '_', $jVersion2).'.txt';
		if( ! file_exists($basePath2.DS.$fName))
		{
            //-- Method list NOT FOUND
            //-- Build it !
            $host  = $_SERVER['HTTP_HOST'];
            $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
            $extra = 'sources/jmethodlister.php?action=methods&jver='.$jVersion2;
            header("Location: http://$host$uri/$extra");
		    echo 'Version file not found '.$basePath2.DS.$fName;

			return;
		}

		$JMethodList2 = self::getJMethodList($basePath2.DS.$fName);

		$html = '';
		$classPanel = '';
		$sourcePages = array();

		foreach ($rawDoc->classes as $class)
		{
			$methods =  $class->getMethods();

			foreach ($methods as $method)
			{
				$declaringClass = $method->getDeclaringClass()->getName();
				if( strtolower($declaringClass) != strtolower($class->getName()) )
				{
					continue;
				}
				//--Everythig starting with a '_' will be ignored - aka private.. @todo change for 1.6
				if( substr($method->name, 0, 1) == '_' && $method->name != '_' )
				{
					continue;
				}

				$code = array();
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

					$code[] = htmlentities($l);
				}//for

				$code2 = array();
				if( isset($JMethodList2[$class->getName()][$method->name]) )
				{
					$M2 = $JMethodList2[$class->getName()][$method->name];
					$code2 = '';
					for ($i = $M2->start - 1; $i < $M2->end; $i ++)
					{
						$l = rtrim($fileContents2[$i]);
						//-- Strip leading tabs
						if( substr($l, 0, 1) == "\t")
						{
							$l = substr($l, 1);
						}

						//-- Convert tabs to three spaces
						$l = str_replace("\t", '   ', $l);

						$code2[] = htmlentities($l);
					}//for
				}

				if( $code == $code2 )
				{
					$compareTable = '<h1 style="color: #00cc00;">No Changes..</h1>';
				}
				else
				{
					$dwDiff = new Diff($code, $code2);
					$dwFormatter = new TableDiffFormatter();
					$compareTable = '';
					$compareTable .= '<table class="diff" width="100%">';
					$compareTable .= '<tr>';
					$compareTable .= '<th colspan="2" style="border-bottom: 1px dashed gray;">Joomla! </td>';
					$compareTable .= '<th colspan="2" style="border-bottom: 1px dashed gray;">Joomla! '.$jVersion2.'</th>';
					$compareTable .= '</tr>';
					$compareTable .= $dwFormatter->format($dwDiff);
	#				$compareTable .= '<tr><td>'.nl2br($code).'</td><td>'.nl2br($code2).'</td></tr>';
					$compareTable .= '</table>';
				}

				$p = new stdClass();
				$p->class = $class->getName();
				$p->method = $method->name;
				$p->text = $compareTable;
				$p->isDifferent =($code != $code2);
				$sourcePages[$class->getName().'_'.$method->name] = $p;

			}//foreach methods

			$s =($class->subPackageName) ? $class->subPackageName.'/' : '';

			$classPanel .= '<li style="font-weight: bold;">&nbsp;'.$class->getName().'</li>';

			foreach ($sourcePages as $pName => $page)
			{
				$cssC =( $page->isDifferent ) ? '_changed' : '';
				$classPanel .= '<li class="st_method'.$cssC.'" id="switch-'.$pName.'" onclick="switchPage(\''.$pName.'\');">'.$page->method.'</li>';
				$html .= '<div id="page-'.$pName.'" style="display: none;">';
				$html .= $page->text;
				$html .= '</div>';
			}//foreach

		}//foreach classes

		$classPanel = '<ul class="classpanel">'.$classPanel.'</ul>';

		$t = '';
		$t .= '<table width="100%"><tr valign="top">';
		$t .= '<td width="5%">'.$classPanel.'</td>';
		$t .= '<td>'.$html.'</td>';
		$t .= '</tr></table>';

		return $t;
	}//function

	private function getJMethodList($fileName)
	{
		$list = array();
		$fContents = file($fileName);

		foreach ($fContents as $line)
		{
			list($c, $m, $path, $start, $end) = explode('#', $line);

			$me = new stdClass();
			$me->start = intval($start);
			$me->end = intval($end);
			$me->path = $path;
			$list[$c][$m] = $me;
		}//foreach

		return $list;
	}//function

}//class
