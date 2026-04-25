<?php

namespace Tests\Unit;

use App\Models\ProductionRecord;
use PHPUnit\Framework\TestCase;

class ProductionRecordTest extends TestCase
{
    public function test_it_correctly_calculates_is_on_target_when_productivity_exceeds_target(): void
    {
        $record = tap(new ProductionRecord, function ($r) {
            $r->productivity = 95.5;
            $r->target_productivity = 90.0;
        });

        $this->assertTrue($record->is_on_target);
    }

    public function test_it_correctly_calculates_is_on_target_when_productivity_is_below_target(): void
    {
        $record = tap(new ProductionRecord, function ($r) {
            $r->productivity = 85.0;
            $r->target_productivity = 90.0;
        });

        $this->assertFalse($record->is_on_target);
    }

    public function test_is_on_target_returns_false_if_values_are_null(): void
    {
        $record = tap(new ProductionRecord, function ($r) {
            $r->productivity = null;
            $r->target_productivity = 90.0;
        });

        $this->assertFalse($record->is_on_target);
    }
}
