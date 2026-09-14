<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ApiLoginTest extends TestCase {
 use RefreshDatabase;
 public function test_invalid_credentials_return_401():void{$this->postJson('/api/v1/auth/login',['email'=>'missing@example.com','password'=>'wrong-password'])->assertUnauthorized()->assertJson(['success'=>false,'message'=>'Invalid credentials.']);}
}
