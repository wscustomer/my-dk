<?php

test('tamu diarahkan ke halaman masuk', function () {
    $this->get('/')->assertRedirect('/masuk');
});

test('halaman masuk tampil', function () {
    $this->get('/masuk')->assertOk();
});
