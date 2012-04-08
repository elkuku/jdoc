#!/usr/bin/php
<?php
/**
 * Created by JetBrains PhpStorm.
 * User: elkuku
 * Date: 04.04.12
 * Time: 23:21
 * To change this template use File | Settings | File Templates.
 */

'cli' == PHP_SAPI || die('This script must be executed from the command line.');

version_compare(PHP_VERSION, '5.3', '>=') || die('This script requires PHP >= 5.3');

define('_JEXEC', 1);

error_reporting(- 1);

define('JPATH_BASE', __DIR__);
define('JPATH_SITE', __DIR__);

define('OUTPUT_DIR', '/home/elkuku/stormspace/jdoc-gh-pages');

/**
 * Bootstrap the Joomla! Platform.
 */
require JPATH_BASE.'/sources/joomla-platform/11.4/libraries/import.php';

JError::$legacy = false;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

/**
 *
 */
class JDocBuild extends JApplicationCli
{
    private $classDiff = array();

    private $notes = array();

    private $page = null;

    public function doExecute()
    {
        $this->out('JDoc CLI builder');

        $this->clean()
            ->prepare()
            ->buildDocu()
            ->generateClassList();

        // @todo get version from input (any)
        $a = '11.4';
        $b = 'current';
//        $b = '11.4';

        $this->makeDiff($a, $b);

        $this->out('Finished =;');
    }

    private function clean()
    {

        return $this;
    }

    /**
     * @return JDocBuild
     * @throws Exception
     */
    private function prepare()
    {
        if(0)
            throw new Exception('huhu');

        return $this;
    }

    private function buildDocu()
    {
        $folders = JFolder::folders(JPATH_BASE.'/sources/joomla-platform', '.', false, true);

        if(! $folders)
            throw new Exception('Please put the platform sources in their respective folder in: '.JPATH_BASE.'/sources/joomla-platform', 1);

        foreach($folders as $folder)
        {
            $this->out('Running phpdox in folder '.$folder);

            $command = 'phpdox'
                // Input files
                .' -c '.$folder.'/libraries/joomla'
                // Output directory for generated documentation
                .' -d '.$folder.'/build/api'
                // Output directory for collected data
                .' -x '.$folder.'/build/docs'
                // Generate documentation
                .' -g html';

            $output = shell_exec($command);

            $this->out($output);
        }

        return $this;
    }

    private function generateClassList()
    {
        $platformBase = JPATH_BASE.'/sources/joomla-platform';
        $versions = JFolder::folders($platformBase);

        foreach($versions as $version)
        {
            $path = $platformBase.'/'.$version.'/build/docs/classes.xml';

            $xml = JFactory::getXml($path);

            if(! $xml)
                throw new Exception(__METHOD__.' - Unreadable XML file at: '.$path);

            $list = array();

            /* @var JXMLElement $class */
            foreach($xml->class as $class)
            {
                $name = (string)$class->attributes()->name;

                if($name != (string)$class->attributes()->full)
                    throw new Exception(__METHOD__.' dunno what to do :( --> '
                        .$name.' vs '.$class->attributes()->full);

                $parts = explode('/', $class->attributes()->xml);

                $package = (count($parts)) ? ucfirst($parts[1]) : 'Base';

                $list[$package][] = $name;
            }

            ksort($list);

            $html = array();

            foreach($list as $package => $classes)
            {
                $html[] = '<h2>'.$package.'</h2>';

                $html[] = '<ul>';

                sort($classes);

                foreach($classes as $class)
                {
                    $html[] = '<li>'.$class.'</li>';
                }
                $html[] = '</ul>';

            }

            ob_start();

            $this->page = new stdClass;

            $this->page->body = implode("\n", $html);

            $versionTo = ('current' == $version)
                ? 'current ('.JFile::read(JPATH_BASE.'/sources/joomla-platform/current/version.txt').')'
                : $version;


            $this->page->tagline = sprintf('Classes in version %s'
                , '<span class="versionNr">'.$versionTo.'</span>');

            include 'out/tmpl/default.php';

            $contents = ob_get_clean();

            $path = OUTPUT_DIR.'/classes-'.$version.'.html';
            JFile::write($path, $contents);

            $this->out('File has been written to: '.$path);
        }

    }

    private function makeDiff($a, $b)
    {
        $platformBase = JPATH_BASE.'/sources/joomla-platform';

        if(! JFolder::exists($platformBase.'/'.$a))
            throw new Exception(sprintf('Path %s does not exist', $platformBase.'/'.$a));

        if(! JFolder::exists($platformBase.'/'.$b))
            throw new Exception(sprintf('Path %s does not exist', $platformBase.'/'.$b));

        if('current' == $a || 'current' == $b)
        {
            exec('cd '.$platformBase.'/current/ && git describe > version.txt');
        }

        $this->notes[$a] = array();
        $this->notes[$b] = array();

        $fName = __DIR__.'/bc-notes-'.$a.'.xml';

        if(JFile::exists($fName))
            $this->notes[$a] = $this->getNotes($fName);

        $fName = __DIR__.'/bc-notes-'.$b.'.xml';

        if(JFile::exists($fName))
            $this->notes[$b] = $this->getNotes($fName);

        $this->processXml($platformBase.'/'.$a.'/build/docs/classes.xml', $a);
        $this->processXml($platformBase.'/'.$b.'/build/docs/classes.xml', $b);

        $output = array();

        foreach($this->classDiff as $className => $versions)
        {
            $output[$className] = new JDocDiffResultClass;

            if(array_key_exists($className, $this->notes[$a]))
                $output[$className]->note .= $this->notes[$a][$className]->note;

            if(array_key_exists($className, $this->notes[$b]))
                $output[$className]->note .= $this->notes[$b][$className]->note;

            if(! array_key_exists($a, $versions))
            {
                $output[$className]->status = 1;
                $output[$className]->missingIn = $a;

                continue;
            }

            if(! array_key_exists($b, $versions))
            {
                $output[$className]->status = 1;
                $output[$className]->missingIn = $b;

                continue;
            }

            $allMembers = array_merge($versions[$a]->members, $versions[$b]->members);

            $renamed = array();

            foreach($allMembers as $name => $member)
            {
                if(in_array($name, $renamed))
                    continue;

                $m = new JDocDiffResultMember;

                if(! array_key_exists($name, $versions[$a]->members))
                {
                    $output[$className]->status = 2;

                    if(0 == strpos('_', $name) && array_key_exists(substr($name, 1), $versions[$a]->members))
                    {

                        $m->status = 2;
                        $m->renamedTo = substr($name, 1);

                        $output[$className]->members[$name] = $m;

                        $renamed[] = $m->renamedTo;

                        continue;
                    }

                    $m->status = 1;
                    $m->missingIn = $a;

                    $output[$className]->members[$name] = $m;

                    continue;
                }

                if(! array_key_exists($name, $versions[$b]->members))
                {
                    $output[$className]->status = 2;

                    if(0 == strpos('_', $name) && array_key_exists(substr($name, 1), $versions[$b]->members))
                    {
                        $m->status = 2;
                        $m->renamedTo = substr($name, 1);

                        $output[$className]->members[$name] = $m;
                        $renamed[] = $m->renamedTo;

                        continue;
                    }

                    $m->status = 1;
                    $m->missingIn = $b;

                    $output[$className]->members[$name] = $m;

                    continue;
                }

                $output[$className]->members[$name] = $m;
            }
        }

        $this->formatHtml($output, $a, $b);
    }

    private function formatHtml($output, $a, $b)
    {
        $html = array();

        /*@var JDocDiffResult $result */
        foreach($output as $className => $result)
        {
            $html[] = '<h2 class="state'.$result->status.'">'
                .'<a name ="'.$className.'" href="#'.$className.'">'
                .$className
                .'</a>'
                .'</h2>';

            if($result->note)
                $html[] = '<h3 class="note">'.$result->note.'</h3>';

            $html[] = '<ul>';

            switch($result->status)
            {
                case 0 :
                    // The class has not changed
                    $html[] = '<li class="ok">No changes</li>';
                    break;

                case 1 :
                    // The class does not exist in one version
                    $html[] = '<li class="missing">'.sprintf('Class does not exist in: <strong>%s</strong>', $result->missingIn).'</li>';

                    break;

                case 2 :
                    // The class has changed somehow
                    foreach($result->members as $mName => $member)
                    {
                        if(1 == $member->status)
                        {
                            $html[] = '<li class="missing">'
                                .sprintf('Member <strong>%s</strong> does not exist in: <strong>%s</strong>'
                                    , $mName, $member->missingIn)
                                .'</li>';
                            continue;
                        }
                        if(2 == $member->status)
                        {
                            $html[] = '<li class="renamed">'
                                .sprintf('Member <strong>%s</strong> has been renamed to: <strong>%s</strong>'
                                    , $mName, $member->renamedTo)
                                .'</li>';
                            continue;
                        }
                    }

                    break;

                default :
                    $html[] = '<li class="missing">'.__METHOD__.' - unknown status: '.$result->status.'</li>';

                    break;
            }

            $html[] = '</ul>';
        }

        ob_start();

        $this->page = new stdClass;

        $this->page->body = implode("\n", $html);

        $versionTo = ('current' == $a || 'current' == $b)
            ? JFile::read(JPATH_BASE.'/sources/joomla-platform/current/version.txt')
            : $b;

        $this->page->tagline = sprintf('Changes in classes from version %s to %s'
            , '<span class="versionNr">'.$a.'</span>'
            , '<span class="versionNr">'.$versionTo.'</span>');

        include 'out/tmpl/default.php';

        $contents = ob_get_clean();

        JFile::write(OUTPUT_DIR.'/changes-'.$b.'.html', $contents);

        $this->out('File has been written to: '.OUTPUT_DIR.'/changes.html');

    }

    private function getNotes($path)
    {
        $xml = JFactory::getXml($path);

        if(! $xml)
            throw new Exception(__METHOD__.' - Unreadable XML file at: '.$path);

        $notes = array();

        foreach($xml->class as $class)
        {
            $note = new JDocDiffResultNote;

            $note->className = (string)$class->attributes()->name;
            $note->note = (string)$class->note;

            $notes[$note->className] = $note;
        }

        return $notes;
    }

    private function processXml($path, $key)
    {
        $xml = JFactory::getXml($path);

        if(! $xml)
            throw new Exception(__METHOD__.' - Unreadable XML file at: '.$path);

        /* @var JXMLElement $class */
        foreach($xml->class as $class)
        {
            $name = (string)$class->attributes()->name;

            if($name != (string)$class->attributes()->full)
                throw new Exception(__METHOD__.' dunno what to do :( --> '
                    .$name.' vs '.$class->attributes()->full);

            isset($this->classDiff[$name]) || $this->classDiff[$name] = array();

            $c = new JDocDiffResultClass;

            $c->xml = JFactory::getXml(dirname($path).'/'.$class->attributes()->xml);

            /* @var JXMLElement $member */
            foreach($c->xml->class->member as $member)
            {
                $c->members[(string)$member->attributes()->name] = $member;
            }

            /* @var JXMLElement $member */
            foreach($c->xml->class->method as $method)
            {
                $c->methods[(string)$method->attributes()->name] = $method;
            }

            $this->classDiff[$name][$key] = $c;
        }

        ksort($this->classDiff);
    }
}

class JDocDiffResultClass
{
    public $package = '';

    public $status = 0;

    public $missingIn = '';

    public $xml = null;

    public $members = array();

    public $methods = array();

    public $note = '';

}

class JDocDiffResultMember
{
    public $status = 0;

    public $missingIn = '';

    public $renamedTo = '';

    public $xml = null;
}

class JDocDiffResultNote
{
    public $className = '';

    public $note = '';
}

try
{
    // Execute the application.
    JApplicationCli::getInstance('JDocBuild')->execute();

    exit(0);
}
catch(Exception $e)
{
    // An exception has been caught, just echo the message.
    fwrite(STDOUT, $e->getMessage()."\n");

    exit($e->getCode());
}//try
