<?php
use Spatie\WebTinker\Tinker;

beforeEach(function () {
    $this->tinker = app(Tinker::class);
});

it('removes c style single line comments', function () {
    $cleanCode = $this->tinker->removeComments('// This is a comment ');
    expect($cleanCode)->toEqual('');

    $cleanCode = $this->tinker->removeComments('$user = \'Test\'; // This is a comment ');
    expect($cleanCode)->toEqual('$user = \'Test\'; ');
});
it('removes shell style single line comments', function() {
    $cleanCode = $this->tinker->removeComments('# This is a comment ');
    expect($cleanCode)->toEqual('');

    $cleanCode = $this->tinker->removeComments('$user = \'Test\'; # This is a comment ');
    expect($cleanCode)->toEqual('$user = \'Test\'; ');
});

it('removes php multi line comments', function (){

    $cleanCode = $this->tinker->removeComments("/* This is a multi line comment \n yet another line of comment */");
    expect($cleanCode)->toEqual('');

    $cleanCode = $this->tinker->removeComments(
        "\$user = 'Test User';/* This is a multi line comment \n".
        ' yet another line of comment */'
    );
    expect($cleanCode)->toEqual('$user = \'Test User\';');

    $cleanCode = $this->tinker->removeComments(
        "\$user = /* This is a multi line comment \n".
        " yet another line of comment */'Test User';"
    );
    expect($cleanCode)->toEqual('$user = \'Test User\';');
});
it('removes comments with multiline code input',function(){

    $cleanCode = $this->tinker->removeComments(
        "\$user_id = 1; /* This is a multi line comment \n yet another line of comment */\n".
        '\App\User::find($user_id);'
    );
    expect($cleanCode)->toEqual("\$user_id = 1; \n\\App\\User::find(\$user_id);");

    $cleanCode = $this->tinker->removeComments(
        "\$newUsers = User::latest()->take(3)->get();\n\n// \$allUsers = User::all();"
    );
    expect($cleanCode)->toEqual("\$newUsers = User::latest()->take(3)->get();\n\n");

    $cleanCode = $this->tinker->removeComments(
        "\$newUsers = User::latest()->take(3)->get();\n\n# \$allUsers = User::all();"
    );
    expect($cleanCode)->toEqual("\$newUsers = User::latest()->take(3)->get();\n\n");

});
