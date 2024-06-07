<?php

namespace App\RequestDto;

use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

class ExchangeRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Date]
        public readonly string $date,

        #[Assert\NotBlank]
        #[Assert\Choice(
            options: ['AUD','AZN','GBP','AMD','BYN','BGN','BRL','HUF','VND','HKD',
                    'GEL','DKK', 'AED','USD','EUR','EGP','INR','IDR','KZT','CAD',
                    'QAR','KGS','CNY','MDL','NZD','NOK','PLN','RON','XDR','SGD',
                    'TJS','THB','TRY','TMT','UZS','UAH','CZK','SEK','CHF','RSD',
                    'ZAR','KRW','JPY'],
            message: "Specified currency is not supported."
        )]
        public readonly string $currency,

        #[Assert\Choice(
            options: ['RUR'],
            message: "Unfortunately, only RUR currency is supported."
        )]
        public readonly ?string $baseCurrency = 'RUR',

        #[Assert\Choice(
            options: ['cbr.ru'],
            message: "Unfortunately, only cbr.ru source is supported."
        )]
        public readonly ?string $source = 'cbr.ru',
    )
    {}
}