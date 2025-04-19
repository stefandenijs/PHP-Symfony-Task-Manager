<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidatorService
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator) {
        $this->validator = $validator;
    }

    /**
     * Validates an object and returns a JsonResponse containing the violation errors.
     *
     * @param $entity
     * @param array|null $constraints
     * @param array|null $groups
     * @return JsonResponse|null
     */
    public function validate($entity, array $constraints = null, array $groups = null): ?JsonResponse
    {
        $errors = $this->validator->validate($entity, $constraints, $groups);
        if (count($errors) > 0) {
            $errorList = [];
            foreach ($errors as $violation) {
                $errorList[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                    'code' => $violation->getCode(),
                ];
            }

            return new JsonResponse($errorList, Response::HTTP_BAD_REQUEST);
        }

        return null;
    }
}