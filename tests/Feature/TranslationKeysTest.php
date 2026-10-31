<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

test('every static translation key used in the frontend exists in lang/bg/ui.php', function () {
    $known = Arr::dot(trans('ui', [], 'bg'));
    $missing = [];

    foreach (File::allFiles(resource_path('js')) as $file) {
        preg_match_all("/\\\$?\\bt\\(\\s*'([a-z0-9_]+(?:\\.[a-z0-9_]+)+)'/", $file->getContents(), $matches);

        foreach ($matches[1] as $key) {
            if (! array_key_exists($key, $known)) {
                $missing[] = $file->getRelativePathname().': '.$key;
            }
        }
    }

    expect($missing)->toBe([]);
});
