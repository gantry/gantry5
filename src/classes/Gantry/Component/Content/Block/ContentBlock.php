<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Content\Block;

use Gantry\Component\Serializable\Serializable;

/**
 * Class to create nested blocks of content.
 *
 * $innerBlock = ContentBlock::create();
 * $innerBlock->setContent('my inner content');
 * $outerBlock = ContentBlock::create();
 * $outerBlock->setContent(sprintf('Inside my outer block I have %s.', $innerBlock->getToken()));
 * $outerBlock->addBlock($innerBlock);
 * echo $outerBlock;
 *
 * @package Gantry\Component\Content\Block
 * @since 5.4.3
 */
class ContentBlock implements ContentBlockInterface
{
    use Serializable;

    /** @var int */
    protected $version = 1;
    /** @var string */
    protected $id;
    /** @var string */
    protected $tokenTemplate = '@@BLOCK-%s@@';
    /** @var string */
    protected $content = '';
    /** @var ContentBlockInterface[] */
    protected $blocks = [];

    /**
     * @param string $id
     * @return static
     * @since 5.4.3
     */
    public static function create($id = null)
    {
        return new static($id);
    }

    /**
     * @param array $serialized
     * @return ContentBlockInterface
     * @since 5.4.3
     */
    public static function fromArray(array $serialized)
    {
        try {
            $type = isset($serialized['_type']) ? $serialized['_type'] : null;
            $id = isset($serialized['id']) ? $serialized['id'] : null;

            if (!$type || !$id || !is_subclass_of($type, ContentBlockInterface::class, true)) {
                throw new \RuntimeException('Bad data');
            }

            $instance = new $type($id);
            $instance->build($serialized);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Cannot unserialize Block: %s', $e->getMessage()), $e->getCode(), $e);
        }

        return $instance;
    }

    /**
     * Block constructor.
     *
     * @param string $id
     * @since 5.4.3
     */
    public function __construct($id = null)
    {
        $this->id = $id ? (string) $id : $this->generateId();
    }

    /**
     * @return string
     * @since 5.4.3
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     * @since 5.4.3
     */
    public function getToken()
    {
        return sprintf($this->tokenTemplate, $this->getId());
    }

    /**
     * @return array
     * @since 5.4.3
     */
    public function toArray()
    {
        $blocks = [];
        foreach ($this->blocks as $block) {
            $blocks[$block->getId()] = $block->toArray();
        }

        $array = [
            '_type' => get_class($this),
            '_version' => $this->version,
            'id' => $this->id,
        ];

        if ($this->content) {
            $array['content'] = $this->content;
        }

        if ($blocks) {
            $array['blocks'] = $blocks;
        }

        return $array;
    }

    /**
     * @return string
     * @since 5.4.3
     */
    public function toString()
    {
        if (!$this->blocks) {
            return (string) $this->content;
        }

        $tokens = [];
        $replacements = [];
        foreach ($this->blocks as $block) {
            $tokens[] = $block->getToken();
            $replacements[] = $block->toString();
        }

        return str_replace($tokens, $replacements, (string) $this->content);
    }

    /**
     * @return string
     * @since 5.4.3
     */
    public function __toString()
    {
        try {
            return $this->toString();
        } catch (\Exception $e) {
            return sprintf('Error while rendering block: %s', $e->getMessage());
        }
    }

    /**
     * @param array $serialized
     * @since 5.4.3
     */
    public function build(array $serialized)
    {
        $this->checkVersion($serialized);

        $this->id = isset($serialized['id']) ? $serialized['id'] : $this->generateId();

        if (isset($serialized['content'])) {
            $this->setContent($serialized['content']);
        }

        $blocks = isset($serialized['blocks']) ? (array) $serialized['blocks'] : [];
        foreach ($blocks as $block) {
            $this->addBlock(self::fromArray($block));
        }
    }

    /**
     * @param string $content
     * @return $this
     * @since 5.4.3
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @param ContentBlockInterface $block
     * @return $this
     * @since 5.4.3
     */
    public function addBlock(ContentBlockInterface $block)
    {
        $this->blocks[$block->getId()] = $block;

        return $this;
    }

    /**
     * @return array
     * @since 5.4.3
     */
    #[\ReturnTypeWillChange]
    public function __serialize()
    {
        return $this->toArray();
    }

    /**
     * @param array $serialized
     * @since 5.4.3
     */
    #[\ReturnTypeWillChange]
    public function __unserialize($serialized)
    {
        $this->build($serialized);
    }

    /**
     * @return string
     * @since 5.4.3
     */
    protected function generateId()
    {
        return uniqid('', true);
    }

    /**
     * @param array $serialized
     * @throws \RuntimeException
     * @since 5.4.3
     */
    protected function checkVersion(array $serialized)
    {
        $version = isset($serialized['_version']) ? (string) $serialized['_version'] : 1;
        if ($version !== $this->version) {
            throw new \RuntimeException(sprintf('Unsupported version %s', $version));
        }
    }
}
