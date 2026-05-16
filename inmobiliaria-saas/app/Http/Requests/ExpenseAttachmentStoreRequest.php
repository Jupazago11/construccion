<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseAttachmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'max:204800', 'mimetypes:image/jpeg,image/png,image/webp,application/pdf,video/mp4,video/quicktime,video/webm,video/x-msvideo,video/mpeg,video/3gpp,video/3gpp2'],
        ];
    }
}
