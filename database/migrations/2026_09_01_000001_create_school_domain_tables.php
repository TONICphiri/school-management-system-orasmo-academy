<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('one_time_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 20);
            $table->string('kind', 20);
            $table->string('channel', 10);
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employment_number', 30)->nullable();
            $table->string('qualification', 20);
            $table->string('specialisation')->nullable();
            $table->date('first_appointment')->nullable();
            $table->timestamps();
        });

        Schema::create('committees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('committee_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('position', 30)->default('Member');
            $table->unique(['committee_id', 'user_id']);
        });

        Schema::create('governance_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('body', 10);
            $table->string('position', 60)->default('Member');
            $table->boolean('is_voting')->default(true);
            $table->date('term_ends')->nullable();
            $table->timestamps();
        });

        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 20);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('number');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('term_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->timestamps();
        });

        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('phase', 20);
            $table->string('name', 40);
            $table->unsignedTinyInteger('ordinal');
            $table->string('national_exam', 10)->nullable();
            $table->string('instruction_language', 20)->default('English');
            $table->timestamps();
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 40);
            $table->unsignedTinyInteger('from_ordinal');
            $table->unsignedTinyInteger('to_ordinal');
            $table->foreignId('head_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->foreignId('head_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phase', 20);
            $table->string('name', 60);
            $table->string('code', 12);
            $table->boolean('is_core')->default(true);
            $table->boolean('is_optional')->default(false);
            $table->unsignedTinyInteger('ca_weight')->default(40);
            $table->unsignedTinyInteger('exam_weight')->default(60);
            $table->timestamps();
        });

        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('level_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stream', 30);
            $table->foreignId('class_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('capacity')->default(60);
            $table->string('room', 30)->nullable();
            $table->timestamps();
            $table->unique(['academic_year_id', 'class_teacher_id']);
        });

        Schema::create('class_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('periods_per_week')->default(5);
            $table->timestamps();
            $table->unique(['school_class_id', 'subject_id']);
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('school_class_id')->nullable()->constrained()->nullOnDelete();
            $table->string('admission_number', 30);
            $table->string('first_name', 60);
            $table->string('last_name', 60);
            $table->string('gender', 10);
            $table->date('date_of_birth')->nullable();
            $table->string('home_village')->nullable();
            $table->string('traditional_authority')->nullable();
            $table->string('home_district')->nullable();
            $table->string('maneb_exam_number', 30)->nullable();
            $table->string('status', 20)->default('ENROLLED');
            $table->date('admitted_on')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'admission_number']);
        });

        Schema::create('guardian_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('relationship', 30)->default('Parent');
            $table->unique(['user_id', 'student_id']);
        });

        Schema::create('student_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_subject_id')->constrained()->cascadeOnDelete();
            $table->unique(['student_id', 'class_subject_id']);
        });

        Schema::create('timetable_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_subject_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day');
            $table->unsignedTinyInteger('period');
            $table->timestamps();
        });

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 10);
            $table->string('title', 80);
            $table->string('language', 20)->default('English');
            $table->unsignedSmallInteger('max_score')->default(100);
            $table->date('held_on')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 6, 2)->nullable();
            $table->boolean('absent')->default(false);
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('entered_at')->nullable();
            $table->timestamps();
            $table->unique(['assessment_id', 'student_id']);
        });

        Schema::create('grade_bands', function (Blueprint $table) {
            $table->id();
            $table->string('phase', 20);
            $table->unsignedTinyInteger('min_score');
            $table->unsignedTinyInteger('max_score');
            $table->string('grade', 5);
            $table->string('label', 40);
            $table->unsignedTinyInteger('sort_order');
        });

        Schema::create('subject_result_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('DRAFT');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->string('remark')->nullable();
            $table->timestamps();
            $table->unique(['class_subject_id', 'term_id']);
        });

        Schema::create('class_result_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 30)->default('OPEN');
            $table->foreignId('class_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('class_reviewed_at')->nullable();
            $table->foreignId('deputy_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deputy_approved_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->string('remark')->nullable();
            $table->timestamps();
            $table->unique(['school_class_id', 'term_id']);
        });

        Schema::create('report_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->text('class_teacher_comment')->nullable();
            $table->text('head_comment')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'term_id']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('attended_on');
            $table->string('status', 10);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'attended_on']);
        });

        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('category', 30);
            $table->string('title');
            $table->text('body');
            $table->string('link')->nullable();
            $table->string('priority', 10)->default('NORMAL');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'read_at']);
        });

        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 10);
            $table->string('recipient');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->string('status', 20)->default('QUEUED');
            $table->string('provider_reference')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 60);
            $table->string('target_type', 60)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->text('description');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['school_id', 'created_at']);
        });

        Schema::create('feedback_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->string('status', 20)->default('OPEN');
            $table->text('response')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('escalated_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inspection_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supervisor_id')->constrained('users')->cascadeOnDelete();
            $table->date('visit_date');
            $table->string('directorate', 80);
            $table->string('overall_rating', 20);
            $table->text('findings');
            $table->text('recommendations');
            $table->boolean('flag_follow_up')->default(false);
            $table->date('follow_up_by')->nullable();
            $table->string('status', 20)->default('SUBMITTED');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['inspection_reports', 'feedback_items', 'audit_logs', 'message_logs', 'notices', 'attendances', 'report_comments', 'class_result_statuses', 'subject_result_statuses', 'grade_bands', 'marks', 'assessments', 'timetable_slots', 'student_subjects', 'guardian_student', 'students', 'class_subjects', 'school_classes', 'subjects', 'departments', 'sections', 'levels', 'term_breaks', 'terms', 'academic_years', 'governance_memberships', 'committee_user', 'committees', 'teacher_profiles', 'one_time_codes'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
