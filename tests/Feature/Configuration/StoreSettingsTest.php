<?php

declare(strict_types=1);

namespace Tests\Feature\Configuration;

use App\Actions\Configuration\UpdateStoreSettings;
use App\Enums\ActivityAction;
use App\Enums\Currency;
use App\Livewire\Configuration\StoreSettingsForm;
use App\Models\ActivityLog;
use App\Models\StoreSetting;
use App\Models\User;
use App\Support\Money;
use App\Support\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StoreSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_store_settings_table_matches_configuration_schema(): void
    {
        $this->assertTrue(Schema::hasColumns('store_settings', [
            'id',
            'store_name',
            'address',
            'phone',
            'email',
            'currency_code',
            'currency_symbol',
            'receipt_footer',
            'logo_path',
            'low_stock_threshold',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_migration_seeds_a_single_default_settings_row(): void
    {
        $this->assertSame(1, StoreSetting::query()->count());

        $settings = StoreSettings::current();

        $this->assertSame('SimplePOS Store', $settings->store_name);
        $this->assertSame('IDR', $settings->currency_code);
        $this->assertSame('Rp', $settings->currency_symbol);
        $this->assertSame('5.000', $settings->low_stock_threshold);
    }

    public function test_owner_can_update_store_name_and_subsequent_read_shows_new_name(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(StoreSettingsForm::class)
            ->set('store_name', 'Toko Maju')
            ->set('address', 'Jl. Contoh 1')
            ->set('phone', '08123456789')
            ->set('email', 'toko@example.com')
            ->set('currency_code', Currency::Idr->value)
            ->set('receipt_footer', 'Terima kasih')
            ->set('low_stock_threshold', '3')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('settings.edit'));

        $settings = StoreSettings::current();

        $this->assertSame('Toko Maju', $settings->store_name);
        $this->assertSame('Jl. Contoh 1', $settings->address);
        $this->assertSame('08123456789', $settings->phone);
        $this->assertSame('toko@example.com', $settings->email);
        $this->assertSame('Terima kasih', $settings->receipt_footer);
        $this->assertSame('3.000', $settings->low_stock_threshold);

        $this->actingAs($owner)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('Toko Maju', false);
    }

    public function test_administrator_can_update_settings(): void
    {
        $admin = User::factory()->administrator()->create();

        Livewire::actingAs($admin)
            ->test(StoreSettingsForm::class)
            ->set('store_name', 'Admin Updated Store')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Admin Updated Store', StoreSettings::current()->store_name);
    }

    public function test_cashier_cannot_access_settings(): void
    {
        $cashier = User::factory()->cashier()->create();
        $settings = StoreSettings::current();

        $this->actingAs($cashier)->get(route('settings.edit'))->assertForbidden();

        Livewire::actingAs($cashier)
            ->test(StoreSettingsForm::class)
            ->assertForbidden();

        $this->assertFalse($cashier->can('update', $settings));
    }

    public function test_unknown_currency_code_is_rejected(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(StoreSettingsForm::class)
            ->set('currency_code', 'EUR')
            ->call('save')
            ->assertHasErrors(['currency_code']);

        $this->assertSame('IDR', StoreSettings::current()->currency_code);
    }

    public function test_valid_logo_upload_replaces_the_previous_logo_path(): void
    {
        $owner = User::factory()->owner()->create();
        $settings = StoreSettings::current();
        $previousPath = 'logos/old-logo.png';
        Storage::disk('public')->put($previousPath, 'old');
        $settings->update(['logo_path' => $previousPath]);

        $upload = UploadedFile::fake()->image('store-logo.png');

        Livewire::actingAs($owner)
            ->test(StoreSettingsForm::class)
            ->set('logo', $upload)
            ->call('save')
            ->assertHasNoErrors();

        $settings->refresh();
        $this->assertNotNull($settings->logo_path);
        $this->assertNotSame($previousPath, $settings->logo_path);
        Storage::disk('public')->assertExists($settings->logo_path);
        Storage::disk('public')->assertMissing($previousPath);
    }

    public function test_invalid_logo_is_rejected_and_previous_logo_path_is_unchanged(): void
    {
        $owner = User::factory()->owner()->create();
        $settings = StoreSettings::current();
        $previousPath = 'logos/keep-me.png';
        Storage::disk('public')->put($previousPath, 'keep');
        $settings->update(['logo_path' => $previousPath]);

        Livewire::actingAs($owner)
            ->test(StoreSettingsForm::class)
            ->set('logo', UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'))
            ->call('save')
            ->assertHasErrors(['logo']);

        $this->assertSame($previousPath, $settings->fresh()->logo_path);
        Storage::disk('public')->assertExists($previousPath);
    }

    public function test_logo_replacement_works_when_the_previous_file_is_missing(): void
    {
        $owner = User::factory()->owner()->create();
        $settings = StoreSettings::current();
        $settings->update(['logo_path' => 'logos/missing.png']);

        Livewire::actingAs($owner)
            ->test(StoreSettingsForm::class)
            ->set('logo', UploadedFile::fake()->image('new-logo.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $settings->refresh();
        $this->assertNotSame('logos/missing.png', $settings->logo_path);
        Storage::disk('public')->assertExists($settings->logo_path);
    }

    public function test_settings_update_writes_store_settings_updated_audit_row(): void
    {
        $owner = User::factory()->owner()->create();
        $settings = StoreSettings::current();

        app(UpdateStoreSettings::class)->execute($owner, $settings, [
            'store_name' => 'Audited Store',
            'currency_code' => Currency::Idr->value,
            'low_stock_threshold' => '5',
        ]);

        $log = ActivityLog::query()->sole();

        $this->assertSame(ActivityAction::StoreSettingsUpdated, $log->action);
        $this->assertSame($owner->id, $log->user_id);
        $this->assertSame('StoreSetting', $log->subject_type);
        $this->assertSame($settings->id, $log->subject_id);
        $this->assertSame('SimplePOS Store', $log->old_values['store_name'] ?? null);
        $this->assertSame('Audited Store', $log->new_values['store_name'] ?? null);
        $this->assertArrayNotHasKey('logo', $log->old_values ?? []);
    }

    public function test_money_component_uses_configured_currency_symbol(): void
    {
        $settings = StoreSettings::current();
        $settings->update([
            'currency_code' => Currency::Usd->value,
            'currency_symbol' => Currency::Usd->symbol(),
        ]);
        StoreSettings::clearCache();

        $this->assertSame('$ 12,50', Money::format('12.5'));
    }

    public function test_low_stock_threshold_of_zero_is_allowed(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(StoreSettingsForm::class)
            ->set('low_stock_threshold', '0')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('0.000', StoreSettings::current()->low_stock_threshold);
    }
}
