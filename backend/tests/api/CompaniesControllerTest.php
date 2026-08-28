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
        $auth = $this->withHeaders(['Authorization' => $this->bearerFor('arjun.rep@brightfield.test')])->withBodyFormat('json');

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
        $auth = $this->withHeaders(['Authorization' => $this->bearerFor('arjun.rep@brightfield.test')])->withBodyFormat('json');

        $auth->post('api/v1/companies', ['name' => 'Acme Co'])->assertStatus(201);
        $second = $auth->post('api/v1/companies', ['name' => 'Acme Co']);

        $second->assertStatus(409);
        $this->assertSame('DUPLICATE_NAME', json_decode($second->getJSON(), true)['error']['code']);
    }

    public function testListSupportsSearchAndPagination(): void
    {
        $auth = $this->withHeaders(['Authorization' => $this->bearerFor('arjun.rep@brightfield.test')])->withBodyFormat('json');

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
        $arjun = $this->withHeaders(['Authorization' => $this->bearerFor('arjun.rep@brightfield.test')])->withBodyFormat('json');
        $meera = $this->withHeaders(['Authorization' => $this->bearerFor('meera.rep@brightfield.test')])->withBodyFormat('json');

        $create = $arjun->post('api/v1/companies', ['name' => "Arjun's Exclusive Co"]);
        $id     = json_decode($create->getJSON(), true)['data']['id'];

        $meera->get('api/v1/companies/' . $id)->assertStatus(404);
    }

    public function testManagerCanSeeTheirReportsCompany(): void
    {
        $arjun  = $this->withHeaders(['Authorization' => $this->bearerFor('arjun.rep@brightfield.test')])->withBodyFormat('json');
        $priya  = $this->withHeaders(['Authorization' => $this->bearerFor('priya.manager@brightfield.test')])->withBodyFormat('json');

        $create = $arjun->post('api/v1/companies', ['name' => "Arjun's Team Co"]);
        $id     = json_decode($create->getJSON(), true)['data']['id'];

        $priya->get('api/v1/companies/' . $id)->assertStatus(200);
    }

    public function testUpdateAndDelete(): void
    {
        $auth   = $this->withHeaders(['Authorization' => $this->bearerFor('arjun.rep@brightfield.test')])->withBodyFormat('json');
        $create = $auth->post('api/v1/companies', ['name' => 'Delete Me Inc']);
        $id     = json_decode($create->getJSON(), true)['data']['id'];

        $auth->put('api/v1/companies/' . $id, ['industry' => 'Retail'])->assertStatus(200);
        $auth->delete('api/v1/companies/' . $id)->assertStatus(200);
        $auth->get('api/v1/companies/' . $id)->assertStatus(404);
    }
}
