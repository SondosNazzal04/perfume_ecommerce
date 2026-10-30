
<?php

class User
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findByEmail(string $email): ?array
    {

        $sql = "SELECT * FROM users WHERE email= ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT id,name,email,phone,role,email_verified_at FROM users WHERE id =? ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$id]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function register(
        string $name,
        string $email,
        string $password,
        string $phone
    ): int {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name , email , password ,phone)
        VALUES (?,?,?,?)";
        $stmt = $this->db->prepare($sql);

        $stmt->execute([$name, $email, $hashedPassword, $phone]);



        return (int) $this->db->lastInsertId();
    }

    public function markEmailAsVerified(int $userid): bool
    {

        $sql = "UPDATE users 
    SET email_verified_at=NOW()
    WHERE id=? AND email_verified_at IS NULL";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$userid]);
        return $stmt->rowCount() > 0;
    }
    public function findByGoogleId(string $googleId): ?array
    {
        $sql = "SELECT * FROM users WHERE google_id = ?";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$googleId]);


        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function createGoogleUser(
        string $name,
        string $email,
        string $googleId
    ): int {
        $sql = "INSERT INTO users
            (name, email, google_id, email_verified_at)
            VALUES (?, ?, ?, NOW())";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$name, $email, $googleId]);

        return (int) $this->db->lastInsertId();
    }
    public function updatePhone(int $userId, string $phone): bool
    {
        $sql = "UPDATE users SET phone=? WHERE id=?";
        $stmt = $this->db->prepare($sql);

        $stmt->execute([$phone, $userId]);

        return $stmt->rowCount() > 0;
    }
    public function login(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);

        if (
            !$user || !is_string($user['password']) ||
            !password_verify($password, $user['password'])
        ) {
            return null;
        }

        if ($user['email_verified_at'] === null) {
            return null;
        }

        return [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
    }

    public function linkGoogleAccount(
        int $userId,
        string $googleId
    ): bool {
        $sql = "UPDATE users
            SET google_id = ?
            WHERE id = ?
            AND google_id IS NULL";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$googleId, $userId]);

        return $stmt->rowCount() > 0;
    }
}
