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

use froq\service\Service;

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
     * @var froq\service\Service
     */
    private $service;

    /**
     * File (main).
     * @var string
     */
    private $file;

    /**
     * File head (header).
     * @var string
     */
    private $fileHead;

    /**
     * File foot (footer).
     * @var string
     */
    private $fileFoot;

    /**
     * Metas.
     * @var array
     */
    private $metas = [];

    /**
     * Html.
     * @var froq\view\Html
     */
    private $html;

    /**
     * Constructor.
     * @param froq\service\Service $service
     * @param string               $file
     */
    public function __construct(Service $service, string $file = null)
    {
        $this->service = $service;

        if ($file != null) {
            $this->setFile($file);
        }

        $this->html = new Html();
    }

    /**
     * Get service.
     * @return froq\service\Service
     */
    public function getService(): Service
    {
        return $this->service;
    }

    /**
     * Get file.
     * @return ?string
     */
    public function getFile(): ?string
    {
        return $this->file;
    }

    /**
     * Get file head.
     * @return ?string
     */
    public function getFileHead(): ?string
    {
        return $this->fileHead;
    }

    /**
     * Get file foot.
     * @return ?string
     */
    public function getFileFoot(): ?string
    {
        return $this->fileFoot;
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
     * Het html.
     * @return froq\view\Html
     */
    public function getHtml(): Html
    {
        return $this->html;
    }

    /**
     * Set file.
     * @param  string $file
     * @return void
     */
    public function setFile(string $file): void
    {
        $this->file = $this->prepareFilePath($file);
    }

    /**
     * Set file head.
     * @return void
     */
    public function setFileHead(): void
    {
        // check local service file
        $this->fileHead = $this->prepareFilePath('head', false);
        if (!file_exists($this->fileHead)) {
            // look up for default file
            $this->fileHead = $this->prepareDefaultFilePath('head');
        }
    }

    /**
     * Set file foot.
     * @return void
     */
    public function setFileFoot(): void
    {
        // check local service file
        $this->fileFoot = $this->prepareFilePath('foot', false);
        if (!file_exists($this->fileFoot)) {
            // look up for default file
            $this->fileFoot = $this->prepareDefaultFilePath('foot');
        }
    }

    /**
     * Load.
     * @param  string     $file
     * @param  array|null $data
     * @return void
     */
    public function load(string $file, array $data = null): void
    {
        if ($data != null) {
            extract($data);
        }
        include $file;
    }

    /**
     * Display.
     * @param  array|null $data
     * @return void
     */
    public function display(array $data = null): void
    {
        if ($this->fileHead != null) {
            $this->load($this->fileHead, $data);
        }

        $this->load($this->file, $data); // main file

        if ($this->fileFoot != null) {
            $this->load($this->fileFoot, $data);
        }
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
     * @return any
     */
    public function getMeta(string $name, $valueDefault = null)
    {
        return $this->metas[$name] ?? $valueDefault;
    }

    /**
     * Meta (get/set meta).
     * @param  string   $name
     * @param  any|null $value
     * @return any|null|void
     */
    public function meta(string $name, $value = null)
    {
        if ($value === null) {
            return $this->getMeta($name);
        }

        $this->setMeta($name, $value);
    }

    /**
     * Html.
     * @param  ... $arguments
     * @return string
     * @since  3.0
     */
    public function html(...$arguments): string
    {
        return $this->html::create(...$arguments);
    }

    /**
     * Prepare file path.
     * @param  string $file
     * @param  bool   $fileCheck
     * @return string
     * @throws froq\view\ViewException
     */
    private function prepareFilePath(string $file, bool $fileCheck = true): string
    {
        $file = str_replace(["\0", "\r", "\n"], '', trim($file));
        if ($file == '') {
            throw new ViewException('No valid file given');
        }

        // custom
        if ($file[0] == '.') {
            $fileCheck = false;
            if (!file_exists($file)) {
                throw new ViewException("Custom view file '{$file}' not found");
            }
        } elseif ($file[0] == '@') {
            // custom in default view folder
            $file = sprintf('%s/app/service/default/view/%s.php', APP_DIR, substr($file, 1));
            $fileCheck = false;
            if (!file_exists($file)) {
                throw new ViewException("Default view file '{$file}' not found");
            }
        } else {
            $file = sprintf('%s/app/service/%s/view/%s.php', APP_DIR, $this->service->getName(), $file);
        }

        if ($fileCheck && !file_exists($file)) {
            // look up default folder
            if ($this->service->isDefaultService()) {
                $file = sprintf('%s/app/service/default/%s/view/%s', APP_DIR,
                    $this->service->getName(), basename($file));
            }

            if (!file_exists($file)) {
                throw new ViewException("View file '{$file}' not found");
            }
        }

        return $file;
    }

    /**
     * Prepare default file path.
     * @param  string $file
     * @param  bool   $fileCheck
     * @return string
     * @throws froq\view\ViewException
     */
    private function prepareDefaultFilePath(string $file, bool $fileCheck = true): string
    {
        $file = sprintf('%s/app/service/default/view/%s.php', APP_DIR, $file);
        if ($fileCheck && !file_exists($file)) {
            throw new ViewException("View file '{$file}' not found");
        }

        return $file;
    }
}
