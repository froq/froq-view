<?php
/**
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Copyright (c) 2015 Kerem Güneş
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

namespace froq\view;

/**
 * View exception.
 * @package froq\view
 * @object  froq\view\Html
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0
 */
final class Html
{
    /**
     * Self closing tags.
     * @var array
     */
    private static $selfClosingTags = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
        'link', 'meta', 'param', 'source', 'track', 'wbr', 'command', 'keygen', 'menuitem'];

    /**
     * Call.
     * @param  string $method
     * @param  array  $methodArgs
     * @return string
     */
    public function __call($method, $methodArgs)
    {
        return self::__callStatic($method, $methodArgs);
    }

    /**
     * Call static.
     * @param  string $method
     * @param  array  $methodArgs
     * @return string
     */
    public static function __callStatic($method, $methodArgs)
    {
        return self::create(...array_merge([$method], $methodArgs));
    }

    /**
     * Create.
     * @param  string      $tag
     * @param  string|null $content
     * @param  array|null  $attributes
     * @param  bool        $selfClosing
     * @param  bool        $v5
     * @return string
     */
    public static function create(string $tag, string $content = null, array $attributes = null,
        bool $selfClosing = false, bool $v5 = true): string
    {
        if ($attributes != null) {
            $tmp = [];
            foreach ($attributes as $name => $value) {
                if ($value === null) { // eg: ['a' => 1, 'b' => null, ...]
                    $tmp[] = $name;
                } elseif (is_int($name)) { // eg: ['a' => 1, 'b', ...]
                    $tmp[] = $value;
                } else { // eg: ['a' => 1, 'b' => 2, ...]
                    $tmp[] = sprintf('%s="%s"', $name, strtr((string) $value, ['"' => '&quot;']));
                }
            }
            $attributes = ' '. join(' ', $tmp);
        }

        return $selfClosing || in_array($tag, self::$selfClosingTags)
            ? sprintf('<%s%s%s>', $tag, $attributes, $v5 ? '' : ' /')
            : sprintf('<%s%s>%s</%s>', $tag, $attributes, $content, $tag);
    }
}
