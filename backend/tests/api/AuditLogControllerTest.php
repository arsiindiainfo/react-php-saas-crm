<?php

namespace Tests\Api;

use Tests\Support\ApiTestCase;

/**
 * @internal
 */
final class AuditLogControllerTest extends ApiTestCase
{
    public function testAdminSeesAuditTrailAfterActions(): void
    {
        $rep   = $this->withHeaders(['Authorization' => $this->bearerFor('arjun.rep@brightfield.test')])->withBodyFormat('json');
        $admin = $this->withHeaders(['Authorization' => $this->bearerFor('admin@brightfield.test')])->withBodyFormat('json');

        $rep->post('api/v1/companies', ['name' => 'Audited Co']);

        $log = $admin->get('api/v1/audit-logs?action=COMPANY_CREATED');
        $log->assertStatus(200);
        $body = json_decode($log->getJSON(), true);

        $this->assertGreaterThanOrEqual(1, $body['meta']['total']);
        $this->assertSame('COMPANY_CREATED', $body['data'][0]['action']);
    }

    public function testNonAdminCannotReadTheAuditLog(): void
    {
        $rep = $this->withHeaders(['Authorization' => $this->bearerFor('arjun.rep@brightfield.test')])->withBodyFormat('json');

        $rep->get('api/v1/audit-logs')->assertStatus(403);
    }
}
