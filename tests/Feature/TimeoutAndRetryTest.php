<?php

use Baron\Recombee\Facades\Recombee;
use Recombee\RecommApi\Client;
use Recombee\RecommApi\Exceptions\ApiTimeoutException;
use Recombee\RecommApi\Requests\RecommendItemsToUser;
use Recombee\RecommApi\Requests\SetViewPortion;

// Return raw API collections rather than converting ids to local app models
beforeEach(fn () => config()->set('recombee.response', 'api'));

function timeoutException($request = null): ApiTimeoutException
{
    return new ApiTimeoutException(
        $request ?? new RecommendItemsToUser('1', 25),
        new \Exception('Client did not get response within 3000 ms')
    );
}

/** First call times out, the next one returns $response */
function timeoutThenReturn(array $response): Closure
{
    $calls = 0;

    return function () use (&$calls, $response) {
        if ($calls++ === 0) {
            throw timeoutException();
        }

        return $response;
    };
}

it('uses the sdk default timeout when none is configured', function () {
    $this->mock(Client::class)
        ->shouldReceive('send')
        ->once()
        ->withArgs(fn ($request) => $request->getTimeout() === 3000)
        ->andReturn(['recomms' => []]);

    Recombee::user(1)->recommendItems()->get();
});

it('applies the configured timeout', function () {
    config()->set('recombee.timeout', 1500);

    $this->mock(Client::class)
        ->shouldReceive('send')
        ->once()
        ->withArgs(fn ($request) => $request->getTimeout() === 1500)
        ->andReturn(['recomms' => []]);

    Recombee::user(1)->recommendItems()->get();
});

it('lets a per-call timeout override the configured one', function () {
    config()->set('recombee.timeout', 1500);

    $this->mock(Client::class)
        ->shouldReceive('send')
        ->once()
        ->withArgs(fn ($request) => $request->getTimeout() === 800)
        ->andReturn(['recomms' => []]);

    Recombee::user(1)->recommendItems()->timeout(800)->get();
});

it('does not retry timeouts by default', function () {
    $this->mock(Client::class)
        ->shouldReceive('send')
        ->once()
        ->andThrow(timeoutException());

    Recombee::user(1)->recommendItems()->get();
})->throws(ApiTimeoutException::class);

it('retries recommendations that time out when configured', function () {
    config()->set('recombee.retries', 1);
    $items = [['id' => '111']];

    $this->mock(Client::class)
        ->shouldReceive('send')
        ->twice()
        ->andReturnUsing(timeoutThenReturn(['recomms' => $items]));

    $results = Recombee::user(1)->recommendItems()->get();

    expect($results->collection->all())->toEqual($items);
});

it('retries segment recommendations that time out', function () {
    $items = [['id' => '111']];

    $this->mock(Client::class)
        ->shouldReceive('send')
        ->twice()
        ->andReturnUsing(timeoutThenReturn(['recomms' => $items]));

    $results = Recombee::recommendItemsForSegment('episodes')->retries(1)->get();

    expect($results->collection->all())->toEqual($items);
});

it('gives up after the configured number of retries', function () {
    config()->set('recombee.retries', 2);

    $this->mock(Client::class)
        ->shouldReceive('send')
        ->times(3)
        ->andThrow(timeoutException());

    Recombee::user(1)->recommendItems()->get();
})->throws(ApiTimeoutException::class);

it('does not retry errors other than timeouts', function () {
    config()->set('recombee.retries', 2);

    $this->mock(Client::class)
        ->shouldReceive('send')
        ->once()
        ->andThrow(new \RuntimeException('Not found', 404));

    Recombee::user(1)->recommendItems()->get();
})->throws(\RuntimeException::class);

it('never retries write requests', function () {
    config()->set('recombee.retries', 2);

    $this->mock(Client::class)
        ->shouldReceive('send')
        ->once()
        ->andThrow(timeoutException(new SetViewPortion('1', '2', 0.5)));

    Recombee::user(1)->viewedPortion('2', 0.5)->save();
})->throws(ApiTimeoutException::class);
