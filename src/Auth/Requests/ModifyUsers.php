<?php namespace Vebto\Auth\Requests;

use Vebto\Base\BaseFormRequest;

class ModifyUsers extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $userId = $this->route('id');

        $rules = [
            'first_name'    => 'alpha|min:2|max:255|nullable',
            'last_name'     => 'alpha|min:2|max:255|nullable',
            'permissions'   => 'array',
            'groups'        => 'array',
            'password'      => 'min:3|max:255',
            'email'         => "email|min:3|max:255|unique:users,email,$userId",
            //'phone'         => "nullable|digits:12",
        ];

        if ($this->method() === 'POST') {
            $rules['email']    = 'required|'.$rules['email'];
            $rules['password'] = 'required|'.$rules['password'];
        }

        return $rules;
    }
}
