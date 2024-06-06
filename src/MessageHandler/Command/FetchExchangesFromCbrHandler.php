<?php

namespace App\MessageHandler\Command;

use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Message\Command\FetchExchangesFromCbrCommand;
use App\Repository\ExchangeRepository;
use App\Service\SbrClientService;

#[AsMessageHandler]
class FetchExchangesFromCbrHandler
{
    public function __construct(
        private readonly ExchangeRepository $exchangeRepository
    )
    {}

    /**
     * @throws Exception
     */
    public function __invoke(FetchExchangesFromCbrCommand $command): void
    {
        $currencyMap = SbrClientService::getDailyCurrencyMap([
            'date_req' => date('d/m/Y', $command->getDateToFetch()->getTimestamp())
        ]);

        $this->exchangeRepository->saveExchangeRateBatchToCache(
            $command->getDateToFetch(),
            $currencyMap,
            'RUR',
        );
    }
}