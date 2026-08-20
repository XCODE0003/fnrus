<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExpirePendingOrdersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('database.connections.sqlite.prefix', 'new_');
        app('db')->purge('sqlite');
        app('db')->reconnect('sqlite');

        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('pid')->default(0);
            $table->unsignedBigInteger('bid')->default(0);
            $table->unsignedInteger('count_all')->default(0);
            $table->unsignedTinyInteger('status')->default(1);
            $table->unsignedInteger('expired_at')->default(0);
        });
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('count_all')->default(0);
        });
        Schema::create('materials', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('oid')->default(0);
            $table->unsignedBigInteger('bid')->default(0);
            $table->unsignedTinyInteger('status')->default(1);
        });
    }

    public function test_direct_expiration_helper_returns_orders_and_releases_stock_only_once(): void
    {
        Product::query()->insert(['id' => 10, 'count_all' => 3]);
        Order::query()->insert([
            'id' => 20,
            'pid' => 10,
            'bid' => 30,
            'count_all' => 2,
            'status' => 1,
            'expired_at' => time() - 1,
        ]);
        Material::query()->insert([
            'id' => 40,
            'oid' => 20,
            'bid' => 30,
            'status' => 4,
        ]);

        $expired = Order::changeStatusByExpiredAt();
        $alreadyExpired = Order::changeStatusByExpiredAt();

        $this->assertCount(1, $expired);
        $this->assertSame(20, (int) $expired->first()->id);
        $this->assertTrue($alreadyExpired->isEmpty());

        $this->assertSame(4, (int) Order::findOrFail(20)->status);
        $this->assertSame(5, (int) Product::findOrFail(10)->count_all);
        $material = Material::findOrFail(40);
        $this->assertSame(1, (int) $material->status);
        $this->assertSame(0, (int) $material->oid);
        $this->assertSame(0, (int) $material->bid);
    }

    public function test_expire_command_is_registered_and_reports_count(): void
    {
        Order::query()->insert([
            'id' => 21,
            'pid' => 0,
            'bid' => 30,
            'count_all' => 0,
            'status' => 1,
            'expired_at' => time() - 1,
        ]);

        $this->assertSame(0, Artisan::call('orders:expire'));
        $this->assertStringContainsString('Expired 1 pending order(s).', Artisan::output());
        $this->assertSame(4, (int) Order::findOrFail(21)->status);
    }
}
