<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_health_records_table
 *
 * Tabel ini menyimpan:
 *  - Input fitur kesehatan dari pengguna (age, gender, bmi, glucose, blood_pressure)
 *  - Hasil prediksi ML untuk 5 penyakit (float 0.0–1.0)
 *  - Saran / rekomendasi dari LLM (longText)
 *  - Metadata (user_id, timestamps, soft delete)
 */
return new class extends Migration
{
    /**
     * Driver koneksi yang digunakan.
     * Pastikan config/database.php mysql sudah dikonfigurasi.
     */
    protected $connection = 'mysql';

    public function up(): void
    {
        Schema::connection('mysql')->create('health_records', function (Blueprint $table) {

            // ── Primary Key ─────────────────────────────────────────────────
            $table->id();

            // ── Relasi ke User ───────────────────────────────────────────────
            // nullable agar guest (belum login) tetap bisa menyimpan record
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // ══════════════════════════════════════════════════════════════════
            //  INPUT FITUR KESEHATAN PASIEN
            // ══════════════════════════════════════════════════════════════════

            // Usia pasien (tahun)
            $table->unsignedTinyInteger('age')
                  ->comment('Usia pasien dalam tahun (0–120)');

            // Jenis kelamin: 0 = Perempuan, 1 = Laki-laki
            $table->tinyInteger('gender')
                  ->default(1)
                  ->comment('0 = Perempuan, 1 = Laki-laki');

            // Body Mass Index (kg/m²) — presisi 2 desimal
            $table->decimal('bmi', 5, 2)
                  ->comment('Body Mass Index dalam kg/m² (contoh: 27.50)');

            // Kadar glukosa darah (mg/dL)
            $table->decimal('glucose', 6, 2)
                  ->comment('Kadar glukosa darah dalam mg/dL');

            // Tekanan darah sistolik (mmHg)
            $table->unsignedSmallInteger('blood_pressure')
                  ->comment('Tekanan darah sistolik dalam mmHg');

            // Kolesterol total (mg/dL) — opsional, nullable
            $table->decimal('cholesterol', 6, 2)
                  ->nullable()
                  ->comment('Total kolesterol dalam mg/dL (opsional)');

            // Detak jantung (bpm) — opsional, nullable
            $table->unsignedSmallInteger('heart_rate')
                  ->nullable()
                  ->comment('Detak jantung dalam beats per minute (opsional)');

            // ══════════════════════════════════════════════════════════════════
            //  HASIL PREDIKSI ML — PROBABILITAS PER PENYAKIT (0.0000 – 1.0000)
            // ══════════════════════════════════════════════════════════════════

            // Risiko Diabetes
            $table->decimal('diabetes_risk', 5, 4)
                  ->nullable()
                  ->comment('Probabilitas diabetes dari model ML (0.0000–1.0000)');

            // Risiko Hipertensi
            $table->decimal('hypertension_risk', 5, 4)
                  ->nullable()
                  ->comment('Probabilitas hipertensi dari model ML (0.0000–1.0000)');

            // Risiko Penyakit Jantung
            $table->decimal('heart_disease_risk', 5, 4)
                  ->nullable()
                  ->comment('Probabilitas penyakit jantung dari model ML (0.0000–1.0000)');

            // Risiko Stroke
            $table->decimal('stroke_risk', 5, 4)
                  ->nullable()
                  ->comment('Probabilitas stroke dari model ML (0.0000–1.0000)');

            // Risiko Chronic Kidney Disease
            $table->decimal('ckd_risk', 5, 4)
                  ->nullable()
                  ->comment('Probabilitas CKD dari model ML (0.0000–1.0000)');

            // Penyakit dengan risiko tertinggi (string label)
            $table->string('highest_risk_disease', 50)
                  ->nullable()
                  ->comment('Label penyakit dengan probabilitas tertinggi');

            // Mode model yang digunakan saat inference
            $table->string('model_mode', 20)
                  ->default('fallback')
                  ->comment('Mode model: pytorch | xgboost | fallback');

            // ══════════════════════════════════════════════════════════════════
            //  SARAN / REKOMENDASI DARI LLM
            // ══════════════════════════════════════════════════════════════════

            // Saran lengkap dalam format teks panjang (bisa Markdown/HTML)
            $table->longText('llm_advice')
                  ->nullable()
                  ->comment('Saran dan rekomendasi kesehatan yang digenerate oleh LLM');

            // Prompt yang dikirim ke LLM (untuk audit/debug)
            $table->text('llm_prompt')
                  ->nullable()
                  ->comment('Prompt yang dikirim ke LLM (untuk audit)');

            // Model LLM yang digunakan (contoh: gemini-pro, gpt-4o)
            $table->string('llm_model', 100)
                  ->nullable()
                  ->comment('Nama model LLM yang digunakan');

            // ── Status & Metadata ────────────────────────────────────────────
            // Status record: pending → processed → failed
            $table->enum('status', ['pending', 'processed', 'failed'])
                  ->default('pending')
                  ->comment('Status pemrosesan record');

            // IP address pengguna (untuk rate limiting / audit)
            $table->ipAddress('ip_address')
                  ->nullable()
                  ->comment('IP address pengguna saat submit');

            // ── Timestamps & Soft Delete ─────────────────────────────────────
            $table->timestamps();          // created_at, updated_at
            $table->softDeletes();         // deleted_at (soft delete)

            // ── Indexes ──────────────────────────────────────────────────────
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
            $table->index('highest_risk_disease');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('health_records');
    }
};
