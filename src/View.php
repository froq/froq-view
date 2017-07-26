<?php
/**
 * Copyright (c) 2016 Kerem Güneş
 *    <k-gun@mail.com>
 *
 * GNU General Public License v3.0
 *    <http://www.gnu.org/licenses/gpl-3.0.txt>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Froq\View;

use Froq\Service\Service;

/**
 * @package    Froq
 * @subpackage Froq\View
 * @object     Froq\View\View
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class View
{
    /**
     * Partial files.
     * @const string
     */
    const PARTIAL_HEAD = 'partial/head',
          PARTIAL_FOOT = 'partial/foot';

    /**
     * Service.
     * @var Froq\Service\Service
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
     * Constructor.
     * @param Froq\Service\Service $service
     * @param string               $file
     */
    public function __construct(Service $service, string $file = null)
    {
        $this->service = $service;

        if ($file != null) {
            $this->setFile($file);
        }
    }

    /**
     * Get service.
     * @return Froq\Service\Service
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
     * Set file.
     * @param  string $file
     * @return self
     */
    public function setFile(string $file): self
    {
        $this->file = $this->prepareFile($file);

        return $this;
    }

    /**
     * Set file head.
     * @return self
     */
    public function setFileHead(): self
    {
        // check local service file
        $this->fileHead = $this->prepareFile(self::PARTIAL_HEAD, false);
        if (!is_file($this->fileHead)) {
            // look up for global service file
            $this->fileHead = $this->prepareFileDefault(self::PARTIAL_HEAD);
        }

        return $this;
    }

    /**
     * Set file foot.
     * @return self
     */
    public function setFileFoot(): self
    {
        // check local service file
        $this->fileFoot = $this->prepareFile(self::PARTIAL_FOOT, false);
        if (!is_file($this->fileFoot)) {
            // look up for global service file
            $this->fileFoot = $this->prepareFileDefault(self::PARTIAL_FOOT);
        }

        return $this;
    }

    /**
     * Display.
     * @param  array|null $data
     * @return void
     */
    public function display(array $data = null)
    {
        $this->include($this->file, $data);
    }

    /**
     * Display head.
     * @param  array|null $data
     * @return void
     */
    public function displayHead(array $data = null)
    {
        if ($this->fileHead) {
            $this->include($this->fileHead, $data);
        }
    }

    /**
     * Display foot.
     * @param  array|null $data
     * @return void
     */
    public function displayFoot(array $data = null)
    {
        if ($this->fileFoot) {
            $this->include($this->fileFoot, $data);
        }
    }

    /**
     * Display all.
     * @param  array|null $data
     * @return void
     */
    public function displayAll(array $data = null)
    {
        $this->displayHead($data);
        $this->display($data);
        $this->displayFoot($data);
    }

    /**
     * Include.
     * @param  string     $file
     * @param  array|null $data
     * @return void
     */
    public function include(string $file, array $data = null)
    {
        if (!empty($data)) {
            extract($data);
        }

        include($file);
    }

    /**
     * Set meta.
     * @param  string $name
     * @param  any    $value
     * @return self
     */
    public function setMeta(string $name, $value): self
    {
        $this->metas[$name] = $value;

        return $this;
    }

    /**
     * Get meta.
     * @param  string $name
     * @param  any    $valueDefault
     * @return any
     */
    public function getMeta(string $name, $valueDefault = null)
    {
        return $this->metas[$name] ?? $valueDefault;
    }

    /**
     * Prepare file.
     * @param  string $file
     * @param  bool   $fileCheck
     * @return string
     * @throws Froq\View\ViewException
     */
    private function prepareFile(string $file, bool $fileCheck = true): string
    {
        if ($file == '') {
            throw new ViewException("No file given!");
        }

        // custom file given
        if ($file[0] == '.') {
            $file = sprintf('%s.php', $file);
        } else {
            $file = sprintf('%s/app/service/%s/view/%s.php', APP_DIR, $this->service->getName(), $file);
        }

        // check file
        if ($fileCheck && !is_file($file)) {
            // look up default folder
            if ($this->service->isDefaultService()) {
                $file = sprintf('%s/app/service/default/%s/view/%s', APP_DIR,
                    $this->service->getName(), basename($file));
            }

            if (!is_file($file)) {
                throw new ViewException("View file not found! file: '{$file}'");
            }
        }

        return $file;
    }

    /**
     * Prepare file default.
     * @param  string $file
     * @param  bool   $fileCheck
     * @return string
     * @throws Froq\View\ViewException
     */
    private function prepareFileDefault(string $file, bool $fileCheck = true): string
    {
        $file = sprintf('%s/app/service/default/view/%s.php', APP_DIR, $file);
        if ($fileCheck && !is_file($file)) {
            throw new ViewException("View file not found! file: '{$file}'");
        }

        return $file;
    }
}
