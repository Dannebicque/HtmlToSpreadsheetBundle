<?php

namespace Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Styler;

final class StyleRegistry
{
    /** @var array<string,array> */
    private array $styles = [];

    public function __construct(array $defaultStyles = [])
    {
        // Presets internes, garantis
        $builtins = [
            'th' => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FF333333']],
                'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFEEEEEE']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
                'borders' => [
                    'bottom' => ['borderStyle' => 'thin', 'color' => ['argb' => 'FFCCCCCC']],
                ],
            ],
            'money' => [
                'alignment' => ['horizontal' => 'right'],
                'numberFormat' => ['formatCode' => '# ##0,00 [$€-fr-FR]'],
            ],
            'date' => [
                'alignment' => ['horizontal' => 'center'],
                'numberFormat' => ['formatCode' => 'dd/mm/yyyy'],
            ],
            'percent2' => [
                'alignment' => ['horizontal' => 'right'],
                'numberFormat' => ['formatCode' => '0,00%'],
            ],
        ];

        // Ce que tu passes en config écrase ou complète les builtins
        $this->styles = array_merge($builtins, $defaultStyles);
    }

    public function has(string $name): bool
    {
        return isset($this->styles[$name]);
    }

    /** @return array style array compatible PhpSpreadsheet */
    public function get(string $name): array
    {
        if (!$this->has($name)) {
            $known = implode(', ', array_keys($this->styles));
            throw new \InvalidArgumentException("Style nommé inconnu: '$name'. Styles connus: [{$known}]");
        }
        return $this->styles[$name];
    }

    public function all(): array
    {
        return $this->styles;
    }

    public function register(string $name, array $style): void
    {
        $this->styles[$name] = $style;
    }
}
