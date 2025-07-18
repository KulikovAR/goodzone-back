<?php

namespace Tests\Feature\City;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchCityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_filtered_unique_cities_from_dadata()
    {
        // Фейковый ответ от Dadata API
        Http::fake([
            'https://suggestions.dadata.ru/*' => Http::response([
                'suggestions' => [
                    [
                        'data' => [
                            'city' => 'Москва',
                            'city_type_full' => 'город',
                        ],
                    ],
                    [
                        'data' => [
                            'city' => null,
                            'settlement_with_type' => 'Московский посёлок',
                            'city_type_full' => 'посёлок',
                        ],
                    ],
                    [
                        'data' => [
                            'city' => null,
                            'settlement_with_type' => null,
                            'city_type_full' => 'город',
                        ],
                    ],
                    [
                        'data' => [
                            'city' => 'Москва',
                            'city_type_full' => 'город',
                        ],
                    ],
                ],
            ]),
        ]);

        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/search-cities?s=моск');

        $response->assertOk();

        // Проверяем структуру
        $response->assertJsonStructure([
            '*' => ['city'],
        ]);

        // Проверяем, что вернулись нужные города
        $response->assertJsonFragment(['city' => 'Москва']);
        $response->assertJsonFragment(['city' => 'Московский посёлок']);

        // Убедимся, что дубликаты и null'ы отфильтрованы
        $cities = collect($response->json())->pluck('city');
        $this->assertCount($cities->unique()->count(), $cities);
        $this->assertFalse($cities->contains(null));
    }

    #[Test]
    public function it_returns_empty_array_when_no_valid_cities_found()
    {
        Http::fake([
            'https://suggestions.dadata.ru/*' => Http::response([
                'suggestions' => [
                    [
                        'data' => [
                            'city' => null,
                            'settlement_with_type' => null,
                            'city_type_full' => 'посёлок',
                        ],
                    ],
                    [
                        'data' => [
                            'city' => null,
                            'settlement_with_type' => null,
                        ],
                    ],
                ],
            ]),
        ]);

        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/search-cities?s=xyz');

        $response->assertOk();
        $response->assertExactJson([]);
    }

    #[Test]
    public function it_limits_the_number_of_returned_unique_cities()
    {
        Http::fake([
            'https://suggestions.dadata.ru/*' => Http::response([
                'suggestions' => [
                    ['data' => ['city' => 'Москва', 'city_type_full' => 'город']],
                    ['data' => ['city' => 'Магадан', 'city_type_full' => 'город']],
                    ['data' => ['city' => 'Махачкала', 'city_type_full' => 'город']],
                    ['data' => ['city' => 'Минск', 'city_type_full' => 'город']],
                    ['data' => ['city' => 'Майкоп', 'city_type_full' => 'город']],
                    ['data' => ['city' => 'Мурманск', 'city_type_full' => 'город']],
                    ['data' => ['city' => 'Магнитогорск', 'city_type_full' => 'город']],
                ],
            ]),
        ]);

        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/search-cities?s=м');

        $response->assertOk();

        $cities = collect($response->json());
        $this->assertLessThanOrEqual(6, $cities->count());
    }

    #[Test]
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/v1/search-cities?s=москва');

        $response->assertStatus(401);
    }

    #[Test]
    public function it_requires_search_query_param()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/search-cities');

        $response->assertStatus(422);
    }

    #[Test]
    public function it_handles_dadata_api_errors_gracefully()
    {
        Http::fake([
            'https://suggestions.dadata.ru/*' => Http::response([
                'message' => 'Something went wrong on Dadata side.'
            ], 500),
        ]);

        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/search-cities?s=москва');

        $response->assertStatus(400);
        $response->assertJsonFragment(['message' => 'Something went wrong on Dadata side.']);
    }
}
