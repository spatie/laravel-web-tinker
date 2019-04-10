<?php

namespace Spatie\WebTinker\Tests;

use Spatie\WebTinker\Tinker;

class TinkerTest extends TestCase
{
    /** @test */
    public function remove_c_style_single_line_comments()
    {
        $tinker = new Tinker();

        // Only comment
        $cleanCode = $tinker->removeComments('// This is a comment ');
        $this->assertSame('', $cleanCode);

        // Comment at the end of line of code
        $cleanCode = $tinker->removeComments('$user = \'Test\'; // This is a comment ');
        $this->assertSame('$user = \'Test\'; ', $cleanCode);
    }

    /** @test */
    public function remove_shell_style_single_line_comments()
    {
        $tinker = new Tinker();

        // Only comment
        $cleanCode = $tinker->removeComments('# This is a comment ');
        $this->assertSame('', $cleanCode);

        // Comment at the end of line of code
        $cleanCode = $tinker->removeComments('$user = \'Test\'; # This is a comment ');
        $this->assertSame('$user = \'Test\'; ', $cleanCode);
    }

    /** @test */
    public function remove_php_multi_line_comments()
    {
        $tinker = new Tinker();

        // Only comment
        $cleanCode = $tinker->removeComments("/* This is a multi line comment \n yet another line of comment */");
        $this->assertSame('', $cleanCode);

        // Multi line comment at the end of a line of code
        $cleanCode = $tinker->removeComments(
            "\$user = 'Test User';/* This is a multi line comment \n".
            ' yet another line of comment */'
        );
        $this->assertSame('$user = \'Test User\';', $cleanCode);

        // Inline Multi line comment
        $cleanCode = $tinker->removeComments(
            "\$user = /* This is a multi line comment \n".
            " yet another line of comment */'Test User';"
        );
        $this->assertSame('$user = \'Test User\';', $cleanCode);
    }

    /** @test */
    public function remove_comments_with_multiline_code_input()
    {
        $tinker = new Tinker();

        // Multi line comment in between lines of code
        $cleanCode = $tinker->removeComments(
            "\$user_id = 1; /* This is a multi line comment \n yet another line of comment */\n".
            '\App\User::find($user_id);'
        );
        $this->assertSame("\$user_id = 1; \n\\App\\User::find(\$user_id);", $cleanCode);

        // C Style commented line of code
        $cleanCode = $tinker->removeComments(
            "\$newUsers = User::latest()->take(3)->get();\n\n// \$allUsers = User::all();"
        );
        $this->assertSame("\$newUsers = User::latest()->take(3)->get();\n\n", $cleanCode);

        // Shell Style commented line of code
        $cleanCode = $tinker->removeComments(
            "\$newUsers = User::latest()->take(3)->get();\n\n# \$allUsers = User::all();"
        );
        $this->assertSame("\$newUsers = User::latest()->take(3)->get();\n\n", $cleanCode);
    }
}
