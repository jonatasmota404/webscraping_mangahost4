<?php

require "vendor/autoload.php";

use HeadlessChromium\BrowserFactory;

$browserFactory = new BrowserFactory();

// starts headless chrome
$browser = $browserFactory->createBrowser(['customFlags' => ['--lang=pt-BR']]);

try {
    // creates a new page and navigate to an url
    $page = $browser->createPage();
    $page->navigate('https://mangahost4.com/manga/rosariovampire-mh13937')->waitForNavigation();

    //get manga title
    $mangaTitle = $page->evaluate("document.querySelector('.title').innerText")->getReturnValue(9999999999);

    // get manga links
    $mangaLinks = $page->evaluate("
        let linksElements = document.querySelectorAll('div.pop-content div.tags a');
        let links = [];
        for(let index = 0; index<linksElements.length; index++){
            links.push(linksElements[index].href);
        }
        links
    ")->getReturnValue(999999999);

    //get image links from manga
    foreach (array_reverse($mangaLinks) as $index => $mangaLink){
        if ($index >=5){
            break;
        }
        $chapterArray = explode("/", $mangaLink);
        $chapter = end($chapterArray);
        $dir = "manga\\$mangaTitle capítulo-$chapter";

        $page->navigate($mangaLink)->waitForNavigation();

        $imageLinks = $page->evaluate("
        let elements = document.querySelectorAll('#slider img');
        let images = [];
        for(let index = 0; index<elements.length; index++){
            images.push(elements[index].src);
        }
        images")->getReturnValue(999999999);;

        if (!is_dir($dir)){
            if (!mkdir($dir) && !is_dir($dir)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
            if ($dh = opendir($dir)) {
                foreach ($imageLinks as $key => $imageLink){
                    $imageStream =  file_put_contents("$dir\\$mangaTitle-$key.jpg", file_get_contents($imageLink));
                }
            }
            closedir($dh);
        }
    }
} finally {
    // bye
    $browser->close();
}
