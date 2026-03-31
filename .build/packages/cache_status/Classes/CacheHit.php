<?php

declare(strict_types=1);

namespace Bnf\CacheStatus;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\NullResponse;

final readonly class CacheHit implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($response instanceof NullResponse) {
            return $response;
        }

        return $response->withHeader(
            'X-TYPO3-Cache',
            $this->hitCache($request) ? 'HIT' : 'MISS'
        );
    }

    private function hitCache(ServerRequestInterface $request): bool
    {
        return $request->getAttribute('frontend.page.parts')?->hasPageContentBeenLoadedFromCache() ?? false;
    }
}
