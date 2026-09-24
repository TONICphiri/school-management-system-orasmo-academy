<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('learner_uid', 16)->nullable()->index()->after('id');
            // One learner keeps the same ID in every school record, so it is unique per school only
            $table->unique(['school_id', 'learner_uid']);
            $table->string('physical_address', 200)->nullable()->after('home_district');
            $table->string('emergency_contact_name', 120)->nullable()->after('physical_address');
            $table->string('emergency_contact_phone', 30)->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_relationship', 40)->nullable()->after('emergency_contact_phone');
            $table->foreignId('registered_by')->nullable()->after('admitted_on')->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('transferred_from_id')->nullable()->after('registered_by');
        });

        Schema::create('learner_school_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('school_name', 150);
            $table->string('school_code', 20)->nullable();
            $table->string('last_class', 40)->nullable();
            $table->unsignedSmallInteger('year_left')->nullable();
            $table->string('reason', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('finance_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 12);
            $table->string('category', 60);
            $table->string('description', 200)->nullable();
            $table->decimal('amount', 14, 2);
            $table->date('entry_date');
            $table->string('reference', 40)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['school_id', 'term_id', 'type']);
        });

        foreach (DB::table('students')->whereNull('learner_uid')->orderBy('id')->pluck('id') as $id) {
            DB::table('students')->where('id', $id)->update(['learner_uid' => \App\Models\Student::makeLearnerUid($id)]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_entries');
        Schema::dropIfExists('learner_school_histories');
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'learner_uid']);
            $table->dropConstrainedForeignId('registered_by');
            $table->dropColumn(['learner_uid', 'physical_address', 'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship', 'transferred_from_id']);
        });
    }
};
