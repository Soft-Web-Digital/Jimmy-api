<?php

use function Pest\Laravel\get;

uses()->group('web-routes');




it('redirects on root web route', function () {
    get('/')->assertRedirect(config('frontend.url'));
});
