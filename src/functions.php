<?php
namespace Owl\Service;

function get($id)
{
    $args = func_get_args();
    $container = \Owl\Service\Container::getInstance();

    return call_user_func_array([$container, 'get'], $args);
}
