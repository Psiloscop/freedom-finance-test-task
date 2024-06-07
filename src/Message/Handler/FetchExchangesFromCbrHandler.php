<?php

namespace App\Message\Handler;

use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Message\Command\FetchExchangeRatesFromCbrCommand;
use App\Repository\ExchangeRepository;
use App\Service\SbrClientService;

#[AsMessageHandler(
    fromTransport: 'async_fetch_exchange_rate_from_cbr'
)]
class FetchExchangesFromCbrHandler
{
    public function __construct(
        private readonly ExchangeRepository $exchangeRepository
    )
    {}

    /**
     * @throws Exception
     */
    public function __invoke(FetchExchangeRatesFromCbrCommand $command): void
    {
//        throw new Exception("Error in " . __CLASS__);

        $currencyMap = SbrClientService::getDailyCurrencyMap([
            'date_req' => date('d/m/Y', $command->getDate()->getTimestamp())
        ]);

        $this->exchangeRepository->saveExchangeRateBatchToCache(
            $command->getSource(),
            $command->getDate(),
            $currencyMap,
            'RUR',
        );
    }
}