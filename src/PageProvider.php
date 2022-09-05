<?php

/**
 * This file is part of contao-community-alliance/dependency-container.
 *
 * (c) 2013-2018 Contao Community Alliance <https://c-c-a.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao-community-alliance/dependency-container
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Tristan Lins <tristan@lins.io>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2013-2018 Contao Community Alliance <https://c-c-a.org>
 * @license    https://github.com/contao-community-alliance/dependency-container/blob/master/LICENSE LGPL-3.0
 * @link       https://github.com/contao-community-alliance/dependency-container
 * @filesource
 */

namespace DependencyInjection\Container;

use Contao\PageModel;

/**
 * A provider that provide the current active page model.
 */
class PageProvider
{
    /**
     * The current page.
     *
     * @var PageModel|null
     */
    private ?PageModel $page = null;

    /**
     * Singleton service.
     *
     * @psalm-suppress MixedInferredReturnType
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public static function getInstance(): PageProvider
    {
        /**
         * @psalm-suppress MixedReturnStatement
         * @psalm-suppress MixedArrayAccess
         */
        return $GLOBALS['container']['page-provider'];
    }

    /**
     * Get the current page.
     */
    public function getPage(): ?PageModel
    {
        return $this->page;
    }

    /**
     * Set the current page.
     *
     * @param PageModel $page The page model.
     *
     * @internal
     */
    public function setPage(PageModel $page): PageProvider
    {
        $this->page = $page;
        return $this;
    }
}
