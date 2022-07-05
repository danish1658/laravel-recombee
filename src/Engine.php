<?php

namespace Baron\Recombee;

use Baron\Recombee\Support\RecommendationCollection;
use Baron\Recombee\Support\UserCollection;
use Illuminate\Support\Arr;
use Recombee\RecommApi\Client;
use Recombee\RecommApi\Requests\ListUsers;
use Recombee\RecommApi\Requests\RecommendItemsToItem;
use Recombee\RecommApi\Requests\RecommendItemsToUser;
use Recombee\RecommApi\Requests\ResetDatabase;

class Engine
{
    public function __construct(
        protected Client $recombee
    ) {
    }

    public function reset()
    {
        return $this->recombee->send(new ResetDatabase());
    }

    public function listUsers(Builder $builder)
    {
        return $this->mapUsers($builder, $this->recombee->send(new ListUsers(
            $builder->prepareOptions()
        )));
    }

    public function recommendItemsToUser(Builder $builder)
    {
        return $this->map($builder, $this->recombee->send(new RecommendItemsToUser(
            $builder->getInitiator()->getId(),
            $builder->limit,
            $builder->prepareOptions()
        )));
    }

    public function recommendItemsToItem(Builder $builder)
    {
        return $this->map($builder, $this->recombee->send(new RecommendItemsToItem(
            $builder->getInitiator()->getId(),
            $builder->targetUserId,
            $builder->limit,
            $builder->prepareOptions()
        )));
    }

    protected function map(Builder $builder, array $results)
    {
        return (new RecommendationCollection(Arr::get($results, 'recomms')))
            ->additional(['meta' => Arr::except($results, 'recomms')]);
    }

    protected function mapUsers(Builder $builder, array $results)
    {
        return new UserCollection($results);
    }
}
