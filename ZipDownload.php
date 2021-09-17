<?php


class ZipDownload extends ZipArchive
{
    public string $fileName;
    public string $dir;
    private string $path;
    private string $fullPath;

    public function __construct(string $fileName, string $dir)
    {
        $this->fileName = $fileName;
        $this->dir = $dir;
        $this->path = __DIR__ . '\\' . $this->dir;
        $this->fullPath = $this->path . '\\' . $this->fileName . '.zip';
    }

    private function listDir($dir) :array
    {
        $scanDir = scandir($this->path.'/'. $dir);
        array_shift($scanDir);
        array_shift($scanDir);
        return $scanDir;
    }

    private function listSubDir(): array
    {
        $scanDir = scandir($this->path);
        array_shift($scanDir);
        array_shift($scanDir);
        return $scanDir;
    }

    public function createZipArchive(?string $path) :void
    {
        $fullPath = is_null($path)?$this->fullPath:$path."\\".$this->fileName . ".zip";
        // Criamos o arquivo e verificamos se ocorreu tudo certo
        if( $this->open($fullPath, ZipArchive::CREATE) ){
            $listSubDir = $this->listSubDir();

            foreach ($listSubDir as $dir){
                $scanDir = $this->listDir($dir);
                // adiciona ao zip todos os arquivos contidos no diretório.
                foreach($scanDir as $file){
                    $this->addFile($this->path.'\\'.$dir."\\".$file, $dir."\\".$file);
                }
            }
            // fechar o arquivo zip após a inclusão dos arquivos desejados
            $this->close();
        }
    }

    public function ZipDownloadArchive() :void
    {
        // Primeiro nos certificamos de que o arquivo zip foi criado.
        if(file_exists($this->fullPath)){
            // Forçamos o download do arquivo.
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="'.$this->fileName.'.zip'.'"');
            readfile($this->fullPath);
            //removemos o arquivo zip após download
            unlink($this->fullPath);
        }
    }

    public function clear(){
        unlink($this->path);
    }
}