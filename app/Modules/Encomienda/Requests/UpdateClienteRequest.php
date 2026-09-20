<?php

declare(strict_types=1);
namespace App\Modules\Encomienda\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateClienteRequest extends FormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { $id=(int)$this->route('cliente'); return ['nombres'=>['sometimes','required','string','max:255'],'apellido_paterno'=>['sometimes','required','string','max:255'],'apellido_materno'=>['sometimes','nullable','string','max:255'],'ci'=>['sometimes','nullable','string','max:255',Rule::unique('cliente','ci')->ignore($id)],'telefono'=>['sometimes','nullable','string','max:30']]; }
}
