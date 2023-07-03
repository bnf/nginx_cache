<?php

declare(strict_types=1);

namespace Bnf\CacheStatus;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

final class CacheHit implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($response instanceof NullResponse) {
            return $response;
        }

        $tsfe = $request->getAttribute('frontend.controller', $GLOBALS['TSFE'] ?? null);
        if (!$tsfe instanceof TypoScriptFrontendController) {
            return $response;
        }

        return $response->withHeader(
            'X-TYPO3-Cache',
            $this->hitCache($tsfe) ? 'HIT' : 'MISS'
        );
    }

    private function hitCache(TypoScriptFrontendController $tsfe): bool
    {
        return call_user_func(\Closure::bind(function() use ($tsfe) {
            return $tsfe->pageContentWasLoadedFromCache ?? $tsfe->cacheContentFlag ?? false;
        }, null, $tsfe));
    }
}
