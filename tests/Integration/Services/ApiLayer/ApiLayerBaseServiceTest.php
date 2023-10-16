<?php

use function Pest\Laravel\get;

uses()->group('service', 'external');





it('checks if the base URL is reachable', function () {
    get(config('_apilayer.base_url'))->assertOk();
});
