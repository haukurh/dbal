<?php

namespace Haukurh\DBAL\DSN;


interface DSNInterface
{
    public function toString();

    public function __toString();
}
