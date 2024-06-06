<?php

namespace App\Message\Command;

use DateTimeInterface;

class FetchExchangesFromCbrCommand
{
    public function __construct(
        private readonly DateTimeInterface $dateToFetch,
    )
    {}

    public function getDateToFetch(): DateTimeInterface
    {
        return $this->dateToFetch;
    }
}