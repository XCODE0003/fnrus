<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * NOTE: the configured Laravel session cookie name (config('session.cookie'))
     * is added at runtime in the constructor — Laravel itself stores session
     * data as encrypted blobs on disk (config('session.encrypt') = true), so
     * the cookie carries only the raw session ID and must NOT be re-encrypted
     * on top. Otherwise EncryptCookies::handle() throws DecryptException when
     * it sees the raw 40-char ID and discards the cookie, dropping the session
     * across api/web group boundaries.
     *
     * @var array<int, string>
     */
    protected $except = [
        'locale',
        'session_token',
    ];

    public function __construct(\Illuminate\Contracts\Encryption\Encrypter $encrypter)
    {
        parent::__construct($encrypter);
        $sessionCookie = (string) config('session.cookie', '');
        if ($sessionCookie !== '' && !in_array($sessionCookie, $this->except, true)) {
            $this->except[] = $sessionCookie;
        }
    }
}
