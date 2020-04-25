<?php

namespace Haukurh\DBAL\DSN;


class SqliteMemory implements DSNInterface
{
    /**
     * Returns DSN URI
     *
     * @return string
     */
    public function toString(): string
    {
        return "sqlite::memory:";
    }

    public function __toString()
    {
        return $this->toString();
    }
}
