<?php declare(strict_types=1);

namespace AiKvadrober\ConsoleLog\Controller\Logger;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class Save extends Action implements CsrfAwareActionInterface
{
    public function __construct(
        Context $context,
        private readonly LoggerInterface $logger,
        private readonly SerializerInterface $serializer
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $rawContent = $this->getRequest()->getContent();
            $payload = $this->serializer->unserialize($rawContent);

            $this->logger->info('[FRONTEND]', ['payload' => $payload]);
        } catch (\Throwable $e) {
            $this->logger->debug($e->getMessage(), ['exception' => $e]);
        }
    }

    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
