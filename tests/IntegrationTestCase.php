<?php
namespace Civi\CompilePlugin\Tests;

use Civi\CompilePlugin\Util\EnvHelper;
use ProcessHelper\ProcessHelper as PH;

class IntegrationTestCase extends \PHPUnit\Framework\TestCase
{

    public static function getComposerJson()
    {
        return [
          'authors' => [
            [
              'name' => 'Tester McFakus',
              'email' => 'tester@example.org',
            ],
          ],

          'repositories' => [
            'composer-compile-plugin' => [
              'type' => 'path',
              'url' => self::getPluginSourceDir(),
            ],
            'test-cherry-yogurt' => [
              'type' => 'path',
              'url' => self::getPluginSourceDir() . '/tests/pkgs/cherry-yogurt',
            ],
            'test-cherry-jam' => [
              'type' => 'path',
              'url' => self::getPluginSourceDir() . '/tests/pkgs/cherry-jam',
            ],
            'test-strawberry-jam' => [
              'type' => 'path',
              'url' => self::getPluginSourceDir() . '/tests/pkgs/strawberry-jam',
            ],
            'test-scss-method' => [
              'type' => 'path',
              'url' => self::getPluginSourceDir() . '/tests/pkgs/scss-method',
              'options' => [
                'symlink' => false,
              ],
            ],
            'test-scss-script' => [
              'type' => 'path',
              'url' => self::getPluginSourceDir() . '/tests/pkgs/scss-script',
              'options' => [
                'symlink' => false,
              ],
            ],
            'test-rosti' => [
              'type' => 'path',
              'url' => self::getPluginSourceDir() . '/tests/pkgs/rosti',
              'options' => [
                'symlink' => false,
              ],
            ],
          ],
        ];
    }

    /**
     * @return string
     *   The root folder of the composer-compile-plugin.
     */
    public static function getPluginSourceDir()
    {
        return dirname(__DIR__);
    }

    /**
     * @return string
     *   The path of the autogenerated composer project.
     */
    public static function getTestDir()
    {
        return self::$testDir;
    }

    private static $origDir;
    private static $testDir;
    private static $origEnv;

    /**
     * Create a temp folder with a "composer.json" file and chdir() into it.
     *
     * @param array $composerJson
     * @return string
     */
    public static function initTestProject($composerJson)
    {
        self::$origDir = getcwd();
        self::$origEnv = EnvHelper::getAll();
        if (getenv('USE_TEST_PROJECT')) {
            self::$testDir = rtrim(getenv('USE_TEST_PROJECT'), DIRECTORY_SEPARATOR);
            @unlink(self::$testDir . DIRECTORY_SEPARATOR . 'composer.lock');
        } else {
            self::$testDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'compileplg-' . md5(__DIR__ . time() . rand(
                0,
                10000
            ));
            self::cleanDir(self::$testDir);
        }

        if (!is_dir(self::$testDir)) {
            mkdir(self::$testDir);
        }
        file_put_contents(
            self::$testDir . DIRECTORY_SEPARATOR . 'composer.json',
            json_encode(
                $composerJson,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );
        chdir(self::$testDir);
        return self::$testDir;
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        if (self::$testDir) {
            chdir(self::$origDir);
            self::$origDir = null;

            if (getenv('USE_TEST_PROJECT')) {
                fwrite(
                    STDERR,
                    sprintf(
                        "\n\nTest project location (%s): %s\n",
                        static::CLASS,
                        self::$testDir
                    )
                );
            } else {
                self::cleanDir(self::$testDir);
            }
            self::$testDir = null;
        }

        EnvHelper::setAll(self::$origEnv);
    }

    /**
     * If a directory exists, remove it.
     *
     * @param string $dir
     */
    protected static function cleanDir($dir)
    {
        PH::runOk(['if [ -d @DIR ]; then rm -rf @DIR ; fi', 'DIR' => $dir]);
    }

    protected static function cleanFile($file)
    {
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * List of env-vars, as they existed at the start of the test-case.
     *
     * @var array
     */
    private $origEnvTestCase;

    protected function setUp()
    {
        $this->origEnvTestCase = EnvHelper::getAll();
        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();
        EnvHelper::setAll($this->origEnvTestCase);
    }


    public function assertSameFileContent($expected, $actual)
    {
        $this->assertEquals(
            file_get_contents($expected),
            file_get_contents($actual)
        );
    }

    public function assertFileContent($file, $content, $message = null)
    {
        if ($content === null) {
            $this->assertFileNotExists(
                $file,
                "($message) File should not exist"
            );
        } else {
            $this->assertFileExists($file, "($message) File should exist");
            $this->assertEquals(
                $content,
                file_get_contents($file),
                "($message) File should match given content"
            );
        }
    }

    public function assertFileIsSymlink($path)
    {
        $this->assertTrue(
            file_exists($path),
            "Path ($path) should exist (symlink file)"
        );
        $this->assertTrue(is_link($path), "Path ($path) should be a symlink");

        $linkTgt = readlink($path);
        $this->assertTrue(is_string($linkTgt));
        $this->assertTrue(
            is_file(dirname($path) . '/' . $linkTgt),
            "Path ($path) should be symlinking pointing to a file. Found tgt ($linkTgt)"
        );
    }

    public function assertFileIsNormal($path)
    {
        $this->assertTrue(
            file_exists($path),
            "Path ($path) should exist (normal file)"
        );
        $this->assertTrue(
            is_file($path),
            "Path ($path) should be a normal file"
        );
        $this->assertTrue(
            !is_link($path),
            "Path ($path) should not be a symlink"
        );
    }

    public function assertDirIsSymlink($path)
    {
        $this->assertTrue(
            file_exists($path),
            "Path ($path) should exist (symlink dir)"
        );
        $this->assertTrue(is_link($path), "Path ($path) should be a symlink");

        $linkTgt = readlink($path);
        $this->assertTrue(is_string($linkTgt));
        $this->assertTrue(
            is_dir(dirname($path) . '/' . $linkTgt),
            "Path ($path) should be symlinking pointing to a dir. Found tgt ($linkTgt"
        );
    }

    public function assertDirIsNormal($path)
    {
        $this->assertTrue(
            file_exists($path),
            "Path ($path) should exist (normal dir)"
        );
        $this->assertTrue(
            !is_link($path),
            "Path ($path) should not be a symlink"
        );
        $this->assertTrue(is_dir($path), "Path ($path) should be a dir");
    }

    /**
     * @param array $expectLines
     *    Lines that should be present in output.
     * @param string $outputFilter
     *   A regexp to identify output lines that are interesting.
     * @param string $actualOutput
     *   The full command output
     */
    public function assertOutputLines($expectLines, $outputFilter, $actualOutput)
    {
        $actualLines = array_values(preg_grep(
            $outputFilter,
            explode("\n", $actualOutput)
        ));

        $serialize = print_r([
          'expect' => $expectLines,
          'actual' => $actualLines
        ], 1);

        $this->assertEquals(
            count($expectLines),
            count($actualLines),
            "Compare line count in $serialize"
        );
        foreach ($expectLines as $offset => $expectLine) {
            $this->assertRegExp(
                ";$expectLine;",
                $actualLines[$offset],
                "Check line $offset in $serialize"
            );
        }
    }
}
