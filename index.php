<?php

require "vendor/autoload.php";
require "Zip.php";

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Exception\CommunicationException;
use HeadlessChromium\Exception\EvaluationFailed;

$browserFactory = new BrowserFactory();

/*Inicia leitura de dados pelo terminal*/
echo "Digite a url\n";
$url = readline();
$exit = false;

while ($exit !== true){
    echo "Digite o capítulo \n";
    $chapter = readline();

    switch ($chapter) {
        case ($chapter === false):
            echo "Campo vazio. Digite um valor\n";
            $chapter = readline();
            break;
        case (!is_numeric($chapter)):
            echo "Campo não é um número. Digite um valor\n";
            $chapter = readline();
            break;
        default:
            $exit = true;
    }

    echo "Digite a quantidade\n";
    $qtd = readline();

    switch ($qtd) {
        case ($qtd === false):
            echo "Campo vazio. Digite um valor\n";
            $qtd = readline();
            break;
        case (!is_numeric($qtd)):
            echo "Campo não é um número. Digite um valor\n";
            $qtd = readline();
            break;
        default:
            $exit = true;
    }

    $exit = true;
}

echo "Digite o caminho para o manga\n";
$mangaDir = readline();
/*Termina leitura de dados pelo terminal*/

// inicia o headless chrome
$browser = $browserFactory->createBrowser(['customFlags' => ['--lang=pt-BR']]);

try {
    //cria uma nova página e navega pela url
    $page = $browser->createPage();
    $page->navigate($url)->waitForNavigation();

    //pega o título do manga
    $mangaTitle = $page->evaluate("document.querySelector('.title').innerText")->getReturnValue(9999999999);

    // define valores padrões caso a variável não seja do tipo previsto.
    $chapter = is_numeric($chapter)?$chapter:1;
    $qtd = is_numeric($qtd)?$qtd:1;

    for ($index = 0;$index < $qtd; $index++ ){
        $url .= "/$chapter";

        //cria pasta que vai armazenar os mangás baixados temporariamente.
        if (!mkdir('manga') && !is_dir('manga')) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', 'manga'));
        }
        $dir = "manga/$mangaTitle capítulo-$chapter";

        //navega na url
        $page->navigate($url)->waitForNavigation();

        //seleciona todas as imagens do manga no link com código JavaScript
        $imageLinks = $page->evaluate("
        let elements = document.querySelectorAll('#slider img');
        let images = [];
        for(let index = 0; index<elements.length; index++){
            images.push(elements[index].src);
        }
        images")->getReturnValue(999999999);
        //verifica se o diretório existe, se não, cria um.
        if (!is_dir($dir)){
            if (!mkdir($dir) && !is_dir($dir)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
            //abre o diretório se ele existir
            if ($dh = opendir($dir)) {
                foreach ($imageLinks as $key => $imageLink){
                    //insere
                    file_put_contents("$dir/$mangaTitle-$key.jpg", file_get_contents($imageLink));
                }
            }
            closedir($dh);
        }
        $chapter++;
    }
} catch (CommunicationException | EvaluationFailed | Exception $e) {
    echo $e->getMessage();
} finally {
    // fecha o browser
    $browser->close();

    //intancia o objeto zip
    $zip = new Zip($mangaTitle, 'manga');
    //cria o arquivo zip
    $zip->createZipArchive($mangaDir);
    //limpa o diretório
    $zip->clear();
}
