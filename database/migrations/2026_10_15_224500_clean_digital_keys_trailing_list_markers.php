<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Retired: punctuation and trailing digits can be part of valid credentials.
        // Existing records must only be corrected after an explicit human review.
    }

    public function down(): void
    {
        // Safe no-op on rollback
    }
};
