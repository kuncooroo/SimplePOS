<?php



declare(strict_types=1);



namespace Tests\Feature\Install;



use App\Actions\Install\WriteInitialStoreSettings;

use App\Actions\Install\WriteInstallLock;

use App\Enums\UserRole;

use App\Models\User;

use App\Support\Install\InstallState;

use Illuminate\Foundation\Testing\RefreshDatabase;

use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Hash;

use Tests\TestCase;



class InstallerLockTest extends TestCase

{

    use RefreshDatabase;



    private string $tempDir;



    protected function setUp(): void

    {

        parent::setUp();



        config(['installer.installed' => false]);



        $this->tempDir = sys_get_temp_dir().'/simplepos-lock-'.uniqid('', true);

        mkdir($this->tempDir);



        file_put_contents($this->tempDir.'/.env', implode(PHP_EOL, [

            'APP_NAME=SimplePOS',

            'APP_ENV=testing',

            'APP_KEY=base64:'.base64_encode(random_bytes(32)),

            'APP_DEBUG=false',

            'APP_URL=http://localhost',

            'DB_CONNECTION=sqlite',

            'DB_DATABASE=:memory:',

        ]).PHP_EOL);



        config(['installer.env_path' => $this->tempDir.'/.env']);

    }



    protected function tearDown(): void

    {

        if (is_file($path = storage_path('app/installed'))) {

            unlink($path);

        }



        if (is_file($this->tempDir.'/.env')) {

            unlink($this->tempDir.'/.env');

        }



        if (is_dir($this->tempDir)) {

            rmdir($this->tempDir);

        }



        parent::tearDown();

    }



    public function test_finish_writes_lock_and_redirects_to_login_with_flash(): void

    {

        InstallState::storePendingOwner([
            'name' => 'Store Owner',
            'email' => 'owner@example.test',
            'password_hash' => Hash::make('owner-secret'),
        ]);

        app(WriteInitialStoreSettings::class)->execute('Demo Store');
        $this->prepareFinishStep();

        $this->post(route('install.complete.store'))

            ->assertRedirect(route('login'))

            ->assertSessionHas('installation_complete', true);



        $this->assertTrue(InstallState::hasInstallLock());

        $this->assertTrue(InstallState::isInstalled());

        $this->assertSame('owner@example.test', User::query()->where('role', UserRole::Owner)->value('email'));



        $envContents = (string) file_get_contents($this->tempDir.'/.env');

        $this->assertStringContainsString('APP_INSTALLED=true', $envContents);



        $this->get(route('login'))

            ->assertOk()

            ->assertSee('Installation complete. Sign in as Owner.', false);

    }



    public function test_owner_can_sign_in_after_installation(): void

    {

        InstallState::storePendingOwner([
            'name' => 'Store Owner',
            'email' => 'owner@example.test',
            'password_hash' => Hash::make('owner-secret'),
        ]);

        app(WriteInitialStoreSettings::class)->execute('Demo Store');
        $this->prepareFinishStep();

        $this->post(route('install.complete.store'))->assertRedirect(route('login'));



        $this->post(route('login.store'), [

            'email' => 'owner@example.test',

            'password' => 'owner-secret',

        ])->assertRedirect(route('dashboard'));

    }



    public function test_install_routes_return_not_found_after_lock(): void

    {

        $this->seedInstalledOwnerWithLock();



        $this->get(route('install.welcome'))->assertNotFound();

        $this->post(route('install.welcome.continue'))->assertNotFound();

        $this->get(route('install.complete'))->assertNotFound();

        $this->post(route('install.complete.store'))->assertNotFound();

    }



    public function test_install_post_returns_not_found_with_csrf_token_after_lock(): void

    {

        $this->seedInstalledOwnerWithLock();



        $this->withSession(['_token' => 'test-token'])

            ->post(route('install.complete.store'), ['_token' => 'test-token'])

            ->assertNotFound();

    }



    public function test_install_routes_return_not_found_when_owner_exists_without_lock(): void

    {

        User::factory()->owner()->create([

            'email' => 'owner@example.test',

        ]);



        $this->get(route('install.welcome'))->assertNotFound();

        $this->post(route('install.owner.store'), [

            'name' => 'Another Owner',

            'email' => 'other@example.test',

            'password' => 'password123',

            'password_confirmation' => 'password123',

        ])->assertNotFound();

    }



    public function test_deleting_lock_file_still_blocks_installer_when_owner_exists(): void

    {

        $this->seedInstalledOwnerWithLock();



        unlink(storage_path('app/installed'));



        $this->assertFalse(InstallState::hasInstallLock());

        $this->assertTrue(InstallState::isInstalled());

        $this->get(route('install.welcome'))->assertNotFound();

    }



    public function test_write_install_lock_action_does_not_invoke_migrate_fresh(): void

    {

        InstallState::storePendingOwner([

            'name' => 'Store Owner',

            'email' => 'owner@example.test',

            'password_hash' => Hash::make('owner-secret'),

        ]);



        app(WriteInitialStoreSettings::class)->execute('Demo Store');



        Artisan::spy();



        app(WriteInstallLock::class)->execute();



        Artisan::shouldNotHaveReceived('call', ['migrate:fresh', \Mockery::any()]);

        Artisan::shouldNotHaveReceived('call', ['db:wipe', \Mockery::any()]);

    }



    private function seedInstalledOwnerWithLock(): void
    {
        app(WriteInitialStoreSettings::class)->execute('Demo Store');

        User::factory()->owner()->create([
            'email' => 'owner@example.test',
        ]);

        app(WriteInstallLock::class)->execute();
    }

    private function prepareFinishStep(): void
    {
        InstallState::advanceToDatabase();
        InstallState::markEnvironmentWritten();
        InstallState::markMigrationsCompleted();
        InstallState::advanceToComplete();
    }
}

