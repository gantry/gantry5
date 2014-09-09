<?php
namespace Gantry\Framework;

class Document
{
    public static function addHeaderTag(array $element)
    {
        $doc = \JFactory::getDocument();
        switch ($element['tag']) {
            case 'link':
                if (!empty($element['href']) && !empty($element['rel']) && $element['rel'] == 'stylesheet') {
                    $href = $element['href'];
                    $type = !empty($element['type']) ? $element['type'] : 'text/css';
                    $media = !empty($element['media']) ? $element['media'] : null;
                    unset($element['tag'], $element['rel'], $element['content'], $element['href'], $element['type'], $element['media']);
                    $doc->AddStyleSheet($href, $type, $media, $element);
                    return true;
                }
                break;

            case 'style':
                if (!empty($element['content'])) {
                    $content = $element['content'];
                    $type = !empty($element['type']) ? $element['type'] : 'text/css';
                    $doc->addStyleDeclaration($content, $type);
                    return true;
                }
                break;

            case 'script':
                if (!empty($element['src'])) {
                    $src = $element['src'];
                    $type = !empty($element['type']) ? $element['type'] : 'text/javascript';
                    $doc->addScript($src, $type);
                    return true;

                } elseif (!empty($element['content'])) {
                    $content = $element['content'];
                    $type = !empty($element['type']) ? $element['type'] : 'text/javascript';
                    $doc->addScriptDeclaration($content, $type);
                    return true;
                }
                break;
        }
        return false;
    }

    public static function rootUri()
    {
        return '/' . trim(\JUri::root(true), '/');
    }
}
