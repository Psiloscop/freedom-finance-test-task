<?php

namespace App\Controller;

use Exception;
use DateTime;
use DateInterval;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use App\Service\SbrClientService;
use App\RequestDto\ExchangeRequestDto;
use App\Repository\ExchangeRepository;

class ApiController extends AbstractController
{
    #[Route('/exchange', name: 'app_exchange')]
    public function exchange(
        #[MapRequestPayload] ExchangeRequestDto $exchangeRequest,
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

        if ( $exchangeRateForCurrentDay === null )
        {
            try
            {
                $currencyMap = SbrClientService::getDailyCurrencyMap(['date_req' => date('d/m/Y', $currentDay->getTimestamp())]);

                $exchangeRepository->saveExchangeRateBatchToCache(
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
                $currencyMap = SbrClientService::getDailyCurrencyMap(['date_req' => date('d/m/Y', $previousDay->getTimestamp())]);

                $exchangeRepository->saveExchangeRateBatchToCache(
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