<?php

namespace Encore\LaraTrace\Commands;

class ContextGet extends AbstractCommand
{
    protected $command = 'context_get';

    protected $arguments = [
        '-d' => '0',
        '-c' => '0',
    ];

    public function response($response)
    {
        $xml = $response[0];

        $xml = simplexml_load_string($xml);

        foreach ($xml->property as $element) {

            if ($variable = $this->getAttributes($element)) {
                //dump($variable);
            }
        }

    }

    protected function getAttributes($element)
    {
        if ((string)$element->attributes()->type == 'int') {
            return ['name' => (string)$element->attributes()->name, 'value' => (int) (string) $element];
        }

        if ((string)$element->attributes()->type == 'float') {
            return ['name' => (string)$element->attributes()->name, 'value' => (float) (string) $element];
        }

        if ((string)$element->attributes()->type == 'string') {
            return ['name' => (string)$element->attributes()->name, 'value' => base64_decode((string) $element)];
        }

        if ((string)$element->attributes()->type == 'array') {
            $retval = ['name' => (string)$element->attributes()->name];

            foreach ($element->property as $item) {
                $retval['value'][(string)$item->attributes()->name] = $this->getAttributes($item);
            }

            return $retval;
        }
    }
}
