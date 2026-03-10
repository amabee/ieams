<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $types = "'sick','vacation','emergency','maternity','paternity','unpaid','other'";

        DB::statement("ALTER TABLE leaves MODIFY leave_type ENUM({$types}) NOT NULL");
        DB::statement("ALTER TABLE leave_balances MODIFY leave_type ENUM({$types}) NOT NULL");
    }

    public function down(): void
    {
        $types = "'sick','vacation','emergency','other'";

        DB::statement("ALTER TABLE leaves MODIFY leave_type ENUM({$types}) NOT NULL");
        DB::statement("ALTER TABLE leave_balances MODIFY leave_type ENUM({$types}) NOT NULL");
    }
};
