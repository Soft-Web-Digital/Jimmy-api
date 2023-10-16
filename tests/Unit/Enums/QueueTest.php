<?php

use App\Enums\Queue;

uses()->group('enum');





it('has the default queue', function () {
    expect(array_key_exists('default', Queue::valuePair()))->toBeTrue();
});
