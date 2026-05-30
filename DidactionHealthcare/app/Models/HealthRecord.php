<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Eloquent: HealthRecord
 *
 * Merepresentasikan satu sesi prediksi kesehatan pengguna yang mencakup:
 *  - Input fitur kesehatan (age, gender, bmi, glucose, blood_pressure, dll.)
 *  - Hasil prediksi ML untuk 5 penyakit (probabilitas 0.0–1.0)
 *  - Saran / rekomendasi dari LLM
 *
 * @property int         $id
 * @property int|null    $user_id
 * @property int         $age
 * @property int         $gender
 * @property float       $bmi
 * @property float       $glucose
 * @property int         $blood_pressure
 * @property float|null  $cholesterol
 * @property int|null    $heart_rate
 *
 * @property float|null  $diabetes_risk
 * @property float|null  $hypertension_risk
 * @property float|null  $heart_disease_risk
 * @property float|null  $stroke_risk
 * @property float|null  $ckd_risk
 * @property string|null $highest_risk_disease
 * @property string      $model_mode
 *
 * @property string|null $llm_advice
 * @property string|null $llm_prompt
 * @property string|null $llm_model
 *
 * @property string      $status
 * @property string|null $ip_address
 *
 * @property \Carbon\Carbon      $created_at
 * @property \Carbon\Carbon      $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read User|null $user
 */
class HealthRecord extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Nama tabel di database.
     */
    protected $table = 'health_records';

    /**
     * Koneksi database (PostgreSQL).
     */
    protected $connection = 'mysql';

    // ─── Mass Assignment ───────────────────────────────────────────────────────

    /**
     * Kolom yang boleh diisi melalui mass assignment (create / fill).
     */
    protected $fillable = [
        // Input pasien
        'user_id',
        'age',
        'gender',
        'bmi',
        'glucose',
        'blood_pressure',
        'cholesterol',
        'heart_rate',

        // Hasil prediksi ML
        'diabetes_risk',
        'hypertension_risk',
        'heart_disease_risk',
        'stroke_risk',
        'ckd_risk',
        'highest_risk_disease',
        'model_mode',

        // Saran LLM
        'llm_advice',
        'llm_prompt',
        'llm_model',

        // Metadata
        'status',
        'ip_address',
    ];

    /**
     * Kolom yang tidak boleh di-expose saat serialisasi (JSON / array).
     * llm_prompt disembunyikan karena bisa berisi data sensitif pasien.
     */
    protected $hidden = [
        'llm_prompt',
        'deleted_at',
    ];

    // ─── Casting ───────────────────────────────────────────────────────────────

    /**
     * Cast otomatis tipe data saat akses property.
     */
    protected $casts = [
        // Input pasien
        'age'            => 'integer',
        'gender'         => 'integer',
        'bmi'            => 'float',
        'glucose'        => 'float',
        'blood_pressure' => 'integer',
        'cholesterol'    => 'float',
        'heart_rate'     => 'integer',

        // Hasil ML — selalu float, nullable
        'diabetes_risk'      => 'float',
        'hypertension_risk'  => 'float',
        'heart_disease_risk' => 'float',
        'stroke_risk'        => 'float',
        'ckd_risk'           => 'float',

        // Timestamps
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ─── Relasi ────────────────────────────────────────────────────────────────

    /**
     * HealthRecord dimiliki oleh satu User (bisa null untuk guest).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Accessor / Attribute ──────────────────────────────────────────────────

    /**
     * Kembalikan semua risiko penyakit sebagai array asosiatif.
     * Berguna untuk dikirim ke view atau API response.
     *
     * @return array<string, float|null>
     */
    public function getAllRisksAttribute(): array
    {
        return [
            'diabetes'      => $this->diabetes_risk,
            'hypertension'  => $this->hypertension_risk,
            'heart_disease' => $this->heart_disease_risk,
            'stroke'        => $this->stroke_risk,
            'ckd'           => $this->ckd_risk,
        ];
    }

    /**
     * Kembalikan label risiko tertinggi beserta persentasenya.
     * Contoh: "Diabetes (68.2%)"
     */
    public function getHighestRiskSummaryAttribute(): string
    {
        $risks = array_filter($this->all_risks, fn($v) => $v !== null);

        if (empty($risks)) {
            return 'Tidak tersedia';
        }

        $maxKey  = array_key_max($risks);
        $maxVal  = $risks[$maxKey] * 100;
        $display = [
            'diabetes'      => 'Diabetes',
            'hypertension'  => 'Hipertensi',
            'heart_disease' => 'Penyakit Jantung',
            'stroke'        => 'Stroke',
            'ckd'           => 'Gagal Ginjal Kronis',
        ];

        return sprintf('%s (%.1f%%)', $display[$maxKey] ?? $maxKey, $maxVal);
    }

    /**
     * Kembalikan label kategori risiko berdasarkan probabilitas.
     *
     * @param  float $probability nilai 0.0–1.0
     * @return string "Rendah" | "Sedang" | "Tinggi"
     */
    public static function riskLabel(float $probability): string
    {
        return match(true) {
            $probability < 0.30 => 'Rendah',
            $probability < 0.60 => 'Sedang',
            default             => 'Tinggi',
        };
    }

    /**
     * Accessor: label risiko diabetes sebagai teks.
     */
    public function getDiabetesRiskLabelAttribute(): string
    {
        return $this->diabetes_risk !== null
            ? self::riskLabel($this->diabetes_risk)
            : 'Belum diproses';
    }

    /**
     * Accessor: label risiko hipertensi sebagai teks.
     */
    public function getHypertensionRiskLabelAttribute(): string
    {
        return $this->hypertension_risk !== null
            ? self::riskLabel($this->hypertension_risk)
            : 'Belum diproses';
    }

    /**
     * Apakah record ini sudah diproses (prediksi ML dan LLM sudah ada)?
     */
    public function getIsProcessedAttribute(): bool
    {
        return $this->status === 'processed'
            && $this->diabetes_risk !== null
            && $this->llm_advice !== null;
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    /**
     * Scope: hanya record yang sudah diproses.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Scope: hanya record yang masih pending.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: record dengan risiko diabetes di atas threshold.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  float $threshold default 0.6
     */
    public function scopeHighDiabetesRisk($query, float $threshold = 0.6)
    {
        return $query->where('diabetes_risk', '>=', $threshold);
    }

    /**
     * Scope: record dengan risiko hipertensi di atas threshold.
     */
    public function scopeHighHypertensionRisk($query, float $threshold = 0.6)
    {
        return $query->where('hypertension_risk', '>=', $threshold);
    }

    /**
     * Scope: record milik user tertentu.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ─── Business Logic Helpers ────────────────────────────────────────────────

    /**
     * Tandai record sebagai berhasil diproses setelah ML + LLM selesai.
     * Dipanggil dari controller setelah menerima response dari FastAPI.
     *
     * @param  array  $mlResults    Response dari POST /predict FastAPI
     * @param  string $llmAdvice    Teks saran dari LLM
     * @param  string $llmModel     Nama model LLM yang digunakan
     * @return bool
     */
    public function markAsProcessed(
        array  $mlResults,
        string $llmAdvice,
        string $llmModel = 'gemini-pro'
    ): bool {
        return $this->update([
            'diabetes_risk'       => $mlResults['diabetes']      ?? null,
            'hypertension_risk'   => $mlResults['hypertension']  ?? null,
            'heart_disease_risk'  => $mlResults['heart_disease'] ?? null,
            'stroke_risk'         => $mlResults['stroke']        ?? null,
            'ckd_risk'            => $mlResults['ckd']           ?? null,
            'highest_risk_disease'=> $mlResults['highest_risk']  ?? null,
            'model_mode'          => $mlResults['model_mode']    ?? 'fallback',
            'llm_advice'          => $llmAdvice,
            'llm_model'           => $llmModel,
            'status'              => 'processed',
        ]);
    }

    /**
     * Tandai record sebagai gagal diproses.
     */
    public function markAsFailed(): bool
    {
        return $this->update(['status' => 'failed']);
    }

    /**
     * Kembalikan input fitur sebagai array untuk dikirim ke ML service.
     * Sesuai dengan schema PredictRequest di FastAPI.
     *
     * @return array<string, mixed>
     */
    public function toMlInput(): array
    {
        return [
            'age'            => $this->age,
            'gender'         => $this->gender,
            'bmi'            => $this->bmi,
            'glucose'        => $this->glucose,
            'blood_pressure' => $this->blood_pressure,
            'cholesterol'    => $this->cholesterol ?? 200.0,
            'heart_rate'     => $this->heart_rate  ?? 75.0,
        ];
    }
}
