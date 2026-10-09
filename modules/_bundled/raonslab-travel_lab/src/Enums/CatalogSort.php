<?php

namespace Modules\Raonslab\TravelLab\Enums;

enum CatalogSort: string
{
    case RECOMMENDED = 'recommended';
    case PRICE_ASC = 'price_asc';
    case PRICE_DESC = 'price_desc';
    case DEPARTURE_ASC = 'departure_asc';
}
