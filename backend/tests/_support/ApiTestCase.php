<?php

namespace Tests\Support;

use App\Libraries\AuthContext;
use App\Libraries\JwtService;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Shared base for feature (CIUnitTestCase + FeatureTestTrait) tests that hit
 * the real dockerized MySQL with the real stored procedures loaded (§25).
 */
abstract class ApiTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace   = null; // migrate every namespace (our app/ migrations included)
    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $seed        = \App\Database\Seeds\UserSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        AuthContext::reset();
    }

    /**
     * Logs in as the given seeded user (see UserSeeder) and returns the
     * Authorization header value to pass to withHeaders().
     */
    protected function bearerFor(string $email): string
    {
        $user = (new UserModel())->where('email', $email)->first();

        $token = (new JwtService())->issueAccessToken(['sub' => $user->id, 'role' => $user->role]);

        return 'Bearer ' . $token;
    }
}
