<?php

namespace App\Interface;

use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface ExchangeRateServiceInterface
{
    function fetchData(DateTimeInterface $date, string $baseCurrency): array;
}