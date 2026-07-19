<?php

namespace Buildr\Elements;

use Buildr\Fields\Field;

class Form extends Element
{
    public static string $icon = 'form';

    public static function view(): string
    {
        return 'buildr::elements.form';
    }

    public static function contentFields(): array
    {
        return [
            Field::repeater('fields', [
                Field::select('type', [
                    'text' => 'Text', 'email' => 'Email', 'tel' => 'Phone', 'textarea' => 'Textarea',
                ])->default('text'),
                Field::text('label')->default('Field label'),
                Field::toggle('required'),
            ])->default([
                ['type' => 'text', 'label' => 'Name', 'required' => true],
                ['type' => 'email', 'label' => 'Email', 'required' => true],
                ['type' => 'textarea', 'label' => 'Message', 'required' => false],
            ]),
            Field::text('submit_label')->default('Send Message'),
            Field::text('success')->label('Success message')->default('Thanks — we\'ll be in touch shortly.'),
            Field::text('recipient')->label('Recipient email')->help('Submissions are stored in the database; email delivery hooks in via config later.'),
        ];
    }

    public static function styleFields(): array
    {
        return [
            Field::color('label_color')->section('Labels'),
            Field::color('field_background')->section('Fields'),
            Field::sides('field_radius', ['px'])->section('Fields'),
        ];
    }

    public function css(string $selector): array
    {
        return [
            $selector => ['display' => 'flex', 'flex-direction' => 'column', 'gap' => '14px'],
            "{$selector} :where(label)" => array_filter([
                'font-weight' => '600', 'font-size' => '14px', 'display' => 'block', 'margin-bottom' => '6px',
                'color' => $this->node->setting('style', 'label_color'),
            ]),
            "{$selector} :where(input,textarea)" => array_filter([
                'width' => '100%', 'padding' => '10px 12px', 'border' => '1px solid #d1d5db',
                'border-radius' => '8px', 'font' => 'inherit',
                'background' => $this->node->setting('style', 'field_background'),
            ]),
            "{$selector} :where(.frm-success)" => ['padding' => '14px 16px', 'border-radius' => '8px', 'background' => '#e7f5ec', 'color' => '#1e7c4d', 'font-weight' => '600'],
        ];
    }
}
