<?php

namespace THM\Organizer\Tables;

/**
 * Explicitly allows Joomla to set null values to table properties.
 */
trait Nullable
{
    /**
     * @inheritDoc
     */
    public function store($updateNulls = true): bool
    {
        /** @noinspection PhpMultipleClassDeclarationsInspection */
        return parent::store($updateNulls);
    }
}