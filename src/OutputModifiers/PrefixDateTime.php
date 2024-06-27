<?php

namespace Spatie\WebTinker\OutputModifiers;

class PrefixDateTime implements OutputModifier
{
    public function modify(string $output = ''): string
    {
        $output = htmlentities($output);

        return '<span class="text-dimmed">'.now()->format('Y-m-d H:i:s').'</span><br>'.$output;
    }
}
