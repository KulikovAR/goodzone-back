<?php

namespace App\Http\Controllers\Traits;

trait HandlesBatchOperations
{
    protected function processBatchOperations(array $operations, callable $handler): array
    {
        $results = [];
        $allSuccess = true;
        $anySuccess = false;

        foreach ($operations as $operation) {
            try {
                $result = $handler($operation);
                $results[] = [
                    'id' => $operation['id_sell'],
                    'success' => true,
                    'data' => $result
                ];
                $anySuccess = true;
            } catch (\Exception $e) {
                $results[] = [
                    'id' => $operation['id_sell'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                $allSuccess = false;
            }
        }

        return [
            'results' => $results,
            'http_code' => $allSuccess ? 200 : ($anySuccess ? 206 : 400)
        ];
    }
}