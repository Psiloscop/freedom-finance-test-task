<?php

namespace App\Controller;

use App\Interface\ExchangeRateServiceInterface;
use Exception;
use DateTime;
use DateInterval;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use App\RequestDto\ExchangeRequestDto;
use App\Repository\ExchangeRepository;

class ApiController extends AbstractController
{
    #[Route('/exchange', name: 'app_exchange', methods: ['POST'])]
    public function exchange(
        #[MapRequestPayload] ExchangeRequestDto $exchangeRequest,
        #[TaggedLocator(ExchangeRateServiceInterface::class)] ContainerInterface $exchangeRateServices,
        ExchangeRepository $exchangeRepository
    ): JsonResponse
    {
        $currentDay = new DateTime($exchangeRequest->date);
        $previousDay = clone $currentDay;
        $previousDay->sub(DateInterval::createFromDateString('1 day'));

        $exchangeRateForCurrentDay = $exchangeRepository->findExchangeRateInCache(
            $currentDay,
            $exchangeRequest->currency,
            $exchangeRequest->baseCurrency
        );
        $exchangeRateForPreviousDay = $exchangeRepository->findExchangeRateInCache(
            $previousDay,
            $exchangeRequest->currency,
            $exchangeRequest->baseCurrency
        );

        $serviceTag = "rate_exchange_service_$exchangeRequest->source";

        if ( !$exchangeRateServices->has($serviceTag) )
        {
            return new JsonResponse([
                'errors' => [
                    sprintf('Source "%s" not supported.', $exchangeRequest->source)
                ],
            ], 400);
        }

        /**
         * @var ExchangeRateServiceInterface $rateExchangeService
         */
        $rateExchangeService = $exchangeRateServices->get($serviceTag);

        if ( $exchangeRateForCurrentDay === null )
        {
            try
            {
                $currencyMap = $rateExchangeService->fetchData($currentDay, $exchangeRequest->baseCurrency);

                $exchangeRepository->saveExchangeRateBatchToCache(
                    $exchangeRequest->source,
                    $currentDay,
                    $currencyMap,
                    $exchangeRequest->baseCurrency,
                );

                $exchangeRateForCurrentDay = $exchangeRepository->findExchangeRateInCache(
                    $currentDay,
                    $exchangeRequest->currency,
                    $exchangeRequest->baseCurrency
                );
            }
            catch ( Exception $e )
            {
                return new JsonResponse([
                    'errors' => [ $e->getMessage() ],
                ], 400);
            }
        }

        if ( $exchangeRateForPreviousDay === null )
        {
            try
            {
                $currencyMap = $rateExchangeService->fetchData($previousDay, $exchangeRequest->baseCurrency);

                $exchangeRepository->saveExchangeRateBatchToCache(
                    $exchangeRequest->source,
                    $previousDay,
                    $currencyMap,
                    $exchangeRequest->baseCurrency,
                );

                $exchangeRateForPreviousDay = $exchangeRepository->findExchangeRateInCache(
                    $previousDay,
                    $exchangeRequest->currency,
                    $exchangeRequest->baseCurrency
                );
            }
            catch ( Exception $e )
            {
                return new JsonResponse([
                    'errors' => [ $e->getMessage() ],
                ], 400);
            }
        }

        $prevDayDiff = bcsub(
            $exchangeRateForCurrentDay->getRate(),
            $exchangeRateForPreviousDay->getRate(),
            8,
        );
        $prevDayDiff = rtrim($prevDayDiff, '0');
        if ( $prevDayDiff[strlen($prevDayDiff) - 1] == '.' )
        {
            $prevDayDiff .= '0';
        }

        return new JsonResponse([
            'base' => $exchangeRateForCurrentDay->getBaseCurrency(),
            'currency' => $exchangeRateForCurrentDay->getCurrency(),
            'rate' => $exchangeRateForCurrentDay->getRate(),
            'prev_day_diff' => $prevDayDiff,
        ]);
    }
}