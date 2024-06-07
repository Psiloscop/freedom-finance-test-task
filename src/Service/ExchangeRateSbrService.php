<?php

/**
 * CBR.RU API for PHP.
 *
 * @author   Alexander Pushkarev <axp-dev@yandex.com>
 * @link     https://github.com/axp-dev/cbrru-api
 * @license  MIT License
 * @version  1.0.0
 */

namespace App\Service;

use Exception;
use JsonException;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use App\Interface\ExchangeRateServiceInterface;

#[AsTaggedItem(index: 'rate_exchange_service_cbr.ru')]
class ExchangeRateSbrService implements ExchangeRateServiceInterface
{
    /**
     * @param DateTimeInterface $date
     * @param string $baseCurrency
     * @return array
     * @throws Exception
     */
    public function fetchData(DateTimeInterface $date, string $baseCurrency): array
    {
        $xml = simplexml_load_file("https://www.cbr.ru/scripts/XML_daily.asp?date_req={$date->format('d/m/Y')}");

        try
        {
            $json = json_encode($xml, JSON_THROW_ON_ERROR);
            $data = json_decode($json, true);
        }
        catch ( JsonException )
        {
            throw new Exception("Parse error");
        }

        if ( count($data) == 1 && is_string($data[0]) )
        {
            throw new Exception("Error response from CBR: " . trim($data[0]));
        }

        $currencyMap = [];
        foreach ( $data['Valute'] as $rateData )
        {
            $currencyMap[$rateData['CharCode']] = (float) str_replace(',', '.', $rateData['VunitRate']);
        }

        return $currencyMap;
    }
}