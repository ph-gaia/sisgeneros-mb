<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit5470b3d9c1802370f7b5691fc7e170d5
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symfony\\Component\\Process\\' => 26,
        ),
        'R' => 
        array (
            'Respect\\Validation\\' => 19,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Symfony\\Component\\Process\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/process',
        ),
        'Respect\\Validation\\' => 
        array (
            0 => __DIR__ . '/..' . '/respect/validation/library',
        ),
    );

    public static $prefixesPsr0 = array (
        'R' => 
        array (
            'Rah\\Danpu\\' => 
            array (
                0 => __DIR__ . '/..' . '/rah/danpu/src',
            ),
        ),
        'P' => 
        array (
            'PHPassLib' => 
            array (
                0 => __DIR__ . '/..' . '/rych/phpass/src',
            ),
        ),
        'K' => 
        array (
            'Knp\\Snappy' => 
            array (
                0 => __DIR__ . '/..' . '/knplabs/knp-snappy/src',
            ),
        ),
        'H' => 
        array (
            'HTR' => 
            array (
                0 => __DIR__ . '/../..' . '/vendor',
            ),
        ),
        'A' => 
        array (
            'App' => 
            array (
                0 => __DIR__ . '/../..' . '/',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit5470b3d9c1802370f7b5691fc7e170d5::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit5470b3d9c1802370f7b5691fc7e170d5::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit5470b3d9c1802370f7b5691fc7e170d5::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}