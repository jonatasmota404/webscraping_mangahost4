<?php

require "vendor/autoload.php";
require "ZipDownload.php";

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Exception\CommunicationException;
use HeadlessChromium\Exception\EvaluationFailed;

$browserFactory = new BrowserFactory();
echo "Digite a url\n";
$url = readline();
$exit = false;

while ($exit !== true){
    echo "Digite a quantidade\n";
    $qtd = readline();
    if (!$qtd || !is_numeric($qtd)){
        echo "Digite um número\n";
    }else{
        $exit = true;
    }
}

echo "Digite o caminho para o manga\n";
$mangaDir = readline();

// starts headless chrome
$browser = $browserFactory->createBrowser(['customFlags' => ['--lang=pt-BR']]);

try {
    // creates a new page and navigate to an url
    $page = $browser->createPage();
    $page->navigate($url)->waitForNavigation();

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
        if ($index >= $qtd){
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
} catch (CommunicationException | EvaluationFailed | Exception $e) {
    echo $e->getMessage();
} finally {
    // bye
    $browser->close();
    $zip = new ZipDownload($mangaTitle, 'manga');
    $zip->createZipArchive($mangaDir);
    $zip->clear();

}
