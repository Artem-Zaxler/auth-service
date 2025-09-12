<?php

namespace App\Service;

use App\Dto\ReportRequestDto;
use App\Repository\ReportRepository;
use Psr\Log\LoggerInterface;

class ReportService
{
    public function __construct(
        private ReportRepository $reportRepository,
        private LoggerInterface $logger
    ) {}

    public function generateReport(ReportRequestDto $dto): array
    {
        if (empty($dto->reportType)) {
            $this->logger->warning('Report generation failed: report type not specified');
            throw new \InvalidArgumentException('Тип отчета не указан');
        }

        switch ($dto->reportType) {
            case 'user_activity':
                $report = $this->reportRepository->getUserActivityReport($dto);
                $this->logger->info('User activity report generated', ['report_type' => $dto->reportType]);
                return $report;
            case 'user_registrations':
                $report = $this->reportRepository->getUserRegistrationsReport($dto);
                $this->logger->info('User registrations report generated', ['report_type' => $dto->reportType]);
                return $report;
            default:
                $this->logger->warning('Report generation failed: unknown report type', ['report_type' => $dto->reportType]);
                throw new \InvalidArgumentException('Неизвестный тип отчета: ' . $dto->reportType);
        }
    }
}
