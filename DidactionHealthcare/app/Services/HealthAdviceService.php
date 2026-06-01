<?php

namespace App\Services;

/**
 * HealthAdviceService
 *
 * Generate saran kesehatan personal berdasarkan penyakit yang diprediksi.
 * Includes: pola makan, olahraga, peringatan medis.
 */
class HealthAdviceService
{
    /**
     * Database saran per penyakit.
     */
    private array $adviceDatabase = [];

    public function __construct()
    {
        $this->initializeAdviceDatabase();
    }

    /**
     * Generate respons lengkap sesuai format spec.
     */
    public function generateFullResponse(array $predictions, array $symptoms): array
    {
        if (empty($predictions)) {
            return [
                'status' => 'no_prediction',
                'message' => 'Tidak dapat membuat prediksi dari gejala yang diberikan',
            ];
        }

        $topDisease = $predictions[0]['disease'];
        $diseaseData = $this->adviceDatabase[$topDisease] ?? null;

        if (!$diseaseData) {
            return [
                'status' => 'error',
                'message' => 'Data saran tidak tersedia untuk penyakit ini',
            ];
        }

        return [
            'status' => 'success',
            'symptoms_reported' => $this->formatSymptoms($symptoms),
            'predictions' => $this->formatPredictions($predictions),
            'explanation' => $this->getExplanation($topDisease, $symptoms),
            'food_recommendations' => $diseaseData['food'],
            'exercise_recommendations' => $diseaseData['exercise'],
            'medical_warning' => $this->getMedicalWarning($topDisease),
        ];
    }

    /**
     * Format gejala yang dilaporkan.
     */
    private function formatSymptoms(array $symptoms): array
    {
        return [
            'Usia' => $symptoms['age'] . ' tahun',
            'Jenis Kelamin' => $symptoms['gender'] == 1 ? 'Laki-laki' : 'Perempuan',
            'Glukosa Darah' => $symptoms['glucose'] . ' mg/dL',
            'Tekanan Darah Sistolik' => $symptoms['blood_pressure'] . ' mmHg',
            'BMI' => $symptoms['bmi'] . ' kg/m²',
        ];
    }

    /**
     * Format prediksi penyakit (top 3).
     */
    private function formatPredictions(array $predictions): array
    {
        $icons = ['🔴', '🟡', '🟢'];
        $formatted = [];

        foreach (array_slice($predictions, 0, 3) as $index => $pred) {
            $label = match($index) {
                0 => 'Kemungkinan Utama',
                1 => 'Kemungkinan Kedua',
                2 => 'Kemungkinan Ketiga',
                default => 'Lainnya',
            };

            $formatted[] = [
                'rank' => $index + 1,
                'icon' => $icons[$index] ?? '⚪',
                'label' => $label,
                'disease' => $pred['name'],
                'confidence' => $pred['confidence'] . '%',
            ];
        }

        return $formatted;
    }

    /**
     * Get penjelasan mengapa gejala mengarah ke penyakit.
     */
    private function getExplanation(string $disease, array $symptoms): string
    {
        $explanations = [
            'diabetes' => sprintf(
                'Kadar glukosa darah %d mg/dL termasuk tinggi, yang merupakan indikator utama diabetes. Kombinasi dengan BMI %.1f dan usia %d tahun meningkatkan risiko signifikan.',
                $symptoms['glucose'],
                $symptoms['bmi'],
                $symptoms['age']
            ),
            'hipertensi' => sprintf(
                'Tekanan darah sistolik %d mmHg termasuk tinggi (normal: <120 mmHg). Ini adalah ciri utama hipertensi, terutama pada usia %d tahun dengan BMI %.1f.',
                $symptoms['blood_pressure'],
                $symptoms['age'],
                $symptoms['bmi']
            ),
            'penyakit_jantung' => sprintf(
                'Kombinasi tekanan darah %d mmHg, glukosa %d mg/dL, dan BMI %.1f menunjukkan faktor risiko cardiovascular yang signifikan berdasarkan data historis.',
                $symptoms['blood_pressure'],
                $symptoms['glucose'],
                $symptoms['bmi']
            ),
            'stroke' => sprintf(
                'Tekanan darah tinggi (%d mmHg) dan glukosa tinggi (%d mg/dL) adalah faktor risiko stroke utama, terutama pada usia %d tahun.',
                $symptoms['blood_pressure'],
                $symptoms['glucose'],
                $symptoms['age']
            ),
            'penyakit_ginjal_kronis' => sprintf(
                'Tekanan darah tinggi (%d mmHg) dan glukosa tinggi (%d mg/dL) dapat merusak fungsi ginjal dari waktu ke waktu, dengan BMI %.1f.',
                $symptoms['blood_pressure'],
                $symptoms['glucose'],
                $symptoms['bmi']
            ),
        ];

        return $explanations[$disease] ?? 'Kondisi memerlukan evaluasi lebih lanjut dari tenaga medis profesional.';
    }

    /**
     * Get medical warning berdasarkan penyakit.
     */
    private function getMedicalWarning(string $disease): array
    {
        $warnings = [
            'diabetes' => [
                'disclaimer' => 'Prediksi ini dihasilkan oleh model AI berdasarkan data gejala dan BUKAN merupakan diagnosis medis resmi. Segera konsultasikan hasil ini kepada dokter atau tenaga kesehatan profesional untuk pemeriksaan lebih lanjut.',
                'red_flags' => [
                    'Rasa haus yang berlebihan',
                    'Sering buang air kecil',
                    'Lelah dan lemas tanpa sebab jelas',
                    'Penglihatan kabur',
                    'Luka yang sulit sembuh',
                ],
                'when_to_see_doctor' => 'Segera ke dokter jika mengalami gejala di atas atau konsultasi rutin setiap 3 bulan.',
                'when_to_er' => 'Langsung ke IGD jika mengalami: kesadaran menurun, napas cepat/berat, atau kadar gula darah sangat tinggi (>400 mg/dL).',
            ],
            'hipertensi' => [
                'disclaimer' => 'Prediksi ini dihasilkan oleh model AI berdasarkan data gejala dan BUKAN merupakan diagnosis medis resmi. Segera konsultasikan hasil ini kepada dokter atau tenaga kesehatan profesional untuk pemeriksaan lebih lanjut.',
                'red_flags' => [
                    'Sakit kepala parah',
                    'Nyeri dada',
                    'Sesak napas',
                    'Pandangan kabur',
                    'Mimisan',
                ],
                'when_to_see_doctor' => 'Konsultasi rutin setiap bulan untuk monitoring tekanan darah dan penyesuaian obat.',
                'when_to_er' => 'Langsung ke IGD jika tekanan darah mendadak sangat tinggi (>180/120) dengan gejala sakit kepala, nyeri dada, atau sesak napas.',
            ],
            'penyakit_jantung' => [
                'disclaimer' => 'Prediksi ini dihasilkan oleh model AI berdasarkan data gejala dan BUKAN merupakan diagnosis medis resmi. Segera konsultasikan hasil ini kepada dokter atau tenaga kesehatan profesional untuk pemeriksaan lebih lanjut.',
                'red_flags' => [
                    'Nyeri dada atau terasa tertekan',
                    'Sesak napas',
                    'Nyeri menjalar ke lengan/bahu/leher',
                    'Keringat dingin',
                    'Mual atau pusing',
                ],
                'when_to_see_doctor' => 'Konsultasi ke kardiolog untuk EKG, stress test, atau angiografi jika diperlukan.',
                'when_to_er' => 'LANGSUNG KE IGD jika nyeri dada atau sesak napas tiba-tiba. Panggil ambulans dan jangan menunggu!',
            ],
            'stroke' => [
                'disclaimer' => 'Prediksi ini dihasilkan oleh model AI berdasarkan data gejala dan BUKAN merupakan diagnosis medis resmi. Segera konsultasikan hasil ini kepada dokter atau tenaga kesehatan profesional untuk pemeriksaan lebih lanjut.',
                'red_flags' => [
                    'Kelemahan mendadak di satu sisi tubuh',
                    'Kesulitan berbicara atau memahami kata-kata',
                    'Penglihatan kabur di satu mata',
                    'Kehilangan keseimbangan',
                    'Sakit kepala parah tanpa sebab',
                ],
                'when_to_see_doctor' => 'Konsultasi rutin untuk monitoring faktor risiko (hipertensi, diabetes, kolesterol).',
                'when_to_er' => 'SEGERA KE IGD jika ada gejala stroke! Ingat: FAST (Face, Arms, Speech, Time). Stroke adalah keadaan darurat medis - setiap menit sangat penting!',
            ],
            'penyakit_ginjal_kronis' => [
                'disclaimer' => 'Prediksi ini dihasilkan oleh model AI berdasarkan data gejala dan BUKAN merupakan diagnosis medis resmi. Segera konsultasikan hasil ini kepada dokter atau tenaga kesehatan profesional untuk pemeriksaan lebih lanjut.',
                'red_flags' => [
                    'Air kemih berbusa atau berdarah',
                    'Pembengkakan di kaki atau wajah',
                    'Kelelahan ekstrem',
                    'Tekanan darah tinggi',
                    'Nafsu makan menurun',
                ],
                'when_to_see_doctor' => 'Konsultasi ke nephrologist untuk tes fungsi ginjal (creatinine, BUN) secara berkala.',
                'when_to_er' => 'Ke IGD jika: pembengkakan ekstrem, sesak napas tiba-tiba, atau urine tidak keluar dalam 24 jam.',
            ],
        ];

        return $warnings[$disease] ?? [
            'disclaimer' => 'Prediksi ini BUKAN diagnosis medis. Konsultasikan ke dokter untuk evaluasi lebih lanjut.',
            'red_flags' => [],
            'when_to_see_doctor' => 'Segera konsultasi ke dokter',
            'when_to_er' => 'Jika gejala memburuk, langsung ke IGD',
        ];
    }

    /**
     * Initialize advice database.
     */
    private function initializeAdviceDatabase(): void
    {
        $this->adviceDatabase = [
            'diabetes' => [
                'food' => [
                    'recommended' => [
                        [
                            'name' => 'Oat (gandum murni)',
                            'reason' => 'Kaya serat larut yang memperlambat penyerapan gula darah'
                        ],
                        [
                            'name' => 'Sayuran hijau (bayam, brokoli, buncis)',
                            'reason' => 'Rendah kalori, kaya nutrisi, minimal gula'
                        ],
                        [
                            'name' => 'Ikan berlemak (salmon, mackerel)',
                            'reason' => 'Omega-3 membantu mengurangi peradangan dan risiko penyakit jantung'
                        ],
                        [
                            'name' => 'Almond dan kacang-kacangan',
                            'reason' => 'Protein dan lemak baik yang membantu stabilitas gula darah'
                        ],
                        [
                            'name' => 'Buah dengan indeks glikemik rendah (apel, jeruk, berry)',
                            'reason' => 'Kaya serat dan vitamin tanpa lonjakan gula darah mendadak'
                        ],
                        [
                            'name' => 'Yogurt tanpa gula',
                            'reason' => 'Probiotik baik untuk kesehatan dan kadar gula stabil'
                        ],
                    ],
                    'avoid' => [
                        [
                            'name' => 'Minuman manis (soft drink, jus manis)',
                            'reason' => 'Gula murni langsung meningkatkan kadar glukosa darah drastis'
                        ],
                        [
                            'name' => 'Kue, roti putih, nasi putih',
                            'reason' => 'Karbohidrat sederhana yang cepat naik gula darah'
                        ],
                        [
                            'name' => 'Gorengan dan makanan berlemak tinggi',
                            'reason' => 'Meningkatkan berat badan dan resistansi insulin'
                        ],
                        [
                            'name' => 'Permen dan dessert manis',
                            'reason' => 'Gula terpusat dalam jumlah tinggi'
                        ],
                        [
                            'name' => 'Makanan olahan dan instan',
                            'reason' => 'Mengandung gula tersembunyi dan tinggi natrium'
                        ],
                        [
                            'name' => 'Buah kering dan manisan',
                            'reason' => 'Gula terkonsentrasi dan rendah serat'
                        ],
                    ],
                    'daily_pattern' => [
                        'breakfast' => '07:00 - Sarapan sehat: oat dengan buah, atau roti gandum dengan telur',
                        'snack_morning' => '10:00 - Camilan: 1 apel atau segenggam almond',
                        'lunch' => '12:30 - Makan siang: ikan/ayam, sayuran hijau, nasi merah',
                        'snack_afternoon' => '15:00 - Yogurt tanpa gula atau kacang-kacangan',
                        'dinner' => '18:30 - Makan malam: sayuran, protein, porsi karbohidrat kurang dari pagi',
                        'note' => 'Porsi: 1 piring (ukuran standar), jangan menambah 2x lipat. Minum air putih minimal 8 gelas/hari.',
                    ],
                ],
                'exercise' => [
                    'recommended' => [
                        [
                            'type' => 'Jalan cepat/bersepeda',
                            'duration' => '150 menit/minggu (dibagi 5 hari)',
                            'frequency' => '5x seminggu, 30 menit setiap kali',
                            'benefit' => 'Meningkatkan sensitivitas insulin dan mengontrol gula darah'
                        ],
                        [
                            'type' => 'Latihan kekuatan (weights, bodyweight)',
                            'duration' => '2x seminggu',
                            'frequency' => '2 hari per minggu, 20-30 menit',
                            'benefit' => 'Membangun otot yang membantu mengontrol glukosa'
                        ],
                        [
                            'type' => 'Yoga atau stretching',
                            'duration' => 'Setiap hari',
                            'frequency' => '10-15 menit',
                            'benefit' => 'Mengurangi stress yang mempengaruhi kadar gula'
                        ],
                        [
                            'type' => 'Berenang',
                            'duration' => '30-45 menit',
                            'frequency' => '2-3x seminggu',
                            'benefit' => 'Olahraga low-impact yang meningkatkan kardiovaskular'
                        ],
                    ],
                    'avoid' => [
                        [
                            'type' => 'Olahraga ekstrem/intensitas tinggi tanpa persiapan',
                            'reason' => 'Bisa menyebabkan fluktuasi gula darah dan hipoglikemia'
                        ],
                        [
                            'type' => 'Olahraga tanpa makan/minum',
                            'reason' => 'Risiko gula darah jatuh drastis'
                        ],
                    ],
                    'tips' => [
                        'Minum air sebelum, selama, dan sesudah olahraga',
                        'Siapkan camilan kecil (apel, kacang) jika olahraga >60 menit',
                        'Olahraga terbaik adalah 1-2 jam setelah makan',
                        'Hentikan olahraga jika terasa pusing, nyeri dada, atau sesak napas',
                    ],
                ],
            ],
            'hipertensi' => [
                'food' => [
                    'recommended' => [
                        [
                            'name' => 'Buah-buahan kaya kalium (pisang, jeruk, alpukat)',
                            'reason' => 'Kalium membantu menurunkan tekanan darah'
                        ],
                        [
                            'name' => 'Sayuran berdaun hijau (bayam, kale)',
                            'reason' => 'Rendah natrium, kaya mineral penurun tekanan darah'
                        ],
                        [
                            'name' => 'Ikan berlemak (salmon, tuna)',
                            'reason' => 'Omega-3 membantu menenangkan pembuluh darah'
                        ],
                        [
                            'name' => 'Whole grain (oat, gandum)',
                            'reason' => 'Serat larut mengurangi tekanan darah'
                        ],
                        [
                            'name' => 'Kacang-kacangan dan biji-bijian',
                            'reason' => 'Magnesium membantu relaksasi pembuluh darah'
                        ],
                        [
                            'name' => 'Susu rendah lemak',
                            'reason' => 'Kalsium penting untuk regulasi tekanan darah'
                        ],
                    ],
                    'avoid' => [
                        [
                            'name' => 'Garam dan makanan asin',
                            'reason' => 'Natrium naik tekanan darah secara langsung (<2300mg/hari)'
                        ],
                        [
                            'name' => 'Makanan olahan (ham, sosis, kornet)',
                            'reason' => 'Tinggi natrium dan pengawet berbahaya'
                        ],
                        [
                            'name' => 'Minuman beralkohol',
                            'reason' => 'Meningkatkan tekanan darah dan merusak jantung'
                        ],
                        [
                            'name' => 'Minuman berkafein berlebihan (kopi, teh)',
                            'reason' => 'Kafein meningkatkan tekanan darah sementara'
                        ],
                        [
                            'name' => 'Makanan berlemak tinggi',
                            'reason' => 'Kolesterol tinggi memperparah kondisi'
                        ],
                        [
                            'name' => 'Minuman manis',
                            'reason' => 'Meningkatkan berat badan yang naik tekanan darah'
                        ],
                    ],
                    'daily_pattern' => [
                        'breakfast' => '07:00 - Oat dengan pisang dan madu',
                        'snack_morning' => '10:00 - Jeruk atau alpukat',
                        'lunch' => '12:30 - Salmon panggang, sayuran hijau, nasi merah',
                        'snack_afternoon' => '15:00 - Kacang-kacangan tanpa garam',
                        'dinner' => '18:30 - Ayam tanpa kulit, buncis, tahu',
                        'note' => 'BATASI GARAM! Target: <2300mg natrium/hari. Cek tekanan darah secara rutin.',
                    ],
                ],
                'exercise' => [
                    'recommended' => [
                        [
                            'type' => 'Jalan cepat/jogging ringan',
                            'duration' => '150 menit/minggu',
                            'frequency' => '5x seminggu, 30 menit',
                            'benefit' => 'Menurunkan tekanan darah dan memperkuat jantung'
                        ],
                        [
                            'type' => 'Bersepeda stabil',
                            'duration' => '30-45 menit',
                            'frequency' => '3-4x seminggu',
                            'benefit' => 'Latihan kardio aman untuk hipertensi'
                        ],
                        [
                            'type' => 'Berenang',
                            'duration' => '30 menit',
                            'frequency' => '3x seminggu',
                            'benefit' => 'Low-impact, full body workout'
                        ],
                        [
                            'type' => 'Yoga/Tai Chi',
                            'duration' => '30 menit',
                            'frequency' => '4-5x seminggu',
                            'benefit' => 'Relaksasi dan stress management'
                        ],
                    ],
                    'avoid' => [
                        [
                            'type' => 'Angkat beban berat',
                            'reason' => 'Bisa lonjakan tekanan darah mendadak'
                        ],
                        [
                            'type' => 'Olahraga intensitas tinggi tanpa warm-up',
                            'reason' => 'Risiko stroke atau serangan jantung'
                        ],
                    ],
                    'tips' => [
                        'Mulai pelan-pelan, tingkatkan intensitas secara bertahap',
                        'Warm-up 5 menit, cool-down 5 menit',
                        'Monitor detak jantung: target 50-70% dari max (max = 220 - usia)',
                        'Hentikan jika sesak, nyeri dada, atau pusing',
                    ],
                ],
            ],
            'penyakit_jantung' => [
                'food' => [
                    'recommended' => [
                        [
                            'name' => 'Ikan lemak (salmon, mackerel, sardine)',
                            'reason' => 'Omega-3 mengurangi peradangan dan menormalkan ritme jantung'
                        ],
                        [
                            'name' => 'Oat dan whole grains',
                            'reason' => 'Serat larut menurunkan kolesterol LDL'
                        ],
                        [
                            'name' => 'Buah dan sayuran berwarna',
                            'reason' => 'Antioksidan melindungi jantung dari kerusakan'
                        ],
                        [
                            'name' => 'Olive oil (minyak zaitun)',
                            'reason' => 'Lemak sehat yang menurunkan kolesterol jahat'
                        ],
                        [
                            'name' => 'Kacang almond dan walnut',
                            'reason' => 'Arginine dan lemak sehat untuk kesehatan pembuluh darah'
                        ],
                        [
                            'name' => 'Coklat hitam (70%+ cocoa)',
                            'reason' => 'Flavonoid menurunkan tekanan darah'
                        ],
                    ],
                    'avoid' => [
                        [
                            'name' => 'Lemak jenuh dan trans fat',
                            'reason' => 'Meningkatkan kolesterol LDL dan plak arteri'
                        ],
                        [
                            'name' => 'Makanan asin',
                            'reason' => 'Menaikkan tekanan darah'
                        ],
                        [
                            'name' => 'Minuman beralkohol berlebihan',
                            'reason' => 'Melemahkan fungsi jantung'
                        ],
                        [
                            'name' => 'Makanan olahan',
                            'reason' => 'Tinggi natrium dan kolesterol'
                        ],
                        [
                            'name' => 'Kopi berlebihan',
                            'reason' => 'Kafein tinggi bisa memicu aritmia'
                        ],
                        [
                            'name' => 'Makanan cepat saji',
                            'reason' => 'Tinggi lemak trans dan natrium'
                        ],
                    ],
                    'daily_pattern' => [
                        'breakfast' => '07:00 - Oat dengan berry dan almond',
                        'snack_morning' => '10:00 - Apel atau orange',
                        'lunch' => '12:30 - Salmon baked, spinach, sweet potato',
                        'snack_afternoon' => '15:00 - Segelintir walnut',
                        'dinner' => '18:30 - Ayam tanpa kulit, brokoli, nasi merah',
                        'note' => 'Target kolesterol total <200mg/dL. Monitor tekanan darah dan detak jantung.',
                    ],
                ],
                'exercise' => [
                    'recommended' => [
                        [
                            'type' => 'Jalan cepat',
                            'duration' => '150 menit/minggu',
                            'frequency' => '5x seminggu, 30 menit',
                            'benefit' => 'Memperkuat jantung tanpa membebani'
                        ],
                        [
                            'type' => 'Berenang atau aqua aerobic',
                            'duration' => '30-45 menit',
                            'frequency' => '3x seminggu',
                            'benefit' => 'Latihan kardio low-impact'
                        ],
                        [
                            'type' => 'Bersepeda stabil',
                            'duration' => '30 menit',
                            'frequency' => '3-4x seminggu',
                            'benefit' => 'Meningkatkan kapasitas kardiovaskular'
                        ],
                        [
                            'type' => 'Latihan pernapasan dalam',
                            'duration' => '10-15 menit',
                            'frequency' => 'Setiap hari',
                            'benefit' => 'Relaksasi dan stress management'
                        ],
                    ],
                    'avoid' => [
                        [
                            'type' => 'Olahraga intensitas tinggi',
                            'reason' => 'Beban pada jantung terlalu berat'
                        ],
                        [
                            'type' => 'Olahraga kompetitif yang stresful',
                            'reason' => 'Stress meningkatkan beban jantung'
                        ],
                    ],
                    'tips' => [
                        'Selalu lakukan medical check-up sebelum mulai olahraga baru',
                        'Pakai alat pemantau detak jantung',
                        'Hentikan jika nyeri dada, sesak napas, atau pusing',
                        'Olahraga di bawah pengawasan dokter atau trainer berpengalaman',
                        'Jangan olahraga dalam cuaca ekstrem (terlalu panas/dingin)',
                    ],
                ],
            ],
            'stroke' => [
                'food' => [
                    'recommended' => [
                        [
                            'name' => 'Sayuran berdaun hijau (bayam, kale)',
                            'reason' => 'Vitamin K membantu pembekuan darah yang sehat'
                        ],
                        [
                            'name' => 'Ikan berlemak (salmon)',
                            'reason' => 'Omega-3 mencegah pembekuan darah abnormal'
                        ],
                        [
                            'name' => 'Bawang putih dan bawang merah',
                            'reason' => 'Mengurangi kemampuan darah menggumpal berlebihan'
                        ],
                        [
                            'name' => 'Buah berry dan jeruk',
                            'reason' => 'Vitamin C dan bioflavonoid memperkuat pembuluh darah'
                        ],
                        [
                            'name' => 'Teh hijau',
                            'reason' => 'Antioksidan melindungi pembuluh darah otak'
                        ],
                        [
                            'name' => 'Whole grain dan kacang',
                            'reason' => 'Kaya serat untuk tekanan darah sehat'
                        ],
                    ],
                    'avoid' => [
                        [
                            'name' => 'Garam berlebihan',
                            'reason' => 'Meningkatkan tekanan darah, risiko stroke'
                        ],
                        [
                            'name' => 'Minuman beralkohol',
                            'reason' => 'Meningkatkan tekanan darah drastis'
                        ],
                        [
                            'name' => 'Kolesterol tinggi (gorengan, lemak)',
                            'reason' => 'Plak di arteri memicu stroke'
                        ],
                        [
                            'name' => 'Minuman manis',
                            'reason' => 'Meningkatkan berat badan dan tekanan darah'
                        ],
                        [
                            'name' => 'Makanan siap saji',
                            'reason' => 'Natrium dan kolesterol tinggi'
                        ],
                        [
                            'name' => 'Kafein berlebihan',
                            'reason' => 'Bisa menaikkan tekanan darah'
                        ],
                    ],
                    'daily_pattern' => [
                        'breakfast' => '07:00 - Oat dengan buah berry',
                        'snack_morning' => '10:00 - Jus jeruk segar (tanpa gula)',
                        'lunch' => '12:30 - Salmon panggang, bayam, nasi merah',
                        'snack_afternoon' => '15:00 - Kacang-kacangan',
                        'dinner' => '18:30 - Ayam, brokoli, tahu',
                        'note' => 'STRICT: Kontrol garam, kolesterol, dan tekanan darah. Target TDS <130 mmHg.',
                    ],
                ],
                'exercise' => [
                    'recommended' => [
                        [
                            'type' => 'Jalan ringan/bersepeda',
                            'duration' => '150 menit/minggu',
                            'frequency' => '5x seminggu, 30 menit',
                            'benefit' => 'Meningkatkan aliran darah otak'
                        ],
                        [
                            'type' => 'Berenang',
                            'duration' => '30 menit',
                            'frequency' => '3x seminggu',
                            'benefit' => 'Latihan full-body tanpa tekanan'
                        ],
                        [
                            'type' => 'Tai Chi',
                            'duration' => '30 menit',
                            'frequency' => '3x seminggu',
                            'benefit' => 'Keseimbangan dan koordinasi'
                        ],
                        [
                            'type' => 'Yoga dengan fokus keseimbangan',
                            'duration' => '20-30 menit',
                            'frequency' => 'Setiap hari',
                            'benefit' => 'Mencegah jatuh dan meningkatkan koordinasi'
                        ],
                    ],
                    'avoid' => [
                        [
                            'type' => 'Olahraga intensitas tinggi',
                            'reason' => 'Risiko serangan stroke berulang'
                        ],
                        [
                            'type' => 'Olahraga kontak (tinju, karate)',
                            'reason' => 'Berisiko cedera kepala'
                        ],
                    ],
                    'tips' => [
                        'Penting: Bekerja sama dengan terapis fisik profesional',
                        'Latihan keseimbangan dan koordinasi sangat penting',
                        'Hentikan jika ada gejala stroke berulang: kelemahan, bicara cadel',
                        'Monitor tekanan darah sebelum dan sesudah olahraga',
                    ],
                ],
            ],
            'penyakit_ginjal_kronis' => [
                'food' => [
                    'recommended' => [
                        [
                            'name' => 'Sayuran rendah kalium (kol, wortel, timun)',
                            'reason' => 'Kalium tinggi merusak ginjal yang sudah lemah'
                        ],
                        [
                            'name' => 'Protein berkualitas tinggi (telur putih, ikan)',
                            'reason' => 'Protein baik tetapi dalam porsi terbatas'
                        ],
                        [
                            'name' => 'Roti putih, nasi putih',
                            'reason' => 'Kalori tanpa beban kalium untuk ginjal'
                        ],
                        [
                            'name' => 'Olive oil',
                            'reason' => 'Lemak sehat tanpa protein berlebihan'
                        ],
                        [
                            'name' => 'Apel, pir, nanas',
                            'reason' => 'Buah rendah kalium dan fosfor'
                        ],
                        [
                            'name' => 'Susu rendah fosfor (khusus untuk CKD)',
                            'reason' => 'Jika fosfor belum terlalu tinggi'
                        ],
                    ],
                    'avoid' => [
                        [
                            'name' => 'Garam dan natrium',
                            'reason' => 'Meningkatkan tekanan darah dan beban ginjal'
                        ],
                        [
                            'name' => 'Kalium tinggi (pisang, jeruk, tomat)',
                            'reason' => 'Ginjal tidak bisa mengatur kalium, berbahaya untuk jantung'
                        ],
                        [
                            'name' => 'Fosfor tinggi (daging merah, susu)',
                            'reason' => 'Melemahkan tulang dan ginjal'
                        ],
                        [
                            'name' => 'Minuman beralkohol',
                            'reason' => 'Merusak ginjal lebih lanjut'
                        ],
                        [
                            'name' => 'Makanan olahan',
                            'reason' => 'Tinggi natrium dan fosfor'
                        ],
                        [
                            'name' => 'Kafein berlebihan',
                            'reason' => 'Meningkatkan asam urine'
                        ],
                    ],
                    'daily_pattern' => [
                        'breakfast' => '07:00 - Roti putih dengan telur putih, teh tawar',
                        'snack_morning' => '10:00 - Apel atau pir',
                        'lunch' => '12:30 - Ikan putih, kol rebus, nasi putih',
                        'snack_afternoon' => '15:00 - Roti tawar, mentega',
                        'dinner' => '18:30 - Dada ayam, wortel, beras',
                        'note' => 'VERY IMPORTANT: Batasi cairan jika pada stage lanjut. Monitor electrolyte (K, Na, P). Konsultasi ahli gizi nephrologist.',
                    ],
                ],
                'exercise' => [
                    'recommended' => [
                        [
                            'type' => 'Jalan ringan',
                            'duration' => '20-30 menit',
                            'frequency' => '3-5x seminggu',
                            'benefit' => 'Menjaga kesehatan umum tanpa stress ginjal'
                        ],
                        [
                            'type' => 'Yoga ringan',
                            'duration' => '15-20 menit',
                            'frequency' => '3x seminggu',
                            'benefit' => 'Stress management'
                        ],
                        [
                            'type' => 'Stretching',
                            'duration' => '10 menit',
                            'frequency' => 'Setiap hari',
                            'benefit' => 'Fleksibilitas'
                        ],
                    ],
                    'avoid' => [
                        [
                            'type' => 'Olahraga intensitas tinggi',
                            'reason' => 'Stress metabolik pada ginjal'
                        ],
                        [
                            'type' => 'Angkat beban berat',
                            'reason' => 'Bisa meningkatkan tekanan dalam ginjal'
                        ],
                    ],
                    'tips' => [
                        'Konsultasi dokter sebelum mulai olahraga apapun',
                        'Hindari dehidrasi tetapi juga jangan minum berlebihan',
                        'Monitor tekanan darah sebelum dan sesudah',
                        'Hentikan jika sesak napas atau tidak enak badan',
                        'Prioritas: Istirahat cukup dan stress management',
                    ],
                ],
            ],
        ];
    }
}
