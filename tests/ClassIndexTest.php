<?php

namespace Spatie\WebTinker\Tests;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Spatie\WebTinker\Completion\ClassIndex;

class ClassIndexTest extends TestCase
{
    protected string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/web-tinker-'.bin2hex(random_bytes(8));
        File::makeDirectory($this->directory.'/vendor/composer', 0755, true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    #[Test]
    public function it_refreshes_the_index_when_caching_is_disabled(): void
    {
        $index = new ClassIndex($this->directory, 0);
        $this->assertNotContains('NewApplicationClass', $index->all());

        File::put($this->directory.'/vendor/composer/autoload_classmap.php', '<?php return ["NewApplicationClass" => "unused.php"];');

        $this->assertContains('NewApplicationClass', $index->all());
    }

    #[Test]
    public function it_refreshes_after_the_cache_expires_in_a_long_running_process(): void
    {
        $path = $this->directory.'/vendor/composer/autoload_classmap.php';
        File::put($path, '<?php return ["OriginalApplicationClass" => "unused.php"];');
        $mtime = filemtime($path);
        $index = new ClassIndex($this->directory, 10);
        $index->all();

        File::put($path, '<?php return ["NewApplicationClass" => "unused.php"];');
        touch($path, $mtime);
        $this->assertNotContains('NewApplicationClass', $index->all());

        $this->travel(11)->seconds();

        $this->assertContains('NewApplicationClass', $index->all());
        $this->assertNotContains('OriginalApplicationClass', $index->all());
    }
}
