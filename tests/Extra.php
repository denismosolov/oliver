<?php

declare(strict_types=1);

namespace Oliver\Tests;

/**
 * Add to TestCase
 */
trait Extra
{
    /**
     * Make sure the response structure is okay
     */
    private function assertStructure(array $result): void
    {
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('text', $result['response']);
        $this->assertArrayHasKey('session_state', $result);
        $this->assertArrayHasKey('text', $result['session_state']);
        $this->assertArrayHasKey('context', $result['session_state']);
    }
}
