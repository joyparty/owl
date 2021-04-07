<?php

namespace Owl;

use RuntimeException;
use UnexpectedValueException;

/**
 * @param mixed $string
 *
 * @return bool
 */
function str_has_tags($string): bool
{
    if (is_string($string) && strlen($string) > 2) {
        return $string !== strip_tags($string);
    }

    return false;
}

function array_set_in(array &$target, array $path, $value, $push = false)
{
    $last_key = array_pop($path);

    foreach ($path as $key) {
        if (!array_key_exists($key, $target)) {
            $target[$key] = [];
        }

        $target = &$target[$key];

        if (!is_array($target)) {
            throw new RuntimeException('Cannot use a scalar value as an array');
        }
    }

    if ($push) {
        if (!array_key_exists($last_key, $target)) {
            $target[$last_key] = [];
        } elseif (!is_array($target[$last_key])) {
            throw new RuntimeException('Cannot use a scalar value as an array');
        }

        array_push($target[$last_key], $value);
    } else {
        $target[$last_key] = $value;
    }
}

/**
 * difference between 'set in' and 'push in':
 *  set in:
 *      $target[$path] = $value;.
 *  push in:
 *      $target[$path][] = $value;
 *
 * @param array $target
 * @param array $path
 * @param mixed $value
 */
function array_push_in(array &$target, array $path, $value)
{
    array_set_in($target, $path, $value, true);
}

function array_get_in(array $target, array $path)
{
    foreach ($path as $key) {
        if (!isset($target[$key])) {
            return false;
        }

        $target = &$target[$key];
    }

    return $target;
}

function array_unset_in(array &$target, array $path)
{
    $last_key = array_pop($path);

    foreach ($path as $key) {
        if (!is_array($target)) {
            return;
        }

        if (!array_key_exists($key, $target)) {
            return;
        }

        $target = &$target[$key];
    }

    unset($target[$last_key]);
}

/**
 * @example
 *  $value = [
 *      'a' => [
 *          'b' => [],
 *      ],
 *      'c' => [
 *          'd' => [
 *              'e' => 1,
 *          ],
 *      ],
 *  ];
 *  $result = \Owl\array_trim($value);
 *  // the following expression is true
 *  $result == [
 *      'c' => [
 *          'd' => [
 *              'e' => 1,
 *          ],
 *      ],
 *  ]);
 *
 * @param array $target
 *
 * @return array
 */
function array_trim(array $target): array
{
    $keys = array_keys($target);
    $is_array = ($keys === array_keys($keys));

    $result = [];

    foreach ($target as $key => $value) {
        if (is_array($value) && $value) {
            $value = array_trim($value);
        }

        if ($value === null || $value === '' || $value === []) {
            continue;
        }

        $result[$key] = $value;
    }

    if ($is_array && $result) {
        $result = array_values($result);
    }

    return $result;
}

function safe_json_encode($value, $options = 0, $depth = 512)
{
    $value = json_encode($value, $options, $depth);

    if ($value === false && json_last_error() !== JSON_ERROR_NONE) {
        throw new UnexpectedValueException(json_last_error_msg(), json_last_error());
    }

    return $value;
}

function safe_json_decode($json, $assoc = false, $depth = 512, $options = 0)
{
    $value = json_decode($json, $assoc, $depth, $options);

    if ($value === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new UnexpectedValueException(json_last_error_msg(), json_last_error());
    }

    return $value;
}
