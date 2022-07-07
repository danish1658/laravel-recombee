<?php

declare(strict_types=1);

namespace Baron\Recombee;

use Baron\Recombee\Support\Entity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class Builder
{
    protected Entity $initiator;
    protected Entity $target;

    protected array $action;
    protected array $params;
    protected array $options;

    public function __construct(protected Engine $engine)
    {
        $this->options = [];
        $this->params = ['count' => 25];
    }

    public function engine(): Engine
    {
        return $this->engine;
    }

    public function user(Model|string $userId, array $values = []): self
    {
        $this->initiator = new Entity($userId, $values, Entity::USER);

        return $this;
    }

    public function item(Model|string $itemId, array $values = []): self
    {
        $this->initiator = new Entity($itemId, $values, Entity::ITEM);

        return $this;
    }

    public function users(): self
    {
        $this->action = ['get' => \Baron\Recombee\Actions\Users\ListUsers::class];

        return $this;
    }

    public function reset()
    {
        $this->action = ['delete' => \Baron\Recombee\Actions\Miscellaneous\ResetDatabase::class];

        return $this->delete();
    }

    public function recommendable()
    {
        $this->action = $this->getInitiator()->isUser()
            ? ['post' => \Baron\Recombee\Actions\Users\AddUser::class]
            : ['post' => \Baron\Recombee\Actions\Items\AddItem::class];

        return $this->save();
    }

    public function param(string $key, mixed $value = null): mixed
    {
        if (func_num_args() === 1) {
            return Arr::get($this->params, $key, null);
        }

        $this->params[$key] = $value;

        return $this;
    }

    public function option(string $key, mixed $value = null): mixed
    {
        if (func_num_args() === 1) {
            return Arr::get($this->options, $key, null);
        }

        $this->options[$key] = $value;

        return $this;
    }

    public function limit(?int $limit = null): int|self
    {
        if (is_null($limit)) {
            return $this->param('count');
        }

        $this->param('count', $limit);

        return $this;
    }

    public function seenBy(Model|string $targetUser): self
    {
        $this->param('targetUserId', (new Entity($targetUser))->getId());

        return $this;
    }

    public function select(...$properties): self
    {
        $this->option('returnProperties', empty($properties) ? null : true);
        $this->option('includedProperties', implode(',', $properties) ?: null);

        return $this;
    }

    public function recommendItems(): self
    {
        $this->action = $this->initiator->isUser()
            ? ['get' => \Baron\Recombee\Actions\Recommendations\RecommendItemsToUser::class]
            : ['get' => \Baron\Recombee\Actions\Recommendations\RecommendItemsToItem::class];

        return $this;
    }

    public function views(): self
    {
        $this->action = $this->initiator->isUser()
            ? ['get' => \Baron\Recombee\Actions\Interactions\Views\ListUserDetailViews::class]
            : ['get' => \Baron\Recombee\Actions\Interactions\Views\ListItemDetailViews::class];

        return $this;
    }

    public function viewed(Model|string $item): self
    {
        $this->target = new Entity($item);
        $this->action = [
            'post' => \Baron\Recombee\Actions\Interactions\Views\AddDetailView::class,
            'delete' => \Baron\Recombee\Actions\Interactions\Views\DeleteDetailView::class,
        ];

        return $this;
    }

    public function purchases(): self
    {
        $this->action = $this->initiator->isUser()
            ? ['get' => \Baron\Recombee\Actions\Interactions\Purchases\ListUserPurchases::class]
            : ['get' => \Baron\Recombee\Actions\Interactions\Purchases\ListItemPurchases::class];

        return $this;
    }

    public function purchased(Model|string $item): self
    {
        $this->target = new Entity($item);
        $this->action = [
            'post' => \Baron\Recombee\Actions\Interactions\Purchases\AddPurchase::class,
            'delete' => \Baron\Recombee\Actions\Interactions\Purchases\DeletePurchase::class,
        ];

        return $this;
    }

    public function ratings(): self
    {
        $this->action = $this->initiator->isUser()
            ? ['get' => \Baron\Recombee\Actions\Interactions\Ratings\ListUserRatings::class]
            : ['get' => \Baron\Recombee\Actions\Interactions\Ratings\ListItemRatings::class];

        return $this;
    }

    public function rated(Model|string $item, ?float $rating = null): self
    {
        $this->target = new Entity($item);
        $this->param('rating', $rating);
        $this->action = [
            'post' => \Baron\Recombee\Actions\Interactions\Ratings\AddRating::class,
            'delete' => \Baron\Recombee\Actions\Interactions\Ratings\DeleteRating::class,
        ];

        return $this;
    }

    public function cart(): self
    {
        $this->action = $this->initiator->isUser()
            ? ['get' => \Baron\Recombee\Actions\Interactions\Cart\ListUserCartAdditions::class]
            : ['get' => \Baron\Recombee\Actions\Interactions\Cart\ListItemCartAdditions::class];

        return $this;
    }

    public function carted(Model|string $item): self
    {
        $this->target = new Entity($item);
        $this->action = [
            'post' => \Baron\Recombee\Actions\Interactions\Cart\AddCartAddition::class,
            'delete' => \Baron\Recombee\Actions\Interactions\Cart\DeleteCartAddition::class,
        ];

        return $this;
    }

    public function bookmarks(): self
    {
        $this->action = $this->initiator->isUser()
            ? ['get' => \Baron\Recombee\Actions\Interactions\Bookmarks\ListUserBookmarks::class]
            : ['get' => \Baron\Recombee\Actions\Interactions\Bookmarks\ListItemBookmarks::class];

        return $this;
    }

    public function bookmarked(Model|string $item): self
    {
        $this->target = new Entity($item);
        $this->action = [
            'post' => \Baron\Recombee\Actions\Interactions\Bookmarks\AddBookmark::class,
            'delete' => \Baron\Recombee\Actions\Interactions\Bookmarks\DeleteBookmark::class,
        ];

        return $this;
    }

    public function viewPortions(): self
    {
        $this->action = $this->initiator->isUser()
            ? ['get' => \Baron\Recombee\Actions\Interactions\PortionViews\ListUserViewPortions::class]
            : ['get' => \Baron\Recombee\Actions\Interactions\PortionViews\ListItemViewPortions::class];

        return $this;
    }

    public function viewedPortion(Model|string $item, ?float $portion = null): self
    {
        $this->target = new Entity($item);
        $this->param('portion', $portion);
        $this->action = [
            'post' => \Baron\Recombee\Actions\Interactions\PortionViews\SetViewPortion::class,
            'delete' => \Baron\Recombee\Actions\Interactions\PortionViews\DeleteViewPortion::class,
        ];

        return $this;
    }

    public function get()
    {
        return $this->performAction('get');
    }

    public function save()
    {
        return $this->performAction('post');
    }

    public function delete()
    {
        return $this->performAction('delete');
    }

    public function getInitiator(): Entity
    {
        return $this->initiator;
    }

    public function getTarget(): Entity
    {
        return $this->target;
    }

    public function prepareOptions(array $baseOptions = []): array
    {
        return collect(array_merge($baseOptions, $this->options))
            ->filter()
            ->all();
    }

    protected function performAction(string $verb)
    {
        return (new ($this->action[$verb])($this))->execute();
    }
}
