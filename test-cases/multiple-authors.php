<?php
include_once('../OSMepub.php');

$gen = new OSMepub\Ebook("OSMtestbook", "http://localhost:35873/this");
$gen->addAuthor("Dawson Goodell");
$gen->addAuthor("Billy Bob");
$gen->addAuthor("Jimmy the Grape");
$gen->addContributor("OSM Publishing House", "pbl"); //Types taken from http://www.loc.gov/marc/relators/relacode.html

$gen->setCover("<h1>OSMtestbook</h1><h3>An experiment by Dawson Goodell and OSMstudios.</h3>");
$gen->visibleTOC(TRUE);
$gen->addChapter("Chapter 1", "<p>Some content for the first chapter.</p>");
$gen->addChapter("Chapter 2", "<p>Some content for the second chapter.</p>");
$gen->addChapter("Chapter 3", "<p>Some content for the third chapter.</p>");
$gen->addChapter("Chapter 4", "<p>Some content for the fourth chapter.</p>");
$gen->addChapter("Chapter 5", "<p>Some content for the fifth chapter.</p>");
$gen->addChapter("Chapter 6", "<p>Some content for the sixth chapter.</p>");

$gen->sendFile();
?>