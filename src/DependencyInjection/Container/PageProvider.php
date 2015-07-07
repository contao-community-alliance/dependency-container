<?php

/**
 * Dependency Container for Contao Open Source CMS
 * Copyright (C) 2013 Tristan Lins
 *
 * PHP version 5
 *
 * @copyright  (c) 2013 Contao Community Alliance
 * @author         Tristan Lins <tristan.lins@bit3.de>
 * @author         Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package        dependency-container
 * @license        LGPL-3.0+
 * @filesource
 */

namespace DependencyInjection\Container;

/**
 * A provider that provide the current active page model.
 */
class PageProvider
{
    /**
     * The current page.
     *
     * @var \PageModel|null
     */
    private $page;

    /**
     * Singleton service.
     *
     * @return PageProvider
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public static function getInstance()
    {
        return $GLOBALS['container']['page-provider'];
    }

    /**
     * Get the current page.
     *
     * @return \PageModel|null
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Set the current page.
     *
     * @param \PageModel $page The page model.
     *
     * @return static
     * @internal
     */
    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }
}
