<?php

namespace Encore\LaraTrace\Commands;

class Run extends AbstractCommand
{
    protected $command = 'run';

    public function response($response)
    {
        return $this->getXmlAttributes($response);
    }
}
