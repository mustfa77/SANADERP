<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Writes audit entries. The message is a translation key
 * (lang/{ar,en}/audit.php "actions.{action}") plus parameters.
 */
class AuditLogger
{
    public function __construct(private Request $request) {}

    /**
     * @param  array<string, mixed>  $params  Translation parameters. Bilingual
     *                                        values may be given as ['ar' => .., 'en' => ..].
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public function log(
        string $action,
        ?Model $target = null,
        array $params = [],
        ?array $old = null,
        ?array $new = null,
        ?User $actor = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => ($actor ?? Auth::user())?->getKey(),
            'action_key' => $action,
            'params' => $params ?: null,
            'target_type' => $target?->getMorphClass(),
            'target_id' => $target?->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => $this->request->ip(),
            'user_agent' => substr((string) $this->request->userAgent(), 0, 1000),
        ]);
    }
}
