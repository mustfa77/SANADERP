<?php

namespace App\Support\Reference;

use App\Models\Concerns\Sortable;
use BackedEnum;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * Definition of one reference-data list managed by ReferenceController
 * (fields, permissions, search and filters). Labels come from
 * lang/{ar,en}/reference.php under "resources.{key}" and "fields".
 *
 * Field definition keys:
 * - type: text | textarea | email | tel | number | decimal | time | select
 * - translatable: stored as {name}_ar / {name}_en
 * - required, list (shown in the table), dir (ltr for codes/phones)
 * - options: Closure(): array<string|int, string> for selects
 * - rules: list of extra rules, or Closure(?Model $record): list
 *
 * @phpstan-type FieldDefinition array{type: string, translatable?: bool, required?: bool, list?: bool, dir?: string, options?: Closure, rules?: array<int, mixed>|Closure, step?: string}
 */
class ReferenceResource
{
    /**
     * @param  class-string<Model>  $model
     * @param  array{view: string, create: string, edit: string, delete: string}  $permissions
     * @param  array<string, FieldDefinition>  $fields
     * @param  list<string>  $search  Columns searched with LIKE.
     * @param  array<string, Closure(): array<string|int, string>>  $filters  Column => options.
     * @param  Closure(array<string, mixed>, ?Model): array<string, mixed>|null  $extraRules
     */
    public function __construct(
        public string $key,
        public string $model,
        public string $icon,
        public array $permissions,
        public array $fields,
        public array $search = [],
        public array $filters = [],
        public ?string $showRoute = null,
        public ?Closure $extraRules = null,
    ) {}

    /**
     * Select options resolved once per instance (the registry is built per request).
     *
     * @var array<string, array<string|int, string>>
     */
    private array $resolvedOptions = [];

    /**
     * @return array<string|int, string>
     */
    public function options(string $field): array
    {
        return $this->resolvedOptions[$field] ??= ($this->fields[$field]['options'] ?? $this->filters[$field])();
    }

    /**
     * @return array<string|int, string>
     */
    public function filterOptions(string $column): array
    {
        return $this->resolvedOptions['filter:'.$column] ??= ($this->filters[$column])();
    }

    public function permission(string $action): string
    {
        return $this->permissions[$action];
    }

    public function routeName(string $action): string
    {
        return "admin.reference.{$this->key}.{$action}";
    }

    public function isSortable(): bool
    {
        return in_array(Sortable::class, class_uses_recursive($this->model), true);
    }

    /**
     * Query honouring the model's global scopes (e.g. BranchScope for camps),
     * so out-of-scope records resolve to 404.
     *
     * @return Builder<Model>
     */
    public function query(): Builder
    {
        $query = $this->model::query();

        return $this->isSortable() ? $query->ordered() : $query->orderBy('name_'.app()->getLocale());
    }

    public function title(): string
    {
        return __("reference.resources.{$this->key}.title");
    }

    public function singular(): string
    {
        return __("reference.resources.{$this->key}.singular");
    }

    /**
     * Label of a field, with the language for bilingual columns.
     */
    public function fieldLabel(string $name, ?string $locale = null): string
    {
        $label = __("reference.fields.{$name}");

        return $locale ? __("reference.field_in_language.{$locale}", ['field' => $label]) : $label;
    }

    /**
     * Database columns of the form (bilingual fields expanded).
     *
     * @return list<string>
     */
    public function columns(): array
    {
        $columns = [];

        foreach ($this->fields as $name => $field) {
            if ($field['translatable'] ?? false) {
                $columns[] = "{$name}_ar";
                $columns[] = "{$name}_en";
            } else {
                $columns[] = $name;
            }
        }

        return [...$columns, 'is_active'];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(?Model $record): array
    {
        $rules = ['is_active' => ['nullable', 'boolean']];

        foreach ($this->fields as $name => $field) {
            $required = ($field['required'] ?? false) ? 'required' : 'nullable';
            $extra = $field['rules'] ?? [];
            $extra = $extra instanceof Closure ? $extra($record) : $extra;

            $base = match ($field['type']) {
                'textarea' => ['string', 'max:1000'],
                'email' => ['email', 'max:255'],
                'tel' => ['string', 'max:20', 'regex:/^[0-9+\s-]{7,20}$/'],
                'number' => ['integer', 'min:0'],
                'decimal' => ['numeric', 'min:0'],
                'time' => ['date_format:H:i'],
                'select' => [Rule::in(array_map('strval', array_keys($this->options($name))))],
                default => ['string', 'max:255'],
            };

            if ($field['translatable'] ?? false) {
                $rules["{$name}_ar"] = [$required, ...$base, ...$extra];
                $rules["{$name}_en"] = [$required, ...$base, ...$extra];
            } else {
                $rules[$name] = [$required, ...$base, ...$extra];
            }
        }

        return $rules;
    }

    /**
     * Validation attribute names.
     *
     * @return array<string, string>
     */
    public function attributeNames(): array
    {
        $names = [];

        foreach ($this->fields as $name => $field) {
            if ($field['translatable'] ?? false) {
                $names["{$name}_ar"] = $this->fieldLabel($name, 'ar');
                $names["{$name}_en"] = $this->fieldLabel($name, 'en');
            } else {
                $names[$name] = $this->fieldLabel($name);
            }
        }

        return $names;
    }

    /**
     * Fields shown as table columns.
     *
     * @return array<string, FieldDefinition>
     */
    public function listFields(): array
    {
        return array_filter($this->fields, fn (array $field): bool => $field['list'] ?? false);
    }

    /**
     * Value of a field for display in the current locale.
     */
    public function display(Model $record, string $name): string
    {
        $field = $this->fields[$name];

        if ($field['translatable'] ?? false) {
            return (string) $record->trans($name);
        }

        $value = $record->getAttribute($name);
        $value = $value instanceof BackedEnum ? $value->value : $value;

        return match ($field['type']) {
            'select' => (string) ($this->options($name)[$value] ?? '—'),
            'time' => $value ? substr((string) $value, 0, 5) : '—',
            'decimal' => $value === null ? '—' : rtrim(rtrim(number_format((float) $value, 2), '0'), '.'),
            default => filled($value) ? (string) $value : '—',
        };
    }

    /**
     * Value for a form input (enums as their value, times as HH:MM).
     */
    public function formValue(Model $record, string $column): mixed
    {
        $value = $record->getAttribute($column);

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        $field = $this->fields[$column] ?? null;

        return ($field['type'] ?? null) === 'time' && $value ? substr((string) $value, 0, 5) : $value;
    }

    /**
     * Name used in audit messages and flashes (both languages when bilingual).
     *
     * @return array{ar: ?string, en: ?string}|string
     */
    public function recordName(Model $record): array|string
    {
        if (isset($this->fields['name']) && ($this->fields['name']['translatable'] ?? false)) {
            return $record->translations('name');
        }

        $first = array_key_first($this->listFields());

        return $first ? $this->display($record, $first) : '#'.$record->getKey();
    }
}
