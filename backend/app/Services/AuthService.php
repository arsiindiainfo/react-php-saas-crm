<?php

namespace App\Services;

use App\Entities\User;
use App\Exceptions\ApiException;
use App\Exceptions\DuplicateNameException;
use App\Exceptions\ForbiddenRoleException;
use App\Exceptions\RecordNotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Libraries\EmailTemplate;
use App\Libraries\JwtService;
use App\Libraries\ResendMailer;
use App\Models\RefreshTokenModel;
use App\Models\UserModel;
use Config\Crm;

/**
 * Login/refresh/logout + (folded in, per the spec's file tree which has no
 * separate UserService) ADMIN user management: invite, list, update.
 */
class AuthService
{
    private UserModel $users;
    private RefreshTokenModel $refreshTokens;
    private JwtService $jwt;
    private Crm $crm;

    public function __construct()
    {
        $this->users         = new UserModel();
        $this->refreshTokens = new RefreshTokenModel();
        $this->jwt           = new JwtService();
        $this->crm           = config(Crm::class);
    }

    /**
     * @return array{accessToken:string,refreshToken:string,user:User}
     */
    public function login(string $email, string $password): array
    {
        $row = $this->users->authenticate($email);

        if ($row === null || ! password_verify($password, $row['passwordHash'])) {
            throw new UnauthorizedException('Invalid email or password.');
        }

        if ($row['status'] !== 'ACTIVE') {
            throw new UnauthorizedException('This account has been disabled.');
        }

        return $this->issueTokens($row['id']);
    }

    /**
     * @return array{accessToken:string,refreshToken:string,user:User}
     */
    public function refresh(string $rawRefreshToken): array
    {
        $stored = $this->refreshTokens->findValid($rawRefreshToken);

        if ($stored === null) {
            throw new UnauthorizedException('Refresh token is invalid or expired.');
        }

        $this->refreshTokens->revoke($rawRefreshToken);

        return $this->issueTokens((int) $stored['user_id']);
    }

    public function logout(string $rawRefreshToken): void
    {
        $this->refreshTokens->revoke($rawRefreshToken);
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): void
    {
        /** @var User|null $user */
        $user = $this->users->find($userId);

        if ($user !== null && in_array($user->email, $this->crm->demoUserEmails, true)) {
            throw new ForbiddenRoleException('This is a shared demo account and its password cannot be changed.');
        }

        if ($user === null || ! password_verify($currentPassword, $user->password_hash)) {
            throw new UnauthorizedException('Current password is incorrect.');
        }

        $this->users->update($userId, ['password_hash' => password_hash($newPassword, PASSWORD_BCRYPT)]);
    }

    /**
     * @return array{accessToken:string,refreshToken:string,user:User}
     */
    private function issueTokens(int $userId): array
    {
        /** @var User $user */
        $user = $this->users->find($userId);

        $accessToken = $this->jwt->issueAccessToken([
            'sub'  => $user->id,
            'role' => $user->role,
        ]);

        $rawRefreshToken = bin2hex(random_bytes(32));
        $this->refreshTokens->issue($user->id, $rawRefreshToken, $this->crm->refreshTokenTtl);

        return ['accessToken' => $accessToken, 'refreshToken' => $rawRefreshToken, 'user' => $user];
    }

    // ---------------------------------------------------------------
    // ADMIN user management (§15)
    // ---------------------------------------------------------------

    public function inviteUser(string $name, string $email, string $role, ?int $managerId, int $invitedBy): User
    {
        if (! in_array($role, $this->crm->userRoles, true)) {
            throw new ApiException('Unknown role.', 400, 'VALIDATION_ERROR');
        }

        $temporaryPassword = bin2hex(random_bytes(8));
        $passwordHash      = password_hash($temporaryPassword, PASSWORD_BCRYPT);

        $result = $this->users->invite($name, $email, $passwordHash, $role, $managerId, $invitedBy);

        if ($result['statusCode'] === 'DUPLICATE_EMAIL') {
            throw new DuplicateNameException('A user with this email already exists.');
        }

        if ($result['statusCode'] === 'MANAGER_REQUIRED') {
            throw new ApiException('A manager is required for a Sales Rep.', 400, 'VALIDATION_ERROR');
        }

        if ($result['statusCode'] !== 'OK') {
            throw new ApiException($result['message'], 500, 'INTERNAL_ERROR');
        }

        $this->sendInviteEmail($name, $email, $temporaryPassword);

        /** @var User $user */
        $user = $this->users->find($result['userId']);

        return $user;
    }

    // Delivery failure should not block the invite itself succeeding — the
    // inviting ADMIN can still relay the temporary password out-of-band.
    private function sendInviteEmail(string $name, string $email, string $temporaryPassword): void
    {
        $html = EmailTemplate::renderInvite($name, $email, $temporaryPassword);

        try {
            if (! (new ResendMailer())->send($email, 'Your Arsi CRM account', $html)) {
                log_message('error', 'Failed to send invite email to ' . $email);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Failed to send invite email: ' . $e->getMessage());
        }
    }

    /**
     * @return list<User>
     */
    public function listUsers(): array
    {
        return $this->users->orderBy('name', 'ASC')->findAll();
    }

    public function updateUser(int $userId, array $fields, User $actingAdmin): User
    {
        $user = $this->users->find($userId);

        if ($user === null) {
            throw new RecordNotFoundException('user', 'USER_NOT_FOUND');
        }

        $allowed = array_intersect_key($fields, array_flip(['role', 'status', 'manager_id']));

        if (isset($allowed['role']) && ! in_array($allowed['role'], $this->crm->userRoles, true)) {
            throw new ApiException('Unknown role.', 400, 'VALIDATION_ERROR');
        }

        if ($userId === $actingAdmin->id && isset($allowed['status']) && $allowed['status'] === 'DISABLED') {
            throw new ForbiddenRoleException('You cannot disable your own account.');
        }

        if ($user->email === $this->crm->protectedUserEmail && isset($allowed['status']) && $allowed['status'] === 'DISABLED') {
            throw new ForbiddenRoleException('This account cannot be disabled.');
        }

        if (
            in_array($user->email, $this->crm->demoUserEmails, true)
            && isset($allowed['status']) && $allowed['status'] === 'DISABLED'
            && $actingAdmin->email !== $this->crm->protectedUserEmail
        ) {
            throw new ForbiddenRoleException('Only the owner account can disable a demo login account.');
        }

        $this->users->update($userId, $allowed);

        /** @var User $updated */
        $updated = $this->users->find($userId);

        return $updated;
    }

    public function deleteUser(int $userId, User $actingAdmin): void
    {
        $target = $this->users->find($userId);

        if ($target !== null && $target->email === $this->crm->protectedUserEmail) {
            throw new ForbiddenRoleException('This account cannot be removed.');
        }

        if (
            $target !== null
            && in_array($target->email, $this->crm->demoUserEmails, true)
            && $actingAdmin->email !== $this->crm->protectedUserEmail
        ) {
            throw new ForbiddenRoleException('Only the owner account can remove a demo login account.');
        }

        $result = $this->users->deleteUser($userId, $actingAdmin->id);

        if ($result['statusCode'] === 'USER_NOT_FOUND') {
            throw new RecordNotFoundException('user', 'USER_NOT_FOUND');
        }

        if ($result['statusCode'] === 'FORBIDDEN_ROLE') {
            throw new ForbiddenRoleException($result['message']);
        }

        if ($result['statusCode'] === 'USER_HAS_CONTENT') {
            throw new ApiException($result['message'], 409, 'USER_HAS_CONTENT');
        }

        if ($result['statusCode'] !== 'OK') {
            throw new ApiException($result['message'], 500, 'INTERNAL_ERROR');
        }
    }
}
