<?php

namespace Aschmelyun\Cleaver\Compilers;

use Aschmelyun\Cleaver\Engines\FileEngine;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class JsonCompiler extends Compiler
{

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @param SplFileInfo $file
     */
    public function __construct(SplFileInfo $file)
    {
        $this->file = $file;

        $this->httpClient = HttpClient::create();

        $this->json = json_decode(
            $file->getContents()
        );

        foreach($this->json as $idx => $item) {
            if (
                (is_string($item)) &&
                (substr($item, 0, 5) === '/data') &&
                (substr($item, -5, 5) === '.json') &&
                (file_exists(FileEngine::$resourceDir . $item))
            ) {
                $this->json->{$idx} = json_decode(file_get_contents(FileEngine::$resourceDir . $item));
                continue;
            }

            if (
                (is_string($item)) &&
                (substr($item, 0, 5) === 'json:')
            ) {
                $url = substr($item, 5);
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    $this->json->{$idx} = (object) $this->httpClient->request('GET', $url)->toArray();
                }
            }
        }

        $this->json->mix = FileEngine::mixManifestData();
    }

}
