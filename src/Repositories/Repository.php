<?php

namespace Smartisan\Settings\Repositories;

use Smartisan\Settings\Contracts\Repository as RepositoryContract;
use Smartisan\Settings\EntryFilter;

abstract class Repository implements RepositoryContract
{
    /**
     * Settings filter instance.
     */
    protected EntryFilter $entryFilter;

    /**
     * Set settings filter.
     */
    public function withFilter(EntryFilter $filter): Repository
    {
        $this->entryFilter = $filter;

        return $this;
    }
}
