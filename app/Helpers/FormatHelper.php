<?php

namespace App\Helpers;

use Carbon\Carbon;

class FormatHelper
{
    /**
     * Format number to always show 2 decimal places
     */
    public static function formatNumber($number, $decimals = 2): string
    {
        if ($number === null || $number === '') {
            return number_format(0, $decimals);
        }
        
        return number_format((float) $number, $decimals);
    }

    /**
     * Format currency with 2 decimal places
     */
    public static function formatCurrency($amount): string
    {
        return '$' . self::formatNumber($amount, 2);
    }

    /**
     * Format date to d-m-y format
     */
    public static function formatDate($date): string
    {
        if (!$date) {
            return '';
        }
        
        if ($date instanceof Carbon) {
            return $date->format('d-m-Y');
        }
        
        return Carbon::parse($date)->format('d-m-Y');
    }

    /**
     * Format date and time to d-m-y H:i format
     */
    public static function formatDateTime($date): string
    {
        if (!$date) {
            return '';
        }
        
        if ($date instanceof Carbon) {
            return $date->format('d-m-Y H:i');
        }
        
        return Carbon::parse($date)->format('d-m-Y H:i');
    }

    /**
     * Format date and time with seconds to d-m-y H:i:s format
     */
    public static function formatDateTimeWithSeconds($date): string
    {
        if (!$date) {
            return '';
        }
        
        if ($date instanceof Carbon) {
            return $date->format('d-m-Y H:i:s');
        }
        
        return Carbon::parse($date)->format('d-m-Y H:i:s');
    }

    /**
     * Format percentage with 2 decimal places
     */
    public static function formatPercentage($percentage): string
    {
        return self::formatNumber($percentage, 2) . '%';
    }

    /**
     * Format quantity with 2 decimal places for stock
     */
    public static function formatQuantity($quantity): string
    {
        return self::formatNumber($quantity, 2);
    }

    /**
     * Format price with 2 decimal places
     */
    public static function formatPrice($price): string
    {
        return self::formatNumber($price, 2);
    }

    /**
     * Format text with HTML tags
     */
    public static function formatText($text): string
    {
        return htmlspecialchars($text);
    }
} 