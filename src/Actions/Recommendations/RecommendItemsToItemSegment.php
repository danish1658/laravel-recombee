<?php

declare(strict_types=1);

namespace Baron\Recombee\Actions\Recommendations;

use Baron\Recombee\Actions\ListRecommendations;
use Recombee\RecommApi\Requests\RecommendItemsToItemSegment as ApiRequest;

class RecommendItemsToItemSegment extends ListRecommendations
{
    protected function generateRequest()
    {
        return new ApiRequest(
            $this->builder->param('contextSegmentId'),
            $this->builder->param('targetUserId'),
            $this->builder->param('count'),
            $this->builder->prepareOptions($this->defaultOptions)
        );
    }
}
