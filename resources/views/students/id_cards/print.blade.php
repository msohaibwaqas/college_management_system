<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $settings = \App\Models\InstituteSetting::getSettings();
        $colors = \App\Models\InstituteSetting::getColors();
        $primaryColor = $colors['primary'] ?? '#006159';
        $secondaryColor = $colors['secondary'] ?? '#2e9f82';
        $highlightColor = $colors['highlight'] ?? '#d6ec8c';
    @endphp
    <title>Student ID Cards - {{ $settings->short_name ?? 'College' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800;900&family=Cinzel:wght@700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: {{ $primaryColor }};
            --secondary-color: {{ $secondaryColor }};
            --highlight-color: {{ $highlightColor }};
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', 'Helvetica Neue', Arial, sans-serif;
            background: #e2e8f0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            font-weight: 700;
        }

        /* Toolbar */
        .toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #0f172a;
            color: white;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 1000;
            box-shadow: 0 4px 14px rgba(0,0,0,0.3);
        }

        .toolbar-title {
            font-size: 1.1rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-print {
            background: var(--primary-color);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 22px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 800;
            font-size: 0.95rem;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
            transition: all 0.2s ease;
        }
        .btn-print:hover {
            transform: translateY(-1px);
            filter: brightness(1.15);
        }

        .page {
            margin-top: 70px;
            padding: 25px;
            display: grid;
            grid-template-columns: repeat(2, 85.6mm);
            gap: 12mm 10mm;
            justify-content: center;
        }

        /* ── CR80 ID Card Outer Frame ────────────────────────── */
        .id-card {
            width: 85.6mm;
            height: 53.98mm;
            background: #ffffff;
            border-radius: 3.5mm;
            overflow: hidden;
            position: relative;
            box-shadow: 0 8px 24px rgba(0,0,0,0.18), 0 0 0 1px rgba(0,0,0,0.06);
            display: flex;
            flex-direction: column;
            border: 1px solid #cbd5e1;
            font-weight: 700 !important;
        }

        .id-card *, .id-card div, .id-card span, .id-card label, .id-card p {
            font-weight: 800 !important;
        }

        /* Top Header */
        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, color-mix(in srgb, var(--primary-color) 70%, black) 100%);
            height: 14.5mm;
            display: flex;
            align-items: center;
            padding: 0 3.5mm;
            color: white;
            position: relative;
            border-bottom: 2px solid var(--primary-color);
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--secondary-color) 50%, var(--primary-color) 100%);
        }

        .logo {
            width: 10.5mm;
            height: 10.5mm;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 2.8mm;
            padding: 0.5mm;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            border: 1.5px solid var(--secondary-color);
            flex-shrink: 0;
        }

        .logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .header-titles {
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
        }

        .institute-name {
            font-family: 'Cinzel', serif;
            font-size: 8.6pt;
            font-weight: 900 !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.15;
            color: #ffffff;
            text-shadow: 0 1px 2px rgba(0,0,0,0.5);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .institute-sub {
            font-size: 5.5pt;
            font-weight: 800 !important;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #ffffff;
            opacity: 0.9;
            margin-top: 1px;
        }

        /* Card Main Content */
        .main-content {
            flex: 1;
            display: flex;
            padding: 2.5mm 3.5mm 1.5mm 3.5mm;
            position: relative;
            background: #ffffff;
        }

        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 36%;
            transform: translate(-50%, -50%);
            width: 36mm;
            height: 36mm;
            opacity: 0.07;
            pointer-events: none;
            z-index: 0;
        }

        .details {
            flex: 1;
            padding-right: 20mm;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            z-index: 1;
        }

        .student-name {
            font-size: 9pt;
            font-weight: 900 !important;
            color: var(--primary-color);
            margin-bottom: 2px;
            font-family: 'Cinzel', serif;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            line-height: 1.15;
            word-break: break-word;
            overflow-wrap: break-word;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            max-height: 2.3em;
        }

        .role-badge {
            display: inline-block;
            background: var(--primary-color);
            color: #ffffff;
            font-size: 5.8pt;
            font-weight: 900 !important;
            padding: 1.5px 6px;
            border-radius: 2px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 2.5px;
            width: fit-content;
            border: 1px solid var(--secondary-color);
            box-shadow: 0 1px 3px rgba(0,0,0,0.15);
        }

        .info-grid {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 1.2px 6px;
            font-size: 7.2pt;
            line-height: 1.25;
        }

        .label {
            color: var(--primary-color);
            font-weight: 900 !important;
            text-transform: uppercase;
            font-size: 6.8pt;
            letter-spacing: 0.2px;
        }

        .value {
            color: #0f172a;
            font-weight: 800 !important;
            font-size: 7.2pt;
        }

        /* Photo Right Frame */
        .photo-container {
            width: 17.5mm;
            height: 22mm;
            position: absolute;
            right: 3.5mm;
            top: 2.5mm;
            border-radius: 1.5mm;
            border: 2px solid var(--primary-color);
            background: #f1f5f9;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            z-index: 2;
        }

        .photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Footer Bar */
        .footer-bar {
            height: 12.5mm;
            background: #ffffff;
            border-top: 1.5px solid var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 3.5mm;
            position: relative;
            z-index: 2;
        }

        .barcode-container {
            height: 10.5mm;
            display: flex;
            align-items: center;
            flex: 1;
            padding: 0.5mm 0;
            background: #ffffff;
        }

        .barcode-container img, .barcode-container svg {
            height: 100%;
            max-width: 52mm;
            background: #ffffff;
        }

        .id-number {
            font-family: 'Courier New', monospace;
            font-size: 8.5pt;
            font-weight: 900 !important;
            color: var(--primary-color);
            margin-left: 2.5mm;
            letter-spacing: 0.5px;
        }

        .expiration {
            font-size: 5.8pt;
            font-weight: 800 !important;
            color: #ffffff;
            text-align: right;
            line-height: 1.25;
            background: var(--primary-color);
            padding: 2px 6px;
            border-radius: 3px;
            border: 1px solid var(--primary-color);
            flex-shrink: 0;
        }

        .expiration strong {
            font-weight: 900 !important;
            color: #ffffff;
            display: block;
            font-size: 6.5pt;
        }

        /* ── Print Media Optimization ────────────────────────── */
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #ffffff !important;
            }
            .page {
                margin: 0 !important;
                padding: 0 !important;
                display: block !important;
            }
            .id-card {
                margin: 0 4mm 4mm 0 !important;
                float: left !important;
                page-break-inside: avoid !important;
                box-shadow: none !important;
                border: 1px solid #94a3b8 !important;
            }
        }
    </style>
</head>
<body>
@php
    $generator = null;
    try {
        if (class_exists(\Picqer\Barcode\BarcodeGeneratorSVG::class)) {
            $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
        }
    } catch (\Exception $e) {
        // Ignore barcode error
    }
@endphp

    <div class="toolbar no-print">
        <div class="toolbar-title">
            <i class="fas fa-id-card" style="color: var(--secondary-color);"></i> Student Identification Cards
        </div>
        <button class="btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Print ID Cards
        </button>
    </div>

    <div class="page">
        @if($students->isEmpty())
            <div style="grid-column: 1 / -1; text-align: center; padding: 50px; background: white; border-radius: 8px;">
                <h2 style="color: #c00;">No students found</h2>
                <p>Criteria: {{ request()->fullUrl() }}</p>
                <p>Please check if the student exists and has status 'active' or 'admitted'.</p>
            </div>
        @endif

        @foreach($students as $admission)
        @php
            $isDto = $admission instanceof \App\DTOs\IdCardItemDto;
            $fullName = $isDto ? $admission->fullName : trim(($admission->student->first_name ?? 'Unknown') . ' ' . ($admission->student->last_name ?? ''));
            $rollNo = $isDto ? $admission->rollNo : ($admission->college_roll_no ?? $admission->roll_no ?? 'N/A');
            $fatherName = $isDto ? $admission->fatherName : ($admission->student?->father?->name ?? null);
            $courseTitle = $isDto ? $admission->courseTitle : (
                ($admission->student && $admission->student->electives && $admission->student->electives->isNotEmpty())
                    ? $admission->student->electives->sortBy('sequence')->map(fn($e) => $e->subject?->name)->filter()->implode(', ')
                    : (isset($admission->program) ? ($admission->program->name . ' (Sem ' . $admission->current_semester . ')') : (($admission->current_class ?? '') == '11' ? 'HSSC Part-I' : (($admission->current_class ?? '') == '12' ? 'HSSC Part-II' : ($admission->current_class ?? 'N/A'))))
            );
            $photoUrl = $isDto ? $admission->photoUrl : ($admission->student ? $admission->student->getProfilePictureUrl() : null);
            $studentId = $isDto ? $admission->studentId : $admission->student_id;
            $barcodeVal = $isDto ? $admission->barcodeValue : str_pad((string)$studentId, 6, '0', STR_PAD_LEFT);
            $expiryText = $isDto ? $admission->expiryText : ('AUG ' . (($admission->admission_year ?? date('Y')) + (isset($admission->program) ? ($admission->program->duration_years ?? 4) : 2)));
        @endphp
        <div class="id-card">
            <!-- Header -->
            <div class="header">
                <div class="logo">
                    @php
                        $logoUrl = null;
                        if (!empty($settings->logo_path)) {
                            if (strpos($settings->logo_path, 'uploads/') === 0) {
                                $logoUrl = asset($settings->logo_path);
                            } else {
                                $logoUrl = asset('storage/' . $settings->logo_path);
                            }
                        }
                    @endphp
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo">
                    @else
                        <i class="fas fa-university" style="font-size: 18px; color: var(--primary-color);"></i>
                    @endif
                </div>
                <div class="header-titles">
                    <div class="institute-name">
                        {{ $settings->short_name ?? $settings->institute_title ?? 'Govt. Graduate College' }}
                    </div>
                    <div class="institute-sub">
                        {{ strtoupper($settings->institute_location ?? 'Chishtian') }}
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <!-- Watermark -->
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" class="watermark" alt="Watermark">
                @endif

                <div class="details">
                    <div class="student-name">
                        {{ $fullName }}
                    </div>
                    <div class="role-badge">Student</div>

                    <div class="info-grid">
                        <div class="label">Roll No:</div>
                        <div class="value">{{ $rollNo }}</div>

                        @if($fatherName)
                            <div class="label">Father:</div>
                            <div class="value text-truncate" style="max-width: 130px;">{{ $fatherName }}</div>
                        @endif

                        <div class="label">Course:</div>
                        <div class="value" style="font-size: 6.8pt; line-height: 1.2;">
                            {{ $courseTitle }}
                        </div>
                    </div>
                </div>

                <div class="photo-container">
                    @if($photoUrl)
                        <img src="{{ $photoUrl }}" alt="Photo" onerror="this.src='https://via.placeholder.com/150'">
                    @else
                        <div style="width:100%;height:100%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-user text-muted" style="font-size:24px;"></i>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Footer with Barcode -->
            <div class="footer-bar">
                <div class="barcode-container">
                    @if($generator)
                        @php
                            try {
                                echo $generator->getBarcode($barcodeVal, $generator::TYPE_CODE_128, 3.0, 42);
                            } catch (\Exception $e) {
                                echo '<span style="font-size:8px;color:red;font-weight:bold;">Barcode Error</span>';
                            }
                        @endphp
                    @else
                        <span style="font-size:9px;color:#666;font-weight:bold;">[Barcode]</span>
                    @endif
                    <span class="id-number">{{ $studentId }}</span>
                </div>
                <div class="expiration">
                    EXPIRES<br>
                    <strong>{{ $expiryText }}</strong>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="d-print-none text-center pb-4 pt-2">
        @cmsCredits
    </div>
</body>
</html>
