<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;

interface ValidatorServiceInterface
{
    public function validate($entity, array $constraints = null, array $groups = null): null|JsonResponse;
}