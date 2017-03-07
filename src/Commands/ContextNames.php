<?php

namespace Encore\LaraTrace\Commands;

class ContextNames extends AbstractCommand
{
    protected $command = 'context_names';

    protected $arguments = [
        '-d' => '0',
    ];
}
