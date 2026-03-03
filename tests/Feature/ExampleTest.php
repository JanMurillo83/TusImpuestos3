<?php

it('returns a successful response', function () {
    $response = $this->get('/');

    // En un entorno sin sesión autenticada, la página inicial suele redirigir.
    $response->assertStatus(302);
});
