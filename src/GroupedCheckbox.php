<?php

namespace NovaBrFields\GroupedCheckbox;

use App\Models\Permission;
use App\Services\PermissionGrouper;
use Closure;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;
use Spatie\Permission\PermissionRegistrar;

class GroupedCheckbox extends Field
{
    /**
     * The field's component.
     */
    public $component = 'grouped-checkbox';

    /**
     * Closure that returns the grouped items array.
     */
    protected ?Closure $groupsResolver = null;

    /**
     * Closure that syncs selected IDs to the model.
     */
    protected ?Closure $syncCallback = null;

    /**
     * Closure that resolves selected IDs from the model.
     */
    protected ?Closure $selectedIdsResolver = null;

    /**
     * Set the closure that provides grouped items.
     */
    public function groups(Closure $callback): static
    {
        $this->groupsResolver = $callback;

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
     * Resolve the field's value for display.
     */
    public function resolve($resource, $attribute = null): void
    {
        if ($this->groupsResolver === null) {
            throw new \LogicException(
                'GroupedCheckbox: groups callback is not defined. Call groups() before resolving.'
            );
        }

        $groups = call_user_func($this->groupsResolver);

        $selectedIds = $this->selectedIdsResolver
            ? call_user_func($this->selectedIdsResolver, $resource)
            : [];

        $this->withMeta(['groups' => $groups]);
        $this->value = $selectedIds;

        $count = count($selectedIds);
        $this->withMeta(['indexValue' => "{$count} permissões"]);
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

    /**
     * Create a GroupedCheckbox pre-configured for Spatie Permissions.
     */
    public static function forPermissions(string $label = 'Permissões'): static
    {
        $field = new static($label, 'permissions');

        $field->groups(function () {
            $permissions = Permission::with('permissionCategory')->get();
            $grouped = PermissionGrouper::group($permissions);

            return collect($grouped)->map(fn (array $group) => [
                'label' => $group['label'],
                'items' => collect($group['permissions'])->map(fn (Permission $p) => [
                    'id' => $p->id,
                    'label' => static::formatPermissionLabel($p, $group),
                ])->values()->all(),
            ])->values()->all();
        });

        $field->selectedUsing(fn (object $model) => $model->permissions->pluck('id')->all());

        $field->syncUsing(function (object $model, array $ids) {
            $model->syncPermissions(
                Permission::whereIn('id', $ids)->get()
            );
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });

        return $field;
    }

    /**
     * Format a permission label using PermissionGrouper mappings.
     */
    protected static function formatPermissionLabel(Permission $permission, array $group): string
    {
        $parsed = PermissionGrouper::parsePermission($permission->name);

        if ($parsed['action'] === null || $parsed['resource'] === null) {
            return $permission->name;
        }

        $resourceLabel = PermissionGrouper::getResourceLabel($parsed['resource']);
        $actionLabel = PermissionGrouper::getActionLabel($parsed['action']);

        $resources = collect($group['permissions'])
            ->map(fn (Permission $p) => PermissionGrouper::parsePermission($p->name)['resource'])
            ->filter()
            ->unique();

        if ($resources->count() > 1) {
            return "{$resourceLabel} — {$actionLabel}";
        }

        return $actionLabel;
    }
}
