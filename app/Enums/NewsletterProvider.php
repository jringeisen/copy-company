<?php

namespace App\Enums;

enum NewsletterProvider: string
{
    case BuiltIn = 'built_in';
    case Mailchimp = 'mailchimp';
    case Flodesk = 'flodesk';
    case ConvertKit = 'convertkit';
    case Klaviyo = 'klaviyo';
}
