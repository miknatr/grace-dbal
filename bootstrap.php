<?php

error_reporting(E_ALL | E_STRICT);
ini_set("display_errors", 1);

$namespaces = array(
    'Grace\\Tests\\SQLBuilder' => __DIR__ . '/lib',
    'Grace\\SQLBuilder'        => __DIR__ . '/lib',
    'Grace\\Tests\\DBAL'       => __DIR__ . '/lib',
    'Grace\\DBAL'              => __DIR__ . '/lib',
);

spl_autoload_register(function($className) use ($namespaces)
{
    $className = ltrim($className, '\\');

    foreach ($namespaces as $prefix => $dir) {
        if (strpos($className, $prefix) === 0) {
            $fileName  = $dir . DIRECTORY_SEPARATOR;

            if ($lastNsPos = strrpos($className, '\\')) {
                $namespace = substr($className, 0, $lastNsPos);
                $className = substr($className, $lastNsPos + 1);
                $fileName  .= str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
            }

            $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

            require $fileName;
        }
    }
});

if (file_exists(__DIR__ . '/config.php')) {
    /** @noinspection PhpIncludeInspection */
    require_once __DIR__ . '/config.php';
} else {
    require_once __DIR__ . '/config.php.dist';
}
