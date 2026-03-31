<?php
declare(strict_types=1);

namespace Bnf\NginxCache\Middleware;

/**
 * nginx_cache – NGINX Cache Manager for TYPO3
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Adminpanel\Utility\StateUtility;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;
use TYPO3\CMS\Frontend\Page\PageParts;

final readonly class CacheInterceptor implements MiddlewareInterface
{
    public function __construct(
        #[Autowire(service: 'cache.runtime')]
        private FrontendInterface $nginxCache,
        private PackageManager $packageManager,
        private Context $context,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $uri = $this->getNormalizedParams($request)->getRequestUrl();
        $cachable = (
            $request->getMethod() === 'GET' &&
            !str_contains($uri, '?') &&
            $this->getCacheInstruction($request)->isCachingAllowed() &&
            !$this->getPageParts($request)->hasNotCachedContentElements() &&
            !$this->context->getAspect('frontend.user')->isUserOrGroupSet() &&
            !$this->context->getPropertyFromAspect('workspace', 'isOffline', false) &&
            !$this->isAdminPanelVisible($request)
        );

        if (!$cachable) {
            return $response;
        }

        $lifetime = $this->getCacheDataCollector($request)->resolveLifetime();
        $pageCacheTags = array_map(
            static fn(CacheTag $cacheTag) => $cacheTag->name,
            $this->getCacheDataCollector($request)->getCacheTags()
        );

        $this->nginxCache->set(md5($uri), $uri, $pageCacheTags, $lifetime);

        /* Note: We use an explicit opt-in strategy to define requests as cachable.
         * That means this functionality relies on the "fastcgi_cache_valid 0"
         * in nginx.conf as documented in README.rst  */
        return $response->withHeader(
            'X-Accel-Expires',
            (string)($lifetime > 0
                ? $lifetime
                // unlimited is not supported by nginx
                : 24 * 60 * 60)
        );
    }

    private function getCacheInstruction(ServerRequestInterface $request): CacheInstruction
    {
        return $request->getAttribute('frontend.cache.instruction')
            ?? throw new \RuntimeException('frontend.page.parts attribute is required', 1775059553);
    }

    private function getPageParts(ServerRequestInterface $request): PageParts
    {
        return $request->getAttribute('frontend.page.parts')
            ?? throw new \RuntimeException('frontend.page.parts attribute is required', 1775059554);
    }

    private function getCacheDataCollector(ServerRequestInterface $request): CacheDataCollector
    {
        return $request->getAttribute('frontend.cache.collector')
            ?? throw new \RuntimeException('frontend.cache.collector attribute is required', 1775059555);
    }

    private function getFrontendTypoScript(ServerRequestInterface $request): FrontendTypoScript
    {
        return $request->getAttribute('frontend.typoscript')
            ?? throw new \RuntimeException('frontend.typoscript attribute is required', 1775059556);
    }

    protected function getNormalizedParams(ServerRequestInterface $request): ?NormalizedParams
    {
        return $request->getAttribute('normalizedParams')
            ?? throw new \RuntimeException('frontend.typoscript attribute is required', 1775059557);
    }

    private function isAdminPanelVisible(ServerRequestInterface $request): bool
    {
        return (
            $this->packageManager->isPackageActive('adminpanel') &&
            StateUtility::isActivatedForUser() &&
            ($this->getFrontendTypoScript($request)->getConfigArray()['admPanel'] ?? false) &&
            StateUtility::isHiddenForUser() == false
        );
    }
}
