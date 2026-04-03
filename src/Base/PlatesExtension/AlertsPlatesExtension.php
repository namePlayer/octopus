<?php
declare(strict_types=1);

namespace App\Base\PlatesExtension;

use App\Base\Interface\AlertServiceInterface;
use App\Software;
use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;

class AlertsPlatesExtension implements ExtensionInterface
{

    private Engine $engine;

    public function __construct(
        private readonly AlertServiceInterface $alertService,
    )
    {
    }

    public function register(Engine $engine): void
    {
        $this->engine = $engine;
        $engine->registerFunction('outputAlerts', [$this, 'outputAlerts']);
    }

    public function outputAlerts(): string
    {
        $output = '';
        foreach ($this->alertService->getAllAlerts() as $alert) {
            $output .= $this->engine->render(Software::ALERT_DEFAULT_TEMPLATE, ['alert' => $alert]);
        }

        return $output;
    }

}
