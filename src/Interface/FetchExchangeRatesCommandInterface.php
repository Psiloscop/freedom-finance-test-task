<?php

namespace App\Interface;

use DateTimeZone;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface FetchExchangeRatesCommandInterface
{
    function getRoutingKey(): string;

    function getSource(): string;

    function getBaseCurrency(): string;

    function getTimezone(): DateTimeZone;

    function setDate(DateTimeInterface $date): void;

    function getDate(): DateTimeInterface;
}