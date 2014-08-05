<?php
include_once('./OSMepub.php');

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $gen = new OSMepub\Ebook("OSMtestbook", "http://localhost:35873/this");
    $gen->addAuthor("Dawson Goodell");
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
    $gen->saveFile('./test.epub');
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>OSMepub Test Page</title>

        <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet">
        <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css" rel="stylesheet">
        <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
    </head>
    <body>
        <div class="container">
            <h1>OSMepub Test Cases</h1>

            <div class="row">
                <div class="span3">
                
                </div>
                <div class="span3">
                
                </div>
                <div class="span3">
                
                </div>
                <div class="span3">
                
                </div>
            </div>
        </div>
    </body>
</html>
