<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitfe3f091f921efcf7c30c3fea8f879e3b
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Psr\\Log\\' => 8,
        ),
        'D' => 
        array (
            'DVDoug\\BoxPacker\\' => 17,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'DVDoug\\BoxPacker\\' => 
        array (
            0 => __DIR__ . '/..' . '/dvdoug/boxpacker/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitfe3f091f921efcf7c30c3fea8f879e3b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitfe3f091f921efcf7c30c3fea8f879e3b::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}