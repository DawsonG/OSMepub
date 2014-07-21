<?php
include_once('./OSMepub.php');

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $gen = new OSMepub\Ebook("OSMtestbook", "http://localhost:35873/this");

    //$gen->addCover("<h1>OSMtestbook</h1><h3>An experiment by Dawson Goodell and OSMstudios.</h3>");
    //$gen->visibleTOC(TRUE);
    $gen->addChapter("Chapter 1", "<p>Some content for the first chapter.</p>");
    $gen->addChapter("Chapter 2", "<p>Some content for the second chapter.</p>");
    $gen->addChapter("Chapter 3", "<p>Some content for the third chapter.</p>");
    $gen->addChapter("Chapter 4", "<p>Some content for the fourth chapter.</p>");
    $gen->addChapter("Chapter 5", "<p>Some content for the fifth chapter.</p>");
    $gen->addChapter("Chapter 6", "<p>Some content for the sixth chapter.</p>");

    $gen->Generate();
    echo 'Post Back';
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>OSMepub Test Page</title>
    </head>
    <body>
        <form action="/" method="post">
            <button type="submit">Generate Simple Book</button>
        </form>
<pre>{
    "id": "com.acme.books.MyUniqueBookID",
    "title": "Sample eBook Title",
    "language": "en",
 
    "authors": [
        { "name": "John Smith", "role": "aut"},
        { "name": "Jane Appleseed", "role": "dsr"}
    ],
 
    "description": "Brief description of the book",
    "subject": "List of keywords, pertinent to content, separated by commas",
    "publisher": "ACME Publishing Inc.",
    "rights": "Copyright (c) 2013, Someone",
    "date": "2013-02-27",
    "relation": "http://www.acme.com/books/MyUniqueBookIDWebEdition/",
 
    "files": {
        "coverpage": "coverpage.md",
        "title-page": "titlepage.md",
        "include": [
            { "id":"ncx", "path":"toc.ncx" },
            "cover.jpg",
            "style.css",
            "*.md",
            "media/*"
        ],
        "index": "index.md",
        "exclude": []
    },
 
    "spine": {
        "toc": "ncx",
        "items": [
            "coverpage",
            "title-page",
            "copyright",
            "foreword",
            "|^c\d{1,2}-.*$|",
            "index"
        ]
    }
}</pre>
    </body>
</html>
