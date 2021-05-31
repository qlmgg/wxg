<?php

namespace App\Rules;

use App\Models\IDParser;
use Illuminate\Contracts\Validation\Rule;

class IdCardRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {

        if (empty($value)) {
            return true;
        }

        $parser = new IDParser();
        $parser->setId($value);

        //身份证号码格式是否正确
        return $parser->isValidate();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return '请输入正确的身份证号码';
    }
}
