<?php

class SuiteManifestTest extends PHPUnit_Framework_TestCase
{
    public function testHardCodedFileListAndTestCount()
    {
        $manifest = require dirname(__DIR__) . '/expected-suite.php';
        $root = dirname(__DIR__) . '/';
        $files = array();
        foreach (glob(dirname(__DIR__) . '/*Test.php') as $path) {
            $files[] = 'tests/' . basename($path);
        }
        foreach (glob(dirname(__DIR__) . '/*/*Test.php') as $path) {
            $files[] = 'tests/' . basename(dirname($path)) . '/' . basename($path);
        }
        sort($files);
        $this->assertSame($manifest['files'], $files);

        $count = 0;
        foreach ($manifest['files'] as $file) {
            $src = file_get_contents($root . str_replace('tests/', '', $file));
            if (preg_match_all('/function\s+(test[A-Za-z0-9_]*)\s*\(/', $src, $matches)) {
                $count += count($matches[1]);
            }
        }
        $this->assertSame($manifest['test_count'], $count);
    }
}
