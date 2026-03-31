<?php

namespace Dashed\DashedMenus\Policies;

use Dashed\DashedCore\Policies\BaseResourcePolicy;

class MenuPolicy extends BaseResourcePolicy
{
    protected function resourceName(): string
    {
        return 'Menu';
    }
}
