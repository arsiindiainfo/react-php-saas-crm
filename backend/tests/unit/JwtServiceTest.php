<?php

namespace Tests\Unit;

use App\Libraries\JwtService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class JwtServiceTest extends CIUnitTestCase
{
    public function testIssuedTokenDecodesBackToItsClaims(): void
    {
        $service = new JwtService();
        $token   = $service->issueAccessToken(['sub' => 42, 'role' => 'ADMIN']);

        $decoded = $service->decode($token);

        $this->assertNotNull($decoded);
        $this->assertSame(42, $decoded['sub']);
        $this->assertSame('ADMIN', $decoded['role']);
    }

    public function testTamperedTokenFailsToDecode(): void
    {
        $service = new JwtService();
        $token   = $service->issueAccessToken(['sub' => 1, 'role' => 'SALES_REP']);

        $this->assertNull($service->decode($token . 'tampered'));
    }

    public function testGarbageStringFailsToDecode(): void
    {
        $this->assertNull((new JwtService())->decode('not-a-jwt'));
    }
}
