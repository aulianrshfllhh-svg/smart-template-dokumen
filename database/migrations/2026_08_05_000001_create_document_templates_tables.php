<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Arsitektur Multi-Dokumen Smart Template:
     * Support Renja, RKPD, dan Evaluasi RKPD berbasis Konfigurasi Dinamis.
     */
    public function up(): void
    {
        // 1. Tabel Master Template Dokumen (document_templates)
        if (Schema::hasTable('document_templates') && !Schema::hasColumn('document_templates', 'code')) {
            Schema::dropIfExists('template_sections');
            Schema::dropIfExists('document_templates');
        }

        if (!Schema::hasTable('document_templates')) {
            Schema::create('document_templates', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique(); // RENJA, RKPD, EVALUASI_RKPD
                $table->string('name');
                $table->text('description')->nullable();
                $table->json('format_config')->nullable();
                $table->json('toolbar_config')->nullable();
                $table->json('validation_config')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Tabel Seksi/Bagian Template (template_sections)
        if (!Schema::hasTable('template_sections')) {
            Schema::create('template_sections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('template_id')->constrained('document_templates')->cascadeOnDelete();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->enum('section_type', [
                    'cover',
                    'preface',
                    'table_of_contents',
                    'list_of_tables',
                    'list_of_figures',
                    'list_of_charts',
                    'list_of_appendices',
                    'chapter',
                    'subchapter',
                    'appendix'
                ])->default('subchapter');
                $table->string('code'); // ex: COVER, PREFACE, BAB I, 1.1, LAMPIRAN 1
                $table->string('title');
                $table->unsignedSmallInteger('sequence')->default(0);
                $table->boolean('is_required')->default(true);
                $table->boolean('is_editable')->default(true);
                $table->boolean('is_automatic')->default(false);
                $table->boolean('page_break_before')->default(true);
                $table->boolean('page_break_after')->default(false);
                $table->json('format_config')->nullable();
                $table->timestamps();
            });
        }

        // 3. Tambah FK & Metadata pada renja_documents
        Schema::table('renja_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('renja_documents', 'template_id')) {
                $table->foreignId('template_id')->nullable()->after('opd_id')->constrained('document_templates')->nullOnDelete();
            }
            if (!Schema::hasColumn('renja_documents', 'cover_data')) {
                $table->json('cover_data')->nullable()->after('status');
            }
            if (!Schema::hasColumn('renja_documents', 'metadata')) {
                $table->json('metadata')->nullable()->after('cover_data');
            }
        });

        // 4. Tambah tipe seksi pada renja_sections
        Schema::table('renja_sections', function (Blueprint $table) {
            if (!Schema::hasColumn('renja_sections', 'template_section_id')) {
                $table->foreignId('template_section_id')->nullable()->after('document_id')->constrained('template_sections')->nullOnDelete();
            }
            if (!Schema::hasColumn('renja_sections', 'section_type')) {
                $table->string('section_type')->default('subchapter')->after('template_section_id');
            }
            if (!Schema::hasColumn('renja_sections', 'metadata')) {
                $table->json('metadata')->nullable()->after('order_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('renja_sections', function (Blueprint $table) {
            if (Schema::hasColumn('renja_sections', 'template_section_id')) {
                $table->dropForeign(['template_section_id']);
                $table->dropColumn(['template_section_id', 'section_type', 'metadata']);
            }
        });

        Schema::table('renja_documents', function (Blueprint $table) {
            if (Schema::hasColumn('renja_documents', 'template_id')) {
                $table->dropForeign(['template_id']);
                $table->dropColumn(['template_id', 'cover_data', 'metadata']);
            }
        });

        Schema::dropIfExists('template_sections');
        Schema::dropIfExists('document_templates');
    }
};
