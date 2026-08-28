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
        $rep   = $this->actingAs('arjun.rep@brightfield.test');
        $admin = $this->actingAs('admin@brightfield.test');

        $rep->post('api/v1/companies', ['name' => 'Audited Co']);

        $log = $admin->get('api/v1/audit-logs?action=COMPANY_CREATED');
        $log->assertStatus(200);
        $body = json_decode($log->getJSON(), true);

        $this->assertGreaterThanOrEqual(1, $body['meta']['total']);
        $this->assertSame('COMPANY_CREATED', $body['data'][0]['action']);
    }

    public function testNonAdminCannotReadTheAuditLog(): void
    {
        $rep = $this->actingAs('arjun.rep@brightfield.test');

        $rep->get('api/v1/audit-logs')->assertStatus(403);
    }
}
