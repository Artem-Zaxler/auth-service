<?php

namespace App\Service;

use App\Dto\ReportRequestDto;
use App\Repository\ReportRepository;

class ReportService
{
    public function __construct(
        private ReportRepository $reportRepository
    ) {}

    public function generateReport(ReportRequestDto $dto): array
    {
        if (empty($dto->reportType)) {
            throw new \InvalidArgumentException('Тип отчета не указан');
        }

        switch ($dto->reportType) {
            case 'user_activity':
                return $this->reportRepository->getUserActivityReport($dto);
            case 'user_registrations':
                return $this->reportRepository->getUserRegistrationsReport($dto);
            default:
                throw new \InvalidArgumentException('Неизвестный тип отчета: ' . $dto->reportType);
        }
    }
}
