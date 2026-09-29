<?php

namespace Tests\Unit;

use App\Services\TelegramMessageFormatter;
use Tests\TestCase;

class TelegramMessageFormatterTest extends TestCase
{
    public function test_it_converts_rich_editor_html_to_telegram_html(): void
    {
        $message = app(TelegramMessageFormatter::class)->format(
            '<h2>Alerta</h2><p><strong>Veículo:</strong> ABC-123</p><ul><li>Primeiro item</li><li>Segundo item</li></ul><p><a href="https://example.com">Abrir registro</a></p>',
        );

        $this->assertSame(
            "<b>Alerta</b>\n\n<b>Veículo:</b> ABC-123\n\n- Primeiro item\n- Segundo item\n\n<a href=\"https://example.com\">Abrir registro</a>",
            $message,
        );
    }

    public function test_it_escapes_text_and_removes_unsupported_links(): void
    {
        $message = app(TelegramMessageFormatter::class)->format(
            '<p>5 &lt; 10 &amp; 10 &gt; 5</p><p><a href="javascript:alert(1)">Link</a></p>',
        );

        $this->assertSame("5 &lt; 10 &amp; 10 &gt; 5\n\nLink", $message);
    }
}
