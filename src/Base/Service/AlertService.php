<?php
declare(strict_types=1);

namespace App\Base\Service;

use App\Base\DTO\NewAlertDTO;
use App\Base\Interface\AlertServiceInterface;
use Monolog\Logger;

readonly class AlertService implements AlertServiceInterface
{

    public function __construct(
        private Logger $logger
    )
    {
    }

    public function addAlert(string $type, string $message): void
    {
        $newAlertDTO = new NewAlertDTO($type, $message);
        $this->addAlertToSession($newAlertDTO);
    }

    public function getAllAlerts(bool $clearAlertsAfterwards = true): array
    {
        $alerts = [];
        if(isset($_SESSION['alerts'])) {
            $alerts = $_SESSION['alerts'];
        }

        if($clearAlertsAfterwards) {
            $this->clearAlerts();
        }

        return $alerts;
    }

    public function clearAlerts(): void
    {
        $_SESSION['alerts'] = [];
    }

    private function addAlertToSession(NewAlertDTO $newAlertDTO): void
    {
        $_SESSION['alerts'][] = ['type' => $newAlertDTO->type, 'message' => $newAlertDTO->message];
    }

}
