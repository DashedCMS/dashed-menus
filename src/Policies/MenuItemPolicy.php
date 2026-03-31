<?php

namespace Dashed\DashedMenus\Policies;

use Dashed\DashedCore\Policies\BaseResourcePolicy;

class MenuItemPolicy extends BaseResourcePolicy
{
    protected function resourceName(): string
    {
        return 'MenuItem';
    }
}
