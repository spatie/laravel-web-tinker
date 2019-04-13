<?php

namespace Spatie\WebTinker\Tests;

use Spatie\WebTinker\Tinker;

class TinkerTest extends TestCase
{
    /** @var \Spatie\WebTinker\Tinker */
    private $tinker;

    public function setUp(): void
    {
        parent::setUp();
        $this->tinker = new Tinker();
    }

    /** @test */
    public function it_removes_c_style_single_line_comments()
    {
        $cleanCode = $this->tinker->removeComments('// This is a comment ');
        $this->assertSame('', $cleanCode);

        $cleanCode = $this->tinker->removeComments('$user = \'Test\'; // This is a comment ');
        $this->assertSame('$user = \'Test\'; ', $cleanCode);
    }

    /** @test */
    public function it_removes_shell_style_single_line_comments()
    {
        $cleanCode = $this->tinker->removeComments('# This is a comment ');
        $this->assertSame('', $cleanCode);

        $cleanCode = $this->tinker->removeComments('$user = \'Test\'; # This is a comment ');
        $this->assertSame('$user = \'Test\'; ', $cleanCode);
    }

    /** @test */
    public function it_removes_php_multi_line_comments()
    {
        $cleanCode = $this->tinker->removeComments("/* This is a multi line comment \n yet another line of comment */");
        $this->assertSame('', $cleanCode);

        $cleanCode = $this->tinker->removeComments(
            "\$user = 'Test User';/* This is a multi line comment \n".
            ' yet another line of comment */'
        );
        $this->assertSame('$user = \'Test User\';', $cleanCode);

        $cleanCode = $this->tinker->removeComments(
            "\$user = /* This is a multi line comment \n".
            " yet another line of comment */'Test User';"
        );
        $this->assertSame('$user = \'Test User\';', $cleanCode);
    }

    /** @test */
    public function it_removes_comments_with_multiline_code_input()
    {
        $cleanCode = $this->tinker->removeComments(
            "\$user_id = 1; /* This is a multi line comment \n yet another line of comment */\n".
            '\App\User::find($user_id);'
        );
        $this->assertSame("\$user_id = 1; \n\\App\\User::find(\$user_id);", $cleanCode);

        $cleanCode = $this->tinker->removeComments(
            "\$newUsers = User::latest()->take(3)->get();\n\n// \$allUsers = User::all();"
        );
        $this->assertSame("\$newUsers = User::latest()->take(3)->get();\n\n", $cleanCode);

        $cleanCode = $this->tinker->removeComments(
            "\$newUsers = User::latest()->take(3)->get();\n\n# \$allUsers = User::all();"
        );
        $this->assertSame("\$newUsers = User::latest()->take(3)->get();\n\n", $cleanCode);
    }
}
