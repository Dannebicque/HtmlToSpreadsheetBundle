<?php

namespace Davidannebicque\HtmlToSpreadsheetBundle\Html;

final class HtmlToXlsxOptions
{
    public function __construct(
        public bool $strict = true,
        public ?string $numberLocale = null, // fallback global
    ) {}
}
