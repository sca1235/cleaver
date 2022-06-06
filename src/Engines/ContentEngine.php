<?php

namespace Aschmelyun\Cleaver\Engines;

use Aschmelyun\Cleaver\Compilers\JsonCompiler;
use Aschmelyun\Cleaver\Compilers\MarkdownCompiler;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Tightenco\Collect\Support\Collection;

class ContentEngine
{

    /**
     * @param FileEngine $fileEngine
     * @param string|null $pageBuildOverride
     * @return Collection
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public static function generateCollection(FileEngine $fileEngine, ?string $pageBuildOverride = null): Collection
    {
        $content = [];
        foreach($fileEngine->getContentFiles($pageBuildOverride) as $contentFile) {
            $compiler = null;
            $ext = pathinfo($contentFile, PATHINFO_EXTENSION);
            switch($ext) {
                case 'json':
                    $compiler = new JsonCompiler($contentFile);
                    break;
                case 'md':
                case 'markdown':
                    $compiler = new MarkdownCompiler($contentFile);
                    break;
                default:
                    break;
            }

            if($compiler && $compiler->checkContent(false)) {
                $content[] = $compiler->json;
            }
        }

        return new Collection($content);
    }

}