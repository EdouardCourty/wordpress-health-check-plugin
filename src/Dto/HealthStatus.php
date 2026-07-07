<?php

declare(strict_types=1);

namespace HealthCheck\Dto;

enum HealthStatus: string
{
    case Ok = 'ok';
    case Ko = 'ko';
}
