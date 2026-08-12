<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Route;
use PDOException;
use Tests\TestCase;

class DatabaseBusyErrorTest extends TestCase
{
    public function test_sqlite_locked_error_has_a_safe_retry_response(): void
    {
        Route::get('/_test/database-busy', function (): never {
            throw new QueryException(
                'sqlite',
                'update meter_readings set status = ?',
                ['RECORDED'],
                new PDOException('SQLSTATE[HY000]: General error: 5 database is locked'),
            );
        });

        $this->get('/_test/database-busy')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '2')
            ->assertSeeText('Database đang bận')
            ->assertDontSee('update meter_readings');
    }
}
