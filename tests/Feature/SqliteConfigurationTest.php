<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SqliteConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sqlite_integrity_and_locking_options_are_configured(): void
    {
        $sqlite = config('database.connections.sqlite');

        $this->assertTrue($sqlite['foreign_key_constraints']);
        $this->assertSame(5000, $sqlite['busy_timeout']);
        $this->assertSame('WAL', $sqlite['journal_mode']);
        $this->assertSame('NORMAL', $sqlite['synchronous']);
        $this->assertSame('IMMEDIATE', $sqlite['transaction_mode']);
        $this->assertSame(1, DB::scalar('PRAGMA foreign_keys'));
        $this->assertSame(5000, DB::scalar('PRAGMA busy_timeout'));
    }
}
