<?php

namespace Tests\Feature;

use App\Models\EmployeePosition;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployeePositionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_positions_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('employee_positions', [
            'id',
            'code',
            'name',
            'category',
            'description',
            'is_active',
            'created_at',
            'updated_at',
            'deleted_at',
        ]));
    }

    public function test_employee_position_can_be_created_with_boolean_cast(): void
    {
        $position = EmployeePosition::factory()->create([
            'code' => 'JBT-KEPALA',
            'name' => 'Kepala Sekolah',
            'category' => EmployeePosition::CATEGORY_STRUCTURAL,
            'is_active' => true,
        ])->refresh();

        $this->assertSame('JBT-KEPALA', $position->code);
        $this->assertSame('Kepala Sekolah', $position->name);
        $this->assertSame(EmployeePosition::CATEGORY_STRUCTURAL, $position->category);
        $this->assertTrue($position->is_active);
    }

    public function test_employee_position_code_must_be_unique(): void
    {
        EmployeePosition::factory()->create(['code' => 'JBT-UNIK']);

        $this->expectException(QueryException::class);

        EmployeePosition::factory()->create(['code' => 'JBT-UNIK']);
    }

    public function test_employee_position_uses_soft_deletes(): void
    {
        $position = EmployeePosition::factory()->create();

        $position->delete();

        $this->assertNull(EmployeePosition::query()->find($position->getKey()));
        $this->assertNotNull(EmployeePosition::withTrashed()->find($position->getKey()));
        $this->assertSoftDeleted('employee_positions', ['id' => $position->getKey()]);
    }
}
