<?php

namespace Clue\StreamFilter;

function append($stream, $callback, $read_write = STREAM_FILTER_ALL)
{
    $ret = @\stream_filter_append($stream, register(), $read_write, $callback);

    // PHP 8 throws above on type errors, older PHP and memory issues can throw here
    // @codeCoverageIgnoreStart
    if ($ret === false) {
        $error = \error_get_last() + array('message' => '');
        throw new \RuntimeException('Unable to append filter: ' . $error['message']);
    }
    // @codeCoverageIgnoreEnd

    return $ret;
}

function prepend($stream, $callback, $read_write = STREAM_FILTER_ALL)
{
    $ret = @\stream_filter_prepend($stream, register(), $read_write, $callback);

    // PHP 8 throws above on type errors, older PHP and memory issues can throw here
    // @codeCoverageIgnoreStart
    if ($ret === false) {
        $error = \error_get_last() + array('message' => '');
        throw new \RuntimeException('Unable to prepend filter: ' . $error['message']);
    }
    // @codeCoverageIgnoreEnd

    return $ret;
}

function fun($filter, $parameters = null)
{
    $fp = \fopen('php://memory', 'w');
    if (\func_num_args() === 1) {
        $filter = @\stream_filter_append($fp, $filter, \STREAM_FILTER_WRITE);
    } else {
        $filter = @\stream_filter_append($fp, $filter, \STREAM_FILTER_WRITE, $parameters);
    }

    if ($filter === false) {
        \fclose($fp);
        $error = \error_get_last() + array('message' => '');
        throw new \RuntimeException('Unable to access built-in filter: ' . $error['message']);
    }

    // append filter function which buffers internally
    $buffer = '';
    append($fp, function ($chunk) use (&$buffer) {
        $buffer .= $chunk;

        // always return empty string in order to skip actually writing to stream resource
        return '';
    }, \STREAM_FILTER_WRITE);

    $closed = false;

    return function ($chunk = null) use ($fp, $filter, &$buffer, &$closed) {
        if ($closed) {
            throw new \RuntimeException('Unable to perform operation on closed stream');
        }
        if ($chunk === null) {
            $closed = true;
            $buffer = '';
            \fclose($fp);
            return $buffer;
        }
        // initialize buffer and invoke filters by attempting to write to stream
        $buffer = '';
        \fwrite($fp, $chunk);

        // buffer now contains everything the filter function returned
        return $buffer;
    };
}

function remove($filter)
{
    if (@\stream_filter_remove($filter) === false) {
        // PHP 8 throws above on type errors, older PHP and memory issues can throw here
        $error = \error_get_last();
        throw new \RuntimeException('Unable to remove filter: ' . $error['message']);
    }
}

function register()
{
    static $registered = null;
    if ($registered === null) {
        $registered = 'stream-callback';
        \stream_filter_register($registered, __NAMESPACE__ . '\CallbackFilter');
    }
    return $registered;
}
