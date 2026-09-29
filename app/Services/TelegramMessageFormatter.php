<?php

namespace App\Services;

use DOMDocument;
use DOMNode;

class TelegramMessageFormatter
{
    public function format(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadHTML(
            '<?xml encoding="UTF-8" ?><div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $output = '';
        $root = $document->documentElement;

        if ($root) {
            foreach ($root->childNodes as $node) {
                $output .= $this->renderNode($node);
            }
        }

        return $this->normalizeWhitespace($output);
    }

    private function renderNode(DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return htmlspecialchars($node->nodeValue ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return '';
        }

        $tag = strtolower($node->nodeName);
        $content = $this->renderChildren($node);

        return match ($tag) {
            'br' => "\n",
            'strong', 'b' => $content === '' ? '' : "<b>{$content}</b>",
            'em', 'i' => $content === '' ? '' : "<i>{$content}</i>",
            'u' => $content === '' ? '' : "<u>{$content}</u>",
            's', 'strike', 'del' => $content === '' ? '' : "<s>{$content}</s>",
            'code' => $content === '' ? '' : "<code>{$content}</code>",
            'pre' => $content === '' ? '' : "<pre>{$content}</pre>",
            'blockquote' => $content === '' ? '' : "<blockquote>{$content}</blockquote>",
            'a' => $this->renderLink($node, $content),
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6' => $content === '' ? '' : "<b>{$content}</b>\n\n",
            'li' => $content === '' ? '' : "- {$content}\n",
            'p', 'div', 'section' => $content === '' ? '' : "{$content}\n\n",
            'ul', 'ol' => $content."\n",
            default => $content,
        };
    }

    private function renderChildren(DOMNode $node): string
    {
        $content = '';

        foreach ($node->childNodes as $child) {
            $content .= $this->renderNode($child);
        }

        return $content;
    }

    private function renderLink(DOMNode $node, string $content): string
    {
        $href = $node->attributes?->getNamedItem('href')?->nodeValue;

        if (! is_string($href) || ! in_array(parse_url($href, PHP_URL_SCHEME), ['http', 'https', 'tg'], true)) {
            return $content;
        }

        return '<a href="'.htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'">'.$content.'</a>';
    }

    private function normalizeWhitespace(string $content): string
    {
        $content = preg_replace("/\xC2\xA0/u", ' ', $content) ?? $content;
        $content = preg_replace("/\n[ \t]+/", "\n", $content) ?? $content;
        $content = preg_replace("/\n{3,}/", "\n\n", $content) ?? $content;

        return trim($content);
    }
}
