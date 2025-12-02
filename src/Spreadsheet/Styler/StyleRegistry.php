<?php

namespace Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Styler;

final class StyleRegistry
{
    /** @var array<string,array> */
    private array $styles = [];

    public function __construct(array $defaultStyles = [])
    {
        $this->styles =  $defaultStyles;
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
