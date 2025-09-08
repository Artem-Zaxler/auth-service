<?php

namespace App\Dto;

use Symfony\Component\HttpFoundation\Request;

class ReportRequestDto
{
    public ?\DateTimeImmutable $dateFrom = null;
    public ?\DateTimeImmutable $dateTo = null;
    public ?string $reportType = null;

    public static function fromRequest(Request $request): self
    {
        $dto = new self();

        $dateFrom = $request->request->get('date_from');
        $dateTo = $request->request->get('date_to');
        $reportType = $request->request->get('report_type');

        if (empty($reportType)) {
            throw new \InvalidArgumentException('Тип отчета не указан');
        }

        $dto->reportType = $reportType;

        if ($dateFrom) {
            $dto->dateFrom = new \DateTimeImmutable($dateFrom);
        }

        if ($dateTo) {
            $dto->dateTo = new \DateTimeImmutable($dateTo);
        }

        if ($dto->dateFrom && $dto->dateTo && $dto->dateFrom > $dto->dateTo) {
            throw new \InvalidArgumentException('Дата "от" не может быть позже даты "до"');
        }

        return $dto;
    }
}
