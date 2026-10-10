<?php

class PsMainmenuDirectAccessRunner
{
    public static function run($targetFile)
    {
        $markerFile = getenv('PS_MAINMENU_MARKER_FILE');
        $runner = dirname(__FILE__) . '/run_direct_access_child.php';
        $php = PHP_BINARY;
        $cmd = escapeshellarg($php) . ' -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 ' .
            escapeshellarg($runner) . ' ' . escapeshellarg($targetFile) . ' ' . escapeshellarg($markerFile);
        $descriptor = array(1 => array('pipe', 'w'), 2 => array('pipe', 'w'));
        $proc = proc_open($cmd, $descriptor, $pipes);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);

        return array('exit' => $exit, 'stdout' => $stdout, 'stderr' => $stderr);
    }

    public static function assertCleanChildOutput(array $result)
    {
        $diag = '/(Notice:|Warning:|Deprecated:|Strict Standards:|Parse error:|Fatal error:|Recoverable fatal error:)/';
        PHPUnit_Framework_Assert::assertSame(0, $result['exit']);
        PHPUnit_Framework_Assert::assertNotRegExp($diag, $result['stdout']);
        PHPUnit_Framework_Assert::assertNotRegExp($diag, $result['stderr']);
    }
}
