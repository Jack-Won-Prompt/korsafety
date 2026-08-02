<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * 리치 에디터(Quill)로 작성한 HTML을 허용 목록 기준으로 정제한다.
 * 상품 상세 설명은 판매점 계정도 작성하고 쇼핑몰에서 원문 그대로 출력되므로,
 * 스크립트·이벤트 핸들러·javascript: URL이 저장되지 않도록 서버에서 걸러낸다.
 */
class RichTextSanitizer
{
    /** 허용 태그 → 해당 태그에서 추가로 허용할 속성 (공통 허용 속성은 아래 COMMON_ATTRS) */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'span' => [], 'div' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'sub' => [], 'sup' => [],
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'ul' => [], 'ol' => [], 'li' => ['data-list'],
        'blockquote' => [], 'pre' => [], 'code' => [], 'hr' => [],
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height'],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'td' => ['colspan', 'rowspan'], 'th' => ['colspan', 'rowspan'],
    ];

    private const COMMON_ATTRS = ['class', 'style'];

    /** style 속성에서 남길 CSS 속성 */
    private const ALLOWED_STYLES = ['color', 'background-color', 'text-align', 'width', 'height', 'font-size', 'font-weight'];

    public static function clean(?string $html): ?string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return null;
        }
        // 태그가 전혀 없으면 평문 — 그대로 둔다
        if (! preg_match('/<\w+[\s\S]*?>/', $html)) {
            return $html;
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $ok = $doc->loadHTML(
            '<?xml encoding="utf-8" ?><div id="__root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if (! $ok) {
            return strip_tags($html);   // 파싱 실패 시엔 태그를 모두 제거하는 쪽이 안전
        }

        $root = $doc->getElementById('__root');
        if (! $root) {
            return strip_tags($html);
        }

        self::cleanNode($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out) === '' ? null : trim($out);
    }

    private static function cleanNode(DOMNode $node): void
    {
        // 자식 목록이 순회 중에 바뀌므로 복사본으로 돈다
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (! array_key_exists($tag, self::ALLOWED)) {
                    // script·style·iframe 등 위험 태그는 내용까지 통째로 제거,
                    // 그 외 모르는 태그는 자식만 살려서 끌어올린다
                    if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'link', 'meta'], true)) {
                        $node->removeChild($child);
                    } else {
                        self::cleanNode($child);
                        while ($child->firstChild) {
                            $node->insertBefore($child->firstChild, $child);
                        }
                        $node->removeChild($child);
                    }
                    continue;
                }

                self::cleanAttributes($child, $tag);

                // src가 걸러진 이미지는 빈 껍데기만 남으므로 제거
                if ($tag === 'img' && ! $child->hasAttribute('src')) {
                    $node->removeChild($child);
                    continue;
                }

                self::cleanNode($child);
            } elseif ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);
            }
        }
    }

    private static function cleanAttributes(DOMElement $el, string $tag): void
    {
        $allowed = array_merge(self::ALLOWED[$tag], self::COMMON_ATTRS);

        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->nodeName);
            $value = trim($attr->nodeValue);

            if (! in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->nodeName);   // on* 이벤트 핸들러 포함 전부 제거
                continue;
            }

            if ($name === 'href' || $name === 'src') {
                if (! self::safeUrl($value)) {
                    $el->removeAttribute($attr->nodeName);
                }
                continue;
            }
            if ($name === 'style') {
                $style = self::cleanStyle($value);
                $style === '' ? $el->removeAttribute('style') : $el->setAttribute('style', $style);
            }
        }

        // 외부 링크는 새 창 + 참조 차단
        if ($tag === 'a' && $el->hasAttribute('href')) {
            $el->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function safeUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }
        // 상대 경로 허용, 그 외에는 http(s)/mailto/data:image 만
        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }

        return (bool) preg_match('#^(https?://|mailto:|data:image/(png|jpe?g|gif|webp);base64,)#i', $url);
    }

    private static function cleanStyle(string $style): string
    {
        $kept = [];
        foreach (explode(';', $style) as $decl) {
            if (! str_contains($decl, ':')) {
                continue;
            }
            [$prop, $val] = array_map('trim', explode(':', $decl, 2));
            $prop = strtolower($prop);
            if (! in_array($prop, self::ALLOWED_STYLES, true)) {
                continue;
            }
            if (preg_match('/url\s*\(|expression|javascript:/i', $val)) {
                continue;
            }
            $kept[] = $prop.': '.$val;
        }

        return implode('; ', $kept);
    }
}
