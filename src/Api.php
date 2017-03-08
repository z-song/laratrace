<?php

namespace Encore\LaraTrace;

use Encore\LaraTrace\Commands\AbstractCommand;
use Encore\LaraTrace\Commands\ContextGet;
use Encore\LaraTrace\Commands\ContextNames;
use Encore\LaraTrace\Commands\Init;
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

    protected $commandQueue = [];

    public function __construct(Server $server)
    {
        $this->server = $server;

        $this->initBreakPoints();
        $this->initCommandQueue();
    }

    protected function initBreakPoints()
    {
        $this->breakPoints = collect();
    }

    protected function initCommandQueue()
    {
        array_push($this->commandQueue, new Init());
    }

    public function setResponse($xml)
    {
        $command = array_shift($this->commandQueue);

        if ($command instanceof AbstractCommand) {
            $result = $command->response(simplexml_load_string($xml));

            dump($result);
        }
    }

    public function send(AbstractCommand $command)
    {
        array_push($this->commandQueue, $command);

        foreach ($this->server->getConnections() as $connection) {
            $connection->write($this->formatCommand($command));
        }

        return $this;
    }

    private function getTransactionId()
    {
        return $this->transactionId++;
    }

    protected function formatCommand(AbstractCommand $command)
    {
        return $command->setTransactionId($this->getTransactionId())->__toString();
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

    public function contextGet($contextId = 0, $depth = 0)
    {
        return $this->send(new ContextGet($contextId, $depth));
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
