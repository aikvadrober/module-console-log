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

            if (!is_array($payload)) {
                return;
            }

            // one physical log line per captured console call, chronological order
            usort($payload, static function (array $a, array $b): int {
                return ((int)($a['time'] ?? 0)) <=> ((int)($b['time'] ?? 0));
            });

            foreach ($payload as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $this->logger->info($this->formatEntry($entry));
            }
        } catch (\Throwable $e) {
            $this->logger->debug($e->getMessage(), ['exception' => $e]);
        }
    }

    /**
     * Format a single console entry as "HH:MM:SS.mmm [LEVEL] arg1 arg2 ...".
     */
    private function formatEntry(array $entry): string
    {
        $timeMs = (int)($entry['time'] ?? 0);
        $time = $timeMs > 0
            ? date('H:i:s', (int)($timeMs / 1000)) . '.' . sprintf('%03d', $timeMs % 1000)
            : '00:00:00.000';
        $level = strtoupper((string)($entry['level'] ?? 'log'));
        $parts = array_map([$this, 'formatArgument'], (array)($entry['data'] ?? []));

        return sprintf('%s [%s] %s', $time, $level, implode(' ', $parts));
    }

    /**
     * Render one console argument: strings inline (newlines escaped),
     * everything else as JSON.
     */
    private function formatArgument($value): string
    {
        if (is_string($value)) {
            return str_replace(["\r\n", "\r", "\n"], '\n ', $value);
        }
        if (is_bool($value) || $value === null) {
            return var_export($value, true);
        }
        if (is_scalar($value)) {
            return (string)$value;
        }
        $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json === false ? print_r($value, true) : $json;
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
