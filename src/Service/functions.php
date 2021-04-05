<?php

namespace Owl\Service;

use Owl\Service\Container;

function get($id)
{
    $args = func_get_args();
    $container = Container::getInstance();

    return call_user_func_array([$container, 'get'], $args);
}
