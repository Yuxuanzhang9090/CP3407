<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/app_helpers.php';

final class AppHelpersTest extends TestCase
{
    public function testCp3407Money(): void
    {
        $this->assertEquals('SGD 12.50', cp3407_money(12.5));
    }

    public function testCp3407Label(): void
    {
        $this->assertEquals('Ready For Pickup', cp3407_label('ready_for_pickup'));
    }

    public function testCp3407BadgeClass(): void
    {
        $this->assertEquals('success', cp3407_badge_class('paid'));
        $this->assertEquals('danger', cp3407_badge_class('cancelled'));
        $this->assertEquals('secondary', cp3407_badge_class('unknown'));
    }
}