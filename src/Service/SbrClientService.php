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

class SbrClientService
{
    const DAILY = 'http://www.cbr.ru/scripts/XML_daily.asp';
    const CURRENCY_CODE = 'http://www.cbr.ru/scripts/XML_valFull.asp';
    const DYNAMIC = 'http://www.cbr.ru/scripts/XML_dynamic.asp';
    const OSTAT = 'http://www.cbr.ru/scripts/XML_ostat.asp';
    const METAL = 'http://www.cbr.ru/scripts/xml_metall.asp';
    const MKR = 'http://www.cbr.ru/scripts/xml_mkr.asp';
    const DEPO = 'http://www.cbr.ru/scripts/xml_depo.asp';
    const NEWS = 'http://www.cbr.ru/scripts/XML_News.asp';
    const BIC = 'http://www.cbr.ru/scripts/XML_bic.asp';
    const SWAP = 'http://www.cbr.ru/scripts/xml_swap.asp';
    const COINBASE = 'http://www.cbr.ru/scripts/XMLCoinsBase.asp';

    /**
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public static function getDailyCurrencyMap(array $params): array
    {
        $result = self::getDaily($params);

        $currencyMap = [];
        foreach ( $result['Valute'] as $rateData )
        {
            $currencyMap[$rateData['CharCode']] = (float) str_replace(',', '.', $rateData['VunitRate']);
        }

        return $currencyMap;
    }

    /**
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public static function getDaily(array $params)
    {
        return self::query(self::DAILY, $params);
    }

    /**
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public static function getCurrencyCode(array $params): mixed
    {
        return self::query(self::CURRENCY_CODE, $params);
    }

    /**
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public static function getDynamic(array $params): mixed
    {
        return self::query(self::DYNAMIC, $params);
    }

    /**
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public static function getOStat(array $params): mixed
    {
        return self::query(self::OSTAT, $params);
    }

    /**
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public static function getMetal(array $params): mixed
    {
        return self::query(self::METAL, $params);
    }

    /**
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public static function getMKR(array $params): mixed
    {
        return self::query(self::MKR, $params);
    }

    /**
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public static function getDEPO(array $params): mixed
    {
        return self::query(self::DEPO, $params);
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public static function getNews(): mixed
    {
        return self::query(self::NEWS, []);
    }

    /**
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public static function getBIC(array $params): mixed
    {
        return self::query(self::BIC, $params);
    }

    /**
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public static function getSwap(array $params): mixed
    {
        return self::query(self::SWAP, $params);
    }

    /**
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public static function getCoinsBase(array $params): mixed
    {
        return self::query(self::COINBASE, $params);
    }

    /**
     * @throw
     * @param string $url
     * @param array $params
     * @return array|mixed
     * @throws Exception
     */
    protected static function query(string $url, array $params = []): mixed
    {
        $xml = simplexml_load_file($url . ( count($params) > 0 ? '?' : '' ) . http_build_query($params));
        $json = json_encode($xml, JSON_THROW_ON_ERROR);
        $data = json_decode($json, true);

        if ( count($data) == 1 && is_string($data[0]) )
        {
            throw new Exception("Error response from CBR: " . trim($data[0]));
        }

        return $data;
    }
}