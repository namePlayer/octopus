<?php
declare(strict_types=1);

namespace App\Base\PlatesExtension;

use App\Base\Interface\AlertServiceInterface;
use App\Base\Interface\TranslationInterface;
use App\Software;
use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;

class AlertsPlatesExtension implements ExtensionInterface
{

    private Engine $engine;

    public function __construct(
        private readonly AlertServiceInterface $alertService,
        private readonly TranslationInterface $translator,
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
            $message = $alert['message'];
            if(str_starts_with($message, Software::ALERT_TRANSLATION_INDICATOR)) {
                $message = str_replace(Software::ALERT_TRANSLATION_INDICATOR, '', $message);
                $alert['message'] = $this->translator->translate($message);
            }
            $output .= $this->engine->render(Software::ALERT_DEFAULT_TEMPLATE, ['alert' => $alert]);
        }

        return $output;
    }

}
