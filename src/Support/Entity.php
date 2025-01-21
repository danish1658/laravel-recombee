<?php

declare(strict_types=1);

namespace Baron\Recombee\Support;

use Illuminate\Database\Eloquent\Model;
use UnhandledMatchError;

class Entity
{
    public const USER = 'user';
    public const ITEM = 'item';

    protected ?string $id;
    protected string $key = 'id';
    protected string $type;
    protected array $values = [];

    public function __construct(Model|string $entity = null, array $values = [], string $type = self::USER)
    {
        $userClass = config('recombee.user');
        $itemClass = config('recombee.item');

        if ($entity instanceof Model) {
            $this->id = (string) $entity->getAttribute($entity->getKeyName());
            $this->key = $entity->getKeyName();
            $this->type = match (true) {
                $entity instanceof $userClass => self::USER,
                is_string($itemClass) && $entity instanceof $itemClass => self::ITEM,
                is_array($itemClass) && collect($itemClass)->contains(fn($class) => $entity instanceof $class) => self::ITEM,
                default => throw new UnhandledMatchError()
            };
        } else {
            $this->id = $entity;
            $this->values = $values;
            $this->type = $type;
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function getKeyName()
    {
        return $this->key;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getValues()
    {
        return $this->values;
    }

    public function isUser()
    {
        return $this->type === self::USER;
    }

    public function isItem()
    {
        return $this->type === self::ITEM;
    }
}
