<?php

namespace App\Support\Onboarding;

use App\Models\Exercise;

/**
 * Built-in exercise library (global: clinic_id null). DRAFT content: common, low-risk home exercises with
 * Indonesian instructions; needs clinical sign-off before launch. Videos are not available yet (video_url null).
 *
 * body_area: lower_limb | low_back | shoulder | neck | general.  dose_unit: reps | seconds (hold) | minutes.
 */
final class ExerciseLibrary
{
    public const GENERAL_PRECAUTION = 'Hentikan jika muncul nyeri tajam, pusing, sesak berat, atau nyeri dada.';

    /** slug => [name, type, body_area, dose_unit, instructions] */
    public const ITEMS = [
        // ---------------------------------------------------------------- peregangan
        'hamstring-stretch' => ['Peregangan paha belakang (duduk)', 'stretching', 'lower_limb', 'seconds',
            "Duduk di tepi kursi, luruskan satu tungkai ke depan dengan tumit di lantai.\nCondongkan badan ke depan dengan punggung tetap lurus sampai terasa tarikan ringan di paha belakang.\nTahan, lalu ganti tungkai."],
        'calf-stretch' => ['Peregangan betis di dinding', 'stretching', 'lower_limb', 'seconds',
            "Berdiri menghadap dinding, kedua tangan menempel di dinding.\nLangkahkan satu kaki ke belakang, tumit tetap menempel lantai, tekuk lutut depan.\nTahan saat terasa tarikan di betis belakang, lalu ganti kaki."],
        'knee-to-chest' => ['Lutut ke dada', 'stretching', 'low_back', 'seconds',
            "Berbaring telentang dengan kedua lutut ditekuk.\nTarik satu lutut perlahan ke arah dada dengan kedua tangan.\nTahan, turunkan perlahan, lalu ganti sisi."],
        'cross-body-shoulder' => ['Peregangan bahu menyilang', 'stretching', 'shoulder', 'seconds',
            "Berdiri atau duduk tegak.\nBawa satu lengan menyilang di depan dada, tahan dengan tangan lainnya di atas siku.\nTahan saat terasa tarikan di bahu belakang, lalu ganti sisi."],
        'neck-side-stretch' => ['Peregangan leher ke samping', 'stretching', 'neck', 'seconds',
            "Duduk tegak, bahu rileks.\nMiringkan kepala perlahan ke arah bahu kanan sampai terasa tarikan ringan di sisi kiri leher.\nTahan, kembali ke tengah, lalu ke sisi lain."],
        'overhead-reach' => ['Meraih ke atas', 'stretching', 'general', 'seconds',
            "Berdiri tegak, kaki selebar bahu.\nAngkat kedua lengan ke atas seolah meraih langit-langit sambil menarik napas.\nTahan, lalu turunkan perlahan sambil membuang napas."],

        // ---------------------------------------------------------------- penguatan
        'straight-leg-raise' => ['Angkat tungkai lurus', 'strengthening', 'lower_limb', 'reps',
            "Berbaring telentang, satu lutut ditekuk dan tungkai lainnya lurus.\nKencangkan otot paha depan, angkat tungkai lurus setinggi lutut yang ditekuk.\nTurunkan perlahan. Ulangi, lalu ganti tungkai."],
        'sit-to-stand' => ['Duduk–berdiri dari kursi', 'strengthening', 'lower_limb', 'reps',
            "Duduk di kursi kokoh tanpa roda, kaki selebar bahu.\nCondongkan badan sedikit ke depan, berdiri tanpa (atau dengan sedikit) bantuan tangan.\nDuduk kembali perlahan."],
        'heel-raise' => ['Jinjit (heel raise)', 'strengthening', 'lower_limb', 'reps',
            "Berdiri di belakang kursi dan berpegangan.\nAngkat kedua tumit setinggi mungkin, tahan sebentar.\nTurunkan perlahan."],
        'bridge' => ['Bridging (angkat panggul)', 'strengthening', 'low_back', 'reps',
            "Berbaring telentang, kedua lutut ditekuk, telapak kaki di lantai.\nKencangkan otot perut dan bokong, angkat panggul sampai badan lurus dari bahu ke lutut.\nTahan 3 detik, turunkan perlahan."],
        'bird-dog' => ['Bird dog', 'strengthening', 'low_back', 'reps',
            "Posisi merangkak, tangan di bawah bahu dan lutut di bawah panggul.\nLuruskan satu lengan ke depan dan tungkai seberangnya ke belakang, punggung tetap datar.\nTahan 3 detik, kembali, lalu ganti sisi."],
        'wall-push-up' => ['Push-up ke dinding', 'strengthening', 'shoulder', 'reps',
            "Berdiri menghadap dinding sejauh satu lengan, telapak tangan di dinding setinggi bahu.\nTekuk siku perlahan sampai dada mendekati dinding.\nDorong kembali ke posisi awal."],
        'chin-tuck' => ['Tarik dagu (chin tuck)', 'strengthening', 'neck', 'reps',
            "Duduk tegak dengan pandangan lurus ke depan.\nTarik dagu lurus ke belakang seperti membuat dagu ganda, tanpa menunduk.\nTahan 5 detik, lalu lepaskan."],

        // ---------------------------------------------------------------- aerobik ringan
        'easy-walk' => ['Jalan kaki santai', 'aerobic', 'general', 'minutes',
            "Berjalan di permukaan rata dengan kecepatan nyaman.\nAnda seharusnya masih bisa berbicara sambil berjalan.\nGunakan alas kaki yang nyaman."],
        'marching' => ['Jalan di tempat', 'aerobic', 'general', 'minutes',
            "Berdiri di dekat kursi atau dinding untuk berpegangan bila perlu.\nAngkat lutut bergantian seperti berjalan di tempat, ayunkan lengan.\nJaga napas tetap teratur."],

        // ---------------------------------------------------------------- relaksasi
        'progressive-relaxation' => ['Relaksasi otot progresif', 'relaxation', 'general', 'minutes',
            "Berbaring atau duduk nyaman, pejamkan mata.\nKencangkan satu kelompok otot (mulai dari kaki) selama 5 detik, lalu lepaskan dan rasakan otot mengendur.\nLanjutkan ke betis, paha, perut, tangan, bahu, dan wajah."],

        // ---------------------------------------------------------------- ROM
        'ankle-pump' => ['Pompa pergelangan kaki', 'rom', 'lower_limb', 'reps',
            "Berbaring atau duduk dengan tungkai lurus.\nTarik ujung kaki ke arah Anda, lalu dorong menjauh.\nLakukan perlahan dan berirama."],
        'heel-slide' => ['Geser tumit (heel slide)', 'rom', 'lower_limb', 'reps',
            "Berbaring telentang dengan tungkai lurus.\nGeser tumit mendekati bokong sehingga lutut menekuk sejauh nyaman.\nGeser kembali sampai lurus."],
        'pelvic-tilt' => ['Pelvic tilt', 'rom', 'low_back', 'reps',
            "Berbaring telentang, kedua lutut ditekuk.\nKencangkan otot perut dan tekan punggung bawah ke lantai.\nTahan 3 detik, lalu lepaskan."],
        'cat-camel' => ['Cat–camel', 'rom', 'low_back', 'reps',
            "Posisi merangkak.\nLengkungkan punggung ke atas seperti kucing sambil menunduk, lalu turunkan punggung perlahan sambil mengangkat kepala.\nGerakkan perlahan dalam rentang yang nyaman."],
        'pendulum' => ['Pendulum bahu', 'rom', 'shoulder', 'reps',
            "Berdiri membungkuk dengan satu tangan bertumpu di meja.\nBiarkan lengan yang sakit menggantung rileks.\nAyunkan lengan perlahan membentuk lingkaran kecil, searah lalu berlawanan arah jarum jam."],
        'wall-walk' => ['Jari merambat di dinding', 'rom', 'shoulder', 'reps',
            "Berdiri menghadap dinding.\nRambatkan jari-jari ke atas dinding setinggi yang nyaman.\nTurunkan perlahan dengan cara yang sama."],
        'neck-rotation' => ['Putar kepala perlahan', 'rom', 'neck', 'reps',
            "Duduk tegak, bahu rileks.\nPutar kepala perlahan menoleh ke kanan sejauh nyaman, kembali ke tengah, lalu ke kiri.\nJangan memaksakan gerakan."],

        // ---------------------------------------------------------------- keseimbangan
        'weight-shift' => ['Memindahkan berat badan', 'balance', 'general', 'seconds',
            "Berdiri di belakang kursi kokoh dan berpegangan.\nPindahkan berat badan perlahan ke kaki kanan, tahan, lalu ke kaki kiri.\nJaga badan tetap tegak."],
        'tandem-stance' => ['Berdiri satu garis (tandem)', 'balance', 'general', 'seconds',
            "Berdiri di samping meja atau kursi dan berpegangan.\nLetakkan satu kaki tepat di depan kaki lainnya (tumit menyentuh ujung jari).\nTahan, lalu ganti kaki depan."],
        'single-leg-stance' => ['Berdiri satu kaki', 'balance', 'general', 'seconds',
            "Berdiri di belakang kursi kokoh dan berpegangan.\nAngkat satu kaki sedikit dari lantai.\nTahan, lalu ganti kaki. Lakukan hanya dengan pegangan dan pendamping bila perlu."],

        // ---------------------------------------------------------------- pernapasan
        'diaphragmatic-breathing' => ['Pernapasan diafragma', 'breathing', 'general', 'reps',
            "Duduk atau berbaring nyaman, satu tangan di dada dan satu di perut.\nTarik napas perlahan lewat hidung sampai perut terangkat, dada tetap tenang.\nBuang napas perlahan lewat mulut."],
        'pursed-lip-breathing' => ['Pursed-lip breathing', 'breathing', 'general', 'reps',
            "Duduk rileks.\nTarik napas lewat hidung selama 2 hitungan.\nBuang napas lewat bibir yang dimonyongkan (seperti meniup lilin) selama 4 hitungan."],
    ];

    /** Upserts the library into the exercises table (seeder, or lazily before the first program is built). */
    public static function sync(): void
    {
        // without events: BelongsToClinic would otherwise give the global library the signed-in user's clinic
        Exercise::withoutEvents(function () {
            foreach (self::ITEMS as $slug => [$name, $type, $area, $unit, $instructions]) {
                Exercise::withoutGlobalScopes()->updateOrCreate(['slug' => $slug], [
                    'clinic_id' => null,
                    'name' => $name,
                    'type' => $type,
                    'body_area' => $area,
                    'dose_unit' => $unit,
                    'instructions' => $instructions,
                    'precautions' => self::GENERAL_PRECAUTION,
                ]);
            }
        });
    }

    public static function isSynced(): bool
    {
        return Exercise::withoutGlobalScopes()->whereNull('clinic_id')->whereIn('slug', array_keys(self::ITEMS))->count() === count(self::ITEMS);
    }
}
