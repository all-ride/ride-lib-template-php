<?php

namespace pallo\library\template\engine;

use pallo\library\template\exception\ResourceNotFoundException;
use pallo\library\template\exception\ResourceNotSetException;
use pallo\library\template\theme\ThemeModel;
use pallo\library\template\Template;
use pallo\library\system\file\browser\FileBrowser;

/**
 * Plain PHP template engine
 */
class PhpEngine extends AbstractEngine {

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
     * Constructs a new PHP template engine
     * @param pallo\library\system\file\browser\FileBrowser $fileBrowser
     * @param string $path
     * @return null
     */
    public function __construct(FileBrowser $fileBrowser, ThemeModel $themeModel, $path = null) {
        $this->fileBrowser = $fileBrowser;

        $this->setThemeModel($themeModel);
        $this->setPath($path);
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
     * Renders a template
     * @param pallo\library\template\Template $template Template to render
     * @return string Rendered template
     * @throws pallo\library\template\exception\ResourceNotSetException when
     * no template resource was set to the template
     * @throws pallo\library\template\exception\ResourceNotFoundException when
     * the template resource could not be found by the engine
     */
    public function render(Template $template) {
        $templateFile = $this->getTemplateFile($template);

        extract($template->getVariables());

        ob_start();
        require $templateFile;
        $rendered = ob_get_contents();
        ob_end_clean();

        return $rendered;
    }

    /**
     * Gets the template file for the provided resource
     * @param pallo\library\template\Template $template
     * @return string Absolute path of the template file
     * @throws pallo\library\template\exception\ResourceNotFoundException
     */
    public function getTemplateFile(Template $template) {
        $resource = $template->getResource();
        if (!$resource) {
            throw new ResourceNotSetException();
        }

        $file = null;

        $themeHierarchy = $this->getTheme($template);
        if ($themeHierarchy) {
            foreach ($themeHierarchy as $theme => $null) {
                try {
                    $file = $this->getThemeTemplateFile($name, $theme);

                    break;
                } catch (ResourceNotFoundException $exception) {
                    $file = null;
                }
            }
        }

        if (!$file) {
            $file = $this->getThemeTemplateFile($name);
        }

        return $file->getAbsolutePath();
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