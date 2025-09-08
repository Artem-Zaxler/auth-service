<?php

namespace App\Controller;

use App\Service\ReportService;
use App\Dto\ReportRequestDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ReportController extends AbstractController
{
    public function __construct(
        private ReportService $reportService
    ) {}

    #[Route(path: "/admin/reports", name: "admin_reports", methods: ["GET", "POST"])]
    public function reports(Request $request): Response
    {
        $results = [];
        $formData = [
            'report_type' => '',
            'date_from' => '',
            'date_to' => ''
        ];

        if ($request->isMethod('POST')) {
            try {
                $dto = ReportRequestDto::fromRequest($request);
                $results = $this->reportService->generateReport($dto);

                $formData['report_type'] = $dto->reportType;
                $formData['date_from'] = $dto->dateFrom ? $dto->dateFrom->format('Y-m-d') : '';
                $formData['date_to'] = $dto->dateTo ? $dto->dateTo->format('Y-m-d') : '';

                $this->addFlash('success', 'Отчет успешно сгенерирован');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());

                $formData['report_type'] = $request->request->get('report_type', '');
                $formData['date_from'] = $request->request->get('date_from', '');
                $formData['date_to'] = $request->request->get('date_to', '');
            }
        }

        return $this->render("admin/reports/index.html.twig", [
            'results' => $results,
            'formData' => $formData,
            'reportTypes' => [
                'user_activity' => 'Активность пользователей',
                'user_registrations' => 'Регистрации пользователей'
            ]
        ]);
    }
}
