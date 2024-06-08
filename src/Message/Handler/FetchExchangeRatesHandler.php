<?php

namespace App\Message\Handler;

use Exception;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Repository\ExchangeRepository;
use App\Interface\ExchangeRateServiceInterface;
use App\Message\Command\FetchExchangeRatesFromCbrCommand;

#[AsMessageHandler(
    fromTransport: 'async_fetch_exchange_rate_from_cbr'
)]
class FetchExchangeRatesHandler
{
    public function __construct(
        #[TaggedLocator(ExchangeRateServiceInterface::class)] private readonly ContainerInterface $exchangeRateServices,
        private readonly ExchangeRepository $exchangeRepository
    )
    {}

    /**
     * @throws
     */
    public function __invoke(FetchExchangeRatesFromCbrCommand $command): void
    {
        $serviceTag = "rate_exchange_service_{$command->getSource()}";

        if ( !$this->exchangeRateServices->has($serviceTag) )
        {
            throw new Exception(sprintf('Source "%s" not supported.', $command->getSource()));
        }

        /**
         * @var ExchangeRateServiceInterface $rateExchangeService
         */
        $rateExchangeService = $this->exchangeRateServices->get($serviceTag);

        $currencyMap = $rateExchangeService->fetchData($command->getDate(), $command->getBaseCurrency());

        $this->exchangeRepository->saveExchangeRateBatchToCache(
            $command->getSource(),
            $command->getDate(),
            $currencyMap,
            $command->getBaseCurrency(),
        );
    }
}