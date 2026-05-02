<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Behavioural tests for LoginThrottle middleware.
 *
 * We register a synthetic route that simulates the AuthController's response
 * shape (`{ok: bool, description: string}`) so the test does not depend on
 * the real auth stack / DB users.
 *
 * The middleware writes to `login_attempts` table; in this test that table
 * may not exist, so DB::table()->insert() throws and the middleware logs
 * a warning and continues. The middleware is designed to be DB-resilient.
 */
class LoginThrottleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin.login', [
            'max_attempts'   => 3,   // small numbers for a fast test
            'decay_minutes'  => 15,
            'lockout_minutes'=> 30,
            'captcha_after'  => 2,
            'fail_delay_ms'  => 0,   // disable the 2s sleep in tests
        ]);

        Cache::flush();

        Route::post('/_test/login', function () {
            $ok = request()->input('password') === 'correct';
            return response()->json([
                'ok' => $ok,
                'description' => $ok ? 'in' : 'bad',
            ]);
        })->middleware('login.throttle');
    }

    public function test_failed_attempts_get_captcha_required_flag_after_threshold(): void
    {
        $payload = ['username' => 'alice', 'password' => 'wrong'];

        // Attempt 1: no captcha flag yet (under threshold of 2).
        $r1 = $this->postJson('/_test/login', $payload);
        $r1->assertOk()->assertJsonMissing(['captcha_required' => true]);

        // Attempt 2: reaches captcha_after threshold.
        $r2 = $this->postJson('/_test/login', $payload);
        $r2->assertOk()->assertJson(['captcha_required' => true]);
    }

    public function test_account_locks_after_max_attempts(): void
    {
        $payload = ['username' => 'bob', 'password' => 'wrong'];

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/_test/login', $payload);
        }

        // Subsequent attempt must be 429 with locked=true.
        $r = $this->postJson('/_test/login', $payload);
        $r->assertStatus(429)->assertJson(['locked' => true]);
    }

    public function test_successful_login_clears_failure_counter(): void
    {
        $bad = ['username' => 'carol', 'password' => 'wrong'];
        $good = ['username' => 'carol', 'password' => 'correct'];

        $this->postJson('/_test/login', $bad);
        $this->postJson('/_test/login', $bad);

        $ok = $this->postJson('/_test/login', $good);
        $ok->assertOk()->assertJson(['ok' => true]);

        // After success the counter should be reset, so another bad attempt
        // is treated as the 1st (no captcha flag yet).
        $r = $this->postJson('/_test/login', $bad);
        $r->assertOk()->assertJsonMissing(['captcha_required' => true]);
    }
}
