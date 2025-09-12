<?php

namespace App\Dto;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class ReportRequestDto
{
    #[Assert\DateTime(format: 'Y-m-d', message: "Date from must be in format YYYY-MM-DD")]
    public ?\DateTimeImmutable $dateFrom = null;

    #[Assert\DateTime(format: 'Y-m-d', message: "Date to must be in format YYYY-MM-DD")]
    public ?\DateTimeImmutable $dateTo = null;

    #[Assert\NotBlank(message: "Report type is required")]
    #[Assert\Choice(
        choices: ['user_activity', 'user_registrations'],
        message: "Invalid report type. Must be one of: user_activity, user_registrations"
    )]
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
            try {
                $dto->dateFrom = new \DateTimeImmutable($dateFrom);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Неверный формат даты "от"');
            }
        }

        if ($dateTo) {
            try {
                $dto->dateTo = new \DateTimeImmutable($dateTo);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Неверный формат даты "до"');
            }
        }

        if ($dto->dateFrom && $dto->dateTo && $dto->dateFrom > $dto->dateTo) {
            throw new \InvalidArgumentException('Дата "от" не может быть позже даты "до"');
        }

        return $dto;
    }
}
