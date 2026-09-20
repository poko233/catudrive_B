<?php

declare(strict_types=1);
namespace App\Modules\Encomienda\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreClienteRequest extends FormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['nombres'=>['required','string','max:255'],'apellido_paterno'=>['required','string','max:255'],'apellido_materno'=>['nullable','string','max:255'],'ci'=>['nullable','string','max:255','unique:cliente,ci'],'telefono'=>['nullable','string','max:30']]; }
}
