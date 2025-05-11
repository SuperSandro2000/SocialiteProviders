<?php

namespace SocialiteProviders\DexIdp;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DexIdpExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('dexidp', Provider::class);
    }
}
