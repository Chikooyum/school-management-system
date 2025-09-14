<?php

Route::get('/{any}', function () {
    // NAH, LIHAT BAGIAN INI
    return view('app'); // <--- NAMA INILAH YANG KITA CARI
})->where('any', '.*');
