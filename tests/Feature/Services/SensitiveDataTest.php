<?php

namespace Tests\Feature\Services;

use App\Services\SensitiveData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SensitiveDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    public function test_masks_passport_and_national_id_formats(): void
    {
        $masker = new SensitiveData;

        $this->assertSame('P01****6C', $masker->mask('P0123456C', 'passport'));
        $this->assertSame('10******98', $masker->mask('1012345698', 'national_id'));
    }

    public function test_user_without_view_sensitive_sees_masked_value(): void
    {
        $this->actingAs($this->userWithRole('rental_team'));

        $html = Blade::render('<x-masked :value="$value" permission="workers.view_sensitive" type="passport" />', ['value' => 'P0123456C']);

        $this->assertStringContainsString('P01****6C', $html);
        $this->assertStringNotContainsString('P0123456C', $html);
    }

    public function test_user_with_view_sensitive_sees_full_value(): void
    {
        $this->actingAs($this->userWithRole('general_manager'));

        $html = Blade::render('<x-masked :value="$value" permission="workers.view_sensitive" type="passport" />', ['value' => 'P0123456C']);

        $this->assertStringContainsString('P0123456C', $html);
    }
}
