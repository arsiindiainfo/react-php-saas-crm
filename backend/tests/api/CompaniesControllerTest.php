<?php

namespace Tests\Api;

use Tests\Support\ApiTestCase;

/**
 * @internal
 */
final class CompaniesControllerTest extends ApiTestCase
{
    public function testCreateAndFetchACompany(): void
    {
        $auth = $this->actingAs('arjun.rep@brightfield.test');

        $create = $auth->post('api/v1/companies', ['name' => 'NovaTrail Logistics', 'industry' => 'Logistics']);
        $create->assertStatus(201);
        $id = json_decode($create->getJSON(), true)['data']['id'];

        $show = $auth->get('api/v1/companies/' . $id);
        $show->assertStatus(200);
        $body = json_decode($show->getJSON(), true);
        $this->assertSame('NovaTrail Logistics', $body['data']['name']);
        $this->assertSame('PROSPECT', $body['data']['status']);
    }

    public function testDuplicateNameIsRejected(): void
    {
        $auth = $this->actingAs('arjun.rep@brightfield.test');

        $auth->post('api/v1/companies', ['name' => 'Acme Co'])->assertStatus(201);
        $second = $auth->post('api/v1/companies', ['name' => 'Acme Co']);

        $second->assertStatus(409);
        $this->assertSame('DUPLICATE_NAME', json_decode($second->getJSON(), true)['error']['code']);
    }

    public function testListSupportsSearchAndPagination(): void
    {
        $auth = $this->actingAs('arjun.rep@brightfield.test');

        foreach (['Alpha Corp', 'Beta Corp', 'Gamma Industries'] as $name) {
            $auth->post('api/v1/companies', ['name' => $name])->assertStatus(201);
        }

        $result = $auth->get('api/v1/companies?search=Corp&sort=name&direction=asc&page=1&limit=10');
        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertSame(2, $body['meta']['total']);
        $this->assertSame('Alpha Corp', $body['data'][0]['name']);
    }

    public function testRepCannotSeeAnotherRepsCompany(): void
    {
        $arjun = $this->actingAs('arjun.rep@brightfield.test');
        $meera = $this->actingAs('meera.rep@brightfield.test');

        $create = $arjun->post('api/v1/companies', ['name' => "Arjun's Exclusive Co"]);
        $id     = json_decode($create->getJSON(), true)['data']['id'];

        $meera->get('api/v1/companies/' . $id)->assertStatus(404);
    }

    public function testManagerCanSeeTheirReportsCompany(): void
    {
        $arjun = $this->actingAs('arjun.rep@brightfield.test');
        $priya = $this->actingAs('priya.manager@brightfield.test');

        $create = $arjun->post('api/v1/companies', ['name' => "Arjun's Team Co"]);
        $id     = json_decode($create->getJSON(), true)['data']['id'];

        $priya->get('api/v1/companies/' . $id)->assertStatus(200);
    }

    public function testUpdateAndDelete(): void
    {
        $auth   = $this->actingAs('arjun.rep@brightfield.test');
        $create = $auth->post('api/v1/companies', ['name' => 'Delete Me Inc']);
        $id     = json_decode($create->getJSON(), true)['data']['id'];

        $auth->put('api/v1/companies/' . $id, ['industry' => 'Retail'])->assertStatus(200);
        $auth->delete('api/v1/companies/' . $id)->assertStatus(200);
        $auth->get('api/v1/companies/' . $id)->assertStatus(404);
    }
}
