<?php

namespace Encore\LaraTrace;

use Encore\LaraTrace\Commands\AbstractCommand;
use Encore\LaraTrace\Commands\ContextGet;
use Encore\LaraTrace\Commands\ContextNames;
use Encore\LaraTrace\Commands\Run;
use Encore\LaraTrace\Commands\SetBreakPoint;
use Encore\LaraTrace\Commands\Status;
use Encore\LaraTrace\Commands\StepInto;
use Illuminate\Support\Str;
use React\Socket\Connection;

class Api
{
    protected $server;

    protected $transactionId = 0;

    protected $breakPoints;

    protected $callback;

    public function __construct(Server $server)
    {
        $this->server = $server;

        $this->initBreakPoints();
    }

    protected function initBreakPoints()
    {
        $this->breakPoints = collect();
    }

    public function call($response)
    {
        if ($this->callback instanceof \Closure) {
            call_user_func($this->callback, $response);
        }
    }

    public function then(\Closure $callback)
    {
        $this->callback = $callback;

        return $this;
    }
    
    public function send(AbstractCommand $command)
    {
        foreach ($this->server->getConnections() as $connection) {
            $connection->write($this->formatCommand($command));
        }

        if (method_exists($command, 'response')) {
            $this->then(function ($response) use ($command) {
                return call_user_func([$command, 'response'], $response);
            });
        }

        return $this;
    }

    protected function getTransactionId()
    {
        return $this->transactionId++;
    }

    protected function formatCommand(AbstractCommand $command)
    {
        return $command->appendTransactionId($this->getTransactionId())->__toString();
    }

    public function status()
    {
        return $this->send(new Status());
    }

    public function stepInto()
    {
        return $this->send(new StepInto());
    }

    public function breakpointSet($file, $line, $expression = '')
    {
        $breakPoint = new SetBreakPoint($file, $line, $expression);

        $this->breakPoints->push($breakPoint);

        return $this->send($breakPoint);
    }

    public function applyBreakPoints(Connection $connection)
    {
        foreach ($this->breakPoints as $breakPoint) {
            $connection->write($this->formatCommand($breakPoint));
        }
    }

    public function run()
    {
        return $this->send(new Run());
    }

    public function contextNames()
    {
        return $this->send(new ContextNames());
    }

    public function contextGet()
    {
        return $this->send(new ContextGet());
    }

    public function dispatch($input)
    {
        $input = trim($input);

        if (empty($input)) {
            return;
        }

        $input = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->server->getOutput()->write(json_last_error_msg() . "\n");

            return;
        }

        $command = array_get($input, 'cmd');
        $args    = array_get($input, 'args', []);

        if (method_exists($this, $method = Str::camel($command))) {
            call_user_func_array([$this, $method], $args);
        }
    }

    public function parseResponse($response)
    {
        $results = [];

        foreach ($response as $xml) {
            $xml = simplexml_load_string('<?xml version="1.0" encoding="iso-8859-1"?>' . $xml, 'SimpleXMLElement');
            $json = json_encode($xml);
            $results[] = json_decode($json,true);
        }

        return $results;
    }
}
