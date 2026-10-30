<?php

use Illuminate\Support\Facades\Route;

// Laravel carga este archivo con el grupo web: sesiones, errores y protección CSRF.
Route::view('/', 'welcome')->name('inicio');

// Las rutas de los casos de uso se añadirán al implementar cada controlador.
