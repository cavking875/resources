<?php

declare(strict_types=1);

namespace App\Http;

use App\Dashboard\MonthlyDemandService;
use App\Dashboard\RoleGapService;
use App\Finance\FinancialModelService;
use App\Report\AiSummaryService;
use App\Report\CsvExportService;
use PDO;

final class InsightsController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function roleGap(?string $month): array
    {
        $service = new RoleGapService($this->pdo);

        return $service->forMonth($month);
    }

    public function monthlyDemand(?string $startMonth, int $months): array
    {
        $service = new MonthlyDemandService($this->pdo);

        return $service->range($startMonth, $months);
    }

    public function aiSummary(?string $startMonth, int $months): array
    {
        $service = new AiSummaryService($this->pdo);

        return $service->boardSummary($startMonth, $months);
    }

    public function financialSummary(float $targetPercent): array
    {
        $service = new FinancialModelService($this->pdo);

        return $service->portfolioSummary($targetPercent);
    }

    public function roleGapCsv(?string $month): string
    {
        $service = new CsvExportService($this->pdo);

        return $service->roleGapCsv($month);
    }

    public function monthlyDemandCsv(?string $startMonth, int $months): string
    {
        $service = new CsvExportService($this->pdo);

        return $service->monthlyDemandCsv($startMonth, $months);
    }
}
