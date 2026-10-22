<?php

namespace Tests\Feature;

use Tests\TestCase;

class VersionEndpointTest extends TestCase
{
    public function test_version_endpoint_reports_build_revision_and_source_state(): void
    {
        $response = $this->getJson('/version');

        $response->assertOk()->assertJsonStructure([
            'commit',
            'source',
            'working_tree_clean',
        ]);

        $payload = $response->json();

        $this->assertIsString($payload['commit']);
        $this->assertContains($payload['source'], ['local', 'railway', 'unavailable']);
        $this->assertTrue(
            $payload['working_tree_clean'] === null
            || is_bool($payload['working_tree_clean']),
        );
    }
}
