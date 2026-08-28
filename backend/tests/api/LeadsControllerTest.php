<?php

namespace Tests\Api;

use Tests\Support\ApiTestCase;

/**
 * @internal
 */
final class LeadsControllerTest extends ApiTestCase
{
    private function createQualifiedLead($auth, string $firstName = 'Lee', string $companyName = 'Lee Co'): int
    {
        $create = $auth->post('api/v1/leads', [
            'firstName' => $firstName, 'lastName' => 'Adworth', 'companyName' => $companyName, 'source' => 'WEBSITE',
        ]);
        $id = json_decode($create->getJSON(), true)['data']['id'];

        $auth->put('api/v1/leads/' . $id, ['status' => 'CONTACTED']);
        $auth->put('api/v1/leads/' . $id, ['status' => 'QUALIFIED']);

        return $id;
    }

    public function testCreateAndBoardStatusMoves(): void
    {
        $auth = $this->actingAs('arjun.rep@brightfield.test');

        $id = $this->createQualifiedLead($auth);

        $show = $auth->get('api/v1/leads/' . $id);
        $this->assertSame('QUALIFIED', json_decode($show->getJSON(), true)['data']['status']);
    }

    public function testConvertCreatesCompanyContactAndDeal(): void
    {
        $auth = $this->actingAs('arjun.rep@brightfield.test');

        $id = $this->createQualifiedLead($auth, 'Sam', 'Sam Industries');

        $convert = $auth->post('api/v1/leads/' . $id . '/convert', ['dealName' => 'Sam Industries — New Business', 'dealValue' => 5000]);
        $convert->assertStatus(200);
        $body = json_decode($convert->getJSON(), true)['data'];

        $this->assertSame('Sam Industries — New Business', $body['deal']['name']);
        $this->assertSame('PROSPECTING', $body['deal']['stage']);
        $this->assertSame('Sam', $body['contact']['firstName']);

        // lead is now terminal
        $again = $auth->post('api/v1/leads/' . $id . '/convert', ['dealName' => 'x']);
        $again->assertStatus(409);
        $this->assertSame('ALREADY_CONVERTED', json_decode($again->getJSON(), true)['error']['code']);
    }

    public function testConvertingANonQualifiedLeadFails(): void
    {
        $auth = $this->actingAs('arjun.rep@brightfield.test');

        $create = $auth->post('api/v1/leads', ['firstName' => 'New', 'lastName' => 'Lead', 'source' => 'OTHER']);
        $id     = json_decode($create->getJSON(), true)['data']['id'];

        $result = $auth->post('api/v1/leads/' . $id . '/convert', ['dealName' => 'x']);
        $result->assertStatus(409);
        $this->assertSame('NOT_QUALIFIED', json_decode($result->getJSON(), true)['error']['code']);
    }

    public function testDisqualifyRequiresAReason(): void
    {
        $auth = $this->actingAs('arjun.rep@brightfield.test');

        $create = $auth->post('api/v1/leads', ['firstName' => 'Bad', 'lastName' => 'Fit', 'source' => 'OTHER']);
        $id     = json_decode($create->getJSON(), true)['data']['id'];

        $auth->post('api/v1/leads/' . $id . '/disqualify', [])->assertStatus(400);

        $ok = $auth->post('api/v1/leads/' . $id . '/disqualify', ['reason' => 'Out of budget']);
        $ok->assertStatus(200);
        $this->assertSame('DISQUALIFIED', json_decode($ok->getJSON(), true)['data']['status']);
    }
}
