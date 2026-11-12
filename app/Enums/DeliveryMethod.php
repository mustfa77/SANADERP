<?php

namespace App\Enums;

/**
 * How a recruited worker is handed over to the client.
 */
enum DeliveryMethod: string
{
    /** The client collects the worker at the office. */
    case Office = 'office';

    /** The worker is taken to the client's home (home delivery fee). */
    case Home = 'home';

    public function label(): string
    {
        return __("recruitment.delivery_methods.{$this->value}");
    }
}
