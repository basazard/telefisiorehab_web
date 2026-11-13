<?php

/*
 | Consent, privacy and terms (PRD 11 E4). The statements are text, not code: a lawyer's wording changes this file only.
 | Change consent_version whenever the texts or the statements change: everyone is asked to consent again.
 */

return [

    // DRAFT until a lawyer has reviewed resources/legal/*.md and the statements below.
    'consent_version' => '2026.1-draft',

    // Marker that the texts are still temporary; `pilot:check` (E10) fails while a legal file contains it.
    'placeholder_marker' => 'TEKS SEMENTARA: belum ditinjau pengacara',

    // Statements a user must each tick, per role group. Keys are stored with the consent as evidence.
    'items' => [
        'patient' => [
            'terms' => 'Saya telah membaca dan menyetujui Syarat Penggunaan dan Kebijakan Privasi TeleFisioRehab.',
            'health_data' => 'Saya setuju data kesehatan saya (profil, keluhan, hasil assessment, program dan catatan latihan) diproses oleh '
                .'TeleFisioRehab dan dapat dilihat oleh fisioterapis dan admin klinik tempat saya terdaftar, untuk keperluan layanan fisioterapi saya.',
        ],
        'staff' => [
            'terms' => 'Saya telah membaca dan menyetujui Syarat Penggunaan dan Kebijakan Privasi TeleFisioRehab.',
            'confidentiality' => 'Saya akan menjaga kerahasiaan data pasien: saya hanya membuka data pasien yang saya tangani, hanya untuk '
                .'keperluan layanan, dan tidak membagikannya kepada pihak lain.',
        ],
    ],

    // "Permintaan data saya" on the profile page: reply time in days (DRAFT).
    'data_request_days' => 14,

];
