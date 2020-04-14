<?php

namespace Tests\Feature;

use App\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function testAnyOneCanAddToCart()
    {
        $this->initSessionArray();
        $p = factory(Product::class)->create();

        $cart = [
            'id' => $p->id,
            'product' => $p,
            'amount' => 2,
            'total' => 26
        ];

        $this->post('/' . app()->getLocale() . '/cart', $cart)->assertOk()
            ->assertJsonPath('amount', 2)
            ->assertSessionHas('cart', [$cart]);

        $p = factory(Product::class)->create();

        $cart2 = [
            'id' => $p->id,
            'product' => $p,
            'amount' => 30,
            'total' => 26
        ];

        $this->post('/' . app()->getLocale() . '/cart', $cart2)->assertOk()
            ->assertJsonPath('amount', 30)
            ->assertSessionHas('cart', [$cart, $cart2]);
    }

    public function testAnyOneCanNotAddTheSameProductTwice()
    {
        $this->initSessionArray();
        $cart = $this->createCart();

        /** @var \App\Product $p */
        $p = $cart['product'];

        $this->post('/' . app()->getLocale() . '/cart', $cart)
            ->assertOk()
            ->assertSessionHas('cart', [$cart]);

        $this->post('/' . app()->getLocale() . '/cart', $cart)
            ->assertOk()
            ->assertExactJson([
                'exists' => true
            ]);
    }

    public function testCartAmountCanBeUpdated()
    {
        $this->initSessionArray();
        $cart = $this->createCart(null, 5);

        /** @var \App\Product $p */
        $p = $cart['product'];

        $this->post('/' . app()->getLocale() . '/cart', $cart)
            ->assertOk()
            ->assertSessionHas('cart', [$cart]);

        $cart['amount'] = 25;
        $cart['total'] = 60;

        $this->patch('/' . app()->getLocale() . '/cart/' . $p->id, ['amount' => 25, 'total' => 60])
            ->assertOk()
            ->assertSessionHas('cart', [$cart])
            ->assertExactJson(['updated' => true]);
    }

    public function testUpdatingCartRequiresItemExists()
    {
        $this->initSessionArray();
        $this->patch('/' . app()->getLocale() . '/cart/55', [])
            ->assertOk()
            ->assertExactJson(['empty' => true]);

        $cart = $this->createCart();
        $this->post('/' . app()->getLocale() . '/cart', $cart)
            ->assertOk()
            ->assertSessionHas('cart', [$cart]);

        $this->patch('/' . app()->getLocale() . '/cart/445')
            ->assertOk()
            ->assertExactJson(['exists' => false]);
    }

    public function testCartCanBeDeleted()
    {
        $this->initSessionArray();
        $cart = $this->createCart();
        $cats2 = $this->createCart();

        $id = $cart['id'];

        $this->post('/' . app()->getLocale() . '/cart', $cart)
            ->assertOk()
            ->assertSessionHas('cart', [$cart]);

        $this->post('/' . app()->getLocale() . '/cart', $cats2)
            ->assertOk()
            ->assertSessionHas('cart', [$cart, $cats2]);

        $this->delete('/' . app()->getLocale() . '/cart/' . $id)
            ->assertOk()
            ->assertSessionHas('cart', [$cats2])
            ->assertExactJson([
                'deleted' => true
            ]);
    }

    public function testRemovingAnItemFromCartErrors()
    {
        $this->initSessionArray();

        // trying to remove while cart is empty
        $this->delete('/' . app()->getLocale() . '/cart/55')
            ->assertOk()
            ->assertExactJson(['empty' => true]);

        // remove cart with invalid id
        $cart = $this->createCart();
        $this->post('/' . app()->getLocale() . '/cart', $cart)
            ->assertOk()
            ->assertSessionHas('cart', [$cart]);

        $this->delete('/' . app()->getLocale() . '/cart/' . 55)
            ->assertOk()
            ->assertExactJson(['exists' => false]);
    }

    public function testLoadingCartList()
    {
        $this->initSessionArray();
        $cart = $this->createCart();
        $this->post('/' . app()->getLocale() . '/cart', $cart)
            ->assertOk()
            ->assertSessionHas('cart', [$cart]);
        $cart2 = $this->createCart();
        $this->post('/' . app()->getLocale() . '/cart', $cart2)
            ->assertOk()
            ->assertSessionHas('cart', [$cart, $cart2]);
        $cart3 = $this->createCart();
        $this->post('/' . app()->getLocale() . '/cart', $cart3)
            ->assertOk()
            ->assertSessionHas('cart', [$cart, $cart2, $cart3]);

        $this->getJson('/' . app()->getLocale() . '/cart')
            ->assertOk()
            ->assertSessionHas('cart', [$cart, $cart2, $cart3]);
    }

    public function testOnlyAuthriedUsersCanCheckout()
    {
        $this->get('/' . app()->getLocale() . '/cart/checkout')
            ->assertStatus(302);

        $this->post('/' . app()->getLocale() . '/cart/checkout', [])
            ->assertStatus(302);
    }

    public function testUserCanNotChekoutCartWithInvalidData()
    {
        $this->signIn();

        $this->post('/' . app()->getLocale() . '/cart/checkout', [])
            ->assertStatus(302)
            ->assertSessionHasErrors(['fname', 'lname', 'address', 'card']);
    }

    public function testUserCanCheckoutCart()
    {
        $this->withoutExceptionHandling();
        $user = $this->signIn();

        $cart = $this->createCart();
        $this->post('/' . app()->getLocale() . '/cart', $cart);
        $cart = $this->createCart();
        $this->post('/' . app()->getLocale() . '/cart', $cart);
        $cart3 = $this->createCart();
        $this->post('/' . app()->getLocale() . '/cart', $cart3);

        $userNameArr = explode(' ', $user->name);

        $this->post('/' . app()->getLocale() . '/cart/checkout', [
            'fname' => $userNameArr[0],
            'lname' => $userNameArr[1],
            'address' => $this->faker->address,
            'card' => $this->faker->creditCardNumber
        ])->assertStatus(302)
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas('cart', [])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('orders', ['product_id' => $cart['id']]);
        $this->assertDatabaseHas('orders', ['product_id' => $cart3['id']]);
    }

    public function testUserCanNotCheckoutIfNoItemsInCart()
    {
        $user = $this->signIn();

        $this->get('/en/cart/checkout')
            ->assertOk()
            ->assertSee('alert-warning')
            ->assertDontSee('alert-success')
            ->assertDontSee('alert-danger')
            ->assertDontSee('fname');
    }

    public function testCartWillBeCheckProductRemaningAmount()
    {
        $cart = $this->createCart();
        $this->post('/' . app()->getLocale() . '/cart', $cart)
            ->assertOk()
            ->assertSessionHas('cart', [$cart]);
        $product = factory(Product::class)->create();
        $cart2 = $this->createCart($product);
        $this->post('/' . app()->getLocale() . '/cart', $cart2)
            ->assertOk()
            ->assertSessionHas('cart', [$cart, $cart2]);

        $this->getJson('/en/cart')
            ->assertOk();

        // CONSIDER another user checkout a product with full amount
        // THEN we will check for product amount on very cart loading
        $product->amount = 0;
        $product->update();

        $cart2['product']['amount'] = 0;

        // get the cart again
        $this->getJson('/en/cart')
            ->assertOk()
            ->assertSessionHas('cart', [$cart, $cart2]);
    }

    private function createCart(
        ?object $product = null,
        ?int $amount = null,
        ?float $total = null
    ): array {
        $product = $product ?? factory(Product::class)->create();
        return [
            'id' => $product->id,
            'product' => $product,
            'amount' => $amount ?? $this->faker->randomDigit,
            'total' => $total ?? $this->faker->randomFloat(1, 0, 10000)
        ];
    }

    private function initSessionArray()
    {
        if (!session()->has('cart')) {
            session()->put('cart', []);
        }
    }
}
