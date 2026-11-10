<?php

namespace App\Models\Concerns;

/**
 * Bilingual columns stored as {field}_ar / {field}_en.
 *
 * $model->trans('name') returns the value in the current locale and falls
 * back to the other language when it is empty.
 */
trait HasTranslations
{
    public function trans(string $field, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $fallback = $locale === 'ar' ? 'en' : 'ar';

        $value = $this->getAttribute("{$field}_{$locale}");

        return filled($value) ? $value : $this->getAttribute("{$field}_{$fallback}");
    }

    /**
     * Both language values of a field, e.g. for audit log parameters.
     *
     * @return array{ar: ?string, en: ?string}
     */
    public function translations(string $field): array
    {
        return [
            'ar' => $this->getAttribute("{$field}_ar"),
            'en' => $this->getAttribute("{$field}_en"),
        ];
    }
}
