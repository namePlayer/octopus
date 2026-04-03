<?php
declare(strict_types=1);

namespace App\Base\Interface;

interface AlertServiceInterface
{

    public function addAlert(string $type, string $message): void;

    public function getAllAlerts(bool $clearAlertsAfterwards = true): array;

    public function clearAlerts(): void;

}
