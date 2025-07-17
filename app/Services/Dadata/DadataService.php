<?php

namespace App\Services\Dadata;

use App\Enums\DadataBaseUrlEnum;
use App\Enums\DadataUrlEnum;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DadataService
{
    private DadataClient $client;

    public function __construct()
    {
        $this->client = new DadataClient();
    }

    public function searchCities(string $query, int $count = 6): array
    {
        $fetchCount = max($count * 3, 10);

        $params = [
            'query' => $query,
            'count' => $fetchCount,
            'from' => 'city',
        ];

        $response = $this->client->client->post(
            DadataUrlEnum::API_URL->value . DadataBaseUrlEnum::SUGGEST->value,
            $params
        );

        if (! $response->successful()) {
            throw new BadRequestHttpException(json_decode($response->body())->message ?? 'Dadata error');
        }

        $normalizedQuery = mb_strtolower($query);

        return collect(json_decode($response->body(), true)['suggestions'])
            ->filter(function ($item) use ($normalizedQuery) {
                $data = $item['data'];
                $cityName = $data['city'] ?? $data['settlement_with_type'] ?? null;

                // Оставляем только те города, в названии которых есть запрос
                return $cityName && str_contains(mb_strtolower($cityName), $normalizedQuery);
            })
            ->map(fn ($item) => [
                'city' => $item['data']['city'] ?? $item['data']['settlement_with_type'],
            ])
            ->unique('city')
            ->values()
            ->take($count)
            ->toArray();
    }
}