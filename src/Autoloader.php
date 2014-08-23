<?php

namespace Collectivism\Autoloader;

/**
 * Autoloader
 *
 * A simple autoloading class which works out of the box
 *
 * A general-purpose implementation that includes the optional functionality
 * of allowing multiple base directories for a single namespace prefix.
 *
 * Given a foo-bar package of classes in the file system at the following
 * paths ...
 *
 *     /path/to/packages/foo-bar/
 *         src/
 *             Baz.php             # Foo\Bar\Baz
 *             Qux/
 *                 Quux.php        # Foo\Bar\Qux\Quux
 *         tests/
 *             BazTest.php         # Foo\Bar\BazTest
 *             Qux/
 *                 QuuxTest.php    # Foo\Bar\Qux\QuuxTest
 *
 * ... add the path to the class files for the \Foo\Bar\ namespace prefix
 * as follows:
 *
 *      <?php
 *      use \Collectivism\Autoloader;
 *      // instantiate the loader
 *      $loader = Autoloader::getInstance();
 *
 *      $classMap = array(
 *          'Foo\\Bar' => __DIR__ . '/path/to/package/foo-bar/src',
 *          'Foo\\Bar' => __DIR__ . '/path/to/package/foo-bar/tests',
 *      );
 *
 *      // register the autoloader
 *      $loader->register();
 *
 *
 * The following line would cause the autoloader to attempt to load the
 * \Foo\Bar\Qux\Quux class from /path/to/packages/foo-bar/src/Qux/Quux.php:
 *
 *      <?php
 *      new \Foo\Bar\Qux\Quux();
 *
 * The following line would cause the autoloader to attempt to load the
 * \Foo\Bar\Qux\QuuxTest class from /path/to/packages/foo-bar/tests/Qux/QuuxTest.php:
 *
 *      <?php
 *      new \Foo\Bar\Qux\QuuxTest();
 */

class Autoloader
{
    protected static $classMap;

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @staticvar Singleton $instance The *Singleton* instances of this class.
     *
     * @return Singleton The *Singleton* instance.
     */
    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }

        return $instance;
    }

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct()
    {
        self::$classMap = array();
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Private unserialize method to prevent unserializing of the *Singleton*
     * instance.
     *
     * @return void
     */
    private function __wakeup()
    {
    }

    /**
     * An associative array where the key is a namespace prefix and the value
     * is an array of base directories for classes in that namespace.
     *
     * @var array
     */
    protected $prefixes = array();

    /**
     * Register loader with SPL autoloader stack.
     *
     * @return void
     */
    public function register($classMap)
    {
        self::$classMap = $classMap;
        spl_autoload_register(array($this, 'load'));
    }

    /**
     * Load the classmap given via register
     *
     * @return void
     */
    protected function load()
    {
        foreach (self::$classMap as $namespace => $baseDir) {
            $namespace = trim($namespace,'\\');
            $this->addNamespace($namespace,$baseDir);
            $this->getFiles($namespace, $baseDir);
        }
    }

    /**
     * Adds a base directory for a namespace prefix.
     *
     * @param  string $prefix  The namespace prefix.
     * @param  string $baseDir A base directory for class files in the
     *                         namespace.
     * @param  bool   $prepend If true, prepend the base directory to the stack
     *                         instead of appending it; this causes it to be searched first rather
     *                         than last.
     * @return void
     */
    protected function addNamespace($prefix, $baseDir, $prepend = false)
    {
        // normalize namespace prefix
        //$prefix = trim($prefix, '\\') ;

        // normalize the base directory with a trailing separator
        $baseDir = rtrim(rtrim($baseDir, '/'), DIRECTORY_SEPARATOR) . '/';

        // initialize the namespace prefix array
        if (isset($this->prefixes[$prefix]) === false) {
            $this->prefixes[$prefix] = array();
        }

        // retain the base directory for the namespace prefix
        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $baseDir);
        } else {
            array_push($this->prefixes[$prefix], $baseDir);
        }
    }

    protected function validate($namespace,$file)
    {
        $spaces = $this->getNamespace($file);
        if (in_array($namespace, $spaces)) {
            return true;
        }

        return false;
    }

    protected function getNamespace($file)
    {
        $fp = fopen($file, 'r');
        $namespace = array();
        while (!feof($fp)) {
            $line = fgets($fp);
            if (stripos($line,'namespace')!==false) {
                $line = trim($line);
                $n = explode(" ", $line);
                $a = substr($n[1], 0,strpos($n[1],';'));
                array_push($namespace,$a);
            }
        }

        return $namespace;
    }

    /**
     * getFiles
     *
     * Scans the directory and returns the list of files
     * Also checks the directory exists or not
     *
     * @param   $namespace string      Namespace
     * @param   $path      string      Directory path of namespace
     *
     * @return mixed Returns list of files if directory is valid
     */
    protected function getFiles($namespace, $path)
    {
        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $fileName) {
                if (!is_dir($fileName)) {
                    $file = $path .'/'.$fileName;
                    if ($this->validate($namespace,$file)) {
                        $this->requireFile($file);
                    }

                }
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Loads the class file for a given class name.
     *
     * @param  string $class The fully-qualified class name.
     * @return mixed  The mapped file name on success, or boolean false on
     *                      failure.
     */

    protected function loadClass($className)
    {
        // the current namespace prefix
        $prefix = $className;

        // work backwards through the namespace names of the fully-qualified
        // class name to find a mapped file name
        while (false !== $pos = strrpos($prefix, '\\')) {

            // retain the trailing namespace separator in the prefix
            $prefix = substr($className, 0, $pos + 1);

            // the rest is the relative class name
            $relativeClass = substr($className, $pos + 1);

            // try to load a mapped file for the prefix and relative class
            $mappedFile = $this->loadMappedFile($prefix, $relativeClass);
            if ($mappedFile) {
                return $mappedFile;
            }

            // remove the trailing namespace separator for the next iteration
            // of strrpos()
            $prefix = rtrim($prefix, '\\');
        }

        // never found a mapped file
        return false;
    }

    /**
     * Load the mapped file for a namespace prefix and relative class.
     *
     * @param  string $prefix        The namespace prefix.
     * @param  string $relativeClass The relative class name.
     * @return mixed  Boolean false if no mapped file can be loaded, or the
     *                              name of the mapped file that was loaded.
     */
    protected function loadMappedFile($prefix, $relativeClass)
    {
        // are there any base directories for this namespace prefix?
        if (isset($this->prefixes[$prefix]) === false) {
            return false;
        }

        // look through base directories for this namespace prefix
        foreach ($this->prefixes[$prefix] as $baseDir) {

            // replace the namespace prefix with the base directory,
            // replace namespace separators with directory separators
            // in the relative class name, append with .php
            $file = $baseDir
                  . str_replace('\\', '/', $relativeClass)
                  . '.php';

            // if the mapped file exists, require it
            if ($this->requireFile($file)) {
                // yes, we're done
                return $file;
            }
        }

        // never found it
        return false;
    }

    /**
     * If a file exists, require it from the file system.
     *
     * @param  string $file The file to require.
     * @return bool   True if the file exists, false if not.
     */
    protected function requireFile($file)
    {
        if (file_exists($file)) {
            require $file;

            return true;
        }

        return false;
    }
}
