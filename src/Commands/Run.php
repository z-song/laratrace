<?php

namespace Encore\LaraTrace\Commands;

class Run extends AbstractCommand
{
    protected $command = 'run';

    public function response($response)
    {
        $xml = $response[0];

        $attributes = $this->getXmlAttributes($xml);

        dump($attributes);
    }
}
