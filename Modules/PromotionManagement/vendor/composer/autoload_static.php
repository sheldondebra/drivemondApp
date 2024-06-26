<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit9e882800f1e7da925a2db3c0a40fed4b
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'Modules\\PromotionManagement\\' => 28,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Modules\\PromotionManagement\\' => 
        array (
            0 => __DIR__ . '/../..' . '/',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit9e882800f1e7da925a2db3c0a40fed4b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit9e882800f1e7da925a2db3c0a40fed4b::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit9e882800f1e7da925a2db3c0a40fed4b::$classMap;

        }, null, ClassLoader::class);
    }
}
