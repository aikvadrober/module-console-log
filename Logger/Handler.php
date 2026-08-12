<?php declare(strict_types=1);

namespace AiKvadrober\ConsoleLog\Logger;

use Magento\Framework\Logger\Handler\Base;

class Handler extends Base
{
    protected $fileName = '/var/log/frontend-console.log';
}
