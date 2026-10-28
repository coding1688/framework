<?php

namespace App\Libs\Ai\Sources;

use App\Libs\Ai\Exceptions\AiException;
use DOMDocument;
use DOMXPath;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class WebSourceReader
{
    public function __construct(private readonly PublicUrlPolicy $policy) {}

    /** @return array{url: string, title: string, text: string, images: list<string>} */
    public function read(string $url): array
    {
        for ($redirect = 0; $redirect <= 3; $redirect++) {
            $target = $this->policy->resolve($url);
            $address = $target['addresses'][0];
            $address = str_contains($address, ':') ? '['.$address.']' : $address;
            $maximum = (int) config('ai.sources.max_bytes', 1048576);
            $sink = new BoundedSourceStream(Utils::streamFor(fopen('php://temp', 'w+b')), $maximum);
            try {
                $response = Http::timeout((int) config('ai.sources.timeout', 15))->connectTimeout(5)
                    ->withHeaders(['Accept' => 'text/html,application/xhtml+xml,text/plain', 'User-Agent' => 'Framework/1.0 WebSourceReader'])
                    ->setHandler(new CurlHandler)
                    ->withOptions(['allow_redirects' => false, 'proxy' => '', 'sink' => $sink,
                        'on_headers' => function (ResponseInterface $headers) use ($sink): void {
                            if ($headers->getStatusCode() >= 200 && $headers->getStatusCode() < 300) {
                                $sink->checkLength((int) $headers->getHeaderLine('Content-Length'));
                                $type = strtolower(explode(';', $headers->getHeaderLine('Content-Type'))[0]);
                                if (! in_array($type, ['text/html', 'application/xhtml+xml', 'text/plain'], true)) {
                                    throw new AiException('source_failed');
                                }
                            }
                        },
                        'curl' => [CURLOPT_RESOLVE => [$target['host'].':'.$target['port'].':'.$address]],
                    ])->get($url);
                if ($response->redirect()) {
                    $location = $response->header('Location');
                    if (! $location || $redirect === 3) {
                        throw new AiException('source_failed');
                    }
                    $url = (string) UriResolver::resolve(new Uri($url), new Uri($location));
                    $response->toPsrResponse()->getBody()->close();

                    continue;
                }
                if (! $response->successful()) {
                    throw new AiException('source_failed');
                }
                $contentType = strtolower(explode(';', $response->header('Content-Type'))[0]);
                if (! in_array($contentType, ['text/html', 'application/xhtml+xml', 'text/plain'], true)) {
                    throw new AiException('source_failed');
                }
                if ((int) $response->header('Content-Length') > $maximum) {
                    throw new AiException('source_too_large');
                }
                $stream = $response->toPsrResponse()->getBody();
                if ($stream->isSeekable()) {
                    $stream->rewind();
                }
                $html = '';
                try {
                    while (! $stream->eof()) {
                        $html .= $stream->read(8192);
                        if (strlen($html) > $maximum) {
                            throw new AiException('source_too_large');
                        }
                    }
                } finally {
                    $stream->close();
                }

                return $this->extract($html, $url, $contentType);
            } catch (AiException $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                throw new AiException($sink->exceeded() ? 'source_too_large' : 'source_failed');
            } finally {
                $sink->close();
            }
        }

        throw new AiException('source_failed');
    }

    /** @return array{url: string, title: string, text: string, images: list<string>} */
    private function extract(string $html, string $url, string $contentType): array
    {
        $textLimit = (int) config('ai.sources.max_text_length', 24000);
        if ($contentType === 'text/plain') {
            return ['url' => $url, 'title' => '', 'text' => mb_substr($html, 0, $textLimit), 'images' => []];
        }
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);
        $title = trim($xpath->evaluate('string(//title)'));
        $images = [];
        foreach ($xpath->query('//meta[@property="og:image"]/@content | //img/@src | //img/@data-src') as $attribute) {
            try {
                $image = (string) UriResolver::resolve(new Uri($url), new Uri($attribute->nodeValue));
                $this->policy->resolve($image);
                $images[] = $image;
            } catch (Throwable) {
                continue;
            }
            if (count($images) >= 16) {
                break;
            }
        }
        foreach ($xpath->query('//script | //style | //noscript | //nav | //footer | //header') as $element) {
            $element->parentNode?->removeChild($element);
        }
        foreach ($xpath->query('//p | //div | //li | //tr | //h1 | //h2 | //h3 | //br') as $element) {
            $element->appendChild($document->createTextNode("\n"));
        }
        $text = preg_replace('/[\t ]+/u', ' ', $document->textContent) ?? '';
        $text = preg_replace('/\n\s*\n+/u', "\n", $text) ?? $text;

        return ['url' => $url, 'title' => mb_substr($title, 0, 256), 'text' => mb_substr(trim($text), 0, $textLimit), 'images' => array_values(array_unique($images))];
    }
}
