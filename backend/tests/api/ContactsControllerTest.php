<?php

namespace Tests\Api;

use Tests\Support\ApiTestCase;

/**
 * @internal
 */
final class ContactsControllerTest extends ApiTestCase
{
    public function testCreateContactUnderACompany(): void
    {
        $auth = $this->withHeaders(['Authorization' => $this->bearerFor('arjun.rep@brightfield.test')])->withBodyFormat('json');

        $company   = $auth->post('api/v1/companies', ['name' => 'Contact Co'])->getJSON();
        $companyId = json_decode($company, true)['data']['id'];

        $contact = $auth->post('api/v1/contacts', [
            'companyId' => $companyId,
            'firstName' => 'Jane',
            'lastName'  => 'Doe',
            'email'     => 'jane@contactco.test',
        ]);
        $contact->assertStatus(201);
        $body = json_decode($contact->getJSON(), true);
        $this->assertSame('Jane', $body['data']['firstName']);
        $this->assertSame($companyId, $body['data']['companyId']);
    }

    public function testCreateContactUnderMissingCompanyIs404(): void
    {
        $auth = $this->withHeaders(['Authorization' => $this->bearerFor('arjun.rep@brightfield.test')])->withBodyFormat('json');

        $result = $auth->post('api/v1/contacts', [
            'companyId' => 999999,
            'firstName' => 'Ghost',
            'lastName'  => 'Company',
        ]);

        $result->assertStatus(404);
        $this->assertSame('COMPANY_NOT_FOUND', json_decode($result->getJSON(), true)['error']['code']);
    }

    public function testListFiltersByCompanyId(): void
    {
        $auth = $this->withHeaders(['Authorization' => $this->bearerFor('arjun.rep@brightfield.test')])->withBodyFormat('json');

        $companyAId = json_decode($auth->post('api/v1/companies', ['name' => 'Company A'])->getJSON(), true)['data']['id'];
        $companyBId = json_decode($auth->post('api/v1/companies', ['name' => 'Company B'])->getJSON(), true)['data']['id'];

        $auth->post('api/v1/contacts', ['companyId' => $companyAId, 'firstName' => 'A1', 'lastName' => 'Person']);
        $auth->post('api/v1/contacts', ['companyId' => $companyBId, 'firstName' => 'B1', 'lastName' => 'Person']);

        $result = $auth->get('api/v1/contacts?companyId=' . $companyAId);
        $body   = json_decode($result->getJSON(), true);

        $this->assertSame(1, $body['meta']['total']);
        $this->assertSame('A1', $body['data'][0]['firstName']);
    }
}
