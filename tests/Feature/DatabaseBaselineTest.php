<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseBaselineTest extends TestCase
{
    use RefreshDatabase;

    public function test_environment_using_mysql(): void
    {
        $this->assertSame('mysql', config('database.default'));
    }

    public function test_tabel_dasar_ada(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('migrations'));
    }
}
