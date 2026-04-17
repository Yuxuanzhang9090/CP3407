<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/tracking_record_helpers.php';

final class TrackingRecordTest extends TestCase
{
    public function testCreateTrackingHistoryRecord(): void
    {
        $record = createTrackingHistoryRecord(101, 'preparing', 'system', 'Kitchen started preparing.');

        $this->assertEquals(101, $record['order_id']);
        $this->assertEquals('preparing', $record['status']);
        $this->assertEquals('system', $record['updated_by']);
        $this->assertEquals('Kitchen started preparing.', $record['notes']);
    }
}