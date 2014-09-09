<?php
namespace Gantry\Framework;

class DocumentHeader
{
    public static function add(array $element)
    {
        $doc = \JFactory::getDocument();
        switch ($element['tag']) {
            case 'link':
                if (!empty($element['href'])) {
                    $href = $element['href'];
                    $type = !empty($element['type']) ? $element['type'] : 'text/css';
                    $media = !empty($element['media']) ? $element['media'] : null;
                    unset($element['tag'], $element['content'], $element['href'], $element['type'], $element['media']);
                    $doc->AddStyleSheet(\JUri::root(true) .'/'. $href, $type, $media, $element);
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
                    $doc->addScript(\JUri::root(true) .'/'. $src, $type);
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
}
