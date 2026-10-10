<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

final class HexagonalArchitectureTest extends TestCase
{
    /**
     * Artículo I — El dominio no depende de nada
     */
    public function test_domain_layer_does_not_depend_on_framework_or_adapters(): void
    {
        $domainDir = __DIR__ . '/../../../app/Domain';
        $files = $this->getPhpFiles($domainDir);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $this->assertStringNotContainsString(
                'Illuminate',
                $content,
                "Domain file {$file} violates Constitution Article I by importing Illuminate."
            );
            $this->assertStringNotContainsString(
                'Adapters',
                $content,
                "Domain file {$file} violates Constitution Article I by importing Adapters."
            );
        }
    }

    /**
     * Artículo II — El núcleo declara sus puertos; el mundo los implementa
     */
    public function test_application_layer_does_not_import_adapters(): void
    {
        $appDir = __DIR__ . '/../../../app/Application';
        $files = $this->getPhpFiles($appDir);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $this->assertStringNotContainsString(
                'Adapters',
                $content,
                "Application file {$file} violates Constitution Article II by importing Adapters."
            );
        }
    }

    private function getPhpFiles(string $dir): array
    {
        $result = [];
        if (!is_dir($dir)) return $result;

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $result[] = $file->getPathname();
            }
        }
        return $result;
    }
}
