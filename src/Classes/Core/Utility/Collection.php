<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Classes\Core\Utility;

use Loom\FrameworkComponent\Classes\Database\LoomModel;
use Traversable;

class Collection implements \IteratorAggregate, \Countable
{
    protected array $items = [];

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function add(mixed $item): void
    {
        if (is_array($item)) {
            $this->items = array_merge($this->items, $item);
            return;
        }

        $this->items[] = $item;
    }

    public function remove(mixed $item): void
    {
        if ($item instanceof LoomModel) {
            $key = array_search($this->find(function (LoomModel $model) use ($item) {
                return get_class($model) === get_class($item)
                    && $model->getIdentifierValue() === $item->getIdentifierValue();
            }), $this->items, true);
        } else {
            $key = array_search($item, $this->items, true);
        }

        if ($key !== false) {
            unset($this->items[$key]);
        }
    }

    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    public function last(): mixed
    {
        return $this->items[count($this->items) - 1];
    }

    public function find(callable $callback): mixed
    {
        return array_find($this->items, fn($item) => $callback($item));
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function toArray(): array
    {
        return $this->items;
    }
}