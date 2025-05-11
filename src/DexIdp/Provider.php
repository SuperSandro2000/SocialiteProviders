<?php

namespace SocialiteProviders\DexIdp;

use Exception;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://dexidp.io/docs/guides/using-dex/
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'DEXIDP';

    public static function additionalConfigKeys(): array
    {
        return ['base_url'];
    }

    public const CACHE_KEY = 'dex_idp_openid_config';

    protected $usesPKCE = true;

    protected $scopeSeparator = ' ';

    protected $scopes = ['openid', 'profile', 'email', 'groups'];

    protected function getAuthUrl($state): string
    {
        $config = $this->getOpenIdConfiguration();

        return $this->buildAuthUrlFromBase($config->authorization_endpoint, $state);
    }

    protected function getTokenUrl(): string
    {
        $config = $this->getOpenIdConfiguration();

        return $config->token_endpoint;
    }

    protected function getUserByToken($token)
    {
        $config = $this->getOpenIdConfiguration();

        $response = $this->getHttpClient()->get($config->userinfo_endpoint, [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'                 => $user['sub'],
            'name'               => $user['name'],
            'preferred_username' => $user['preferred_username'],
            'email'              => $user['email'],
            'email_verified'     => $user['email_verified'],
            'groups'             => $user['groups'] ?? "",
        ]);
    }

    private function getOpenIdConfiguration()
    {
        $expires = Carbon::now()->addHour();

        return Cache::remember(self::CACHE_KEY, $expires, function () {
            try {
                $response = $this->getHttpClient()->get($this->getConfig('base_url') . '/.well-known/openid-configuration');
            } catch (Exception $e) {
                throw new InvalidStateException("Error on getting OpenID Configuration. {$e}");
            }

            return json_decode((string) $response->getBody());
        });
    }
}
