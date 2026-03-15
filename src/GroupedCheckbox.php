<?php

namespace NovaBrFields\GroupedCheckbox;

use Closure;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class GroupedCheckbox extends Field
{
    /**
     * The field's component.
     */
    public $component = 'grouped-checkbox';

    /**
     * Closure that returns the options (grouped or flat).
     */
    protected ?Closure $optionsResolver = null;

    /**
     * Closure that syncs selected IDs to the model.
     */
    protected ?Closure $syncCallback = null;

    /**
     * Closure that resolves selected IDs from the model.
     */
    protected ?Closure $selectedIdsResolver = null;

    /**
     * The formatted string for the index view.
     */
    protected string|Closure|null $indexLabel = null;

    /**
     * Set the options as grouped items.
     *
     * Expected format:
     * [
     *   ['label' => 'Group Name', 'items' => [['id' => 1, 'label' => 'Item']]],
     * ]
     */
    public function groups(Closure $callback): static
    {
        $this->optionsResolver = $callback;
        $this->withMeta(['grouped' => true]);

        return $this;
    }

    /**
     * Set the options as a flat list (no grouping).
     *
     * Expected format:
     * [
     *   ['id' => 1, 'label' => 'Item A'],
     *   ['id' => 2, 'label' => 'Item B'],
     * ]
     */
    public function options(Closure $callback): static
    {
        $this->optionsResolver = $callback;
        $this->withMeta(['grouped' => false]);

        return $this;
    }

    /**
     * Set the closure that fills/syncs selected IDs on the model.
     *
     * @param  Closure(object, array<int>): void  $callback
     */
    public function syncUsing(Closure $callback): static
    {
        $this->syncCallback = $callback;

        return $this;
    }

    /**
     * Set the closure that resolves selected IDs from the model.
     *
     * @param  Closure(object): array<int>  $callback
     */
    public function selectedUsing(Closure $callback): static
    {
        $this->selectedIdsResolver = $callback;

        return $this;
    }

    /**
     * Set the label displayed on the index view.
     *
     * Accepts a static string or a closure that receives the count.
     * Example: ->indexLabel(fn (int $count) => "{$count} itens")
     */
    public function indexLabel(string|Closure $label): static
    {
        $this->indexLabel = $label;

        return $this;
    }

    /**
     * Resolve the field's value for display.
     */
    public function resolve($resource, $attribute = null): void
    {
        if ($this->optionsResolver === null) {
            throw new \LogicException(
                'GroupedCheckbox: options not defined. Call groups() or options() before resolving.'
            );
        }

        $options = call_user_func($this->optionsResolver);

        $selectedIds = $this->selectedIdsResolver
            ? call_user_func($this->selectedIdsResolver, $resource)
            : [];

        $this->withMeta(['groups' => $options]);
        $this->value = $selectedIds;

        $count = count($selectedIds);
        $indexValue = match (true) {
            $this->indexLabel instanceof Closure => call_user_func($this->indexLabel, $count),
            is_string($this->indexLabel) => $this->indexLabel,
            default => (string) $count,
        };
        $this->withMeta(['indexValue' => $indexValue]);
    }

    /**
     * Hydrate the model from the request.
     */
    protected function fillAttributeFromRequest(
        NovaRequest $request,
        string $requestAttribute,
        object $model,
        string $attribute
    ): ?Closure {
        if ($this->syncCallback === null) {
            throw new \LogicException(
                'GroupedCheckbox: sync callback is not defined. Call syncUsing() before saving.'
            );
        }

        $json = $request->input($requestAttribute, '[]');
        $ids = json_decode($json, true);

        if (! is_array($ids)) {
            $ids = [];
        }

        $ids = array_filter($ids, fn ($id) => is_int($id) || (is_string($id) && ctype_digit($id)));
        $ids = array_values(array_map('intval', $ids));

        $syncCallback = $this->syncCallback;

        return function () use ($model, $ids, $syncCallback) {
            call_user_func($syncCallback, $model, $ids);
        };
    }
}
