<?php

namespace Encore\LaraTrace\Commands;

class ContextNames extends AbstractCommand
{
    protected $command = 'context_names';

    protected $arguments = [
        '-d' => '0',
    ];

    public function response($xml)
    {
        $contexts = [];

        foreach ($xml->context as $context) {
            $contexts[(string) $context->attributes()->id] = (string) $context->attributes()->name;
        }

        return $contexts;
    }
}
