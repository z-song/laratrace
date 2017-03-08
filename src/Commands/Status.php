<?php

namespace Encore\LaraTrace\Commands;

class Status extends AbstractCommand
{
    protected $command = 'status';

    public function response($response)
    {
        return $this->getXmlAttributes($response);
    }
}
