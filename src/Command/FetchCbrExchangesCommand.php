<?php

namespace App\Command;

use DateTime;
use DateTimeZone;
use DateInterval;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Message\Command\FetchExchangesFromCbrCommand;

#[AsCommand(
    name: 'app:fetch-cbr-exchanges',
    description: 'This command fetch exchange data from cbr.ru',
)]
class FetchCbrExchangesCommand extends Command
{
    private const OPTION_DAYS = 'days';

    public function __construct(
        private readonly MessageBusInterface $messageBus
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                self::OPTION_DAYS,
                null,
                InputOption::VALUE_REQUIRED,
                'Defines the days amount for fetching since today. The default is 180.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $days = 180;
        if ( $input->getOption(self::OPTION_DAYS) )
        {
            $days = $input->getOption(self::OPTION_DAYS);

            if ( !is_numeric($days) )
            {
                throw new InvalidArgumentException('Invalid ' . self::OPTION_DAYS . ' option.');
            }

            $days = (int) $days;

            $io->note(sprintf('The exchanges from cbr.ru are going to be fetched for %s days.', $days));
        }

        try {
            $dateTime = new DateTime(
                timezone: new DateTimeZone('Europe/Moscow'),
            );

            for ( $day = 1; $day <= $days; $day++ )
            {
                $dateTime->sub(DateInterval::createFromDateString('1 day'));

                $this->messageBus->dispatch(
                    new FetchExchangesFromCbrCommand($dateTime),
                    [ new AmqpStamp('cbr') ]
                );
            }

            $io->success(sprintf('The commands for fetching exchanges from cbr.ru have been dispatched for %s days.', $days));

            return Command::SUCCESS;
        }
        catch ( Exception $exception )
        {
            $io->error(sprintf('Command execution error: %s', $exception->getMessage()));

            return Command::FAILURE;
        }
    }
}
