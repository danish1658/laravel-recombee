<?php

namespace Baron\Recombee\Actions\Interactions\Bookmarks;

use Baron\Recombee\Actions\Action;
use Baron\Recombee\Collection\InteractionCollection;
use Recombee\RecommApi\Requests\ListItemBookmarks as ApiRequest;

class ListItemBookmarks extends Action
{
    public function execute()
    {
        return $this->map($this->query());
    }

    protected function buildApiRequest()
    {
        return new ApiRequest(
            $this->builder->getInitiator()->getId()
        );
    }

    protected function map($results): InteractionCollection
    {
        return new InteractionCollection($results);
    }
}
