<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Yearly sequential numbers such as "MR-2026-0001" (read under lock, so
 * call it inside the transaction that saves the record).
 */
class SequenceNumber
{
    /**
     * @param  class-string<Model>  $model
     */
    public static function next(string $model, string $prefix, string $column = 'number'): string
    {
        $start = $prefix.'-'.now()->format('Y').'-';
        $last = $model::query()->withoutGlobalScopes()->where($column, 'like', $start.'%')->lockForUpdate()->max($column);
        $sequence = $last ? (int) substr($last, strlen($start)) + 1 : 1;

        return $start.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
