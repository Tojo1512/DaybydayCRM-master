<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DiscountSetting;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DiscountSettingController extends Controller
{
    /**
     * Récupère le paramètre de remise actif
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGlobalDiscount()
    {
        $setting = DiscountSetting::getActive();
        
        if (!$setting) {
            $setting = new DiscountSetting([
                'global_discount_rate' => 0,
                'start_date' => Carbon::now(),
                'end_date' => null
            ]);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'global_discount_rate' => $setting->global_discount_rate,
                'start_date' => $setting->start_date ? $setting->start_date->format('Y-m-d') : null,
                'end_date' => $setting->end_date ? $setting->end_date->format('Y-m-d') : null,
            ]
        ]);
    }
    
    /**
     * Met à jour le paramètre de remise
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateGlobalDiscount(Request $request)
    {
        $validated = $request->validate([
            'global_discount_rate' => 'required|numeric|min:0|max:100',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        
        $setting = new DiscountSetting($validated);
        $setting->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Taux de remise mis à jour avec succès'
        ]);
    }
    
    /**
     * Insère une nouvelle remise et termine la précédente
     * Cette méthode gère la logique où la remise sans date de fin est la remise active
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function insertNewDiscount(Request $request)
    {
        $validated = $request->validate([
            'global_discount_rate' => 'required|numeric|min:0|max:100',
        ]);
        
        // Trouver la remise active sans date de fin
        $currentDiscount = DiscountSetting::getActiveInfiniteDiscount();
        
        // Si une remise active existe, mettre à jour sa date de fin
        if ($currentDiscount) {
            $currentDiscount->end_date = Carbon::now();
            $currentDiscount->save();
        }
        
        // Créer une nouvelle remise sans date de fin
        $newDiscount = new DiscountSetting([
            'global_discount_rate' => $validated['global_discount_rate'],
            'start_date' => Carbon::now(),
            'end_date' => null
        ]);
        
        $newDiscount->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Nouvelle remise créée avec succès',
            'data' => [
                'global_discount_rate' => $newDiscount->global_discount_rate,
                'start_date' => $newDiscount->start_date->format('Y-m-d'),
                'end_date' => null
            ]
        ]);
    }
} 