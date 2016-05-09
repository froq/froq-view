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

use Froq\App;
use Froq\Util\Traits\GetterTrait as Getter;

/**
 * @package     Froq
 * @subpackage  Froq\View
 * @object      Froq\View\View
 * @author      Kerem Güneş <k-gun@mail.com>
 */
final class View
{
    /**
     * Getter.
     * @object Froq\Util\Traits\Getter
     */
    use Getter;

    /**
     * Partial files.
     * @const string
     */
    const PARTIAL_HEAD = 'partial/head',
          PARTIAL_FOOT = 'partial/foot';

    /**
     * App object.
     * @var Froq\App
     */
    private $app;

    /**
     * File (main).
     * @var string
     */
    private $file;

    /**
     * Head file.
     * @var string
     */
    private $headFile;

    /**
     * Foot file.
     * @var string
     */
    private $footFile;

    /**
     * Metas.
     * @var array
     */
    private $metas = [];

    /**
     * Constructor.
     * @param Froq\App $app
     * @param string   $file
     */
    final public function __construct(App $app, string $file = null)
    {
        $this->app = $app;

        // set file
        if ($file) {
            $this->setFile($file);
        }
    }

    /**
     * Set file.
     * @param  string $file
     * @return self
     */
    final public function setFile(string $file): self
    {
        $this->file = $this->prepareFile($file);

        return $this;
    }

    /**
     * Set head file.
     * @return self
     */
    final public function setHeadFile(): self
    {
        // check local service file
        $this->headFile = $this->prepareFile(self::PARTIAL_HEAD, false);
        if (!is_file($this->headFile)) {
            // look up for global service file
            $this->headFile = $this->prepareDefaultFile(self::PARTIAL_HEAD);
        }

        return $this;
    }

    /**
     * Set foot file.
     * @return self
     */
    final public function setFootFile(): self
    {
        // check local service file
        $this->footFile = $this->prepareFile(self::PARTIAL_FOOT, false);
        if (!is_file($this->footFile)) {
            // look up for global service file
            $this->footFile = $this->prepareDefaultFile(self::PARTIAL_FOOT);
        }

        return $this;
    }

    /**
     * Display view file.
     * @param  array|null $data
     * @return void
     */
    final public function display(array $data = null)
    {
        $this->include($this->file, $data);
    }

    /**
     * Display partial/head file.
     * @param  array|null $data
     * @return void
     */
    final public function displayHead(array $data = null)
    {
        if ($this->headFile) {
            $this->include($this->headFile, $data);
        }
    }

    /**
     * Display partial/foot file.
     * @param  array $data
     * @return void
     */
    final public function displayFoot(array $data = null)
    {
        if ($this->footFile) {
            $this->include($this->footFile, $data);
        }
    }

    /**
     * Display all files.
     * @param  array|null $data
     * @return void
     */
    final public function displayAll(array $data = null)
    {
        $this->displayHead($data);
        $this->display($data);
        $this->displayFoot($data);
    }

    /**
     * Include file.
     * @param  string $file
     * @param  array|null $data
     * @return void
     */
    final public function include(string $file, array $data = null)
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
    final public function setMeta(string $name, $value): self
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
    final public function getMeta(string $name, $valueDefault = null)
    {
        return $this->metas[$name] ?? $valueDefault;
    }

    /**
     * Prepare file path.
     * @param  string $file
     * @param  bool   $fileCheck
     * @return string
     */
    final private function prepareFile(string $file, bool $fileCheck = true): string
    {
        if ($file == '') {
            throw new ViewException("No file given!");
        }

        // custom file given
        if ($file[0] == '.') {
            $file = sprintf('%s.php', $file);
        } else {
            $file = sprintf('./app/service/%s/view/%s.php',
                $this->app->service->name, $file);
        }

        // check file
        if ($fileCheck && !is_file($file)) {
            // look up default folder
            if ($this->app->service->isDefaultService()) {
                $file = sprintf('./app/service/default/%s/view/%s',
                    $this->app->service->name, basename($file));
            }

            if (!is_file($file)) {
                throw new ViewException("View file not found! file: '{$file}'");
            }
        }

        return $file;
    }

    /**
     * Prepare default file path.
     * @param  string $file
     * @param  bool   $fileCheck
     * @return string
     */
    final private function prepareDefaultFile(string $file, bool $fileCheck = true): string
    {
        $file = sprintf('./app/service/default/view/%s.php', $file);
        if ($fileCheck && !is_file($file)) {
            throw new ViewException("View file not found! file: '{$file}'");
        }

        return $file;
    }
}
