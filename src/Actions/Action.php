<?php

declare(strict_types=1);

namespace Baron\Recombee\Actions;

use Baron\Recombee\Builder;
use Recombee\RecommApi\Exceptions\ApiTimeoutException;

abstract class Action
{
    protected array $defaultOptions = [];

    public function __construct(protected Builder $builder)
    {
        $this->builder = $builder;
        $this->setUp();
    }

    protected function setUp(): void
    {
    }

    /**
     * Whether a request that times out may be safely retried.
     * Only read-only actions should enable this.
     */
    protected bool $retryOnTimeout = false;

    protected function query(): mixed
    {
        $request = $this->buildApiRequest();

        $timeout = $this->builder->param('timeout') ?? config('recombee.timeout');
        if (! empty($timeout)) {
            $request->setTimeout((int) $timeout);
        }

        $retries = $this->retryOnTimeout
            ? max(0, (int) ($this->builder->param('retries') ?? config('recombee.retries', 0)))
            : 0;

        $client = $this->builder->engine()->client();

        for ($attempt = 0; ; $attempt++) {
            try {
                return $client->send($request);
            } catch (ApiTimeoutException $e) {
                if ($attempt >= $retries) {
                    throw $e;
                }
            }
        }
    }

    protected function mapAsBoolean($response): bool
    {
        return $response === 'ok' ? true : false;
    }

    abstract protected function execute();

    abstract protected function buildApiRequest();
}
