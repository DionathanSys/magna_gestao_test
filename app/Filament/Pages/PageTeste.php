<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class PageTeste extends Page
{
    protected string $view = 'filament.pages.page-teste';

    protected static ?string $title = 'Custom Page Title';

    protected static ?string $navigationLabel = 'Custom Navigation Label';

    protected static ?string $slug = 'custom-url-slug';

    protected ?string $heading = 'Custom Page Heading';

    protected ?string $subheading = 'Custom Page Subheading';
}

