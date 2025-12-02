<?php

namespace Davidannebicque\HtmlToSpreadsheetBundle\Tests\Command;

use Davidannebicque\HtmlToSpreadsheetBundle\Command\XlsLintCommand;
use Davidannebicque\HtmlToSpreadsheetBundle\Html\AttributeValidator;
use Davidannebicque\HtmlToSpreadsheetBundle\Html\HtmlTableInterpreter;
use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Styler\SheetStyler;
use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Styler\StyleRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class XlsLintCommandTest extends TestCase
{
    private Environment $twig;
    private HtmlTableInterpreter $interpreter;
    private XlsLintCommand $command;

    protected function setUp(): void
    {
        $loader = new ArrayLoader([
            'valid.html.twig' => '<table data-xls-sheet="Test"><tr><td>{{ value }}</td></tr></table>',
            'invalid.html.twig' => '<table><tr><td>No sheet attribute</td></tr></table>',
            'multiple.html.twig' => '<table data-xls-sheet="Sheet1"><tr><td>A</td></tr></table>
                                     <table data-xls-sheet="Sheet2"><tr><td>B</td></tr></table>',
        ]);

        $this->twig = new Environment($loader);

        $registry = new StyleRegistry([]);
        $styler = new SheetStyler($registry);
        $validator = new AttributeValidator(strict: false);
        $this->interpreter = new HtmlTableInterpreter($styler, $validator);

        $this->command = new XlsLintCommand($this->twig, $this->interpreter);
    }

    public function testCommandName(): void
    {
        $this->assertEquals('xls:lint', $this->command->getName());
    }

    public function testCommandDescription(): void
    {
        $description = $this->command->getDescription();
        $this->assertStringContainsString('Vérifie', $description);
        $this->assertStringContainsString('templates', $description);
    }

    public function testExecuteWithValidTemplate(): void
    {
        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute([
            'templates' => ['valid.html.twig'],
            '--context' => '{"value": "test"}',
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('valid.html.twig', $output);
        $this->assertStringContainsString('OK', $output);
    }

    public function testExecuteWithInvalidTemplate(): void
    {
        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute([
            'templates' => ['invalid.html.twig'],
        ]);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('invalid.html.twig', $output);
        $this->assertStringContainsString('ERREUR', $output);
    }

    public function testExecuteWithMultipleTemplates(): void
    {
        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute([
            'templates' => ['valid.html.twig', 'multiple.html.twig'],
            '--context' => '{"value": "test"}',
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('valid.html.twig', $output);
        $this->assertStringContainsString('multiple.html.twig', $output);
    }

    public function testExecuteWithMixedValidityTemplates(): void
    {
        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute([
            'templates' => ['valid.html.twig', 'invalid.html.twig'],
            '--context' => '{"value": "test"}',
        ]);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('valid.html.twig', $output);
        $this->assertStringContainsString('invalid.html.twig', $output);
    }

    public function testNonStrictModeOption(): void
    {
        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute([
            'templates' => ['valid.html.twig'],
            '--non-strict' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    public function testContextOptionWithJsonData(): void
    {
        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute([
            'templates' => ['valid.html.twig'],
            '--context' => '{"value": "custom value"}',
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    public function testVerboseOutputShowsStackTrace(): void
    {
        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(
            ['templates' => ['invalid.html.twig']],
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]
        );

        $this->assertEquals(Command::FAILURE, $exitCode);
        // In verbose mode, stack trace should be shown
        $output = $tester->getDisplay();
        $this->assertStringContainsString('ERREUR', $output);
    }

    public function testEmptyContextDefaultsToEmptyArray(): void
    {
        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute([
            'templates' => ['valid.html.twig'],
            // No context option provided
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    public function testInvalidJsonContextDefaultsToEmptyArray(): void
    {
        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute([
            'templates' => ['valid.html.twig'],
            '--context' => 'invalid json',
        ]);

        // Should still succeed as it defaults to empty array
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }
}
