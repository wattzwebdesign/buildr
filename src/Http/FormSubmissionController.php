<?php

namespace Buildr\Http;

use Buildr\Models\PageNode;
use Illuminate\Http\Request;

class FormSubmissionController
{
    public function __invoke(Request $request, int $node)
    {
        $formNode = PageNode::query()->whereKey($node)->where('type', 'form')->firstOrFail();
        $fields = $formNode->setting('content', 'fields') ?? [];

        $payload = [];
        foreach (array_values($fields) as $i => $field) {
            $value = (string) $request->input("f{$i}", '');
            if (($field['required'] ?? false) && trim($value) === '') {
                return back()->withInput();
            }
            $payload[$field['label'] ?? "Field {$i}"] = $value;
        }

        $formNode->page->formSubmissions()->create([
            'form_key' => $formNode->cssId(),
            'payload' => $payload,
        ]);

        return redirect()->to(url()->previous().'?sent='.$formNode->cssId().'#'.$formNode->cssId());
    }
}
