<?php

namespace Tests\Feature\Support;

use App\Models\Client;
use App\Models\Worker;
use App\Support\IdentifierHash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IdentifierHashTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalizes_case_and_spaces(): void
    {
        $this->assertSame('P01234C', IdentifierHash::normalize("  p01 23\t4c "));
        $this->assertNull(IdentifierHash::normalize('   '));
        $this->assertSame(IdentifierHash::make('p01 234c'), IdentifierHash::make('P01234C'));
    }

    public function test_lookup_is_an_hmac_with_the_secret_key_not_a_plain_hash(): void
    {
        $expected = hash_hmac('sha256', 'P01234C', config('app.identifier_hash_key'));

        $this->assertSame($expected, IdentifierHash::make('p01234c'));
        $this->assertNotSame(hash('sha256', 'P01234C'), IdentifierHash::make('P01234C'));

        config(['app.identifier_hash_key' => 'another-key']);
        $this->assertNotSame($expected, IdentifierHash::make('P01234C'));
    }

    public function test_models_store_encrypted_values_and_hmac_lookups(): void
    {
        $worker = Worker::factory()->withIqama()->create(['passport_no' => 'P0123456C']);
        $client = Client::factory()->create(['national_id' => '1012345678']);

        $row = DB::table('workers')->where('id', $worker->id)->first();
        $this->assertNotSame('P0123456C', $row->passport_no);
        $this->assertSame('P0123456C', $worker->fresh()->passport_no);
        $this->assertSame(IdentifierHash::make('P0123456C'), $row->passport_lookup);
        $this->assertSame(IdentifierHash::make($worker->iqama_no), $row->iqama_lookup);

        $this->assertNotSame('1012345678', DB::table('clients')->where('id', $client->id)->value('national_id'));
        $this->assertSame(IdentifierHash::make('1012345678'), DB::table('clients')->where('id', $client->id)->value('national_id_lookup'));
    }

    public function test_changing_the_number_updates_the_lookup(): void
    {
        $worker = Worker::factory()->create(['passport_no' => 'A1111111']);
        $worker->update(['passport_no' => 'B2222222']);

        $this->assertTrue(Worker::query()->whereIdentifier('passport', 'b2222222')->exists());
        $this->assertFalse(Worker::query()->whereIdentifier('passport', 'A1111111')->exists());
    }
}
