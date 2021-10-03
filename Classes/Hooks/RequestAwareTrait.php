<?php
declare(strict_types=1);

namespace Qbus\NginxCache\Hooks;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\Utility\StateUtility;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

trait RequestAwareTrait
{
    protected function getServerRequest(): ServerRequestInterface
    {
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface) {
            return $GLOBALS['TYPO3_REQUEST'];
        }
        return ServerRequestFactory::fromGlobals();
    }

    protected function getTypoScriptFrontendController(): ?TypoScriptFrontendController
    {
        $request = $this->getServerRequest();
        return $request->getAttribute('frontend.controller', $GLOBALS['TSFE'] ?? null);
    }

    protected function isAdminPanelVisible(): bool
    {
        return (
            ExtensionManagementUtility::isLoaded('adminpanel') &&
            StateUtility::isActivatedForUser() &&
            StateUtility::isActivatedInTypoScript() &&
            StateUtility::isHiddenForUser() == false
        );
    }

    protected function isFrontendEditingActive(TypoScriptFrontendController $frontendController): bool
    {
        return (
            /* Note: we do not use $GLOBALS['BE_USER']->isFrontendEditingActive() as that checks
             * additional for adminPanel->isAdminModuleEnabled('edit'), but that has no influence
             * on cache clearing. */
            $frontendController->displayEditIcons == 1 ||
            $frontendController->displayFieldEditIcons == 1
        );
    }

    protected function getUri(ServerRequestInterface $request): string
    {
        $normalizedParams = $this->getNormalizedParams($request);
        if ($normalizedParams !== null) {
            return $normalizedParams->getRequestUrl();
        }

       return (string)$request->getUri();
    }

    protected function getNormalizedParams(ServerRequestInterface $request): ?NormalizedParams
    {
        $normalizedParams = $request->getAttribute('normalizedParams');
        if ($normalizedParams instanceof NormalizedParams) {
            return $normalizedParams;
        }
        return null;
    }
}
