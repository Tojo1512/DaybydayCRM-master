<?php

namespace App\Api\v1\Controllers;

use App\Api\v1\Services\Dashboard\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends ApiController
{
    public $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Récupère toutes les statistiques détaillées pour le dashboard
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        $stats = $this->dashboardService->getStats();
        
        return $this->respond([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Récupère un résumé simplifié des statistiques pour le dashboard
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSummary()
    {
        $summary = $this->dashboardService->getSummary();
        
        return $this->respond([
            'success' => true,
            'data' => $summary
        ]);
    }
    
    /**
     * Récupère spécifiquement les statistiques des offres
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOffers()
    {
        $stats = $this->dashboardService->getStats();
        
        return $this->respond([
            'success' => true,
            'data' => $stats['offers']
        ]);
    }
} 