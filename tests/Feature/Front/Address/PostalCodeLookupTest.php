<?php

declare(strict_types=1);

namespace Tests\Feature\Front\Address;

use App\Models\Prefecture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PostalCodeLookupTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Http::preventStrayRequests();

        $this->user = User::factory()->create();
        Prefecture::factory()->create(['name' => '東京都']);
    }

    /**
     * @param  list<array<string, string>>|null  $results
     * @return array<string, mixed>
     */
    private function body(?array $results): array
    {
        return ['message' => null, 'results' => $results, 'status' => 200];
    }

    /**
     * @return array<string, string>
     */
    private function resultRow(string $prefecture = '東京都', string $city = '渋谷区', string $town = '架空町'): array
    {
        return ['address1' => $prefecture, 'address2' => $city, 'address3' => $town];
    }

    #[Test]
    public function 未ログインではログイン画面へリダイレクトされる(): void
    {
        $this->get(route('postal-codes.show', ['postalCode' => '1500041']))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function 郵便番号から都道府県と市区町村と町域が返る(): void
    {
        Http::fake(['*' => Http::response($this->body([$this->resultRow()]))]);

        $this->actingAs($this->user)
            ->get(route('postal-codes.show', ['postalCode' => '1500041']))
            ->assertOk()
            ->assertExactJson([
                'prefecture_id' => Prefecture::query()->where('name', '東京都')->value('id'),
                'prefecture_name' => '東京都',
                'city' => '渋谷区',
                'town' => '架空町',
            ]);
    }

    #[Test]
    public function 都道府県名がマスタにないときは都道府県の識別子が空で返る(): void
    {
        Http::fake(['*' => Http::response($this->body([$this->resultRow(prefecture: '架空県')]))]);

        $this->actingAs($this->user)
            ->get(route('postal-codes.show', ['postalCode' => '1500041']))
            ->assertOk()
            ->assertJson(['prefecture_id' => null, 'prefecture_name' => '架空県']);
    }

    #[Test]
    public function 複数の町域が返ったときは先頭が採用される(): void
    {
        Http::fake(['*' => Http::response($this->body([
            $this->resultRow(town: '架空町一丁目'),
            $this->resultRow(town: '架空町二丁目'),
        ]))]);

        $this->actingAs($this->user)
            ->get(route('postal-codes.show', ['postalCode' => '1500041']))
            ->assertOk()
            ->assertJson(['town' => '架空町一丁目']);
    }

    #[Test]
    public function 該当する住所がないときは見つからないとして返る(): void
    {
        Http::fake(['*' => Http::response($this->body(null))]);

        $this->actingAs($this->user)
            ->get(route('postal-codes.show', ['postalCode' => '9999999']))
            ->assertNotFound()
            ->assertJson(['message' => '該当する住所が見つかりませんでした。']);
    }

    #[Test]
    public function 七桁の数字でない郵便番号は受け付けない(): void
    {
        $this->actingAs($this->user)
            ->get('/postal-codes/150004')
            ->assertNotFound();

        $this->actingAs($this->user)
            ->get('/postal-codes/150-0041')
            ->assertNotFound();
    }

    #[Test]
    public function 外部サービスに到達できないときはエラーとして返る(): void
    {
        Http::fake(fn () => throw new ConnectionException('接続できません'));

        $this->actingAs($this->user)
            ->get(route('postal-codes.show', ['postalCode' => '1500041']))
            ->assertStatus(502)
            ->assertJson(['message' => '住所を取得できませんでした。']);
    }

    #[Test]
    public function 同じ郵便番号への二回目の問い合わせは外部サービスを呼ばない(): void
    {
        Http::fake(['*' => Http::response($this->body([$this->resultRow()]))]);

        $this->actingAs($this->user)
            ->get(route('postal-codes.show', ['postalCode' => '1500041']))
            ->assertOk();

        $this->actingAs($this->user)
            ->get(route('postal-codes.show', ['postalCode' => '1500041']))
            ->assertOk()
            ->assertJson(['city' => '渋谷区']);

        Http::assertSentCount(1);
    }

    #[Test]
    public function 該当がなかった郵便番号はキャッシュされない(): void
    {
        Http::fake(['*' => Http::response($this->body(null))]);

        $this->actingAs($this->user)
            ->get(route('postal-codes.show', ['postalCode' => '9999999']))
            ->assertNotFound();

        $this->actingAs($this->user)
            ->get(route('postal-codes.show', ['postalCode' => '9999999']))
            ->assertNotFound();

        Http::assertSentCount(2);
    }

    #[Test]
    public function 外部サービスがサーバーエラーを返したときはエラーとして返る(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        $this->actingAs($this->user)
            ->get(route('postal-codes.show', ['postalCode' => '1500041']))
            ->assertStatus(502)
            ->assertJson(['message' => '住所を取得できませんでした。']);

        // サーバーエラーは再送の対象になる
        Http::assertSentCount(3);
    }

    #[Test]
    public function 外部サービスが応答本文でエラーを知らせたときは該当なしと区別する(): void
    {
        Http::fake(['*' => Http::response([
            'message' => 'パラメータが不正です。',
            'results' => null,
            'status' => 400,
        ])]);

        $this->actingAs($this->user)
            ->get(route('postal-codes.show', ['postalCode' => '1500041']))
            ->assertStatus(502);
    }

    #[Test]
    public function 応答の形式が想定と異なるときはエラーとして返る(): void
    {
        Http::fake(['*' => Http::response($this->body([['address1' => 123]]))]);

        $this->actingAs($this->user)
            ->get(route('postal-codes.show', ['postalCode' => '1500041']))
            ->assertStatus(502);
    }

    #[Test]
    public function 応答本文でエラーを知らせた郵便番号はキャッシュされない(): void
    {
        Http::fake(['*' => Http::response([
            'message' => 'パラメータが不正です。',
            'results' => null,
            'status' => 400,
        ])]);

        $this->actingAs($this->user)
            ->get(route('postal-codes.show', ['postalCode' => '1500041']))
            ->assertStatus(502);

        $this->actingAs($this->user)
            ->get(route('postal-codes.show', ['postalCode' => '1500041']))
            ->assertStatus(502);

        Http::assertSentCount(2);
    }
}
