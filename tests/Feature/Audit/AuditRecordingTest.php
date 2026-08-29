<?php

declare(strict_types=1);

namespace Tests\Feature\Audit;

use App\Actions\Audit\RecordActivity;
use App\Actions\Identity\ChangeUserRole;
use App\Actions\Identity\ChangeUserStatus;
use App\Actions\Identity\CreateUser;
use App\Actions\Identity\UpdateUser;
use App\Enums\ActivityAction;
use App\Enums\UserRole;
use App\Livewire\Identity\UserForm;
use App\Livewire\Identity\UserIndex;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class AuditRecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_logs_table_matches_the_audit_schema(): void
    {
        $this->assertTrue(Schema::hasColumns('activity_logs', [
            'id',
            'user_id',
            'action',
            'subject_type',
            'subject_id',
            'old_values',
            'new_values',
            'context',
            'occurred_at',
            'created_at',
        ]));
        $this->assertFalse(Schema::hasColumn('activity_logs', 'updated_at'));
    }

    public function test_creating_a_user_writes_user_created_without_password(): void
    {
        $owner = User::factory()->owner()->create();

        $created = app(CreateUser::class)->execute($owner, [
            'name' => 'Ada Admin',
            'email' => 'ada@example.com',
            'password' => 'secret-pass',
            'role' => UserRole::Administrator->value,
            'active' => true,
        ]);

        $log = ActivityLog::query()->sole();

        $this->assertSame(ActivityAction::UserCreated, $log->action);
        $this->assertSame($owner->id, $log->user_id);
        $this->assertSame('User', $log->subject_type);
        $this->assertSame($created->id, $log->subject_id);
        $this->assertSame([
            'name' => 'Ada Admin',
            'email' => 'ada@example.com',
            'role' => UserRole::Administrator->value,
            'active' => true,
        ], $log->new_values);
        $this->assertArrayNotHasKey('password', $log->new_values ?? []);
        $this->assertStringNotContainsString('secret-pass', json_encode($log->getAttributes()));
    }

    public function test_role_change_writes_old_and_new_role(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();

        app(ChangeUserRole::class)->execute($owner, $cashier, UserRole::Administrator);

        $log = ActivityLog::query()->sole();

        $this->assertSame(ActivityAction::UserRoleChanged, $log->action);
        $this->assertSame($owner->id, $log->user_id);
        $this->assertSame($cashier->id, $log->subject_id);
        $this->assertSame(['role' => UserRole::Cashier->value], $log->old_values);
        $this->assertSame(['role' => UserRole::Administrator->value], $log->new_values);
        $this->assertSame(UserRole::Administrator, $cashier->fresh()->role);
    }

    public function test_status_change_writes_user_status_changed(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();

        Livewire::actingAs($owner)
            ->test(UserIndex::class)
            ->call('confirmDeactivate', $cashier->id)
            ->call('deactivate')
            ->assertHasNoErrors();

        $log = ActivityLog::query()->sole();

        $this->assertSame(ActivityAction::UserStatusChanged, $log->action);
        $this->assertSame(['active' => true], $log->old_values);
        $this->assertSame(['active' => false], $log->new_values);
        $this->assertFalse($cashier->fresh()->active);

        app(ChangeUserStatus::class)->execute($owner, $cashier->fresh(), true);

        $this->assertTrue($cashier->fresh()->active);
        $this->assertSame(2, ActivityLog::query()->count());
        $this->assertSame(
            ActivityAction::UserStatusChanged,
            ActivityLog::query()->latest('id')->first()->action,
        );
    }

    public function test_password_change_does_not_store_password_in_audit_json(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create([
            'email' => 'casey@example.com',
        ]);

        Livewire::actingAs($owner)
            ->test(UserForm::class, ['user' => $cashier])
            ->set('password', 'new-secret')
            ->set('password_confirmation', 'new-secret')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(0, ActivityLog::query()->count());

        $log = app(RecordActivity::class)->execute(
            actor: $owner,
            action: ActivityAction::UserCreated,
            subject: $cashier,
            newValues: [
                'email' => $cashier->email,
                'password' => 'new-secret',
                'password_confirmation' => 'new-secret',
            ],
        );

        $this->assertSame(['email' => $cashier->email], $log->new_values);
        $this->assertArrayNotHasKey('password', $log->new_values ?? []);
        $this->assertStringNotContainsString('new-secret', (string) json_encode($log->fresh()->getAttributes()));
    }

    public function test_failed_audit_rolls_back_the_user_write(): void
    {
        $owner = User::factory()->owner()->create();

        $this->mock(RecordActivity::class, function ($mock): void {
            $mock->shouldReceive('execute')->once()->andThrow(new RuntimeException('forced audit failure'));
        });

        try {
            app(CreateUser::class)->execute($owner, [
                'name' => 'Rolled Back',
                'email' => 'rollback@example.com',
                'password' => 'password',
                'role' => UserRole::Cashier->value,
            ]);
            $this->fail('Expected the audit failure to abort user creation.');
        } catch (RuntimeException $exception) {
            $this->assertSame('forced audit failure', $exception->getMessage());
        }

        $this->assertDatabaseMissing('users', [
            'email' => 'rollback@example.com',
        ]);
        $this->assertSame(0, ActivityLog::query()->count());
    }

    public function test_update_user_role_from_the_form_is_audited_in_one_transaction(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create([
            'name' => 'Casey',
            'email' => 'casey@example.com',
        ]);

        app(UpdateUser::class)->execute($owner, $cashier, [
            'name' => 'Casey',
            'email' => 'casey@example.com',
            'role' => UserRole::Administrator->value,
            'active' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $cashier->id,
            'role' => UserRole::Administrator->value,
        ]);
        $this->assertSame(1, ActivityLog::query()->count());
        $this->assertSame(ActivityAction::UserRoleChanged, ActivityLog::query()->sole()->action);
    }

    public function test_policy_denies_update_and_delete_for_every_role(): void
    {
        $log = ActivityLog::factory()->create();

        foreach ([
            User::factory()->owner()->create(),
            User::factory()->administrator()->create(),
            User::factory()->cashier()->create(),
        ] as $user) {
            $this->assertFalse($user->can('update', $log));
            $this->assertFalse($user->can('delete', $log));
            $this->assertFalse($user->can('forceDelete', $log));
            $this->assertFalse($user->can('create', ActivityLog::class));
        }

        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();

        $this->assertTrue($owner->can('viewAny', ActivityLog::class));
        $this->assertFalse($cashier->can('viewAny', ActivityLog::class));
    }

    public function test_future_activity_actions_exist_without_being_written(): void
    {
        $this->assertSame('STOCK_MANUAL_ADJUSTED', ActivityAction::StockManualAdjusted->value);
        $this->assertSame('STORE_SETTINGS_UPDATED', ActivityAction::StoreSettingsUpdated->value);
        $this->assertSame(0, ActivityLog::query()->whereIn('action', [
            ActivityAction::StockManualAdjusted,
            ActivityAction::StoreSettingsUpdated,
        ])->count());
    }

    public function test_there_is_no_http_endpoint_to_insert_audit_rows(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->post('/activity-logs', [
            'action' => ActivityAction::UserCreated->value,
        ])->assertNotFound();

        $this->assertSame(0, ActivityLog::query()->count());
    }
}
