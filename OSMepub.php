<?php
namespace OSMepub;

include_once('./Zip.php');

function GUID() {
    if (function_exists('com_create_guid') === true) {
        return trim(com_create_guid(), '{}');
    }
    
    return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}

function IsNullOrEmpty($str) {
    if (isset($str)) {
        $str = trim($str);
        return empty($str);
    }
    
    return TRUE;
}

function rrmdir($dir) { 
    if (is_dir($dir)) { 
        $objects = scandir($dir); 
        foreach ($objects as $object) { 
            if ($object != "." && $object != "..") { 
                if (filetype($dir."/".$object) == "dir") 
                    rrmdir($dir."/".$object); 
                else
                    unlink($dir."/".$object); 
            }
        } 
        reset($objects); 
        rmdir($dir); 
    } 
} 

class Ebook {
    //Given OR Generated automatically   
    private $id; //unique book identifier.

    //Set by user
    private $title; //Book title
    private $language = "en"; //Book language
    private $identifier;
    private $authors; //An array of author objects
    private $description;
    private $subject;
    private $rights;
    private $date;
    private $relation;
    private $contributors;

    private $showCover = FALSE; //Do we have a cover?
    private $showTOC = FALSE;  //Do we have a table of contents or not?

    //Handled internally
    private $files; //An array of static files to be created. 
    private $chapters; //An array of chapter objects

    private $zip;

    private $fileHead = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';
    private $contentOpf;
    private $tocNcx;
    private $navPointCount;

    // Constructor and Destructor
    function __construct($title, $id) {
        $this->title = $title;
        $this->identifier = $id;
        $this->id = GUID();
        $this->chapters = array();
        $this->contributors = array();
        $this->navPointCount = 0;

        $this->files = array(
            "container" => array("name" => "META-INF/container.xml", "type" => "static", "content" => "<?xml version=\"1.0\"?>\n<container version=\"1.0\" xmlns=\"urn:oasis:names:tc:opendocument:xmlns:container\">\n<rootfiles>\n<rootfile full-path=\"content.opf\" media-type=\"application/oebps-package+xml\"/>\n</rootfiles>\n</container>")
        );

    }

    function __destruct() {

    }

    //Properties
    function VisibleTOC($arg) {
        if (isset($arg)) {
            $this->showTOC = $arg;
        }

        return $this->showTOC;
    }

    function addAuthor($name) {
        array_push($this->contributors, array(
            "is_creator" => true,
            "name" => $name,
            "type" => "aut"
        ));
    }

    function addContributor($name, $type) {
        array_push($this->contributors, array(
            "is_creator" => FALSE,
            "name" => $name,
            "type" => $type
        ));   
    }

    // Use functions
    function addStyling($file, $content) {
        array_push($this->files, array("name" => $file, "type" => "css", "contents" => $content));
    }

    function addCover($content) {
        $this->showCover = TRUE;

        $this->files['cover'] = array("name" => "cover.html", "type" => "cover", "content" => $content));
    }

    function addChapter($chapter_title, $content) {
        $content = $this->fileHead . "<html xmlns=\"http://www.w3.org/1999/xhtml\"><head><title>$chapter_title</title></head><body><h1>$chapter_title</h1>$content</body></html>";

        $id = "Chapter" . count($this->chapters) . ".html";
        $chapter = array("id" => $id, "title" => $chapter_title, "content" => $content);
        array_push($this->chapters, $chapter);
    }
     
    function Generate() {
        $this->writeContentOpf();
        $this->writeTOC();

        $this->createArchive();
    }



    // Internal use functions
    function writeTOC($title = "Table of Contents", $cssFileName = NULL) {
        //A TOC.ncx file is required
        @date_default_timezone_set("GMT");

        $w = new \XMLWriter();
        $w->openMemory();
        $w->startDocument('1.0');
        $w->setIndent(4);

        $w->writeRaw("<!DOCTYPE ncx PUBLIC \"-//NISO//DTD ncx 2005-1//EN\" \"http://www.daisy.org/z3986/2005/ncx-2005-1.dtd\">");
        $w->startElement('ncx');
            $w->writeAttribute('xmlns', 'http://www.daisy.org/z3986/2005/ncx/');
            $w->writeAttribute('version', '2005-1');
            $w->startElement('head');
                $w->startElement('meta');
                    $w->writeAttribute('name', 'dtb:uid');
                    $w->writeAttribute('content', $this->identifier);
                $w->endElement();

                $w->startElement('meta');
                    $w->writeAttribute('name', 'dtb:depth');
                    $w->writeAttribute('content', '2');
                $w->endElement();

                $w->startElement('meta');
                    $w->writeAttribute('name', 'dtb:totalPageCount');
                    $w->writeAttribute('content', '0');
                $w->endElement();

                $w->startElement('meta');
                    $w->writeAttribute('name', 'dtb:maxPageNumber');
                    $w->writeAttribute('content', '0');
                $w->endElement();
            $w->endElement();

            $w->startElement('docTitle');
                $w->startElement('text');
                    $w->text($this->title);
                $w->endElement();
            $w->endElement();

            $w->startElement('navMap');
                if ($this->showCover)
                    $this->createNavPoint($w, $this->title, 'cover.html');

                if ($this->showTOC)
                    $this->createNavPoint($w, 'Table of Contents', 'toc.html');

                foreach($this->chapters as &$chapter) {
                    $this->createNavPoint($w, $chapter['title'], $chapter['id']);
                }
            $w->endElement();
        $w->endElement();
        $w->endDocument();   
        $this->tocNcx = $w->outputMemory(TRUE);

        //But a visible .html TOC is not
        if(!$this->showTOC)
            return;

        $w = new \XMLWriter();
        $w->openMemory();
        $w->startDocument('1.0', 'UTF-8');
        $w->setIndent(4);

        $w->writeRaw("<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">");
        $w->startElement('html');
            $w->writeAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
            $w->writeElement('head');
                $w->writeElement('title');
                    $w->text($title);
                $w->endElement();
            $w->endElement();

            $w->writeElement('body');
                $w->writeElement('h1');
                    $w->text($title);
                $w->endElement();

                foreach($this->chapters as &$chapter) {
                    $w->writeElement('p');
                        $w->writeElement('a');
                            $w->writeAttribute('href', $chapter['id']);
                            $w->text($chapter['title']);
                        $w->endElement()
                    $w->endElement();
                }
            $w->endElement();
        $w->endElement(); //</html>
        $w->endDocument();

        $this->files['toc'] = array('name' => 'toc.html', 'type' => 'toc', "content" => $w->outputMemory(TRUE));
    }

    function writeContentOpf() {
        @date_default_timezone_set("GMT");

        $w = new \XMLWriter();
        $w->openMemory();
        $w->startDocument('1.0');
        $w->setIndent(4);

        $w->startElement('package');
            $w->writeAttribute('version', '2.0');
            $w->writeAttribute('xmlns', 'http://www.idpf.org/2007/opf');
            $w->writeAttribute('unique-identifier', 'dcidid');
            $w->startElement('metadata');
                $w->writeAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
                $w->writeAttribute('xmlns:dcterms', 'http://purl.org/dc/terms/');
                $w->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
                $w->writeAttribute('xmlns:opf', 'http://www.idpf.org/2007/opf');

                $w->writeElement('dc:title', $this->title);

                if(!IsNullOrEmpty($this->language)) {
                    $w->startElement('dc:language');
                        $w->writeAttribute('xsi:type', 'dcterms:RFC3066');
                        $w->text($this->language);
                    $w->endElement();
                }

                $w->startElement('dc:identifier');
                    $w->writeAttribute('id', 'dcidid');
                    $w->writeAttribute('opf:scheme', 'URI');
                    $w->text($this->identifier);
                $w->endElement();

                if(!IsNullOrEmpty($this->subject)) {
                    $w->startElement('dc:subject');
                        $w->text($this->subject);
                    $w->endElement();
                }

                if (!IsNullOrEmpty($this->description)) {
                    $w->startElement('dc:description');
                        $w->text($this->description);
                    $w->endElement();
                }

                if (!IsNullOrEmpty($this->relation)) {
                    $w->startElement('dc:relation');
                        $w->text($this->relation);
                    $w->endElement();
                }

                foreach($this->contributors as &$contributor) {
                    if ($contributor['is_creator']) {
                        $w->startElement('dc:creator');
                            $w->writeAttribute('opf:role', $contributor['type']);
                            $w->text($contributor['name']);
                        $w->endElement();
                    } else {
                        $w->startElement('dc:contributor');
                            $w->writeAttribute('opf:role', $contributor['type']);
                            $w->text($contributor['name']);
                        $w->endElement();
                    }
                }

                $w->startElement('dc:date');
                    $w->writeAttribute('xsi:type', 'dcterms:W3CDTF');
                    if(IsNullOrEmpty($this->date)) {
                        $this->date = date('Y-m-d');
                    }
                    $w->text($this->date);
                $w->endElement();

                if(!IsNullOrEmpty($this->rights)) {
                    $w->startElement('dc:rights');
                        $w->text($this->rights);
                    $w->endElement();
                }
            $w->endElement(); //End METADATA

            $w->startElement('manifest');
                if ($this->showCover) {
                    $w->startElement('item');
                        $w->writeAttribute('id', 'cover');
                        $w->writeAttribute('href', 'cover.html');
                        $w->writeAttribute('media-type', 'application/xhtml+xml');
                    $w->endElement();
                }

                $w->startElement('item');
                    $w->writeAttribute('id', 'ncx');
                    $w->writeAttribute('href', 'toc.ncx');
                    $w->writeAttribute('media-type', 'application/x-dtbncx+xml');
                $w->endElement();

                foreach ($this->chapters as &$chapter) {
                    $w->startElement('item');
                        $w->writeAttribute('id', $chapter["id"]);
                        $w->writeAttribute('href', $chapter["id"]);
                        $w->writeAttribute('media-type', 'application/xhtml+xml');
                    $w->endElement();
                }
            $w->endElement(); //End MANIFEST

            $w->startElement('spine');
                $w->writeAttribute('toc', 'ncx');
                if ($this->showCover) {
                    $w->startElement('itemred');
                        $w->writeAttribute('idref', 'cover');
                    $w->endElement();
                }

                $w->startElement('itemref');
                    $w->writeAttribute('idref', 'ncx');
                $w->endElement();
                

                foreach ($this->chapters as &$chapter) {
                    $w->startElement('itemref');
                        $w->writeAttribute('idref', $chapter["id"]);
                    $w->endElement();
                }
            $w->endElement(); //End SPINE

            //$w->startElement('guide');

            //$w->endElement(); //End GUIDE
        $w->endElement();  //End PACKAGE
        $w->endDocument();   
        $this->contentOpf = $w->outputMemory(TRUE);
    }

    private function createArchive() {
        $excludes = array('.', '..', '.DS_Store', 'mimetype');

        $this->zip = new Zip();
        $this->zip->setExtraField(FALSE);
        $this->zip->addFile("application/epub+zip", "mimetype", 0, NULL, FALSE);
        $this->zip->setExtraField(TRUE);
        
        $this->zip->addDirectory("META-INF");
        $this->zip->addFile($this->files['container']['content'], 'META-INF/container.xml');

        $this->zip->addFile($this->contentOpf, 'content.opf');
        $this->zip->addFile($this->tocNcx, 'toc.ncx');

        if ($this->showCover) {
            $c = $this->files['cover'];
            $this->zip->addFile($c['content'], $c['name']);
        }

        if ($this->showTOC) {
            $t = $this->files['toc'];
            $this->zip->addFile($t['content'], $t['name']);
        }

        foreach($this->chapters as &$chapter) {
            $this->zip->addFile($chapter["content"], $chapter["id"]);
        }

        $this->zip->sendZip($this->title . ".epub");
    }

    private function createNavPoint($writer, $title, $src) {
        $this->navPointCount++;

        $writer->startElement('navPoint');
            $writer->writeAttribute('id', 'navPoint-' . $this->navPointCount);
            $writer->writeAttribute('playOrder', $this->navPointCount);
            $writer->startElement('navLabel');
                $writer->startElement('text');
                    $writer->text($title);
                $writer->endElement();
            $writer->endElement();
            $writer->startElement('content');
                $writer->writeAttribute('src', $src);
            $writer->endElement();
        $writer->endElement();
    }
}
?>