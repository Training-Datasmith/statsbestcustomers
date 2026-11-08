<?php

class DirectoryGuardTest extends PHPUnit_Framework_TestCase
{
    public function guardFileProvider()
    {
        $root = dirname(dirname(dirname(__FILE__)));

        return array(
            array($root . '/index.php'),
            array($root . '/tests/index.php'),
            array($root . '/tests/phpstan/index.php'),
            array($root . '/translations/index.php'),
            array($root . '/upgrade/index.php'),
        );
    }

    /**
     * @dataProvider guardFileProvider
     */
    public function testGuardExitsCleanly($path)
    {
        $this->assertFileExists($path);

        $wrapper = tempnam(sys_get_temp_dir(), 'guard');
        $php = escapeshellarg(PHP_BINARY);
        $guard = var_export($path, true);
        $code = '<?php error_reporting(-1); ini_set("display_errors", "1"); require ' . $guard . '; echo "SENTINEL_SHOULD_NOT_RUN";';
        file_put_contents($wrapper, $code);

        $descriptorSpec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $process = proc_open($php . ' -d error_reporting=-1 -d display_errors=1 ' . escapeshellarg($wrapper), $descriptorSpec, $pipes);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        unlink($wrapper);

        $this->assertSame(0, $exitCode);
        $this->assertSame('', $stdout);
        $this->assertNotContains('SENTINEL_SHOULD_NOT_RUN', $stdout . $stderr);
        $combined = $stdout . $stderr;
        $this->assertNotRegExp(
            '/^(PHP )?(Notice|Warning|Deprecated|Strict Standards|Parse error|Fatal error):/m',
            $combined
        );
    }
}
