<?php

namespace App\Message\Command;

use DateTimeZone;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use App\Interface\FetchExchangeRatesCommandInterface;

#[AsTaggedItem(index: 'rate_exchange_source_cbr')]
class FetchExchangeRatesFromCbrCommand implements FetchExchangeRatesCommandInterface
{
    private DateTimeInterface $date;

    public function getRoutingKey(): string
    {
        return 'cbr';
    }

    public function getSource(): string
    {
        return 'cbr.ru';
    }

    public function getTimezone(): DateTimeZone
    {
        return new DateTimeZone('Europe/Moscow');
    }

    public function setDate(DateTimeInterface $date): void
    {
        $this->date = $date;
    }

    public function getDate(): DateTimeInterface
    {
        return $this->date;
    }
}