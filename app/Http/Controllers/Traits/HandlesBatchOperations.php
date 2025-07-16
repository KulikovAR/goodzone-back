<?php

namespace App\Http\Controllers\Traits;

trait HandlesBatchOperations
{
    protected function processBatchOperations(array $operations, callable $handler): array
    {
        $results = [];

        foreach ($operations as $operation) {
            try {
                $result = $handler($operation);
                $results[] = [
                    'id' => $operation['id_sell'],
                    'success' => true,
                    'data' => $result
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'id' => $operation['id_sell'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }
}