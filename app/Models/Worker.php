<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\HasTranslations;
use Database\Factories\WorkerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['branch_id', 'agency_id', 'name_ar', 'name_en', 'nationality', 'passport_number', 'iqama_number'])]
class Worker extends Model
{
    /** @use HasFactory<WorkerFactory> */
    use BelongsToBranch, HasFactory, HasTranslations, SoftDeletes;
}
