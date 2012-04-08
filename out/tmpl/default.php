<?php
/**
 * Created by JetBrains PhpStorm.
 * User: elkuku
 * Date: 08.04.12
 * Time: 00:51
 * To change this template use File | Settings | File Templates.
 */

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>J!Doc - Differences</title>
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon"/>

    <link rel="stylesheet" type="text/css" media="screen" href="stylesheets/stylesheet.css">
    <link rel="stylesheet" type="text/css" media="screen" href="stylesheets/jdoc.css"/>

</head>

<body>

<div id="header_wrap" class="outer">
    <header class="inner">

    <h1 id="project_title"><a href="index.html">J!Doc</a></h1>
    <h2 id="project_tagline"><?= $this->page->tagline ?></h2>
        </header>
</div>

<div class="outer">






    <?= $this->page->body ?>
</div>

<div id="footer_wrap" class="outer">
    <footer class="inner">
        <p class="copyright">JDoc is made with the help of <a href="https://github.com/theseer/phpdox">phpdox</a> and is maintained by <a href="https://github.com/elkuku">elkuku</a></p>
    </footer>
</div>

</body>

</html>

