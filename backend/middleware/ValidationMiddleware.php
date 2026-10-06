<?php

declare(strict_types=1);

namespace Middleware;

use Core\Request;
use Core\Response;

/**
 * ValidationMiddleware — factory for input validation middleware.
 *
 * Usage (in a route registration):
 *   ValidationMiddleware::make([
 *       'email'    => 'required|email|max:255',
 *       'password' => 'required|min:8|max:128',
 *   ])
 *
 * Supported rules: required, email, min:{n}, max:{n}, confirmed, alpha_num
 */
class ValidationMiddleware
{
    /**
     * Build and return a callable middleware that validates $rules.
     *
     * @param  array<string, string> $rules  field => pipe-separated rules
     */
    public static function make(array $rules): callable
    {
        return function (Request $request, Response $response, callable $next) use ($rules): void {
            $errors = self::validate($request->all(), $rules);

            if (!empty($errors)) {
                $response->error('Validation failed.', 422, $errors);
                return;
            }

            $next();
        };
    }

    /** @return array<string, string[]>  field => list of error messages */
    private static function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value      = $data[$field] ?? null;
            $fieldRules = explode('|', $ruleString);

            foreach ($fieldRules as $rule) {
                [$ruleName, $param] = array_pad(explode(':', $rule, 2), 2, null);

                $error = match ($ruleName) {
                    'required'  => self::ruleRequired($value),
                    'email'     => self::ruleEmail($value),
                    'min'       => self::ruleMin($value, (int) $param),
                    'max'       => self::ruleMax($value, (int) $param),
                    'confirmed' => self::ruleConfirmed($field, $value, $data),
                    'alpha_num' => self::ruleAlphaNum($value),
                    default     => null,
                };

                if ($error !== null) {
                    $errors[$field][] = sprintf($error, $field, $param);
                    // Stop checking this field after first failure if required failed
                    if ($ruleName === 'required') {
                        break;
                    }
                }
            }
        }

        return $errors;
    }

    // ─── Individual rule implementations ──────────────────────────────────

    private static function ruleRequired(mixed $value): ?string
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            return 'The %s field is required.';
        }
        return null;
    }

    private static function ruleEmail(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;  // Let required handle empty
        }
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return 'The %s field must be a valid email address.';
        }
        return null;
    }

    private static function ruleMin(mixed $value, int $min): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (strlen((string) $value) < $min) {
            return "The %s field must be at least {$min} characters.";
        }
        return null;
    }

    private static function ruleMax(mixed $value, int $max): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (strlen((string) $value) > $max) {
            return "The %s field must not exceed {$max} characters.";
        }
        return null;
    }

    private static function ruleConfirmed(string $field, mixed $value, array $data): ?string
    {
        $confirmField = $field . '_confirmation';
        if ($value !== ($data[$confirmField] ?? null)) {
            return "The %s field confirmation does not match.";
        }
        return null;
    }

    private static function ruleAlphaNum(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!ctype_alnum((string) $value)) {
            return 'The %s field may only contain letters and numbers.';
        }
        return null;
    }
}
