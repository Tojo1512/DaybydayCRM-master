<?php

use App\Services\Activity\ActivityLogger;

if (! function_exists('activity')) {
    function activity(string $logName = null): ActivityLogger
    {
        $defaultLogName = "default";
        return app(ActivityLogger::class)
            ->withname($logName ?? $defaultLogName);
    }
}

if (! function_exists('frontendDate')) {
    function frontendDate(): String
    {
        return app(\App\Repositories\Format\GetDateFormat::class)->getFrontendDate();
    }
}
if (! function_exists('frontendTime')) {
    function frontendTime(): String
    {
        return app(\App\Repositories\Format\GetDateFormat::class)->getFrontendTime();
    }
}
if (! function_exists('carbonTime')) {
    function carbonTime(): String
    {
        return app(\App\Repositories\Format\GetDateFormat::class)->getCarbonTime();
    }
}

if (! function_exists('carbonFullDateWithText')) {
    function carbonFullDateWithText(): String
    {
        return app(\App\Repositories\Format\GetDateFormat::class)->getCarbonFullDateWithText();
    }
}

if (! function_exists('carbonDateWithText')) {
    function carbonDateWithText(): String
    {
        return app(\App\Repositories\Format\GetDateFormat::class)->getCarbonDateWithText();
    }
}

if (! function_exists('carbonDate')) {
    function carbonDate(): String
    {
        return app(\App\Repositories\Format\GetDateFormat::class)->getCarbonDate();
    }
}

if (! function_exists('isDemo')) {
    function isDemo(): String
    {
        return app()->environment() == "demo" ? 1 : 0;
    }
}

if (! function_exists('formatMoney')) {
    function formatMoney($amount, $useCode = false): String
    {
        return app(\App\Repositories\Money\MoneyConverter::class, ['money' => $amount])->format($useCode);
    }
}

if (! function_exists('importCsv')) {
    /**
     * Importe un fichier CSV dans la base de données
     *
     * @param string $tableName Nom de la table dans la base de données
     * @param string $csvFilePath Chemin du fichier CSV à importer
     * @param array|null $csvColumns Ordre des colonnes dans le fichier CSV (facultatif si le fichier a un en-tête)
     * @param array|null $tableColumns Ordre des colonnes dans la table (facultatif si le fichier a un en-tête)
     * @param string $delimiter Délimiteur du fichier CSV (par défaut ",")
     * @param bool $hasHeader Si le fichier contient une ligne d'en-tête (par défaut true)
     * @return array Résultat de l'importation
     */
    function importCsv(
        string $tableName,
        string $csvFilePath,
        ?array $csvColumns = null,
        ?array $tableColumns = null,
        string $delimiter = ',',
        bool $hasHeader = true
    ): array {
        return app(\App\Services\CsvImport\CsvImportService::class)
            ->importCsv($tableName, $csvFilePath, $csvColumns, $tableColumns, $delimiter, $hasHeader);
    }
}