<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ThreeBECardService
{
    protected $token;
    protected $baseUrl;


    public function __construct()
    {
            $this->baseUrl = env('3PE_CARD');
            $this->token = env('3PE_CARD_TOKEN');
    }

       public function getCategories($page)
    {
        $response = Http::withHeaders([
            'token' => $this->token,
        ])->get($this->baseUrl . '/MemberApi_getCategories', [
            'page' => $page,
        ]);

        return $response->json('data');
    }


public function getProducts($page)
{
    $response = Http::withHeaders([
        'token' => $this->token,
    ])
    ->timeout(60)
    ->get($this->baseUrl . '/MemberApi_getProducts', [
        'page' => $page
    ]);

    return $response->json('data') ?? [];
}

public function sendOrder(array $items)
{
    return Http::withHeaders([
        'token' => $this->token,
    ])->post($this->baseUrl . '/MemberApi_postOrder', [
        'items' => $items
    ]);
}

}
