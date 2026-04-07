<?php

declare(strict_types=1);

namespace TastyFonts\Admin\Renderer;

defined('ABSPATH') || exit;

final class DiagnosticsSectionRenderer extends AbstractSectionRenderer
{
    public function __construct(
        \TastyFonts\Support\Storage $storage,
        private readonly ToolsSectionRenderer $toolsRenderer
    ) {
        parent::__construct($storage);
    }

    public function render(array $view): void
    {
        $view['toolsRenderer'] = $this->toolsRenderer;
        $this->renderTemplate('diagnostics-section.php', $view);
    }
}
