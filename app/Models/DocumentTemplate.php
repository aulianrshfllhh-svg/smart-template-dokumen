<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentTemplate extends Model
{
    protected $table = 'document_templates';

    protected $fillable = [
        'code',
        'name',
        'description',
        'format_config',
        'toolbar_config',
        'validation_config',
        'is_active',
    ];

    protected $casts = [
        'format_config' => 'array',
        'toolbar_config' => 'array',
        'validation_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(TemplateSection::class, 'template_id')->orderBy('sequence');
    }

    public function mainChapters(): HasMany
    {
        return $this->hasMany(TemplateSection::class, 'template_id')
            ->where('section_type', 'chapter')
            ->orderBy('sequence');
    }

    public function frontSections(): HasMany
    {
        return $this->hasMany(TemplateSection::class, 'template_id')
            ->whereIn('section_type', [
                'cover',
                'preface',
                'table_of_contents',
                'list_of_tables',
                'list_of_figures',
                'list_of_charts',
                'list_of_appendices'
            ])
            ->orderBy('sequence');
    }

    public function appendices(): HasMany
    {
        return $this->hasMany(TemplateSection::class, 'template_id')
            ->where('section_type', 'appendix')
            ->orderBy('sequence');
    }
}
