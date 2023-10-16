<?php

use function Pest\Laravel\get;

uses()->group('service', 'external');





it('checks if the base URL is reachable', function () {
    get(config('_restcountries.base_url'))->assertOk();
});
