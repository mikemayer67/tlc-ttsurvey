<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit19559defb6e3648a60cf99e25ec9a8ad
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInit19559defb6e3648a60cf99e25ec9a8ad', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit19559defb6e3648a60cf99e25ec9a8ad', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit19559defb6e3648a60cf99e25ec9a8ad::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
