<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Hashing\BcryptHasher;
use PHPUnit\Framework\TestCase;

class UserAuthenticationTest extends TestCase
{
    public function test_laravel_uses_the_existing_user_schema(): void
    {
        $user = new User;
        $user->setRawAttributes(['id_usuario' => 71]);

        $this->assertSame('usuarios', $user->getTable());
        $this->assertSame('id_usuario', $user->getAuthIdentifierName());
        $this->assertSame(71, $user->getAuthIdentifier());
        $this->assertFalse($user->usesTimestamps());
    }

    public function test_laravel_validates_credentials_against_password_hash(): void
    {
        $hasher = new BcryptHasher(['rounds' => 4]);
        $password = bin2hex(random_bytes(20));
        $user = new User;
        $user->setRawAttributes(['password_hash' => $hasher->make($password)]);
        $provider = new EloquentUserProvider($hasher, User::class);

        $this->assertSame('password_hash', $user->getAuthPasswordName());
        $this->assertTrue($provider->validateCredentials($user, ['password' => $password]));
        $this->assertFalse($provider->validateCredentials($user, ['password' => $password.'incorrecta']));
    }

    public function test_serialization_hides_the_hash_and_remember_me_does_not_add_a_column(): void
    {
        $user = new User;
        $user->setRawAttributes(['nombre_usuario' => 'paciente_prueba', 'password_hash' => 'hash_privado']);
        $user->setRememberToken('token_prueba');

        $this->assertArrayNotHasKey('password_hash', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->getAttributes());
        $this->assertSame('', $user->getRememberTokenName());
        $this->assertNull($user->getRememberToken());
    }
}
