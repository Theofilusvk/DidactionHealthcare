<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Analisis Kesehatan - Didaction Healthcare</title>
    <style>
        @page {
            margin: 40px 45px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #2d3748;
            line-height: 1.4;
            font-size: 11px;
            margin: 0;
            padding: 0;
        }
        
        /* Typography */
        h1, h2, h3, h4, h5 {
            color: #003638;
            margin-top: 0;
            font-weight: bold;
        }
        h2 {
            font-size: 15px;
            border-bottom: 2px solid #005B60;
            padding-bottom: 4px;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        h3 {
            font-size: 12px;
            margin-bottom: 8px;
        }
        
        /* Layout Tables */
        .w-full {
            width: 100%;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        table.layout {
            border: none;
            margin-bottom: 15px;
        }
        table.layout td {
            padding: 0;
            vertical-align: top;
            border: none;
        }
        
        /* Header styling */
        .header-logo-text {
            font-size: 20px;
            font-weight: bold;
            color: #005B60;
        }
        .header-logo-icon {
            display: inline-block;
            background-color: #005B60;
            color: #ffffff;
            width: 24px;
            height: 24px;
            text-align: center;
            line-height: 24px;
            font-weight: bold;
            font-size: 16px;
            border-radius: 4px;
            margin-right: 8px;
            vertical-align: middle;
        }
        .header-title-container {
            vertical-align: middle;
        }
        
        .header-date {
            text-align: right;
            color: #718096;
            font-size: 10px;
            padding-top: 6px;
        }
        .divider {
            height: 3px;
            background-color: #005B60;
            margin: 10px 0 20px 0;
        }

        /* Metric Grid Table */
        .metric-table {
            background-color: #FAFDFD;
            border: 1px solid #D1EDE8;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .metric-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #EBF5F3;
            font-size: 11px;
        }
        .metric-table tr:last-child td {
            border-bottom: none;
        }
        .metric-label {
            color: #718096;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            width: 30%;
        }
        .metric-value {
            color: #2d3748;
            font-weight: 600;
            width: 20%;
        }

        /* Status & Score Card */
        .status-card {
            background-color: #ECF9F6;
            border: 1.5px solid #00A19D;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .score-circle {
            background-color: #ffffff;
            border: 4px solid #005B60;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            text-align: center;
            margin-right: 15px;
        }
        .score-num {
            font-size: 26px;
            font-weight: 800;
            color: #005B60;
            line-height: 70px;
            margin: 0;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
        .badge-aman {
            background-color: #EBFDF5;
            color: #1E6B47;
            border: 1px solid #A7F3D0;
        }
        .badge-peringatan {
            background-color: #FFFBEB;
            color: #B25E00;
            border: 1px solid #FDE68A;
        }
        .badge-bahaya {
            background-color: #FDF2F2;
            color: #C22F2F;
            border: 1px solid #FCA5A5;
        }

        /* Disease Risk Table */
        .risk-table {
            margin-bottom: 20px;
        }
        .risk-table th {
            background-color: #005B60;
            color: #ffffff;
            text-align: left;
            padding: 8px 12px;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .risk-table td {
            padding: 9px 12px;
            border-bottom: 1px solid #E2E8F0;
            font-size: 11px;
        }
        .risk-level-cell {
            font-weight: bold;
            text-align: center;
            border-radius: 4px;
            font-size: 9px;
            padding: 2px 6px;
        }
        .level-low {
            background-color: #EBFDF5;
            color: #1E6B47;
        }
        .level-moderate {
            background-color: #FFFBEB;
            color: #B25E00;
        }
        .level-high {
            background-color: #FDF2F2;
            color: #C22F2F;
        }

        /* Recommendation Section */
        .recommendation-box {
            border: 1px solid #E2E8F0;
            background-color: #ffffff;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 15px;
        }
        
        /* Meal Plan Styling */
        .menu-table td {
            padding: 6px 10px;
            border-bottom: 1px dashed #E2E8F0;
            vertical-align: middle;
        }
        .menu-table tr:last-child td {
            border-bottom: none;
        }
        .menu-time {
            font-weight: bold;
            color: #005B60;
            text-transform: uppercase;
            font-size: 9px;
            width: 20%;
        }
        .menu-desc {
            color: #4a5568;
            font-weight: 500;
        }

        /* Physical Activity Grid */
        .activity-grid-table td {
            padding: 8px;
            border: 1px solid #E2E8F0;
            background-color: #FAFDFD;
            width: 25%;
            text-align: center;
        }
        .activity-grid-label {
            font-weight: bold;
            color: #005B60;
            font-size: 8px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .activity-grid-val {
            font-size: 11px;
            font-weight: bold;
            color: #2d3748;
        }

        /* Warning Box */
        .warning-box {
            background-color: #FFFDF5;
            border-left: 4px solid #D97706;
            padding: 10px;
            border-radius: 0 6px 6px 0;
            margin-top: 10px;
        }
        .warning-title {
            font-weight: bold;
            color: #92400E;
            margin-bottom: 4px;
            font-size: 10px;
        }
        .warning-content {
            color: #78350F;
            font-size: 10px;
        }

        /* Lists */
        ul {
            margin: 0;
            padding-left: 15px;
        }
        li {
            margin-bottom: 4px;
            color: #4a5568;
        }

        /* Footer Disclaimer */
        .footer {
            margin-top: 30px;
            border-top: 1px solid #E2E8F0;
            padding-top: 12px;
            text-align: center;
            color: #718096;
            font-size: 9px;
            font-style: italic;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        /* Prevent orphans */
        tr {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>

    <!-- HEADER SECTION -->
    <table class="layout w-full">
        <tr>
            <td class="header-title-container">
                <span class="header-logo-icon">+</span>
                <span class="header-logo-text">Didaction Healthcare</span>
            </td>
            <td class="header-date">
                <strong>Tanggal Cetak:</strong> {{ $date }}<br>
                <strong>ID Laporan:</strong> DHC-{{ strtoupper(substr(md5(uniqid()), 0, 8)) }}
            </td>
        </tr>
    </table>
    
    <div class="divider"></div>

    <!-- REPORT HEALTH SCORE CARD -->
    <div class="status-card">
        <table class="layout w-full" style="margin-bottom: 0;">
            <tr>
                <td style="width: 85px;">
                    <div class="score-circle">
                        <div class="score-num">{{ $data['health_score'] }}</div>
                    </div>
                </td>
                <td>
                    <div style="margin-bottom: 6px;">
                        <span style="font-size: 13px; font-weight: bold; color: #003638; vertical-align: middle; margin-right: 8px;">Status Kesehatan Utama:</span>
                        @if($data['risk_status'] === 'Aman')
                            <span class="status-badge badge-aman">Aman</span>
                        @elseif($data['risk_status'] === 'Peringatan')
                            <span class="status-badge badge-peringatan">Peringatan</span>
                        @else
                            <span class="status-badge badge-bahaya">Bahaya</span>
                        @endif
                    </div>
                    <div style="font-size: 11px; font-weight: bold; color: #005B60; margin-bottom: 4px;">
                        Risiko Tertinggi: {{ $data['highest_risk'] ?: 'Tidak Terdeteksi' }}
                    </div>
                    <p style="margin: 0; color: #4a5568; font-size: 10px; line-height: 1.3;">
                        Berdasarkan pemrosesan data klinis menggunakan algoritma XGBoost/Jaringan Saraf Didaction Healthcare dan analisis gizi AI. Laporan ini menunjukkan gambaran metrik kesehatan fungsional Anda.
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <!-- USER HEALTH DATA SUMMARY -->
    <h2>Ringkasan Data Kesehatan User</h2>
    <table class="metric-table w-full">
        <tr>
            <td class="metric-label">Usia</td>
            <td class="metric-value">{{ $data['input_data']['age'] }} Tahun</td>
            <td class="metric-label">Tekanan Darah Sistolik</td>
            <td class="metric-value">{{ $data['input_data']['blood_pressure'] }} mmHg</td>
        </tr>
        <tr>
            <td class="metric-label">Jenis Kelamin</td>
            <td class="metric-value">{{ ($data['input_data']['gender'] ?? 1) == 1 ? 'Laki-laki' : 'Perempuan' }}</td>
            <td class="metric-label">Kadar Glukosa Darah</td>
            <td class="metric-value">{{ $data['input_data']['glucose'] }} mg/dL</td>
        </tr>
        <tr>
            <td class="metric-label">Indeks Massa Tubuh (BMI)</td>
            <td class="metric-value">
                {{ $data['input_data']['bmi'] }}
                <span style="font-size: 8px; font-weight: normal; color: #718096;">
                    ({{ $data['input_data']['bmi'] >= 30 ? 'Obesitas' : ($data['input_data']['bmi'] >= 25 ? 'Overweight' : ($data['input_data']['bmi'] >= 18.5 ? 'Normal' : 'Underweight')) }})
                </span>
            </td>
            <td class="metric-label">Status Merokok</td>
            <td class="metric-value">
                @if(($data['input_data']['status_merokok'] ?? '') === 'never') Tidak Pernah
                @elseif(($data['input_data']['status_merokok'] ?? '') === 'former') Mantan Perokok
                @elseif(($data['input_data']['status_merokok'] ?? '') === 'active') Aktif
                @else {{ $data['input_data']['status_merokok'] ?? '-' }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="metric-label">Tingkat Aktivitas</td>
            <td class="metric-value" style="text-transform: capitalize;">{{ $data['input_data']['aktivitas_fisik'] ?? 'Sedentary' }}</td>
            <td class="metric-label">Kadar HbA1c</td>
            <td class="metric-value">{{ isset($data['input_data']['hba1c']) ? $data['input_data']['hba1c'] . '%' : 'Tidak Ada Data' }}</td>
        </tr>
        <tr>
            <td class="metric-label">Kadar Protein Urine</td>
            <td class="metric-value">{{ isset($data['input_data']['protein_urine']) ? $data['input_data']['protein_urine'] . ' mg/hari' : 'Tidak Ada Data' }}</td>
            <td class="metric-label">Total Kolesterol</td>
            <td class="metric-value">{{ $data['input_data']['cholesterol'] ?? '200' }} mg/dL</td>
        </tr>
    </table>

    <!-- DISEASE PREDICTIONS TABLE -->
    <h2>Tabel Prediksi Risiko Penyakit</h2>
    <table class="risk-table w-full">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 45%;">Nama Penyakit / Risiko Fisiologis</th>
                <th style="width: 25%; text-align: right; padding-right: 20px;">Probabilitas (%)</th>
                <th style="width: 25%; text-align: center;">Tingkat Risiko</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @foreach($data['predictions'] as $pred)
                <tr>
                    <td>{{ $no++ }}</td>
                    <td style="font-weight: bold; color: #2d3748;">{{ $pred['label'] }}</td>
                    <td style="text-align: right; padding-right: 20px; font-weight: bold; font-family: monospace;">{{ $pred['percentage'] }}</td>
                    <td style="text-align: center;">
                        @if($pred['risk_level'] === 'Low')
                            <span class="risk-level-cell level-low">LOW</span>
                        @elseif($pred['risk_level'] === 'Moderate')
                            <span class="risk-level-cell level-moderate">MODERATE</span>
                        @else
                            <span class="risk-level-cell level-high">HIGH</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- PAGE BREAK FOR RECOMMENDATIONS (Clean separation) -->
    <div class="page-break"></div>

    <!-- DIETARY RECOMMENDATIONS -->
    <h2>Rekomendasi Pola Makan Harian (Meal Plan)</h2>
    
    <div class="recommendation-box">
        <h3 style="color: #005B60; border-bottom: 1px solid #E2E8F0; padding-bottom: 4px; margin-bottom: 8px;">Prinsip Utama Nutrisi</h3>
        <ul>
            @if(isset($data['meal_plan']['prinsip_utama']) && is_array($data['meal_plan']['prinsip_utama']))
                @foreach($data['meal_plan']['prinsip_utama'] as $prinsip)
                    <li>{{ $prinsip }}</li>
                @endforeach
            @else
                <li>Konsumsi makanan sehat seimbang, batasi makanan olahan, lemak jenuh, dan garam berlebih.</li>
            @endif
        </ul>
    </div>

    <div class="recommendation-box" style="padding: 0;">
        <table class="menu-table w-full">
            <thead>
                <tr style="background-color: #F0F9F8;">
                    <th style="width: 20%; padding: 8px 10px; font-size: 9px; text-transform: uppercase; color: #005B60; text-align: left;">Waktu Makan</th>
                    <th style="padding: 8px 10px; font-size: 9px; text-transform: uppercase; color: #005B60; text-align: left;">Saran Menu Harian Konkret</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="menu-time">Sarapan</td>
                    <td class="menu-desc">{{ $data['meal_plan']['menu_harian']['sarapan'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="menu-time">Snack Pagi</td>
                    <td class="menu-desc">{{ $data['meal_plan']['menu_harian']['snack_pagi'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="menu-time">Makan Siang</td>
                    <td class="menu-desc">{{ $data['meal_plan']['menu_harian']['makan_siang'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="menu-time">Snack Sore</td>
                    <td class="menu-desc">{{ $data['meal_plan']['menu_harian']['snack_sore'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="menu-time">Makan Malam</td>
                    <td class="menu-desc">{{ $data['meal_plan']['menu_harian']['makan_malam'] ?? '-' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- PHYSICAL ACTIVITY RECOMMENDATIONS -->
    <h2>Rekomendasi Aktivitas Fisik (Activity Plan)</h2>
    
    <div class="recommendation-box" style="padding: 0; margin-bottom: 10px;">
        <table class="activity-grid-table w-full" style="border-collapse: collapse; border: none;">
            <tr>
                <td>
                    <div class="activity-grid-label">Jenis Olahraga</div>
                    <div class="activity-grid-val">
                        @if(isset($data['activity_plan']['jenis_olahraga']) && is_array($data['activity_plan']['jenis_olahraga']))
                            {{ implode(', ', $data['activity_plan']['jenis_olahraga']) }}
                        @else
                            Jalan Santai
                        @endif
                    </div>
                </td>
                <td>
                    <div class="activity-grid-label">Frekuensi</div>
                    <div class="activity-grid-val">{{ $data['activity_plan']['frekuensi_per_minggu'] ?? '3' }}x / Minggu</div>
                </td>
                <td>
                    <div class="activity-grid-label">Durasi</div>
                    <div class="activity-grid-val">{{ $data['activity_plan']['durasi_per_sesi_menit'] ?? '30' }} Menit / Sesi</div>
                </td>
                <td>
                    <div class="activity-grid-label">Intensitas</div>
                    <div class="activity-grid-val" style="text-transform: uppercase;">{{ $data['activity_plan']['intensitas'] ?? 'sedang' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="recommendation-box warning-box">
        <div class="warning-title">⚠️ Catatan Keamanan Olahraga & Fisik:</div>
        <div class="warning-content">
            <ul>
                @if(isset($data['activity_plan']['catatan_keamanan']) && is_array($data['activity_plan']['catatan_keamanan']))
                    @foreach($data['activity_plan']['catatan_keamanan'] as $catatan)
                        <li style="color: #92400E; margin-bottom: 3px;">{{ $catatan }}</li>
                    @endforeach
                @else
                    <li style="color: #92400E;">Selalu lakukan pemanasan sebelum berolahraga, minum cukup air, dan segera beristirahat jika terasa pusing atau sesak napas.</li>
                @endif
            </ul>
        </div>
    </div>

    <!-- DISCLAIMER & MEDICAL WARNING FOOTER -->
    <div class="footer">
        <p><strong>Disclaimer Penting:</strong> Laporan analisis dan rekomendasi gizi/olahraga ini dihasilkan secara otomatis menggunakan kecerdasan buatan dan aturan klinis umum. Dokumen ini bertujuan hanya sebagai panduan edukatif dan skrining kesehatan fungsional awal, <strong>bukan merupakan diagnosis medis resmi, saran klinis, atau rencana pengobatan dari dokter</strong>. Hasil ini tidak menggantikan konsultasi langsung dengan dokter atau tenaga kesehatan profesional lainnya. Sangat disarankan untuk mendiskusikan laporan ini dengan dokter spesialis atau dokter keluarga Anda sebelum memulai program latihan atau diet baru.</p>
        <p>© {{ date('Y') }} Didaction Healthcare. Hak Cipta Dilindungi Undang-Undang.</p>
    </div>

</body>
</html>
