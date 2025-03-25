<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DiscountSetting extends Model
{
    protected $fillable = [
        'global_discount_rate',
        'start_date',
        'end_date'
    ];

    protected $dates = [
        'start_date',
        'end_date',
    ];

    /**
     * Détermine si la remise est active à la date donnée
     *
     * @param Carbon|null $date
     * @return bool
     */
    public function isActiveAt(Carbon $date = null)
    {
        $date = $date ?: Carbon::now();
        
        // Si la date de fin est null, la remise est toujours active après sa date de début
        if ($this->end_date === null) {
            return $date->gte($this->start_date);
        }
        
        return $date->between($this->start_date, $this->end_date);
    }

    /**
     * Récupère le paramètre de remise actif
     * Priorité aux remises sans date de fin
     *
     * @return self|null
     */
    public static function getActive()
    {
        // Recherche d'abord une remise sans date de fin
        $infiniteDiscount = self::getActiveInfiniteDiscount();
        if ($infiniteDiscount) {
            return $infiniteDiscount;
        }
        
        // Sinon, recherche une remise avec date de fin
        $now = Carbon::now();
        return self::where('start_date', '<=', $now)
            ->whereNotNull('end_date')
            ->where('end_date', '>=', $now)
            ->orderBy('created_at', 'desc')
            ->first();
    }
    
    /**
     * Récupère la remise active sans date de fin
     *
     * @return self|null
     */
    public static function getActiveInfiniteDiscount()
    {
        $now = Carbon::now();
        return self::whereNull('end_date')
            ->where(function($query) use ($now) {
                $query->where('start_date', '<=', $now)
                      ->orWhereNull('start_date');
            })
            ->orderBy('created_at', 'desc')
            ->first();
    }
} 