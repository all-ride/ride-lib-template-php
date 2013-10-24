<?php

namespace pallo\library\template;

use pallo\library\template\exception\ResourceNotFoundException;
use pallo\library\template\exception\ResourceNotSetException;
use pallo\library\system\file\browser\FileBrowser;

/**
 * Plain PHP template engine
 */
class PhpTemplateEngine extends AbstractTemplateEngine {

    /**
     * Name of this template engine
     * @var string
     */
    const NAME = 'php';

    /**
     * Instance of the file browser
     * @var pallo\library\system\file\browser\FileBrowser
     */
    protected $fileBrowser;

    /**
     * Relative path for the file browser
     * @var string
     */
    protected $path;

    /**
     * Machine name of the current theme
     * @var string
     */
    protected $theme;

    /**
     * Constructs a new PHP template engine
     * @param pallo\library\system\file\browser\FileBrowser $fileBrowser
     * @param string $path
     * @return null
     */
    public function __construct(FileBrowser $fileBrowser, $path = null) {
        $this->fileBrowser = $fileBrowser;

        $this->setPath($path);
        $this->setTheme(null);
    }

    /**
     * Sets the path for the file browser
     * @param string $path
     * @return null
     * @throws pallo\library\template\exception\TemplateException when the
     * provided path is invalid or empty
     */
    public function setPath($path) {
        if ($path !== null && (!is_string($path) || !$path)) {
            throw new TemplateException('Could not set the path for the file browser: provided path is empty or invalid');
        }

        $this->path = $path;
    }

    /**
     * Sets the current theme
     * @param string $theme Machine name of the theme
     * @return null
     */
    public function setTheme($theme = null) {
        if ($theme === null) {
            $this->theme = 'default';

            return;
        }

        if (!is_string($theme) || !$theme) {
            throw new TemplateException('Could not set the theme: provided theme is empty or invalid');
        }

        $this->theme = $theme;
    }

    /**
     * Renders a template
     * @param pallo\library\template\Template $template Template to render
     * @return string Rendered template
     * @throws pallo\library\template\exception\ResourceNotSetException when
     * no template resource was set to the template
     * @throws pallo\library\template\exception\ResourceNotFoundException when
     * the template resource could not be found by the engine
     */
    public function render(Template $template) {
        $resource = $template->getResource();
        if (!$resource) {
            throw new ResourceNotSetException();
        }

        $templateFile = $this->getTemplateFile($resource);

        extract($template->getVariables());

        ob_start();
        require $templateFile;
        $rendered = ob_get_contents();
        ob_end_clean();

        return $rendered;
    }

    /**
     * Gets the template file for the provided resource
     * @param string $resource Resource of the template
     * @return string Absolute path of the template file
     * @throws pallo\library\template\exception\ResourceNotFoundException
     */
    public function getTemplateFile($resource) {
        if ($this->theme) {
            try {
                $file = $this->getThemeTemplateFile($name, $this->theme);
            } catch (TemplateNotFoundException $exception) {
                $file = $this->getThemeTemplateFile($name, 'default');
            }
        } else {
            $file = $this->getThemeTemplateFile($name);
        }

        return $file->getAbsolutePath;
    }

    /**
     * Gets the template file for the provided resource
     * @param string $resource Resource of the template
     * @throws pallo\library\template\exception\ResourceNotFoundException
     * @return pallo\library\system\file\File instance of a File if the source
     * is found
     */
    protected function getThemeTemplateFile($resource, $theme = null) {
        $path = '';
        if ($this->path) {
            $path = $this->path . '/';
        }

        if ($theme) {
            $path .= $theme . '/';
        }

        $file = $path . '/' . $resource . '.php';

        $file = $this->fileBrowser->getFile($file);
        if (!$file) {
            if ($this->theme != 'default') {
                $file = $path . self::NAME . '/default/' . $resource . '.php';

                $file = $this->fileBrowser->getFile($file);
            }

            if (!$file) {
                throw new ResourceNotFoundException($path . self::NAME . '/' . $this->theme . '/' . $resource . '.php');
            }
        }

        return $file;
    }

}