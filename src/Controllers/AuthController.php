<?php

namespace App\Controllers;

use App\Models\Utilisateur;
use App\Repositories\UtilisateurRepository;
use App\Core\Database;

class AuthController
{
    private UtilisateurRepository $utilisateurRepository;

    public function __construct()
    {
        $this->utilisateurRepository = new UtilisateurRepository();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function inscrire(array $data): bool
    {
        if ($this->utilisateurRepository->emailExiste($data['email'])) {
            return false;
        }

        $utilisateur = new Utilisateur(
            $data['nom'],
            $data['prenom'],
            $data['email'],
            $data['mot_de_passe'],
            $data['telephone']
        );

        $this->utilisateurRepository->save($utilisateur);

        return true;
    }

    public function connecter(string $email, string $motDePasse): bool
    {
        $utilisateur = $this->utilisateurRepository->findByEmail($email);

        if (!$utilisateur) {
            return false;
        }

        if (!$utilisateur->verifierMotDePasse($motDePasse)) {
            return false;
        }

        $_SESSION['utilisateur_id'] = $utilisateur->getId();
        $_SESSION['nom'] = $utilisateur->getNomComplet();

        // Stocker le flag admin en session
        try {
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("SELECT est_admin FROM utilisateurs WHERE id = :id");
            $stmt->execute([':id' => $utilisateur->getId()]);
            $row = $stmt->fetch();
            $_SESSION['est_admin'] = (bool)($row['est_admin'] ?? false);
        } catch (\Exception $e) {
            $_SESSION['est_admin'] = false;
        }

        return true;
    }

    public function deconnecter(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();
        header("Location: /IAGEB/Projet_kay_dem/Projet_Kaay_Dem/index.php");
        exit;
    }
}
