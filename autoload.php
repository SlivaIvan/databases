<?php

spl_autoload_register(function ($className) {
    // Определяем базовую директорию для поиска классов
    $baseDir = __DIR__ . '/src/';
    
    // Заменяем обратные слеши (для пространств имен) на прямые
    $className = str_replace('\\', '/', $className);
    
    // Формируем путь к файлу
    $file = $baseDir . $className . '.php';
    
    // Если файл существует - подключаем его
    if (file_exists($file)) {
        require $file;
        return;
    }
    
    // Если файл не найден, ищем рекурсивно во вложенных папках
    $directory = new RecursiveDirectoryIterator($baseDir);
    $iterator = new RecursiveIteratorIterator($directory);
    
    foreach ($iterator as $fileInfo) {
        if ($fileInfo->isFile() && $fileInfo->getFilename() === $className . '.php') {
            require $fileInfo->getPathname();
            return;
        }
    }
});