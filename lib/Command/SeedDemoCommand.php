<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Command;

use OCA\FlzUrlaub\Service\VacationDemoPackService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** Zweck: Stellt denselben sicheren Urlaubs-Demo-Pack zusätzlich für automatisierte Staging-Setups bereit. */
final class SeedDemoCommand extends Command {
    public function __construct(private VacationDemoPackService $demoPack) { parent::__construct(); }
    protected function configure(): void { $this->setName('flzurlaub:demo:seed')->setDescription('Erzeugt synthetische Urlaubsdemos für alle Filzmann-Fachgruppen.'); }
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $result = $this->demoPack->install();
        $output->writeln("<info>{$result['coveredGroups']} Fachgruppen abgedeckt; {$result['createdVacations']} Demo-Urlaube erzeugt, {$result['skippedVacations']} bereits vorhanden.</info>");
        return self::SUCCESS;
    }
}
