<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Http\Requests\ClientPortal\Quotes;

use App\Http\ViewComposers\PortalComposer;
use Illuminate\Foundation\Http\FormRequest;
use function auth;

class ProcessQuotesInBulkRequest extends FormRequest
{
    public function authorize()
    {
        return auth('contact')->user()->company->enabled_modules & PortalComposer::MODULE_QUOTES;
    }

    public function rules()
    {
        return [
            'quotes' => ['array'],
        ];
    }
}
