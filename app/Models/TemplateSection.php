<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateSection extends Model
{
    protected $table = 'template_sections';

    protected $fillable = [
        'template_id',
        'parent_id',
        'section_type',
        'code',
        'title',
        'sequence',
        'is_required',
        'is_editable',
        'is_automatic',
        'page_break_before',
        'page_break_after',
        'format_config',
    ];

    protected $casts = [
        'format_config' => 'array',
        'is_required' => 'boolean',
        'is_editable' => 'boolean',
        'is_automatic' => 'boolean',
        'page_break_before' => 'boolean',
        'page_break_after' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TemplateSection::class, 'parent_id');
    }

    public function subSections(): HasMany
    {
        return $this->hasMany(TemplateSection::class, 'parent_id')->orderBy('sequence');
    }

    public function isDescendantOf(int $ancestorId): bool
    {
        $current = $this->parent;
        while ($current) {
            if ($current->id === $ancestorId) {
                return true;
            }
            $current = $current->parent;
        }
        return false;
    }
}
