<?php

namespace App\EventSubscriber;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class PayloadErrorEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ExceptionEvent::class => 'onExceptionEvent'
        ];
    }

    public function onExceptionEvent(ExceptionEvent $event): void
    {
        $isHttpEvent = $event->getThrowable() instanceof HttpExceptionInterface;
        $isValidationEvent = $event->getThrowable()->getPrevious() instanceof ValidationFailedException;

        if ( !$isHttpEvent || !$isValidationEvent )
        {
            return;
        }

        /**
         * @var ValidationFailedException $validationException
         */
        $validationException = $event->getThrowable()->getPrevious();
        $errorMessages = [];
        foreach ($validationException->getViolations() as $violation)
        {
            $errorMessages[$violation->getPropertyPath()] = $violation->getMessage();
        }

        $event->setResponse(
            new JsonResponse(['errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}