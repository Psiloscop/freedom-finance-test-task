<?php

namespace App\Command;

use DateTime;
use DateInterval;
use Exception;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Interface\FetchExchangeRatesCommandInterface;

#[AsCommand(
    name: 'app:fetch-exchange-rates',
    description: 'This command fetch exchange data from defined sources.',
)]
class FetchCbrExchangesCommand extends Command
{
    private const OPTION_SOURCE = 'source';
    private const OPTION_DAYS = 'days';

    public function __construct(
        #[TaggedLocator(FetchExchangeRatesCommandInterface::class)]
        private readonly ContainerInterface  $fetchExchangeRatesCommands,
        private readonly MessageBusInterface $messageBus
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                self::OPTION_SOURCE,
                null,
                InputOption::VALUE_REQUIRED,
                'Defines the exchange rate source.',
                'cbr'
            )
            ->addOption(
                self::OPTION_DAYS,
                null,
                InputOption::VALUE_REQUIRED,
                'Defines the days amount for fetching since today. The default is 180.',
                180
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $source = $input->getOption(self::OPTION_SOURCE);
        $days = $input->getOption(self::OPTION_DAYS);

        if ( !is_numeric($days) )
        {
            $io->error(sprintf('Invalid "%s" option.', self::OPTION_DAYS));

            return Command::FAILURE;
        }

        $days = (int) $days;

        $io->note(sprintf('The exchange rates are going to be fetched for %s days.', $days));

        try {
            $sourceTag = "rate_exchange_command_$source";

            if ( !$this->fetchExchangeRatesCommands->has($sourceTag) )
            {
                $io->error(sprintf('Source "%s" not supported.', $source));

                return Command::FAILURE;
            }

            /**
             * @var FetchExchangeRatesCommandInterface $command
             */
            $command = $this->fetchExchangeRatesCommands->get($sourceTag);

            $dateTime = new DateTime(
                timezone: $command->getTimezone(),
            );

            for ( $day = 1; $day <= $days; $day++ )
            {
                $dateTime->sub(DateInterval::createFromDateString('1 day'));

                $command->setDate($dateTime);

                $this->messageBus->dispatch(
                    $command, [ new AmqpStamp(routingKey: $command->getRoutingKey()) ]
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
