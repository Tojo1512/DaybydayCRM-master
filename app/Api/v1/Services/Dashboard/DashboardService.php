<?php

namespace App\Api\v1\Services\Dashboard;

class DashboardService
{
    private  $clientService;
    private  $projectService;
    private  $taskService;
    private  $offerService;
    private  $invoiceService;
    private  $paymentService;
    
    public function __construct(
        ClientService $clientService,
        ProjectService $projectService,
        TaskService $taskService,
        OfferService $offerService,
        InvoiceService $invoiceService,
        PaymentService $paymentService
    ) {
        $this->clientService = $clientService;
        $this->projectService = $projectService;
        $this->taskService = $taskService;
        $this->offerService = $offerService;
        $this->invoiceService = $invoiceService;
        $this->paymentService = $paymentService;
    }
    
    /**
     * Récupère toutes les statistiques pour le dashboard
     */
    public function getStats(): array
    {
        return [
            'clients' => $this->clientService->getStats(),
            'projects' => $this->projectService->getStats(),
            'tasks' => $this->taskService->getStats(),
            'offers' => $this->offerService->getStats(),
            'invoices' => $this->invoiceService->getStats(),
            'payments' => $this->paymentService->getStats(),
        ];
    }
    
    /**
     * Récupère un résumé simplifié pour le dashboard
     */
    public function getSummary(): array
    {
        return [
            'clients_total' => $this->clientService->getTotalCount(),
            'projects_total' => $this->projectService->getTotalCount(),
            'tasks_total' => $this->taskService->getTotalCount(),
            'tasks_active' => $this->taskService->getActiveCount(),
            'offers_total' => $this->offerService->getTotalCount(),
            'offers_total_value' => $this->offerService->getTotalValue(),
            'invoices_total_value' => $this->invoiceService->getTotalValue(),
            'invoices_unpaid_value' => $this->invoiceService->getUnpaidValue(),
            'payments_total_value' => $this->paymentService->getTotalValue(),
        ];
    }
} 