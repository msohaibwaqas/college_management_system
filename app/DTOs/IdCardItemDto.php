<?php

namespace App\DTOs;

class IdCardItemDto
{
    public function __construct(
        public readonly int $studentId,
        public readonly string $fullName,
        public readonly ?string $fatherName,
        public readonly string $rollNo,
        public readonly string $courseTitle,
        public readonly string $photoUrl,
        public readonly int $admissionYear,
        public readonly string $expiryText,
        public readonly string $barcodeValue,
        public readonly string $type = 'HSSC'
    ) {}
}
