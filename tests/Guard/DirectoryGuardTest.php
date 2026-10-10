<?php

class DirectoryGuardTest extends PHPUnit_Framework_TestCase
{
    public function testEveryIndexPhpExitsWithoutDiagnostics()
    {
        $repoRoot = dirname(dirname(__DIR__)) . '/';
        $expected = array(
            'index.php',
            'tests/index.php',
            'tests/phpstan/index.php',
            'translations/index.php',
            'views/index.php',
            'views/templates/admin/_configure/helpers/form/index.php',
            'views/templates/admin/_configure/helpers/index.php',
            'views/templates/admin/_configure/helpers/list/index.php',
            'views/templates/admin/_configure/index.php',
            'views/templates/admin/index.php',
            'views/templates/index.php',
        );
        $found = array();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($repoRoot, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->getFilename() === 'index.php') {
                $found[] = substr($fileInfo->getPathname(), strlen($repoRoot));
            }
        }
        sort($found);
        $this->assertSame($expected, $found);

        $marker = tempnam(sys_get_temp_dir(), 'psmm_marker_');
        putenv('PS_MAINMENU_MARKER_FILE=' . $marker);
        foreach ($expected as $rel) {
            if (file_exists($marker)) {
                unlink($marker);
            }
            $result = PsMainmenuDirectAccessRunner::run($repoRoot . $rel);
            PsMainmenuDirectAccessRunner::assertCleanChildOutput($result);
            $this->assertFalse(file_exists($marker));
        }
        putenv('PS_MAINMENU_MARKER_FILE');
    }
}
