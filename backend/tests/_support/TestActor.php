<?php

namespace Tests\Support;

use CodeIgniter\Test\TestResponse;

/**
 * Wraps a bearer token so multiple "actors" can be interleaved safely within
 * one test method. FeatureTestTrait::withHeaders() mutates shared state on
 * the TestCase itself and returns $this — so `$a = $this->withHeaders(...);
 * $b = $this->withHeaders(...);` makes $a and $b THE SAME OBJECT with $b's
 * headers, silently making every later "$a->post(...)" call run as $b. This
 * re-applies the actor's own header immediately before every request instead
 * of once up front, so two actors can be freely interleaved.
 */
final class TestActor
{
    public function __construct(
        private readonly ApiTestCase $testCase,
        private readonly string $bearer,
    ) {
    }

    public function get(string $path, ?array $params = null): TestResponse
    {
        return $this->apply()->get($path, $params);
    }

    public function post(string $path, ?array $params = null): TestResponse
    {
        return $this->apply()->post($path, $params);
    }

    public function put(string $path, ?array $params = null): TestResponse
    {
        return $this->apply()->put($path, $params);
    }

    public function delete(string $path, ?array $params = null): TestResponse
    {
        return $this->apply()->delete($path, $params);
    }

    private function apply(): ApiTestCase
    {
        return $this->testCase->withHeaders(['Authorization' => $this->bearer])->withBodyFormat('json');
    }
}
