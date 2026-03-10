<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Drop old free-text position column
            $table->dropColumn('position');

            // Link to positions table
            $table->foreignId('position_id')->nullable()->after('middle_name')
                  ->constrained('positions')->onDelete('set null');

            // Personal details
            $table->date('birthdate')->nullable()->after('address');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('birthdate');
            $table->enum('civil_status', ['single', 'married', 'widowed', 'divorced', 'separated'])
                  ->nullable()->after('gender');

            // Compensation
            $table->decimal('basic_salary', 12, 2)->nullable()->after('civil_status');

            // Government IDs
            $table->string('sss_no', 50)->nullable()->after('basic_salary');
            $table->string('philhealth_no', 50)->nullable()->after('sss_no');
            $table->string('pagibig_no', 50)->nullable()->after('philhealth_no');
            $table->string('tin_no', 50)->nullable()->after('pagibig_no');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['position_id']);
            $table->dropColumn([
                'position_id', 'birthdate', 'gender', 'civil_status',
                'basic_salary', 'sss_no', 'philhealth_no', 'pagibig_no', 'tin_no',
            ]);
            $table->string('position')->nullable()->after('middle_name');
        });
    }
};
