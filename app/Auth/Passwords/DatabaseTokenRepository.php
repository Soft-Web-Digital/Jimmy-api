<?php

namespace App\Auth\Passwords;

use Illuminate\Auth\Passwords\DatabaseTokenRepository as PasswordsDatabaseTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword;

class DatabaseTokenRepository extends PasswordsDatabaseTokenRepository
{
    /**
     * Create a new token record.
     *
     * @param \Illuminate\Contracts\Auth\CanResetPassword $user
     * @return string
     */
    public function create(CanResetPassword $user): string
    {
        $email = $user->getEmailForPasswordReset();

        $this->deleteExisting($user);

        // We will create a new, random code for the user so that we can e-mail them
        // a safe code for their password reset. Then we will insert a record in
        // the database so that we can verify the token within the actual reset.
        $token = $this->createNewToken();

        $this->getTable()->insert($this->getNewPayload($email, $token));

        return $token;
    }

    /**
     * Create a new token for the user.
     *
     * @return string
     */
    public function createNewToken(): string
    {
        return (string) mt_rand(100000, 999999);
    }

    /**
     * Build the record payload for the table.
     *
     * @param string $email
     * @param string $token
     * @return array<string, mixed>
     */
    protected function getNewPayload($email, $token): array
    {
        return [
            'email' => $email,
            'token' => $this->hasher->make($token),
            'created_at' => now(),
        ];
    }

    /**
     * Determine if a token record exists and is valid.
     *
     * @param \Illuminate\Contracts\Auth\CanResetPassword $user
     * @param string $token
     * @return bool
     */
    public function exists(CanResetPassword $user, $token): bool
    {
        $record = (array) $this->getTable()
            ->where('email', $user->getEmailForPasswordReset())
            ->first();

        return $record &&
            !$this->tokenExpired($record['created_at']) &&
            $this->hasher->check($token, $record['token']);
    }

    /**
     * Getter for used table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getUsedTable(): \Illuminate\Database\Query\Builder
    {
        return $this->getTable();
    }
}
