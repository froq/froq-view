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

use froq\view\ViewException;
use froq\service\ServiceInterface;

/**
 * View.
 * @package froq\view
 * @object  froq\view\View
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   1.0
 */
final class View
{
    /**
     * Service.
     * @var froq\service\ServiceInterface
     */
    private ServiceInterface $service;

    /**
     * Partials.
     * @var array<string,string>
     */
    private array $partials = [
        // Possible for default head & foot files.
        // head => './app/service/_default/view/head.php' or just '@head'
        // foot => './app/service/_default/view/foot.php' or just '@foot'
    ];

    /**
     * Metas.
     * @var array<string,any>
     */
    private array $metas = [];

    /**
     * Constructor.
     * @param froq\service\ServiceInterface $service
     * @param array<string,string>|null     $partials
     */
    public function __construct(ServiceInterface $service, array $partials = null)
    {
        $this->service = $service;
        $this->partials = $partials ?? [];
    }

    /**
     * Invoke.
     * @param ... $arguments
     */
    public function __invoke(...$arguments)
    {
        $this->display(...$arguments);
    }

    /**
     * Get service.
     * @return froq\service\ServiceInterface
     */
    public function getService(): ServiceInterface
    {
        return $this->service;
    }

    /**
     * Get partials.
     * @return array
     */
    public function getPartials(): array
    {
        return $this->partials;
    }

    /**
     * Get metas.
     * @return array
     */
    public function getMetas(): array
    {
        return $this->metas;
    }

    /**
     * Set meta.
     * @param  string $name
     * @param  any    $value
     * @return void
     */
    public function setMeta(string $name, $value): void
    {
        $this->metas[$name] = $value;
    }

    /**
     * Get meta.
     * @param  string   $name
     * @param  any|null $valueDefault
     * @return any|null
     */
    public function getMeta(string $name, $valueDefault = null)
    {
        return $this->metas[$name] ?? $valueDefault;
    }

    /**
     * Display.
     * @param  string                    $file
     * @param  array<string,any>|null    $metadata
     * @param  array<string,string>|null $partials
     * @return void
     */
    public function display(string $file, array $metadata = null, array $partials = null): void
    {
        $partials = $partials ?? $this->partials;
        if ($partials != null) {
            if (isset($partials['head'])) {
                $fileHead = $this->prepareFilePath($partials['head']);
            }
            if (isset($partials['foot'])) {
                $fileFoot = $this->prepareFilePath($partials['foot']);
            }
        }

        $meta = (array) ($metadata['meta'] ?? []);
        $data = (array) ($metadata['data'] ?? []);

        // Add metas for later uses in-service global scope.
        if ($meta != null) {
            foreach ($meta as $name => $value) {
                $this->setMeta($name, $value);
            }
        }

        // Extract data & make accessible in included files below.
        if ($data != null) {
            extract($data);
        }

        // Load head file.
        isset($fileHead) && include $fileHead;

        // Load main file.
        include $this->prepareFilePath($file);

        // Load foot file.
        isset($fileFoot) && include $fileFoot;
    }

    /**
     * Prepare file path.
     * @param  string $file
     * @return string
     * @throws froq\view\ViewException
     */
    private function prepareFilePath(string $file): string
    {
        if (!defined('APP_DIR')) {
            throw new ViewException('APP_DIR is not defined');
        }

        if (!preg_match('~^[@]?[\w\-\.\/]+$~', $file)) {
            throw new ViewException('No valid file given');
        }

        // Direct path.
        if (file_exists($file)) {
            return $file;
        }

        // Check sub-folder (eg: view/post.php or view/post/edit.php)
        $file = strpos($file, '/') > 0 ? $file : basename($file);

        $fileExt = '.php';
        $fileExtPos = strrpos($file, $fileExt);
        if ($fileExtPos) {
            $file = substr($file, 0, $fileExtPos);
        }

        // Custom files in default view folder (eg: @error => app/service/_default/view/error.php)
        if ($file[0] == '@') {
            $file = sprintf('%s/app/service/_default/view/%s.php', APP_DIR, substr($file, 1));

            if (!file_exists($file)) {
                throw new ViewException("Default view file '{$file}' not found");
            }

            return $file;
        }

        // Use default view folder for default services.
        if ($this->service->isDefaultService()) {
            $file = sprintf('%s/app/service/_default/%s/view/%s.php', APP_DIR, $this->service->getName(),
                $file);
        } else {
            $file = sprintf('%s/app/service/%s/view/%s.php', APP_DIR, $this->service->getName(),
                $file);
        }

        if (!file_exists($file)) {
            throw new ViewException("View file '{$file}' not found");
        }

        return $file;
    }
}
