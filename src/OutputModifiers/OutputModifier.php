<?php

namespace Spatie\WebTinker\OutputModifiers;

interface OutputModifier
{
    public function modify(string $output = ''): string;
}
