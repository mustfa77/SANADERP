<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Manual ordering by a "sort_order" column, optionally within a group
 * (e.g. travel stages of one country). Override sortGroupColumns() to
 * define the group. New records go to the end of their group.
 */
trait Sortable
{
    public static function bootSortable(): void
    {
        static::creating(function (self $model): void {
            if (! $model->sort_order) {
                $model->sort_order = (int) $model->siblings()->max('sort_order') + 1;
            }
        });
    }

    /**
     * Columns that define the ordering group (empty = one global list).
     *
     * @return list<string>
     */
    public function sortGroupColumns(): array
    {
        return [];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy($query->qualifyColumn('sort_order'))->orderBy($query->qualifyColumn('id'));
    }

    /**
     * Records in the same ordering group (including this one).
     *
     * @return Builder<static>
     */
    public function siblings(): Builder
    {
        $query = static::query();

        foreach ($this->sortGroupColumns() as $column) {
            $query->where($column, $this->getAttribute($column));
        }

        return $query;
    }

    /**
     * Swap places with the previous (-1) or next (+1) record of the group.
     */
    public function move(int $direction): void
    {
        DB::transaction(function () use ($direction): void {
            $this->normalizeGroupOrder();
            $this->refresh();

            $neighbour = $this->siblings()
                ->where('sort_order', $direction < 0 ? '<' : '>', $this->sort_order)
                ->orderBy('sort_order', $direction < 0 ? 'desc' : 'asc')
                ->first();

            if ($neighbour) {
                [$this->sort_order, $neighbour->sort_order] = [$neighbour->sort_order, $this->sort_order];
                $this->save();
                $neighbour->save();
            }
        });
    }

    /**
     * Set the order of a group from a list of ids (drag and drop). Ids that
     * are not in the group are ignored.
     *
     * @param  list<int>  $ids
     */
    public function reorderGroup(array $ids): void
    {
        DB::transaction(function () use ($ids): void {
            $groupIds = $this->siblings()->pluck('id')->all();
            $position = 1;

            foreach (array_values(array_intersect($ids, $groupIds)) as $id) {
                static::query()->whereKey($id)->update(['sort_order' => $position++]);
            }
        });
    }

    /**
     * Renumber the group 1..n keeping the current order (fixes ties).
     */
    private function normalizeGroupOrder(): void
    {
        $position = 1;

        foreach ($this->siblings()->ordered()->pluck('id') as $id) {
            static::query()->whereKey($id)->update(['sort_order' => $position++]);
        }
    }
}
