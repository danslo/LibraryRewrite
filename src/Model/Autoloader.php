<?php

class Danslo_LibraryRewrite_Model_Autoloader
{

    /**
     * Path to library rewrites.
     */
    const XML_PATH_LIBRARY_REWRITE = 'global/libraries/rewrite';

    /**
     * The namespace to wrap original classes in.
     */
    const REWRITE_NAMESPACE = 'Magento';

    /**
     * The directory in which to store cached rewritten libraries.
     */
    const REWRITE_DIRECTORY = 'library_rewrite';

    /**
     * In which directory inside the module we should look for the rewrite.
     */
    const REWRITE_MODULE_DIRECTORY = 'lib';

    /**
     * Cached rewrites.
     *
     * @var array|null
     */
    protected $_rewrites = null;

    /**
     * Gets library rewrites from config.
     *
     * @return array
     */
    protected function _getLibraryRewrites()
    {
        if ($this->_rewrites === null) {
            $rewrites = array();
            $node = Mage::getConfig()->getNode(self::XML_PATH_LIBRARY_REWRITE);
            if ($node) {
                $rewrites = $node->asArray();
            }
            $this->_rewrites = $rewrites;
        }
        return $this->_rewrites;
    }

    /**
     * Gets the rewrite directory.
     *
     * @return string
     */
    protected function _getRewriteDirectory()
    {
        return Mage::getBaseDir('tmp') . DS . self::REWRITE_DIRECTORY;
    }

    /**
     * Creates the rewritten library directory if it doesn't exist.
     *
     * @return void
     */
    protected function _createRewriteDirectory()
    {
        $directory = $this->_getRewriteDirectory();
        if (!file_exists($directory)) {
            @mkdir($directory, 0777, true);
        }
    }

    /**
     * Register the autoloader.
     *
     * @return void
     */
    public function register()
    {
        $this->_createRewriteDirectory();
        spl_autoload_register(array($this, 'load'), true, true);
    }

    /**
     * Unregister the autoloader.
     *
     * @return void
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'load'));
    }

    /**
     * Gets a library file path from a class name.
     *
     * @param string $class
     *
     * @return string|null
     */
    protected function _getLibraryFilePath($class, $baseDirectory)
    {
        $classPath = str_replace(' ', DS, ucwords(str_replace('_', ' ', $class))) . '.php';
        return $baseDirectory . DS . $classPath;
    }

    /**
     * Gets the library and wraps it in a namespace.
     *
     * @param string $filePath
     *
     * @todo I'm sure this can be a lot cleaner.
     *
     * @return string
     */
    protected function _getNamespacedLibrary($filePath)
    {
        $classData = file_get_contents($filePath);

        // Get rid of the initial PHP statement.
        $classData = preg_replace('/<\?php/', '', $classData, 1);

        // Class extends should look in the global namespace, or else we cause
        // the Varien Autoloader to choke.
        $classData = preg_replace_callback('/(class\s+\w+)(\s+extends\s+)?(\w+)/s', function($matches) use($classData) {
            if (isset($matches[3])) {
                return sprintf('%s extends %s', $matches[1], '\\' . $matches[3]);
            } else {
                return $classData;
            }
        }, $classData);

        return '<?php namespace ' . self::REWRITE_NAMESPACE . ';' . PHP_EOL . $classData;
    }

    /**
     * Gets a path to where the rewritten library should be stored.
     *
     * @param string $libraryHash
     *
     * @return string
     */
    protected function _getRewritePath($libraryHash)
    {
        return $this->_getRewriteDirectory() . DS . $libraryHash;
    }

    /**
     * Attempt to load the given class.
     *
     * @param string $class
     *
     * @return void
     */
    public function load($class)
    {
        $rewrites = $this->_getLibraryRewrites();
        if (isset($rewrites[$class])) {
            $libraryPath = $this->_getLibraryFilePath($class, Mage::getBaseDir('lib'));
            $rewritePath = $this->_getLibraryFilePath($class, Mage::getModuleDir(null, $rewrites[$class]) . DS . self::REWRITE_MODULE_DIRECTORY);

            // Check for existence of the original library and the rewrite.
            if (file_exists($libraryPath) && file_exists($rewritePath)) {
                $namespacedLibrary     = $this->_getNamespacedLibrary($libraryPath);
                $namespacedLibraryPath = $this->_getRewritePath(crc32($namespacedLibrary));

                // Store the namespaced library if we haven't done so yet.
                if (!file_exists($namespacedLibraryPath)) {
                    file_put_contents($namespacedLibraryPath, $namespacedLibrary);
                }

                // Include the original library.
                require_once $namespacedLibraryPath;

                // And then the rewrite.
                require_once $rewritePath;
            }
        }
    }

}
