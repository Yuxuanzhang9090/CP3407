<?php
declare(strict_types=1);

function createTrackingHistoryRecord(int $orderId, string $status, string $updatedBy = 'system', string $notes = ''): array
{
    return [
        'order_id' => $orderId,
        'status' => $status,
        'updated_by' => $updatedBy,
        'notes' => $notes
    ];
}