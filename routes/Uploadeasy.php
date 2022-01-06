<?php

use Bramato\Uploadeasy\Uploadeasy;

Route::name('uploadeasy.')->group(function () {
    Route::get('/image/{id}', [Uploadeasy::class, 'show'])->name('show');
    Route::get('/image/{id}/-/{command}', function ($id, $command) {
        return App::call([Uploadeasy::class, 'get'], [$id, $command]);
    })->where('command', '.*');

    /*Form*/
    Route::post('/uploadeasy/image/save', [Uploadeasy::class, 'save'])->name('image.save');
    Route::get('/uploadeasy/image/recognize', [Uploadeasy::class, 'recognize'])->name('image.recognize');
    Route::get('/uploadeasy/image/formimage/{id}', [Uploadeasy::class, 'form'])->name('image.form');
}
);


