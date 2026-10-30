<?php

return [
    // Pilihan fasilitas beserta ikon yang dapat dipakai pada cafe.
    'catalog' => [
        'Live Music' => 'bi-music-note-beamed',
        '24 Jam' => 'bi-clock',
        'WFC Friendly' => 'bi-laptop',
        'Outdoor Seating' => 'bi-tree',
        'Smoking Area' => 'bi-wind',
        'Parking' => 'bi-car-front',
        'Pet Friendly' => 'bi-heart',
        'AC' => 'bi-snow',
    ],

    // Isi hanya fasilitas yang benar-benar tersedia pada cafe tersebut.
    // Gunakan nama cafe persis seperti kolom cafes.name.
    // Contoh:
    // 'Nama Cafe A' => ['Smoking Area', 'Parking'],
    // 'Nama Cafe B' => ['Smoking Area', 'AC'],
    'by_cafe' => [
    ],
];
