<?php
// src/Command/XlsLintCommand.php
namespace Davidannebicque\HtmlToSpreadsheetBundle\Command;

use Davidannebicque\HtmlToSpreadsheetBundle\Html\HtmlTableInterpreter;
use Davidannebicque\HtmlToSpreadsheetBundle\Html\HtmlToXlsxOptions;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;

#[AsCommand(name: 'xls:lint', description: 'Vérifie les templates HTML annotés (data-xls-*)')]
final class XlsLintCommand extends Command
{
    public function __construct(
        private readonly Environment $twig,
        private readonly HtmlTableInterpreter $interpreter,
    ) { parent::__construct(); }

    protected function configure(): void
    {
        $this
            ->addArgument('templates', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Chemins de templates Twig (ex: export/etudiants.html.twig ...)')
            ->addOption('context', null, InputOption::VALUE_OPTIONAL, 'JSON pour le contexte Twig', '{}')
            ->addOption('non-strict', null, InputOption::VALUE_NONE, 'Désactive le mode strict de validation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tpls = (array) $input->getArgument('templates');
        $ctxJson = (string) $input->getOption('context');
        $strict = !$input->getOption('non-strict');

        $ctx = json_decode($ctxJson, true) ?? [];
        $errors = 0;

        foreach ($tpls as $tpl) {
            $output->writeln("<info>• $tpl</info>");
            try {
                $html = $this->twig->render($tpl, $ctx);
                // on ne garde pas le workbook, on teste juste la conversion
                $this->interpreter->fromHtml($html, new HtmlToXlsxOptions(strict: $strict));
                $output->writeln("<fg=green>  OK</>");
            } catch (\Throwable $e) {
                $errors++;
                $output->writeln("<fg=red>  ERREUR:</> ".$e->getMessage());
                if ($output->isVerbose()) {
                    $output->writeln("<comment>".$e->getTraceAsString()."</comment>");
                }
            }
        }

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
