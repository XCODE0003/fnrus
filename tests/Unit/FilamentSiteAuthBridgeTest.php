<?php

namespace Tests\Unit;

use App\Http\Middleware\FilamentSiteAuthBridge;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class FilamentSiteAuthBridgeTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = (string) config('database.default');
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('role_id')->default(0);
            $table->boolean('is_ban')->default(false);
        });
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        config(['database.default' => $this->originalConnection]);

        parent::tearDown();
    }

    public function test_legacy_admin_schema_does_not_break_session_validation(): void
    {
        DB::table('users')->insert([
            'id' => 7,
            'role_id' => 1,
            'is_ban' => 0,
        ]);

        $bridge = new FilamentSiteAuthBridge(app('auth'));
        $method = new ReflectionMethod($bridge, 'adminAccessStillValid');

        $this->assertTrue($method->invoke($bridge, Request::create('/admin'), 7));
    }

    public function test_access_control_columns_are_enforced_when_present(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedInteger('admin_blocked_at')->nullable();
            $table->unsignedInteger('admin_sessions_revoked_at')->nullable();
        });

        DB::table('users')->insert([
            'id' => 8,
            'role_id' => 1,
            'is_ban' => 0,
            'admin_blocked_at' => time(),
        ]);

        $bridge = new FilamentSiteAuthBridge(app('auth'));
        $method = new ReflectionMethod($bridge, 'adminAccessStillValid');

        $this->assertFalse($method->invoke($bridge, Request::create('/admin'), 8));
    }
}
