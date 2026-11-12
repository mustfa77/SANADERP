<?php

namespace App\Services\Workers;

use App\Enums\CvCategory;
use App\Enums\LanguageLevel;
use App\Models\User;
use App\Models\Worker;
use App\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Worker CV (staff screen and the external agency portal): personal CV
 * fields, the profession template's skills and extra fields, employment
 * background and the two photos (private disk).
 */
class WorkerCvService
{
    private const PHOTO_DIRECTORY = 'worker-photos';

    public function __construct(private AuditLogger $audit) {}

    /**
     * Validation rules for the worker's CV template.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(Worker $worker): array
    {
        $template = $worker->cvTemplate();
        $rules = [
            'cv_reference_no' => ['nullable', 'string', 'max:30'],
            'cv_category' => ['nullable', Rule::enum(CvCategory::class)],
            'home_city' => ['nullable', 'string', 'max:100'],
            'education' => ['nullable', 'string', 'max:100'],
            'children_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'height_cm' => ['nullable', 'integer', 'min:100', 'max:230'],
            'weight_kg' => ['nullable', 'integer', 'min:30', 'max:200'],
            'arabic_level' => ['nullable', Rule::enum(LanguageLevel::class)],
            'english_level' => ['nullable', Rule::enum(LanguageLevel::class)],
            'cv_skills' => ['array'],
            'cv_skills.*' => [Rule::in($template['skills'])],
            'experiences' => ['array', 'max:10'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'full_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];

        $rules += $template['experience'] === 'detailed'
            ? [
                'experiences.*.position' => ['nullable', 'string', 'max:100'],
                'experiences.*.company' => ['nullable', 'string', 'max:150'],
                'experiences.*.location' => ['nullable', 'string', 'max:150'],
                'experiences.*.started_at' => ['required_with:experiences.*.position', 'nullable', 'date', 'before_or_equal:today'],
                'experiences.*.ended_at' => ['nullable', 'date', 'after_or_equal:experiences.*.started_at'],
                'experiences.*.description' => ['nullable', 'string', 'max:2000'],
            ]
            : [
                'experiences.*.country' => ['required_with:experiences.*.position', 'nullable', 'string', 'max:100'],
                'experiences.*.position' => ['required_with:experiences.*.country', 'nullable', 'string', 'max:100'],
                'experiences.*.duration' => ['nullable', 'string', 'max:50'],
            ];

        foreach ($template['fields'] as $key => $field) {
            $rules["cv_details.{$key}"] = match ($field['type']) {
                'number' => ['nullable', 'integer', 'min:'.($field['min'] ?? 0), 'max:'.($field['max'] ?? 1000)],
                'date' => ['nullable', 'date'],
                'url' => ['nullable', 'url:http,https', 'max:500'],
                'select' => ['nullable', Rule::in($field['options'])],
                default => ['nullable', 'string', 'max:100'],
            };
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(Worker $worker): array
    {
        $names = collect(['cv_reference_no', 'cv_category', 'home_city', 'education', 'children_count', 'height_cm', 'weight_kg', 'arabic_level', 'english_level', 'photo', 'full_photo'])
            ->mapWithKeys(fn (string $field): array => [$field => __("cv.fields.{$field}")])
            ->all();

        foreach (array_keys($worker->cvTemplate()['fields']) as $key) {
            $names["cv_details.{$key}"] = __("cv.details.{$key}.label");
        }

        $place = $worker->cvTemplate()['place'];

        return [
            ...$names,
            'experiences.*.country' => __("cv.experience.{$place}"),
            ...collect(['position', 'duration', 'company', 'location', 'started_at', 'ended_at', 'description'])
                ->mapWithKeys(fn (string $field): array => ["experiences.*.{$field}" => __("cv.experience.{$field}")])->all(),
        ];
    }

    /**
     * Save validated CV data.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Worker $worker, array $data, User $user, ?UploadedFile $photo = null, ?UploadedFile $fullPhoto = null): Worker
    {
        $template = $worker->cvTemplate();

        DB::transaction(function () use ($worker, $data, $user, $photo, $fullPhoto, $template): void {
            $worker->forceFill([
                ...collect($data)->only(['cv_reference_no', 'cv_category', 'home_city', 'education', 'children_count', 'height_cm', 'weight_kg', 'arabic_level', 'english_level'])->all(),
                'cv_skills' => array_values(array_intersect($template['skills'], (array) ($data['cv_skills'] ?? []))),
                'cv_details' => array_intersect_key((array) ($data['cv_details'] ?? []), $template['fields']) ?: null,
                'cv_updated_at' => now(),
                'cv_updated_by' => $user->id,
            ]);

            if ($photo) {
                $worker->photo_path = $this->replacePhoto($worker->photo_path, $photo);
            }

            if ($fullPhoto) {
                $worker->full_photo_path = $this->replacePhoto($worker->full_photo_path, $fullPhoto);
            }

            $worker->save();

            $detailed = $template['experience'] === 'detailed';
            $columns = $detailed
                ? ['position', 'company', 'location', 'started_at', 'ended_at', 'description']
                : ['country', 'position', 'duration'];

            $worker->experiences()->delete();
            collect($data['experiences'] ?? [])
                ->map(fn (array $row): array => array_map(fn ($value) => filled($value) ? $value : null, array_intersect_key($row, array_flip($columns))))
                ->filter(fn (array $row): bool => array_filter($row) !== [])
                ->values()
                ->each(fn (array $row, int $index) => $worker->experiences()->create([...$row, 'sort_order' => $index]));

            $this->audit->log('worker.cv_updated', $worker, ['name' => $worker->translations('name')]);
        });

        return $worker->fresh(['experiences']);
    }

    private function replacePhoto(?string $current, UploadedFile $file): string
    {
        $path = $file->store(self::PHOTO_DIRECTORY, 'local');

        if ($current) {
            Storage::disk('local')->delete($current);
        }

        return $path;
    }
}
