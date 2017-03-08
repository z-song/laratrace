<?php
/**
 * Created by PhpStorm.
 * User: song
 * Email: zousong@yiban.cn
 * Date: 17/3/7
 * Time: 下午1:18
 */

namespace Encore\LaraTrace\Commands;

abstract class AbstractCommand
{
    const CMD_EOL = "\000";

    protected $command;

    protected $arguments = [];

    protected function getCommand()
    {
        return $this->command;
    }

    protected function argument($arg, $value = null)
    {
        if (func_num_args() == 1) {
            return array_get($this->arguments, $arg);
        }

        $this->arguments[$arg] = $value;

        return $this;
    }

    protected function formatArguments()
    {
        $arguments = [];

        foreach ($this->arguments as $argument => $value) {
            $arguments[] = "$argument $value";
        }

        return implode(' ', $arguments);
    }

    public function response($response)
    {
        return $response;
    }

    public function setTransactionId($transactionId)
    {
        $this->argument('-i', $transactionId);

        return $this;
    }

    public function getXmlAttributes($element)
    {
        if (is_string($element)) {
            $element = simplexml_load_string($element);
        }

        $result = [];

        foreach ($element->attributes() as $attribute => $value) {
            $result[$attribute] = (string) $value;
        }

        return $result;
    }

    public function __toString()
    {
        return sprintf(
            "%s %s%s",
            $this->getCommand(),
            $this->formatArguments(),
            static::CMD_EOL
        );
    }
}
