<?php

namespace App\Services;

use DOMElement;
use Masterminds\HTML5;

class DocumentHtmlService
{
    /** Conservative exclusion for these repairs; does not change document classification. */
    public function preservesLampiran(\App\Models\RenjaDocument $document): bool
    {
        return $document->isLampiranPerbub()
            || preg_match('/lampiran|perbub|perbup|kepbup/i', $document->jenis_dokumen ?? '')
            || str_contains(strtolower($document->source_type ?? ''), 'lampiran');
    }

    /** Read-only rendering only: never use this value to save or provision sections. */
    public function forDocumentDisplay(\App\Models\RenjaDocument $document, ?string $html): string
    {
        return $this->preservesLampiran($document) ? ($html ?? '') : $this->forDisplay($html);
    }

    public function assertEmbeddedImages(string $html): void
    {
        $fragment = (new HTML5(['disable_html_ns' => true]))->loadHTMLFragment($html);
        $xpath = new \DOMXPath($fragment->ownerDocument);
        foreach ($xpath->query('.//img', $fragment) as $image) {
            if (!preg_match('/^data:image\/(png|jpeg|gif|webp);base64,[a-z0-9+\/=]+$/i', $image->getAttribute('src'))) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'export' => 'Gambar eksternal harus disematkan ke dokumen sebelum ekspor Word.',
                ]);
            }
        }
    }

    /** PhpWord parses XML, so HTML void elements must be serialized as XML. */
    public function forWord(string $html): string
    {
        $this->assertEmbeddedImages($html);
        $fragment = (new HTML5(['disable_html_ns' => true]))->loadHTMLFragment($this->forDisplay($html));
        $xml = '';
        foreach ($fragment->childNodes as $node) {
            $xml .= $fragment->ownerDocument->saveXML($node);
        }
        return $xml;
    }

    /** Remove executable markup for display without rewriting stored user content. */
    public function forDisplay(?string $html): string
    {
        if (!$html || !str_contains($html, '<')) {
            return $html ?? '';
        }

        $parser = new HTML5(['disable_html_ns' => true]);
        $fragment = $parser->loadHTMLFragment($html);
        $changed = false;
        $allowed = explode(' ', 'p div span br hr h1 h2 h3 h4 h5 h6 b strong i em u s strike del sub sup blockquote pre code ul ol li dl dt dd table caption colgroup col thead tbody tfoot tr th td a img figure figcaption font center');
        $walk = function ($parent) use (&$walk, &$changed, $allowed) {
            foreach (iterator_to_array($parent->childNodes) as $node) {
                if (!$node instanceof DOMElement) {
                    continue;
                }
                $tag = strtolower($node->tagName);
                if (!in_array($tag, $allowed, true)) {
                    $changed = true;
                    if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template', 'base', 'link', 'meta'], true)) {
                        $parent->removeChild($node);
                        continue;
                    }
                    $walk($node);
                    while ($node->firstChild) {
                        $parent->insertBefore($node->firstChild, $node);
                    }
                    $parent->removeChild($node);
                    continue;
                }
                foreach (iterator_to_array($node->attributes) as $attribute) {
                    $name = strtolower($attribute->name);
                    $value = preg_replace('/[\x00-\x20\x7f]+/', '', $attribute->value);
                    $safe = in_array($name, ['style', 'class', 'id', 'title', 'alt', 'width', 'height', 'colspan', 'rowspan', 'border', 'cellpadding', 'cellspacing', 'align', 'valign', 'start', 'type', 'face', 'size', 'color', 'href', 'src'], true)
                        || str_starts_with($name, 'data-') || str_starts_with($name, 'aria-');
                    if ($name === 'style') {
                        $safe = !preg_match('/url\s*\(|expression\s*\(|@import|behavior|binding|[\\\\<>]/i', $attribute->value);
                    }
                    if (in_array($name, ['src', 'href'], true)) {
                        $safe = !preg_match('/^[a-z][a-z0-9+.-]*:/i', $value)
                            || preg_match('/^(https?:|mailto:)/i', $value)
                            || ($name === 'src' && preg_match('/^data:image\/(png|jpeg|gif|webp);base64,[a-z0-9+\/=]+$/i', $value));
                    }
                    if (!$safe) {
                        $node->removeAttributeNode($attribute);
                        $changed = true;
                    }
                }
                $walk($node);
            }
        };
        $walk($fragment);

        return $changed ? $parser->saveHTML($fragment) : $html;
    }
}
