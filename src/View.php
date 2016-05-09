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

namespace Froq\Util;

use Froq\App;
use Froq\Util\Traits\GetterTrait as Getter;

/**
 * @package     Froq
 * @subpackage  Froq\Util
 * @object      Froq\Util\View
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
    private $fileHead;

    /**
     * Foot file.
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
     * @param Froq\App $app
     * @param string   $file
     * @param bool     $usePartials
     */
    final public function __construct(App $app, string $file = null, bool $usePartials = false)
    {
        $this->app = $app;

        // set file
        if ($file) {
            $this->setFile($file);
        }

        // set partials
        if ($usePartials) {
            // head: check local service file
            $this->fileHead = $this->prepareFile(self::PARTIAL_HEAD, false);
            if (!is_file($this->fileHead)) {
                // look up for global service file
                $this->fileHead = $this->prepareFileDefault(self::PARTIAL_HEAD);
            }

            // foot: check local service file
            $this->fileFoot = $this->prepareFile(self::PARTIAL_FOOT, false);
            if (!is_file($this->fileFoot)) {
                // look up for global service file
                $this->fileFoot = $this->prepareFileDefault(self::PARTIAL_FOOT);
            }
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
     * Display view file.
     * @param  array|null $data
     * @return void
     */
    final public function display(array $data = null)
    {
        // set and include main (real) file
        $this->include($this->file, $data);
    }

    /**
     * Display partial/head file.
     * @param  array|null $data
     * @return void
     */
    final public function displayHead(array $data = null)
    {
        $this->include($fileHead, $data);
    }

    /**
     * Display partial/foot file.
     * @param  array $data
     * @return void
     */
    final public function displayFoot(array $data = null)
    {
        $this->include($fileFoot, $data);
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
     * @param  string $value
     * @return self
     */
    final public function setMeta(string $name, string $value): self
    {
        $this->metas[$name] = $value;

        return $this;
    }

    /**
     * Get meta.
     * @param  string      $name
     * @param  string|null $valueDefault
     * @return string|null
     */
    final public function getMeta(string $name, string $valueDefault = null)
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
            if ($this->app->service->isDefault()) {
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
    final private function prepareFileDefault(string $file, bool $fileCheck = true): string
    {
        $file = sprintf('./app/service/default/view/%s.php', $file);
        if ($fileCheck && !is_file($file)) {
            throw new ViewException("View file not found! file: '{$file}'");
        }

        return $file;
    }
}
