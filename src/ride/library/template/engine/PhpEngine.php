<?php

namespace ride\library\template\engine;

use ride\library\template\exception\ResourceNotFoundException;
use ride\library\template\exception\ResourceNotSetException;
use ride\library\template\Template;
use ride\library\system\file\browser\FileBrowser;

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
     * Extension for the template resources
     * @var string
     */
    const EXTENSION = 'php';

    /**
     * Instance of the file browser
     * @var \ride\library\system\file\browser\FileBrowser
     */
    protected $fileBrowser;

    /**
     * Relative path for the file browser
     * @var string
     */
    protected $path;

    /**
     * Constructs a new PHP template engine
     * @param \ride\library\system\file\browser\FileBrowser $fileBrowser
     * @param string $path
     * @return null
     */
    public function __construct(FileBrowser $fileBrowser, $path = null) {
        $this->fileBrowser = $fileBrowser;

        $this->setPath($path);
    }

    /**
     * Sets the path for the file browser
     * @param string $path
     * @return null
     * @throws \ride\library\template\exception\TemplateException when the
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
     * @param \ride\library\template\Template $template Template to render
     * @return string Rendered template
     * @throws \ride\library\template\exception\ResourceNotSetException when
     * no template resource was set to the template
     * @throws \ride\library\template\exception\ResourceNotFoundException when
     * the template resource could not be found by the engine
     */
    public function render(Template $template) {
        $templateFile = $this->getFile($template);

        extract($template->getVariables());

        ob_start();
        require $templateFile;
        $rendered = ob_get_contents();
        ob_end_clean();

        return $rendered;
    }

    /**
     * Gets the template resource
     * @param \ride\library\template\Template $template Template to get the
     * resource of
     * @return string Absolute path of the template resource
     * @throws \ride\library\template\exception\ResourceNotSetException when
     * no template was set to the template
     * @throws \ride\library\template\exception\ResourceNotFoundException when
     * the template could not be found by the engine
     */
    public function getFile(Template $template) {
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
     * Gets the available template resources for the provided namespace
     * @param string $namespace
     * @param string $theme
     * @return array
     */
    public function getFiles($namespace, $theme = null) {
        $files = array();

        $basePath = '';
        if ($this->path) {
            $basePath = $this->path . '/';
        }

        $theme = $this->themeModel->getTheme($theme);
        $themeHierarchy = $this->getThemeHierarchy($theme);

        if ($themeHierarchy) {
            foreach ($themeHierarchy as $theme => $null) {
                $path = $basePath . $theme . '/' . $namespace;

                $files += $this->getPathFiles($path, $basePath . $theme . '/');
            }
        } else {
            $path = $basePath . $namespace;

            $files += $this->getPathFiles($path, $basePath);
        }

        return $files;
    }

    /**
     * Gets the files for the provided path
     * @param string $path Relative path in the Ride file structure of the
     * requested files
     * @param string $basePath Relative path in the Ride file structure of the
     * engine and theme
     * @return array
     */
    protected function getPathFiles($path, $basePath) {
        $files = array();

        $pathDirectories = $this->fileBrowser->getFiles($path);
        if (!$pathDirectories) {
            return $files;
        }

        foreach ($pathDirectories as $pathDirectory) {
            $pathFiles = $pathDirectory->read();
            foreach ($pathFiles as $pathFile) {
                if ($pathFile->isDirectory() || $pathFile->getExtension() != self::EXTENSION) {
                    continue;
                }

                $pathFile = $this->fileBrowser->getRelativeFile($pathFile);
                $filePath = $pathFile->getPath();
                $resultPath = substr(str_replace($basePath, '', $filePath), 0, (strlen(self::EXTENSION) + 1) * -1);
                $resultName = substr(str_replace($path . '/', '', $filePath), 0, (strlen(self::EXTENSION) + 1) * -1);

                $files[$resultPath] = $resultName;
            }
        }

        return $files;
    }

    /**
     * Gets the template file for the provided resource
     * @param string $resource Resource of the template
     * @throws \ride\library\template\exception\ResourceNotFoundException
     * @return \ride\library\system\file\File instance of a File if the source
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

        $file = $path . '/' . $resource . '.' . self::EXTENSION;

        $file = $this->fileBrowser->getFile($file);
        if (!$file) {
            throw new ResourceNotFoundException($path . '/' . $resource . '.' . self::EXTENSION);
        }

        return $file;
    }

}
