<?php
/**
 * Created by PhpStorm.
 * User: song
 * Email: zousong@yiban.cn
 * Date: 17/3/7
 * Time: 下午1:57
 */

namespace Encore\LaraTrace\Commands;

class SetBreakPoint extends AbstractCommand
{
    protected $command = 'breakpoint_set';

    protected $arguments = [
        '-t' => 'line',
        '-s' => 'enabled',
        '-f' => '',
        '-n' => '',
    ];

    public function __construct($file, $line, $expression = '')
    {
        $this->argument('-f', $file);

        $this->argument('-n', $line);
    }
}
