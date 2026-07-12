<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class PostPasswordGate
{
    public function protects(Post $post): bool
    {
        return $post->is_password_protected;
    }

    public function isAuthorized(Request $request): bool
    {
        return hash_equals($this->signature(), (string) $request->cookie($this->cookieName()));
    }

    public function passwordMatches(string $password): bool
    {
        return hash_equals($this->password(), $password);
    }

    public function authorizationCookie(): Cookie
    {
        return cookie(
            $this->cookieName(),
            $this->signature(),
            $this->cookieMinutes(),
        );
    }

    private function password(): string
    {
        return (string) config('news.password', 'Password Protected');
    }

    private function cookieName(): string
    {
        return (string) config('news.password_cookie', 'news_post_access');
    }

    private function cookieMinutes(): int
    {
        return (int) config('news.password_cookie_minutes', 60);
    }

    private function signature(): string
    {
        return hash('sha256', $this->password().'|'.config('app.key'));
    }
}
