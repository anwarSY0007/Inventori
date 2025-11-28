<?php

namespace App\Http\Requests\Categories;

use App\Helpers\ResponseHelpers;
use App\Models\Category;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CategoryUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $slug = $this->route('category');

        $category = Category::where('slug', $slug)->first();
        $id = $category ? $category->id : null;
        return [
            'name' => 'required|string|max:255',
            // Ignore unique slug untuk ID saat ini
            'slug' => 'required|string|unique:categories,slug,' . $id,
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'tagline' => 'nullable|string|max:255'
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ResponseHelpers::jsonResponse(false, $validator->errors()->first(), $validator->errors(), 422)
        );
    }
}
