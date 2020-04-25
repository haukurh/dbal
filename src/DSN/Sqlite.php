<?php

namespace Haukurh\DBAL\DSN;


class Sqlite implements DSNInterface
{
    protected $filename;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * Returns DSN URI
     *
     * @return string
     */
    public function toString(): string
    {
        return "sqlite:{$this->filename}";
    }

    public function __toString()
    {
        return $this->toString();
    }
}
